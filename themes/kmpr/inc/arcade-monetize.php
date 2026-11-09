<?php
/**
 * Arcade monetisation — revenue validation (demand test) for the Game Center.
 *
 * Surfaces (each one can be switched off in «بازی‌ها > تنظیمات آرشیو > درآمد و تست تقاضا»):
 *  1. «پاس آرکید» fake-door with two price variants (A/B per visitor) → pre-signup modal (no payment).
 *     When «لینک پرداخت» is filled, the buy button goes straight there instead (real checkout later).
 *  2. «حمایت از سازنده» → third-party donation link.
 *  3. Per-game sponsor («حامی این بازی») or a «جای برند شما» CTA.
 *  4. Advergame lead funnel → request wizard (?project=…&utm_*).
 *
 * Storage (1 GB host): every counter lives in ONE non-autoloaded option as daily buckets (≤ 120 days),
 * pre-signups are a tiny non-public CPT. No per-impression rows.
 * Tracking: POST kmpr/v1/arcade/track (per-IP throttle, bots & logged-in admins ignored).
 *
 * Hook points used (owned by the games module; everything degrades gracefully if missing):
 *  PHP  kmpr_games_hub_slot('after_hero'|'sidebar'), kmpr_game_slot($id, 'sidebar'), filter kmpr_game_player_badges.
 *  JS   kmpr:game-start, kmpr:game-over, kmpr:focus-change, [data-kmpr-slot="game-over"|"game-intro"].
 *
 * @package KMPR
 */

if (!defined('ABSPATH')) {
    exit;
}

const KMPR_AM_OPTION   = 'kmpr_am_stats';
const KMPR_AM_KEEP     = 120;
const KMPR_AM_CPT      = 'arcade_presignup';
const KMPR_AM_PAGE     = 'kmpr-arcade-revenue';

/* =========================================================================
 * 1. Settings (ACF on the games options page) + defaults
 * ====================================================================== */

function kmpr_am_defaults()
{
    return array(
        'am_pass_kicker'   => 'ویژه طرفداران گیم‌سنتر',
        'am_pass_title'    => 'پاس آرکید',
        'am_pass_text'     => 'یک اشتراک ماهانه کوچک برای کسی که گیم‌سنتر را دوست دارد؛ امکانات ویژه برای تو و انرژی برای ساختن بازی‌های بیشتر.',
        'am_pass_perks'    => "آواتار و قاب اختصاصی\nنام طلایی در جدول برترین‌ها\nدسترسی زودتر به بازی‌های جدید\nتورنمنت ماهانه با جایزه\nبدون بنر و پیشنهاد",
        'am_price_a'       => 49000,
        'am_price_b'       => 99000,
        'am_price_period'  => 'ماهانه',
        'am_pass_btn'      => 'خرید پاس',
        'am_pass_mini'     => 'رکوردت را طلایی کن',
        'am_pay_url'       => '',
        'am_modal_badge'   => '۵۰٪ تخفیف لانچ',
        'am_modal_title'   => 'به‌زودی راه می‌افتد',
        'am_modal_text'    => 'پیش‌ثبت‌نام کن تا با ۵۰٪ تخفیف لانچ اولین نفر باشی.',
        'am_modal_note'    => 'الان هیچ پرداختی انجام نمی‌شود. شماره یا آیدی‌ات فقط برای خبر دادن زمان لانچ استفاده می‌شود.',
        'am_modal_success' => 'ثبت شد! به محض راه‌افتادن پاس آرکید، اول از همه به تو خبر می‌دهم و تخفیف ۵۰٪ لانچ برایت محفوظ است.',
        'am_support_url'   => '',
        'am_support_title' => 'حمایت از سازنده',
        'am_support_text'  => 'همه بازی‌ها رایگان و بدون تبلیغ مزاحم‌اند. اگر لذت بردی، با یک قهوه انرژی بده.',
        'am_support_btn'   => 'حمایت می‌کنم',
        'am_sponsor_label' => 'حامی این بازی',
        'am_sponsor_cta'   => 'جای برند شما — اسپانسر این بازی شوید',
        'am_adv_title'     => 'این بازی رو برای برندت می‌خوای؟',
        'am_adv_text'      => 'بازی تبلیغاتی اختصاصی بساز؛ با لوگو و رنگ برندت، جایزه و جمع‌آوری لید.',
        'am_adv_btn'       => 'بازی تبلیغاتی بساز',
        'am_adv_type'      => 'web_app',
        'am_over_every'    => 3,
    );
}

function kmpr_am_opt($name)
{
    $d = kmpr_am_defaults();
    $default = $d[$name] ?? '';
    return function_exists('kmpr_field') ? kmpr_field($name, $default, 'option') : $default;
}

/**
 * Surface toggle (true_false needs its own read: kmpr_field() would turn an unchecked box into the default).
 */
function kmpr_am_on($surface)
{
    static $cache = array();
    if (!isset($cache[$surface])) {
        $v = function_exists('get_field') ? get_field('am_on_' . $surface, 'option') : null;
        $cache[$surface] = ($v === null || $v === '') ? true : (bool) $v;
    }
    return $cache[$surface];
}

function kmpr_am_prices()
{
    // Real subscription on: every visitor sees the first plan's monthly price (no A/B on real money).
    if (function_exists('kmpr_sub_enabled') && kmpr_sub_enabled()) {
        $p = kmpr_sub_plans()[0];
        $m = (int) round($p['price'] / max(1, $p['months']));
        return array('A' => $m, 'B' => $m);
    }
    return array(
        'A' => max(0, (int) kmpr_latin_digits_safe(kmpr_am_opt('am_price_a'))),
        'B' => max(0, (int) kmpr_latin_digits_safe(kmpr_am_opt('am_price_b'))),
    );
}

function kmpr_latin_digits_safe($v)
{
    return function_exists('kmpr_latin_digits') ? kmpr_latin_digits($v) : (string) $v;
}

function kmpr_am_num($n)
{
    $s = number_format((float) $n, 0, '.', '٬');
    return function_exists('kmpr_fa_num') ? kmpr_fa_num($s) : $s;
}

function kmpr_am_pass_on()
{
    // The subscription master switch (inc/arcade-subscription.php) hides every pass surface.
    if (function_exists('kmpr_sub_enabled') && !kmpr_sub_enabled()) {
        return false;
    }
    return kmpr_am_on('pass');
}

function kmpr_am_support_on()
{
    return kmpr_am_on('support') && (string) kmpr_am_opt('am_support_url') !== '';
}

