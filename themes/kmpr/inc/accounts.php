<?php
/**
 * Visitor accounts: sign up / log in with a password, one identity on every device.
 *
 * - WordPress users (role subscriber, flagged with user meta `kmpr_acct`), never wp-admin, no admin bar.
 * - Own REST endpoints kmpr/v1/account/* (registered only when the master switch is on → otherwise 404).
 * - One small JSON per user (user meta `kmpr_acct_data`) holds the synced browser data; merge rules are
 *   monotonic (max / union) so any order of syncs converges and nothing is ever lowered.
 * - The arcade player uuid of the first device becomes the account's canonical player id (`kmpr_pid`);
 *   other devices' uuids are merged into it on login (scores, XP, subscription) and recorded as aliases.
 *
 * Design notes: inc/ACCOUNTS.md. Front-end: javascript/account/* → public/js/account.js + public/css/account.css.
 *
 * @package KMPR
 */

if (!defined('ABSPATH')) {
    exit;
}

const KMPR_ACCT_DATA_MAX = 65536;   // bytes of synced JSON per user
const KMPR_ACCT_ALIAS    = 'kmpr_acct_alias'; // option: old device uuid => user id

/* =========================================================================
 * Settings (ACF options page «حساب کاربران»)
 * ====================================================================== */

/** Read an ACF option without needing ACF loaded (ACF stores options as options_{name}). */
function kmpr_acct_opt($name, $default = '')
{
    $v = get_option('options_' . $name, null);
    return ($v === null || $v === '') ? $default : $v;
}

function kmpr_acct_on()
{
    return (bool) kmpr_acct_opt('acct_on', 0);
}

function kmpr_acct_session_days()
{
    return max(1, min(365, (int) kmpr_acct_opt('acct_session_days', 60)));
}

add_action('acf/init', function () {
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }
    if (function_exists('acf_add_options_sub_page')) {
        acf_add_options_sub_page(array(
            'page_title'  => 'حساب کاربری بازدیدکننده‌ها',
            'menu_title'  => 'حساب کاربران',
            'menu_slug'   => 'acf-options-accounts',
            'parent_slug' => 'acf-options-theme-settings',
        ));
    }
    acf_add_local_field_group(array(
        'key'      => 'group_kmpr_accounts',
        'title'    => 'حساب کاربران و دوستان',
        'fields'   => array(
            array('key' => 'field_kmpr_acct_on', 'name' => 'acct_on', 'label' => 'حساب کاربری فعال باشد', 'type' => 'true_false', 'ui' => 1, 'default_value' => 0,
                'instructions' => 'خاموش: هیچ دکمه یا صفحه‌ای نمایش داده نمی‌شود، /account/ و API حساب 404 می‌دهند و همه‌چیز مثل قبل ناشناس روی هر دستگاه کار می‌کند.'),
            array('key' => 'field_kmpr_acct_notify', 'name' => 'acct_notify_signup', 'label' => 'اعلان تلگرام برای ثبت‌نام جدید', 'type' => 'true_false', 'ui' => 1, 'default_value' => 1, 'wrapper' => array('width' => '33')),
            array('key' => 'field_kmpr_acct_days', 'name' => 'acct_session_days', 'label' => 'ماندگاری ورود با «مرا به خاطر بسپار» (روز)', 'type' => 'number', 'min' => 1, 'max' => 365, 'default_value' => 60, 'wrapper' => array('width' => '33')),
            array('key' => 'field_kmpr_friends_on', 'name' => 'friends_on', 'label' => 'فهرست دوستان فعال باشد', 'type' => 'true_false', 'ui' => 1, 'default_value' => 1, 'wrapper' => array('width' => '33'),
                'instructions' => 'فقط وقتی حساب کاربری روشن است کار می‌کند.'),
        ),
        'location' => array(array(array('param' => 'options_page', 'operator' => '==', 'value' => 'acf-options-accounts'))),
    ));
});

/* =========================================================================
 * Helpers
 * ====================================================================== */

/** A visitor account created by this module (and nothing more privileged). */
function kmpr_acct_is_visitor($user)
{
    $user = $user instanceof WP_User ? $user : get_userdata((int) $user);
    return $user && $user->exists() && get_user_meta($user->ID, 'kmpr_acct', true) && !user_can($user, 'edit_posts');
}

function kmpr_acct_ip_key()
{
    $ip = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '0.0.0.0'; // never trust X-Forwarded-For here
    return substr(hash_hmac('sha256', $ip, wp_salt('auth') . 'kmpr-acct'), 0, 24);
}

/**
 * Fixed-window counter. Returns the count after the hit (or the current count when $hit = false).
 */
function kmpr_acct_count($bucket, $key, $window, $hit = true)
{
    $k   = 'kmpr_ar_' . $bucket . '_' . substr(md5($key), 0, 20);
    $row = get_transient($k);
    $row = is_array($row) && ($row['e'] ?? 0) > time() ? $row : array('c' => 0, 'e' => time() + $window);
    if ($hit) {
        $row['c']++;
        set_transient($k, $row, max(1, $row['e'] - time()));
    }
    return (int) $row['c'];
}

function kmpr_acct_reset_count($bucket, $key)
{
    delete_transient('kmpr_ar_' . $bucket . '_' . substr(md5($key), 0, 20));
}

function kmpr_acct_err($message, $status = 400, $code = 'acct')
{
    return new WP_REST_Response(array('ok' => false, 'code' => $code, 'message' => $message), $status);
}

function kmpr_acct_clean_name($name)
{
    $name = wp_strip_all_tags((string) $name);
    $name = preg_replace('~https?://\S+|www\.\S+~i', '', $name);
    $name = trim(preg_replace('/\s+/u', ' ', sanitize_text_field($name)));
    return mb_substr($name, 0, 24);
}

