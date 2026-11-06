<?php
/**
 * Comprehensive LLMs.txt & AI Discovery Management System
 * 
 * Generates and maintains /llms.txt and /llms-full.txt conforming to the llmstxt.org specification.
 * Auto-updates upon any content change and provides a management dashboard in wp-admin.
 */

if (!defined('ABSPATH')) {
    exit;
}

class KMPR_LLMs_Manager {

    private $last_run_transient = 'kmpr_llms_last_regenerated';

    public function __construct() {
        // Direct early intercept for llms.txt & llms-full.txt
        add_action('init', array($this, 'handle_early_request'), 1);

        // Auto-initialize files if not on disk
        add_action('init', array($this, 'ensure_files_exist'), 99);

        // Rewrite rules for serving llms.txt & llms-full.txt dynamically if needed
        add_action('init', array($this, 'add_rewrite_rules'));
        add_filter('query_vars', array($this, 'register_query_vars'));
        add_action('template_redirect', array($this, 'handle_dynamic_request'));

        // Auto-update hooks on any content change
        add_action('save_post', array($this, 'trigger_auto_regenerate'), 20, 2);
        add_action('delete_post', array($this, 'trigger_auto_regenerate'));
        add_action('trash_post', array($this, 'trigger_auto_regenerate'));
        add_action('untrash_post', array($this, 'trigger_auto_regenerate'));
        add_action('acf/save_post', array($this, 'trigger_auto_regenerate'), 20);

        // Robots.txt AI directives
        add_filter('robots_txt', array($this, 'enhance_robots_txt'), 99, 2);

        // Admin Dashboard Menu
        add_action('admin_menu', array($this, 'register_admin_menu'));
        add_action('admin_init', array($this, 'handle_manual_regenerate'));
    }

    /**
     * Early request intercept to serve llms.txt & llms-full.txt immediately
     */
    public function handle_early_request() {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $path = parse_url($uri, PHP_URL_PATH);

        if ($path === '/llms.txt') {
            header('Content-Type: text/plain; charset=utf-8');
            header('X-Robots-Tag: index, follow');
            echo $this->build_llms_txt();
            exit;
        }

        if ($path === '/llms-full.txt') {
            header('Content-Type: text/plain; charset=utf-8');
            header('X-Robots-Tag: index, follow');
            echo $this->build_llms_full_txt();
            exit;
        }
    }

    /**
     * Ensure files exist on disk and are updated
     */
    public function ensure_files_exist() {
        $llms_path = ABSPATH . 'llms.txt';
        $full_path = ABSPATH . 'llms-full.txt';
        if (!file_exists($llms_path) || !file_exists($full_path) || (time() - @filemtime($llms_path) > 86400)) {
            $this->save_files_to_disk();
            flush_rewrite_rules(false);
        }
    }

    /**
     * Add rewrite rules for virtual fallback serving
     */
    public function add_rewrite_rules() {
        add_rewrite_rule('^llms\.txt$', 'index.php?kmpr_llms=1', 'top');
        add_rewrite_rule('^llms-full\.txt$', 'index.php?kmpr_llms_full=1', 'top');
    }

    public function register_query_vars($vars) {
        $vars[] = 'kmpr_llms';
        $vars[] = 'kmpr_llms_full';
        return $vars;
    }

    /**
     * Handle dynamic requests for llms.txt and llms-full.txt
     */
    public function handle_dynamic_request() {
        if (get_query_var('kmpr_llms')) {
            header('Content-Type: text/plain; charset=utf-8');
            header('X-Robots-Tag: index, follow');
            echo $this->build_llms_txt();
            exit;
        }

        if (get_query_var('kmpr_llms_full')) {
            header('Content-Type: text/plain; charset=utf-8');
            header('X-Robots-Tag: index, follow');
            echo $this->build_llms_full_txt();
            exit;
        }
    }