add_action('acf/include_fields', function () {
    if (!function_exists('acf_add_local_field_group') || !function_exists('kmpr_acf')) {
        return;
    }
    $d     = kmpr_am_defaults();
    $f     = function ($name, $label, $type = 'text', $extra = array()) use ($d) {
        if (isset($d[$name])) {
            $extra = array_merge(array('default_value' => $d[$name]), $extra);
        }
        return kmpr_acf('field_kmpr_am_' . $name, $name, $label, $type, $extra);
    };
    $on    = function ($surface, $label) {
        return array('key' => 'field_kmpr_am_on_' . $surface, 'name' => 'am_on_' . $surface, 'label' => $label, 'type' => 'true_false', 'ui' => 1, 'default_value' => 1, 'wrapper' => array('width' => 25));
    };
    $types = function_exists('kmpr_request_options') ? kmpr_request_options()['types'] : array('web_app' => 'وب‌اپلیکیشن');

    acf_add_local_field_group(array(
        'key'      => 'group_kmpr_arcade_monetize',
        'title'    => 'درآمد و تست تقاضا (پاس آرکید، حمایت، اسپانسر)',
        'fields'   => array(
            kmpr_acf_tab('field_kmpr_am_tab_general', 'روشن/خاموش'),
            array('key' => 'field_kmpr_am_msg', 'label' => '', 'name' => '', 'type' => 'message', 'message' => 'هر بخش را می‌توانید جدا خاموش کنید. آمار در «بازی‌ها > درآمد و تست تقاضا» است. هیچ پرداختی تا وقتی «لینک پرداخت» خالی است انجام نمی‌شود؛ کلیک روی «خرید پاس» فقط فرم پیش‌ثبت‌نام را باز می‌کند.'),
            $on('pass', 'پاس آرکید (تست قیمت)'),
            $on('support', 'حمایت از سازنده'),
            $on('sponsor', 'اسپانسر بازی‌ها'),
            $on('adv', 'دعوت به ساخت بازی تبلیغاتی'),
            $f('am_over_every', 'نمایش پاس در پایان بازی: هر چند دست یک‌بار', 'number', array('min' => 2, 'max' => 20, 'instructions' => 'هیچ‌وقت در اولین دست نمایش داده نمی‌شود.')),

            kmpr_acf_tab('field_kmpr_am_tab_pass', 'پاس آرکید'),
            $f('am_pass_kicker', 'برچسب بالای تیتر'),
            $f('am_pass_title', 'تیتر'),
            $f('am_pass_text', 'توضیح', 'textarea', array('rows' => 2, 'new_lines' => '')),
            $f('am_pass_perks', 'مزایا (هر خط یکی)', 'textarea', array('rows' => 5, 'new_lines' => '')),
            $f('am_price_a', 'قیمت نسخه A (تومان)', 'number', array('min' => 0, 'wrapper' => array('width' => 33))),
            $f('am_price_b', 'قیمت نسخه B (تومان)', 'number', array('min' => 0, 'wrapper' => array('width' => 33))),
            $f('am_price_period', 'دوره', 'text', array('wrapper' => array('width' => 33))),
            $f('am_pass_btn', 'متن دکمه', 'text', array('wrapper' => array('width' => 50))),
            $f('am_pass_mini', 'تیتر نسخه کوچک (پایان بازی)', 'text', array('wrapper' => array('width' => 50))),
            $f('am_pay_url', 'لینک پرداخت (اختیاری)', 'url', array('instructions' => 'وقتی درگاه یا صفحه پرداخت شخص ثالث آماده شد اینجا بگذارید؛ دکمه مستقیم به این لینک می‌رود و فرم پیش‌ثبت‌نام دیگر باز نمی‌شود. کلیک‌ها همچنان شمرده می‌شوند. اگر {price} یا {variant} در لینک باشد با قیمت/نسخه بازدیدکننده جایگزین می‌شود.')),
            $f('am_modal_badge', 'برچسب پنجره پیش‌ثبت‌نام', 'text', array('wrapper' => array('width' => 33))),
            $f('am_modal_title', 'تیتر پنجره', 'text', array('wrapper' => array('width' => 33))),
            $f('am_modal_text', 'متن پنجره', 'text', array('wrapper' => array('width' => 33))),
            $f('am_modal_note', 'یادداشت شفافیت (زیر فرم)', 'textarea', array('rows' => 2, 'new_lines' => '')),
            $f('am_modal_success', 'پیام موفقیت', 'textarea', array('rows' => 2, 'new_lines' => '')),

            kmpr_acf_tab('field_kmpr_am_tab_support', 'حمایت از سازنده'),
            $f('am_support_url', 'لینک صفحه حمایت (کافه‌بده، حامی‌باش، دارمت…)', 'url', array('instructions' => 'اگر خالی باشد دکمه حمایت نمایش داده نمی‌شود.')),
            $f('am_support_title', 'تیتر'),
            $f('am_support_text', 'توضیح', 'textarea', array('rows' => 2, 'new_lines' => '')),
            $f('am_support_btn', 'متن دکمه'),

            kmpr_acf_tab('field_kmpr_am_tab_b2b', 'اسپانسر و بازی تبلیغاتی'),
            $f('am_sponsor_label', 'برچسب اسپانسر'),
            $f('am_sponsor_cta', 'متن دعوت به اسپانسری (وقتی بازی اسپانسر ندارد)'),
            $f('am_adv_title', 'تیتر دعوت به بازی تبلیغاتی'),
            $f('am_adv_text', 'توضیح', 'textarea', array('rows' => 2, 'new_lines' => '')),
            $f('am_adv_btn', 'متن دکمه'),
            $f('am_adv_type', 'نوع پروژه پیش‌فرض در فرم درخواست', 'select', array('choices' => $types)),
        ),
        'location' => array(array(array('param' => 'options_page', 'operator' => '==', 'value' => 'kmpr-games-settings'))),
        'menu_order' => 20,
    ));

    acf_add_local_field_group(array(
        'key'      => 'group_kmpr_game_sponsor',
        'title'    => 'اسپانسر این بازی (اختیاری)',
        'fields'   => array(
            kmpr_acf('field_kmpr_gsp_name', 'game_sponsor_name', 'نام حامی', 'text', array('instructions' => 'خالی = بدون اسپانسر (به‌جایش «جای برند شما» نمایش داده می‌شود).', 'wrapper' => array('width' => 50))),
            kmpr_acf('field_kmpr_gsp_url', 'game_sponsor_url', 'لینک حامی', 'url', array('wrapper' => array('width' => 50))),
            kmpr_acf('field_kmpr_gsp_logo', 'game_sponsor_logo', 'لوگو (ترجیحاً افقی، پس‌زمینه شفاف)', 'image', array('return_format' => 'id', 'preview_size' => 'thumbnail', 'mime_types' => 'png,svg,webp,jpg,jpeg')),
            kmpr_acf('field_kmpr_gsp_from', 'game_sponsor_from', 'شروع نمایش', 'date_picker', array('display_format' => 'Y/m/d', 'return_format' => 'Ymd', 'first_day' => 6, 'wrapper' => array('width' => 50), 'instructions' => 'خالی = از همین حالا')),
            kmpr_acf('field_kmpr_gsp_to', 'game_sponsor_to', 'پایان نمایش', 'date_picker', array('display_format' => 'Y/m/d', 'return_format' => 'Ymd', 'first_day' => 6, 'wrapper' => array('width' => 50), 'instructions' => 'خالی = بدون پایان')),
        ),
        'location' => array(array(array('param' => 'post_type', 'operator' => '==', 'value' => 'game'))),
        'position' => 'side',
        'menu_order' => 30,
    ));
});

/**
 * Active sponsor of a game or null.
 */
function kmpr_am_sponsor($game_id)
{
    if (!kmpr_am_on('sponsor') || !$game_id) {
        return null;
    }
    $get  = function ($k) use ($game_id) {
        return function_exists('get_field') ? get_field($k, $game_id) : get_post_meta($game_id, $k, true);
    };
    $name = trim((string) $get('game_sponsor_name'));
    if ($name === '') {
        return null;
    }
    $today = (int) wp_date('Ymd');
    $from  = (int) preg_replace('/\D/', '', (string) $get('game_sponsor_from'));
    $to    = (int) preg_replace('/\D/', '', (string) $get('game_sponsor_to'));
    if (($from && $today < $from) || ($to && $today > $to)) {
        return null;
    }
    $logo = (int) $get('game_sponsor_logo');
    return array('name' => $name, 'url' => (string) $get('game_sponsor_url'), 'logo' => $logo, 'from' => $from, 'to' => $to);
}

/* =========================================================================
 * 2. Counters: one option, daily buckets
 * ====================================================================== */

function kmpr_am_stats_raw()
{
    $s = get_option(KMPR_AM_OPTION, array());
    return is_array($s) ? $s : array();
}

/**
 * Add increments ( key => n ) to today's bucket and prune old days.
 */
function kmpr_am_bump(array $inc)
{
    if (!$inc) {
        return;
    }
    $all = kmpr_am_stats_raw();
    $day = wp_date('Ymd');
    foreach ($inc as $k => $n) {
        $all[$day][$k] = ($all[$day][$k] ?? 0) + (int) $n;
    }
    if (count($all) > KMPR_AM_KEEP) {
        ksort($all);
        $all = array_slice($all, -KMPR_AM_KEEP, null, true);
    }
    update_option(KMPR_AM_OPTION, $all, false);
}

/**
 * Sum of every counter over the last $days days (today included).
 */
function kmpr_am_sum($days, $offset = 0)
{
    $all  = kmpr_am_stats_raw();
    $from = (int) wp_date('Ymd', time() - ($days + $offset - 1) * DAY_IN_SECONDS);
    $to   = (int) wp_date('Ymd', time() - $offset * DAY_IN_SECONDS);
    $out  = array();
    foreach ($all as $day => $bucket) {
        if ((int) $day < $from || (int) $day > $to || !is_array($bucket)) {
            continue;
        }
        foreach ($bucket as $k => $n) {
            $out[$k] = ($out[$k] ?? 0) + (int) $n;
        }
    }
    return $out;
}

/* =========================================================================
 * 3. Pre-signups CPT (non-public)
 * ====================================================================== */

add_action('init', function () {
    register_post_type(KMPR_AM_CPT, array(
        'label'               => 'پیش‌ثبت‌نام پاس آرکید',
        'public'              => false,
        'show_ui'             => false,
        'show_in_rest'        => false,
        'exclude_from_search' => true,
        'publicly_queryable'  => false,
        'rewrite'             => false,
        'query_var'           => false,
        'supports'            => array('title'),
    ));
});

/* =========================================================================
 * 4. REST: track + pre-signup
 * ====================================================================== */

function kmpr_am_is_bot()
{
    $ua = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
    return $ua === '' || (bool) preg_match('/bot|crawl|spider|slurp|headless|lighthouse|pagespeed|preview|facebookexternalhit|curl|wget|python|httpclient|java\/|go-http|axios|node-fetch|phantom|selenium|puppeteer/i', $ua);
}

/**
 * Logged-in admin? REST requests without a nonce are anonymous, so read the auth cookie directly.
 */
function kmpr_am_is_admin_visitor()
{
    if (current_user_can('manage_options')) {
        return true;
    }
    if (empty($_COOKIE[LOGGED_IN_COOKIE])) {
        return false;
    }
    $uid = wp_validate_auth_cookie($_COOKIE[LOGGED_IN_COOKIE], 'logged_in'); // phpcs:ignore
    return $uid && user_can($uid, 'manage_options');
}

function kmpr_am_ip_key($prefix)
{
    return $prefix . md5(($_SERVER['REMOTE_ADDR'] ?? '') . wp_salt('nonce'));
}

/**
 * Per-IP fixed-window throttle. Returns true when the call is allowed.
 */
