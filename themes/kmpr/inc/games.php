<?php
/**
 * Games module: CPT `game` + taxonomy, ACF fields & archive options,
 * player data table, REST API (scores / leaderboards / player stats),
 * admin score manager, asset loading and template helpers.
 *
 * Anti-cheat (kept deliberately simple — it is a portfolio arcade):
 *  - every run starts with POST /games/start which returns an HMAC-signed run token (game + issue time);
 *  - a score must carry a valid, unused token whose age covers the reported duration;
 *  - per-engine caps: max score, max points-per-second and minimum run length;
 *  - honeypot field, per-player and per-IP rate limits (transients), IPs only stored as salted hashes.
 *
 * @package KMPR
 */

if (!defined('ABSPATH')) {
    exit;
}

define('KMPR_GAMES_DB_VERSION', '3.0.0');
define('KMPR_GAMES_REWRITE_VERSION', '1.0.0-2');

require_once __DIR__ . '/games-online.php';
require_once __DIR__ . '/games-admin-players.php';
require_once __DIR__ . '/games-pwa.php';
if (file_exists(__DIR__ . '/games-rooms-extra.php')) {
    require_once __DIR__ . '/games-rooms-extra.php';
}

/* =========================================================================
 * Engines registry (JS modules in javascript/games/engines/*)
 * ====================================================================== */

/**
 * slug => label, max score, max points per second, minimum run length (ms).
 */
function kmpr_game_engines()
{
    return array(
        'jota'         => array('label' => 'سفر فضانورد (JotA)', 'max' => 500000, 'pps' => 900, 'min_ms' => 4000),
        'bug-hunter'   => array('label' => 'شکارچی باگ', 'max' => 200000, 'pps' => 600, 'min_ms' => 4000),
        'type-code'    => array('label' => 'تایپ کد', 'max' => 3000, 'pps' => 60, 'min_ms' => 20000),
        'stack-memory' => array('label' => 'حافظه استک', 'max' => 100000, 'pps' => 400, 'min_ms' => 5000),
        'pixel-runner' => array('label' => 'رانر پیکسلی', 'max' => 500000, 'pps' => 600, 'min_ms' => 3000),
        'hex-hunter'   => array('label' => 'معمای رنگ', 'max' => 100000, 'pps' => 600, 'min_ms' => 4000),
        'snake'        => array('label' => 'مار نئونی', 'max' => 100000, 'pps' => 300, 'min_ms' => 3000),
        'sky-shooter'  => array('label' => 'تیرانداز آسمان', 'max' => 200000, 'pps' => 800, 'min_ms' => 4000),
        'xo'           => array('label' => 'ایکس‌او (آنلاین)', 'max' => 5000, 'pps' => 400, 'min_ms' => 1500, 'online' => true),
        'rps15'        => array('label' => 'سنگ کاغذ قیچی ۱۵ حالته (آنلاین)', 'max' => 10000, 'pps' => 400, 'min_ms' => 2000, 'online' => true),
        'chess'        => array('label' => 'شطرنج (آنلاین)', 'max' => 20000, 'pps' => 200, 'min_ms' => 8000, 'online' => true),
        'color-race'   => array('label' => 'رنگ‌بازی (آنلاین)', 'max' => 20000, 'pps' => 600, 'min_ms' => 5000, 'online' => true),
    );
}

/**
 * Accent palette (select value => hex + Tailwind-ish names used in templates).
 */
function kmpr_game_accents()
{
    return array(
        'amber'   => array('label' => 'کهربایی', 'hex' => '#f59e0b'),
        'sky'     => array('label' => 'آسمانی', 'hex' => '#38bdf8'),
        'emerald' => array('label' => 'زمردی', 'hex' => '#34d399'),
        'violet'  => array('label' => 'بنفش', 'hex' => '#a78bfa'),
        'rose'    => array('label' => 'صورتی', 'hex' => '#fb7185'),
    );
}

function kmpr_game_difficulties()
{
    return array('easy' => 'آسان', 'medium' => 'متوسط', 'hard' => 'سخت');
}

/* =========================================================================
 * CPT + taxonomy
 * ====================================================================== */
add_action('init', function () {
    register_taxonomy('game_category', 'game', array(
        'labels'            => array(
            'name'          => 'ژانر بازی',
            'singular_name' => 'ژانر',
            'menu_name'     => 'ژانرها',
            'all_items'     => 'همه ژانرها',
            'edit_item'     => 'ویرایش ژانر',
            'add_new_item'  => 'افزودن ژانر',
            'search_items'  => 'جستجوی ژانر',
        ),
        'hierarchical'      => true,
        'public'            => true,
        'show_in_rest'      => true,
        'show_admin_column' => true,
        'rewrite'           => array('slug' => 'games/genre', 'with_front' => false),
    ));

    register_post_type('game', array(
        'labels'        => array(
            'name'               => 'بازی‌ها',
            'singular_name'      => 'بازی',
            'menu_name'          => 'بازی‌ها',
            'add_new'            => 'افزودن بازی',
            'add_new_item'       => 'افزودن بازی جدید',
            'edit_item'          => 'ویرایش بازی',
            'new_item'           => 'بازی جدید',
            'view_item'          => 'مشاهده بازی',
            'view_items'         => 'مشاهده بازی‌ها',
            'search_items'       => 'جستجوی بازی',
            'not_found'          => 'بازی‌ای پیدا نشد',
            'not_found_in_trash' => 'بازی‌ای در زباله‌دان نیست',
            'all_items'          => 'همه بازی‌ها',
            'archives'           => 'آرشیو بازی‌ها',
        ),
        'public'        => true,
        'has_archive'   => 'games',
        'rewrite'       => array('slug' => 'games', 'with_front' => false),
        'menu_icon'     => 'dashicons-games',
        'menu_position' => 22,
        'show_in_rest'  => true,
        'supports'      => array('title', 'editor', 'excerpt', 'thumbnail', 'custom-fields'),
    ));

    // One-time rewrite flush after the CPT is registered.
    if (get_option('kmpr_games_rewrite_flushed') !== KMPR_GAMES_REWRITE_VERSION) {
        flush_rewrite_rules(false);
        update_option('kmpr_games_rewrite_flushed', KMPR_GAMES_REWRITE_VERSION, true);
    }
}, 9);

/* =========================================================================
 * Player data table
 * ====================================================================== */
function kmpr_games_table()
{
    global $wpdb;
    return $wpdb->prefix . 'kmpr_game_scores';
}

/**
 * Scores table v2 — ONE row per (game, player): the player's best run + a play counter.
 * v1 → v2 migration (one-time, version-gated): dedupe to best-per-player, sum plays, drop meta/ip_hash.
 */