    /**
     * Add AI Crawler directives to robots.txt
     */
    public function enhance_robots_txt($output, $public) {
        $site_url = untrailingslashit(home_url('/'));
        
        $ai_rules  = "\n# --------------------------------------------------\n";
        $ai_rules .= "# AI Search Engines & LLM Discovery (llmstxt.org)\n";
        $ai_rules .= "# --------------------------------------------------\n";
        $ai_rules .= "User-agent: GPTBot\nAllow: /\n\n";
        $ai_rules .= "User-agent: ClaudeBot\nAllow: /\n\n";
        $ai_rules .= "User-agent: PerplexityBot\nAllow: /\n\n";
        $ai_rules .= "User-agent: Google-Extended\nAllow: /\n\n";
        $ai_rules .= "User-agent: Applebot-Extended\nAllow: /\n\n";
        $ai_rules .= "User-agent: CCBot\nAllow: /\n\n";
        $ai_rules .= "llms-txt: " . $site_url . "/llms.txt\n";
        $ai_rules .= "llms-full-txt: " . $site_url . "/llms-full.txt\n";

        return $output . $ai_rules;
    }

    /**
     * Auto-regenerate files on content save
     */
    public function trigger_auto_regenerate($post_id = 0, $post = null) {
        // Skip revisions, autosaves, or nav_menu_items
        if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
            return;
        }

        if ($post && in_array($post->post_type, array('revision', 'nav_menu_item', 'acf-field', 'acf-field-group'))) {
            return;
        }

        // Throttle regeneration to prevent redundant writes within 3 seconds
        $last_run = get_transient($this->last_run_transient);
        if ($last_run) {
            return;
        }