function kmpr_am_throttle($prefix, $max, $window)
{
    $key  = kmpr_am_ip_key($prefix);
    $hits = (int) get_transient($key);
    if ($hits >= $max) {
        return false;
    }
    set_transient($key, $hits + 1, $window);
    return true;
}

function kmpr_am_is_game($id)
{
    return $id > 0 && get_post_type($id) === 'game' && get_post_status($id) === 'publish';
}

/**
 * Validate one tracking event → counter key (or '' when invalid).
 * Keys: pass.{hub|over}.{A|B}.{i|c|s} · sup.{hub|over|side}.{i|c} · sp.{gid}.{i|c} · spc.{i|c} · adv.{i|c} · pl.{gid}
 */
function kmpr_am_event_key($e)
{
    if (!is_array($e)) {
        return '';
    }
    $t = (string) ($e['t'] ?? '');
    $a = (string) ($e['a'] ?? '');
    $s = (string) ($e['s'] ?? '');
    $g = (int) ($e['g'] ?? 0);
    $v = (string) ($e['v'] ?? '');
    $ic = in_array($a, array('i', 'c'), true);
    switch ($t) {
        case 'pass':
            return ($ic && in_array($s, array('hub', 'over'), true) && in_array($v, array('A', 'B'), true)) ? "pass.$s.$v.$a" : '';
        case 'sup':
            return ($ic && in_array($s, array('hub', 'over', 'side'), true)) ? "sup.$s.$a" : '';
        case 'sp':
            return ($ic && kmpr_am_is_game($g)) ? "sp.$g.$a" : '';
        case 'spc':
        case 'adv':
            return $ic ? "$t.$a" : '';
        case 'pl':
            return ($a === 's' && kmpr_am_is_game($g)) ? "pl.$g" : '';
    }
    return '';
}

add_action('rest_api_init', function () {
    register_rest_route('kmpr/v1', '/arcade/track', array(
        'methods'             => 'POST',
        'callback'            => 'kmpr_am_rest_track',
        'permission_callback' => '__return_true',
    ));
    register_rest_route('kmpr/v1', '/arcade/presignup', array(
        'methods'             => 'POST',
        'callback'            => 'kmpr_am_rest_presignup',
        'permission_callback' => '__return_true',
    ));
});

function kmpr_am_rest_track(WP_REST_Request $request)
{
    $ok = new WP_REST_Response(null, 204);
    if (kmpr_am_is_bot() || kmpr_am_is_admin_visitor()) {
        return $ok;
    }
    if (!kmpr_am_throttle('kmpr_am_t_', 40, 10 * MINUTE_IN_SECONDS)) {
        return new WP_REST_Response(null, 429);
    }
    $p   = $request->get_json_params();
    $evs = array_slice((array) ($p['ev'] ?? array()), 0, 15);
    $inc = array();
    foreach ($evs as $e) {
        $k = kmpr_am_event_key($e);
        if ($k !== '') {
            $inc[$k] = min(5, ($inc[$k] ?? 0) + 1); // a single batch can't inflate one counter
        }
    }
    kmpr_am_bump($inc);
    return $ok;
}

/**
 * Normalise a contact: Iranian mobile → 09xxxxxxxxx, Telegram → @username. '' when empty, false when invalid.
 */
function kmpr_am_contact($raw)
{
    $raw = trim(kmpr_latin_digits_safe(wp_strip_all_tags((string) $raw)));
    if ($raw === '') {
        return '';
    }
    $digits = preg_replace('/[\s\-()+]/', '', $raw);
    if (preg_match('/^(?:0098|98|0)?(9\d{9})$/', $digits, $m)) {
        return '0' . $m[1];
    }
    $raw = preg_replace('#^(https?://)?(t\.me|telegram\.me)/#i', '@', $raw);
    if (preg_match('/^@?([A-Za-z][A-Za-z0-9_]{4,31})$/', $raw, $m)) {
        return '@' . $m[1];
    }
    return false;
}

function kmpr_am_rest_presignup(WP_REST_Request $request)
{
    $p = $request->get_json_params() ?: $request->get_body_params();
    if (!empty($p['website'])) { // honeypot: pretend success
        return new WP_REST_Response(array('ok' => true), 200);
    }
    $opened = (int) ($p['opened'] ?? 0);
    if (!$opened || (time() * 1000 - $opened) < 2500) {
        return new WP_REST_Response(array('ok' => false, 'message' => 'کمی آهسته‌تر! چند ثانیه بعد دوباره امتحان کن.'), 429);
    }
    if (kmpr_am_is_bot()) {
        return new WP_REST_Response(array('ok' => true), 200);
    }
    if (!kmpr_am_throttle('kmpr_am_s_', 4, HOUR_IN_SECONDS)) {
        return new WP_REST_Response(array('ok' => false, 'message' => 'تعداد ثبت‌ها از این اتصال زیاد است؛ کمی بعد دوباره امتحان کن.'), 429);
    }

    $contact = kmpr_am_contact($p['contact'] ?? '');
    if ($contact === false) {
        return new WP_REST_Response(array('ok' => false, 'field' => 'contact', 'message' => 'شماره موبایل (۰۹…) یا آیدی تلگرام (@…) معتبر نیست.'), 400);
    }
    $nick    = sanitize_text_field(mb_substr((string) ($p['nickname'] ?? ''), 0, 32));
    $variant = in_array(($p['variant'] ?? ''), array('A', 'B'), true) ? $p['variant'] : 'A';
    $surface = in_array(($p['surface'] ?? ''), array('hub', 'over'), true) ? $p['surface'] : 'hub';
    $game    = (int) ($p['game'] ?? 0);
    $game    = kmpr_am_is_game($game) ? $game : 0;
    $player  = preg_match('/^[0-9a-f-]{36}$/i', (string) ($p['player'] ?? '')) ? strtolower($p['player']) : '';
    $price   = kmpr_am_prices()[$variant];

    // Same contact (or same player) twice → keep one row, don't count again.
    if ($contact !== '' || $player !== '') {
        $dupe = get_posts(array(
            'post_type'      => KMPR_AM_CPT,
            'post_status'    => 'private',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'no_found_rows'  => true,
            'meta_query'     => array('relation' => 'OR', array('key' => '_am_contact', 'value' => $contact !== '' ? $contact : '-'), array('key' => '_am_player', 'value' => $player !== '' ? $player : '-')),
        ));
        if ($dupe) {
            return new WP_REST_Response(array('ok' => true, 'dupe' => true), 200);
        }
    }

    $id = wp_insert_post(array(
        'post_type'   => KMPR_AM_CPT,
        'post_status' => 'private',
        'post_title'  => $nick !== '' ? $nick : ($contact !== '' ? $contact : 'بی‌نام'),
        'meta_input'  => array(
            '_am_contact' => $contact,
            '_am_nick'    => $nick,
            '_am_variant' => $variant,
            '_am_price'   => $price,
            '_am_surface' => $surface,
            '_am_game'    => $game,
            '_am_player'  => $player,
        ),
    ), true);
    if (is_wp_error($id)) {
        return new WP_REST_Response(array('ok' => false, 'message' => 'ثبت انجام نشد؛ لطفاً دوباره تلاش کن.'), 500);
    }
    if (!kmpr_am_is_admin_visitor()) {
        kmpr_am_bump(array("pass.$surface.$variant.s" => 1));
    }
    delete_transient('kmpr_am_supporters');
    update_post_meta($id, '_am_tg', kmpr_am_notify($id));
    return new WP_REST_Response(array('ok' => true), 200);
}

/**
 * Telegram notice through the project-requests transport. Returns sent|skipped|failed.
 */
function kmpr_am_notify($id)
{
    if (!function_exists('kmpr_tg_send') || !function_exists('kmpr_tg_token') || !kmpr_tg_token() || !kmpr_tg_chat_id()) {
        return 'skipped';
    }
    $e       = function ($v) {
        return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
    };
    $m       = function ($k) use ($id) {
        return get_post_meta($id, $k, true);
    };
    $game    = (int) $m('_am_game');
    $total   = (int) (wp_count_posts(KMPR_AM_CPT)->private ?? 0);
    $lines   = array(
        '🎟 <b>پیش‌ثبت‌نام جدید «پاس آرکید»</b>',
        '',
        '👤 نام: ' . $e($m('_am_nick') ?: '—'),
        '📞 تماس: ' . $e($m('_am_contact') ?: 'ثبت نشده'),
        '💰 نسخه قیمت: ' . $e($m('_am_variant')) . ' — ' . $e(number_format((int) $m('_am_price'))) . ' تومان',
        '📍 محل: ' . ($m('_am_surface') === 'over' ? 'پایان بازی' : 'صفحه گیم‌سنتر') . ($game ? ' · ' . $e(get_the_title($game)) : ''),
        '',
        '📊 مجموع پیش‌ثبت‌نام‌ها: ' . $total,
    );
    return kmpr_tg_send(implode("\n", $lines)) ? 'sent' : 'failed';
}

/* =========================================================================
 * 5. Front-end: assets, config, markup
 * ====================================================================== */

function kmpr_am_on_games_page()
{
    return function_exists('kmpr_is_games_page') ? kmpr_is_games_page() : (is_singular('game') || is_post_type_archive('game') || is_tax('game_category'));
}

