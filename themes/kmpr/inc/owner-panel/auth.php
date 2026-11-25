<?php
/**
 * Owner panel — long-lived, per-device sign-in for administrators (the owner's phone / installed app).
 *
 * Why: a normal WordPress login without "remember me" sets *session* cookies (dropped whenever the mobile
 * browser or the home-screen app is killed — iOS standalone apps also have their own cookie jar) and a
 * server session of at most 48h. The panel then 404s (stealth) and there is no way back in from the app.
 *
 * How:
 *  - Trusted-device cookie `kmpr_pd_<hash>` = "uid.selector.validator" — httpOnly, Secure (on https),
 *    SameSite=Lax, path /panel/ only (never sent to REST, wp-admin or public pages). The DB keeps only
 *    sha256(validator) in user meta `kmpr_panel_devices`; sliding expiry (`kmpr_panel_session_days`,
 *    default 45, 0 = off). Validator rotates on every restore (the previous one stays valid until the
 *    device proves it received the new one, so a lost response never logs the owner out).
 *  - When a /panel/* request arrives without a WP login but with a valid device cookie, a fresh, short
 *    (2-day, persistent) WP session is created for that admin, tagged with the device selector so revoking
 *    the device kills its sessions too. Only users with KMPR_PANEL_CAP are ever restored.
 *  - Invalidated by: logout (from that device), revoke in panel → Settings, wp-admin profile
 *    "Log out everywhere else", password change (password fragment check), capability loss, expiry.
 *  - In-panel login form (inside the app scope, so cookies land in the app's jar) — shown only when the
 *    "door" is open: the installed app's start_url carries an HMAC door key, or the browser has the door
 *    hint cookie / a device cookie. Everyone else still gets the theme 404 (hidden login URL not revealed).
 *    Rate-limited per IP (5 / 15 min) and globally (30 / hour); failures fire `wp_login_failed`.
 *
 * Nothing here touches non-admin / visitor accounts.
 *
 * @package KMPR
 */

if (!defined('ABSPATH')) {
    exit;
}

const KMPR_PANEL_DEV_META  = 'kmpr_panel_devices';
const KMPR_PANEL_DAYS_OPT  = 'kmpr_panel_session_days';
const KMPR_PANEL_DEV_MAX   = 10;

function kmpr_panel_session_days()
{
    $d = get_option(KMPR_PANEL_DAYS_OPT, 45);
    return max(0, min(180, (int) $d));
}

function kmpr_panel_dev_cookie_name()
{
    return 'kmpr_pd_' . COOKIEHASH;
}

function kmpr_panel_hint_cookie_name()
{
    return 'kmpr_ph_' . COOKIEHASH;
}

/** Site-specific door key (HMAC of the auth salt; changing salts rotates it). */
function kmpr_panel_door_key()
{
    return substr(hash_hmac('sha256', 'kmpr-panel-door', wp_salt('logged_in')), 0, 24);
}

function kmpr_panel_setcookie($name, $value, $expires)
{
    if (headers_sent()) {
        return;
    }
    setcookie($name, $value, array(
        'expires'  => (int) $expires,
        'path'     => kmpr_panel_base(),
        'domain'   => COOKIE_DOMAIN ? COOKIE_DOMAIN : '',
        'secure'   => is_ssl(),
        'httponly' => true,
        'samesite' => 'Lax',
    ));
    if ($expires && $expires < time()) {
        unset($_COOKIE[$name]);
    } else {
        $_COOKIE[$name] = $value;
    }
}

