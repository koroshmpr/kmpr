<?php
/**
 * Admin page «هوش مصنوعی و LLMs» (loaded only in wp-admin).
 *
 * Tabs: status, settings (sections, intro text, crawler policy), preview, validation.
 * All actions go through admin-post.php with a nonce + manage_options check.
 *
 * @package KMPR
 */

if (!defined('ABSPATH')) {
    exit;
}

class KMPR_LLMs_Admin
{
    const SLUG = 'kmpr-ai-llms';
    const CAP  = 'manage_options';

    /** @var KMPR_LLMs_Manager */
    private $m;

    public function __construct($manager)
    {
        $this->m = $manager;
        add_action('admin_menu', array($this, 'menu'));
        add_action('admin_post_kmpr_llms_save', array($this, 'save'));
        add_action('admin_post_kmpr_llms_regen', array($this, 'regen'));
    }

    public function menu()
    {
        add_menu_page('مدیریت هوش مصنوعی و ایندکس محتوا', 'هوش مصنوعی و LLMs', self::CAP, self::SLUG, array($this, 'render'), 'dashicons-rest-api', 65);
    }

    private function url($args = array())
    {
        return add_query_arg(array_merge(array('page' => self::SLUG), $args), admin_url('admin.php'));
    }

    /* ------------------------------------------------------------------ */

    public function save()
    {
        if (!current_user_can(self::CAP)) {
            wp_die('دسترسی ندارید.', 403);
        }
        check_admin_referer('kmpr_llms_save');
        $in   = wp_unslash($_POST); // phpcs:ignore WordPress.Security.NonceVerification -- verified above
        $d    = KMPR_LLMs_Manager::defaults();
        $s    = array(
            'summary'  => sanitize_textarea_field($in['summary'] ?? ''),
            'intro'    => sanitize_textarea_field($in['intro'] ?? ''),
            'archived' => empty($in['archived']) ? 0 : 1,
            'md'       => empty($in['md']) ? 0 : 1,
            'link_md'  => empty($in['link_md']) ? 0 : 1,
        );
        foreach (array('txt', 'full') as $group) {
            foreach ($d[$group] as $k => $v) {
                $s[$group][$k] = empty($in[$group][$k]) ? 0 : 1;
            }
        }
        foreach (array_keys(KMPR_LLMs_Manager::bots()) as $ua) {
            $s['bots'][$ua] = (($in['bots'][$ua] ?? 'allow') === 'deny') ? 'deny' : 'allow';
        }
        KMPR_LLMs_Manager::save_settings($s);
        $this->m->rebuild();
        wp_safe_redirect($this->url(array('tab' => 'settings', 'msg' => 'saved')));
        exit;
    }

    public function regen()
    {
        if (!current_user_can(self::CAP)) {
            wp_die('دسترسی ندارید.', 403);
        }
        check_admin_referer('kmpr_llms_regen');
        $this->m->rebuild();
        wp_safe_redirect($this->url(array('msg' => 'regen')));
        exit;
    }

    /* ------------------------------------------------------------------ */

    private function date($ts)
    {
        if (!$ts) {
            return '—';
        }
        return function_exists('shamsi_date') ? shamsi_date('j F Y، H:i', $ts) : wp_date('Y-m-d H:i', $ts);
    }

    private function num($n)
    {
        return function_exists('kmpr_fa_num') ? kmpr_fa_num(number_format_i18n((int) $n)) : number_format_i18n((int) $n);
    }

    private function robots_preview()
    {
        $public = (string) get_option('blog_public');
        $out    = "User-agent: *\n";
        $out   .= $public === '0' ? "Disallow: /\n" : "Disallow: /wp-admin/\nAllow: /wp-admin/admin-ajax.php\n";
        return (string) apply_filters('robots_txt', $out, $public);
    }

