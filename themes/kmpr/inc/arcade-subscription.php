<?php
/**
 * Arcade subscription («پاس آرکید») — real plans on top of the arcade-monetize surfaces.
 *
 * - ONE master switch (`sub_on`, «بازی‌ها > تنظیمات آرشیو > اشتراک»). Off = every pass/subscription surface disappears.
 * - Games stay free for everyone; the pass only adds perks (badge, no upsells, gift days, supporter status).
 * - Identity = the arcade player id (uuid in localStorage, same one the leaderboard uses). No login needed.
 * - Payment: (a) manual — request code + Telegram/WhatsApp/card-to-card, owner approves in «بازی‌ها > اشتراک‌ها»;
 *           (b) Zarinpal gateway (v4) — auto-activates after verify. Either can be switched off.
 * - Storage: members in one non-autoloaded option; requests are a private CPT (tiny, no extra tables).
 * WooCommerce is intentionally not required (not installed, heavy for a 1 GB host); a request/approval flow covers it.
 *
 * @package KMPR
 */

if (!defined('ABSPATH')) {
    exit;
}

const KMPR_SUB_MEMBERS = 'kmpr_sub_members';
const KMPR_SUB_CPT     = 'arcade_sub_req';
const KMPR_SUB_PAGE    = 'kmpr-arcade-subs';

/* ---------------------------------------------------------------- settings */

function kmpr_sub_defaults()
{
    return array(
        'sub_plans'       => "1|49000|ماهانه|0\n3|129000|فصلی|7\n12|449000|سالانه|45",
        'sub_perks'       => "نشان «مشترک ویژه» کنار نامت در جدول برترین‌ها\nبدون بنر و پیشنهاد خرید وسط بازی\nروزهای هدیه در پلن‌های فصلی و سالانه\nحمایت مستقیم از ساخت بازی‌های بیشتر",
        'sub_tg'          => '',
        'sub_wa'          => '',
        'sub_card'        => '',
        'sub_card_name'   => '',
        'sub_manual_note' => 'بعد از ثبت درخواست، کد پیگیری و رسید پرداخت را در تلگرام یا واتساپ بفرست؛ حداکثر تا چند ساعت اشتراکت فعال می‌شود.',
        'sub_zp_merchant' => '',
    );
}

function kmpr_sub_opt($name)
{
    $d = kmpr_sub_defaults();
    $v = function_exists('get_field') ? get_field($name, 'option') : null;
    return ($v === null || $v === false || $v === '') ? ($d[$name] ?? '') : $v;
}

/** true_false with its own default (an unchecked box must stay off). */
function kmpr_sub_flag($name, $default)
{
    static $c = array();
    if (!isset($c[$name])) {
        $v         = function_exists('get_field') ? get_field($name, 'option') : null;
        $c[$name] = ($v === null || $v === '') ? $default : (bool) $v;
    }
    return $c[$name];
}

/** Master switch. */
function kmpr_sub_enabled()
{
    return kmpr_sub_flag('sub_on', true);
}

function kmpr_sub_manual_on()
{
    return kmpr_sub_enabled() && kmpr_sub_flag('sub_pay_manual', true);
}

/** Online payment provider: 'woocommerce' (any WC gateway plugin) or 'zarinpal' (built-in, no plugin). */
function kmpr_sub_provider()
{
    $p = (string) kmpr_sub_opt('sub_provider');
    if ($p === 'woocommerce' && kmpr_sub_wc_ready()) {
        return 'woocommerce';
    }
    return trim((string) kmpr_sub_opt('sub_zp_merchant')) !== '' ? 'zarinpal' : '';
}

function kmpr_sub_wc_ready()
{
    return class_exists('WooCommerce') && function_exists('wc_create_order');
}

function kmpr_sub_gateway_on()
{
    return kmpr_sub_enabled() && kmpr_sub_flag('sub_pay_gateway', false) && kmpr_sub_provider() !== '';
}

/** [ ['months'=>1,'price'=>49000,'label'=>'ماهانه','bonus'=>0], … ] */
function kmpr_sub_plans()
{
    $out = array();
    foreach (preg_split('/\R/u', (string) kmpr_sub_opt('sub_plans')) as $line) {
        $p = array_map('trim', explode('|', function_exists('kmpr_latin_digits') ? kmpr_latin_digits($line) : $line));
        if (count($p) < 2 || (int) $p[0] < 1 || (int) $p[1] < 1) {
            continue;
        }
        $out[] = array('months' => min(36, (int) $p[0]), 'price' => (int) $p[1], 'label' => $p[2] ?? ((int) $p[0] . ' ماهه'), 'bonus' => max(0, (int) ($p[3] ?? 0)));
    }
    return $out ?: array(array('months' => 1, 'price' => 49000, 'label' => 'ماهانه', 'bonus' => 0));
}