function kmpr_am_any_on()
{
    return kmpr_am_pass_on() || kmpr_am_support_on() || kmpr_am_on('sponsor') || kmpr_am_on('adv');
}

function kmpr_am_utm_url($base, $medium, $campaign, $extra = array())
{
    return add_query_arg(array_merge($extra, array('utm_source' => 'arcade', 'utm_medium' => $medium, 'utm_campaign' => $campaign)), $base);
}

function kmpr_am_adv_url()
{
    $base = function_exists('kmpr_request_url') ? kmpr_request_url() : home_url('/contact-us/#smart-configurator');
    $slug = is_singular('game') ? get_post_field('post_name', get_queried_object_id()) : 'hub';
    return kmpr_am_utm_url($base, 'game_over', 'advergame', array('project' => sanitize_key(kmpr_am_opt('am_adv_type')) ?: 'web_app', 'ref' => 'arcade-' . $slug));
}

function kmpr_am_sponsor_cta_url()
{
    $base = function_exists('kmpr_request_url') ? kmpr_request_url() : home_url('/contact-us/#smart-configurator');
    $slug = is_singular('game') ? get_post_field('post_name', get_queried_object_id()) : 'hub';
    return kmpr_am_utm_url($base, 'sponsor_slot', 'sponsorship', array('project' => 'consult', 'ref' => 'sponsor-' . $slug));
}

add_action('wp_enqueue_scripts', function () {
    if (!kmpr_am_on_games_page() || !kmpr_am_any_on()) {
        return;
    }
    $rel  = '/public/js/arcade-monetize.js';
    $file = get_template_directory() . $rel;
    if (!file_exists($file)) {
        return;
    }
    wp_enqueue_script('kmpr-arcade-monetize', get_template_directory_uri() . $rel, array(), (string) filemtime($file), array('in_footer' => true, 'strategy' => 'defer'));
    $game = is_singular('game') ? get_queried_object_id() : 0;
    $cfg  = array(
        'rest'  => esc_url_raw(rest_url('kmpr/v1/arcade')),
        'track' => !kmpr_am_is_admin_visitor(),
        'gid'   => $game,
        'pass'  => kmpr_am_pass_on(),
        'pay'   => (string) kmpr_am_opt('am_pay_url'),
        'price' => kmpr_am_prices(),
        'sup'   => kmpr_am_support_on(),
        'adv'   => kmpr_am_on('adv'),
        'every' => max(2, (int) kmpr_am_opt('am_over_every')),
    );
    wp_add_inline_script('kmpr-arcade-monetize', 'window.kmprAM=' . wp_json_encode($cfg) . ';', 'before');
}, 30);

/* ---------- tiny SVG set (explicit size + shrink-0) ---------- */
function kmpr_am_icon($name, $size = 18)
{
    $paths = array(
        'ticket' => '<path d="M3 8a2 2 0 0 0 2-2h14a2 2 0 0 0 2 2v2a2 2 0 0 0 0 4v2a2 2 0 0 0-2 2H5a2 2 0 0 0-2-2v-2a2 2 0 0 0 0-4Z"/><path d="M14 6v12" stroke-dasharray="2 2.5"/>',
        'check'  => '<path d="m5 12.5 4.5 4.5L19 7.5"/>',
        'coffee' => '<path d="M4 9h13v5a5 5 0 0 1-5 5H9a5 5 0 0 1-5-5Z"/><path d="M17 11h1.5a2.5 2.5 0 0 1 0 5H17"/><path d="M8 3.5c0 1.5 1.2 1.5 1.2 3M12 3.5c0 1.5 1.2 1.5 1.2 3"/>',
        'spark'  => '<path d="M12 3v4M12 17v4M3 12h4M17 12h4M6 6l2.5 2.5M15.5 15.5 18 18M6 18l2.5-2.5M15.5 8.5 18 6"/>',
        'pad'    => '<rect x="2.5" y="7" width="19" height="11" rx="5.5"/><path d="M7.5 10.5v4M5.5 12.5h4"/><circle cx="15.5" cy="11.5" r=".8" fill="currentColor"/><circle cx="17.5" cy="13.8" r=".8" fill="currentColor"/>',
        'arrow'  => '<path d="M15 6l-6 6 6 6"/>',
        'close'  => '<path d="M6 6l12 12M18 6 6 18"/>',
        'crown'  => '<path d="m3 8 4.5 4L12 5l4.5 7L21 8l-2 11H5Z"/>',
    );
    return '<svg width="' . (int) $size . '" height="' . (int) $size . '" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . ($paths[$name] ?? '') . '</svg>';
}

/**
 * Price block: both variants stacked in the same grid cell; JS reveals the visitor's one (zero layout shift).
 */
function kmpr_am_price_html($class = '')
{
    $prices = kmpr_am_prices();
    $out    = '<span class="am-vv ' . esc_attr($class) . '">';
    foreach ($prices as $v => $p) {
        $out .= '<span data-v="' . esc_attr($v) . '"><b>' . esc_html(kmpr_am_num($p)) . '</b> <small>تومان</small></span>';
    }
    return $out . '</span>';
}

function kmpr_am_buy_html($surface, $class = 'am-btn am-btn--gold')
{
    $label = esc_html(kmpr_am_opt('am_pass_btn'));
    $pay   = (string) kmpr_am_opt('am_pay_url');
    if (function_exists('kmpr_sub_enabled') && kmpr_sub_enabled()) {
        return '<button type="button" class="' . esc_attr($class) . '" data-am-go="sub" aria-haspopup="dialog">' . kmpr_am_icon('ticket') . '<span>' . $label . '</span></button>';
    }
    if ($pay !== '') {
        return '<a class="' . esc_attr($class) . '" href="' . esc_url($pay) . '" data-am-go="pay" rel="nofollow">' . kmpr_am_icon('ticket') . '<span>' . $label . '</span></a>';
    }
    return '<button type="button" class="' . esc_attr($class) . '" data-am-go="buy" aria-haspopup="dialog">' . kmpr_am_icon('ticket') . '<span>' . $label . '</span></button>';
}

function kmpr_am_render_pass_card()
{
    $src   = function_exists('kmpr_sub_enabled') && kmpr_sub_enabled() ? kmpr_sub_opt('sub_perks') : kmpr_am_opt('am_pass_perks');
    $perks = function_exists('kmpr_lines') ? kmpr_lines($src) : array();
    ?>
    <section class="am-pass" data-am="pass" data-am-s="hub" aria-labelledby="am-pass-title">
        <div class="am-pass__fx" aria-hidden="true"></div>
        <div class="am-pass__main">
            <span class="am-kicker"><?= kmpr_am_icon('crown', 16); ?><?= esc_html(kmpr_am_opt('am_pass_kicker')); ?></span>
            <h2 id="am-pass-title" class="am-pass__title"><?= esc_html(kmpr_am_opt('am_pass_title')); ?></h2>
            <p class="am-pass__text"><?= esc_html(kmpr_am_opt('am_pass_text')); ?></p>
            <?php if ($perks) : ?>
                <ul class="am-perks">
                    <?php foreach ($perks as $perk) : ?>
                        <li><span class="am-perks__ic"><?= kmpr_am_icon('check', 14); ?></span><?= esc_html($perk); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        <div class="am-pass__buy">
            <div class="am-ticket">
                <span class="am-ticket__label"><?= esc_html(kmpr_am_opt('am_pass_title')); ?> · <?= esc_html(kmpr_am_opt('am_price_period')); ?></span>
                <?= kmpr_am_price_html('am-ticket__price'); ?>
                <?= kmpr_am_buy_html('hub'); ?>
            </div>
        </div>
    </section>
    <?php
}

function kmpr_am_render_support_card($surface, $compact = false)
{
    ?>
    <aside class="am-sup<?= $compact ? ' am-sup--mini' : ''; ?>" data-am="sup" data-am-s="<?= esc_attr($surface); ?>" aria-label="<?= esc_attr(kmpr_am_opt('am_support_title')); ?>">
        <span class="am-sup__ic"><?= kmpr_am_icon('coffee', 24); ?></span>
        <div class="am-sup__body">
            <p class="am-sup__title"><?= esc_html(kmpr_am_opt('am_support_title')); ?></p>
            <p class="am-sup__text"><?= esc_html(kmpr_am_opt('am_support_text')); ?></p>
        </div>
        <a class="am-btn am-btn--ghost" href="<?= esc_url(kmpr_am_opt('am_support_url')); ?>" target="_blank" rel="noopener nofollow" data-am-go="sup"><?= esc_html(kmpr_am_opt('am_support_btn')); ?></a>
    </aside>
    <?php
}

/* ---------- hub slots ----------
 * after_hero → pass card (wide) · sidebar → support card (+ pass if it was not shown) · after_rails → pass fallback.
 */
$GLOBALS['kmpr_am_hub_done'] = array();