    public function render()
    {
        if (!current_user_can(self::CAP)) {
            return;
        }
        $tab   = sanitize_key($_GET['tab'] ?? 'status'); // phpcs:ignore WordPress.Security.NonceVerification
        $msg   = sanitize_key($_GET['msg'] ?? ''); // phpcs:ignore WordPress.Security.NonceVerification
        $tabs  = array('status' => 'وضعیت', 'settings' => 'تنظیمات', 'preview' => 'پیش‌نمایش', 'validate' => 'اعتبارسنجی');
        $tab   = isset($tabs[$tab]) ? $tab : 'status';
        $site  = untrailingslashit(home_url('/'));
        ?>
        <div class="wrap kmpr-llms" dir="rtl">
            <style>
                .kmpr-llms{max-width:1180px}
                .kmpr-llms .kl-head{display:flex;flex-wrap:wrap;gap:16px;align-items:center;justify-content:space-between;margin:12px 0 16px}
                .kmpr-llms .kl-head h1{margin:0;font-size:22px;font-weight:700}
                .kmpr-llms .kl-head p{margin:6px 0 0;color:#50575e}
                .kmpr-llms .kl-actions{display:flex;flex-wrap:wrap;gap:8px}
                .kmpr-llms .kl-actions .button{white-space:nowrap}
                .kmpr-llms .kl-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:14px;margin:16px 0}
                .kmpr-llms .kl-card{background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:16px 18px}
                .kmpr-llms .kl-card h2,.kmpr-llms .kl-card h3{margin:0 0 10px;font-size:14px}
                .kmpr-llms .kl-big{font-size:26px;font-weight:700;line-height:1.2;margin:4px 0}
                .kmpr-llms .kl-muted{color:#646970;font-size:12px}
                .kmpr-llms .kl-pill{display:inline-block;border-radius:999px;padding:2px 10px;font-size:11px;font-weight:600;white-space:nowrap}
                .kmpr-llms .kl-ok{background:#edfaef;color:#007017}
                .kmpr-llms .kl-warn{background:#fcf9e8;color:#8a6100}
                .kmpr-llms .kl-error{background:#fcf0f1;color:#b32d2e}
                .kmpr-llms table.widefat td,.kmpr-llms table.widefat th{vertical-align:middle}
                .kmpr-llms .kl-pre{direction:ltr;text-align:left;unicode-bidi:plaintext;white-space:pre-wrap;word-break:break-word;background:#1d2327;color:#e2e8f0;border-radius:8px;padding:16px;max-height:620px;overflow:auto;font:12px/1.7 Menlo,Consolas,monospace}
                .kmpr-llms .kl-checks label{display:inline-flex;align-items:center;gap:6px;margin:0 0 8px 18px;min-height:28px}
                .kmpr-llms textarea.large-text{min-height:84px}
                .kmpr-llms .kl-list{margin:0;padding:0;list-style:none}
                .kmpr-llms .kl-list li{display:flex;gap:10px;align-items:flex-start;padding:9px 0;border-bottom:1px solid #f0f0f1}
                .kmpr-llms .kl-list li:last-child{border-bottom:0}
                @media (max-width:782px){.kmpr-llms .kl-checks label{display:flex}}
            </style>

            <div class="kl-head">
                <div>
                    <h1>مدیریت هوش مصنوعی و ایندکس محتوا (AI &amp; LLMs)</h1>
                    <p>فایل‌های <code>llms.txt</code> و <code>llms-full.txt</code>، نسخه Markdown صفحه‌ها و سیاست خزنده‌های هوش مصنوعی در robots.txt.</p>
                </div>
                <div class="kl-actions">
                    <form method="post" action="<?= esc_url(admin_url('admin-post.php')); ?>">
                        <?php wp_nonce_field('kmpr_llms_regen'); ?>
                        <input type="hidden" name="action" value="kmpr_llms_regen">
                        <button type="submit" class="button button-primary">بازسازی همین حالا</button>
                    </form>
                    <a class="button" href="<?= esc_url($site . '/llms.txt'); ?>" target="_blank" rel="noopener">llms.txt</a>
                    <a class="button" href="<?= esc_url($site . '/llms-full.txt'); ?>" target="_blank" rel="noopener">llms-full.txt</a>
                    <a class="button" href="<?= esc_url(wp_nonce_url($this->url(array('tab' => 'validate', 'run' => 1)), 'kmpr_llms_validate')); ?>">اعتبارسنجی</a>
                </div>
            </div>

            <?php if ($msg === 'regen') : ?>
                <div class="notice notice-success is-dismissible"><p>فایل‌ها بازسازی شدند.</p></div>
            <?php elseif ($msg === 'saved') : ?>
                <div class="notice notice-success is-dismissible"><p>تنظیمات ذخیره و فایل‌ها بازسازی شدند.</p></div>
            <?php endif; ?>

            <nav class="nav-tab-wrapper" aria-label="بخش‌ها">
                <?php foreach ($tabs as $k => $label) : ?>
                    <a href="<?= esc_url($this->url(array('tab' => $k))); ?>" class="nav-tab<?= $tab === $k ? ' nav-tab-active' : ''; ?>"><?= esc_html($label); ?></a>
                <?php endforeach; ?>
            </nav>

            <?php
            switch ($tab) {
                case 'settings':
                    $this->tab_settings();
                    break;
                case 'preview':
                    $this->tab_preview();
                    break;
                case 'validate':
                    $this->tab_validate();
                    break;
                default:
                    $this->tab_status();
            }
            ?>
        </div>
        <?php
    }

    /* ------------------------------------------------------------------ */

    private function tab_status()
    {
        $st    = KMPR_LLMs_Manager::state();
        $next  = wp_next_scheduled(KMPR_LLMs_Manager::HOOK);
        $files = array(
            'llms.txt'      => array('key' => 'txt', 'label' => 'فهرست موجز سایت'),
            'llms-full.txt' => array('key' => 'full', 'label' => 'متن کامل محتوا'),
        );
        $warnings = array();
        if (!empty($st['error'])) {
            $warnings[] = $st['error'];
        }
        $yoast = get_option('wpseo');
        if (is_array($yoast) && !empty($yoast['enable_llms_txt'])) {
            $warnings[] = 'قابلیت llms.txt افزونه Yoast روشن است و فایل را در ریشه سایت می‌نویسد که جلوی این ماژول را می‌گیرد. آن را در Yoast › تنظیمات › امکانات سایت خاموش کنید.';
        }
        foreach (array('llms.txt', 'llms-full.txt') as $f) {
            if (is_file(ABSPATH . $f)) {
                $warnings[] = 'فایل ثابت ' . $f . ' در ریشه سایت وجود دارد و وب‌سرور آن را مستقیم (بدون این ماژول) سرو می‌کند. آن را حذف کنید.';
            }
        }
        if ((string) get_option('blog_public') === '0') {
            $warnings[] = 'نمایش سایت برای موتورهای جستجو خاموش است (تنظیمات › خواندن)؛ بخش خزنده‌های هوش مصنوعی در robots.txt اضافه نمی‌شود.';
        }
        if (defined('DISABLE_WP_CRON') && DISABLE_WP_CRON) {
            $warnings[] = 'WP-Cron غیرفعال است؛ اگر کران واقعی سرور تنظیم نشده باشد، بازسازی هنگام اولین درخواست بعد از ۵ دقیقه انجام می‌شود.';
        }
        foreach ($warnings as $w) {
            echo '<div class="notice notice-warning inline"><p>' . esc_html($w) . '</p></div>';
        }
        ?>
        <div class="kl-grid">
            <?php foreach ($files as $name => $f) :
                $path  = KMPR_LLMs_Manager::path($name);
                $exist = is_readable($path);
                $info  = $st[$f['key']] ?? array();
                ?>
                <div class="kl-card">
                    <h2><?= esc_html($name); ?> <span class="kl-pill <?= $exist ? 'kl-ok' : 'kl-error'; ?>"><?= $exist ? 'آماده' : 'ساخته نشده'; ?></span></h2>
                    <div class="kl-big"><?= esc_html($exist ? size_format(filesize($path), 1) : '—'); ?></div>
                    <div class="kl-muted"><?= esc_html($f['label']); ?> · <?= esc_html($this->num($info['words'] ?? 0)); ?> کلمه · حدود <?= esc_html($this->num((int) (($info['chars'] ?? 0) / 3))); ?> توکن</div>
                </div>
            <?php endforeach; ?>
            <div class="kl-card">
                <h2>آخرین ساخت</h2>
                <div class="kl-big" style="font-size:18px"><?= esc_html($this->date($st['generated'] ?? 0)); ?></div>
                <div class="kl-muted">
                    زمان ساخت: <?= esc_html($this->num($st['duration'] ?? 0)); ?> میلی‌ثانیه
                    <?php if (!empty($st['truncated'])) : ?> · <?= esc_html($this->num($st['truncated'])); ?> بخش کوتاه شد<?php endif; ?>
                </div>
                <p class="kl-muted" style="margin:8px 0 0">
                    <?php if ($next) : ?>
                        <span class="kl-pill kl-warn">در صف</span> بازسازی خودکار <?= esc_html($this->num(max(0, $next - time()))); ?> ثانیه دیگر
                    <?php else : ?>
                        <span class="kl-pill kl-ok">به‌روز</span> با هر تغییر محتوا، ۶۰ ثانیه بعد از آخرین ذخیره بازسازی می‌شود.
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <div class="kl-card">
            <h2>محتوای پوشش‌داده‌شده</h2>
            <table class="widefat striped">
                <thead><tr><th>بخش</th><th>llms.txt (پیوند)</th><th>llms-full.txt (مورد)</th><th>حجم در llms-full.txt</th></tr></thead>
                <tbody>
                <?php
                $tc     = (array) ($st['txt_counts'] ?? array());
                $fc     = (array) ($st['full_counts'] ?? array());
                $fs     = (array) ($st['full_sizes'] ?? array());
                $labels = array(
                    'about'     => array('درباره من، رزومه و همکاری', 'about', 'about'),
                    'services'  => array('خدمات', 'services', 'services'),
                    'courses'   => array('دوره‌ها / درس‌ها', 'courses', 'courses'),
                    'portfolio' => array('نمونه‌کارها', 'portfolio', 'portfolio'),
                    'games'     => array('مرکز بازی', 'games', 'games'),
                    'faq'       => array('پرسش‌های متداول', '', 'faq'),
                    'optional'  => array('Optional', 'optional', ''),
                );
                foreach ($labels as $row) :
                    $t = $row[1] !== '' && isset($tc[$row[1]]) ? (int) $tc[$row[1]] : null;
                    if ($row[1] === 'courses' && isset($tc['lessons'])) {
                        $t = $this->num($tc['courses'] ?? 0) . ' دوره، ' . $this->num($tc['lessons']) . ' درس';
                    } elseif ($row[1] === 'portfolio' && isset($tc['archived'])) {
                        $t = $this->num($tc['portfolio'] ?? 0) . ' (' . $this->num($tc['archived']) . ' آرشیو)';
                    } elseif ($t !== null) {
                        $t = $this->num($t);
                    }
                    $f  = $row[2] !== '' && isset($fc[$row[2]]) ? $this->num($fc[$row[2]]) : '—';
                    $sz = $row[2] !== '' && isset($fs[$row[2]]) ? size_format($fs[$row[2]], 1) : '—';
                    ?>
                    <tr><td><?= esc_html($row[0]); ?></td><td><?= esc_html($t ?? '—'); ?></td><td><?= esc_html($f); ?></td><td dir="ltr" style="text-align:right"><?= esc_html($sz); ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <p class="kl-muted">فقط محتوای منتشرشده، بدون رمز و قابل ایندکس (طبق تنظیم noindex یوست) وارد فایل‌ها می‌شود. محل ذخیره: <code dir="ltr"><?= esc_html(str_replace(ABSPATH, '/', KMPR_LLMs_Manager::dir())); ?></code></p>
        </div>
        <?php
    }

    /* ------------------------------------------------------------------ */

    private function tab_settings()
    {
        $s    = KMPR_LLMs_Manager::settings();
        $b    = KMPR_LLMs_Manager::builder();
        $txt  = array('about' => 'درباره من و شروع همکاری', 'services' => 'خدمات', 'courses' => 'دوره‌ها و درس‌ها', 'portfolio' => 'نمونه‌کارها', 'games' => 'مرکز بازی', 'optional' => 'Optional (پیوندهای کم‌اهمیت)');
        $full = array('about' => 'درباره من و رزومه', 'services' => 'خدمات (خروجی‌ها، مراحل، سؤالات)', 'courses' => 'متن کامل درس‌ها', 'portfolio' => 'کیس‌استادی نمونه‌کارها', 'games' => 'بازی‌ها', 'faq' => 'پرسش‌های متداول');
        ?>
        <form method="post" action="<?= esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('kmpr_llms_save'); ?>
            <input type="hidden" name="action" value="kmpr_llms_save">

            <div class="kl-card" style="margin-top:16px">
                <h2>متن معرفی</h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="kl-summary">خلاصه (blockquote)</label></th>
                        <td>
                            <textarea id="kl-summary" name="summary" class="large-text" rows="3" placeholder="<?= esc_attr($b->summary()); ?>"><?= esc_textarea($s['summary']); ?></textarea>
                            <p class="description">یک یا دو جمله اول‌شخص و واقعی. خالی = متن پیش‌فرض (متن placeholder).</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="kl-intro">توضیح تکمیلی</label></th>
                        <td>
                            <textarea id="kl-intro" name="intro" class="large-text" rows="4"><?= esc_textarea($s['intro']); ?></textarea>
                            <p class="description">اختیاری؛ بعد از خلاصه و قبل از بخش‌ها می‌آید (Markdown ساده، بدون تیتر).</p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="kl-grid">
                <div class="kl-card kl-checks">
                    <h3>بخش‌های llms.txt</h3>
                    <?php foreach ($txt as $k => $label) : ?>
                        <label><input type="checkbox" name="txt[<?= esc_attr($k); ?>]" value="1" <?php checked(!empty($s['txt'][$k])); ?>> <?= esc_html($label); ?></label><br>
                    <?php endforeach; ?>
                </div>
                <div class="kl-card kl-checks">
                    <h3>بخش‌های llms-full.txt</h3>
                    <?php foreach ($full as $k => $label) : ?>
                        <label><input type="checkbox" name="full[<?= esc_attr($k); ?>]" value="1" <?php checked(!empty($s['full'][$k])); ?>> <?= esc_html($label); ?></label><br>
                    <?php endforeach; ?>
                </div>
                <div class="kl-card kl-checks">
                    <h3>رفتار</h3>
                    <label><input type="checkbox" name="archived" value="1" <?php checked(!empty($s['archived'])); ?>> نمونه‌کارهای «آرشیو» هم فهرست شوند (با برچسب)</label><br>
                    <label><input type="checkbox" name="md" value="1" <?php checked(!empty($s['md'])); ?>> نسخه Markdown صفحه‌ها (<code>.md</code> و <code>?format=md</code>)</label><br>
                    <label><input type="checkbox" name="link_md" value="1" <?php checked(!empty($s['link_md'])); ?>> پیوندهای llms.txt به نسخه <code>.md</code> اشاره کنند</label>
                    <p class="kl-muted">پیش‌نویس، خصوصی، رمزدار و noindex هیچ‌وقت منتشر نمی‌شوند (۴۰۴).</p>
                </div>
            </div>

            <div class="kl-card">
                <h2>سیاست خزنده‌های هوش مصنوعی (robots.txt)</h2>
                <p class="kl-muted">ربات‌های «جستجو/پاسخ» باعث دیده‌شدن و ارجاع به سایت در ChatGPT، Claude و Perplexity می‌شوند. ربات‌های «آموزش» محتوا را برای آموزش مدل جمع می‌کنند و ارجاع مستقیمی نمی‌دهند. گروه‌های «مجاز» همان قواعد <code>User-agent: *</code> را می‌گیرند.</p>
                <table class="widefat striped">
                    <thead><tr><th>User-agent</th><th>شرکت</th><th>کاربرد</th><th>اجازه</th></tr></thead>
                    <tbody>
                    <?php foreach (KMPR_LLMs_Manager::bots() as $ua => $info) :
                        $v    = $s['bots'][$ua] ?? 'allow';
                        $kind = array('training' => array('آموزش', 'kl-warn'), 'search' => array('جستجو', 'kl-ok'), 'user' => array('درخواست کاربر', 'kl-ok'))[$info[2]];
                        ?>
                        <tr>
                            <td dir="ltr" style="text-align:right"><code><?= esc_html($ua); ?></code></td>
                            <td><?= esc_html($info[0]); ?></td>
                            <td><span class="kl-pill <?= esc_attr($kind[1]); ?>"><?= esc_html($kind[0]); ?></span> <span class="kl-muted"><?= esc_html($info[1]); ?></span></td>
                            <td style="white-space:nowrap">
                                <label><input type="radio" name="bots[<?= esc_attr($ua); ?>]" value="allow" <?php checked($v, 'allow'); ?>> مجاز</label>
                                &nbsp;
                                <label><input type="radio" name="bots[<?= esc_attr($ua); ?>]" value="deny" <?php checked($v, 'deny'); ?>> مسدود</label>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <p><button type="submit" class="button button-primary button-large">ذخیره و بازسازی</button></p>
        </form>
        <?php
    }

    /* ------------------------------------------------------------------ */

    private function tab_preview()
    {
        $which = (($_GET['file'] ?? '') === 'full') ? 'llms-full.txt' : 'llms.txt'; // phpcs:ignore WordPress.Security.NonceVerification
        $path  = KMPR_LLMs_Manager::path($which);
        $limit = 300000;
        $body  = is_readable($path) ? (string) file_get_contents($path, false, null, 0, $limit) : '';
        $more  = is_readable($path) && filesize($path) > $limit;
        if ($more) {
            $body = mb_strcut($body, 0, $limit, 'UTF-8');
        }
        ?>
        <p class="subsubsub" style="float:none;margin:14px 0">
            <a href="<?= esc_url($this->url(array('tab' => 'preview'))); ?>" class="<?= $which === 'llms.txt' ? 'current' : ''; ?>">llms.txt</a> |
            <a href="<?= esc_url($this->url(array('tab' => 'preview', 'file' => 'full'))); ?>" class="<?= $which === 'llms-full.txt' ? 'current' : ''; ?>">llms-full.txt</a>
        </p>
        <?php if ($body === '') : ?>
            <div class="notice notice-info inline"><p>فایل هنوز ساخته نشده است؛ «بازسازی همین حالا» را بزنید.</p></div>
        <?php else : ?>
            <?php if ($more) : ?><p class="kl-muted">فقط ۳۰۰ کیلوبایت اول نمایش داده شده است؛ نسخه کامل را در زبانه جدید باز کنید.</p><?php endif; ?>
            <pre class="kl-pre"><?= esc_html($body); ?></pre>
        <?php endif;
    }

    /* ------------------------------------------------------------------ */

    private function tab_validate()
    {
        $run = isset($_GET['run'], $_GET['_wpnonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'kmpr_llms_validate');
        if (!$run) {
            echo '<p style="margin-top:16px"><a class="button button-primary" href="' . esc_url(wp_nonce_url($this->url(array('tab' => 'validate', 'run' => 1)), 'kmpr_llms_validate')) . '">اجرای اعتبارسنجی</a></p>';
            echo '<p class="kl-muted">ساختار llms.txt طبق llmstxt.org، پیوندهای داخلی شکسته، حجم فایل‌ها و معتبر بودن دستورهای robots.txt بررسی می‌شود.</p>';
            return;
        }
        require_once dirname(__FILE__) . '/builder.php';
        $txt    = is_readable(KMPR_LLMs_Manager::path('llms.txt')) ? (string) file_get_contents(KMPR_LLMs_Manager::path('llms.txt')) : '';
        $full   = is_readable(KMPR_LLMs_Manager::path('llms-full.txt')) ? (string) file_get_contents(KMPR_LLMs_Manager::path('llms-full.txt')) : '';
        $robots = $this->robots_preview();
        $res    = KMPR_LLMs_Builder::validate($txt, $full, $robots);
        $icons  = array('ok' => array('yes-alt', 'kl-ok', 'درست'), 'warn' => array('warning', 'kl-warn', 'هشدار'), 'error' => array('dismiss', 'kl-error', 'خطا'));
        ?>
        <div class="kl-card" style="margin-top:16px">
            <h2>نتیجه اعتبارسنجی</h2>
            <ul class="kl-list">
                <?php foreach ($res as $r) : $i = $icons[$r[0]]; ?>
                    <li><span class="kl-pill <?= esc_attr($i[1]); ?>"><?= esc_html($i[2]); ?></span><span><?= esc_html($r[1]); ?></span></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="kl-card" style="margin-top:16px">
            <h2>robots.txt نهایی</h2>
            <pre class="kl-pre"><?= esc_html($robots); ?></pre>
        </div>
        <?php
    }
}