add_action('init', function () {
    if (get_option('kmpr_games_db_version') === KMPR_GAMES_DB_VERSION) {
        return;
    }
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $table   = kmpr_games_table();
    $charset = $wpdb->get_charset_collate();
    $exists  = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) === $table;

    if ($exists) {
        $cols = $wpdb->get_col("SHOW COLUMNS FROM {$table}"); // phpcs:ignore
        if (!in_array('plays', $cols, true)) {
            $wpdb->query("ALTER TABLE {$table} ADD COLUMN plays int(10) unsigned NOT NULL DEFAULT 1 AFTER duration_ms"); // phpcs:ignore
            // Fold every player's run count into their best row, then delete the other runs.
            $wpdb->query("UPDATE {$table} s JOIN (SELECT game_id, player_id, COUNT(*) c FROM {$table} GROUP BY game_id, player_id) x ON x.game_id = s.game_id AND x.player_id = s.player_id SET s.plays = x.c"); // phpcs:ignore
        }
        $wpdb->query("DELETE s FROM {$table} s JOIN {$table} b ON b.game_id = s.game_id AND b.player_id = s.player_id AND (b.score > s.score OR (b.score = s.score AND b.id < s.id))"); // phpcs:ignore
        foreach (array('meta', 'ip_hash') as $col) {
            if (in_array($col, $cols, true)) {
                $wpdb->query("ALTER TABLE {$table} DROP COLUMN {$col}"); // phpcs:ignore
            }
        }
        foreach (array('player', 'created') as $idx) {
            if ($wpdb->get_var($wpdb->prepare("SHOW INDEX FROM {$table} WHERE Key_name = %s", $idx))) { // phpcs:ignore
                $wpdb->query("ALTER TABLE {$table} DROP INDEX {$idx}"); // phpcs:ignore
            }
        }
    }
    dbDelta("CREATE TABLE {$table} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  game_id bigint(20) unsigned NOT NULL,
  player_id char(36) NOT NULL,
  nickname varchar(32) NOT NULL DEFAULT '',
  score int(10) unsigned NOT NULL DEFAULT 0,
  duration_ms int(10) unsigned NOT NULL DEFAULT 0,
  plays int(10) unsigned NOT NULL DEFAULT 1,
  user_id bigint(20) unsigned NULL DEFAULT NULL,
  created_at datetime NOT NULL,
  last_played_at datetime NOT NULL DEFAULT '2000-01-01 00:00:00',
  PRIMARY KEY  (id),
  UNIQUE KEY game_player (game_id,player_id),
  KEY game_score (game_id,score)
) {$charset};");
    $wpdb->query("UPDATE {$table} SET last_played_at = created_at WHERE last_played_at < '2001-01-01'"); // phpcs:ignore
    update_option('kmpr_games_db_version', KMPR_GAMES_DB_VERSION, true);
}, 5);

/**
 * Storage mode: 'light' (default — bests, leaderboards, XP on the server) or 'local' (browser only).
 */
function kmpr_games_storage_mode()
{
    $v = function_exists('get_field') ? get_field('gs_storage', 'option') : '';
    return $v === 'local' ? 'local' : 'light';
}
function kmpr_games_server_scores()
{
    return kmpr_games_storage_mode() === 'light';
}

/* =========================================================================
 * ACF: per-game fields + archive options page
 * ====================================================================== */
function kmpr_games_defaults()
{
    return array(
        'ga_kicker'        => 'آرکید کوروش',
        'ga_title'         => 'بازی‌هایی که برای سرگرمی و تمرین کد نوشته‌ام',
        'ga_text'          => 'هر بازی را از صفر با جاوااسکریپت خالص و Canvas ساخته‌ام؛ بدون فریم‌ورک سنگین، روان روی موبایل و دسکتاپ. امتیازتان ذخیره می‌شود و می‌توانید اسم‌تان را به جدول برترین‌ها برسانید.',
        'ga_featured'      => 'بازی ویژه',
        'ga_grid_title'    => 'همه بازی‌ها',
        'ga_grid_text'     => 'یکی را انتخاب کنید، رکورد بزنید و دوباره برگردید.',
        'ga_cta_title'     => 'برای کسب‌وکارتان هم چیزی تعاملی می‌خواهید؟',
        'ga_cta_text'      => 'از گیمیفیکیشن کمپین و مسابقه آنلاین تا ابزارهای تعاملی سایت؛ همین دقت را برای پروژه شما هم به کار می‌برم.',
        'ga_cta_btn'       => 'شروع پروژه با من',
        'gs_how_title'     => 'چطور بازی کنم؟',
        'gs_lb_title'      => 'جدول برترین‌ها',
        'gs_tech_title'    => 'پشت صحنه فنی',
        'gs_more_title'    => 'بازی‌های دیگر',
        'gs_privacy'       => 'برای ثبت امتیاز فقط یک نام مستعار می‌خواهم؛ هیچ اطلاعات شخصی ذخیره نمی‌شود.',
    );
}

/**
 * Usage line for the settings page (only queried on that screen).
 */
function kmpr_games_usage_message()
{
    if (!is_admin() || ($_GET['page'] ?? '') !== 'kmpr-games-settings' || !function_exists('kmpr_games_storage_bytes')) { // phpcs:ignore
        return '';
    }
    ob_start();
    kmpr_games_storage_box();
    return ob_get_clean();
}

function kmpr_game_opt($name)
{
    $d = kmpr_games_defaults();
    return function_exists('kmpr_field') ? kmpr_field($name, $d[$name] ?? '', 'option') : ($d[$name] ?? '');
}

add_action('acf/init', function () {
    if (function_exists('acf_add_options_sub_page')) {
        acf_add_options_sub_page(array(
            'page_title'  => 'تنظیمات صفحه بازی‌ها',
            'menu_title'  => 'تنظیمات آرشیو',
            'menu_slug'   => 'kmpr-games-settings',
            'parent_slug' => 'edit.php?post_type=game',
            'capability'  => 'edit_posts',
        ));
    }
});