/** Is the login form allowed to show for this (logged-out) request? */
function kmpr_panel_door_open()
{
    $key = kmpr_panel_door_key();
    $k   = isset($_GET['k']) ? sanitize_text_field(wp_unslash($_GET['k'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification
    if ($k !== '' && hash_equals($key, $k)) {
        kmpr_panel_set_hint();
        return true;
    }
    $hint = isset($_COOKIE[kmpr_panel_hint_cookie_name()]) ? (string) $_COOKIE[kmpr_panel_hint_cookie_name()] : '';
    if ($hint !== '' && hash_equals($key, $hint)) {
        return true;
    }
    return (bool) kmpr_panel_dev_parse();
}

function kmpr_panel_set_hint()
{
    $n = kmpr_panel_hint_cookie_name();
    if (!isset($_COOKIE[$n]) || !hash_equals(kmpr_panel_door_key(), (string) $_COOKIE[$n])) {
        kmpr_panel_setcookie($n, kmpr_panel_door_key(), time() + YEAR_IN_SECONDS);
    }
}

/* =========================================================================
 * Device records
 * ====================================================================== */

/** @return array{0:int,1:string,2:string}|null [uid, selector, validator] */
function kmpr_panel_dev_parse()
{
    $raw = isset($_COOKIE[kmpr_panel_dev_cookie_name()]) ? (string) $_COOKIE[kmpr_panel_dev_cookie_name()] : '';
    if (!preg_match('/^(\d{1,12})\.([a-f0-9]{16})\.([A-Za-z0-9]{43})$/', $raw, $m)) {
        return null;
    }
    return array((int) $m[1], $m[2], $m[3]);
}

function kmpr_panel_devices($uid)
{
    $all = get_user_meta($uid, KMPR_PANEL_DEV_META, true);
    return is_array($all) ? $all : array();
}

function kmpr_panel_devices_save($uid, $all)
{
    if ($all) {
        update_user_meta($uid, KMPR_PANEL_DEV_META, $all);
    } else {
        delete_user_meta($uid, KMPR_PANEL_DEV_META);
    }
}

function kmpr_panel_pass_frag($user)
{
    return substr(hash_hmac('sha256', (string) $user->user_pass, wp_salt('auth')), 0, 16);
}

function kmpr_panel_dev_hash($validator)
{
    return hash_hmac('sha256', $validator, wp_salt('secure_auth'));
}

function kmpr_panel_client_ua()
{
    return substr(sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'] ?? '')), 0, 200);
}

function kmpr_panel_client_ip()
{
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
}

/** Masked IP for display (never store the full address). */
function kmpr_panel_mask_ip($ip)
{
    if (strpos($ip, ':') !== false) {
        return implode(':', array_slice(explode(':', $ip), 0, 3)) . ':…';
    }
    $p = explode('.', $ip);
    return count($p) === 4 ? $p[0] . '.' . $p[1] . '.' . $p[2] . '.x' : '';
}

/** Short human label from a user-agent string. */
function kmpr_panel_ua_label($ua)
{
    $os = 'دستگاه';
    foreach (array('iPhone' => 'iPhone', 'iPad' => 'iPad', 'Android' => 'Android', 'Macintosh' => 'Mac', 'Windows' => 'Windows', 'Linux' => 'Linux') as $k => $v) {
        if (stripos($ua, $k) !== false) {
            $os = $v;
            break;
        }
    }
    $br = '';
    foreach (array('EdgA?/' => 'Edge', 'SamsungBrowser' => 'Samsung', 'Firefox|FxiOS' => 'Firefox', 'Chrome|CriOS' => 'Chrome', 'Safari' => 'Safari') as $re => $v) {
        if (preg_match('#' . $re . '#', $ua)) {
            $br = $v;
            break;
        }
    }
    return trim($os . ($br ? ' · ' . $br : ''));
}

/** Create a device token for $uid and send its cookie. Returns the selector. */
function kmpr_panel_dev_issue($uid, $selector = null)
{
    $days = kmpr_panel_session_days();
    $user = get_userdata($uid);
    if (!$days || !$user) {
        return '';
    }
    $all      = kmpr_panel_devices($uid);
    $selector = $selector ?: bin2hex(random_bytes(8));
    $val      = kmpr_panel_rand43();
    $now      = time();
    $all[$selector] = array(
        'h'  => kmpr_panel_dev_hash($val),
        'p'  => '',
        'c'  => $now,
        'u'  => $now,
        'ua' => kmpr_panel_client_ua(),
        'ip' => kmpr_panel_mask_ip(kmpr_panel_client_ip()),
        'pf' => kmpr_panel_pass_frag($user),
    );
    // Keep the newest KMPR_PANEL_DEV_MAX devices; drop expired ones.
    $all = array_filter($all, function ($d) use ($now, $days) {
        return ((int) ($d['u'] ?? 0)) + $days * DAY_IN_SECONDS > $now;
    });
    uasort($all, function ($a, $b) {
        return (int) $b['u'] - (int) $a['u'];
    });
    $all = array_slice($all, 0, KMPR_PANEL_DEV_MAX, true);
    kmpr_panel_devices_save($uid, $all);
    kmpr_panel_setcookie(kmpr_panel_dev_cookie_name(), $uid . '.' . $selector . '.' . $val, $now + $days * DAY_IN_SECONDS);
    kmpr_panel_set_hint();
    return $selector;
}

function kmpr_panel_rand43()
{
    $s = rtrim(strtr(base64_encode(random_bytes(32)), '+/', 'AB'), '=');
    return substr($s . str_repeat('A', 43), 0, 43);
}

/**
 * Validate the device cookie.
 *
 * @return array|false ['uid'=>, 'sel'=>, 'prev'=>bool]
 */
function kmpr_panel_dev_check()
{
    $days = kmpr_panel_session_days();
    $c    = kmpr_panel_dev_parse();
    if (!$days || !$c) {
        return false;
    }
    list($uid, $sel, $val) = $c;
    $user = get_userdata($uid);
    if (!$user || !user_can($user, KMPR_PANEL_CAP)) {
        return false;
    }
    $all = kmpr_panel_devices($uid);
    $d   = $all[$sel] ?? null;
    if (!$d || ((int) $d['u']) + $days * DAY_IN_SECONDS < time()) {
        return false;
    }
    if (!hash_equals((string) $d['pf'], kmpr_panel_pass_frag($user))) {
        return false; // password changed since this device was trusted
    }
    $hash = kmpr_panel_dev_hash($val);
    if (hash_equals((string) $d['h'], $hash)) {
        return array('uid' => $uid, 'sel' => $sel, 'prev' => false);
    }
    if (!empty($d['p']) && hash_equals((string) $d['p'], $hash)) {
        return array('uid' => $uid, 'sel' => $sel, 'prev' => true);
    }
    return false;
}

/** Rotate (or just slide) a validated device; sets the new cookie. */
function kmpr_panel_dev_refresh($chk, $rotate)
{
    $uid  = $chk['uid'];
    $sel  = $chk['sel'];
    $days = kmpr_panel_session_days();
    $all  = kmpr_panel_devices($uid);
    if (!isset($all[$sel])) {
        return;
    }
    $now = time();
    $d   = &$all[$sel];
    if ($rotate || $chk['prev']) {
        $val = kmpr_panel_rand43();
        // Keep the validator the browser currently holds valid until it presents the new one.
        $d['p'] = $chk['prev'] ? $d['p'] : $d['h'];
        $d['h'] = kmpr_panel_dev_hash($val);
        $c      = $uid . '.' . $sel . '.' . $val;
    } else {
        $d['p'] = ''; // the browser has the current validator → retire the old one
        $c      = (string) $_COOKIE[kmpr_panel_dev_cookie_name()];
    }
    $d['u']  = $now;
    $d['ua'] = kmpr_panel_client_ua();
    $d['ip'] = kmpr_panel_mask_ip(kmpr_panel_client_ip());
    unset($d);
    kmpr_panel_devices_save($uid, $all);
    kmpr_panel_setcookie(kmpr_panel_dev_cookie_name(), $c, $now + $days * DAY_IN_SECONDS);
}

/** Create a (persistent, $len-long) WP login session for $uid tagged with the device selector. */
function kmpr_panel_login_as($uid, $sel, $len = 2 * DAY_IN_SECONDS)
{
    $GLOBALS['kmpr_panel_attach_dev'] = $sel;
    $exp = function () use ($len) {
        return $len;
    };
    add_filter('auth_cookie_expiration', $exp, 99);
    wp_set_auth_cookie($uid, true, is_ssl());
    remove_filter('auth_cookie_expiration', $exp, 99);
    unset($GLOBALS['kmpr_panel_attach_dev']);
    wp_set_current_user($uid);
}

add_filter('attach_session_information', function ($info) {
    if (!empty($GLOBALS['kmpr_panel_attach_dev'])) {
        $info['kmpr_dev'] = (string) $GLOBALS['kmpr_panel_attach_dev'];
    }
    return $info;
});

// Make the just-issued login cookie visible to this request (nonce + session token in the shell).
add_action('set_logged_in_cookie', function ($cookie) {
    if (!empty($GLOBALS['kmpr_panel_attach_dev'])) {
        $_COOKIE[LOGGED_IN_COOKIE] = $cookie;
    }
});

/**
 * /panel/* request: restore a missing WP login from a trusted device, or slide an existing one.
 * Called by the router before the capability check.
 */
function kmpr_panel_dev_sync()
{
    if (!kmpr_panel_session_days()) {
        return;
    }
    if (!is_user_logged_in()) {
        $chk = kmpr_panel_dev_check();
        if ($chk) {
            kmpr_panel_login_as($chk['uid'], $chk['sel']);
            kmpr_panel_dev_refresh($chk, true);
        }
        return;
    }
    if (!kmpr_panel_can()) {
        return;
    }
    $uid = get_current_user_id();
    $chk = kmpr_panel_dev_check();
    if (!$chk || $chk['uid'] !== $uid) {
        // Logged in (e.g. via wp-login) but this browser isn't a trusted device yet → trust it, and tag
        // the current WP session so revoking the device logs it out too.
        $sel = kmpr_panel_dev_issue($uid);
        if ($sel) {
            kmpr_panel_tag_session($uid, wp_get_session_token(), $sel);
        }
        return;
    }
    $d = kmpr_panel_devices($uid)[$chk['sel']] ?? array();
    if ($chk['prev'] || !empty($d['p']) || time() - (int) ($d['u'] ?? 0) > 6 * HOUR_IN_SECONDS) {
        kmpr_panel_dev_refresh($chk, false);
    }
}

function kmpr_panel_tag_session($uid, $token, $sel)
{
    if (!$token) {
        return;
    }
    $m = WP_Session_Tokens::get_instance($uid);
    $s = $m->get($token);
    if ($s) {
        $s['kmpr_dev'] = $sel;
        $m->update($token, $s);
    }
}

/** Revoke a device and every WP session it created. */
function kmpr_panel_dev_revoke($uid, $sel)
{
    $all = kmpr_panel_devices($uid);
    unset($all[$sel]);
    kmpr_panel_devices_save($uid, $all);
    $sessions = get_user_meta($uid, 'session_tokens', true);
    if (is_array($sessions)) {
        $keep = array_filter($sessions, function ($s) use ($sel) {
            return ($s['kmpr_dev'] ?? '') !== $sel;
        });
        if (count($keep) !== count($sessions)) {
            $keep ? update_user_meta($uid, 'session_tokens', $keep) : delete_user_meta($uid, 'session_tokens');
        }
    }
}

/** Revoke every device of $uid except $keep_sel (and their sessions). */
function kmpr_panel_dev_revoke_all($uid, $keep_sel = '')
{
    foreach (array_keys(kmpr_panel_devices($uid)) as $sel) {
        if ($sel !== $keep_sel) {
            kmpr_panel_dev_revoke($uid, $sel);
        }
    }
}

/** Selector of the device making this request (if its cookie is valid for $uid). */
function kmpr_panel_current_device($uid)
{
    $chk = kmpr_panel_dev_check();
    if ($chk && $chk['uid'] === (int) $uid) {
        return $chk['sel'];
    }
    // REST calls don't carry the /panel/-scoped cookie → use the tag on the current WP session.
    if ((int) $uid === get_current_user_id() && wp_get_session_token()) {
        $s = WP_Session_Tokens::get_instance($uid)->get(wp_get_session_token());
        return (string) ($s['kmpr_dev'] ?? '');
    }
    return '';
}

/** Device list for the panel. */
function kmpr_panel_devices_public($uid)
{
    $days = kmpr_panel_session_days();
    $cur  = kmpr_panel_current_device($uid);
    $out  = array();
    foreach (kmpr_panel_devices($uid) as $sel => $d) {
        $exp = (int) $d['u'] + $days * DAY_IN_SECONDS;
        if ($days && $exp < time()) {
            continue;
        }
        $out[] = array(
            'id'      => $sel,
            'label'   => kmpr_panel_ua_label((string) ($d['ua'] ?? '')),
            'ip'      => (string) ($d['ip'] ?? ''),
            'created' => kmpr_panel_when((int) $d['c']),
            'used'    => kmpr_panel_when((int) $d['u']),
            'expires' => kmpr_panel_when($exp),
            'current' => $sel === $cur,
        );
    }
    usort($out, function ($a, $b) {
        return $b['used']['ts'] - $a['used']['ts'];
    });
    return $out;
}

/* =========================================================================
 * Invalidation hooks
 * ====================================================================== */

// Logging out on a device forgets that device (otherwise the panel would sign straight back in).
add_action('wp_logout', function ($user_id = 0) {
    $c = kmpr_panel_dev_parse();
    if ($c && (!$user_id || (int) $user_id === $c[0])) {
        kmpr_panel_dev_revoke($c[0], $c[1]);
    }
    if ($c) {
        kmpr_panel_setcookie(kmpr_panel_dev_cookie_name(), '', time() - YEAR_IN_SECONDS);
    }
});

// wp-admin → Profile → "Log Out Everywhere Else" / "Log out everywhere" (another user).
add_action('wp_ajax_destroy-sessions', function () {
    $uid = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0; // phpcs:ignore -- core verifies the nonce right after
    if (!$uid || !current_user_can('edit_user', $uid) || !check_ajax_referer('update-user_' . $uid, false, false)) {
        return;
    }
    if ($uid === get_current_user_id()) {
        // Only the browser doing this keeps its device (if any).
        $c = kmpr_panel_dev_parse();
        kmpr_panel_dev_revoke_all($uid, ($c && $c[0] === $uid) ? $c[1] : '');
    } else {
        kmpr_panel_dev_revoke_all($uid);
    }
}, 0);

// Show the trusted devices on the user's profile in wp-admin (read-only; revoke via "Log out everywhere").
add_action('show_user_profile', 'kmpr_panel_profile_devices');
add_action('edit_user_profile', 'kmpr_panel_profile_devices');
function kmpr_panel_profile_devices($user)
{
    if (!user_can($user, KMPR_PANEL_CAP) || !current_user_can('edit_user', $user->ID)) {
        return;
    }
    $list = kmpr_panel_devices_public($user->ID);
    echo '<h2>دستگاه‌های مورد اعتماد پنل مدیریت</h2><table class="form-table" role="presentation"><tr><th>دستگاه‌ها</th><td>';
    if (!$list) {
        echo '<p>هیچ دستگاهی ثبت نشده است.</p>';
    } else {
        echo '<ul style="margin:0">';
        foreach ($list as $d) {
            echo '<li>' . esc_html($d['label'] . ' · ' . $d['ip'] . ' · آخرین استفاده: ' . $d['used']['fa']) . '</li>';
        }
        echo '</ul>';
    }
    echo '<p class="description">مدت ماندگاری ورود: ' . esc_html(number_format_i18n(kmpr_panel_session_days())) . ' روز (قابل تغییر در پنل ← تنظیمات). دکمهٔ «خروج از همه‌جا» در پایین همین صفحه همهٔ دستگاه‌ها را هم باطل می‌کند.</p></td></tr></table>';
}

/* =========================================================================
 * In-panel login / logout
 * ====================================================================== */

function kmpr_panel_logout_url()
{
    return add_query_arg('_wpnonce', wp_create_nonce('kmpr_panel_logout'), kmpr_panel_url('logout'));
}

/** GET /panel/logout?_wpnonce= — logs out, forgets the device, returns to the panel login. */
function kmpr_panel_handle_logout()
{
    $n = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';
    if (is_user_logged_in() && wp_verify_nonce($n, 'kmpr_panel_logout')) {
        wp_logout();
    }
    wp_safe_redirect(kmpr_panel_base());
    exit;
}

function kmpr_panel_rl_key()
{
    return 'kmpr_pl_' . md5(kmpr_panel_client_ip());
}

function kmpr_panel_rl_blocked()
{
    return (int) get_transient(kmpr_panel_rl_key()) >= 5 || (int) get_transient('kmpr_pl_all') >= 30;
}

function kmpr_panel_rl_fail()
{
    set_transient(kmpr_panel_rl_key(), (int) get_transient(kmpr_panel_rl_key()) + 1, 15 * MINUTE_IN_SECONDS);
    set_transient('kmpr_pl_all', (int) get_transient('kmpr_pl_all') + 1, HOUR_IN_SECONDS);
}

/** POST /panel/login */
function kmpr_panel_handle_login()
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        wp_safe_redirect(kmpr_panel_base());
        exit;
    }
    // Same-origin form posts only.
    $origin = (string) ($_SERVER['HTTP_ORIGIN'] ?? '');
    if ($origin !== '' && $origin !== 'null' && wp_parse_url($origin, PHP_URL_HOST) !== wp_parse_url(home_url(), PHP_URL_HOST)) {
        kmpr_panel_login_page('درخواست نامعتبر است.', 400);
        exit;
    }
    if (kmpr_panel_rl_blocked()) {
        kmpr_panel_login_page('تلاش‌های ناموفق زیاد بود. چند دقیقه بعد دوباره امتحان کنید.', 429);
        exit;
    }
    $login = isset($_POST['log']) ? sanitize_user(wp_unslash($_POST['log'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification
    $pass  = isset($_POST['pwd']) ? (string) wp_unslash($_POST['pwd']) : ''; // phpcs:ignore
    $user  = ($login !== '' && $pass !== '') ? wp_authenticate($login, $pass) : new WP_Error('empty', '');
    if (is_wp_error($user) || !user_can($user, KMPR_PANEL_CAP)) {
        kmpr_panel_rl_fail();
        if (!is_wp_error($user)) {
            $user = new WP_Error('kmpr_panel_cap', '');
        }
        if ($login !== '') {
            do_action('wp_login_failed', $login, $user);
        }
        usleep(400000); // blunt timing / speed of guessing
        kmpr_panel_login_page('نام کاربری یا رمز عبور درست نیست.', 401);
        exit;
    }

    $days = kmpr_panel_session_days();
    if ($days) {
        $sel = bin2hex(random_bytes(8));
        kmpr_panel_login_as($user->ID, $sel);
        kmpr_panel_dev_issue($user->ID, $sel);
    } else {
        wp_set_auth_cookie($user->ID, true, is_ssl());
        wp_set_current_user($user->ID);
    }
    delete_transient(kmpr_panel_rl_key());
    do_action('wp_login', $user->user_login, $user);

    if (function_exists('kmpr_tg_token') && kmpr_tg_token() && function_exists('kmpr_tg_send')) {
        kmpr_tg_send("🔐 <b>ورود به پنل مدیریت</b>\n" . esc_html(kmpr_panel_ua_label(kmpr_panel_client_ua())) . ' · ' . esc_html(kmpr_panel_mask_ip(kmpr_panel_client_ip())) . "\nاگر شما نبودید، از پنل ← تنظیمات دستگاه را باطل و رمز را عوض کنید.");
    }
    wp_safe_redirect(kmpr_panel_base(), 303);
    exit;
}

/** Minimal self-contained login screen (inside the app scope). */
function kmpr_panel_login_page($error = '', $status = 200)
{
    nocache_headers();
    status_header($status);
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-store, private, max-age=0', true);
    header('X-Robots-Tag: noindex, nofollow, noarchive', true);
    header('Referrer-Policy: same-origin');
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    $font = get_template_directory_uri() . '/public/fonts/yekanBakh/';
    $days = kmpr_panel_session_days();
    ?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex, nofollow">
<meta name="theme-color" content="#020617">
<meta name="color-scheme" content="dark">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<title>ورود به پنل</title>
<link rel="manifest" href="<?= esc_url(kmpr_panel_url('manifest.webmanifest')); ?>" crossorigin="use-credentials">
<link rel="apple-touch-icon" href="<?= esc_url(get_template_directory_uri() . '/public/images/panel/apple-touch-icon.png'); ?>">
<style>
@font-face{font-family:yekanBakh;src:url(<?= esc_url($font . 'Yekan-Bakh-FaNum-Regular.woff2'); ?>) format("woff2");font-weight:400;font-display:swap}
@font-face{font-family:yekanBakh;src:url(<?= esc_url($font . 'Yekan_Bakh_FaNum_06_Bold.woff2'); ?>) format("woff2");font-weight:700;font-display:swap}
*{box-sizing:border-box}html,body{margin:0;min-height:100%;background:#020617;color:#e2e8f0;font-family:yekanBakh,system-ui,sans-serif}
body{display:grid;place-items:center;min-height:100vh;min-height:100dvh;padding:24px 16px;overflow-x:hidden}
main{width:100%;max-width:380px;background:#0f172a;border:1px solid #1e293b;border-radius:24px;padding:28px 22px}
.logo{width:48px;height:48px;border-radius:14px;background:#fbbf24;color:#0f172a;display:grid;place-items:center;font-weight:700;font-size:22px;margin:0 auto 12px}
h1{font-size:20px;text-align:center;margin:0 0 4px}p.sub{color:#94a3b8;text-align:center;margin:0 0 20px;font-size:14px;line-height:1.8}
label{display:block;font-size:14px;margin:0 0 6px;color:#cbd5e1}
input{width:100%;min-height:48px;border-radius:14px;border:1px solid #334155;background:#020617;color:#f1f5f9;padding:10px 14px;font:inherit;font-size:16px;margin-bottom:14px}
input:focus{outline:2px solid #fbbf24;outline-offset:1px}
button{width:100%;min-height:48px;border-radius:999px;border:0;background:#fbbf24;color:#0f172a;font:inherit;font-weight:700;font-size:16px;white-space:nowrap;padding:12px 24px;cursor:pointer}
.err{background:#450a0a;color:#fecaca;border:1px solid #7f1d1d;border-radius:14px;padding:10px 14px;font-size:14px;margin:0 0 14px}
.note{color:#94a3b8;font-size:13px;text-align:center;margin:16px 0 0;line-height:1.8}
</style>
</head>
<body>
<main>
    <div class="logo" aria-hidden="true">K</div>
    <h1>ورود به پنل مدیریت</h1>
    <p class="sub">برای ادامه وارد شوید.</p>
    <?php if ($error) : ?><p class="err" role="alert"><?= esc_html($error); ?></p><?php endif; ?>
    <form method="post" action="<?= esc_url(kmpr_panel_url('login')); ?>" autocomplete="on">
        <label for="op-log">نام کاربری یا ایمیل</label>
        <input id="op-log" name="log" type="text" autocomplete="username" autocapitalize="off" spellcheck="false" dir="ltr" required>
        <label for="op-pwd">رمز عبور</label>
        <input id="op-pwd" name="pwd" type="password" autocomplete="current-password" dir="ltr" required>
        <button type="submit">ورود</button>
    </form>
    <?php if ($days) : ?><p class="note">این دستگاه تا <?= esc_html(number_format_i18n($days)); ?> روز پس از آخرین استفاده وارد می‌ماند. با «خروج» یا باطل کردن دستگاه در تنظیمات پنل، ورود حذف می‌شود.</p><?php endif; ?>
</main>
</body>
</html>
    <?php
}
