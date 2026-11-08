<?php
/**
 * AI & LLMs module: /llms.txt, /llms-full.txt, per-page Markdown (<url>.md) and AI-crawler policy.
 *
 * Front-end cost on a normal page view: one parse_url() + a couple of string compares (wp_loaded)
 * and a cheap check in wp_head for the <link rel="alternate" type="text/markdown">.
 * Everything heavy (builders, HTML→Markdown, admin UI) lives in inc/llms/ and is loaded lazily.
 *
 * - Files are built by a single debounced cron event (60 s after the last content change) and stored in
 *   uploads/kmpr-llms/ (not the web root, so they always go through PHP with correct headers).
 * - Served with Content-Type text/plain; charset=utf-8, Last-Modified/ETag (+304), X-Robots-Tag: noindex.
 * - robots.txt groups for AI crawlers are emitted by inc/seo.php's single robots_txt filter via kmpr_llms_robots_block().
 *
 * @package KMPR
 */

if (!defined('ABSPATH')) {
    exit;
}

class KMPR_LLMs_Manager
{
    const VERSION      = '3.0.0';
    const OPT_SETTINGS = 'kmpr_llms_settings';
    const OPT_STATE    = 'kmpr_llms_state';
    const OPT_VERSION  = 'kmpr_llms_version';
    const OPT_LOCK     = 'kmpr_llms_lock';
    const HOOK         = 'kmpr_llms_rebuild';
    const DELAY        = 60;

    /** @var bool Current request asked for Markdown. */
    private $md_request = false;

    /** @var bool Rebuild already scheduled during this request. */
    private $scheduled = false;

    /** @var array|null */
    private static $settings = null;

    public function __construct()
    {
        add_action('wp_loaded', array($this, 'route'), 0);
        add_action('template_redirect', array($this, 'serve_markdown'), 1);
        add_action('wp_head', array($this, 'head_link'), 4);
        add_action('init', array($this, 'maybe_upgrade'), 99);

        // Debounced rebuild on content changes.
        add_action(self::HOOK, array($this, 'rebuild'));
        add_action('transition_post_status', array($this, 'on_status'), 10, 3);
        add_action('before_delete_post', array($this, 'on_delete'));
        add_action('acf/save_post', array($this, 'on_acf_save'), 30);
        foreach (array('created_term', 'edited_term', 'delete_term') as $hook) {
            add_action($hook, array($this, 'on_term'), 10, 3);
        }

        if (is_admin()) {
            require_once __DIR__ . '/llms/admin.php';
            new KMPR_LLMs_Admin($this);
        }
    }

    /* =====================================================================
     * Settings
     * ================================================================== */

    /** AI crawler user-agents: token => [operator, purpose label, kind]. */
    public static function bots()
    {
        return array(
            'GPTBot'            => array('OpenAI', 'جمع‌آوری داده برای آموزش مدل', 'training'),
            'OAI-SearchBot'     => array('OpenAI', 'جستجوی ChatGPT (نمایش در نتایج)', 'search'),
            'ChatGPT-User'      => array('OpenAI', 'باز کردن صفحه به درخواست کاربر', 'user'),
            'ClaudeBot'         => array('Anthropic', 'جمع‌آوری داده برای آموزش مدل', 'training'),
            'Claude-SearchBot'  => array('Anthropic', 'جستجوی Claude (نمایش در نتایج)', 'search'),
            'PerplexityBot'     => array('Perplexity', 'موتور پاسخ Perplexity', 'search'),
            'Google-Extended'   => array('Google', 'استفاده در Gemini / آموزش (روی ایندکس گوگل اثری ندارد)', 'training'),
            'Applebot-Extended' => array('Apple', 'آموزش مدل‌های Apple (روی Siri/Spotlight اثری ندارد)', 'training'),
            'CCBot'             => array('Common Crawl', 'دیتاست عمومی که در آموزش بیشتر مدل‌ها استفاده می‌شود', 'training'),
            'Bytespider'        => array('ByteDance', 'جمع‌آوری داده برای آموزش مدل', 'training'),
        );
    }