add_action('kmpr_games_hub_slot', function ($slot) {
    $done = &$GLOBALS['kmpr_am_hub_done'];
    $want = array(
        'after_hero'  => array('pass'),
        'sidebar'     => array('sup', 'pass'),
        'after_rails' => array('pass'), // fallback only; it renders before the sidebar in source order
    );
    if (!isset($want[$slot])) {
        return;
    }
    $show = array();
    foreach ($want[$slot] as $what) {
        $on = $what === 'pass' ? kmpr_am_pass_on() : kmpr_am_support_on();
        if ($on && empty($done[$what])) {
            $show[]      = $what;
            $done[$what] = true;
        }
    }
    if (!$show) {
        return;
    }
    echo '<div class="am-hub am-hub--' . esc_attr($slot) . '">';
    foreach ($show as $what) {
        if ($what === 'pass') {
            kmpr_am_render_pass_card();
        } else {
            kmpr_am_render_support_card($slot === 'sidebar' ? 'side' : 'hub', $slot === 'sidebar');
        }
    }
    echo '</div>';
}, 10, 1);

/* ---------- single game: sidebar card ---------- */
add_action('kmpr_game_slot', function ($game_id, $slot = '') {
    if ($slot === 'sidebar' && kmpr_am_support_on()) {
        kmpr_am_render_support_card('side', true);
    }
}, 10, 2);

/**
 * Inert <template>s for the JS-filled DOM slots + the pre-signup dialog.
 */
add_action('wp_footer', function () {
    if (!kmpr_am_on_games_page() || !kmpr_am_any_on()) {
        return;
    }
    $game = is_singular('game') ? get_queried_object_id() : 0;
    if ($game) {
        $sp = kmpr_am_sponsor($game);
        if ($sp) {
            $logo = $sp['logo'] ? wp_get_attachment_image($sp['logo'], 'medium', false, array('class' => 'am-sp__logo', 'alt' => $sp['name'], 'loading' => 'lazy', 'decoding' => 'async')) : '';
            $tag  = $sp['url'] ? 'a' : 'span';
            $attr = $sp['url'] ? ' href="' . esc_url($sp['url']) . '" target="_blank" rel="sponsored noopener" data-am-go="sp"' : '';
            echo '<template data-am-tpl="sponsor"><div class="am-sp" data-am="sp" data-am-g="' . (int) $game . '"><span class="am-sp__label">' . esc_html(kmpr_am_opt('am_sponsor_label')) . '</span><' . $tag . ' class="am-sp__brand"' . $attr . '>' . $logo . '<span>' . esc_html($sp['name']) . '</span></' . $tag . '></div></template>'; // phpcs:ignore
        } elseif (kmpr_am_on('sponsor')) {
            echo '<template data-am-tpl="sponsor-cta"><div class="am-sp am-sp--cta" data-am="spc"><a class="am-sp__cta" href="' . esc_url(kmpr_am_sponsor_cta_url()) . '" data-am-go="spc">' . kmpr_am_icon('spark', 16) . '<span>' . esc_html(kmpr_am_opt('am_sponsor_cta')) . '</span></a></div></template>'; // phpcs:ignore
        }
        if (kmpr_am_pass_on()) {
            echo '<template data-am-tpl="pass"><div class="am-mini am-mini--pass" data-am="pass" data-am-s="over"><span class="am-mini__ic">' . kmpr_am_icon('crown', 22) . '</span><div class="am-mini__body"><p class="am-mini__title">' . esc_html(kmpr_am_opt('am_pass_mini')) . '</p><p class="am-mini__text">' . esc_html(kmpr_am_opt('am_pass_title')) . ' · ' . kmpr_am_price_html() . ' <small>' . esc_html(kmpr_am_opt('am_price_period')) . '</small></p></div>' . kmpr_am_buy_html('over', 'am-btn am-btn--gold am-btn--sm') . '</div></template>'; // phpcs:ignore
        }
        if (kmpr_am_support_on()) {
            echo '<template data-am-tpl="support"><div class="am-mini" data-am="sup" data-am-s="over"><span class="am-mini__ic am-mini__ic--sup">' . kmpr_am_icon('coffee', 22) . '</span><div class="am-mini__body"><p class="am-mini__title">' . esc_html(kmpr_am_opt('am_support_title')) . '</p><p class="am-mini__text">' . esc_html(kmpr_am_opt('am_support_text')) . '</p></div><a class="am-btn am-btn--ghost am-btn--sm" href="' . esc_url(kmpr_am_opt('am_support_url')) . '" target="_blank" rel="noopener nofollow" data-am-go="sup">' . esc_html(kmpr_am_opt('am_support_btn')) . '</a></div></template>'; // phpcs:ignore
        }
        if (kmpr_am_on('adv')) {
            echo '<template data-am-tpl="adv"><div class="am-mini am-mini--adv" data-am="adv"><span class="am-mini__ic am-mini__ic--adv">' . kmpr_am_icon('pad', 22) . '</span><div class="am-mini__body"><p class="am-mini__title">' . esc_html(kmpr_am_opt('am_adv_title')) . '</p><p class="am-mini__text">' . esc_html(kmpr_am_opt('am_adv_text')) . '</p></div><a class="am-btn am-btn--fuchsia am-btn--sm" href="' . esc_url(kmpr_am_adv_url()) . '" data-am-go="adv">' . esc_html(kmpr_am_opt('am_adv_btn')) . '</a></div></template>'; // phpcs:ignore
        }
    }
    if (kmpr_am_pass_on() && (string) kmpr_am_opt('am_pay_url') === '' && !(function_exists('kmpr_sub_enabled') && kmpr_sub_enabled())) {
        kmpr_am_render_modal();
    }
}, 5);

function kmpr_am_render_modal()
{
    $prices = kmpr_am_prices();
    ?>
    <dialog class="am-modal" data-am-modal aria-labelledby="am-modal-title" aria-describedby="am-modal-text">
        <div class="am-modal__card">
            <button type="button" class="am-modal__x" data-am-close aria-label="بستن"><?= kmpr_am_icon('close', 20); ?></button>
            <div class="am-modal__form" data-am-step="form">
                <span class="am-modal__badge"><?= kmpr_am_icon('spark', 16); ?><?= esc_html(kmpr_am_opt('am_modal_badge')); ?></span>
                <h2 id="am-modal-title" class="am-modal__title"><?= esc_html(kmpr_am_opt('am_modal_title')); ?></h2>
                <p id="am-modal-text" class="am-modal__text"><?= esc_html(kmpr_am_opt('am_modal_text')); ?></p>
                <p class="am-modal__price">
                    <span class="am-vv"><?php foreach ($prices as $v => $p) : ?><span data-v="<?= esc_attr($v); ?>"><s><?= esc_html(kmpr_am_num($p)); ?></s> <b><?= esc_html(kmpr_am_num(round($p / 2))); ?></b> <small>تومان · <?= esc_html(kmpr_am_opt('am_price_period')); ?> برای پیش‌ثبت‌نامی‌ها</small></span><?php endforeach; ?></span>
                </p>
                <form class="am-form" data-am-form novalidate>
                    <label class="am-field">
                        <span>نام مستعار</span>
                        <input type="text" name="nickname" maxlength="32" autocomplete="nickname" placeholder="مثلاً: کوروش">
                    </label>
                    <label class="am-field">
                        <span>موبایل یا آیدی تلگرام <em>(اختیاری)</em></span>
                        <input type="text" name="contact" maxlength="40" dir="ltr" autocomplete="tel" inputmode="text" placeholder="09… یا @username" aria-describedby="am-contact-hint">
                        <small id="am-contact-hint">فقط برای خبر دادن زمان لانچ؛ بدون تبلیغ و اسپم.</small>
                    </label>
                    <div class="am-hp" aria-hidden="true"><label>وب‌سایت <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
                    <p class="am-form__status" data-am-status role="status" aria-live="polite"></p>
                    <button type="submit" class="am-btn am-btn--gold am-btn--block"><span>پیش‌ثبت‌نام می‌کنم</span></button>
                    <p class="am-modal__note"><?= esc_html(kmpr_am_opt('am_modal_note')); ?></p>
                </form>
            </div>
            <div class="am-modal__done" data-am-step="done" hidden>
                <span class="am-modal__ok"><?= kmpr_am_icon('check', 32); ?></span>
                <h2 class="am-modal__title" tabindex="-1">جای تو محفوظ شد</h2>
                <p class="am-modal__text"><?= esc_html(kmpr_am_opt('am_modal_success')); ?></p>
                <button type="button" class="am-btn am-btn--ghost am-btn--block" data-am-close>برگردیم سراغ بازی</button>
            </div>
        </div>
    </dialog>
    <?php
}

/* ---------- player badge: early supporters (filter returns short text labels; $row has player_id) ---------- */
function kmpr_am_supporter_ids()
{
    static $ids = null;
    if ($ids === null) {
        $ids = get_transient('kmpr_am_supporters');
        if (!is_array($ids)) {
            global $wpdb;
            $ids = array_flip((array) $wpdb->get_col($wpdb->prepare("SELECT pm.meta_value FROM {$wpdb->postmeta} pm JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE p.post_type = %s AND pm.meta_key = '_am_player' AND pm.meta_value <> ''", KMPR_AM_CPT)));
            set_transient('kmpr_am_supporters', $ids, HOUR_IN_SECONDS);
        }
    }
    return $ids;
}