        set_transient($this->last_run_transient, time(), 3);
        $this->save_files_to_disk();
    }

    /**
     * Build the standard llms.txt content
     */
    public function build_llms_txt() {
        $site_name = get_bloginfo('name');
        $site_desc = get_bloginfo('description');
        $site_url  = untrailingslashit(home_url('/'));
        $date_now  = function_exists('shamsi_date') ? shamsi_date('Y/m/d - H:i', time()) : date('Y-m-d');

        $out = "# {$site_name}\n\n";
        $out .= "> {$site_desc} - آژانس تخصصی طراحی وب‌سایت، سئو، توسعه نرم‌افزار و راهکارهای جامع دیجیتال مارکتینگ.\n\n";
        $out .= "این مستند با استاندارد رسمی [llmstxt.org](https://llmstxt.org/) برای معرفی دقیق ساختار، خدمات، نمونه‌کارها و اطلاعات وبکو به مدل‌های زبانی هوش مصنوعی (LLMs) و ربات‌های کاوشگر ایجاد شده است.\n\n";
        $out .= "- **آدرس وب‌سایت:** {$site_url}\n";
        $out .= "- **تاریخ آخرین به‌روزرسانی:** {$date_now}\n";
        $out .= "- **زبان اصلی:** فارسی (fa-IR)\n";
        $out .= "- **مستند تفصیلی و کامل:** [{$site_url}/llms-full.txt]({$site_url}/llms-full.txt)\n\n";

        // 1. Services Section
        $out .= "## خدمات تخصصی (Services)\n\n";
        $services_query = new WP_Query(array(
            'post_type'      => 'services',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'order'          => 'DESC'
        ));

        if ($services_query->have_posts()) {
            while ($services_query->have_posts()) {
                $services_query->the_post();
                $title = get_the_title();
                $link  = get_permalink();
                $excerpt = wp_strip_all_tags(get_the_excerpt() ?: wp_trim_words(get_the_content(), 20));
                $badge   = get_field('services_badge') ? ' (' . get_field('services_badge') . ')' : '';
                $out .= "- [{$title}{$badge}]({$link}): {$excerpt}\n";
            }
            wp_reset_postdata();
        } else {
            $out .= "- خدمات طراحی سایت اختصاصی، بهینه‌سازی موتورهای جستجو (سئو)، پشتیبانی فنی و امنیتی.\n";
        }
        $out .= "\n";

        // 2. Portfolios Section
        $out .= "## نمونه‌کارها و پروژه‌ها (Portfolio)\n\n";
        $portfolio_query = new WP_Query(array(
            'post_type'      => 'portfolio',
            'post_status'    => 'publish',
            'posts_per_page' => 12,
            'order'          => 'DESC'
        ));

        if ($portfolio_query->have_posts()) {
            while ($portfolio_query->have_posts()) {
                $portfolio_query->the_post();
                $title = get_the_title();
                $link  = get_permalink();
                $excerpt = wp_strip_all_tags(get_the_excerpt() ?: wp_trim_words(get_the_content(), 18));
                $out .= "- [{$title}]({$link}): {$excerpt}\n";
            }
            wp_reset_postdata();
        }
        $out .= "\n";

        // 3. Articles & Insights
        $out .= "## مقالات و راهنماهای آموزشی (Articles & Blog)\n\n";
        $posts_query = new WP_Query(array(
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => 10,
            'order'          => 'DESC'
        ));

        if ($posts_query->have_posts()) {
            while ($posts_query->have_posts()) {
                $posts_query->the_post();
                $title = get_the_title();
                $link  = get_permalink();
                $excerpt = wp_strip_all_tags(get_the_excerpt() ?: wp_trim_words(get_the_content(), 18));
                $out .= "- [{$title}]({$link}): {$excerpt}\n";
            }
            wp_reset_postdata();
        }
        $out .= "\n";

        // 4. Contact & Identity
        $phone = get_field('phone', 'option') ?: 'تماس از طریق وب‌سایت';
        $email = get_field('email', 'option') ?: 'info@kmpr.ir';
        
        $out .= "## راه‌های ارتباطی و مشاوره (Contact & Support)\n\n";
        $out .= "- **تلفن تماس:** {$phone}\n";
        $out .= "- **پست الکترونیک:** {$email}\n";
        $out .= "- **صفحه درباره ما:** [{$site_url}/about-us/]({$site_url}/about-us/)\n";
        $out .= "- **صفحه ارتباط با ما:** [{$site_url}/contact-us/]({$site_url}/contact-us/)\n";

        return $out;
    }

    /**
     * Build the comprehensive llms-full.txt content
     */
    public function build_llms_full_txt() {
        $site_name = get_bloginfo('name');
        $site_desc = get_bloginfo('description');
        $site_url  = untrailingslashit(home_url('/'));
        $date_now  = function_exists('shamsi_date') ? shamsi_date('Y/m/d - H:i', time()) : date('Y-m-d');

        $out = "# {$site_name} - دانشنامه و راهنمای کامل هوش مصنوعی (Full Knowledge Base)\n\n";
        $out .= "> {$site_desc}\n\n";
        $out .= "این فایل نسخه کامل و تفصیلی تمام اطلاعات ساختاریافته، توضیحات خدمات و تعرفه‌ها، نمونه‌کارها و مقالات وب‌سایت وبکو است که به منظور درک کامل و دقیق زمینه فعالیت این کسب‌وکار برای مدل‌های هوش مصنوعی ایجاد شده است.\n\n";
        $out .= "تاریخ آخرین به‌روزرسانی: {$date_now}\n\n";
        $out .= "---\n\n";

        // Detailed Services with Proposals/Pricing
        $out .= "# ۱. خدمات تخصصی و تعرفه‌ها (Detailed Services & Plans)\n\n";
        $services_query = new WP_Query(array(
            'post_type'      => 'services',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'order'          => 'DESC'
        ));

        if ($services_query->have_posts()) {
            while ($services_query->have_posts()) {
                $services_query->the_post();
                $service_id = get_the_ID();
                $out .= "## " . get_the_title() . "\n";
                $out .= "- **آدرس صفحه:** " . get_permalink() . "\n";
                $badge = get_field('services_badge', $service_id);
                if ($badge) {
                    $out .= "- **برچسب خدمت:** " . $badge . "\n";
                }
                $content = wp_strip_all_tags(get_the_content());
                $out .= "\n### توضیحات کامل:\n" . $content . "\n\n";

                // If Proposal rows exist, output all plan tiers
                if (have_rows('proposal', $service_id)) {
                    $out .= "### پلن‌ها و تعرفه‌ها:\n";
                    while (have_rows('proposal', $service_id)) {
                        the_row();
                        $tier_title = get_sub_field('proposal_title');
                        $tier_desc  = get_sub_field('proposal_description');
                        $out .= "#### پلن: {$tier_title}\n";
                        if ($tier_desc) {
                            $out .= "- توضیحات: {$tier_desc}\n";
                        }
                        if (have_rows('proposal_row')) {
                            while (have_rows('proposal_row')) {
                                the_row();
                                $label = get_sub_field('Property_label');
                                $val   = get_sub_field('Property_value');
                                if ($label) {
                                    $out .= "  * {$label}: {$val}\n";
                                }
                            }
                        }
                    }
                    $out .= "\n";
                }
                $out .= "---\n\n";
            }
            wp_reset_postdata();
        }

        // Detailed Portfolios
        $out .= "# ۲. پروژه‌ها و نمونه‌کارهای اجرا شده (Portfolio Cases)\n\n";
        $portfolio_query = new WP_Query(array(
            'post_type'      => 'portfolio',
            'post_status'    => 'publish',
            'posts_per_page' => 20,
            'order'          => 'DESC'
        ));

        if ($portfolio_query->have_posts()) {
            while ($portfolio_query->have_posts()) {
                $portfolio_query->the_post();
                $out .= "## " . get_the_title() . "\n";
                $out .= "- **آدرس پروژه:** " . get_permalink() . "\n";
                $out .= "### شرح پروژه:\n" . wp_strip_all_tags(get_the_content()) . "\n\n";
            }
            wp_reset_postdata();
        }

        // Key Articles
        $out .= "# ۳. مقالات آموزشی و تحلیل‌های تخصصی (Articles)\n\n";
        $posts_query = new WP_Query(array(
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => 15,
            'order'          => 'DESC'
        ));

        if ($posts_query->have_posts()) {
            while ($posts_query->have_posts()) {
                $posts_query->the_post();
                $out .= "## " . get_the_title() . "\n";
                $out .= "- **آدرس مقاله:** " . get_permalink() . "\n";
                $out .= "- **خلاصه مطلب:** " . wp_strip_all_tags(get_the_excerpt() ?: wp_trim_words(get_the_content(), 40)) . "\n\n";
            }
            wp_reset_postdata();
        }

        return $out;
    }

    /**
     * Write llms.txt and llms-full.txt to web root directory
     */
    public function save_files_to_disk() {
        $root_path = ABSPATH;
        $llms_path = $root_path . 'llms.txt';
        $full_path = $root_path . 'llms-full.txt';

        $llms_content = $this->build_llms_txt();
        $full_content = $this->build_llms_full_txt();

        @file_put_contents($llms_path, $llms_content);
        @file_put_contents($full_path, $full_content);

        update_option('kmpr_llms_last_generated', time());
        update_option('kmpr_llms_file_size', @filesize($llms_path) ?: strlen($llms_content));
        update_option('kmpr_llms_full_file_size', @filesize($full_path) ?: strlen($full_content));
        update_option('kmpr_llms_word_count', str_word_count($llms_content));
        update_option('kmpr_llms_full_word_count', str_word_count($full_content));
    }

    /**
     * Register Admin Dashboard Menu
     */
    public function register_admin_menu() {
        add_menu_page(
            'مدیریت هوش مصنوعی و LLMs',
            'هوش مصنوعی و LLMs',
            'manage_options',
            'kmpr-ai-llms',
            array($this, 'render_admin_page'),
            'dashicons-rest-api',
            65
        );
    }

    /**
     * Handle manual regenerate action from admin button
     */
    public function handle_manual_regenerate() {
        if (isset($_POST['kmpr_regenerate_llms']) && check_admin_referer('kmpr_regenerate_llms_action', 'kmpr_regenerate_llms_nonce')) {
            $this->save_files_to_disk();
            wp_safe_redirect(add_query_arg(array('page' => 'kmpr-ai-llms', 'updated' => 'true'), admin_url('admin.php')));
            exit;
        }
    }

    /**
     * Render the Admin Dashboard Interface
     */
    public function render_admin_page() {
        $root_path = ABSPATH;
        $llms_file = $root_path . 'llms.txt';
        $full_file = $root_path . 'llms-full.txt';

        $llms_exists = file_exists($llms_file);
        $full_exists = file_exists($full_file);

        $last_generated = get_option('kmpr_llms_last_generated', $llms_exists ? filemtime($llms_file) : 0);
        $formatted_date = ($last_generated && function_exists('shamsi_date')) ? shamsi_date('d F Y - ساعت H:i', $last_generated) : 'هنوز ایجاد نشده';

        $llms_size = $llms_exists ? size_format(filesize($llms_file), 2) : '0 KB';
        $full_size = $full_exists ? size_format(filesize($full_file), 2) : '0 KB';

        $llms_words = get_option('kmpr_llms_word_count', $llms_exists ? str_word_count(file_get_contents($llms_file)) : 0);
        $full_words = get_option('kmpr_llms_full_word_count', $full_exists ? str_word_count(file_get_contents($full_file)) : 0);

        $est_tokens = round($full_words * 1.35); // Persian token multiplier approximation

        // Content counts
        $services_count  = wp_count_posts('services')->publish ?? 0;
        $portfolio_count = wp_count_posts('portfolio')->publish ?? 0;
        $posts_count     = wp_count_posts('post')->publish ?? 0;
        $pages_count     = wp_count_posts('page')->publish ?? 0;

        $site_url = untrailingslashit(home_url('/'));
        ?>
        <div class="wrap" style="direction: rtl; text-align: right; font-family: inherit; max-width: 1100px; margin-top: 20px;">
            
            <!-- Page Header -->
            <div style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: #ffffff; padding: 28px; border-radius: 16px; margin-bottom: 24px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
                    <div>
                        <span style="background: rgba(217, 119, 6, 0.2); color: #f59e0b; padding: 4px 12px; border-radius: 9999px; font-size: 11px; font-weight: 800; border: 1px solid rgba(217,119,6,0.3); display: inline-block; margin-bottom: 8px;">
                            استاندارد رسمی LLMs.txt
                        </span>
                        <h1 style="color: #ffffff; margin: 0; font-size: 24px; font-weight: 900;">مدیریت هوش مصنوعی و ایندکس محتوا (AI & LLMs)</h1>
                        <p style="color: #94a3b8; margin: 8px 0 0; font-size: 13px;">
                            تولید، همگام‌سازی و مدیریت فایل‌های <code>llms.txt</code> و <code>llms-full.txt</code> جهت شناسایی جامع سایت توسط ChatGPT، Claude، Perplexity و Gemini.
                        </p>
                    </div>

                    <form method="post" action="">
                        <?php wp_nonce_field('kmpr_regenerate_llms_action', 'kmpr_regenerate_llms_nonce'); ?>
                        <button type="submit" name="kmpr_regenerate_llms" class="button button-primary" 
                                style="background: #d97706; border-color: #b45309; padding: 8px 24px; height: auto; font-size: 14px; font-weight: 800; border-radius: 12px; cursor: pointer; box-shadow: 0 4px 12px rgba(217,119,6,0.3);">
                            ⚡ بازسازی و به‌روزرسانی فایل‌های هوش مصنوعی
                        </button>
                    </form>
                </div>
            </div>

            <?php if (isset($_GET['updated']) && $_GET['updated'] === 'true') : ?>
                <div class="notice notice-success is-dismissible" style="border-radius: 8px; border-right-color: #10b981; padding: 12px 16px;">
                    <p style="font-weight: 800; color: #065f46; margin: 0;">فایل‌های llms.txt و llms-full.txt با موفقیت بازسازی و بر روی ریشه سایت ذخیره شدند!</p>
                </div>
            <?php endif; ?>

            <!-- Status Cards Grid -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px; margin-bottom: 24px;">
                
                <!-- Card 1: llms.txt -->
                <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                        <strong style="font-size: 15px; color: #1e293b;">فایل اصلی llms.txt</strong>
                        <?php if ($llms_exists) : ?>
                            <span style="background: #ecfdf5; color: #059669; font-size: 11px; font-weight: 800; padding: 3px 8px; border-radius: 6px;">فعال و آماده</span>
                        <?php else: ?>
                            <span style="background: #fef2f2; color: #dc2626; font-size: 11px; font-weight: 800; padding: 3px 8px; border-radius: 6px;">ایجاد نشده</span>
                        <?php endif; ?>
                    </div>
                    <p style="font-size: 12px; color: #64748b; margin: 4px 0;">حجم فایل: <strong style="color: #0f172a;"><?= $llms_size; ?></strong></p>
                    <p style="font-size: 12px; color: #64748b; margin: 4px 0;">تعداد کلمات: <strong style="color: #0f172a;"><?= number_format($llms_words); ?> کلمه</strong></p>
                    <div style="margin-top: 14px;">
                        <a href="<?= esc_url($site_url . '/llms.txt'); ?>" target="_blank" class="button" style="width: 100%; text-align: center; border-radius: 8px; font-weight: 700;">
                            مشاهده مستقیم /llms.txt &larr;
                        </a>
                    </div>
                </div>

                <!-- Card 2: llms-full.txt -->
                <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                        <strong style="font-size: 15px; color: #1e293b;">دانشنامه کامل llms-full.txt</strong>
                        <?php if ($full_exists) : ?>
                            <span style="background: #ecfdf5; color: #059669; font-size: 11px; font-weight: 800; padding: 3px 8px; border-radius: 6px;">فعال و آماده</span>
                        <?php else: ?>
                            <span style="background: #fef2f2; color: #dc2626; font-size: 11px; font-weight: 800; padding: 3px 8px; border-radius: 6px;">ایجاد نشده</span>
                        <?php endif; ?>
                    </div>
                    <p style="font-size: 12px; color: #64748b; margin: 4px 0;">حجم فایل: <strong style="color: #0f172a;"><?= $full_size; ?></strong></p>
                    <p style="font-size: 12px; color: #64748b; margin: 4px 0;">تخمین توکن کانتکس: <strong style="color: #0f172a;">~<?= number_format($est_tokens); ?> Token</strong></p>
                    <div style="margin-top: 14px;">
                        <a href="<?= esc_url($site_url . '/llms-full.txt'); ?>" target="_blank" class="button" style="width: 100%; text-align: center; border-radius: 8px; font-weight: 700;">
                            مشاهده مستقیم /llms-full.txt &larr;
                        </a>
                    </div>
                </div>

                <!-- Card 3: Auto Update Status -->
                <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                        <strong style="font-size: 15px; color: #1e293b;">سیستم به‌روزرسانی خودکار</strong>
                        <span style="background: #eff6ff; color: #2563eb; font-size: 11px; font-weight: 800; padding: 3px 8px; border-radius: 6px;">خودکار (Auto-Sync)</span>
                    </div>
                    <p style="font-size: 12px; color: #64748b; margin: 4px 0;">وضعیت هوک: <strong style="color: #10b981;">متصل به save_post و acf/save</strong></p>
                    <p style="font-size: 12px; color: #64748b; margin: 4px 0;">آخرین سینک: <strong style="color: #0f172a;"><?= $formatted_date; ?></strong></p>
                    <p style="font-size: 11px; color: #94a3b8; margin-top: 10px;">با هر ایجاد، ویرایش یا حذف خدمات، پروژه‌ها و نوشته‌ها، فایل‌ها در لحظه بازسازی می‌شوند.</p>
                </div>

            </div>

            <!-- Indexed Content Breakdown -->
            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 24px; margin-bottom: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                <h3 style="margin-top: 0; margin-bottom: 16px; font-size: 16px; font-weight: 800; color: #1e293b;">محتوای استخراج شده و معرفی‌شده به مدل‌های هوش مصنوعی</h3>
                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; text-align: center;">
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px;">
                        <span style="font-size: 24px; font-weight: 900; color: #d97706; display: block;"><?= $services_count; ?></span>
                        <span style="font-size: 12px; font-weight: 700; color: #64748b;">خدمات تخصصی با پلن‌ها</span>
                    </div>
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px;">
                        <span style="font-size: 24px; font-weight: 900; color: #2563eb; display: block;"><?= $portfolio_count; ?></span>
                        <span style="font-size: 12px; font-weight: 700; color: #64748b;">نمونه‌کارها و پروژه‌ها</span>
                    </div>
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px;">
                        <span style="font-size: 24px; font-weight: 900; color: #10b981; display: block;"><?= $posts_count; ?></span>
                        <span style="font-size: 12px; font-weight: 700; color: #64748b;">مقالات و آموزش‌ها</span>
                    </div>
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px;">
                        <span style="font-size: 24px; font-weight: 900; color: #8b5cf6; display: block;"><?= $pages_count; ?></span>
                        <span style="font-size: 12px; font-weight: 700; color: #64748b;">صفحات اصلی وب‌سایت</span>
                    </div>
                </div>
            </div>

            <!-- Preview Box -->
            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <h3 style="margin: 0; font-size: 16px; font-weight: 800; color: #1e293b;">پیش‌نمایش زنده محتوای llms.txt</h3>
                    <span style="font-size: 11px; color: #64748b;">فرمت استاندارد Markdown</span>
                </div>
                <textarea readonly style="width: 100%; height: 260px; direction: ltr; font-family: monospace; font-size: 12px; padding: 14px; background: #0f172a; color: #e2e8f0; border-radius: 10px; border: 1px solid #334155; line-height: 1.6; resize: vertical;"><?= esc_textarea($llms_exists ? file_get_contents($llms_file) : $this->build_llms_txt()); ?></textarea>
            </div>

        </div>
        <?php
    }
}

new KMPR_LLMs_Manager();