/** Normalise the login identifier: email | Iranian mobile (09xxxxxxxxx) | username. Returns [type, value] or null. */
function kmpr_acct_parse_login($raw)
{
    $v = trim(function_exists('kmpr_latin_digits') ? kmpr_latin_digits((string) $raw) : (string) $raw);
    if ($v === '' || strlen($v) > 100) {
        return null;
    }
    if (strpos($v, '@') !== false) {
        $e = sanitize_email($v);
        return is_email($e) ? array('email', strtolower($e)) : null;
    }
    $digits = preg_replace('/[\s\-()]/', '', $v);
    if (preg_match('/^(?:\+98|0098|98|0)?(9\d{9})$/', $digits, $m)) {
        return array('mobile', '0' . $m[1]);
    }
    $u = strtolower($v);
    return preg_match('/^[a-z][a-z0-9_.]{2,29}$/', $u) ? array('username', $u) : null;
}

function kmpr_acct_find_user($parsed)
{
    if (!$parsed) {
        return null;
    }
    $u = $parsed[0] === 'email' ? get_user_by('email', $parsed[1]) : get_user_by('login', $parsed[1]);
    return $u ?: null;
}

/** Password policy: 8–128 chars, not trivially weak, not the identifier itself. */
function kmpr_acct_password_problem($pw, $ident = '')
{
    $len = mb_strlen($pw);
    if ($len < 8) {
        return 'رمز عبور باید دست‌کم ۸ کاراکتر باشد.';
    }
    if ($len > 128) {
        return 'رمز عبور خیلی طولانی است.';
    }
    $low = strtolower($pw);
    if (count(array_unique(preg_split('//u', $pw, -1, PREG_SPLIT_NO_EMPTY))) < 4 || ($ident !== '' && strpos($low, strtolower($ident)) !== false)
        || in_array($low, array('12345678', '123456789', '1234567890', 'password', 'password1', 'qwertyui', 'qwerty123', '11111111', '00000000', 'iloveyou', 'abcd1234', 'a1234567', '87654321', 'asdfghjk'), true)) {
        return 'این رمز عبور خیلی ساده است؛ ترکیب حرف و عدد طولانی‌تری انتخاب کن.';
    }
    return '';
}

function kmpr_acct_uuid_ok($pid)
{
    return is_string($pid) && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $pid);
}

/** Who owns a player uuid (canonical or alias)? 0 = nobody. */
function kmpr_acct_pid_owner($pid)
{
    $alias = get_option(KMPR_ACCT_ALIAS, array());
    if (is_array($alias) && isset($alias[$pid])) {
        return (int) $alias[$pid];
    }
    $ids = get_users(array('meta_key' => 'kmpr_pid', 'meta_value' => $pid, 'number' => 1, 'fields' => 'ID', 'count_total' => false));
    return $ids ? (int) $ids[0] : 0;
}

function kmpr_acct_pid($uid)
{
    $pid = (string) get_user_meta($uid, 'kmpr_pid', true);
    return kmpr_acct_uuid_ok($pid) ? $pid : '';
}

/** Public, non-reversible id (same as leaderboards). */
function kmpr_acct_public_id($pid)
{
    return $pid ? substr(hash('sha256', $pid), 0, 10) : '';
}

function kmpr_acct_initials($name)
{
    $name  = trim((string) $name);
    $parts = preg_split('/\s+/u', $name);
    $i     = mb_substr($parts[0] ?? '', 0, 1) . (isset($parts[1]) ? mb_substr($parts[1], 0, 1) : '');
    return $i !== '' ? $i : '؟';
}

function kmpr_acct_user_public($user)
{
    return array(
        'id'       => (int) $user->ID,
        'name'     => $user->display_name,
        'initials' => kmpr_acct_initials($user->display_name),
        'handle'   => (string) get_user_meta($user->ID, 'kmpr_handle', true),
        'findable' => get_user_meta($user->ID, 'kmpr_findable', true) !== '0',
        'hasEmail' => $user->user_email !== '',
        'login'    => $user->user_login,
    );
}

/* =========================================================================
 * Synced data: one small JSON per user, monotonic merge
 * ====================================================================== */

/** Which localStorage keys travel with the account (besides the arcade profile). */
function kmpr_acct_sync_keys()
{
    return apply_filters('kmpr_acct_sync_keys', array(
        'keys'     => array('kmpr_lessons_done'),
        'prefixes' => array('kmpr_kr_'),
    ));
}

function kmpr_acct_kv_allowed($k)
{
    if (!is_string($k) || strlen($k) > 64 || !preg_match('/^[a-z0-9_\-]+$/i', $k)) {
        return false;
    }
    $s = kmpr_acct_sync_keys();
    if (in_array($k, $s['keys'], true)) {
        return true;
    }
    foreach ($s['prefixes'] as $p) {
        if (strpos($k, $p) === 0) {
            return true;
        }
    }
    return false;
}