add_filter('kmpr_game_player_badges', function ($badges, $row = null) {
    $pid = is_array($row) ? ($row['player_id'] ?? '') : (is_object($row) ? ($row->player_id ?? '') : '');
    if (is_array($badges) && is_string($pid) && $pid !== '' && isset(kmpr_am_supporter_ids()[strtolower($pid)])) {
        $badges[] = 'حامی اولیه';
    }
    return $badges;
}, 10, 2);

/* =========================================================================
 * 6. Admin: «درآمد و تست تقاضا»
 * ====================================================================== */

add_action('admin_menu', function () {
    $hook = add_submenu_page('edit.php?post_type=game', 'درآمد و تست تقاضا', 'درآمد و تست تقاضا', 'manage_options', KMPR_AM_PAGE, 'kmpr_am_admin_page');
    if ($hook) {
        add_action('load-' . $hook, 'kmpr_am_admin_actions');
    }
}, 30);

function kmpr_am_admin_url($args = array())
{
    return add_query_arg(array_merge(array('post_type' => 'game', 'page' => KMPR_AM_PAGE), $args), admin_url('edit.php'));
}

/**
 * POST/GET actions before any output: delete (row/bulk), reset, CSV export.
 */
function kmpr_am_admin_actions()
{
    if (!current_user_can('manage_options')) {
        return;
    }
    $req = wp_unslash($_REQUEST); // phpcs:ignore
    $act = sanitize_key($req['am_action'] ?? '');
    if ($act === '' && ($req['action'] ?? '') === 'am_delete') {
        $act = 'bulk_delete';
    }
    if ($act === '' && ($req['action2'] ?? '') === 'am_delete') {
        $act = 'bulk_delete';
    }

    if ($act === 'reset') {
        check_admin_referer('kmpr_am_reset');
        delete_option(KMPR_AM_OPTION);
        wp_safe_redirect(kmpr_am_admin_url(array('am_msg' => 'reset')));
        exit;
    }
    if ($act === 'delete') {
        $id = (int) ($req['id'] ?? 0);
        check_admin_referer('kmpr_am_del_' . $id);
        if (get_post_type($id) === KMPR_AM_CPT) {
            wp_delete_post($id, true);
        }
        wp_safe_redirect(kmpr_am_admin_url(array('am_msg' => 'deleted')));
        exit;
    }
    if ($act === 'bulk_delete') {
        check_admin_referer('bulk-am_signups');
        $n = 0;
        foreach (array_map('intval', (array) ($req['signup'] ?? array())) as $id) {
            if (get_post_type($id) === KMPR_AM_CPT && wp_delete_post($id, true)) {
                $n++;
            }
        }
        wp_safe_redirect(kmpr_am_admin_url(array('am_msg' => 'deleted', 'n' => $n)));
        exit;
    }
    if ($act === 'export') {
        check_admin_referer('kmpr_am_export');
        $ids = get_posts(array('post_type' => KMPR_AM_CPT, 'post_status' => 'private', 'posts_per_page' => -1, 'fields' => 'ids', 'orderby' => 'date', 'order' => 'DESC'));
        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=arcade-presignups-' . wp_date('Y-m-d') . '.csv');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, array('id', 'date', 'nickname', 'contact', 'variant', 'price_toman', 'surface', 'game', 'telegram'));
        $cell = function ($v) { // spreadsheet formula-injection guard (@telegram, +98…)
            $v = (string) $v;
            return ($v !== '' && strpos('=+-@', $v[0]) !== false) ? "'" . $v : $v;
        };
        foreach ($ids as $id) {
            $g = (int) get_post_meta($id, '_am_game', true);
            fputcsv($out, array_map($cell, array($id, get_the_date('Y-m-d H:i', $id), get_post_meta($id, '_am_nick', true), get_post_meta($id, '_am_contact', true), get_post_meta($id, '_am_variant', true), get_post_meta($id, '_am_price', true), get_post_meta($id, '_am_surface', true), $g ? get_the_title($g) : '', get_post_meta($id, '_am_tg', true))));
        }
        fclose($out);
        exit;
    }
}

/**
 * Declared on demand (only the admin page needs it).
 */
function kmpr_am_define_table()
{
    if (class_exists('KMPR_AM_Signups_Table')) {
        return;
    }
    if (!class_exists('WP_List_Table')) {
        require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
    }

    class KMPR_AM_Signups_Table extends WP_List_Table
    {
        public function __construct()
        {
            parent::__construct(array('singular' => 'am_signup', 'plural' => 'am_signups', 'ajax' => false));
        }

        public function get_columns()
        {
            return array('cb' => '<input type="checkbox">', 'name' => 'نام', 'contact' => 'تماس', 'offer' => 'نسخه / قیمت', 'surface' => 'محل', 'tg' => 'تلگرام', 'date' => 'تاریخ');
        }

        protected function get_bulk_actions()
        {
            return array('am_delete' => 'حذف');
        }

        public function no_items()
        {
            echo 'هنوز کسی پیش‌ثبت‌نام نکرده است.';
        }

        public function prepare_items()
        {
            $per   = 20;
            $paged = max(1, (int) ($_GET['paged'] ?? 1)); // phpcs:ignore
            $q     = new WP_Query(array('post_type' => KMPR_AM_CPT, 'post_status' => 'private', 'posts_per_page' => $per, 'paged' => $paged, 'orderby' => 'date', 'order' => 'DESC'));
            $this->items = $q->posts;
            $this->_column_headers = array($this->get_columns(), array(), array());
            $this->set_pagination_args(array('total_items' => (int) $q->found_posts, 'per_page' => $per));
        }

        protected function column_cb($item)
        {
            return '<input type="checkbox" name="signup[]" value="' . (int) $item->ID . '">';
        }

        protected function column_name($item)
        {
            $nick = get_post_meta($item->ID, '_am_nick', true);
            $del  = wp_nonce_url(kmpr_am_admin_url(array('am_action' => 'delete', 'id' => $item->ID)), 'kmpr_am_del_' . $item->ID);
            return '<strong>' . esc_html($nick !== '' ? $nick : '—') . '</strong>' . $this->row_actions(array('delete' => '<a href="' . esc_url($del) . '" onclick="return confirm(\'حذف شود؟\')">حذف</a>'));
        }

        protected function column_default($item, $col)
        {
            $m = function ($k) use ($item) {
                return get_post_meta($item->ID, $k, true);
            };
            switch ($col) {
                case 'contact':
                    $c = (string) $m('_am_contact');
                    if ($c === '') {
                        return '<span style="color:#94a3b8">ثبت نشده</span>';
                    }
                    $href = $c[0] === '@' ? 'https://t.me/' . substr($c, 1) : 'tel:' . $c;
                    return '<a dir="ltr" href="' . esc_url($href) . '" target="_blank" rel="noopener">' . esc_html($c) . '</a>';
                case 'offer':
                    return esc_html($m('_am_variant') . ' — ' . kmpr_am_num((int) $m('_am_price')) . ' تومان');
                case 'surface':
                    $g = (int) $m('_am_game');
                    return esc_html(($m('_am_surface') === 'over' ? 'پایان بازی' : 'هاب گیم‌سنتر') . ($g ? ' · ' . get_the_title($g) : ''));
                case 'tg':
                    $map = array('sent' => 'ارسال شد', 'skipped' => 'توکن تنظیم نشده', 'failed' => 'خطا');
                    return esc_html($map[$m('_am_tg')] ?? '—');
                case 'date':
                    $ts = get_post_time('U', true, $item);
                    return esc_html(function_exists('shamsi_date') ? shamsi_date('j F Y · H:i', $ts + (int) (get_option('gmt_offset') * HOUR_IN_SECONDS)) : get_the_date('', $item));
            }
            return '';
        }
    }
}

function kmpr_am_pct($a, $b, $digits = 1)
{
    if ($b <= 0) {
        return '—';
    }
    return (function_exists('kmpr_fa_num') ? kmpr_fa_num(number_format($a / $b * 100, $digits, '٫', '')) : number_format($a / $b * 100, $digits)) . '٪';
}

/**
 * Plain-language A/B verdict (two-proportion z-test on click-through).
 */