    public static function defaults()
    {
        return array(
            'summary'  => '',
            'intro'    => '',
            'txt'      => array('about' => 1, 'services' => 1, 'courses' => 1, 'portfolio' => 1, 'games' => 1, 'optional' => 1),
            'full'     => array('about' => 1, 'services' => 1, 'courses' => 1, 'portfolio' => 1, 'games' => 1, 'faq' => 1),
            'archived' => 1,
            'md'       => 1,
            'link_md'  => 0,
            'bots'     => array_fill_keys(array_keys(self::bots()), 'allow'),
        );
    }

    public static function settings()
    {
        if (self::$settings === null) {
            $saved = get_option(self::OPT_SETTINGS, array());
            $saved = is_array($saved) ? $saved : array();
            $d     = self::defaults();
            $s     = array_merge($d, $saved);
            foreach (array('txt', 'full', 'bots') as $k) {
                $s[$k] = array_merge($d[$k], is_array($saved[$k] ?? null) ? $saved[$k] : array());
            }
            self::$settings = $s;
        }
        return self::$settings;
    }

    public static function save_settings($s)
    {
        update_option(self::OPT_SETTINGS, $s, true);
        self::$settings = null;
    }

    public static function state()
    {
        $s = get_option(self::OPT_STATE, array());
        return is_array($s) ? $s : array();
    }

    /* =====================================================================
     * Shared helpers
     * ================================================================== */

    /** Post types that have a Markdown twin and appear in the files. */
    public static function md_types()
    {
        return array('post', 'page', 'services', 'portfolio', 'game');
    }

    /** Published, not password-protected, not noindex (Yoast per-post / per-type), not the untranslated privacy page. */
    public static function is_public($p)
    {
        static $titles = null;
        $p = $p instanceof WP_Post ? $p : get_post($p);
        if (!$p || $p->post_status !== 'publish' || $p->post_password !== '' || !in_array($p->post_type, self::md_types(), true)) {
            return false;
        }
        $flag = get_post_meta($p->ID, '_yoast_wpseo_meta-robots-noindex', true);
        if ($flag === '1') {
            return false;
        }
        if ($flag !== '2') {
            $titles = $titles ?? (array) get_option('wpseo_titles', array());
            if (!empty($titles['noindex-' . $p->post_type])) {
                return false;
            }
        }
        if ($p->post_type === 'page' && (int) $p->ID === (int) get_option('wp_page_for_privacy_policy') && !preg_match('/\p{Arabic}/u', $p->post_content)) {
            return false;
        }
        return true;
    }

    /** Markdown URL for a page URL: /blog/foo/ → /blog/foo.md, home → /index.md. */
    public static function md_url($url)
    {
        $url  = preg_replace('/[?#].*$/', '', (string) $url);
        $home = trailingslashit(home_url('/'));
        if (trailingslashit($url) === $home) {
            return $home . 'index.md';
        }
        return untrailingslashit($url) . '.md';
    }

    /** Public URLs (untrailingslashed) used by the link validator. */
    public static function known_urls()
    {
        $urls = array(untrailingslashit(home_url('/')) => 1, untrailingslashit(home_url('/llms.txt')) => 1, untrailingslashit(home_url('/llms-full.txt')) => 1);
        $ids  = get_posts(array('post_type' => self::md_types(), 'post_status' => 'publish', 'posts_per_page' => 1000, 'fields' => 'ids', 'no_found_rows' => true));
        foreach ($ids as $id) {
            if (self::is_public($id)) {
                $u = untrailingslashit(get_permalink($id));
                $urls[$u] = 1;
                $urls[untrailingslashit(self::md_url($u . '/'))] = 1;
            }
        }
        foreach (array('services', 'portfolio', 'game') as $pt) {
            if (post_type_exists($pt) && ($l = get_post_type_archive_link($pt))) {
                $urls[untrailingslashit($l)] = 1;
                $urls[untrailingslashit(self::md_url($l))] = 1;
            }
        }
        foreach (array('category', 'portfolio_categories', 'game_category') as $tax) {
            if (!taxonomy_exists($tax)) {
                continue;
            }
            foreach (get_terms(array('taxonomy' => $tax, 'hide_empty' => true)) as $t) {
                $l = get_term_link($t);
                if (!is_wp_error($l)) {
                    $urls[untrailingslashit($l)] = 1;
                    $urls[untrailingslashit(self::md_url($l))] = 1;
                }
            }
        }
        $urls[untrailingslashit(self::md_url(home_url('/')))] = 1;
        return $urls;
    }