add_action('acf/include_fields', function () {
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }
    $d   = kmpr_sub_defaults();
    $f   = function ($name, $label, $type = 'text', $extra = array()) use ($d) {
        return array_merge(array('key' => 'field_kmpr_sub_' . $name, 'name' => $name, 'label' => $label, 'type' => $type, 'default_value' => $d[$name] ?? ''), $extra);
    };
    $on  = function ($name, $label, $def, $w = 33) {
        return array('key' => 'field_kmpr_sub_' . $name, 'name' => $name, 'label' => $label, 'type' => 'true_false', 'ui' => 1, 'default_value' => $def ? 1 : 0, 'wrapper' => array('width' => $w));
    };
    $tab = function ($k, $l) {
        return array('key' => 'field_kmpr_sub_tab_' . $k, 'label' => $l, 'type' => 'tab', 'placement' => 'top');
    };
    acf_add_local_field_group(array(
        'key'        => 'group_kmpr_arcade_sub',
        'title'      => 'اشتراک «پاس آرکید»',
        'fields'     => array(
            $tab('main', 'کلید و پلن‌ها'),
            $on('sub_on', 'کلید اصلی اشتراک (خاموش = همه بخش‌های اشتراک و پاس پنهان می‌شوند)', true, 100),
            $f('sub_plans', 'پلن‌ها (هر خط: تعداد ماه | قیمت تومان | عنوان | روز هدیه)', 'textarea', array('rows' => 4, 'new_lines' => '', 'instructions' => 'مثال: 3|129000|فصلی|7 — بازی‌ها برای همه رایگان می‌مانند؛ اشتراک فقط امکانات اضافه می‌دهد.')),
            $f('sub_perks', 'مزایای اشتراک (هر خط یکی — فقط چیزهایی که واقعاً فعال است)', 'textarea', array('rows' => 5, 'new_lines' => '')),
            $tab('manual', 'پرداخت دستی'),
            $on('sub_pay_manual', 'درخواست + تأیید دستی (تلگرام / واتساپ / کارت‌به‌کارت)', true, 100),
            $f('sub_tg', 'آیدی تلگرام (بدون @)', 'text', array('wrapper' => array('width' => 50))),
            $f('sub_wa', 'شماره واتساپ (مثل 989121234567)', 'text', array('wrapper' => array('width' => 50))),
            $f('sub_card', 'شماره کارت (اختیاری)', 'text', array('wrapper' => array('width' => 50))),
            $f('sub_card_name', 'به نام', 'text', array('wrapper' => array('width' => 50))),
            $f('sub_manual_note', 'راهنمای پرداخت دستی', 'textarea', array('rows' => 2, 'new_lines' => '')),
            $tab('gw', 'درگاه پرداخت'),
            array('key' => 'field_kmpr_sub_gw_msg', 'label' => '', 'name' => '', 'type' => 'message', 'message' => 'حالت تمام‌خودکار: پرداخت ← تأیید بانک ← فعال‌سازی فوری اشتراک ← پیام تلگرام به شما. اگر خریدار قبل از برگشت از بانک صفحه را ببندد، هر ساعت پرداخت‌های تأییدنشده دوباره بررسی و خودکار فعال می‌شوند. برای کاملاً خودکار، «پرداخت دستی» را در تب قبل خاموش کنید.'),
            $on('sub_pay_gateway', 'پرداخت آنلاین (فعال‌سازی خودکار)', false, 50),
            $f('sub_provider', 'روش پرداخت آنلاین', 'select', array('choices' => array('zarinpal' => 'زرین‌پال داخلی (بدون افزونه)', 'woocommerce' => 'ووکامرس (هر درگاه ایرانی که روی ووکامرس نصب شده)'), 'default_value' => 'zarinpal', 'wrapper' => array('width' => 50), 'instructions' => 'ووکامرس فقط وقتی کار می‌کند که افزونه ووکامرس و افزونه درگاه نصب و فعال باشند؛ محصولات پلن‌ها خودکار ساخته می‌شوند.')),
            $on('sub_zp_sandbox', 'حالت آزمایشی (sandbox)', false, 50),
            $f('sub_zp_merchant', 'مرچنت کد زرین‌پال', 'text', array('instructions' => 'تا وقتی خالی است دکمه درگاه نمایش داده نمی‌شود.')),
        ),
        'location'   => array(array(array('param' => 'options_page', 'operator' => '==', 'value' => 'kmpr-games-settings'))),
        'menu_order' => 19,
    ));
});

/* ---------------------------------------------------------------- members */

function kmpr_sub_members()
{
    $m = get_option(KMPR_SUB_MEMBERS, array());
    return is_array($m) ? $m : array();
}

function kmpr_sub_valid_pid($pid)
{
    $pid = strtolower(trim((string) $pid));
    return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $pid) ? $pid : '';
}

function kmpr_sub_until($pid)
{
    $m = kmpr_sub_members();
    return (int) ($m[strtolower((string) $pid)]['until'] ?? 0);
}

function kmpr_sub_active($pid)
{
    return $pid !== '' && kmpr_sub_until($pid) > time();
}

/** Extend from max(now, current expiry). */
function kmpr_sub_grant($pid, $months, $bonus_days = 0, $nick = '')
{
    $pid = kmpr_sub_valid_pid($pid);
    if (!$pid || $months < 1) {
        return 0;
    }
    $m    = kmpr_sub_members();
    $row  = $m[$pid] ?? array('since' => time());
    $from = max(time(), (int) ($row['until'] ?? 0));
    $row['until'] = $from + ($months * 30 + (int) $bonus_days) * DAY_IN_SECONDS;
    $row['nick']  = $nick !== '' ? $nick : ($row['nick'] ?? '');
    $m[$pid]      = $row;
    update_option(KMPR_SUB_MEMBERS, $m, false);
    delete_transient('kmpr_am_supporters');
    return $row['until'];
}

function kmpr_sub_ungrant($pid, $months, $bonus_days = 0)
{
    $m   = kmpr_sub_members();
    $pid = strtolower((string) $pid);
    if (isset($m[$pid])) {
        $m[$pid]['until'] = max(time() - 1, (int) $m[$pid]['until'] - ($months * 30 + (int) $bonus_days) * DAY_IN_SECONDS);
        update_option(KMPR_SUB_MEMBERS, $m, false);
    }
}

function kmpr_sub_revoke($pid)
{
    $m = kmpr_sub_members();
    unset($m[strtolower((string) $pid)]);
    update_option(KMPR_SUB_MEMBERS, $m, false);
}

function kmpr_sub_date($ts)
{
    if (function_exists('shamsi_date')) {
        return shamsi_date('j F Y', $ts);
    }
    return wp_date('Y/m/d', $ts);
}

/* ---------------------------------------------------------------- requests (CPT) */

add_action('init', function () {
    register_post_type(KMPR_SUB_CPT, array('public' => false, 'show_ui' => false, 'rewrite' => false, 'query_var' => false, 'supports' => array('title'), 'label' => 'درخواست اشتراک'));
});