function kmpr_acct_clean_blob($in)
{
    $in  = is_array($in) ? $in : array();
    $out = array('v' => 1, 'games' => array(), 'kv' => array());
    foreach (array_slice((array) ($in['games'] ?? array()), 0, 200, true) as $eng => $g) {
        $eng = sanitize_key((string) $eng);
        if ($eng === '' || !is_array($g)) {
            continue;
        }
        $ach = array();
        foreach (array_slice((array) ($g['ach'] ?? array()), 0, 100) as $a) {
            $a = substr(sanitize_key((string) $a), 0, 40);
            if ($a !== '') {
                $ach[] = $a;
            }
        }
        $out['games'][$eng] = array(
            'best'  => max(0, (int) ($g['best'] ?? 0)),
            'plays' => max(0, (int) ($g['plays'] ?? 0)),
            'time'  => max(0, (int) ($g['time'] ?? 0)),
            'ach'   => array_values(array_unique($ach)),
            'last'  => max(0, (int) ($g['last'] ?? 0)),
            'at'    => max(0, (int) ($g['at'] ?? 0)),
        );
    }
    foreach (array_slice((array) ($in['kv'] ?? array()), 0, 100, true) as $k => $v) {
        if (kmpr_acct_kv_allowed($k) && is_scalar($v) && strlen((string) $v) <= 8000) {
            $out['kv'][$k] = (string) $v;
        }
    }
    if (!empty($in['avatar']) && is_array($in['avatar'])) {
        $av = array();
        foreach (array_slice($in['avatar'], 0, 6, true) as $k => $v) {
            if (is_scalar($v)) {
                $av[sanitize_key((string) $k)] = is_numeric($v) ? (int) $v : substr(sanitize_key((string) $v), 0, 24);
            }
        }
        $out['avatar'] = $av;
    }
    return $out;
}

/**
 * Generic monotonic merge on json_decode(..., false) values: numbers = max, lists = union, objects (maps) = per key,
 * booleans = or, anything else keeps $a. Objects stay objects, so maps with numeric keys ({"0":2}) survive.
 */
function kmpr_acct_merge_val($a, $b)
{
    if ($a === null) {
        return $b;
    }
    if ($b === null) {
        return $a;
    }
    if (is_bool($a) || is_bool($b)) {
        return (bool) $a || (bool) $b;
    }
    if ((is_int($a) || is_float($a)) && (is_int($b) || is_float($b))) {
        return max($a, $b);
    }
    if (is_array($a) && is_array($b)) {
        $seen = array();
        $out  = array();
        foreach (array_merge($a, $b) as $x) {
            $h = is_scalar($x) ? gettype($x) . ':' . $x : md5(wp_json_encode($x));
            if (!isset($seen[$h])) {
                $seen[$h] = 1;
                $out[]    = $x;
            }
        }
        return array_slice($out, 0, 500);
    }
    if (is_object($a) && is_object($b)) {
        $out = clone $a;
        foreach (get_object_vars($b) as $k => $v) {
            $out->$k = kmpr_acct_merge_val($a->$k ?? null, $v);
        }
        return $out;
    }
    return $a;
}

function kmpr_acct_merge_kv($a, $b)
{
    $da = json_decode($a, false);
    $db = json_decode($b, false);
    if (($da === null && $a !== 'null') || ($db === null && $b !== 'null')) {
        return $a !== '' ? $a : $b; // opaque strings: first (server) wins
    }
    $m = kmpr_acct_merge_val($da, $db);
    return is_string($m) ? $a : wp_json_encode($m);
}

function kmpr_acct_merge($a, $b)
{
    $a = kmpr_acct_clean_blob($a);
    $b = kmpr_acct_clean_blob($b);
    foreach ($b['games'] as $eng => $g) {
        $x = $a['games'][$eng] ?? null;
        if (!$x) {
            $a['games'][$eng] = $g;
            continue;
        }
        $a['games'][$eng] = array(
            'best'  => max($x['best'], $g['best']),
            'plays' => max($x['plays'], $g['plays']),
            'time'  => max($x['time'], $g['time']),
            'ach'   => array_values(array_unique(array_merge($x['ach'], $g['ach']))),
            'last'  => $g['at'] > $x['at'] ? $g['last'] : $x['last'],
            'at'    => max($x['at'], $g['at']),
        );
    }
    foreach ($b['kv'] as $k => $v) {
        $a['kv'][$k] = isset($a['kv'][$k]) ? kmpr_acct_merge_kv($a['kv'][$k], $v) : $v;
    }
    if (!empty($b['avatar'])) {
        $a['avatar'] = $b['avatar'];
    }
    return $a;
}

function kmpr_acct_get_data($uid)
{
    $raw = get_user_meta($uid, 'kmpr_acct_data', true);
    return kmpr_acct_clean_blob(is_string($raw) ? json_decode($raw, true) : array());
}

function kmpr_acct_save_data($uid, $blob)
{
    $json = wp_json_encode(kmpr_acct_clean_blob($blob));
    if (strlen($json) > KMPR_ACCT_DATA_MAX) {
        return false;
    }
    update_user_meta($uid, 'kmpr_acct_data', wp_slash($json));
    return true;
}

/* =========================================================================
 * Linking a device's anonymous player uuid into the account
 * ====================================================================== */

function kmpr_acct_link($uid, $device_pid)
{
    global $wpdb;
    $user       = get_userdata($uid);
    $device_pid = strtolower((string) $device_pid);
    $device_ok  = kmpr_acct_uuid_ok($device_pid);
    $acc        = kmpr_acct_pid($uid);

    if (!$acc) {
        $owner = $device_ok ? kmpr_acct_pid_owner($device_pid) : 0;
        $acc   = ($device_ok && (!$owner || $owner === $uid)) ? $device_pid : wp_generate_uuid4();
        update_user_meta($uid, 'kmpr_pid', $acc);
    } elseif ($device_ok && $device_pid !== $acc) {
        $owner = kmpr_acct_pid_owner($device_pid);
        if (!$owner || $owner === $uid) {
            kmpr_acct_move_player($device_pid, $acc, $uid);
        }
    }

    // Leaderboards / profile show the account's name; rows carry the user id.
    $name = mb_substr($user->display_name, 0, 24);
    if (function_exists('kmpr_games_table')) {
        $wpdb->query($wpdb->prepare('UPDATE ' . kmpr_games_table() . ' SET nickname = %s, user_id = %d WHERE player_id = %s', $name, $uid, $acc)); // phpcs:ignore
    }
    if (function_exists('kmpr_games_players_table')) {
        $wpdb->query($wpdb->prepare('UPDATE ' . kmpr_games_players_table() . ' SET nickname = %s, user_id = %d WHERE player_id = %s', $name, $uid, $acc)); // phpcs:ignore
    }
    return $acc;
}