add_action('acf/include_fields', function () {
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }
    $engines = array();
    foreach (kmpr_game_engines() as $slug => $e) {
        $engines[$slug] = $e['label'];
    }
    $accents = array();
    foreach (kmpr_game_accents() as $k => $a) {
        $accents[$k] = $a['label'];
    }

    $g = function ($key, $name, $label, $type = 'text', $extra = array()) {
        return array_merge(array('key' => 'field_kmpr_game_' . $key, 'name' => $name, 'label' => $label, 'type' => $type), $extra);
    };
    $tab = function ($key, $label) {
        return array('key' => 'field_kmpr_game_' . $key, 'label' => $label, 'type' => 'tab', 'placement' => 'top');
    };

    acf_add_local_field_group(array(
        'key'      => 'group_kmpr_game',
        'title'    => 'تنظیمات بازی',
        'fields'   => array(
            $tab('tab_main', 'بازی'),
            $g('engine', 'game_engine', 'موتور بازی (ماژول JS)', 'select', array('choices' => $engines, 'required' => 1, 'default_value' => 'jota', 'instructions' => 'کدام ماژول جاوااسکریپت در این صفحه اجرا شود.')),
            $g('tagline', 'game_tagline', 'شعار کوتاه', 'text', array('maxlength' => 90)),
            $g('difficulty', 'game_difficulty', 'سختی', 'select', array('choices' => kmpr_game_difficulties(), 'default_value' => 'medium')),
            $g('accent', 'game_accent', 'رنگ شاخص', 'select', array('choices' => $accents, 'default_value' => 'amber')),
            $g('featured', 'game_is_featured', 'بازی ویژه آرشیو', 'true_false', array('ui' => 1)),
            $tab('tab_how', 'راهنما'),
            $g('how', 'game_how_to_play', 'چطور بازی کنم؟ (هر خط یک مرحله)', 'textarea', array('rows' => 5, 'new_lines' => '')),
            $g('controls_desktop', 'game_controls_desktop', 'کنترل‌ها در دسکتاپ (هر خط: کلید | کار)', 'textarea', array('rows' => 4, 'new_lines' => '')),
            $g('controls_mobile', 'game_controls_mobile', 'کنترل‌ها در موبایل (هر خط یک مورد)', 'textarea', array('rows' => 3, 'new_lines' => '')),
            $tab('tab_tech', 'فنی'),
            $g('tech', 'game_tech_used', 'تکنولوژی‌ها (هر خط یکی)', 'textarea', array('rows' => 4, 'new_lines' => '')),
            $g('github', 'game_github_url', 'لینک گیت‌هاب', 'url'),
        ),
        'location' => array(array(array('param' => 'post_type', 'operator' => '==', 'value' => 'game'))),
        'position' => 'normal',
    ));

    $d = kmpr_games_defaults();
    $o = function ($name, $label, $type = 'text', $extra = array()) use ($d) {
        return array_merge(array('key' => 'field_kmpr_game_opt_' . $name, 'name' => $name, 'label' => $label, 'type' => $type, 'default_value' => $d[$name] ?? ''), $extra);
    };
    acf_add_local_field_group(array(
        'key'      => 'group_kmpr_game_archive',
        'title'    => 'محتوای صفحه بازی‌ها',
        'fields'   => array(
            array('key' => 'field_kmpr_game_opt_tab1', 'label' => 'هدر آرشیو', 'type' => 'tab'),
            $o('ga_kicker', 'برچسب بالای تیتر'),
            $o('ga_title', 'تیتر اصلی (H1)'),
            $o('ga_text', 'توضیح', 'textarea', array('rows' => 3, 'new_lines' => '')),
            $o('ga_featured', 'برچسب بازی ویژه'),
            $o('ga_grid_title', 'تیتر لیست بازی‌ها'),
            $o('ga_grid_text', 'توضیح لیست بازی‌ها'),
            array('key' => 'field_kmpr_game_opt_tab2', 'label' => 'دعوت به همکاری', 'type' => 'tab'),
            $o('ga_cta_title', 'تیتر'),
            $o('ga_cta_text', 'توضیح', 'textarea', array('rows' => 2, 'new_lines' => '')),
            $o('ga_cta_btn', 'متن دکمه'),
            array('key' => 'field_kmpr_game_opt_tab3', 'label' => 'صفحه هر بازی', 'type' => 'tab'),
            $o('gs_how_title', 'تیتر راهنما'),
            $o('gs_lb_title', 'تیتر جدول برترین‌ها'),
            $o('gs_tech_title', 'تیتر بخش فنی'),
            $o('gs_more_title', 'تیتر بازی‌های دیگر'),
            $o('gs_privacy', 'یادداشت حریم خصوصی زیر جدول', 'textarea', array('rows' => 2, 'new_lines' => '')),
            array('key' => 'field_kmpr_game_opt_tab4', 'label' => 'ذخیره‌سازی', 'type' => 'tab'),
            array('key' => 'field_kmpr_game_opt_gs_storage', 'name' => 'gs_storage', 'label' => 'حالت ذخیره‌سازی', 'type' => 'radio', 'layout' => 'vertical', 'default_value' => 'light', 'choices' => array(
                'light' => 'سبک (پیش‌فرض): فقط رکوردها و جدول برترین‌ها روی سرور',
                'local' => 'فقط مرورگر: هیچ امتیازی روی سرور ذخیره نشود',
            ), 'instructions' => 'دستاوردها، تاریخچه و تنظیمات همیشه فقط در مرورگر بازیکن ذخیره می‌شوند. اتاق‌های آنلاین موقت‌اند و در هر دو حالت کار می‌کنند.'),
            array('key' => 'field_kmpr_game_opt_gs_snap', 'name' => 'gs_snapshot_hours', 'label' => 'مدت نگهداری بازی نیمه‌تمام (ساعت)', 'type' => 'number', 'default_value' => 12, 'min' => 0, 'max' => 720, 'instructions' => 'بازی نیمه‌تمام فقط در مرورگر بازیکن ذخیره می‌شود. ۰ = همیشه. اتاق‌های آنلاین همیشه ۳۰ دقیقه.'),
            array('key' => 'field_kmpr_game_opt_gs_keep', 'name' => 'gs_keep_top', 'label' => 'حداکثر رکورد نگه‌داشته‌شده برای هر بازی', 'type' => 'number', 'default_value' => 200, 'min' => 10, 'max' => 5000),
            array('key' => 'field_kmpr_game_opt_gs_usage', 'name' => '', 'label' => 'فضای اشغال‌شده', 'type' => 'message', 'message' => kmpr_games_usage_message()),
        ),
        'location' => array(array(array('param' => 'options_page', 'operator' => '==', 'value' => 'kmpr-games-settings'))),
    ));
});

/* =========================================================================
 * Data helpers
 * ====================================================================== */

/**
 * Raw meta read that works with or without ACF.
 */
function kmpr_game_meta($post_id, $name, $default = '')
{
    $v = function_exists('get_field') ? get_field($name, $post_id) : get_post_meta($post_id, $name, true);
    return ($v === null || $v === false || $v === '') ? $default : $v;
}

/**
 * Normalised data for a game post.
 */