function kmpr_sub_req_meta($id)
{
    $g = function ($k) use ($id) {
        return get_post_meta($id, '_s_' . $k, true);
    };
    return array('id' => (int) $id, 'code' => get_post_field('post_title', $id), 'player' => $g('player'), 'nick' => $g('nick'), 'contact' => $g('contact'), 'months' => (int) $g('months'), 'bonus' => (int) $g('bonus'), 'price' => (int) $g('price'), 'label' => $g('label'), 'method' => $g('method'), 'status' => $g('status'), 'ref' => $g('ref'), 'date' => get_post_time('U', true, $id));
}

function kmpr_sub_set_status($id, $status, $ref = '')
{
    $r = kmpr_sub_req_meta($id);
    if (!$r['player'] || in_array($r['status'], array('approved', 'paid'), true)) {
        return false;
    }
    update_post_meta($id, '_s_status', $status);
    if ($ref) {
        update_post_meta($id, '_s_ref', $ref);
    }
    if ($status === 'approved' || $status === 'paid') {
        kmpr_sub_grant($r['player'], $r['months'], $r['bonus'], $r['nick']);
    }
    return true;
}

/* ---------------------------------------------------------------- REST */

add_action('rest_api_init', function () {
    if (!kmpr_sub_enabled()) {
        return;
    }
    $open = '__return_true';
    register_rest_route('kmpr/v1', '/arcade/sub/order', array('methods' => 'POST', 'callback' => 'kmpr_sub_rest_order', 'permission_callback' => $open));
    register_rest_route('kmpr/v1', '/arcade/sub/me', array('methods' => 'GET', 'callback' => 'kmpr_sub_rest_me', 'permission_callback' => $open));
    register_rest_route('kmpr/v1', '/arcade/sub/back', array('methods' => 'GET', 'callback' => 'kmpr_sub_rest_back', 'permission_callback' => $open));
});

function kmpr_sub_rest_me(WP_REST_Request $req)
{
    $pid   = kmpr_sub_valid_pid($req->get_param('player_id'));
    $until = $pid ? kmpr_sub_until($pid) : 0;
    $res   = array('expired' => $until > 0 && $until <= time(), 'active' => $until > time(), 'days' => $until > time() ? (int) ceil(($until - time()) / DAY_IN_SECONDS) : 0, 'until' => $until > time() ? kmpr_sub_date($until) : '');
    if ($pid && !$res['active']) {
        // Latest pending manual request, so the dialog can show «در انتظار تأیید».
        $q = get_posts(array('post_type' => KMPR_SUB_CPT, 'post_status' => 'private', 'numberposts' => 1, 'meta_query' => array(array('key' => '_s_player', 'value' => $pid), array('key' => '_s_status', 'value' => 'pending')), 'fields' => 'ids'));
        $res['pending'] = $q ? get_post_field('post_title', $q[0]) : '';
    }
    $r = new WP_REST_Response($res);
    $r->header('Cache-Control', 'no-store');
    return $r;
}

function kmpr_sub_rest_order(WP_REST_Request $req)
{
    if (function_exists('kmpr_am_throttle') && !kmpr_am_throttle('sub', 6, HOUR_IN_SECONDS)) {
        return new WP_Error('slow', 'درخواست‌ها زیاد بود؛ کمی بعد دوباره امتحان کن.', array('status' => 429));
    }
    if ((string) $req->get_param('website') !== '') {
        return array('ok' => true, 'code' => 'KM-0000');
    }
    $pid    = kmpr_sub_valid_pid($req->get_param('player_id'));
    $plans  = kmpr_sub_plans();
    $i      = (int) $req->get_param('plan');
    $method = $req->get_param('method') === 'gateway' ? 'gateway' : 'manual';
    if (!$pid || !isset($plans[$i])) {
        return new WP_Error('bad', 'اطلاعات ناقص است؛ صفحه را تازه کن.', array('status' => 400));
    }
    if (($method === 'gateway' && !kmpr_sub_gateway_on()) || ($method === 'manual' && !kmpr_sub_manual_on())) {
        return new WP_Error('off', 'این روش پرداخت فعلاً فعال نیست.', array('status' => 400));
    }
    $plan    = $plans[$i];
    $nick    = mb_substr(sanitize_text_field((string) $req->get_param('nickname')), 0, 32);
    $contact = mb_substr(sanitize_text_field((string) $req->get_param('contact')), 0, 40);
    if ($method === 'manual' && $contact === '') {
        return new WP_Error('contact', 'برای تأیید دستی، شماره یا آیدی تلگرامت را بنویس.', array('status' => 400));
    }
    $code = 'KM-' . wp_rand(1000, 9999);
    $id   = wp_insert_post(array('post_type' => KMPR_SUB_CPT, 'post_status' => 'private', 'post_title' => $code), true);
    if (is_wp_error($id)) {
        return new WP_Error('db', 'ثبت نشد؛ دوباره امتحان کن.', array('status' => 500));
    }
    foreach (array('player' => $pid, 'nick' => $nick, 'contact' => $contact, 'months' => $plan['months'], 'bonus' => $plan['bonus'], 'price' => $plan['price'], 'label' => $plan['label'], 'method' => $method, 'status' => $method === 'manual' ? 'pending' : 'started') as $k => $v) {
        update_post_meta($id, '_s_' . $k, $v);
    }

    if ($method === 'gateway') {
        $pay = kmpr_sub_provider() === 'woocommerce' ? kmpr_sub_wc_checkout($id, $plan, $nick, $contact) : kmpr_sub_zp_request($id, $plan);
        if (is_wp_error($pay)) {
            return $pay;
        }
        return array('ok' => true, 'redirect' => $pay);
    }

    kmpr_sub_notify($id);
    $msg   = "سلام، درخواست اشتراک پاس آرکید دارم.\nکد پیگیری: {$code}\nپلن: {$plan['label']} (" . number_format($plan['price']) . " تومان)\nنام: {$nick}";
    $tg    = trim((string) kmpr_sub_opt('sub_tg'), "@ \t");
    $wa    = preg_replace('/\D+/', '', (string) kmpr_sub_opt('sub_wa'));
    return array(
        'ok'    => true,
        'code'  => $code,
        'tg'    => $tg ? 'https://t.me/' . rawurlencode($tg) : '',
        'wa'    => $wa ? 'https://wa.me/' . $wa . '?text=' . rawurlencode($msg) : '',
        'msg'   => $msg,
        'card'  => (string) kmpr_sub_opt('sub_card'),
        'cardn' => (string) kmpr_sub_opt('sub_card_name'),
    );
}

