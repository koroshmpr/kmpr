<?php
/**
 * Content builders for /llms.txt, /llms-full.txt and per-page Markdown (<url>.md).
 *
 * Loaded lazily by KMPR_LLMs_Manager — only during a (scheduled) rebuild, an admin preview/validation
 * or a .md request. Nothing here runs on a normal page view.
 *
 * Everything is read from the site's own content (posts, ACF fields, options); no numbers are invented.
 *
 * @package KMPR
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/markdown.php';

class KMPR_LLMs_Builder
{
    /** Byte caps (UTF-8). */
    const FULL_CAP    = 1400000;
    const CAP_LESSON  = 45000;
    const CAP_SERVICE = 20000;
    const CAP_PF      = 9000;
    const CAP_PAGE    = 40000;

    /** @var array Settings (merged with defaults). */
    private $s;

    /** @var array Build statistics for the admin page. */
    public $stats = array();

    /** @var array Memo of per-request lookups. */
    private $memo = array();

    public function __construct($settings)
    {
        $this->s = $settings;
    }

    /* =====================================================================
     * Small helpers
     * ================================================================== */

    /** One-line plain text, cut on a word boundary. */
    public static function short($text, $max = 160)
    {
        $text = html_entity_decode(wp_strip_all_tags(strip_shortcodes((string) $text)), ENT_QUOTES, 'UTF-8');
        $text = trim(preg_replace('/\s+/u', ' ', $text));
        if (mb_strlen($text) <= $max) {
            return $text;
        }
        $cut = mb_substr($text, 0, $max - 1);
        $pos = mb_strrpos($cut, ' ');
        if ($pos !== false && $pos > $max * 0.6) {
            $cut = mb_substr($cut, 0, $pos);
        }
        return preg_replace('/[\s،,;:؛.\-–—]+$/u', '', $cut) . '…';
    }

    /** HTML fragment → Markdown. */
    private function md($html, $shift = 0, $images = false, $min = 0)
    {
        return KMPR_LLMs_Markdown::convert((string) $html, array('shift' => $shift, 'images' => $images, 'min' => $min));
    }

    /** Rendered post body (blocks/shortcodes/autop) without running the_content side effects. */
    private function content_html($post)
    {
        $c = (string) $post->post_content;
        if ($c === '') {
            return '';
        }
        $c = has_blocks($c) ? do_blocks($c) : wpautop($c);
        $GLOBALS['kmpr_llms_rendering'] = true; // shortcodes such as [kmpr_banner] render as plain links
        $c = do_shortcode($c);
        $GLOBALS['kmpr_llms_rendering'] = false;
        return $c;
    }

    /** Cut Markdown to a byte budget at a paragraph boundary (keeps code fences balanced). */
    private function cap($md, $bytes, $url = '')
    {
        if (strlen($md) <= $bytes) {
            return $md;
        }
        $cut = substr($md, 0, $bytes);
        $pos = strrpos($cut, "\n\n");
        $cut = $pos !== false && $pos > $bytes * 0.5 ? substr($cut, 0, $pos) : mb_strcut($md, 0, $bytes, 'UTF-8');
        if (substr_count($cut, '```') % 2) {
            $cut .= "\n```";
        }
        $this->stats['truncated'] = ($this->stats['truncated'] ?? 0) + 1;
        return rtrim($cut) . "\n\n*(ادامه این بخش در " . ($url ? '[صفحه اصلی](' . $url . ')' : 'سایت') . ')*';
    }

    private function h($level, $text)
    {
        return str_repeat('#', min(6, max(1, $level))) . ' ' . trim(preg_replace('/\s+/u', ' ', (string) $text)) . "\n\n";
    }

    private function fa($n)
    {
        return function_exists('kmpr_fa_num') ? kmpr_fa_num($n) : (string) $n;
    }

    /** URL used inside llms.txt (HTML page or its .md twin). */
    private function link($url)
    {
        return !empty($this->s['link_md']) ? KMPR_LLMs_Manager::md_url($url) : $url;
    }

    private function item($title, $url, $note = '')
    {
        $title = str_replace(array('[', ']'), array('(', ')'), trim(wp_strip_all_tags((string) $title)));
        $note  = trim((string) $note);
        return '- [' . $title . '](' . $this->link($url) . ')' . ($note !== '' ? ': ' . $note : '') . "\n";
    }

    private function field($name, $id, $default = '')
    {
        if (!function_exists('get_field')) {
            $v = is_numeric($id) ? get_post_meta($id, $name, true) : get_option('options_' . $name);
        } else {
            $v = get_field($name, $id);
        }
        return ($v === null || $v === false || $v === '' || $v === array()) ? $default : $v;
    }

    private function page($slug)
    {
        if (!array_key_exists('page_' . $slug, $this->memo)) {
            $p = get_page_by_path($slug);
            $this->memo['page_' . $slug] = ($p && KMPR_LLMs_Manager::is_public($p)) ? $p : null;
        }
        return $this->memo['page_' . $slug];
    }

    /** Page by template file (falls back to slug). */
    private function page_by_template($template, $slug)
    {
        $key = 'tpl_' . $template;
        if (!array_key_exists($key, $this->memo)) {
            $ids = get_posts(array(
                'post_type'      => 'page',
                'post_status'    => 'publish',
                'posts_per_page' => 1,
                'fields'         => 'ids',
                'no_found_rows'  => true,
                'meta_key'       => '_wp_page_template',
                'meta_value'     => $template,
            ));
            $p = $ids ? get_post($ids[0]) : null;
            $this->memo[$key] = ($p && KMPR_LLMs_Manager::is_public($p)) ? $p : $this->page($slug);
        }
        return $this->memo[$key];
    }

    /** Resume value: resume page → options page → code default (mirrors kmpr_resume()). */
    private function resume($name, $default = null)
    {
        if ($default === null) {
            $default = $GLOBALS['kmpr_acf_defaults'][$name] ?? '';
        }
        $page = $this->page_by_template('page-resume.php', 'resume');
        if ($page && metadata_exists('post', $page->ID, $name)) {
            $v = $this->field($name, $page->ID);
            if ($v !== '') {
                return $v;
            }
        }
        return $this->field($name, 'option', $default);
    }

    private function resume_rows($name, $seed)
    {
        $rows = $this->resume($name, array());
        if (is_array($rows) && $rows) {
            return $rows;
        }
        return function_exists('kmpr_resume_seed') ? (array) kmpr_resume_seed($seed) : array();
    }

    private function request_url()
    {
        return function_exists('kmpr_request_url') ? kmpr_request_url() : home_url('/contact-us/');
    }

    /** «دوره: …» unless the course title already starts with «دوره». */
    private function course_label($c)
    {
        $t = trim(wp_strip_all_tags((string) $c['title']));
        return mb_strpos($t, 'دوره') === 0 ? $t : 'دوره: ' . $t;
    }

    private function words($text)
    {
        return (int) preg_match_all('/[\p{L}\p{N}]+/u', (string) $text);
    }

    /* =====================================================================
     * Data sources
     * ================================================================== */

    private function posts($type, $args = array())
    {
        $q = get_posts(array_merge(array(
            'post_type'        => $type,
            'post_status'      => 'publish',
            'posts_per_page'   => 200,
            'no_found_rows'    => true,
            'has_password'     => false,
            'suppress_filters' => true,
            'orderby'          => array('menu_order' => 'ASC', 'date' => 'DESC'),
        ), $args));
        if ($q) {
            update_meta_cache('post', wp_list_pluck($q, 'ID'));
        }
        return array_values(array_filter($q, array('KMPR_LLMs_Manager', 'is_public')));
    }

    public function services()
    {
        return $this->memo['services'] ?? ($this->memo['services'] = $this->posts('services'));
    }

    public function portfolio()
    {
        if (!isset($this->memo['portfolio'])) {
            $items = $this->posts('portfolio', array('orderby' => array('date' => 'DESC')));
            if (empty($this->s['archived'])) {
                $items = array_values(array_filter($items, function ($p) {
                    return !$this->field('site_archived', $p->ID);
                }));
            }
            $this->memo['portfolio'] = $items;
        }
        return $this->memo['portfolio'];
    }

    public function games()
    {
        return $this->memo['games'] ?? ($this->memo['games'] = $this->posts('game', array('orderby' => array('menu_order' => 'ASC', 'date' => 'ASC'))));
    }

    /** Courses with lessons ordered: pillar first, then lesson order. */
    public function courses()
    {
        if (isset($this->memo['courses'])) {
            return $this->memo['courses'];
        }
        $out = array();
        if (function_exists('kmpr_courses')) {
            foreach (kmpr_courses() as $c) {
                $ids    = array_map('intval', $c['lessons']);
                $pillar = (int) $c['pillar'];
                if ($pillar && in_array($pillar, $ids, true)) {
                    $ids = array_merge(array($pillar), array_values(array_diff($ids, array($pillar))));
                }
                $lessons = array();
                foreach ($ids as $id) {
                    $p = get_post($id);
                    if ($p && KMPR_LLMs_Manager::is_public($p)) {
                        $lessons[] = $p;
                    }
                }
                if ($lessons) {
                    $c['posts']  = $lessons;
                    $c['pillar'] = $pillar;
                    $out[]       = $c;
                }
            }
        }
        return $this->memo['courses'] = $out;
    }

    /** FAQ page rows. */
    public function faq()
    {
        if (isset($this->memo['faq'])) {
            return $this->memo['faq'];
        }
        $rows = array();
        $page = $this->page_by_template('faq.php', 'faq');
        if ($page) {
            foreach ((array) $this->field('faq', $page->ID, array()) as $r) {
                if (is_array($r) && trim((string) ($r['question'] ?? '')) !== '' && trim((string) ($r['answer'] ?? '')) !== '') {
                    $rows[] = array('q' => trim(wp_strip_all_tags($r['question'])), 'a' => (string) $r['answer']);
                }
            }
        }
        return $this->memo['faq'] = $rows;
    }

    private function contacts()
    {
        $out = array();
        $get = function ($k) {
            return function_exists('kmpr_contact') ? trim((string) kmpr_contact($k)) : '';
        };
        if ($v = $get('email')) {
            $out['ایمیل'] = $v;
        }
        if ($v = $get('phone')) {
            $out['تلفن'] = $v;
        }
        foreach (array('telegram' => 'تلگرام', 'whatsapp' => 'واتس‌اپ') as $k => $label) {
            $v = function_exists('kmpr_contact_url') ? kmpr_contact_url($k) : $get($k);
            if ($v) {
                $out[$label] = $v;
            }
        }
        foreach (array('linkedin' => 'لینکدین', 'github' => 'گیت‌هاب', 'instagram' => 'اینستاگرام') as $k => $label) {
            if ($v = $get($k)) {
                $out[$label] = $v;
            }
        }
        return $out;
    }

    /* =====================================================================
     * llms.txt
     * ================================================================== */

    public function summary()
    {
        $custom = trim((string) ($this->s['summary'] ?? ''));
        if ($custom !== '') {
            return preg_replace('/\s+/u', ' ', $custom);
        }
        return 'من کوروش محمدپور هستم، طراح و توسعه‌دهنده مستقل وب. سایت اختصاصی، فروشگاه اینترنتی و وب‌اپلیکیشن را با وردپرس و Next.js می‌سازم و روی سرعت (Core Web Vitals) و سئوی فنی تمرکز دارم؛ هر پروژه را خودم از نیازسنجی تا لانچ انجام می‌دهم.';
    }

    private function details()
    {
        $site = untrailingslashit(home_url('/'));
        $out  = '';
        $intro = trim((string) ($this->s['intro'] ?? ''));
        if ($intro !== '') {
            $out .= $intro . "\n\n";
        }
        $job = trim(wp_strip_all_tags((string) $this->resume('resume_job_title')));
        $loc = trim(wp_strip_all_tags((string) $this->resume('resume_location')));
        $out .= '- نقش: ' . ($job ?: 'طراح و توسعه‌دهنده وب') . "\n";
        if (function_exists('kmpr_years_experience')) {
            $out .= '- سابقه: ' . $this->fa(kmpr_years_experience()) . " سال کار حرفه‌ای در طراحی و توسعه وب\n";
        }
        if ($loc) {
            $out .= '- محل: ' . $loc . " (همکاری حضوری و دورکاری)\n";
        }
        // true_false: false/0 is a real answer here, so read it raw (null = never saved → default «available»).
        $available = function_exists('get_field') ? get_field('resume_available', 'option') : null;
        $rpage     = $this->page_by_template('page-resume.php', 'resume');
        if ($rpage && metadata_exists('post', $rpage->ID, 'resume_available') && function_exists('get_field')) {
            $available = get_field('resume_available', $rpage->ID);
        }
        $available = $available === null ? true : (bool) $available;
        $out .= '- وضعیت: ' . trim(wp_strip_all_tags((string) ($available ? $this->resume('resume_available_text') : $this->resume('resume_busy_text')))) . "\n";
        $out .= '- شروع همکاری: [فرم درخواست پروژه](' . $this->request_url() . ") — چند سؤال کوتاه درباره پروژه؛ خودم بررسی می‌کنم و برآورد زمان و هزینه را می‌فرستم.\n";
        $contacts = $this->contacts();
        if ($contacts) {
            $bits = array();
            foreach ($contacts as $label => $v) {
                if (in_array($label, array('ایمیل', 'تلفن', 'تلگرام'), true)) {
                    $bits[] = $label . ': ' . $v;
                }
            }
            if ($bits) {
                $out .= '- تماس مستقیم: ' . implode(' · ', $bits) . "\n";
            }
        }
        $out .= '- زبان محتوا: فارسی (fa-IR). نسخه کامل محتوا: [llms-full.txt](' . $site . '/llms-full.txt)';
        if (!empty($this->s['md'])) {
            $out .= '؛ نسخه Markdown هر صفحه با افزودن `.md` به آدرس آن (مثلاً `' . KMPR_LLMs_Manager::md_url(home_url('/about-us/')) . '`)';
        }
        $out .= ".\n\n";
        return $out;
    }

    public function llms_txt()
    {
        $t   = (array) ($this->s['txt'] ?? array());
        $on  = function ($k) use ($t) {
            return !empty($t[$k]);
        };
        $out = "# کوروش محمدپور (Korosh Mpr)\n\n";
        $out .= '> ' . $this->summary() . "\n\n";
        $out .= $this->details();
        $counts = array();

        // Who I am / how to hire.
        if ($on('about')) {
            $rows = array();
            $pages = array(
                array('page-about-us.php', 'about-us', 'معرفی من، اصول کاری و مسیر حرفه‌ای'),
                array('page-resume.php', 'resume', 'رزومه: مهارت‌ها، سوابق شغلی، تحصیلات و پروژه‌های منتخب'),
                array('faq.php', 'faq', 'پاسخ پرسش‌های رایج درباره هزینه، زمان، پشتیبانی و مالکیت کد'),
                array('page-contact-us.php', 'contact-us', 'راه‌های ارتباط مستقیم با من'),
            );
            foreach ($pages as $row) {
                $p = $this->page_by_template($row[0], $row[1]);
                if ($p) {
                    $rows[] = $this->item(get_the_title($p), get_permalink($p), $row[2]);
                }
            }
            $rows[] = '- [فرم درخواست پروژه](' . $this->request_url() . "): ثبت نیاز پروژه برای دریافت برآورد زمان و هزینه\n";
            $out .= "## درباره من و شروع همکاری\n\n" . implode('', $rows) . "\n";
            $counts['about'] = count($rows);
        }

        // Services.
        if ($on('services') && ($services = $this->services())) {
            $out .= "## خدمات\n\n";
            foreach ($services as $p) {
                $desc = $this->field('services_description', $p->ID) ?: ($p->post_excerpt ?: $p->post_content);
                $dur  = trim((string) $this->field('duration', $p->ID));
                $note = self::short($desc, 150) . ($dur ? ' — زمان تحویل: ' . $dur : '');
                $out .= $this->item(get_the_title($p), get_permalink($p), $note);
            }
            $out .= "\n";
            $counts['services'] = count($services);
        }

        // Courses: one section per course, pillar first then lesson order.
        if ($on('courses')) {
            $n = 0;
            foreach ($this->courses() as $c) {
                $out .= '## ' . $this->course_label($c) . "\n\n";
                $out .= $this->item('صفحه دوره', $c['url'], self::short($c['intro'], 150) . ' (' . $this->fa(count($c['posts'])) . ' درس)');
                $i = 0;
                foreach ($c['posts'] as $p) {
                    $i++;
                    $level = function_exists('kmpr_lesson_level') ? kmpr_lesson_level($p->ID) : null;
                    $tag   = (int) $p->ID === (int) $c['pillar'] ? 'مقاله ستون دوره' : 'درس ' . $this->fa($i);
                    $note  = $tag . ($level ? '، سطح ' . $level['label'] : '') . ' — ' . self::short($p->post_excerpt ?: $this->field('tldr', $p->ID) ?: $p->post_content, 120);
                    $out  .= $this->item(get_the_title($p), get_permalink($p), $note);
                    $n++;
                }
                $out .= "\n";
            }
            $counts['courses'] = count($this->courses());
            $counts['lessons'] = $n;
        }

        // Portfolio.
        if ($on('portfolio') && ($items = $this->portfolio())) {
            $out .= "## نمونه‌کارها\n\n";
            $archived = 0;
            foreach ($items as $p) {
                $note = $this->pf_note($p);
                if ($this->field('site_archived', $p->ID)) {
                    $archived++;
                }
                $out .= $this->item(get_the_title($p), get_permalink($p), $note);
            }
            $out .= "\n";
            $counts['portfolio'] = count($items);
            $counts['archived']  = $archived;
        }

        // Game center.
        if ($on('games') && ($games = $this->games())) {
            $out .= "## مرکز بازی\n\n";
            $hub  = get_post_type_archive_link('game');
            $text = function_exists('kmpr_game_opt') ? kmpr_game_opt('ga_text') : '';
            if ($hub) {
                $out .= $this->item('همه بازی‌ها', $hub, self::short($text ?: 'بازی‌های مرورگری که خودم با جاوااسکریپت خالص ساخته‌ام، با جدول امتیازات.', 150));
            }
            foreach ($games as $p) {
                $out .= $this->item(get_the_title($p), get_permalink($p), $this->game_note($p));
            }
            $out .= "\n";
            $counts['games'] = count($games);
        }

        // Optional (spec: links that can be skipped when context is short).
        if ($on('optional')) {
            $rows = array();
            $blog = (int) get_option('page_for_posts');
            if ($blog && ($bp = get_post($blog)) && KMPR_LLMs_Manager::is_public($bp)) {
                $rows[] = $this->item('وبلاگ', get_permalink($bp), 'همه مقاله‌های آموزشی، دسته‌بندی‌شده در قالب دوره');
            }
            foreach (array('services' => 'فهرست همه خدمات', 'portfolio' => 'آرشیو همه نمونه‌کارها') as $pt => $label) {
                $link = post_type_exists($pt) ? get_post_type_archive_link($pt) : '';
                if ($link) {
                    $rows[] = $this->item($label, $link);
                }
            }
            $terms = get_terms(array('taxonomy' => 'portfolio_categories', 'hide_empty' => true));
            if (!is_wp_error($terms)) {
                foreach ($terms as $term) {
                    $rows[] = $this->item('نمونه‌کارهای ' . $term->name, get_term_link($term), $this->fa((int) $term->count) . ' پروژه');
                }
            }
            $rows[] = '- [llms-full.txt](' . untrailingslashit(home_url('/')) . "/llms-full.txt): متن کامل خدمات، دوره‌ها، نمونه‌کارها و پرسش‌های متداول در یک فایل\n";
            foreach ($this->contacts() as $label => $v) {
                if (preg_match('#^https?://#', $v) && !in_array($label, array('واتس‌اپ'), true)) {
                    $rows[] = '- [' . $label . '](' . esc_url_raw($v) . ")\n";
                }
            }
            $out .= "## Optional\n\n" . implode('', $rows) . "\n";
            $counts['optional'] = count($rows);
        }

        $this->stats['txt_counts'] = $counts;
        return rtrim($out) . "\n";
    }

    private function pf_note($p)
    {
        $bits   = array();
        $client = trim((string) $this->field('client_name', $p->ID));
        $year   = trim((string) $this->field('project_year', $p->ID));
        $stack  = function_exists('kmpr_lines') ? kmpr_lines($this->field('tech_stack', $p->ID)) : array();
        $terms  = get_the_terms($p->ID, 'portfolio_categories');
        if ($client) {
            $bits[] = 'کارفرما: ' . $client;
        }
        if ($terms && !is_wp_error($terms)) {
            $bits[] = $terms[0]->name;
        }
        if ($year) {
            $bits[] = 'سال ' . $year;
        }
        if ($stack) {
            $bits[] = implode('، ', array_slice($stack, 0, 3));
        }
        $note = implode(' · ', $bits);
        if (!$note) {
            $note = self::short($p->post_excerpt ?: $p->post_content, 110);
        }
        if ($this->field('site_archived', $p->ID)) {
            $note = '«آرشیو» ' . $note;
        }
        return $note;
    }

    private function game_note($p)
    {
        $d    = function_exists('kmpr_game_data') ? kmpr_game_data($p->ID) : array();
        $bits = array();
        $line = trim((string) ($d['tagline'] ?? '')) ?: self::short($d['excerpt'] ?? $p->post_content, 110);
        if (!empty($d['genre'])) {
            $bits[] = $d['genre'];
        }
        if (!empty($d['diff_label'])) {
            $bits[] = 'سختی ' . $d['diff_label'];
        }
        return trim($line . ($bits ? ' (' . implode('، ', $bits) . ')' : ''));
    }

    /* =====================================================================
     * Item renderers (shared by llms-full.txt and per-page .md)
     * $h = heading level of the item title.
     * ================================================================== */

    public function service_md($p, $h)
    {
        $id   = $p->ID;
        $url  = get_permalink($p);
        $out  = $this->h($h, get_the_title($p));
        $meta = array('آدرس' => $url);
        foreach (array('duration' => 'زمان تقریبی تحویل', 'starting_price' => 'شروع قیمت', 'services_badge' => 'برچسب') as $k => $label) {
            $v = trim(wp_strip_all_tags((string) $this->field($k, $id)));
            if ($v !== '') {
                $meta[$label] = $v;
            }
        }
        foreach ($meta as $k => $v) {
            $out .= '- ' . $k . ': ' . $v . "\n";
        }
        $out .= "\n";
        if ($desc = trim(wp_strip_all_tags((string) $this->field('services_description', $id)))) {
            $out .= $desc . "\n\n";
        }
        $aud = function_exists('kmpr_lines') ? kmpr_lines($this->field('audience', $id)) : array();
        if ($aud) {
            $out .= $this->h($h + 1, 'مناسب چه کسانی است');
            foreach ($aud as $line) {
                $out .= '- ' . $line . "\n";
            }
            $out .= "\n";
        }
        $rows = array_filter((array) $this->field('deliverables', $id, array()), function ($r) {
            return is_array($r) && !empty($r['title']);
        });
        if ($rows) {
            $out .= $this->h($h + 1, 'خروجی‌ها و امکانات');
            foreach ($rows as $r) {
                $out .= '- **' . trim($r['title']) . '**' . (!empty($r['desc']) ? ': ' . self::short($r['desc'], 400) : '') . "\n";
            }
            $out .= "\n";
        }
        $steps = array_filter((array) $this->field('service_steps', $id, array()), function ($r) {
            return is_array($r) && !empty($r['t']);
        });
        if ($steps) {
            $out .= $this->h($h + 1, 'مراحل اجرا');
            $i = 0;
            foreach ($steps as $r) {
                $out .= (++$i) . '. **' . trim($r['t']) . '**' . (!empty($r['d']) ? ': ' . self::short($r['d'], 400) : '') . "\n";
            }
            $out .= "\n";
        }
        if (($this->field('Proposal_switch', $id) === 'on') && ($plans = (array) $this->field('proposal', $id, array()))) {
            $out .= $this->h($h + 1, 'پلن‌ها');
            foreach ($plans as $plan) {
                if (!is_array($plan) || empty($plan['proposal_title'])) {
                    continue;
                }
                $out .= '- **' . trim($plan['proposal_title']) . '**' . (!empty($plan['proposal_description']) ? ': ' . trim(wp_strip_all_tags($plan['proposal_description'])) : '') . "\n";
                foreach ((array) ($plan['proposal_row'] ?? array()) as $row) {
                    if (is_array($row) && !empty($row['Property_label'])) {
                        $out .= '  - ' . trim($row['Property_label']) . (!empty($row['Property_value']) ? ': ' . trim(wp_strip_all_tags($row['Property_value'])) : '') . "\n";
                    }
                }
            }
            $out .= "\n";
        }
        $body = $this->md($this->content_html($p), $h, false, $h + 2);
        if ($body !== '') {
            $out .= $this->h($h + 1, 'توضیحات') . $body . "\n\n";
        }
        $faq = array_filter((array) $this->field('service_faq', $id, array()), function ($r) {
            return is_array($r) && !empty($r['question']) && !empty($r['answer']);
        });
        if ($faq) {
            $out .= $this->h($h + 1, 'پرسش‌های متداول این خدمت');
            foreach ($faq as $r) {
                $out .= '**' . trim(wp_strip_all_tags($r['question'])) . "**\n\n" . $this->md(wpautop($r['answer'])) . "\n\n";
            }
        }
        return $this->cap(rtrim($out), self::CAP_SERVICE, $url) . "\n\n";
    }

    public function portfolio_md($p, $h)
    {
        $d        = function_exists('kmpr_pf_data') ? kmpr_pf_data($p->ID, 'thumbnail') : array();
        $url      = get_permalink($p);
        $archived = !empty($d['archived']);
        $out      = $this->h($h, get_the_title($p) . ($archived ? ' «آرشیو»' : ''));
        $meta     = array(
            'آدرس'        => $url,
            'کارفرما'     => $d['client'] ?? '',
            'دسته'        => !empty($d['term']) ? $d['term']->name : '',
            'سال اجرا'    => $d['year'] ?? '',
            'نقش من'      => $d['role'] ?? '',
            'مدت اجرا'    => $d['duration'] ?? '',
            'تکنولوژی‌ها' => !empty($d['stack']) ? implode('، ', $d['stack']) : '',
            'نوع قالب'    => $d['theme'] ?? '',
            'وب‌سایت'     => $archived ? '' : ($d['site'] ?? ''),
            'وضعیت'       => $archived ? 'آرشیو — این سایت دیگر آنلاین نیست یا در اختیار من نیست' : '',
        );
        foreach ($meta as $k => $v) {
            if (trim((string) $v) !== '') {
                $out .= '- ' . $k . ': ' . trim(wp_strip_all_tags((string) $v)) . "\n";
            }
        }
        $out .= "\n";
        if (!empty($d['challenge'])) {
            $out .= $this->h($h + 1, 'چالش') . $this->md($d['challenge']) . "\n\n";
        }
        if (!empty($d['solution'])) {
            $out .= $this->h($h + 1, 'راه‌حل') . $this->md($d['solution']) . "\n\n";
        }
        if (!empty($d['results'])) {
            $out .= $this->h($h + 1, 'نتایج');
            foreach ($d['results'] as $r) {
                $out .= '- ' . $r['value'] . ' ' . $r['label'] . "\n";
            }
            $out .= "\n";
        }
        $body = $this->md($this->content_html($p), $h, false, $h + 2);
        if ($body !== '') {
            $out .= $this->h($h + 1, 'شرح پروژه') . $body . "\n\n";
        }
        return $this->cap(rtrim($out), self::CAP_PF, $url) . "\n\n";
    }

    public function game_md($p, $h)
    {
        $d   = function_exists('kmpr_game_data') ? kmpr_game_data($p->ID) : array();
        $url = get_permalink($p);
        $out = $this->h($h, get_the_title($p));
        foreach (array('آدرس' => $url, 'شعار' => $d['tagline'] ?? '', 'ژانر' => $d['genre'] ?? '', 'سختی' => $d['diff_label'] ?? '', 'گیت‌هاب' => $d['github'] ?? '') as $k => $v) {
            if (trim((string) $v) !== '') {
                $out .= '- ' . $k . ': ' . $v . "\n";
            }
        }
        $out .= "\n";
        $body = $this->md($this->content_html($p), $h - 1, false, $h + 1);
        if ($body !== '') {
            $out .= $body . "\n\n";
        }
        if (!empty($d['how'])) {
            $out .= $this->h($h + 1, 'چطور بازی کنم');
            foreach ($d['how'] as $i => $line) {
                $out .= ($i + 1) . '. ' . $line . "\n";
            }
            $out .= "\n";
        }
        if (!empty($d['controls']) || !empty($d['mobile'])) {
            $out .= $this->h($h + 1, 'کنترل‌ها');
            foreach ((array) ($d['controls'] ?? array()) as $c) {
                $out .= '- ' . $c['key'] . ($c['action'] !== '' ? ': ' . $c['action'] : '') . "\n";
            }
            foreach ((array) ($d['mobile'] ?? array()) as $line) {
                $out .= '- موبایل: ' . $line . "\n";
            }
            $out .= "\n";
        }
        if (!empty($d['tech'])) {
            $out .= $this->h($h + 1, 'تکنولوژی‌ها') . '- ' . implode("\n- ", $d['tech']) . "\n\n";
        }
        return $this->cap(rtrim($out), self::CAP_SERVICE, $url) . "\n\n";
    }

    public function lesson_md($p, $h, $course = null, $number = 0)
    {
        $id    = $p->ID;
        $url   = get_permalink($p);
        $out   = $this->h($h, get_the_title($p));
        $level = function_exists('kmpr_lesson_level') ? kmpr_lesson_level($id) : null;
        $meta  = array('آدرس' => $url);
        if ($course) {
            $meta['دوره'] = trim(wp_strip_all_tags($course['title'])) . ($number ? ' — ' . ((int) $id === (int) $course['pillar'] ? 'مقاله ستون' : 'درس ' . $this->fa($number)) : '');
        }
        if ($level) {
            $meta['سطح'] = $level['label'];
        }
        if (function_exists('reading_time')) {
            $meta['زمان مطالعه'] = $this->fa((int) reading_time($id)) . ' دقیقه';
        }
        $meta['به‌روزرسانی'] = gmdate('Y-m-d', strtotime($p->post_modified_gmt . ' UTC'));
        foreach ($meta as $k => $v) {
            $out .= '- ' . $k . ': ' . $v . "\n";
        }
        $out .= "\n";
        if ($tldr = trim(wp_strip_all_tags((string) $this->field('tldr', $id)))) {
            $out .= '> خلاصه: ' . preg_replace('/\s+/u', ' ', $tldr) . "\n\n";
        }
        $kt = function_exists('kmpr_lines') ? kmpr_lines(wp_strip_all_tags((string) $this->field('key_takeaways', $id))) : array();
        if ($kt) {
            $out .= $this->h($h + 1, 'نکات کلیدی') . '- ' . implode("\n- ", $kt) . "\n\n";
        }
        $out .= $this->md($this->content_html($p), $h - 1, false, $h + 1) . "\n\n";
        $faq = function_exists('kmpr_post_faq') ? kmpr_post_faq($id) : array();
        if ($faq) {
            $out .= $this->h($h + 1, 'پرسش‌های متداول');
            foreach ($faq as $r) {
                $out .= '**' . trim(wp_strip_all_tags($r['question'])) . "**\n\n" . $this->md(wpautop($r['answer'])) . "\n\n";
            }
        }
        return $this->cap(rtrim($out), self::CAP_LESSON, $url) . "\n\n";
    }

    public function faq_md($h)
    {
        $out = '';
        foreach ($this->faq() as $r) {
            $out .= $this->h($h, $r['q']) . $this->md(wpautop($r['a'])) . "\n\n";
        }
        return $out;
    }

    public function resume_md($h)
    {
        $out  = '';
        $name = trim((string) $this->resume('resume_full_name'));
        $lat  = trim((string) $this->resume('resume_latin_name'));
        $meta = array(
            'نام'         => $name . ($lat ? ' (' . $lat . ')' : ''),
            'عنوان شغلی'  => wp_strip_all_tags((string) $this->resume('resume_job_title')),
            'محل'         => wp_strip_all_tags((string) $this->resume('resume_location')),
        );
        foreach ($meta as $k => $v) {
            if (trim($v) !== '') {
                $out .= '- ' . $k . ': ' . trim($v) . "\n";
            }
        }
        $out .= "\n";
        if ($sum = trim(wp_strip_all_tags((string) $this->resume('resume_summary')))) {
            $out .= $sum . "\n\n";
        }
        $focus = $this->resume('resume_focus', array());
        if (is_array($focus) && $focus) {
            $out .= $this->h($h, 'مهارت‌های کلیدی');
            foreach ($focus as $r) {
                if (is_array($r) && !empty($r['label'])) {
                    $out .= '- ' . trim($r['label']) . (!empty($r['note']) ? ': ' . trim($r['note']) : '') . "\n";
                }
            }
            $out .= "\n";
        }
        $skills = array('resume_skills_frontend' => 'فرانت‌اند', 'resume_skills_backend' => 'بک‌اند و CMS', 'resume_skills_tools' => 'ابزارها و استانداردها');
        $lines  = '';
        foreach ($skills as $k => $label) {
            $v = trim(wp_strip_all_tags((string) $this->resume($k)));
            if ($v !== '') {
                $lines .= '- ' . $label . ': ' . preg_replace('/\s+/u', ' ', $v) . "\n";
            }
        }
        if ($lines) {
            $out .= $this->h($h, 'جعبه‌ابزار') . $lines . "\n";
        }
        $exp = $this->resume_rows('resume_experiences', 'experiences');
        if ($exp) {
            $out .= $this->h($h, 'سوابق شغلی');
            foreach ($exp as $r) {
                if (!is_array($r) || empty($r['job_title'])) {
                    continue;
                }
                $out .= '- **' . trim($r['job_title']) . '**' . (!empty($r['company']) ? ' — ' . trim($r['company']) : '') . (!empty($r['period']) ? ' (' . trim($r['period']) . ')' : '') . "\n";
                if (!empty($r['description'])) {
                    $out .= '  ' . self::short($r['description'], 500) . "\n";
                }
                if (!empty($r['tags'])) {
                    $out .= '  تکنولوژی‌ها: ' . trim($r['tags']) . "\n";
                }
            }
            $out .= "\n";
        }
        $edu = $this->resume_rows('resume_education', 'education');
        if ($edu) {
            $out .= $this->h($h, 'تحصیلات و دوره‌ها');
            foreach ($edu as $r) {
                if (is_array($r) && !empty($r['degree'])) {
                    $out .= '- ' . trim($r['degree']) . (!empty($r['institution']) ? ' — ' . trim($r['institution']) : '') . (!empty($r['period']) ? ' (' . trim($r['period']) . ')' : '') . "\n";
                }
            }
            $out .= "\n";
        }
        $langs = $this->resume('resume_languages', array());
        if (is_array($langs) && $langs) {
            $out .= $this->h($h, 'زبان‌ها');
            foreach ($langs as $r) {
                if (is_array($r) && !empty($r['name'])) {
                    $out .= '- ' . trim($r['name']) . (!empty($r['level_label']) ? ': ' . trim($r['level_label']) : '') . "\n";
                }
            }
            $out .= "\n";
        }
        $domains = function_exists('kmpr_lines') ? kmpr_lines($this->resume('resume_project_domains')) : array();
        if ($domains) {
            $out .= $this->h($h, 'حوزه‌ها و مشتریان') . '- ' . implode("\n- ", $domains) . "\n\n";
        }
        return $out;
    }

    public function about_md($page, $h)
    {
        $id      = $page->ID;
        $get     = function ($name, $default = null) use ($id) {
            return function_exists('kmpr_field') ? kmpr_field($name, $default, $id) : $this->field($name, $id, (string) $default);
        };
        $out     = '';
        if ($t = trim(wp_strip_all_tags((string) $get('aboutus-title')))) {
            $out .= '**' . $t . "**\n\n";
        }
        $out    .= $this->md((string) $get('aboutus-content')) . "\n\n";
        $values  = $get('about_values', array());
        if ((!is_array($values) || !$values) && function_exists('kmpr_resume_seed')) {
            $values = kmpr_resume_seed('about_values');
        }
        if (is_array($values) && $values) {
            $out .= $this->h($h, trim(wp_strip_all_tags((string) $get('about_values_title'))) ?: 'اصول کاری من');
            foreach ($values as $v) {
                if (is_array($v) && !empty($v['title'])) {
                    $out .= '- **' . trim($v['title']) . '**' . (!empty($v['text']) ? ': ' . trim(wp_strip_all_tags($v['text'])) : '') . "\n";
                }
            }
            $out .= "\n";
        }
        // post_content of this page is not rendered by page-about-us.php (legacy copy), so it is not exported.
        return $out;
    }

    public function contact_md($page, $h)
    {
        $out = 'سریع‌ترین راه شروع همکاری، [فرم درخواست پروژه](' . $this->request_url() . ") است.\n\n";
        $out .= $this->h($h, 'راه‌های ارتباطی');
        foreach ($this->contacts() as $label => $v) {
            $out .= '- ' . $label . ': ' . $v . "\n";
        }
        foreach (array('contact_response_note' => 'زمان پاسخ‌گویی', 'contact_hours' => 'ساعات کاری') as $k => $label) {
            $v = function_exists('kmpr_field') ? trim(wp_strip_all_tags((string) kmpr_field($k, null, $page->ID))) : '';
            if ($v !== '') {
                $out .= '- ' . $label . ': ' . $v . "\n";
            }
        }
        return $out . "\n";
    }

    /* =====================================================================
     * llms-full.txt
     * ================================================================== */

    public function llms_full()
    {
        $f      = (array) ($this->s['full'] ?? array());
        $budget = self::FULL_CAP;
        $parts  = array();
        $sizes  = array();
        $counts = array();
        $site   = untrailingslashit(home_url('/'));

        $head  = "# کوروش محمدپور (Korosh Mpr) — متن کامل سایت\n\n";
        $head .= '> ' . $this->summary() . "\n\n";
        $head .= 'این فایل نسخه کامل و تمیز (Markdown) محتوای اصلی سایت ' . $site . ' برای مدل‌های زبانی است. فهرست کوتاه و پیوندها در [llms.txt](' . $site . '/llms.txt) است. ';
        $head .= 'آخرین به‌روزرسانی: ' . gmdate('Y-m-d') . ".\n\n";
        $head .= '- شروع همکاری: [فرم درخواست پروژه](' . $this->request_url() . ")\n\n";
        $budget -= strlen($head);

        $add = function ($key, $title, $items, $render) use (&$budget, &$parts, &$sizes, &$counts) {
            if (!$items) {
                return;
            }
            $buf   = '## ' . $title . "\n\n";
            $n     = 0;
            $total = count($items);
            foreach ($items as $item) {
                $chunk = $render($item, $n);
                if (strlen($buf) + strlen($chunk) > $budget) {
                    $buf .= '*(' . $this->fa($total - $n) . " مورد دیگر به دلیل محدودیت حجم حذف شد؛ فهرست کامل در llms.txt است.)*\n\n";
                    $this->stats['truncated'] = ($this->stats['truncated'] ?? 0) + 1;
                    break;
                }
                $buf .= $chunk;
                $n++;
            }
            $budget       -= strlen($buf);
            $parts[$key]   = $buf;
            $sizes[$key]   = strlen($buf);
            $counts[$key]  = $n;
        };

        // Priority order (small, high-value sections first); display order is fixed below.
        if (!empty($f['about'])) {
            $add('about', 'درباره من و رزومه', array(1), function () {
                $out  = '';
                $about = $this->page_by_template('page-about-us.php', 'about-us');
                if ($about) {
                    $out .= $this->about_md($about, 3);
                }
                $out .= $this->resume_md(3);
                $out .= $this->h(3, 'راه‌های ارتباطی');
                foreach ($this->contacts() as $label => $v) {
                    $out .= '- ' . $label . ': ' . $v . "\n";
                }
                return $out . "\n";
            });
        }
        if (!empty($f['services'])) {
            $add('services', 'خدمات', $this->services(), function ($p) {
                return $this->service_md($p, 3);
            });
        }
        if (!empty($f['faq'])) {
            $add('faq', 'پرسش‌های متداول', $this->faq(), function ($r) {
                return $this->h(3, $r['q']) . $this->md(wpautop($r['a'])) . "\n\n";
            });
        }
        if (!empty($f['courses'])) {
            $lessons = array();
            foreach ($this->courses() as $c) {
                foreach ($c['posts'] as $i => $p) {
                    $lessons[] = array($c, $p, $i + 1);
                }
            }
            $last = 0;
            $add('courses', 'دوره‌های آموزشی', $lessons, function ($row) use (&$last) {
                list($c, $p, $num) = $row;
                $out = '';
                if ($last !== (int) $c['id']) {
                    $last = (int) $c['id'];
                    $out .= $this->h(3, $this->course_label($c));
                    $out .= '- آدرس دوره: ' . $c['url'] . "\n- تعداد درس: " . $this->fa(count($c['posts'])) . "\n\n";
                    if ($c['intro']) {
                        $out .= trim(wp_strip_all_tags($c['intro'])) . "\n\n";
                    }
                }
                return $out . $this->lesson_md($p, 4, $c, $num);
            });
        }
        if (!empty($f['portfolio'])) {
            $add('portfolio', 'نمونه‌کارها (کیس‌استادی)', $this->portfolio(), function ($p) {
                return $this->portfolio_md($p, 3);
            });
        }
        if (!empty($f['games'])) {
            $add('games', 'مرکز بازی', $this->games(), function ($p) {
                return $this->game_md($p, 3);
            });
        }

        $out = $head;
        foreach (array('about', 'services', 'courses', 'portfolio', 'games', 'faq') as $k) {
            if (isset($parts[$k])) {
                $out .= $parts[$k];
            }
        }
        $this->stats['full_sizes']  = $sizes;
        $this->stats['full_counts'] = $counts;
        return rtrim($out) . "\n";
    }

    /* =====================================================================
     * Per-page Markdown for the current main query (called on template_redirect).
     * Returns null when the URL has no Markdown version (→ 404).
     * ================================================================== */

    public function current_md()
    {
        if (is_front_page() && !is_paged()) {
            return array('body' => $this->llms_txt(), 'canonical' => home_url('/'), 'modified' => 0);
        }
        if (is_singular()) {
            $p = get_queried_object();
            if (!($p instanceof WP_Post) || !KMPR_LLMs_Manager::is_public($p) || !in_array($p->post_type, KMPR_LLMs_Manager::md_types(), true)) {
                return null;
            }
            return array('body' => $this->single_doc($p), 'canonical' => get_permalink($p), 'modified' => strtotime($p->post_modified_gmt . ' UTC'));
        }
        if (is_paged()) {
            return null;
        }
        if (is_post_type_archive(array('services', 'portfolio', 'game'))) {
            $pt   = get_query_var('post_type');
            $pt   = is_array($pt) ? reset($pt) : $pt;
            $map  = array('services' => array('خدمات', 'services'), 'portfolio' => array('نمونه‌کارها', 'portfolio'), 'game' => array('مرکز بازی', 'games'));
            $list = $pt === 'services' ? $this->services() : ($pt === 'portfolio' ? $this->portfolio() : $this->games());
            $body = '# ' . $map[$pt][0] . "\n\n> " . $this->summary() . "\n\n";
            foreach ($list as $p) {
                $note  = $pt === 'portfolio' ? $this->pf_note($p) : ($pt === 'game' ? $this->game_note($p) : self::short($this->field('services_description', $p->ID) ?: $p->post_content, 160) . (($d = $this->field('duration', $p->ID)) ? ' — زمان تحویل: ' . $d : ''));
                $body .= '- [' . get_the_title($p) . '](' . get_permalink($p) . '): ' . $note . "\n";
            }
            return array('body' => $body, 'canonical' => get_post_type_archive_link($pt), 'modified' => 0);
        }
        if (is_category()) {
            $term = get_queried_object();
            foreach ($this->courses() as $c) {
                if ((int) $c['id'] === (int) $term->term_id) {
                    $body = '# ' . $this->course_label($c) . "\n\n";
                    if ($c['intro']) {
                        $body .= '> ' . self::short($c['intro'], 400) . "\n\n";
                    }
                    $body .= $this->h(2, 'درس‌ها (به ترتیب)');
                    foreach ($c['posts'] as $i => $p) {
                        $tag   = (int) $p->ID === (int) $c['pillar'] ? 'مقاله ستون' : 'درس ' . $this->fa($i + 1);
                        $body .= '- [' . get_the_title($p) . '](' . get_permalink($p) . '): ' . $tag . ' — ' . self::short($p->post_excerpt ?: $p->post_content, 140) . "\n";
                    }
                    return array('body' => $body, 'canonical' => $c['url'], 'modified' => 0);
                }
            }
            return null;
        }
        if (is_home()) {
            $body = "# وبلاگ و دوره‌های آموزشی\n\n> مقاله‌های آموزشی من، دسته‌بندی‌شده در قالب دوره.\n\n";
            foreach ($this->courses() as $c) {
                $body .= '- [' . trim(wp_strip_all_tags($c['title'])) . '](' . $c['url'] . '): ' . self::short($c['intro'], 140) . ' (' . $this->fa(count($c['posts'])) . " درس)\n";
            }
            return array('body' => $body, 'canonical' => get_permalink((int) get_option('page_for_posts')), 'modified' => 0);
        }
        return null;
    }

    /** Full Markdown document for one public post. */
    public function single_doc($p)
    {
        $url = get_permalink($p);
        switch ($p->post_type) {
            case 'services':
                $body = $this->service_md($p, 1);
                break;
            case 'portfolio':
                $body = $this->portfolio_md($p, 1);
                break;
            case 'game':
                $body = $this->game_md($p, 1);
                break;
            case 'post':
                $course = null;
                $num    = 0;
                foreach ($this->courses() as $c) {
                    foreach ($c['posts'] as $i => $lp) {
                        if ((int) $lp->ID === (int) $p->ID) {
                            $course = $c;
                            $num    = $i + 1;
                            break 2;
                        }
                    }
                }
                $body = $this->lesson_md($p, 1, $course, $num);
                break;
            default:
                $tpl   = get_page_template_slug($p);
                $title = $this->h(1, get_the_title($p));
                if ($tpl === 'faq.php') {
                    $intro = trim(wp_strip_all_tags((string) $p->post_content));
                    $body  = $title . ($intro !== '' ? '> ' . preg_replace('/\s+/u', ' ', $intro) . "\n\n" : '') . $this->faq_md(2);
                } elseif ($tpl === 'page-resume.php') {
                    $body = $title . $this->resume_md(2);
                } elseif ($tpl === 'page-about-us.php') {
                    $body = $title . $this->about_md($p, 2);
                } elseif ($tpl === 'page-contact-us.php') {
                    $body = $title . $this->contact_md($p, 2);
                } else {
                    $body = $title . $this->cap($this->md($this->content_html($p), 0, true, 2), self::CAP_PAGE, $url);
                }
        }
        return rtrim($body) . "\n\n---\n\nمنبع: " . $url . ' · کوروش محمدپور · فهرست کامل سایت: ' . untrailingslashit(home_url('/')) . "/llms.txt\n";
    }

    /* =====================================================================
     * Validation (admin)
     * ================================================================== */

    /**
     * @return array list of array(level: ok|warn|error, message)
     */
    public static function validate($txt, $full, $robots)
    {
        $r     = array();
        $lines = preg_split('/\r\n|\r|\n/', (string) $txt);

        // 1. Structure per llmstxt.org.
        $h1 = preg_grep('/^# /', $lines);
        if (count($h1) === 1 && strpos(ltrim((string) $txt), '# ') === 0) {
            $r[] = array('ok', 'llms.txt با یک تیتر H1 (نام سایت) شروع می‌شود.');
        } else {
            $r[] = array('error', 'llms.txt باید دقیقاً یک H1 داشته باشد و با آن شروع شود (' . count($h1) . ' مورد پیدا شد).');
        }
        $first_after = '';
        foreach ($lines as $i => $l) {
            if ($i > 0 && trim($l) !== '') {
                $first_after = $l;
                break;
            }
        }
        $r[] = strpos($first_after, '> ') === 0
            ? array('ok', 'خلاصه blockquote بلافاصله بعد از H1 آمده است.')
            : array('warn', 'خلاصه (blockquote با «> ») بعد از H1 پیدا نشد.');

        $section = '';
        $bad     = array();
        $deep    = 0;
        $links   = array();
        $sections = array();
        foreach ($lines as $n => $l) {
            if (preg_match('/^(#{2,6}) (.+)$/', $l, $m)) {
                if (strlen($m[1]) > 2) {
                    $deep++;
                }
                $section    = trim($m[2]);
                $sections[] = $section;
                continue;
            }
            if ($section === '' || trim($l) === '') {
                continue;
            }
            if (!preg_match('/^- \[[^\]]+\]\(([^)\s]+)\)(: .+)?$/u', $l, $m)) {
                $bad[] = ($n + 1);
                continue;
            }
            $links[] = $m[1];
        }
        $r[] = $bad
            ? array('warn', 'خط‌های بخش‌ها باید فهرست پیوند «- [عنوان](آدرس): توضیح» باشند؛ خط(های) ' . implode('، ', array_slice($bad, 0, 10)) . ' این قالب را ندارند.')
            : array('ok', 'همه بخش‌ها فهرست پیوند استاندارد هستند (' . count($sections) . ' بخش H2، ' . count($links) . ' پیوند).');
        if ($deep) {
            $r[] = array('warn', $deep . ' تیتر H3 یا عمیق‌تر در llms.txt هست؛ استاندارد فقط H2 را برای بخش‌ها تعریف می‌کند.');
        }
        $r[] = in_array('Optional', $sections, true)
            ? array('ok', 'بخش «## Optional» برای پیوندهای کم‌اهمیت وجود دارد.')
            : array('warn', 'بخش «## Optional» وجود ندارد (اختیاری است ولی توصیه می‌شود).');

        // 2. Internal links.
        $home   = wp_parse_url(home_url('/'), PHP_URL_HOST);
        $broken = array();
        $known  = KMPR_LLMs_Manager::known_urls();
        foreach (array_unique($links) as $url) {
            if (wp_parse_url($url, PHP_URL_HOST) !== $home) {
                continue;
            }
            $clean = strtok($url, '#');
            if (isset($known[untrailingslashit($clean)])) {
                continue;
            }
            $html = preg_match('#\.md$#', $clean) ? preg_replace('#(/index(\.html)?)?\.md$#', '/', $clean) : $clean;
            $pid  = url_to_postid($html);
            if ($pid && ($p = get_post($pid)) && KMPR_LLMs_Manager::is_public($p)) {
                continue;
            }
            if (isset($known[untrailingslashit($html)])) {
                continue;
            }
            $broken[] = $url;
        }
        $r[] = $broken
            ? array('error', 'پیوند داخلی نامعتبر: ' . implode(' ، ', array_slice($broken, 0, 8)) . (count($broken) > 8 ? ' …' : ''))
            : array('ok', 'همه پیوندهای داخلی به صفحه‌های منتشرشده و قابل ایندکس اشاره می‌کنند.');

        // 3. Size.
        $s1  = strlen((string) $txt);
        $s2  = strlen((string) $full);
        $r[] = $s1 > 50000 ? array('warn', 'llms.txt ' . size_format($s1) . ' است؛ بهتر است زیر ۵۰ کیلوبایت و موجز بماند.') : array('ok', 'حجم llms.txt: ' . size_format($s1, 1));
        $r[] = $s2 > 1500000 ? array('error', 'llms-full.txt ' . size_format($s2) . ' است (بیش از ۱٫۵ مگابایت).') : array('ok', 'حجم llms-full.txt: ' . size_format($s2, 1));

        // 4. robots.txt directives.
        $valid  = '/^(user-agent|allow|disallow|sitemap|crawl-delay|clean-param|host|request-rate|visit-time|noindex)\s*:/i';
        $issues = array();
        $has_ua = false;
        foreach (preg_split('/\r\n|\r|\n/', (string) $robots) as $n => $l) {
            $l = trim(preg_replace('/#.*$/', '', $l));
            if ($l === '') {
                continue;
            }
            if (!preg_match($valid, $l)) {
                $issues[] = 'خط ' . ($n + 1) . ': دستور ناشناخته «' . $l . '»';
                continue;
            }
            if (preg_match('/^user-agent\s*:/i', $l)) {
                $has_ua = true;
            } elseif (preg_match('/^(allow|disallow)\s*:\s*(.*)$/i', $l, $m)) {
                if (!$has_ua) {
                    $issues[] = 'خط ' . ($n + 1) . ': Allow/Disallow قبل از هر User-agent';
                }
                if ($m[2] !== '' && $m[2][0] !== '/' && $m[2][0] !== '*') {
                    $issues[] = 'خط ' . ($n + 1) . ': مسیر باید با / یا * شروع شود';
                }
            }
        }
        $r[] = $issues ? array('error', 'robots.txt: ' . implode(' | ', $issues)) : array('ok', 'robots.txt فقط دستورهای معتبر دارد (معیار ممیزی robots-txt در Lighthouse).');
        return $r;
    }
}