/** Fold everything stored under $old into $new (scores = best, plays/XP summed, subscription kept). */
function kmpr_acct_move_player($old, $new, $uid)
{
    global $wpdb;
    $alias       = get_option(KMPR_ACCT_ALIAS, array());
    $alias       = is_array($alias) ? $alias : array();
    $alias[$old] = (int) $uid;
    update_option(KMPR_ACCT_ALIAS, $alias, false);

    if (function_exists('kmpr_games_table')) {
        $t = kmpr_games_table();
        $rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$t} WHERE player_id = %s", $old), ARRAY_A); // phpcs:ignore
        $ok   = true;
        foreach ((array) $rows as $r) {
            $done = $wpdb->query($wpdb->prepare(
                "INSERT INTO {$t} (game_id, player_id, nickname, score, duration_ms, plays, user_id, created_at, last_played_at) VALUES (%d, %s, %s, %d, %d, %d, %d, %s, %s)
                ON DUPLICATE KEY UPDATE
                    duration_ms = IF(VALUES(score) > score, VALUES(duration_ms), duration_ms),
                    created_at = IF(VALUES(score) > score, VALUES(created_at), created_at),
                    score = GREATEST(score, VALUES(score)),
                    plays = plays + VALUES(plays),
                    last_played_at = GREATEST(last_played_at, VALUES(last_played_at)),
                    user_id = VALUES(user_id)", // phpcs:ignore
                $r['game_id'], $new, $r['nickname'], $r['score'], $r['duration_ms'], $r['plays'], $uid, $r['created_at'], $r['last_played_at']
            ));
            $ok = $ok && $done !== false;
        }
        if (!$ok) {
            return; // keep the old rows (and no alias side effects beyond the map) rather than lose scores
        }
        $wpdb->delete($t, array('player_id' => $old));
        delete_transient('kmpr_games_top_players');
    }
    if (function_exists('kmpr_games_players_table')) {
        $pt  = kmpr_games_players_table();
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$pt} WHERE player_id = %s", $old), ARRAY_A); // phpcs:ignore
        if ($row) {
            $has = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$pt} WHERE player_id = %s", $new)); // phpcs:ignore
            if ($has) {
                $wpdb->query($wpdb->prepare(
                    "UPDATE {$pt} SET xp = xp + %d, wins = wins + %d, losses = losses + %d, draws = draws + %d, plays_total = plays_total + %d, ach_count = GREATEST(ach_count, %d) WHERE player_id = %s", // phpcs:ignore
                    $row['xp'], $row['wins'], $row['losses'], $row['draws'], $row['plays_total'], $row['ach_count'], $new
                ));
                $wpdb->delete($pt, array('player_id' => $old));
            } else {
                $wpdb->update($pt, array('player_id' => $new), array('player_id' => $old));
            }
        }
    }
    // Subscription: pending/paid requests follow the account; the members option is re-keyed by the filter below.
    $wpdb->query($wpdb->prepare("UPDATE {$wpdb->postmeta} SET meta_value = %s WHERE meta_key = '_s_player' AND meta_value = %s", $new, $old)); // phpcs:ignore
    $members = get_option('kmpr_sub_members', null);
    if (is_array($members) && isset($members[$old])) {
        update_option('kmpr_sub_members', $members, false);
    }
}

/**
 * Never lose a paid subscription: any write to the members list re-keys aliased device uuids to the account's
 * player id, adding the remaining time of both (also covers a Zarinpal payment that completes after linking).
 */
add_filter('pre_update_option_kmpr_sub_members', function ($value) {
    $alias = get_option(KMPR_ACCT_ALIAS, array());
    if (!is_array($value) || !is_array($alias) || !$alias) {
        return $value;
    }
    $now = time();
    foreach (array_keys($value) as $pid) {
        if (!isset($alias[$pid])) {
            continue;
        }
        $to = kmpr_acct_pid((int) $alias[$pid]);
        if (!$to || $to === $pid) {
            continue;
        }
        $old  = $value[$pid];
        $cur  = $value[$to] ?? null;
        $left = max(0, (int) ($old['until'] ?? 0) - $now);
        if ($cur) {
            $cur['until'] = max($now, (int) $cur['until']) + $left;
            $cur['since'] = min((int) ($cur['since'] ?? $now), (int) ($old['since'] ?? $now));
        } else {
            $cur = $old;
        }
        $value[$to] = $cur;
        unset($value[$pid]);
    }
    return $value;
});

/* =========================================================================
 * Logged-in game requests always act as the account's player
 * ====================================================================== */
add_filter('rest_request_before_callbacks', function ($response, $handler, $request) {
    if (!kmpr_acct_on() || !is_user_logged_in() || !preg_match('#^/kmpr/v1/(games|arcade)/#', $request->get_route())) {
        return $response;
    }
    $uid = get_current_user_id();
    $pid = kmpr_acct_pid($uid);
    if (!$pid) {
        return $response;
    }
    if ($request->get_param('player_id') !== null) {
        $request->set_param('player_id', $pid);
    }
    if ($request->get_param('nickname') !== null) {
        $request->set_param('nickname', mb_substr(wp_get_current_user()->display_name, 0, 24));
    }
    return $response;
}, 10, 3);