/* ---------- Zarinpal v4 ---------- */
function kmpr_sub_zp_base()
{
    return kmpr_sub_flag('sub_zp_sandbox', false) ? 'https://sandbox.zarinpal.com/pg/' : 'https://payment.zarinpal.com/pg/';
}

function kmpr_sub_zp_request($id, $plan)
{
    $res = wp_remote_post(kmpr_sub_zp_base() . 'v4/payment/request.json', array(
        'timeout' => 15,
        'headers' => array('Content-Type' => 'application/json', 'Accept' => 'application/json'),
        'body'    => wp_json_encode(array(
            'merchant_id'  => trim((string) kmpr_sub_opt('sub_zp_merchant')),
            'amount'       => $plan['price'] * 10, // toman → rial
            'callback_url' => add_query_arg('r', $id, rest_url('kmpr/v1/arcade/sub/back')),
            'description'  => 'اشتراک پاس آرکید — ' . $plan['label'],
        )),
    ));
    $body = json_decode(wp_remote_retrieve_body($res), true);
    $auth = $body['data']['authority'] ?? '';
    if (is_wp_error($res) || (int) ($body['data']['code'] ?? 0) !== 100 || !$auth) {
        return new WP_Error('gw', 'اتصال به درگاه برقرار نشد؛ از روش پرداخت دستی استفاده کن.', array('status' => 502));
    }
    update_post_meta($id, '_s_auth', $auth);
    return kmpr_sub_zp_base() . 'StartPay/' . rawurlencode($auth);
}

function kmpr_sub_rest_back(WP_REST_Request $req)
{
    $id   = (int) $req->get_param('r');
    $auth = (string) $req->get_param('Authority');
    $ok   = false;
    if ($id && get_post_type($id) === KMPR_SUB_CPT && $auth !== '' && hash_equals((string) get_post_meta($id, '_s_auth', true), $auth)) {
        $r = kmpr_sub_req_meta($id);
        if ($r['status'] === 'paid') {
            $ok = true;
        } elseif ($req->get_param('Status') === 'OK') {
            $ok = kmpr_sub_zp_verify($id);
        } else {
            update_post_meta($id, '_s_status', 'failed');
        }
    }
    wp_safe_redirect(add_query_arg(array_filter(array('sub' => $ok ? 'ok' : 'fail', 'ref' => $ok && $id ? (string) get_post_meta($id, '_s_ref', true) : '')), get_post_type_archive_link('game')));
    exit;
}

/** Verify a Zarinpal authority once; idempotent (a paid request is never granted twice). */
function kmpr_sub_zp_verify($id)
{
    $r    = kmpr_sub_req_meta($id);
    $auth = (string) get_post_meta($id, '_s_auth', true);
    if ($r['status'] === 'paid' || $r['status'] === 'approved') {
        return true;
    }
    if ($auth === '' || !trim((string) kmpr_sub_opt('sub_zp_merchant'))) {
        return false;
    }
    $res  = wp_remote_post(kmpr_sub_zp_base() . 'v4/payment/verify.json', array(
        'timeout' => 15,
        'headers' => array('Content-Type' => 'application/json', 'Accept' => 'application/json'),
        'body'    => wp_json_encode(array('merchant_id' => trim((string) kmpr_sub_opt('sub_zp_merchant')), 'amount' => $r['price'] * 10, 'authority' => $auth)),
    ));
    if (is_wp_error($res)) {
        return false; // network: keep status, the hourly reconcile retries
    }
    $body = json_decode(wp_remote_retrieve_body($res), true);
    $code = (int) ($body['data']['code'] ?? 0);
    if ($code === 100 || $code === 101) {
        kmpr_sub_set_status($id, 'paid', (string) ($body['data']['ref_id'] ?? ''));
        kmpr_sub_notify($id);
        return true;
    }
    update_post_meta($id, '_s_status', 'failed');
    return false;
}

/* ---------- hourly reconcile: payments whose buyer closed the tab before the bank redirect ---------- */
add_action('init', function () {
    if (!wp_next_scheduled('kmpr_sub_reconcile')) {
        wp_schedule_event(time() + 300, 'hourly', 'kmpr_sub_reconcile');
    }
});
add_action('kmpr_sub_reconcile', function () {
    if (!kmpr_sub_enabled() || kmpr_sub_provider() !== 'zarinpal') {
        return;
    }
    $ids = get_posts(array(
        'post_type'   => KMPR_SUB_CPT,
        'post_status' => 'private',
        'numberposts' => 30,
        'fields'      => 'ids',
        'date_query'  => array(array('after' => '26 hours ago')),
        'meta_query'  => array(array('key' => '_s_status', 'value' => array('started', 'failed'), 'compare' => 'IN'), array('key' => '_s_auth', 'compare' => 'EXISTS')),
    ));
    foreach ($ids as $id) {
        if (time() - (int) get_post_time('U', true, $id) > 10 * MINUTE_IN_SECONDS) {
            kmpr_sub_zp_verify($id);
        }
    }
});

/* ---------- WooCommerce bridge: any Iranian gateway plugin, fully automatic ---------- */