function kmpr_am_verdict($s, $prices)
{
    $r = array();
    foreach (array('A', 'B') as $v) {
        $r[$v] = array('i' => 0, 'c' => 0, 's' => 0);
        foreach (array('hub', 'over') as $sf) {
            foreach (array('i', 'c', 's') as $a) {
                $r[$v][$a] += (int) ($s["pass.$sf.$v.$a"] ?? 0);
            }
        }
    }
    $min = 30;
    if ($r['A']['i'] < $min || $r['B']['i'] < $min) {
        return array('tone' => 'wait', 'text' => 'هنوز داده کافی نیست: برای مقایسه منصفانه هر نسخه حداقل ' . kmpr_am_num($min) . ' نمایش لازم دارد (الان A: ' . kmpr_am_num($r['A']['i']) . '، B: ' . kmpr_am_num($r['B']['i']) . ').', 'rows' => $r);
    }
    $ca = $r['A']['c'] / $r['A']['i'];
    $cb = $r['B']['c'] / $r['B']['i'];
    $pool = ($r['A']['c'] + $r['B']['c']) / ($r['A']['i'] + $r['B']['i']);
    $se   = sqrt(max(1e-12, $pool * (1 - $pool) * (1 / $r['A']['i'] + 1 / $r['B']['i'])));
    $z    = ($cb - $ca) / $se;
    $sure = abs($z) >= 1.96 ? 'این تفاوت از نظر آماری قابل اتکاست.' : 'هنوز قطعی نیست؛ اجازه دهید داده بیشتری جمع شود.';
    if ($ca == 0 && $cb == 0) {
        return array('tone' => 'wait', 'text' => 'هیچ‌کدام از دو نسخه هنوز کلیک نخورده است.', 'rows' => $r);
    }
    $hi   = $cb >= $ca ? 'B' : 'A';
    $lo   = $hi === 'A' ? 'B' : 'A';
    $chi  = max($ca, $cb);
    $clo  = min($ca, $cb);
    $diff = $clo > 0 ? round(($chi - $clo) / $clo * 100) : null;
    if ($diff !== null && $diff < 10) {
        $cheap = $prices['A'] <= $prices['B'] ? 'A' : 'B';
        $other = $cheap === 'A' ? 'B' : 'A';
        $text  = 'دو قیمت تقریباً یک‌اندازه کلیک خورده‌اند (اختلاف ' . kmpr_am_num($diff) . '٪). یعنی قیمت بالاتر (نسخه ' . $other . ') چیزی از تقاضا کم نکرده است.';
    } else {
        $text = 'نسخه ' . $hi . ' (' . kmpr_am_num($prices[$hi]) . ' تومان) ' . ($diff === null ? 'کلیک گرفته ولی نسخه ' . $lo . ' هنوز هیچ' : 'با ' . kmpr_am_num($diff) . '٪ بیشتر از نسخه ' . $lo . ' کلیک شده است') . '.';
    }
    // Revenue proxy: signups per 1000 impressions × price.
    $rpm = array();
    foreach (array('A', 'B') as $v) {
        $rpm[$v] = $r[$v]['s'] / $r[$v]['i'] * 1000 * $prices[$v];
    }
    if ($rpm['A'] + $rpm['B'] > 0) {
        $best  = $rpm['B'] >= $rpm['A'] ? 'B' : 'A';
        $text .= ' از نظر درآمد بالقوه (پیش‌ثبت‌نام × قیمت) نسخه ' . $best . ' جلوتر است: حدود ' . kmpr_am_num($rpm[$best]) . ' تومان به ازای هر ۱۰۰۰ نمایش.';
    }
    return array('tone' => abs($z) >= 1.96 ? 'win' : 'lean', 'text' => $text . ' ' . $sure, 'rows' => $r);
}