/* =========================================================================
 * Sessions, wp-admin lock-out, admin bar (visitor accounts only)
 * ====================================================================== */
add_filter('auth_cookie_expiration', function ($expiration, $user_id, $remember) {
    if (!kmpr_acct_is_visitor($user_id)) {
        return $expiration; // owner / editors keep their own policy
    }
    return $remember ? kmpr_acct_session_days() * DAY_IN_SECONDS : 2 * DAY_IN_SECONDS;
}, 20, 3);

add_action('admin_init', function () {
    if (wp_doing_ajax() || !is_user_logged_in() || !kmpr_acct_is_visitor(wp_get_current_user())) {
        return;
    }
    wp_safe_redirect(home_url('/account/'));
    exit;
}, 1);

add_filter('show_admin_bar', function ($show) {
    return (is_user_logged_in() && kmpr_acct_is_visitor(wp_get_current_user())) ? false : $show;
}, 20);

/* =========================================================================
 * REST API kmpr/v1/account/*
 * ====================================================================== */

/** CSRF guard for every account call: custom header (forces CORS preflight) + same-origin Origin/Referer. */
function kmpr_acct_guard(WP_REST_Request $req)
{
    if ($req->get_header('x_kmpr_acct') !== '1') {
        return false;
    }
    $origin = (string) ($req->get_header('origin') ?: $req->get_header('referer'));
    if ($origin === '') {
        return true; // same-origin GET/POST from old browsers; the custom header already blocks cross-site
    }
    return wp_parse_url($origin, PHP_URL_HOST) === wp_parse_url(home_url(), PHP_URL_HOST);
}

function kmpr_acct_perm_public(WP_REST_Request $req)
{
    return kmpr_acct_guard($req) ? true : new WP_Error('forbidden', 'درخواست نامعتبر.', array('status' => 403));
}

function kmpr_acct_perm_user(WP_REST_Request $req)
{
    if (!kmpr_acct_guard($req)) {
        return new WP_Error('forbidden', 'درخواست نامعتبر.', array('status' => 403));
    }
    if (!is_user_logged_in()) {
        return new WP_Error('login', 'ابتدا وارد حساب شو.', array('status' => 401));
    }
    // Owner / editors keep using wp-admin; visitor endpoints are for visitor accounts only.
    return kmpr_acct_is_visitor(wp_get_current_user()) ? true : new WP_Error('forbidden', 'این بخش برای حساب‌های بازدیدکننده است.', array('status' => 403));
}

add_action('rest_api_init', function () {
    if (!kmpr_acct_on()) {
        return; // master switch off → every account route is a 404
    }
    $ns  = 'kmpr/v1';
    $pub = 'kmpr_acct_perm_public';
    $usr = 'kmpr_acct_perm_user';
    $r   = function ($path, $method, $cb, $perm) use ($ns) {
        register_rest_route($ns, '/account/' . $path, array('methods' => $method, 'callback' => $cb, 'permission_callback' => $perm));
    };
    $r('register', 'POST', 'kmpr_acct_rest_register', $pub);
    $r('login', 'POST', 'kmpr_acct_rest_login', $pub);
    $r('reset/request', 'POST', 'kmpr_acct_rest_reset_request', $pub);
    $r('reset/confirm', 'POST', 'kmpr_acct_rest_reset_confirm', $pub);
    $r('logout', 'POST', 'kmpr_acct_rest_logout', $usr);
    $r('me', 'GET', 'kmpr_acct_rest_me', $usr);
    $r('sync', 'POST', 'kmpr_acct_rest_sync', $usr);
    $r('profile', 'POST', 'kmpr_acct_rest_profile', $usr);
    $r('password', 'POST', 'kmpr_acct_rest_password', $usr);
});

function kmpr_acct_no_store($data, $status = 200)
{
    $res = new WP_REST_Response($data, $status);
    $res->header('Cache-Control', 'no-store, private');
    return $res;
}

/** Shared tail of register + login: cookie, device link, merged data. */
function kmpr_acct_session_start($user, WP_REST_Request $req, $remember)
{
    wp_set_current_user($user->ID);
    wp_set_auth_cookie($user->ID, (bool) $remember, is_ssl());
    $pid  = kmpr_acct_link($user->ID, (string) $req->get_param('pid'));
    $data = kmpr_acct_merge(kmpr_acct_get_data($user->ID), $req->get_param('data'));
    kmpr_acct_save_data($user->ID, $data);
    update_user_meta($user->ID, 'kmpr_last_login', time());
    return kmpr_acct_no_store(array('ok' => true, 'user' => kmpr_acct_user_public($user), 'pid' => $pid, 'data' => $data));
}