/** Hidden virtual product per plan (created on first use, kept in sync with the plan price). */
function kmpr_sub_wc_product($plan)
{
    $key   = 'kmpr_sub_wc_' . $plan['months'];
    $pid   = (int) get_option($key);
    $price = kmpr_sub_wc_amount($plan['price']);
    $prod  = $pid ? wc_get_product($pid) : null;
    if (!$prod) {
        $prod = new WC_Product_Simple();
        $prod->set_virtual(true);
        $prod->set_sold_individually(true);
        $prod->set_catalog_visibility('hidden');
        $prod->set_status('publish');
    }
    $prod->set_name('پاس آرکید — ' . $plan['label']);
    if ((string) $prod->get_regular_price() !== (string) $price) {
        $prod->set_regular_price((string) $price);
    }
    $id = $prod->save();
    update_option($key, $id, false);
    return $prod;
}

/** Plan prices are toman; a store in rial needs ×10. */
function kmpr_sub_wc_amount($toman)
{
    return function_exists('get_woocommerce_currency') && get_woocommerce_currency() === 'IRR' ? $toman * 10 : $toman;
}

function kmpr_sub_wc_checkout($id, $plan, $nick, $contact)
{
    try {
        $order = wc_create_order(array('status' => 'pending', 'created_via' => 'kmpr-arcade'));
        $order->add_product(kmpr_sub_wc_product($plan), 1);
        $order->set_billing_first_name($nick ?: 'بازیکن');
        $order->set_billing_last_name('گیم‌سنتر');
        $digits = preg_replace('/\D+/', '', function_exists('kmpr_latin_digits') ? kmpr_latin_digits($contact) : $contact);
        if (strlen($digits) >= 10) {
            $order->set_billing_phone($digits);
        }
        $order->update_meta_data('_kmpr_sub_req', $id);
        $order->calculate_totals();
        $order->save();
        update_post_meta($id, '_s_order', $order->get_id());
        return $order->get_checkout_payment_url();
    } catch (Throwable $e) {
        return new WP_Error('wc', 'ساخت سفارش ناموفق بود؛ کمی بعد دوباره امتحان کن.', array('status' => 500));
    }
}

function kmpr_sub_wc_req($order)
{
    $order = is_numeric($order) ? wc_get_order($order) : $order;
    $id    = $order ? (int) $order->get_meta('_kmpr_sub_req') : 0;
    return ($id && get_post_type($id) === KMPR_SUB_CPT) ? array($order, $id) : array(null, 0);
}

// Paid (any gateway) → activate once, close the order (virtual goods need no shipping).
function kmpr_sub_wc_paid($order_id)
{
    list($order, $id) = kmpr_sub_wc_req($order_id);
    if (!$id) {
        return;
    }
    if (kmpr_sub_set_status($id, 'paid', (string) ($order->get_transaction_id() ?: 'WC#' . $order->get_id()))) {
        kmpr_sub_notify($id);
    }
    if ($order->get_status() === 'processing') {
        $order->update_status('completed', 'اشتراک پاس آرکید خودکار فعال شد.');
    }
}
add_action('woocommerce_payment_complete', 'kmpr_sub_wc_paid');
add_action('woocommerce_order_status_processing', 'kmpr_sub_wc_paid');
add_action('woocommerce_order_status_completed', 'kmpr_sub_wc_paid');

// Refund / cancel after payment → take the time back.
function kmpr_sub_wc_reverse($order_id)
{
    list($order, $id) = kmpr_sub_wc_req($order_id);
    if (!$id) {
        return;
    }
    $r = kmpr_sub_req_meta($id);
    if ($r['status'] === 'paid') {
        kmpr_sub_ungrant($r['player'], $r['months'], $r['bonus']);
        update_post_meta($id, '_s_status', 'refunded');
    }
}
add_action('woocommerce_order_status_refunded', 'kmpr_sub_wc_reverse');
add_action('woocommerce_order_status_cancelled', 'kmpr_sub_wc_reverse');

// Guests pay with the order key; our orders carry no e-mail, so WC must not ask to «verify the e-mail».
add_filter('woocommerce_order_email_verification_required', function ($required, $order = null) {
    list($o, $id) = $order ? kmpr_sub_wc_req($order) : array(null, 0);
    return $id ? false : $required;
}, 10, 2);

// After the gateway, send the buyer back to the Game Center instead of the shop's thank-you page.
add_filter('woocommerce_get_return_url', function ($url, $order) {
    list($o, $id) = $order ? kmpr_sub_wc_req($order) : array(null, 0);
    return $id ? add_query_arg('sub', 'ok', get_post_type_archive_link('game')) : $url;
}, 10, 2);

function kmpr_sub_notify($id)
{
    if (!function_exists('kmpr_tg_send') || !function_exists('kmpr_tg_token') || !kmpr_tg_token()) {
        return;
    }
    $r = kmpr_sub_req_meta($id);
    $e = function ($v) {
        return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
    };
    $head = $r['status'] === 'paid' ? '✅ <b>اشتراک پرداخت شد (درگاه)</b>' : '🎟 <b>درخواست اشتراک — منتظر تأیید</b>';
    kmpr_tg_send(implode("\n", array($head, '', 'کد: ' . $e($r['code']), 'پلن: ' . $e($r['label']) . ' — ' . number_format($r['price']) . ' تومان', 'نام: ' . $e($r['nick'] ?: '—'), 'تماس: ' . $e($r['contact'] ?: '—'), '', 'تأیید: ' . admin_url('edit.php?post_type=game&page=' . KMPR_SUB_PAGE))));
}

/* ---------------------------------------------------------------- perks */

// Leaderboard badge.
add_filter('kmpr_game_player_badges', function ($badges, $row = null) {
    $pid = is_array($row) ? ($row['player_id'] ?? '') : (is_object($row) ? ($row->player_id ?? '') : '');
    if (kmpr_sub_enabled() && is_array($badges) && $pid && kmpr_sub_active(strtolower($pid))) {
        $badges[] = 'مشترک ویژه';
    }
    return $badges;
}, 9, 2);

/* ---------------------------------------------------------------- front-end */