    /** Storage directory (outside the web root's direct path so the web server never serves stale copies). */
    public static function dir()
    {
        $up = wp_upload_dir(null, false);
        return trailingslashit($up['basedir']) . 'kmpr-llms';
    }

    public static function path($file)
    {
        return self::dir() . '/' . $file;
    }

    public static function builder()
    {
        require_once __DIR__ . '/llms/builder.php';
        return new KMPR_LLMs_Builder(self::settings());
    }

    /* =====================================================================
     * Routing (cheap check on every front-end request)
     * ================================================================== */

    private static function base_path()
    {
        $p = (string) wp_parse_url(home_url('/'), PHP_URL_PATH);
        return rtrim($p, '/');
    }

    public function route()
    {
        if (is_admin() || wp_doing_ajax() || wp_doing_cron() || (defined('REST_REQUEST') && REST_REQUEST)) {
            return;
        }
        $uri  = (string) ($_SERVER['REQUEST_URI'] ?? '');
        $path = (string) wp_parse_url($uri, PHP_URL_PATH);
        if ($path === '') {
            return;
        }
        $base = self::base_path();
        $rel  = ($base !== '' && strpos($path, $base) === 0) ? substr($path, strlen($base)) : $path;

        if ($rel === '/llms.txt' || $rel === '/llms-full.txt') {
            $this->serve_file(ltrim($rel, '/'));
        }

        $s = self::settings();
        if (empty($s['md'])) {
            return;
        }
        if (substr($rel, -3) === '.md') {
            $new   = preg_replace('#(/index(\.html)?)?\.md$#', '', $rel);
            $query = (string) wp_parse_url($uri, PHP_URL_QUERY);
            $_SERVER['REQUEST_URI'] = $base . ($new === '' ? '/' : $new) . ($query !== '' ? '?' . $query : '');
            $this->md_request       = true;
        } elseif (isset($_GET['format']) && $_GET['format'] === 'md') { // phpcs:ignore WordPress.Security.NonceVerification
            $this->md_request = true;
        }
        if ($this->md_request) {
            add_filter('redirect_canonical', '__return_false', 99);
        }
    }

    /** True when the client already has this version. */
    private static function not_modified($etag, $mtime)
    {
        $inm = trim(wp_unslash((string) ($_SERVER['HTTP_IF_NONE_MATCH'] ?? ''))); // WP adds magic quotes to $_SERVER
        if ($inm !== '') {
            return in_array($etag, array_map('trim', explode(',', str_replace('W/', '', $inm))), true);
        }
        $ims = (string) ($_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? '');
        return $mtime && $ims !== '' && strtotime($ims) >= $mtime;
    }