function kmpr_acct_rest_register(WP_REST_Request $req)
{
    if (trim((string) $req->get_param('website')) !== '') {
        return kmpr_acct_no_store(array('ok' => true, 'pending' => true)); // honeypot: pretend, store nothing
    }
    $ip = kmpr_acct_ip_key();
    if (kmpr_acct_count('reg', $ip, HOUR_IN_SECONDS) > 5) {
        return kmpr_acct_err('ثبت‌نام‌های زیادی از این شبکه انجام شده؛ کمی بعد دوباره تلاش کن.', 429, 'rate');
    }
    $name  = kmpr_acct_clean_name($req->get_param('name'));
    $login = kmpr_acct_parse_login($req->get_param('login'));
    $pw    = (string) $req->get_param('password');
    $email = trim((string) $req->get_param('email'));
    if (mb_strlen($name) < 2) {
        return kmpr_acct_err('نامت را بنویس (دست‌کم ۲ حرف).', 400, 'name');
    }
    if (!$login) {
        return kmpr_acct_err('ایمیل، شماره موبایل یا نام کاربری معتبر وارد کن (نام کاربری: حروف انگلیسی و عدد، ۳ تا ۳۰ کاراکتر).', 400, 'login');
    }
    if ($problem = kmpr_acct_password_problem($pw, $login[1])) {
        return kmpr_acct_err($problem, 400, 'password');
    }
    if ($login[0] === 'email') {
        $email = $login[1];
    } elseif ($email !== '') {
        $email = sanitize_email($email);
        if (!is_email($email)) {
            return kmpr_acct_err('ایمیل بازیابی معتبر نیست.', 400, 'email');
        }
    }
    $user_login = $login[0] === 'email' ? 'u' . strtolower(wp_generate_password(10, false)) : $login[1];
    // Generic message: do not confirm which accounts exist (registration is IP rate-limited too).
    if (username_exists($user_login) || ($email !== '' && email_exists($email))) {
        return kmpr_acct_err('ساخت حساب با این مشخصات ممکن نیست. اگر قبلاً حساب ساخته‌ای، وارد شو یا رمز را بازیابی کن.', 400, 'taken');
    }
    $uid = wp_insert_user(array(
        'user_login'   => $user_login,
        'user_pass'    => $pw,
        'user_email'   => $email,
        'display_name' => $name,
        'nickname'     => $name,
        'first_name'   => $name,
        'role'         => 'subscriber',
    ));
    if (is_wp_error($uid)) {
        return kmpr_acct_err('ساخت حساب ممکن نشد. دوباره تلاش کن.', 400, 'insert');
    }
    update_user_meta($uid, 'kmpr_acct', time());
    update_user_meta($uid, 'show_admin_bar_front', 'false');
    if ($login[0] === 'mobile') {
        update_user_meta($uid, 'kmpr_mobile', $login[1]);
    }
    do_action('kmpr_acct_registered', $uid);

    if (kmpr_acct_opt('acct_notify_signup', 1) && function_exists('kmpr_tg_token') && kmpr_tg_token() && function_exists('kmpr_tg_send')) {
        $total = count_users()['avail_roles']['subscriber'] ?? 0;
        kmpr_tg_send('👤 <b>ثبت‌نام جدید در سایت</b>' . "\n" . 'نام: ' . esc_html($name) . "\n" . 'روش: ' . array('email' => 'ایمیل', 'mobile' => 'موبایل', 'username' => 'نام کاربری')[$login[0]] . "\n" . 'تعداد حساب‌ها: ' . (int) $total);
    }
    return kmpr_acct_session_start(get_userdata($uid), $req, $req->get_param('remember') !== false);
}

function kmpr_acct_rest_login(WP_REST_Request $req)
{
    if (trim((string) $req->get_param('website')) !== '') {
        return kmpr_acct_err('نام کاربری یا رمز عبور درست نیست.', 401, 'invalid');
    }
    $generic = 'نام کاربری یا رمز عبور درست نیست.';
    $ip      = kmpr_acct_ip_key();
    $parsed  = kmpr_acct_parse_login($req->get_param('login'));
    $acctkey = $parsed ? $parsed[1] : strtolower(trim((string) $req->get_param('login')));
    // Limits are checked before the password, so a locked account never reveals a correct guess.
    if (kmpr_acct_count('lip', $ip, 15 * MINUTE_IN_SECONDS, false) >= 20 || kmpr_acct_count('lac', $acctkey, 15 * MINUTE_IN_SECONDS, false) >= 6) {
        return kmpr_acct_err('تلاش‌های ناموفق زیاد بود؛ ۱۵ دقیقه بعد دوباره امتحان کن یا رمز را بازیابی کن.', 429, 'rate');
    }
    $pw   = (string) $req->get_param('password');
    $user = kmpr_acct_find_user($parsed);
    $ok   = false;
    if ($user && kmpr_acct_is_visitor($user) && $pw !== '' && strlen($pw) <= 200) {
        $ok = wp_check_password($pw, $user->user_pass, $user->ID);
    } else {
        wp_hash_password(wp_generate_password(12, false)); // same hashing cost on the miss path (no user enumeration)
    }
    if (!$ok) {
        kmpr_acct_count('lip', $ip, 15 * MINUTE_IN_SECONDS);
        kmpr_acct_count('lac', $acctkey, 15 * MINUTE_IN_SECONDS);
        return kmpr_acct_err($generic, 401, 'invalid');
    }
    kmpr_acct_reset_count('lac', $acctkey);
    if (function_exists('wp_password_needs_rehash') && wp_password_needs_rehash($user->user_pass, $user->ID)) {
        wp_set_password($pw, $user->ID);
    }
    do_action('wp_login', $user->user_login, $user);
    return kmpr_acct_session_start($user, $req, (bool) $req->get_param('remember'));
}

function kmpr_acct_rest_logout(WP_REST_Request $req)
{
    wp_logout();
    return kmpr_acct_no_store(array('ok' => true));
}

function kmpr_acct_rest_me(WP_REST_Request $req)
{
    $u = wp_get_current_user();
    return kmpr_acct_no_store(array('ok' => true, 'user' => kmpr_acct_user_public($u), 'pid' => kmpr_acct_pid($u->ID), 'data' => kmpr_acct_get_data($u->ID)));
}