add_action('wp_enqueue_scripts', function () {
    if (!kmpr_sub_enabled() || !function_exists('kmpr_am_on_games_page') || !kmpr_am_on_games_page()) {
        return;
    }
    $rel  = '/public/js/arcade-sub.js';
    $file = get_template_directory() . $rel;
    if (!file_exists($file)) {
        return;
    }
    wp_enqueue_script('kmpr-arcade-sub', get_template_directory_uri() . $rel, array(), (string) filemtime($file), array('in_footer' => true, 'strategy' => 'defer'));
    wp_add_inline_script('kmpr-arcade-sub', 'window.kmprSub=' . wp_json_encode(array('rest' => esc_url_raw(rest_url('kmpr/v1/arcade/sub'))), JSON_UNESCAPED_UNICODE) . ';', 'before');
}, 31);

function kmpr_sub_toman($n)
{
    return function_exists('kmpr_am_num') ? kmpr_am_num($n) : number_format($n);
}

add_action('wp_footer', function () {
    if (!kmpr_sub_enabled() || !function_exists('kmpr_am_on_games_page') || !kmpr_am_on_games_page()) {
        return;
    }
    $plans = kmpr_sub_plans();
    $perks = array_filter(array_map('trim', preg_split('/\R/u', (string) kmpr_sub_opt('sub_perks'))));
    $ic    = function ($n, $s = 18) {
        return function_exists('kmpr_am_icon') ? kmpr_am_icon($n, $s) : '';
    };
    $base  = $plans[0]['price'] / max(1, $plans[0]['months']);
    ?>
    <dialog class="am-modal sub-modal" data-sub-modal aria-labelledby="sub-title">
        <div class="am-modal__card sub-card">
            <button type="button" class="am-modal__x" data-sub-close aria-label="بستن"><?= $ic('close', 20); ?></button>
            <div data-sub-step="active" hidden>
                <span class="sub-ok"><?= $ic('crown', 30); ?></span>
                <h2 class="am-modal__title">پاس آرکید تو فعال است</h2>
                <p class="am-modal__text">اعتبار تا <b data-sub-until></b> (<span data-sub-days></span> روز). ممنون که حامی گیم‌سنتری! می‌توانی همین حالا تمدید کنی؛ روزها به انتهای اشتراک اضافه می‌شوند.</p>
                <p class="am-modal__text" data-sub-ref hidden></p>
                <div class="sub-card-no">کد بازیابی اشتراک (برای گوشی یا مرورگر جدید نگهش دار): <b dir="ltr" data-sub-pid style="word-break:break-all"></b> <button type="button" class="sub-mini" data-sub-copypid>کپی</button></div>
                <button type="button" class="am-btn am-btn--gold am-btn--block" data-sub-renew><span>تمدید اشتراک</span></button>
            </div>
            <form data-sub-step="form" class="sub-form" novalidate>
                <span class="sub-kicker"><?= $ic('crown', 16); ?> بازی‌ها برای همه رایگان می‌ماند</span>
                <h2 id="sub-title" class="am-modal__title"><?= esc_html(function_exists('kmpr_am_opt') ? kmpr_am_opt('am_pass_title') : 'پاس آرکید'); ?></h2>
                <?php if ($perks) : ?>
                    <ul class="sub-perks"><?php foreach ($perks as $p) : ?><li><?= $ic('check', 14); ?><span><?= esc_html($p); ?></span></li><?php endforeach; ?></ul>
                <?php endif; ?>
                <fieldset class="sub-plans">
                    <legend class="screen-reader-text">انتخاب پلن</legend>
                    <?php foreach ($plans as $i => $p) :
                        $per  = $p['price'] / $p['months'];
                        $save = $p['months'] > 1 && $base > $per ? round((1 - $per / $base) * 100) : 0; ?>
                        <label class="sub-plan">
                            <input type="radio" name="plan" value="<?= (int) $i; ?>"<?= $i === (count($plans) > 1 ? 1 : 0) ? ' checked' : ''; ?>>
                            <span class="sub-plan__name"><?= esc_html($p['label']); ?><?php if ($save) : ?> <em><?= esc_html(kmpr_sub_toman($save)); ?>٪ ارزان‌تر</em><?php endif; ?></span>
                            <span class="sub-plan__price"><b><?= esc_html(kmpr_sub_toman($p['price'])); ?></b> تومان</span>
                            <span class="sub-plan__meta"><?= esc_html(kmpr_sub_toman($p['months'] * 30)); ?> روز<?= $p['bonus'] ? ' + <strong>' . esc_html(kmpr_sub_toman($p['bonus'])) . ' روز هدیه</strong>' : ''; ?></span>
                        </label>
                    <?php endforeach; ?>
                </fieldset>
                <label class="am-field"><span>نام مستعار</span><input type="text" name="nickname" maxlength="32" autocomplete="nickname" placeholder="همان نام جدول برترین‌ها"></label>
                <label class="am-field"><span>موبایل یا آیدی تلگرام</span><input type="text" name="contact" maxlength="40" dir="ltr" autocomplete="tel" placeholder="09… یا @username"></label>
                <div class="am-hp" aria-hidden="true"><label>وب‌سایت <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
                <p class="am-form__status" data-sub-status role="status" aria-live="polite"></p>
                <div class="sub-pay">
                    <?php if (kmpr_sub_gateway_on()) : ?><button type="submit" name="method" value="gateway" class="am-btn am-btn--gold am-btn--block"><span>پرداخت آنلاین (درگاه)</span></button><?php endif; ?>
                    <?php if (kmpr_sub_manual_on()) : ?><button type="submit" name="method" value="manual" class="am-btn <?= kmpr_sub_gateway_on() ? 'am-btn--ghost' : 'am-btn--gold'; ?> am-btn--block"><span>درخواست و پرداخت از طریق تلگرام / واتساپ</span></button><?php endif; ?>
                </div>
                <details class="sub-restore">
                    <summary>قبلاً روی دستگاه دیگری اشتراک خریده‌ای؟</summary>
                    <div class="sub-restore__row"><input type="text" dir="ltr" data-sub-restore-in placeholder="کد بازیابی" aria-label="کد بازیابی اشتراک"><button type="button" class="sub-mini" data-sub-restore>بازیابی</button></div>
                </details>
                <p class="am-modal__note">اشتراک روی همین دستگاه/مرورگر (شناسه بازیکن) فعال می‌شود. <?= esc_html(kmpr_sub_opt('sub_manual_note')); ?></p>
            </form>
            <div data-sub-step="manual" hidden>
                <span class="sub-ok"><?= $ic('check', 30); ?></span>
                <h2 class="am-modal__title" tabindex="-1">درخواستت ثبت شد</h2>
                <p class="am-modal__text">کد پیگیری: <b class="sub-code" data-sub-code dir="ltr"></b></p>
                <div class="sub-card-no" data-sub-cardbox hidden>کارت به کارت: <b dir="ltr" data-sub-card></b> <small data-sub-cardn></small></div>
                <p class="am-modal__text"><?= esc_html(kmpr_sub_opt('sub_manual_note')); ?></p>
                <div class="sub-pay">
                    <a class="am-btn am-btn--gold am-btn--block" data-sub-tg target="_blank" rel="noopener" hidden><span>ارسال در تلگرام</span></a>
                    <a class="am-btn am-btn--ghost am-btn--block" data-sub-wa target="_blank" rel="noopener" hidden><span>ارسال در واتساپ</span></a>
                    <button type="button" class="am-btn am-btn--ghost am-btn--block" data-sub-copy><span>کپی متن درخواست</span></button>
                </div>
            </div>
        </div>
    </dialog>
    <?php
}, 6);