function kmpr_game_data($post_id = null, $cover_size = 'large')
{
    $post_id  = $post_id ?: get_the_ID();
    $engines  = kmpr_game_engines();
    $engine   = (string) kmpr_game_meta($post_id, 'game_engine', 'jota');
    $engine   = isset($engines[$engine]) ? $engine : 'jota';
    $accents  = kmpr_game_accents();
    $accent   = (string) kmpr_game_meta($post_id, 'game_accent', 'amber');
    $accent   = isset($accents[$accent]) ? $accent : 'amber';
    $diffs    = kmpr_game_difficulties();
    $diff     = (string) kmpr_game_meta($post_id, 'game_difficulty', 'medium');
    $diff     = isset($diffs[$diff]) ? $diff : 'medium';
    $terms    = get_the_terms($post_id, 'game_category');
    $genre    = ($terms && !is_wp_error($terms)) ? $terms[0] : null;
    $cover    = null;
    if (has_post_thumbnail($post_id)) {
        $tid = get_post_thumbnail_id($post_id);
        $src = wp_get_attachment_image_src($tid, $cover_size);
        if ($src) {
            $cover = array('url' => $src[0], 'width' => (int) $src[1], 'height' => (int) $src[2], 'alt' => get_post_meta($tid, '_wp_attachment_image_alt', true) ?: get_the_title($post_id));
        }
    }
    $controls = array();
    foreach (kmpr_lines(kmpr_game_meta($post_id, 'game_controls_desktop')) as $line) {
        $parts      = array_map('trim', explode('|', $line, 2));
        $controls[] = array('key' => $parts[0], 'action' => $parts[1] ?? '');
    }

    return array(
        'id'          => $post_id,
        'title'       => get_the_title($post_id),
        'url'         => get_permalink($post_id),
        'excerpt'     => has_excerpt($post_id) ? get_the_excerpt($post_id) : wp_trim_words(wp_strip_all_tags(get_post_field('post_content', $post_id)), 26),
        'engine'      => $engine,
        'tagline'     => (string) kmpr_game_meta($post_id, 'game_tagline'),
        'accent'      => $accent,
        'accent_hex'  => $accents[$accent]['hex'],
        'difficulty'  => $diff,
        'diff_label'  => $diffs[$diff],
        'diff_level'  => array_search($diff, array_keys($diffs), true) + 1,
        'genre'       => $genre ? $genre->name : '',
        'genre_url'   => $genre ? get_term_link($genre) : '',
        'cover'       => $cover,
        'featured'    => (bool) kmpr_game_meta($post_id, 'game_is_featured', false),
        'how'         => kmpr_lines(kmpr_game_meta($post_id, 'game_how_to_play')),
        'controls'    => $controls,
        'mobile'      => kmpr_lines(kmpr_game_meta($post_id, 'game_controls_mobile')),
        'tech'        => kmpr_lines(kmpr_game_meta($post_id, 'game_tech_used')),
        'github'      => (string) kmpr_game_meta($post_id, 'game_github_url'),
    );
}

/**
 * Is a page a games page (enqueue gate)?
 */
function kmpr_is_games_page()
{
    return is_singular('game') || is_post_type_archive('game') || is_tax('game_category');
}

/* =========================================================================
 * Assets: games bundle only on game pages
 * ====================================================================== */
add_action('wp_enqueue_scripts', function () {
    if (!kmpr_is_games_page()) {
        return;
    }
    $file = get_template_directory() . '/public/js/games/index.js';
    wp_enqueue_script('kmpr-games', get_template_directory_uri() . '/public/js/games/index.js', array(), file_exists($file) ? (string) filemtime($file) : KMPR_VERSION, array(
        'in_footer' => true,
        'strategy'  => 'defer',
    ));
    $user = wp_get_current_user();
    wp_localize_script('kmpr-games', 'kmprGames', array(
        'rest'     => esc_url_raw(rest_url('kmpr/v1/games')),
        'nonce'    => is_user_logged_in() ? wp_create_nonce('wp_rest') : '',
        'storage'  => kmpr_games_storage_mode(),
        'appIcon'  => get_template_directory_uri() . '/public/images/arcade/icon-192.png',
        'snapTtl'  => (function () {
            $v = function_exists('get_field') ? get_field('gs_snapshot_hours', 'option') : null;
            return ($v === null || $v === '' || $v === false) ? 12 : max(0, (float) $v);
        })(),
        'userName' => $user && $user->exists() ? $user->display_name : '',
    ));
}, 20);

/**
 * The games bundle is an ES module with lazily-imported per-game chunks.
 */
add_filter('script_loader_tag', function ($tag, $handle) {
    if ($handle === 'kmpr-games' && strpos($tag, 'type="module"') === false) {
        $tag = str_replace('<script ', '<script type="module" ', $tag);
    }
    return $tag;
}, 10, 2);

/* =========================================================================
 * Leaderboards (shared by REST + templates)
 * ====================================================================== */

/**
 * Top players (best score per player) for a game.
 *
 * @return array<int, array{nickname:string, score:int, created_at:string, player:string}>
 */