    /** Send a text response with validators and exit. */
    private function send($body, $type, $mtime, $etag, $canonical = '', $is_file = false)
    {
        if (function_exists('header_remove')) {
            header_remove('Expires');
            header_remove('Pragma');
            header_remove('X-Pingback');
        }
        status_header(200);
        header('Content-Type: ' . $type . '; charset=utf-8');
        header('X-Robots-Tag: noindex');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: public, max-age=3600');
        header('ETag: ' . $etag);
        if ($mtime) {
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $mtime) . ' GMT');
        }
        if ($canonical) {
            header('Link: <' . esc_url_raw($canonical) . '>; rel="canonical"', false);
        }
        if (self::not_modified($etag, $mtime)) {
            status_header(304);
            exit;
        }
        if (!$is_file) {
            header('Content-Length: ' . strlen($body));
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'HEAD') {
                echo $body; // phpcs:ignore WordPress.Security.EscapeOutput -- plain text / markdown response
            }
        } else {
            // $body = file path.
            header('Content-Length: ' . filesize($body));
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'HEAD') {
                readfile($body);
            }
        }
        exit;
    }

    private function serve_file($name)
    {
        $path  = self::path($name);
        $stale = !is_readable($path);
        if (!$stale) {
            // Cron not running (DISABLE_WP_CRON without a real cron): rebuild lazily once the event is 5 min overdue.
            $next  = wp_next_scheduled(self::HOOK);
            $stale = $next && $next < time() - 300;
        }
        if ($stale) {
            $this->rebuild();
            clearstatcache(true, $path);
        }
        if (is_readable($path)) {
            $mtime = (int) filemtime($path);
            $this->send($path, 'text/plain', $mtime, sprintf('"%x-%x"', $mtime, filesize($path)), '', true);
        }
        // Storage not writable: build in memory.
        $b    = self::builder();
        $body = $name === 'llms.txt' ? $b->llms_txt() : $b->llms_full();
        $this->send($body, 'text/plain', 0, '"' . md5($body) . '"');
    }

    /** Markdown twin of the current page (template_redirect, after WP resolved the query). */
    public function serve_markdown()
    {
        if (!$this->md_request) {
            return;
        }
        $doc = is_404() ? null : self::builder()->current_md();
        if (!$doc) {
            status_header(404);
            nocache_headers();
            header('Content-Type: text/markdown; charset=utf-8');
            header('X-Robots-Tag: noindex');
            echo "# ۴۰۴\n\nاین آدرس نسخه Markdown ندارد یا منتشر نشده است. فهرست صفحه‌ها: " . esc_url_raw(home_url('/llms.txt')) . "\n";
            exit;
        }
        $this->send($doc['body'], 'text/markdown', (int) $doc['modified'], '"' . md5($doc['body']) . '"', $doc['canonical']);
    }

    /** <link rel="alternate" type="text/markdown"> on pages that have a Markdown twin. */
    public function head_link()
    {
        $s = self::settings();
        if (empty($s['md']) || is_paged() || is_404() || is_search()) {
            return;
        }
        $url = '';
        if (is_front_page()) {
            $url = home_url('/');
        } elseif (is_singular(self::md_types())) {
            $p   = get_queried_object();
            $url = self::is_public($p) ? get_permalink($p) : '';
        } elseif (is_post_type_archive(array('services', 'portfolio', 'game'))) {
            $pt  = get_query_var('post_type');
            $url = get_post_type_archive_link(is_array($pt) ? reset($pt) : $pt);
        } elseif (is_category()) {
            $t   = get_queried_object();
            $url = ($t instanceof WP_Term && $t->count && (int) $t->term_id !== (int) get_option('default_category')) ? get_term_link($t) : '';
        } elseif (is_home() && get_option('page_for_posts')) {
            $url = get_permalink((int) get_option('page_for_posts'));
        }
        if ($url && !is_wp_error($url)) {
            echo '<link rel="alternate" type="text/markdown" title="Markdown" href="' . esc_url(self::md_url($url)) . '">' . "\n";
        }
    }

    /* =====================================================================
     * Rebuild scheduling (debounced)
     * ================================================================== */

    public function schedule()
    {
        if ($this->scheduled) {
            return;
        }
        $this->scheduled = true;
        $when = time() + self::DELAY;
        $next = wp_next_scheduled(self::HOOK);
        if ($next && $next >= $when - 5) {
            return;
        }
        if ($next) {
            wp_unschedule_event($next, self::HOOK);
        }
        wp_schedule_single_event($when, self::HOOK);
    }

    public function on_status($new, $old, $post)
    {
        if (($new === 'publish' || $old === 'publish') && $post instanceof WP_Post && in_array($post->post_type, self::md_types(), true)
            && !wp_is_post_revision($post) && !wp_is_post_autosave($post)) {
            $this->schedule();
        }
    }

    public function on_delete($post_id)
    {
        $p = get_post($post_id);
        if ($p && $p->post_status === 'publish' && in_array($p->post_type, self::md_types(), true)) {
            $this->schedule();
        }
    }

    public function on_acf_save($post_id)
    {
        if (is_numeric($post_id)) {
            $p = get_post((int) $post_id);
            if ($p && $p->post_status === 'publish' && in_array($p->post_type, self::md_types(), true)) {
                $this->schedule();
            }
            return;
        }
        // Options pages (contact, resume, games…) and term fields (course intro/title).
        if (is_string($post_id) && preg_match('/^(options?|term_|category_)/', $post_id)) {
            $this->schedule();
        }
    }

    public function on_term($term_id, $tt_id, $taxonomy)
    {
        if (in_array($taxonomy, array('category', 'portfolio_categories', 'game_category'), true)) {
            $this->schedule();
        }
    }

    /** Build both files; write only when content changed (keeps Last-Modified/ETag stable for crawlers). */
    public function rebuild()
    {
        if (!add_option(self::OPT_LOCK, time(), '', false)) {
            if (time() - (int) get_option(self::OPT_LOCK) < 120) {
                return false;
            }
            update_option(self::OPT_LOCK, time(), false);
        }
        $t0 = microtime(true);
        if (function_exists('set_time_limit')) {
            @set_time_limit(120); // phpcs:ignore
        }
        $error = '';
        $b     = null;
        try {
            $b    = self::builder();
            $txt  = $b->llms_txt();
            $full = $b->llms_full();
        } catch (Throwable $e) {
            $error = $e->getMessage();
            $txt   = $full = '';
        }

        $written = false;
        if ($error === '') {
            $dir = self::dir();
            if (!is_dir($dir)) {
                wp_mkdir_p($dir);
            }
            if (!file_exists($dir . '/index.php')) {
                @file_put_contents($dir . '/index.php', "<?php\n// Silence is golden.\n"); // phpcs:ignore
                @file_put_contents($dir . '/.htaccess', "<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n"); // phpcs:ignore
            }
            $written = self::write($dir . '/llms.txt', $txt) && self::write($dir . '/llms-full.txt', $full);
            if (!$written) {
                $error = 'پوشه ' . $dir . ' قابل نوشتن نیست؛ فایل‌ها هنگام درخواست در حافظه ساخته می‌شوند.';
            }
        }

        $words = function ($t) {
            return (int) preg_match_all('/[\p{L}\p{N}]+/u', $t);
        };
        update_option(self::OPT_STATE, array(
            'generated'   => time(),
            'duration'    => (int) round((microtime(true) - $t0) * 1000),
            'error'       => $error,
            'txt'         => array('bytes' => strlen($txt), 'words' => $words($txt), 'chars' => mb_strlen($txt)),
            'full'        => array('bytes' => strlen($full), 'words' => $words($full), 'chars' => mb_strlen($full)),
            'txt_counts'  => $b ? ($b->stats['txt_counts'] ?? array()) : array(),
            'full_counts' => $b ? ($b->stats['full_counts'] ?? array()) : array(),
            'full_sizes'  => $b ? ($b->stats['full_sizes'] ?? array()) : array(),
            'truncated'   => $b ? (int) ($b->stats['truncated'] ?? 0) : 0,
            'version'     => self::VERSION,
        ), false);

        wp_clear_scheduled_hook(self::HOOK);
        delete_option(self::OPT_LOCK);
        return $written;
    }

    private static function write($file, $content)
    {
        if (is_readable($file) && md5_file($file) === md5($content)) {
            return true;
        }
        $tmp = $file . '.' . wp_generate_password(6, false) . '.tmp';
        if (@file_put_contents($tmp, $content, LOCK_EX) === false) { // phpcs:ignore
            return false;
        }
        if (!@rename($tmp, $file)) { // phpcs:ignore
            @unlink($tmp); // phpcs:ignore
            return false;
        }
        return true;
    }

    /* =====================================================================
     * One-time upgrade (runs once per VERSION bump)
     * ================================================================== */

    public function maybe_upgrade()
    {
        if (get_option(self::OPT_VERSION) === self::VERSION) {
            return;
        }
        // v2 stats options → state.
        $state = self::state();
        if (!$state && ($t = (int) get_option('kmpr_llms_last_generated'))) {
            update_option(self::OPT_STATE, array('generated' => $t), false);
        }
        foreach (array('kmpr_llms_last_generated', 'kmpr_llms_file_size', 'kmpr_llms_full_file_size', 'kmpr_llms_word_count', 'kmpr_llms_full_word_count') as $old) {
            delete_option($old);
        }
        delete_transient('kmpr_llms_last_regenerated');
        if (get_option(self::OPT_SETTINGS) === false) {
            add_option(self::OPT_SETTINGS, self::defaults(), '', true);
        }
        // v2 wrote the files into the web root, where the web server serves them statically
        // (no charset, no X-Robots-Tag, never refreshed by PHP). Remove our copies only.
        foreach (array('llms.txt', 'llms-full.txt') as $f) {
            $p = ABSPATH . $f;
            if (is_file($p) && is_writable($p)) {
                $head = (string) file_get_contents($p, false, null, 0, 400);
                if (strpos($head, 'Korosh Mpr') !== false) {
                    @unlink($p); // phpcs:ignore
                }
            }
        }
        // v2 registered ^llms\.txt$ rewrite rules; drop them once.
        flush_rewrite_rules(false);
        update_option(self::OPT_VERSION, self::VERSION, true);
        $this->schedule();
    }

    /* =====================================================================
     * robots.txt (called from inc/seo.php — the single robots_txt filter)
     * ================================================================== */

    /** Allow/Disallow rules of the `User-agent: *` group (mirrored for allowed AI bots, since a named group replaces `*`). */
    private static function star_rules($robots)
    {
        $rules   = array();
        $in_star = false;
        $seen    = false;
        foreach (preg_split('/\r\n|\r|\n/', (string) $robots) as $line) {
            $l = trim(preg_replace('/#.*$/', '', $line));
            if ($l === '') {
                continue;
            }
            if (preg_match('/^user-agent\s*:\s*(.+)$/i', $l, $m)) {
                if ($seen) {
                    $in_star = false;
                    $seen    = false;
                }
                $in_star = $in_star || trim($m[1]) === '*';
                continue;
            }
            if (preg_match('/^(allow|disallow)\s*:\s*(.*)$/i', $l, $m)) {
                $seen = true;
                if ($in_star) {
                    $rules[] = ucfirst(strtolower($m[1])) . ': ' . trim($m[2]);
                }
            }
        }
        return $rules ?: array('Allow: /');
    }

    public static function robots_block($robots)
    {
        $s     = self::settings();
        $site  = untrailingslashit(home_url('/'));
        $allow = array();
        $deny  = array();
        foreach (array_keys(self::bots()) as $ua) {
            if (($s['bots'][$ua] ?? 'allow') === 'deny') {
                $deny[] = $ua;
            } else {
                $allow[] = $ua;
            }
        }
        $out  = "\n# AI crawlers (policy: WordPress admin > AI & LLMs)\n";
        $out .= '# LLM-friendly summary (llmstxt.org): ' . $site . "/llms.txt\n";
        $out .= '# Full content for LLMs: ' . $site . "/llms-full.txt\n";
        if ($allow) {
            foreach ($allow as $ua) {
                $out .= 'User-agent: ' . $ua . "\n";
            }
            $out .= implode("\n", self::star_rules($robots)) . "\n\n";
        }
        if ($deny) {
            foreach ($deny as $ua) {
                $out .= 'User-agent: ' . $ua . "\n";
            }
            $out .= "Disallow: /\n";
        }
        return rtrim($out) . "\n";
    }
}

/** robots.txt block for AI crawlers (used by inc/seo.php). */
function kmpr_llms_robots_block($robots = '')
{
    return KMPR_LLMs_Manager::robots_block($robots);
}

$GLOBALS['kmpr_llms'] = new KMPR_LLMs_Manager();