/* ---------------------------------------------------------------- admin: «اشتراک‌ها» */

add_action('admin_menu', function () {
    $pending = (int) (new WP_Query(array('post_type' => KMPR_SUB_CPT, 'post_status' => 'private', 'meta_key' => '_s_status', 'meta_value' => 'pending', 'fields' => 'ids', 'posts_per_page' => 1)))->found_posts;
    $label   = 'اشتراک‌ها' . ($pending ? ' <span class="awaiting-mod">' . (int) $pending . '</span>' : '');
    $hook    = add_submenu_page('edit.php?post_type=game', 'اشتراک‌ها', $label, 'manage_options', KMPR_SUB_PAGE, 'kmpr_sub_admin_page');
    if ($hook) {
        add_action('load-' . $hook, 'kmpr_sub_admin_actions');
    }
});

function kmpr_sub_admin_actions()
{
    if (!current_user_can('manage_options') || empty($_REQUEST['sub_do'])) {
        return;
    }
    check_admin_referer('kmpr_sub');
    $do  = sanitize_key($_REQUEST['sub_do']);
    $msg = 'done';
    if ($do === 'approve' || $do === 'reject') {
        $id = (int) ($_REQUEST['id'] ?? 0);
        if (get_post_type($id) === KMPR_SUB_CPT) {
            $do === 'approve' ? kmpr_sub_set_status($id, 'approved') : update_post_meta($id, '_s_status', 'rejected');
        }
    } elseif ($do === 'grant') {
        $ok  = kmpr_sub_grant(wp_unslash($_POST['player'] ?? ''), max(0, (int) ($_POST['months'] ?? 0)), max(0, (int) ($_POST['bonus'] ?? 0)), sanitize_text_field(wp_unslash($_POST['nick'] ?? '')));
        $msg = $ok ? 'done' : 'badpid';
    } elseif ($do === 'revoke') {
        kmpr_sub_revoke(sanitize_text_field(wp_unslash($_REQUEST['player'] ?? '')));
    }
    wp_safe_redirect(add_query_arg(array('post_type' => 'game', 'page' => KMPR_SUB_PAGE, 'msg' => $msg), admin_url('edit.php')));
    exit;
}