function kmpr_game_leaderboard($game_id, $period = 'all', $limit = 10)
{
    global $wpdb;
    if (!kmpr_games_server_scores()) {
        return array();
    }
    $game_id = (int) $game_id;
    $period  = $period === 'week' ? 'week' : 'all';
    $key     = 'kmpr_glb_' . $game_id . '_' . $period;
    $cached  = get_transient($key);
    if (is_array($cached)) {
        return array_slice($cached, 0, $limit);
    }
    $table = kmpr_games_table();
    $where = $wpdb->prepare('game_id = %d AND score > 0', $game_id);
    if ($period === 'week') {
        $where .= $wpdb->prepare(' AND created_at >= %s', gmdate('Y-m-d H:i:s', time() - WEEK_IN_SECONDS));
    }
    $rows = $wpdb->get_results("SELECT player_id, nickname, score, created_at FROM {$table} WHERE {$where} ORDER BY score DESC, created_at ASC LIMIT 20", ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $where is prepared above.
    $out  = array();
    foreach ((array) $rows as $r) {
        $out[] = array(
            'nickname'   => $r['nickname'] !== '' ? $r['nickname'] : 'بازیکن ناشناس',
            'score'      => (int) $r['score'],
            'created_at' => mysql_to_rfc3339($r['created_at']),
            'player'     => substr(hash('sha256', $r['player_id']), 0, 10), // public, non-reversible id for "is this me"
            // Extension point (e.g. a "supporter" badge): array of short text labels.
            'badges'     => array_values(array_filter(array_map('strval', (array) apply_filters('kmpr_game_player_badges', array(), $r)))),
        );
    }
    set_transient($key, $out, 60);
    return array_slice($out, 0, $limit);
}

function kmpr_game_bust_cache($game_id)
{
    delete_transient('kmpr_glb_' . (int) $game_id . '_all');
    delete_transient('kmpr_glb_' . (int) $game_id . '_week');
    delete_transient('kmpr_games_stats');
}

/**
 * Plays count + champion per game (archive hub).
 */
function kmpr_games_stats()
{
    $cached = get_transient('kmpr_games_stats');
    if (is_array($cached)) {
        return $cached;
    }
    global $wpdb;
    $table = kmpr_games_table();
    $rows  = kmpr_games_server_scores() ? $wpdb->get_results("SELECT game_id, SUM(plays) plays, COUNT(*) players FROM {$table} GROUP BY game_id", ARRAY_A) : array(); // phpcs:ignore
    $out   = array('games' => array(), 'plays' => 0, 'players' => 0);
    foreach ((array) $rows as $r) {
        $top = kmpr_game_leaderboard((int) $r['game_id'], 'all', 1);
        $out['games'][(int) $r['game_id']] = array('plays' => (int) $r['plays'], 'players' => (int) $r['players'], 'top' => $top[0] ?? null);
        $out['plays'] += (int) $r['plays'];
    }
    $out['players'] = kmpr_games_server_scores() ? (int) $wpdb->get_var("SELECT COUNT(DISTINCT player_id) FROM {$table}") : 0; // phpcs:ignore
    set_transient('kmpr_games_stats', $out, 120);
    return $out;
}

/* =========================================================================
 * REST API — kmpr/v1/games
 * ====================================================================== */
function kmpr_game_ip_hash()
{
    $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '0.0.0.0';
    return hash_hmac('sha256', $ip, wp_salt('auth'));
}

function kmpr_game_error($message, $status = 400, $code = 'kmpr_game_error')
{
    return new WP_REST_Response(array('ok' => false, 'code' => $code, 'message' => $message), $status);
}

/**
 * Resolve a published game post → engine slug, or null.
 */
function kmpr_game_engine_for($game_id)
{
    $post = get_post((int) $game_id);
    if (!$post || $post->post_type !== 'game' || $post->post_status !== 'publish') {
        return null;
    }
    $engine = (string) kmpr_game_meta($post->ID, 'game_engine', '');
    $all    = kmpr_game_engines();
    return isset($all[$engine]) ? $engine : null;
}

function kmpr_game_sign($payload)
{
    return hash_hmac('sha256', $payload, wp_salt('nonce') . 'kmpr-games');
}

function kmpr_game_valid_player($id)
{
    return is_string($id) && preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i', $id);
}

add_action('rest_api_init', function () {
    $ns = 'kmpr/v1';

    register_rest_route($ns, '/games/start', array(
        'methods'             => 'POST',
        'permission_callback' => '__return_true',
        'callback'            => function (WP_REST_Request $req) {
            $game_id = (int) $req->get_param('game');
            if (!kmpr_game_engine_for($game_id)) {
                return kmpr_game_error('بازی پیدا نشد.', 404, 'no_game');
            }
            // Light rate limit: 150 starts / 10 min per IP (shared NATs, rapid rematches).
            $ipkey = 'kmpr_gst_' . substr(kmpr_game_ip_hash(), 0, 20);
            $count = (int) get_transient($ipkey);
            if ($count > 150) {
                return kmpr_game_error('کمی آهسته‌تر!', 429, 'rate');
            }
            set_transient($ipkey, $count + 1, 10 * MINUTE_IN_SECONDS);
            // Counters only (no activity log): online matches are counted when they finish instead.
            $pid = strtolower((string) $req->get_param('player_id'));
            if (kmpr_game_valid_player($pid) && !$req->get_param('online')) {
                kmpr_game_count_play($game_id, $pid, (string) $req->get_param('nickname'));
            }
            $payload = $game_id . '.' . time() . '.' . wp_generate_password(10, false);
            return array('ok' => true, 'token' => $payload . '.' . kmpr_game_sign($payload));
        },
    ));

    register_rest_route($ns, '/games/score', array(
        'methods'             => 'POST',
        'permission_callback' => '__return_true',
        'callback'            => 'kmpr_game_rest_score',
    ));

    register_rest_route($ns, '/games/leaderboard', array(
        'methods'             => 'GET',
        'permission_callback' => '__return_true',
        'args'                => array(
            'game'   => array('required' => true, 'sanitize_callback' => 'absint'),
            'period' => array('default' => 'all', 'enum' => array('all', 'week')),
        ),
        'callback'            => function (WP_REST_Request $req) {
            $game_id = (int) $req->get_param('game');
            if (!kmpr_game_engine_for($game_id)) {
                return kmpr_game_error('بازی پیدا نشد.', 404, 'no_game');
            }
            $period = $req->get_param('period') === 'week' ? 'week' : 'all';
            $res    = rest_ensure_response(array('ok' => true, 'game' => $game_id, 'period' => $period, 'rows' => kmpr_game_leaderboard($game_id, $period, 10)));
            $res->header('Cache-Control', 'public, max-age=30');
            return $res;
        },
    ));

    register_rest_route($ns, '/games/player', array(
        'methods'             => 'GET',
        'permission_callback' => '__return_true',
        'callback'            => function (WP_REST_Request $req) {
            $pid = (string) $req->get_param('player_id');
            if (!kmpr_game_valid_player($pid)) {
                return kmpr_game_error('شناسه بازیکن معتبر نیست.', 400, 'bad_player');
            }
            global $wpdb;
            $table   = kmpr_games_table();
            $games   = array();
            $total   = 0;
            $profile = function_exists('kmpr_game_profile_public') ? kmpr_game_profile_public(kmpr_game_profile_row($pid)) : null;
            if (kmpr_games_server_scores()) {
                $rows = $wpdb->get_results($wpdb->prepare("SELECT game_id, score, plays FROM {$table} WHERE player_id = %s", $pid), ARRAY_A); // phpcs:ignore
                foreach ((array) $rows as $r) {
                    $gid = (int) $r['game_id'];
                    if (get_post_status($gid) !== 'publish') {
                        continue;
                    }
                    // Compact: engine slug → best / plays (titles & history live in the browser).
                    $games[] = array('engine' => (string) kmpr_game_meta($gid, 'game_engine'), 'best' => (int) $r['score'], 'plays' => (int) $r['plays']);
                    $total  += (int) $r['plays'];
                }
            }
            return array('ok' => true, 'player' => substr(hash('sha256', $pid), 0, 10), 'nickname' => $profile['nickname'] ?? '', 'total_plays' => $total, 'games' => $games, 'profile' => $profile, 'storage' => kmpr_games_storage_mode());
        },
    ));
});

/**
 * POST /games/score
 */
function kmpr_game_rest_score(WP_REST_Request $req)
{
    global $wpdb;

    // Honeypot: bots fill every field. Pretend success, store nothing.
    if (trim((string) $req->get_param('website')) !== '') {
        return array('ok' => true, 'stored' => false);
    }
    // Browser-only storage mode: nothing is written on the server.
    if (!kmpr_games_server_scores()) {
        return array('ok' => true, 'stored' => false, 'local' => true);
    }

    $game_id = (int) $req->get_param('game');
    $engine  = kmpr_game_engine_for($game_id);
    if (!$engine) {
        return kmpr_game_error('بازی پیدا نشد.', 404, 'no_game');
    }
    $client_engine = (string) $req->get_param('engine');
    if ($client_engine !== '' && $client_engine !== $engine) {
        return kmpr_game_error('موتور بازی با این صفحه هم‌خوانی ندارد.', 400, 'engine_mismatch');
    }

    $player = (string) $req->get_param('player_id');
    if (!kmpr_game_valid_player($player)) {
        return kmpr_game_error('شناسه بازیکن معتبر نیست.', 400, 'bad_player');
    }
    $player = strtolower($player);

    $conf     = kmpr_game_engines()[$engine];
    $score    = (int) $req->get_param('score');
    $duration = (int) $req->get_param('duration_ms');
    if ($score <= 0) {
        return kmpr_game_error('امتیاز صفر ثبت نمی‌شود.', 400, 'zero');
    }
    if ($duration < $conf['min_ms'] || $duration > 6 * HOUR_IN_SECONDS * 1000) {
        return kmpr_game_error('مدت بازی معتبر نیست.', 400, 'duration');
    }
    // Impossibly fast runs (points per second over the engine's ceiling, small slack).
    if ($score > ($conf['pps'] * ($duration / 1000)) + 50) {
        return kmpr_game_error('این امتیاز با این سرعت ممکن نیست!', 400, 'too_fast');
    }
    $score = min($score, (int) $conf['max']);

    // Run token: signed by /start, single use, must be at least as old as the run.
    $token = (string) $req->get_param('token');
    $parts = explode('.', $token);
    if (count($parts) !== 4) {
        return kmpr_game_error('توکن بازی معتبر نیست.', 400, 'token');
    }
    list($t_game, $t_time, $t_rand, $t_sig) = $parts;
    if (!hash_equals(kmpr_game_sign($t_game . '.' . $t_time . '.' . $t_rand), $t_sig) || (int) $t_game !== $game_id) {
        return kmpr_game_error('توکن بازی معتبر نیست.', 400, 'token');
    }
    $age_ms = (time() - (int) $t_time) * 1000;
    if ($age_ms > 6 * HOUR_IN_SECONDS * 1000 || $age_ms + 2500 < $duration) {
        return kmpr_game_error('زمان بازی با توکن هم‌خوانی ندارد.', 400, 'token_time');
    }
    // No per-run "used token" rows: a replayed token can only re-submit a score already within
    // its own signed time window and the per-engine caps, and the table keeps only the best per player.

    // Rate limits.
    $ip_hash = kmpr_game_ip_hash();
    $p_key   = 'kmpr_gsp_' . md5($player);
    if (get_transient($p_key)) {
        return kmpr_game_error('چند ثانیه صبر کنید و دوباره تلاش کنید.', 429, 'rate');
    }
    $ip_key = 'kmpr_gsi_' . substr($ip_hash, 0, 24);
    $ip_cnt = (int) get_transient($ip_key);
    if ($ip_cnt >= 60) {
        return kmpr_game_error('تعداد ثبت امتیاز از این شبکه زیاد است؛ کمی بعد دوباره تلاش کنید.', 429, 'rate');
    }

    // Nickname.
    $user_id = get_current_user_id();
    $nick    = wp_strip_all_tags((string) $req->get_param('nickname'));
    $nick    = preg_replace('~https?://\S+|www\.\S+~i', '', $nick);
    $nick    = trim(preg_replace('/\s+/u', ' ', sanitize_text_field($nick)));
    $nick    = mb_substr($nick, 0, 24);
    if ($nick === '' && $user_id) {
        $nick = mb_substr(wp_get_current_user()->display_name, 0, 24);
    }
    if ($nick === '') {
        $nick = 'بازیکن ناشناس';
    }

    // Upsert: one row per (game, player) — keep the best run, count every play.
    $table = kmpr_games_table();
    $now   = current_time('mysql', true);
    $ok    = $wpdb->query($wpdb->prepare(
        "INSERT INTO {$table} (game_id, player_id, nickname, score, duration_ms, plays, user_id, created_at, last_played_at) VALUES (%d, %s, %s, %d, %d, 1, " . ($user_id ? '%d' : 'NULL') . ", %s, %s)
        ON DUPLICATE KEY UPDATE
            last_played_at = VALUES(last_played_at),
            nickname = VALUES(nickname),
            user_id = COALESCE(VALUES(user_id), user_id),
            duration_ms = IF(VALUES(score) > score, VALUES(duration_ms), duration_ms),
            created_at = IF(VALUES(score) > score, VALUES(created_at), created_at),
            score = GREATEST(score, VALUES(score))", // phpcs:ignore
        ...array_values(array_filter(array($game_id, $player, $nick, $score, $duration, $user_id ?: null, $now, $now), fn($v) => $v !== null))
    ));
    if ($ok === false) {
        return kmpr_game_error('ذخیره امتیاز ناموفق بود.', 500, 'db');
    }

    $xp = function_exists('kmpr_game_run_xp') ? kmpr_game_run_xp($engine, $score) : 0;
    if ($xp && function_exists('kmpr_game_profile_add')) {
        kmpr_game_profile_add($player, $xp, '', $nick !== 'بازیکن ناشناس' ? $nick : '');
    }
    set_transient($p_key, 1, 3);
    set_transient($ip_key, $ip_cnt + 1, HOUR_IN_SECONDS);
    kmpr_game_bust_cache($game_id);

    $best  = (int) $wpdb->get_var($wpdb->prepare("SELECT score FROM {$table} WHERE game_id = %d AND player_id = %s", $game_id, $player)); // phpcs:ignore
    $board = kmpr_game_leaderboard($game_id, 'all', 10);
    $me    = substr(hash('sha256', $player), 0, 10);
    $rank  = 0;
    foreach ($board as $i => $row) {
        if ($row['player'] === $me) {
            $rank = $i + 1;
            break;
        }
    }
    return array('ok' => true, 'stored' => true, 'xp' => $xp, 'score' => $score, 'best' => $best, 'rank' => $rank, 'nickname' => $nick, 'rows' => $board);
}

/* =========================================================================
 * Admin: scores screen + dashboard widget
 * ====================================================================== */
add_action('admin_menu', function () {
    add_submenu_page('edit.php?post_type=game', 'امتیازهای بازیکنان', 'امتیازها و بازیکنان', 'edit_posts', 'kmpr-game-scores', 'kmpr_game_scores_screen');
});

add_action('admin_post_kmpr_game_score_delete', function () {
    if (!current_user_can('edit_posts')) {
        wp_die('دسترسی ندارید.');
    }
    $id = isset($_GET['id']) ? absint($_GET['id']) : 0;
    check_admin_referer('kmpr_game_score_delete_' . $id);
    global $wpdb;
    $game_id = (int) $wpdb->get_var($wpdb->prepare('SELECT game_id FROM ' . kmpr_games_table() . ' WHERE id = %d', $id)); // phpcs:ignore
    $wpdb->delete(kmpr_games_table(), array('id' => $id), array('%d'));
    kmpr_game_bust_cache($game_id);
    wp_safe_redirect(add_query_arg(array('post_type' => 'game', 'page' => 'kmpr-game-scores', 'deleted' => 1), admin_url('edit.php')));
    exit;
});

function kmpr_game_scores_screen()
{
    global $wpdb;
    $table   = kmpr_games_table();
    $game    = isset($_GET['game']) ? absint($_GET['game']) : 0; // phpcs:ignore WordPress.Security.NonceVerification
    $paged   = max(1, isset($_GET['paged']) ? absint($_GET['paged']) : 1); // phpcs:ignore WordPress.Security.NonceVerification
    $per     = 50;
    $where   = $game ? $wpdb->prepare('WHERE game_id = %d', $game) : '';
    $total   = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} {$where}"); // phpcs:ignore
    $rows    = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} {$where} ORDER BY id DESC LIMIT %d OFFSET %d", $per, ($paged - 1) * $per)); // phpcs:ignore
    $counts  = $wpdb->get_results("SELECT game_id, SUM(plays) plays, COUNT(*) players, MAX(score) best FROM {$table} GROUP BY game_id ORDER BY plays DESC"); // phpcs:ignore
    $base    = admin_url('edit.php?post_type=game&page=kmpr-game-scores');
    ?>
    <div class="wrap">
        <h1>امتیازها و بازیکنان</h1>
        <?php kmpr_games_storage_box(); ?>
        <?php if (!empty($_GET['deleted'])) : // phpcs:ignore ?>
            <div class="notice notice-success is-dismissible"><p>امتیاز حذف شد.</p></div>
        <?php endif; ?>

        <h2>آمار هر بازی</h2>
        <table class="widefat striped" style="max-width:820px">
            <thead><tr><th>بازی</th><th>تعداد بازی</th><th>بازیکن یکتا</th><th>بهترین امتیاز</th><th></th></tr></thead>
            <tbody>
            <?php if (!$counts) : ?>
                <tr><td colspan="5">هنوز امتیازی ثبت نشده است.</td></tr>
            <?php endif; ?>
            <?php foreach ((array) $counts as $c) : ?>
                <tr>
                    <td><?= esc_html(get_the_title($c->game_id) ?: '#' . $c->game_id); ?></td>
                    <td><?= esc_html(number_format_i18n($c->plays)); ?></td>
                    <td><?= esc_html(number_format_i18n($c->players)); ?></td>
                    <td><?= esc_html(number_format_i18n($c->best)); ?></td>
                    <td><a href="<?= esc_url(add_query_arg('game', (int) $c->game_id, $base)); ?>">فقط این بازی</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <h2 style="margin-top:2em">رکوردهای بازیکنان (یک ردیف برای هر بازیکن در هر بازی) <?= $game ? '— ' . esc_html(get_the_title($game)) . ' <a class="page-title-action" href="' . esc_url($base) . '">همه</a>' : ''; ?></h2>
        <table class="widefat striped">
            <thead><tr><th>#</th><th>بازی</th><th>نام مستعار</th><th>بهترین امتیاز</th><th>مدت</th><th>کاربر</th><th>بازیکن</th><th>دفعات</th><th>تاریخ رکورد</th><th></th></tr></thead>
            <tbody>
            <?php if (!$rows) : ?>
                <tr><td colspan="10">موردی نیست.</td></tr>
            <?php endif; ?>
            <?php foreach ((array) $rows as $r) :
                $del = wp_nonce_url(admin_url('admin-post.php?action=kmpr_game_score_delete&id=' . (int) $r->id), 'kmpr_game_score_delete_' . (int) $r->id); ?>
                <tr>
                    <td><?= (int) $r->id; ?></td>
                    <td><?= esc_html(get_the_title($r->game_id)); ?></td>
                    <td><?= esc_html($r->nickname); ?></td>
                    <td><strong><?= esc_html(number_format_i18n($r->score)); ?></strong></td>
                    <td><?= esc_html(round($r->duration_ms / 1000, 1)); ?> ث</td>
                    <td><?= $r->user_id ? esc_html(get_the_author_meta('display_name', $r->user_id)) : '—'; ?></td>
                    <td><code><?= esc_html(substr($r->player_id, 0, 8)); ?></code></td>
                    <td><?= esc_html(number_format_i18n($r->plays)); ?></td>
                    <td><?= esc_html(function_exists('shamsi_date') ? shamsi_date('Y/m/d H:i', strtotime($r->created_at . ' UTC')) : $r->created_at); ?></td>
                    <td><a href="<?= esc_url($del); ?>" style="color:#b32d2e" onclick="return confirm('این امتیاز حذف شود؟');">حذف</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php
        $pages = (int) ceil($total / $per);
        if ($pages > 1) {
            echo '<div class="tablenav"><div class="tablenav-pages">' . wp_kses_post(paginate_links(array('base' => add_query_arg('paged', '%#%', $game ? add_query_arg('game', $game, $base) : $base), 'format' => '', 'current' => $paged, 'total' => $pages))) . '</div></div>';
        }
        ?>
    </div>
    <?php
}