function kmpr_am_admin_page()
{
    if (!current_user_can('manage_options')) {
        return;
    }
    $range  = (int) ($_GET['range'] ?? 30); // phpcs:ignore
    $range  = in_array($range, array(7, 30, 90), true) ? $range : 30;
    $s      = kmpr_am_sum($range);
    $prices = kmpr_am_prices();
    $n      = function ($k) use ($s) {
        return (int) ($s[$k] ?? 0);
    };
    $msg     = sanitize_key($_GET['am_msg'] ?? ''); // phpcs:ignore
    $verdict = kmpr_am_verdict($s, $prices);
    $total_signups = (int) (wp_count_posts(KMPR_AM_CPT)->private ?? 0);
    $surf    = array('hub' => 'هاب گیم‌سنتر', 'over' => 'پایان بازی');

    // Plays per game (own counters) + all-time plays from the games module when available.
    $games   = get_posts(array('post_type' => 'game', 'post_status' => 'publish', 'posts_per_page' => 100, 'orderby' => 'menu_order title', 'order' => 'ASC'));
    $gstats  = function_exists('kmpr_games_stats') ? kmpr_games_stats() : array('games' => array());
    $s7      = kmpr_am_sum(7);
    $weeks   = $range / 7;
    $plays_r = 0;
    $plays_7 = 0;
    foreach ($games as $g) {
        $plays_r += (int) ($s['pl.' . $g->ID] ?? 0);
        $plays_7 += (int) ($s7['pl.' . $g->ID] ?? 0);
    }
    $sp_i = 0;
    $sp_c = 0;
    foreach ($s as $k => $v) {
        if (preg_match('/^sp\.\d+\.(i|c)$/', $k, $m)) {
            ${'sp_' . $m[1]} += (int) $v;
        }
    }
    $over_imp = $n('sup.over.i') + $n('adv.i') + $n('pass.over.A.i') + $n('pass.over.B.i');
    ?>
    <div class="wrap kmpr-am-admin">
        <style>
            .kmpr-am-admin{--a:#d946ef;--b:#f59e0b}
            .kmpr-am-admin .am-head{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px;margin:12px 0 16px}
            .kmpr-am-admin .am-range a{display:inline-block;padding:6px 14px;border:1px solid #c3c4c7;border-radius:999px;text-decoration:none;margin-inline-start:4px;background:#fff}
            .kmpr-am-admin .am-range a.is-on{background:#1d2327;color:#fff;border-color:#1d2327}
            .kmpr-am-admin .am-kpis{display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:12px;margin:0 0 18px}
            .kmpr-am-admin .am-kpi{background:#fff;border:1px solid #dcdcde;border-radius:12px;padding:14px 16px}
            .kmpr-am-admin .am-kpi b{display:block;font-size:24px;line-height:1.3;margin-top:4px}
            .kmpr-am-admin .am-kpi span{color:#50575e}
            .kmpr-am-admin .am-box{background:#fff;border:1px solid #dcdcde;border-radius:12px;padding:16px 18px;margin:0 0 18px}
            .kmpr-am-admin .am-box h2{margin:0 0 10px;font-size:16px}
            .kmpr-am-admin .am-verdict{border-inline-start:4px solid #8c8f94;padding:10px 14px;background:#f6f7f7;border-radius:8px;font-size:14px;line-height:2}
            .kmpr-am-admin .am-verdict.win{border-color:#00a32a;background:#edfaef}
            .kmpr-am-admin .am-verdict.lean{border-color:var(--b);background:#fcf9e8}
            .kmpr-am-admin table.am-t{border-collapse:collapse;width:100%}
            .kmpr-am-admin table.am-t th,.kmpr-am-admin table.am-t td{padding:8px 10px;border-bottom:1px solid #f0f0f1;text-align:start}
            .kmpr-am-admin table.am-t th{color:#50575e;font-weight:600}
            .kmpr-am-admin .am-tag{display:inline-block;min-width:22px;text-align:center;padding:1px 6px;border-radius:6px;color:#fff;font-weight:700}
            .kmpr-am-admin .am-tag.A{background:var(--a)}.kmpr-am-admin .am-tag.B{background:var(--b)}
            .kmpr-am-admin .am-grid2{display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:18px}
            .kmpr-am-admin .am-kit{width:100%;min-height:210px;font-family:inherit;line-height:1.9}
            .kmpr-am-admin .am-muted{color:#646970}
            .kmpr-am-admin .am-scroll{overflow-x:auto}
        </style>
        <h1>درآمد و تست تقاضای گیم‌سنتر</h1>
        <?php if ($msg === 'reset') : ?><div class="notice notice-success is-dismissible"><p>آمار صفر شد.</p></div><?php endif; ?>
        <?php if ($msg === 'deleted') : ?><div class="notice notice-success is-dismissible"><p>حذف شد.</p></div><?php endif; ?>

        <div class="am-head">
            <p class="am-muted" style="margin:0">آمار بازدید ادمین‌ها و ربات‌ها شمرده نمی‌شود. داده‌ها به‌صورت روزانه و حداکثر <?= esc_html(kmpr_am_num(KMPR_AM_KEEP)); ?> روز نگه داشته می‌شوند. <a href="<?= esc_url(admin_url('admin.php?page=kmpr-games-settings')); ?>">تنظیمات و متن‌ها</a></p>
            <nav class="am-range" aria-label="بازه زمانی">
                <?php foreach (array(7, 30, 90) as $d) : ?>
                    <a class="<?= $d === $range ? 'is-on' : ''; ?>" href="<?= esc_url(kmpr_am_admin_url(array('range' => $d))); ?>"<?= $d === $range ? ' aria-current="page"' : ''; ?>><?= esc_html(kmpr_am_num($d)); ?> روز</a>
                <?php endforeach; ?>
            </nav>
        </div>

        <div class="am-kpis">
            <div class="am-kpi"><span>دست بازی (<?= esc_html(kmpr_am_num($range)); ?> روز)</span><b><?= esc_html(kmpr_am_num($plays_r)); ?></b></div>
            <div class="am-kpi"><span>نمایش پاس</span><b><?= esc_html(kmpr_am_num($n('pass.hub.A.i') + $n('pass.hub.B.i') + $n('pass.over.A.i') + $n('pass.over.B.i'))); ?></b></div>
            <div class="am-kpi"><span>کلیک «خرید پاس»</span><b><?= esc_html(kmpr_am_num($n('pass.hub.A.c') + $n('pass.hub.B.c') + $n('pass.over.A.c') + $n('pass.over.B.c'))); ?></b></div>
            <div class="am-kpi"><span>پیش‌ثبت‌نام (در بازه / کل)</span><b><?= esc_html(kmpr_am_num($n('pass.hub.A.s') + $n('pass.hub.B.s') + $n('pass.over.A.s') + $n('pass.over.B.s'))); ?> / <?= esc_html(kmpr_am_num($total_signups)); ?></b></div>
            <div class="am-kpi"><span>کلیک حمایت</span><b><?= esc_html(kmpr_am_num($n('sup.hub.c') + $n('sup.over.c') + $n('sup.side.c'))); ?></b></div>
            <div class="am-kpi"><span>کلیک بازی تبلیغاتی</span><b><?= esc_html(kmpr_am_num($n('adv.c'))); ?></b></div>
        </div>

        <div class="am-box">
            <h2>نتیجه تست قیمت</h2>
            <p class="am-verdict <?= esc_attr($verdict['tone']); ?>"><?= esc_html($verdict['text']); ?></p>
            <div class="am-scroll">
                <table class="am-t">
                    <thead><tr><th>نسخه</th><th>محل</th><th>قیمت</th><th>نمایش</th><th>کلیک</th><th>پیش‌ثبت‌نام</th><th>نرخ کلیک</th><th>تبدیل کلیک ← ثبت</th><th>تبدیل کل</th></tr></thead>
                    <tbody>
                    <?php foreach (array('A', 'B') as $v) :
                        foreach ($surf as $sk => $sl) :
                            $i = $n("pass.$sk.$v.i");
                            $c = $n("pass.$sk.$v.c");
                            $g = $n("pass.$sk.$v.s"); ?>
                            <tr><td><span class="am-tag <?= esc_attr($v); ?>"><?= esc_html($v); ?></span></td><td><?= esc_html($sl); ?></td><td><?= esc_html(kmpr_am_num($prices[$v])); ?></td><td><?= esc_html(kmpr_am_num($i)); ?></td><td><?= esc_html(kmpr_am_num($c)); ?></td><td><?= esc_html(kmpr_am_num($g)); ?></td><td><?= esc_html(kmpr_am_pct($c, $i)); ?></td><td><?= esc_html(kmpr_am_pct($g, $c)); ?></td><td><?= esc_html(kmpr_am_pct($g, $i)); ?></td></tr>
                        <?php endforeach;
                        $t = $verdict['rows'][$v]; ?>
                        <tr style="background:#f6f7f7;font-weight:600"><td><span class="am-tag <?= esc_attr($v); ?>"><?= esc_html($v); ?></span></td><td>جمع</td><td><?= esc_html(kmpr_am_num($prices[$v])); ?></td><td><?= esc_html(kmpr_am_num($t['i'])); ?></td><td><?= esc_html(kmpr_am_num($t['c'])); ?></td><td><?= esc_html(kmpr_am_num($t['s'])); ?></td><td><?= esc_html(kmpr_am_pct($t['c'], $t['i'])); ?></td><td><?= esc_html(kmpr_am_pct($t['s'], $t['c'])); ?></td><td><?= esc_html(kmpr_am_pct($t['s'], $t['i'])); ?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ((string) kmpr_am_opt('am_pay_url') !== '') : ?><p class="am-muted">«لینک پرداخت» فعال است؛ کلیک‌ها به صفحه پرداخت می‌روند و فرم پیش‌ثبت‌نام نمایش داده نمی‌شود.</p><?php endif; ?>
        </div>

        <div class="am-grid2">
            <div class="am-box">
                <h2>حمایت و بازی تبلیغاتی</h2>
                <table class="am-t">
                    <thead><tr><th>بخش</th><th>نمایش</th><th>کلیک</th><th>نرخ کلیک</th></tr></thead>
                    <tbody>
                    <?php foreach (array('sup.hub' => 'حمایت · هاب', 'sup.side' => 'حمایت · ستون کناری', 'sup.over' => 'حمایت · پایان بازی', 'adv' => 'بازی تبلیغاتی · پایان بازی', 'spc' => '«جای برند شما» · بازی‌های بدون اسپانسر') as $k => $label) : ?>
                        <tr><td><?= esc_html($label); ?></td><td><?= esc_html(kmpr_am_num($n("$k.i"))); ?></td><td><?= esc_html(kmpr_am_num($n("$k.c"))); ?></td><td><?= esc_html(kmpr_am_pct($n("$k.c"), $n("$k.i"))); ?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if (!kmpr_am_support_on()) : ?><p class="am-muted">دکمه حمایت خاموش است یا لینک آن خالی است.</p><?php endif; ?>
            </div>

            <div class="am-box">
                <h2>مدیا کیت (برای فرستادن به اسپانسر)</h2>
                <?php
                $avg_week = $weeks > 0 ? round($plays_r / $weeks) : 0;
                $ctr      = $sp_i ? kmpr_am_pct($sp_c, $sp_i) : '—';
                $kit      = "گیم‌سنتر kmpr.ir — مدیا کیت\n"
                    . '• دست بازی در ۷ روز گذشته: ' . kmpr_am_num($plays_7) . "\n"
                    . '• میانگین هفتگی (' . kmpr_am_num($range) . ' روز اخیر): ' . kmpr_am_num($avg_week) . " دست\n"
                    . '• تعداد بازی‌ها: ' . kmpr_am_num(count($games)) . "\n"
                    . '• نمایش در صفحه پایان بازی (' . kmpr_am_num($range) . ' روز): ' . kmpr_am_num($over_imp) . "\n"
                    . ($sp_i ? '• نرخ کلیک لوگوی حامی: ' . $ctr . "\n" : '')
                    . "• جایگاه: لوگو و لینک «حامی این بازی» در صفحه شروع و پایان هر دست\n"
                    . '• مخاطب: برنامه‌نویس‌ها، طراحان و علاقه‌مندان تکنولوژی';
                ?>
                <p class="am-muted">اعداد به‌صورت خودکار از شمارنده‌ها محاسبه می‌شوند؛ متن زیر را کپی کنید.</p>
                <textarea class="am-kit" readonly onclick="this.select()"><?= esc_textarea($kit); ?></textarea>
            </div>
        </div>

        <div class="am-box">
            <h2>بازی‌ها و اسپانسرها</h2>
            <div class="am-scroll">
                <table class="am-t">
                    <thead><tr><th>بازی</th><th>حامی فعال</th><th>دست بازی (<?= esc_html(kmpr_am_num($range)); ?> روز)</th><th>میانگین در هفته</th><th>۷ روز اخیر</th><th>کل دست‌ها (ماژول بازی)</th><th>نمایش حامی</th><th>کلیک حامی</th><th>نرخ کلیک</th></tr></thead>
                    <tbody>
                    <?php foreach ($games as $g) :
                        $sp = kmpr_am_sponsor($g->ID);
                        $pl = $n('pl.' . $g->ID);
                        $si = $n('sp.' . $g->ID . '.i');
                        $sc = $n('sp.' . $g->ID . '.c'); ?>
                        <tr>
                            <td><a href="<?= esc_url(get_edit_post_link($g->ID)); ?>"><?= esc_html(get_the_title($g)); ?></a></td>
                            <td><?= $sp ? esc_html($sp['name']) : '<span class="am-muted">—</span>'; ?></td>
                            <td><?= esc_html(kmpr_am_num($pl)); ?></td>
                            <td><?= esc_html(kmpr_am_num($weeks > 0 ? round($pl / $weeks) : 0)); ?></td>
                            <td><?= esc_html(kmpr_am_num((int) ($s7['pl.' . $g->ID] ?? 0))); ?></td>
                            <td><?= isset($gstats['games'][$g->ID]) ? esc_html(kmpr_am_num($gstats['games'][$g->ID]['plays'])) : '<span class="am-muted">—</span>'; ?></td>
                            <td><?= esc_html(kmpr_am_num($si)); ?></td>
                            <td><?= esc_html(kmpr_am_num($sc)); ?></td>
                            <td><?= esc_html(kmpr_am_pct($sc, $si)); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="am-box">
            <div style="display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:10px">
                <h2 style="margin:0">پیش‌ثبت‌نام‌های پاس آرکید</h2>
                <a class="button" href="<?= esc_url(wp_nonce_url(kmpr_am_admin_url(array('am_action' => 'export')), 'kmpr_am_export')); ?>">خروجی CSV</a>
            </div>
            <form method="get">
                <input type="hidden" name="post_type" value="game">
                <input type="hidden" name="page" value="<?= esc_attr(KMPR_AM_PAGE); ?>">
                <input type="hidden" name="range" value="<?= (int) $range; ?>">
                <?php
                kmpr_am_define_table();
                $table = new KMPR_AM_Signups_Table();
                $table->prepare_items();
                $table->display();
                ?>
            </form>
        </div>

        <form method="post" action="<?= esc_url(kmpr_am_admin_url()); ?>" onsubmit="return confirm('همه شمارنده‌ها (نه پیش‌ثبت‌نام‌ها) صفر شوند؟')">
            <?php wp_nonce_field('kmpr_am_reset'); ?>
            <input type="hidden" name="am_action" value="reset">
            <button type="submit" class="button button-link-delete">ریست آمار</button>
            <span class="am-muted">فقط شمارنده‌ها پاک می‌شوند؛ لیست پیش‌ثبت‌نام‌ها باقی می‌ماند.</span>
        </form>
    </div>
    <?php
}