function kmpr_sub_admin_page()
{
    $url   = function ($args) {
        return wp_nonce_url(add_query_arg(array_merge(array('post_type' => 'game', 'page' => KMPR_SUB_PAGE), $args), admin_url('edit.php')), 'kmpr_sub');
    };
    $reqs  = get_posts(array('post_type' => KMPR_SUB_CPT, 'post_status' => 'private', 'numberposts' => 60));
    $mem   = kmpr_sub_members();
    uasort($mem, function ($a, $b) {
        return ($b['until'] ?? 0) <=> ($a['until'] ?? 0);
    });
    $st    = array('pending' => 'در انتظار تأیید', 'approved' => 'تأیید شد', 'paid' => 'پرداخت آنلاین', 'rejected' => 'رد شد', 'started' => 'رفته به درگاه', 'failed' => 'پرداخت ناموفق', 'refunded' => 'بازگشت وجه');
    // Revenue (all paid / approved requests; small private CPT, so a full scan is cheap).
    $rev   = array('all' => 0, 'month' => 0, 'n' => 0, 'plans' => array());
    $mstart = strtotime(wp_date('Y-m-01 00:00:00'));
    foreach (get_posts(array('post_type' => KMPR_SUB_CPT, 'post_status' => 'private', 'numberposts' => -1, 'fields' => 'ids', 'meta_query' => array(array('key' => '_s_status', 'value' => array('paid', 'approved'), 'compare' => 'IN')))) as $rid) {
        $rr = kmpr_sub_req_meta($rid);
        $rev['all'] += $rr['price'];
        $rev['n']++;
        $rev['plans'][$rr['label']] = ($rev['plans'][$rr['label']] ?? 0) + 1;
        if ($rr['date'] >= $mstart) {
            $rev['month'] += $rr['price'];
        }
    }
    $active = count(array_filter(kmpr_sub_members(), fn($m) => ($m['until'] ?? 0) > time()));
    $soon   = count(array_filter(kmpr_sub_members(), fn($m) => ($m['until'] ?? 0) > time() && $m['until'] - time() < 3 * DAY_IN_SECONDS));
    ?>
    <div class="wrap">
        <h1>اشتراک‌های پاس آرکید</h1>
        <?php if (!kmpr_sub_enabled()) : ?><div class="notice notice-warning"><p>کلید اصلی اشتراک خاموش است (بازی‌ها › تنظیمات آرشیو › اشتراک). هیچ بخش اشتراکی در سایت نمایش داده نمی‌شود.</p></div><?php endif; ?>
        <?php if (($_GET['msg'] ?? '') === 'badpid') : ?><div class="notice notice-error"><p>شناسه بازیکن معتبر نیست.</p></div><?php elseif (!empty($_GET['msg'])) : ?><div class="notice notice-success is-dismissible"><p>انجام شد.</p></div><?php endif; ?>

        <div style="display:flex;gap:12px;flex-wrap:wrap;margin:16px 0">
            <?php foreach (array(array('درآمد کل', number_format($rev['all']) . ' تومان'), array('درآمد این ماه', number_format($rev['month']) . ' تومان'), array('فروش موفق', $rev['n']), array('مشترک فعال', $active), array('انقضا تا ۳ روز', $soon)) as $card) : ?>
                <div style="background:#fff;border:1px solid #dcdcde;border-radius:10px;padding:12px 16px;min-width:150px"><div style="color:#646970;font-size:12px"><?= esc_html($card[0]); ?></div><div style="font-size:20px;font-weight:700;margin-top:4px"><?= esc_html((string) $card[1]); ?></div></div>
            <?php endforeach; ?>
        </div>
        <?php if ($rev['plans']) : ?><p>فروش بر اساس پلن: <?= esc_html(implode(' · ', array_map(fn($k, $v) => $k . ': ' . $v, array_keys($rev['plans']), $rev['plans']))); ?></p><?php endif; ?>
        <p>روش پرداخت آنلاین فعال: <b><?= esc_html(kmpr_sub_gateway_on() ? (kmpr_sub_provider() === 'woocommerce' ? 'ووکامرس' : 'زرین‌پال داخلی') : 'خاموش'); ?></b><?= kmpr_sub_manual_on() ? ' · پرداخت دستی هم روشن است' : ' · فقط خودکار'; ?></p>

        <h2>درخواست‌ها</h2>
        <table class="widefat striped">
            <thead><tr><th>کد</th><th>تاریخ</th><th>نام / تماس</th><th>پلن</th><th>روش</th><th>وضعیت</th><th></th></tr></thead>
            <tbody>
            <?php if (!$reqs) : ?><tr><td colspan="7">هنوز درخواستی ثبت نشده.</td></tr><?php endif; ?>
            <?php foreach ($reqs as $p) :
                $r = kmpr_sub_req_meta($p->ID); ?>
                <tr>
                    <td><b dir="ltr"><?= esc_html($r['code']); ?></b></td>
                    <td><?= esc_html(kmpr_sub_date($r['date'])); ?></td>
                    <td><?= esc_html($r['nick'] ?: '—'); ?><br><code dir="ltr"><?= esc_html($r['contact'] ?: '—'); ?></code></td>
                    <td><?= esc_html($r['label']); ?> · <?= esc_html(number_format($r['price'])); ?> تومان<?= $r['bonus'] ? ' · +' . (int) $r['bonus'] . ' روز' : ''; ?></td>
                    <td><?= $r['method'] === 'gateway' ? 'درگاه' . ($r['ref'] ? ' <code>' . esc_html($r['ref']) . '</code>' : '') : 'دستی'; ?></td>
                    <td><?= esc_html($st[$r['status']] ?? $r['status']); ?></td>
                    <td>
                        <?php if (in_array($r['status'], array('pending', 'rejected', 'failed', 'started'), true)) : ?>
                            <a class="button button-primary" href="<?= esc_url($url(array('sub_do' => 'approve', 'id' => $r['id']))); ?>">تأیید و فعال‌سازی <?= (int) $r['months']; ?> ماه</a>
                        <?php endif; ?>
                        <?php if ($r['status'] === 'pending') : ?>
                            <a class="button" href="<?= esc_url($url(array('sub_do' => 'reject', 'id' => $r['id']))); ?>">رد</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <h2 style="margin-top:2em">فعال‌سازی دستی</h2>
        <form method="post" action="<?= esc_url(admin_url('edit.php?post_type=game&page=' . KMPR_SUB_PAGE)); ?>">
            <?php wp_nonce_field('kmpr_sub'); ?><input type="hidden" name="sub_do" value="grant">
            <input type="text" name="player" placeholder="شناسه بازیکن (uuid)" dir="ltr" size="40" required>
            <input type="number" name="months" min="1" max="36" value="1" style="width:5em"> ماه
            <input type="number" name="bonus" min="0" max="365" value="0" style="width:5em"> روز هدیه
            <input type="text" name="nick" placeholder="نام (اختیاری)">
            <button class="button button-primary">فعال / تمدید کن</button>
        </form>

        <h2 style="margin-top:2em">مشترک‌ها (<?= count($mem); ?>)</h2>
        <table class="widefat striped">
            <thead><tr><th>نام</th><th>شناسه بازیکن</th><th>از</th><th>تا</th><th></th></tr></thead>
            <tbody>
            <?php if (!$mem) : ?><tr><td colspan="5">هنوز مشترکی نیست.</td></tr><?php endif; ?>
            <?php foreach ($mem as $pid => $m) : ?>
                <tr<?= ($m['until'] ?? 0) < time() ? ' style="opacity:.55"' : ''; ?>>
                    <td><?= esc_html($m['nick'] ?? '—'); ?></td>
                    <td><code dir="ltr"><?= esc_html($pid); ?></code></td>
                    <td><?= esc_html(kmpr_sub_date((int) ($m['since'] ?? time()))); ?></td>
                    <td><?= esc_html(kmpr_sub_date((int) ($m['until'] ?? 0))); ?><?= ($m['until'] ?? 0) < time() ? ' (منقضی)' : ''; ?></td>
                    <td><a class="button-link-delete" href="<?= esc_url($url(array('sub_do' => 'revoke', 'player' => $pid))); ?>" onclick="return confirm('لغو اشتراک؟')">لغو</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}