add_action('wp_dashboard_setup', function () {
    if (!current_user_can('edit_posts')) {
        return;
    }
    wp_add_dashboard_widget('kmpr_games_widget', 'آرکید — آمار بازی‌ها', function () {
        $s = kmpr_games_stats();
        echo '<p>کل دست‌های ثبت‌شده: <strong>' . esc_html(number_format_i18n($s['plays'])) . '</strong> — بازیکن یکتا: <strong>' . esc_html(number_format_i18n($s['players'])) . '</strong></p><ul>';
        foreach ($s['games'] as $gid => $g) {
            echo '<li>' . esc_html(get_the_title($gid)) . ': ' . esc_html(number_format_i18n($g['plays'])) . ' دست' . ($g['top'] ? ' — قهرمان: ' . esc_html($g['top']['nickname']) . ' (' . esc_html(number_format_i18n($g['top']['score'])) . ')' : '') . '</li>';
        }
        echo '</ul><p><a href="' . esc_url(admin_url('edit.php?post_type=game&page=kmpr-game-scores')) . '">مدیریت امتیازها</a></p>';
    });
});

/* =========================================================================
 * Archive query: all games, featured first handled in template
 * ====================================================================== */
add_action('pre_get_posts', function ($q) {
    if (is_admin() || !$q->is_main_query()) {
        return;
    }
    if ($q->is_post_type_archive('game') || $q->is_tax('game_category')) {
        $q->set('posts_per_page', 24);
        $q->set('orderby', array('menu_order' => 'ASC', 'date' => 'DESC'));
    }
});