function kmpr_acct_rest_sync(WP_REST_Request $req)
{
    $uid = get_current_user_id();
    if (kmpr_acct_count('sync', (string) $uid, MINUTE_IN_SECONDS) > 30) {
        return kmpr_acct_err('کمی آهسته‌تر!', 429, 'rate');
    }
    $in = $req->get_param('data');
    if (strlen((string) wp_json_encode($in)) > KMPR_ACCT_DATA_MAX) {
        return kmpr_acct_err('داده‌ها بیش از حد بزرگ است.', 413, 'size');
    }
    $device = strtolower((string) $req->get_param('pid'));
    $pid    = kmpr_acct_pid($uid);
    if (!$pid || ($device !== '' && $device !== $pid)) {
        $pid = kmpr_acct_link($uid, $device); // device id changed (e.g. recovery code import) → fold it in
    }
    $data = kmpr_acct_merge(kmpr_acct_get_data($uid), $in);
    if (!kmpr_acct_save_data($uid, $data)) {
        return kmpr_acct_err('داده‌ها بیش از حد بزرگ است.', 413, 'size');
    }
    return kmpr_acct_no_store(array('ok' => true, 'pid' => $pid, 'data' => $data));
}

function kmpr_acct_rest_profile(WP_REST_Request $req)
{
    global $wpdb;
    $u = wp_get_current_user();
    if (kmpr_acct_count('prof', (string) $u->ID, HOUR_IN_SECONDS) > 30) {
        return kmpr_acct_err('کمی آهسته‌تر!', 429, 'rate');
    }
    if ($req->get_param('name') !== null) {
        $name = kmpr_acct_clean_name($req->get_param('name'));
        if (mb_strlen($name) < 2) {
            return kmpr_acct_err('نام باید دست‌کم ۲ حرف باشد.', 400, 'name');
        }
        wp_update_user(array('ID' => $u->ID, 'display_name' => $name, 'nickname' => $name));
        $pid = kmpr_acct_pid($u->ID);
        if ($pid && function_exists('kmpr_games_table')) {
            $wpdb->update(kmpr_games_table(), array('nickname' => $name), array('player_id' => $pid));
        }
        if ($pid && function_exists('kmpr_games_players_table')) {
            $wpdb->update(kmpr_games_players_table(), array('nickname' => $name), array('player_id' => $pid));
        }
    }
    if ($req->get_param('email') !== null) {
        $email = sanitize_email((string) $req->get_param('email'));
        if ($email !== '' && !is_email($email)) {
            return kmpr_acct_err('ایمیل معتبر نیست.', 400, 'email');
        }
        $other = $email !== '' ? email_exists($email) : false;
        if ($other && (int) $other !== $u->ID) {
            return kmpr_acct_err('این ایمیل قابل استفاده نیست.', 400, 'email');
        }
        if (!wp_check_password((string) $req->get_param('password'), $u->user_pass, $u->ID)) {
            return kmpr_acct_err('برای تغییر ایمیل، رمز فعلی را درست وارد کن.', 403, 'password');
        }
        wp_update_user(array('ID' => $u->ID, 'user_email' => $email));
    }
    do_action('kmpr_acct_profile_update', $u->ID, $req);
    return kmpr_acct_no_store(array('ok' => true, 'user' => kmpr_acct_user_public(get_userdata($u->ID))));
}

function kmpr_acct_rest_password(WP_REST_Request $req)
{
    $u = wp_get_current_user();
    if (kmpr_acct_count('pwc', (string) $u->ID, 15 * MINUTE_IN_SECONDS) > 6) {
        return kmpr_acct_err('تلاش‌های زیاد؛ کمی بعد دوباره امتحان کن.', 429, 'rate');
    }
    if (!wp_check_password((string) $req->get_param('current'), $u->user_pass, $u->ID)) {
        return kmpr_acct_err('رمز فعلی درست نیست.', 403, 'password');
    }
    $new = (string) $req->get_param('password');
    if ($problem = kmpr_acct_password_problem($new, $u->user_login)) {
        return kmpr_acct_err($problem, 400, 'weak');
    }
    wp_set_password($new, $u->ID);                       // also logs out every other device
    WP_Session_Tokens::get_instance($u->ID)->destroy_all();
    wp_set_current_user($u->ID);
    wp_set_auth_cookie($u->ID, true, is_ssl());
    return kmpr_acct_no_store(array('ok' => true));
}

function kmpr_acct_rest_reset_request(WP_REST_Request $req)
{
    $done = kmpr_acct_no_store(array('ok' => true, 'message' => 'اگر حسابی با این مشخصات و یک ایمیل ثبت‌شده وجود داشته باشد، لینک تغییر رمز به آن ایمیل فرستاده شد.'));
    if (trim((string) $req->get_param('website')) !== '') {
        return $done;
    }
    if (kmpr_acct_count('rip', kmpr_acct_ip_key(), HOUR_IN_SECONDS) > 5) {
        return kmpr_acct_err('درخواست‌های زیادی فرستادی؛ کمی بعد دوباره تلاش کن.', 429, 'rate');
    }
    $parsed = kmpr_acct_parse_login($req->get_param('login'));
    $user   = kmpr_acct_find_user($parsed);
    if (!$user || !kmpr_acct_is_visitor($user) || !$user->user_email || kmpr_acct_count('rac', (string) $user->ID, HOUR_IN_SECONDS) > 3) {
        return $done;
    }
    $key = get_password_reset_key($user);
    if (is_wp_error($key)) {
        return $done;
    }
    $url = add_query_arg(array('reset' => $key, 'u' => $user->ID), home_url('/account/'));
    wp_mail(
        $user->user_email,
        'تغییر رمز حساب — ' . wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES),
        "سلام {$user->display_name}،\n\nبرای انتخاب رمز تازه روی لینک زیر بزن (تا ۲۴ ساعت معتبر است):\n{$url}\n\nاگر این درخواست از طرف تو نبوده، این ایمیل را نادیده بگیر؛ رمزت عوض نمی‌شود."
    );
    do_action('kmpr_acct_reset_sent', $user->ID, $url); // hook for an SMS provider later
    return $done;
}