/* =========================================================================
 * Schema.org VideoGame JSON-LD (single game)
 * ====================================================================== */
function kmpr_game_schema($post_id)
{
    $d     = kmpr_game_data($post_id, 'full');
    $stats = kmpr_games_stats();
    $g     = $stats['games'][$post_id] ?? null;
    $data  = array(
        '@context'            => 'https://schema.org',
        '@type'               => array('VideoGame', 'WebApplication'),
        '@id'                 => $d['url'] . '#game',
        'name'                => $d['title'],
        'url'                 => $d['url'],
        'description'         => wp_strip_all_tags($d['excerpt']),
        'inLanguage'          => 'fa-IR',
        'applicationCategory' => 'GameApplication',
        'operatingSystem'     => 'Any (web browser)',
        'gamePlatform'        => array('Web browser', 'Desktop', 'Mobile'),
        'playMode'            => 'SinglePlayer',
        'genre'               => $d['genre'] ?: null,
        'isAccessibleForFree' => true,
        'offers'              => array('@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'IRR'),
        'author'              => array('@type' => 'Person', 'name' => 'کوروش محمدپور', 'alternateName' => 'Korosh Mpr', 'url' => home_url('/')),
        'datePublished'       => get_the_date('c', $post_id),
        'dateModified'        => get_the_modified_date('c', $post_id),
        'image'               => $d['cover']['url'] ?? null,
        'keywords'            => implode(', ', $d['tech']),
    );
    if (!empty($d['github'])) {
        $data['codeRepository'] = $d['github'];
        $data['sameAs']         = $d['github'];
    }
    if ($g && $g['plays'] > 0) {
        $data['interactionStatistic'] = array('@type' => 'InteractionCounter', 'interactionType' => 'https://schema.org/PlayAction', 'userInteractionCount' => (int) $g['plays']);
    }
    $crumbs = array(
        '@context'        => 'https://schema.org',
        '@type'           => 'BreadcrumbList',
        'itemListElement' => array(
            array('@type' => 'ListItem', 'position' => 1, 'name' => 'خانه', 'item' => home_url('/')),
            array('@type' => 'ListItem', 'position' => 2, 'name' => 'بازی‌ها', 'item' => get_post_type_archive_link('game')),
            array('@type' => 'ListItem', 'position' => 3, 'name' => $d['title'], 'item' => $d['url']),
        ),
    );
    return '<script type="application/ld+json">' . wp_json_encode(array_filter($data, fn($v) => $v !== null && $v !== ''), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>'
        . '<script type="application/ld+json">' . wp_json_encode($crumbs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
}

/**
 * ItemList JSON-LD for the archive.
 */
function kmpr_games_archive_schema($ids)
{
    $items = array();
    foreach (array_values($ids) as $i => $id) {
        $items[] = array('@type' => 'ListItem', 'position' => $i + 1, 'url' => get_permalink($id), 'name' => get_the_title($id));
    }
    $data = array(
        '@context'        => 'https://schema.org',
        '@type'           => 'CollectionPage',
        'name'            => wp_strip_all_tags(kmpr_game_opt('ga_title')),
        'url'             => get_post_type_archive_link('game'),
        'inLanguage'      => 'fa-IR',
        'mainEntity'      => array('@type' => 'ItemList', 'itemListElement' => $items),
    );
    return '<script type="application/ld+json">' . wp_json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
}

/* =========================================================================
 * Daily challenge (deterministic per site-day)
 * ====================================================================== */
function kmpr_games_daily()
{
    $targets = array('jota' => 6000, 'bug-hunter' => 3000, 'type-code' => 450, 'stack-memory' => 4000, 'pixel-runner' => 2500, 'hex-hunter' => 3000, 'snake' => 900, 'sky-shooter' => 4000);
    $games   = get_posts(array('post_type' => 'game', 'post_status' => 'publish', 'posts_per_page' => 50, 'orderby' => 'ID', 'order' => 'ASC', 'fields' => 'ids', 'no_found_rows' => true));
    $pool    = array();
    foreach ($games as $id) {
        $engine = (string) kmpr_game_meta($id, 'game_engine');
        if (isset($targets[$engine])) {
            $pool[] = array('id' => $id, 'engine' => $engine);
        }
    }
    if (!$pool) {
        return null;
    }
    $date = wp_date('Y-m-d');
    $pick = $pool[abs(crc32('kmpr-daily-' . $date)) % count($pool)];
    // ±25% variation, rounded to a friendly number.
    $mult   = 0.75 + (abs(crc32('t' . $date)) % 51) / 100;
    $target = (int) (round($targets[$pick['engine']] * $mult / 50) * 50);
    return array('id' => $pick['id'], 'engine' => $pick['engine'], 'target' => max(50, $target), 'date' => $date);
}

/**
 * Render player badges (from the kmpr_game_player_badges filter) as small chips.
 */
function kmpr_game_badges_html($badges)
{
    $out = '';
    foreach ((array) $badges as $b) {
        $out .= ' <span class="game-badge">' . esc_html($b) . '</span>';
    }
    return $out;
}

/**
 * Persian-formatted integer with Persian thousands separator.
 */
function kmpr_game_num($n)
{
    return kmpr_fa_num(number_format((float) $n, 0, '.', '٬'));
}

/**
 * Body class for Game Center pages (arcade header/footer swap in header.php / footer.php).
 */
add_filter('body_class', function ($classes) {
    if (kmpr_is_games_page()) {
        $classes[] = 'is-arcade';
    }
    return $classes;
});