function kmpr_acct_rest_reset_confirm(WP_REST_Request $req)
{
    if (kmpr_acct_count('rcf', kmpr_acct_ip_key(), HOUR_IN_SECONDS) > 20) {
        return kmpr_acct_err('تلاش‌های زیاد؛ کمی بعد دوباره امتحان کن.', 429, 'rate');
    }
    $user = get_userdata((int) $req->get_param('u'));
    $bad  = kmpr_acct_err('این لینک معتبر نیست یا منقضی شده؛ دوباره درخواست بده.', 400, 'key');
    if (!$user || !kmpr_acct_is_visitor($user)) {
        return $bad;
    }
    $check = check_password_reset_key((string) $req->get_param('key'), $user->user_login);
    if (is_wp_error($check)) {
        return $bad;
    }
    $new = (string) $req->get_param('password');
    if ($problem = kmpr_acct_password_problem($new, $user->user_login)) {
        return kmpr_acct_err($problem, 400, 'weak');
    }
    reset_password($user, $new);
    WP_Session_Tokens::get_instance($user->ID)->destroy_all();
    kmpr_acct_reset_count('lac', $user->user_login);
    return kmpr_acct_no_store(array('ok' => true));
}

/* =========================================================================
 * /account/ page, header button, assets
 * ====================================================================== */
add_action('init', function () {
    add_rewrite_rule('^account/?$', 'index.php?kmpr_account=1', 'top');
    if (get_option('kmpr_acct_rewrite') !== '1') {
        flush_rewrite_rules(false);
        update_option('kmpr_acct_rewrite', '1', true);
    }
});
add_filter('query_vars', function ($vars) {
    $vars[] = 'kmpr_account';
    return $vars;
});

function kmpr_is_account_page()
{
    return (bool) get_query_var('kmpr_account');
}

add_action('template_redirect', function () {
    if (!kmpr_is_account_page()) {
        return;
    }
    if (!kmpr_acct_on()) {
        global $wp_query;
        $wp_query->set_404();
        status_header(404);
        nocache_headers();
        return;
    }
    nocache_headers();
});

add_filter('template_include', function ($template) {
    if (kmpr_is_account_page() && kmpr_acct_on()) {
        return get_template_directory() . '/template-parts/account/page.php';
    }
    return $template;
});

add_filter('pre_get_document_title', function ($title) {
    return (kmpr_is_account_page() && kmpr_acct_on()) ? 'حساب کاربری — ' . get_bloginfo('name') : $title;
}, 20);

add_filter('wp_robots', function ($robots) {
    if (kmpr_is_account_page()) {
        $robots['noindex']  = true;
        $robots['nofollow'] = true;
    }
    return $robots;
});

/** Header entry point: `do_action('kmpr_account_button', 'site'|'games')`. */
add_action('kmpr_account_button', function ($where = 'site') {
    if (!kmpr_acct_on()) {
        return;
    }
    $user  = wp_get_current_user();
    $in    = $user->exists();
    $class = $where === 'games' ? 'ka-hbtn ka-hbtn--games' : 'ka-hbtn';
    if ($in) {
        printf(
            '<a href="%s" class="%s is-in" data-acct-btn aria-label="%s"><span class="ka-hbtn__av" aria-hidden="true">%s</span></a>',
            esc_url(home_url('/account/')),
            esc_attr($class),
            esc_attr('حساب کاربری: ' . $user->display_name),
            esc_html(kmpr_acct_initials($user->display_name))
        );
        return;
    }
    printf(
        '<a href="%s" class="%s" data-acct-open data-acct-btn aria-label="ورود یا ثبت‌نام"><svg width="18" height="18" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg><span class="ka-hbtn__t">ورود</span></a>',
        esc_url(home_url('/account/')),
        esc_attr($class)
    );
});

add_action('wp_enqueue_scripts', function () {
    if (!kmpr_acct_on()) {
        return;
    }
    $dir  = get_template_directory();
    $uri  = get_template_directory_uri();
    $ver  = function ($f) use ($dir) {
        return file_exists($dir . $f) ? (string) filemtime($dir . $f) : KMPR_VERSION;
    };
    wp_enqueue_style('kmpr-account', $uri . '/public/css/account.css', array(), $ver('/public/css/account.css'));
    // In <head> + defer: runs before the (footer, deferred) games module, so the server copy is merged into
    // localStorage before any game reads the profile.
    wp_enqueue_script('kmpr-account', $uri . '/public/js/account.js', array(), $ver('/public/js/account.js'), array('in_footer' => false, 'strategy' => 'defer'));

    $user = wp_get_current_user();
    $cfg  = array(
        'rest'    => esc_url_raw(rest_url('kmpr/v1/account')),
        'ajax'    => esc_url_raw(admin_url('admin-ajax.php')),
        'page'    => esc_url_raw(home_url('/account/')),
        'games'   => esc_url_raw(get_post_type_archive_link('game') ?: home_url('/games/')),
        'keys'    => kmpr_acct_sync_keys(),
        'friends' => function_exists('kmpr_friends_on') && kmpr_friends_on(),
        'user'    => null,
    );
    if ($user->exists()) {
        $cfg['user']  = kmpr_acct_user_public($user);
        $cfg['nonce'] = wp_create_nonce('wp_rest');
        $cfg['pid']   = kmpr_acct_pid($user->ID);
        $cfg['data']  = kmpr_acct_get_data($user->ID);
        $cfg['visitor'] = kmpr_acct_is_visitor($user);
    }
    wp_add_inline_script('kmpr-account', 'window.kmprAccount=' . wp_json_encode($cfg) . ';', 'before');
}, 5);
