<?php
/**
 * Friends list for visitor accounts (needs inc/accounts.php).
 *
 * Storage = user meta (small int arrays, capped): kmpr_friends, kmpr_freq_in / kmpr_freq_out (uid => time),
 * kmpr_blocked. Public identity = handle (`kmpr_handle`, chosen or auto) + private invite code (`kmpr_fcode`) for
 * the share link /account/?add=CODE. Emails / phones / logins are never exposed.
 *
 * Online status = the existing presence transient (inc/games-lobby.php); invites ride on the presence heartbeat
 * response (rest_post_dispatch) — no new polling on game pages. «دعوت به بازی» either sends a room code directly
 * (JS API window.kmprFriends.invite) or leaves an "intent" that is fulfilled when the inviter's next online room
 * is created (POST /games/rooms).
 *
 * @package KMPR
 */

if (!defined('ABSPATH')) {
    exit;
}

const KMPR_FRIENDS_MAX = 200;
const KMPR_INVITE_TTL  = 900;

function kmpr_friends_on()
{
    return kmpr_acct_on() && (bool) kmpr_acct_opt('friends_on', 1);
}

/* ---------------------------------------------------------------- storage */

function kmpr_fr_ids($uid, $key)
{
    $v = get_user_meta($uid, $key, true);
    return is_array($v) ? $v : array();
}

function kmpr_fr_set($uid, $key, $val)
{
    update_user_meta($uid, $key, $val);
}

function kmpr_fr_friends($uid)
{
    return array_values(array_unique(array_map('intval', kmpr_fr_ids($uid, 'kmpr_friends'))));
}

function kmpr_fr_is_blocked($by, $who)
{
    return in_array((int) $who, array_map('intval', kmpr_fr_ids($by, 'kmpr_blocked')), true);
}

function kmpr_fr_link($a, $b)
{
    foreach (array(array($a, $b), array($b, $a)) as list($x, $y)) {
        $f = kmpr_fr_friends($x);
        if (!in_array($y, $f, true)) {
            $f[] = $y;
            kmpr_fr_set($x, 'kmpr_friends', array_slice($f, -KMPR_FRIENDS_MAX));
        }
        foreach (array('kmpr_freq_in', 'kmpr_freq_out') as $k) {
            $r = kmpr_fr_ids($x, $k);
            unset($r[$y]);
            kmpr_fr_set($x, $k, $r);
        }
    }
}

function kmpr_fr_unlink($a, $b)
{
    foreach (array(array($a, $b), array($b, $a)) as list($x, $y)) {
        kmpr_fr_set($x, 'kmpr_friends', array_values(array_diff(kmpr_fr_friends($x), array($y))));
        foreach (array('kmpr_freq_in', 'kmpr_freq_out') as $k) {
            $r = kmpr_fr_ids($x, $k);
            unset($r[$y]);
            kmpr_fr_set($x, $k, $r);
        }
    }
}

/** Handle + invite code are created on first use. */
function kmpr_fr_handle($uid)
{
    $h = (string) get_user_meta($uid, 'kmpr_handle', true);
    if ($h === '') {
        do {
            $h = 'player' . wp_rand(10000, 999999);
        } while (kmpr_fr_by_meta('kmpr_handle', $h));
        update_user_meta($uid, 'kmpr_handle', $h);
    }
    return $h;
}

function kmpr_fr_code($uid)
{
    $c = (string) get_user_meta($uid, 'kmpr_fcode', true);
    if ($c === '') {
        $c = strtolower(wp_generate_password(10, false));
        update_user_meta($uid, 'kmpr_fcode', $c);
    }
    return $c;
}

function kmpr_fr_by_meta($key, $value)
{
    $ids = get_users(array('meta_key' => $key, 'meta_value' => $value, 'number' => 1, 'fields' => 'ID', 'count_total' => false));
    return $ids ? (int) $ids[0] : 0;
}

/* ---------------------------------------------------------------- views */

function kmpr_fr_presence_map()
{
    $list = get_transient('kmpr_presence');
    $out  = array();
    $now  = time();
    foreach (is_array($list) ? $list : array() as $k => $row) {
        if ($now - (int) $row['t'] <= (defined('KMPR_PRESENCE_TTL') ? KMPR_PRESENCE_TTL : 70)) {
            $out[$k] = $row;
        }
    }
    return $out;
}

function kmpr_fr_where_title($w)
{
    if ($w === 'hub' || $w === '') {
        return 'در گیم‌سنتر';
    }
    $label = function_exists('kmpr_game_engines') ? (kmpr_game_engines()[$w]['label'] ?? $w) : $w;
    return 'در حال بازی: ' . preg_replace('/\s*\(.*\)$/u', '', $label);
}

function kmpr_fr_card($uid, $presence = null, $with_scores = false)
{
    global $wpdb;
    $u = get_userdata($uid);
    if (!$u) {
        return null;
    }
    $pid  = kmpr_acct_pid($uid);
    $pub  = kmpr_acct_public_id($pid);
    $card = array('id' => (int) $uid, 'name' => $u->display_name, 'initials' => kmpr_acct_initials($u->display_name), 'handle' => kmpr_fr_handle($uid), 'online' => false, 'status' => '');
    if ($presence !== null && $pub && isset($presence[$pub])) {
        $card['online'] = true;
        $card['where']  = $presence[$pub]['w'];
        $card['status'] = kmpr_fr_where_title($presence[$pub]['w']);
    }
    if ($with_scores && $pid && function_exists('kmpr_games_table')) {
        $rows = $wpdb->get_results($wpdb->prepare('SELECT game_id, score FROM ' . kmpr_games_table() . ' WHERE player_id = %s AND score > 0 ORDER BY last_played_at DESC LIMIT 4', $pid), ARRAY_A); // phpcs:ignore
        $card['best'] = array();
        foreach ((array) $rows as $r) {
            if (get_post_status((int) $r['game_id']) === 'publish') {
                $card['best'][] = array('title' => get_the_title((int) $r['game_id']), 'score' => (int) $r['score'], 'url' => get_permalink((int) $r['game_id']));
            }
        }
    }
    return $card;
}

function kmpr_fr_invites($uid)
{
    $inv = get_transient('kmpr_finv_' . $uid);
    $now = time();
    $out = array();
    foreach (is_array($inv) ? $inv : array() as $i) {
        if ($now - (int) $i['t'] < KMPR_INVITE_TTL && !kmpr_fr_is_blocked($uid, $i['from'])) {
            $out[] = $i;
        }
    }
    return $out;
}

function kmpr_fr_push_invite($from, $to, $game_id, $code)
{
    $fu   = get_userdata($from);
    $list = kmpr_fr_invites($to);
    $list = array_values(array_filter($list, fn($i) => !((int) $i['from'] === (int) $from)));
    $list[] = array(
        'code'  => $code,
        'from'  => (int) $from,
        'name'  => $fu ? $fu->display_name : '',
        'game'  => (int) $game_id,
        'title' => get_the_title($game_id),
        'url'   => add_query_arg('room', $code, get_permalink($game_id)),
        't'     => time(),
    );
    set_transient('kmpr_finv_' . $to, array_slice($list, -10), KMPR_INVITE_TTL);
}

/** Published games that have an online-room mode. */
function kmpr_fr_online_games()
{
    $engines = function_exists('kmpr_game_room_handlers') ? array_keys(kmpr_game_room_handlers()) : array();
    $engines = apply_filters('kmpr_friends_invite_engines', $engines);
    if (!$engines) {
        return array();
    }
    $ids = get_posts(array('post_type' => 'game', 'post_status' => 'publish', 'numberposts' => 100, 'fields' => 'ids', 'meta_query' => array(array('key' => 'game_engine', 'value' => $engines, 'compare' => 'IN'))));
    $out = array();
    foreach ($ids as $id) {
        $out[] = array('id' => (int) $id, 'title' => get_the_title($id), 'engine' => (string) get_post_meta($id, 'game_engine', true));
    }
    return $out;
}

/** Game by id or engine slug. */
function kmpr_fr_game_id($game)
{
    if (is_numeric($game)) {
        return get_post_type((int) $game) === 'game' && get_post_status((int) $game) === 'publish' ? (int) $game : 0;
    }
    $slug = sanitize_key((string) $game);
    if ($slug === '') {
        return 0;
    }
    $ids = get_posts(array('post_type' => 'game', 'post_status' => 'publish', 'numberposts' => 1, 'fields' => 'ids', 'meta_key' => 'game_engine', 'meta_value' => $slug));
    if (!$ids) {
        $p   = get_page_by_path($slug, OBJECT, 'game');
        $ids = $p ? array($p->ID) : array();
    }
    return $ids ? (int) $ids[0] : 0;
}

/* ---------------------------------------------------------------- REST */

add_action('rest_api_init', function () {
    if (!kmpr_friends_on()) {
        return;
    }
    $r = function ($path, $method, $cb) {
        register_rest_route('kmpr/v1', '/account/friends' . $path, array('methods' => $method, 'callback' => $cb, 'permission_callback' => 'kmpr_acct_perm_user'));
    };
    $r('', 'GET', 'kmpr_fr_rest_list');
    $r('/request', 'POST', 'kmpr_fr_rest_request');
    $r('/respond', 'POST', 'kmpr_fr_rest_respond');
    $r('/remove', 'POST', 'kmpr_fr_rest_remove');
    $r('/block', 'POST', 'kmpr_fr_rest_block');
    $r('/settings', 'POST', 'kmpr_fr_rest_settings');
    $r('/invite', 'POST', 'kmpr_fr_rest_invite');
    $r('/invite/dismiss', 'POST', 'kmpr_fr_rest_dismiss');
});

function kmpr_fr_rest_list(WP_REST_Request $req)
{
    $uid = get_current_user_id();
    $pre = kmpr_fr_presence_map();
    $ids = kmpr_fr_friends($uid);
    $in  = array_keys(kmpr_fr_ids($uid, 'kmpr_freq_in'));
    $out = array_keys(kmpr_fr_ids($uid, 'kmpr_freq_out'));
    cache_users(array_merge($ids, $in, $out));
    $friends = array_values(array_filter(array_map(fn($id) => kmpr_fr_card($id, $pre, true), $ids)));
    usort($friends, fn($a, $b) => ($b['online'] <=> $a['online']) ?: strcmp($a['name'], $b['name']));
    $blocked = array_values(array_filter(array_map(fn($id) => kmpr_fr_card($id), array_map('intval', kmpr_fr_ids($uid, 'kmpr_blocked')))));
    return kmpr_acct_no_store(array(
        'ok'       => true,
        'me'       => array(
            'handle'   => kmpr_fr_handle($uid),
            'link'     => add_query_arg('add', kmpr_fr_code($uid), home_url('/account/')),
            'findable' => get_user_meta($uid, 'kmpr_findable', true) !== '0',
        ),
        'friends'  => $friends,
        'incoming' => array_values(array_filter(array_map(fn($id) => kmpr_fr_card((int) $id), $in))),
        'outgoing' => array_values(array_filter(array_map(fn($id) => kmpr_fr_card((int) $id), $out))),
        'blocked'  => $blocked,
        'invites'  => kmpr_fr_invites($uid),
        'games'    => kmpr_fr_online_games(),
    ));
}

function kmpr_fr_rest_request(WP_REST_Request $req)
{
    $uid = get_current_user_id();
    if (kmpr_acct_count('frq', (string) $uid, HOUR_IN_SECONDS) > 30) {
        return kmpr_acct_err('درخواست‌های زیادی فرستادی؛ کمی بعد دوباره امتحان کن.', 429, 'rate');
    }
    $code   = strtolower(preg_replace('/[^a-z0-9]/i', '', (string) $req->get_param('code')));
    $handle = strtolower(ltrim(trim((string) $req->get_param('handle')), '@'));
    $to     = 0;
    if ($code !== '') {
        $to = kmpr_fr_by_meta('kmpr_fcode', $code); // share link works even when not findable
    } elseif (preg_match('/^[a-z][a-z0-9_]{2,19}$/', $handle)) {
        $to = kmpr_fr_by_meta('kmpr_handle', $handle);
        if ($to && get_user_meta($to, 'kmpr_findable', true) === '0') {
            $to = 0;
        }
    }
    if (!$to || !kmpr_acct_is_visitor($to)) {
        return kmpr_acct_err('بازیکنی با این مشخصات پیدا نشد.', 404, 'not_found');
    }
    if ($to === $uid) {
        return kmpr_acct_err('این خودتی! 🙂', 400, 'self');
    }
    if (in_array($to, kmpr_fr_friends($uid), true)) {
        return kmpr_acct_no_store(array('ok' => true, 'state' => 'friends', 'message' => 'از قبل با هم دوستید.'));
    }
    $sent = array('ok' => true, 'state' => 'sent', 'message' => 'درخواست دوستی فرستاده شد.');
    if (kmpr_fr_is_blocked($to, $uid) || kmpr_fr_is_blocked($uid, $to)) {
        return kmpr_acct_no_store($sent); // never reveal a block
    }
    $mine_in = kmpr_fr_ids($uid, 'kmpr_freq_in');
    if (isset($mine_in[$to])) {
        kmpr_fr_link($uid, $to); // they already asked me → accept
        return kmpr_acct_no_store(array('ok' => true, 'state' => 'friends', 'message' => 'حالا با هم دوستید!'));
    }
    $their_in = kmpr_fr_ids($to, 'kmpr_freq_in');
    if (count($their_in) < 50 && count(kmpr_fr_friends($uid)) < KMPR_FRIENDS_MAX) {
        $their_in[$uid] = time();
        kmpr_fr_set($to, 'kmpr_freq_in', $their_in);
        $mine_out      = kmpr_fr_ids($uid, 'kmpr_freq_out');
        $mine_out[$to] = time();
        kmpr_fr_set($uid, 'kmpr_freq_out', $mine_out);
    }
    return kmpr_acct_no_store($sent);
}

function kmpr_fr_rest_respond(WP_REST_Request $req)
{
    $uid = get_current_user_id();
    $from = (int) $req->get_param('id');
    $in  = kmpr_fr_ids($uid, 'kmpr_freq_in');
    if (!isset($in[$from])) {
        return kmpr_acct_err('درخواستی پیدا نشد.', 404, 'not_found');
    }
    if ($req->get_param('accept')) {
        kmpr_fr_link($uid, $from);
    } else {
        kmpr_fr_unlink($uid, $from); // clears both request lists
    }
    return kmpr_fr_rest_list($req);
}

function kmpr_fr_rest_remove(WP_REST_Request $req)
{
    kmpr_fr_unlink(get_current_user_id(), (int) $req->get_param('id'));
    return kmpr_fr_rest_list($req);
}

function kmpr_fr_rest_block(WP_REST_Request $req)
{
    $uid = get_current_user_id();
    $who = (int) $req->get_param('id');
    $b   = array_map('intval', kmpr_fr_ids($uid, 'kmpr_blocked'));
    if ($req->get_param('unblock')) {
        $b = array_values(array_diff($b, array($who)));
    } elseif ($who && $who !== $uid && get_userdata($who)) {
        kmpr_fr_unlink($uid, $who);
        $b[] = $who;
        $b   = array_slice(array_values(array_unique($b)), -500);
    }
    kmpr_fr_set($uid, 'kmpr_blocked', $b);
    return kmpr_fr_rest_list($req);
}

function kmpr_fr_rest_settings(WP_REST_Request $req)
{
    $uid = get_current_user_id();
    if ($req->get_param('findable') !== null) {
        update_user_meta($uid, 'kmpr_findable', $req->get_param('findable') ? '1' : '0');
    }
    if ($req->get_param('handle') !== null) {
        $h = strtolower(trim((string) $req->get_param('handle')));
        if (!preg_match('/^[a-z][a-z0-9_]{2,19}$/', $h)) {
            return kmpr_acct_err('شناسه باید با حرف انگلیسی شروع شود و ۳ تا ۲۰ حرف/عدد/خط‌زیر باشد.', 400, 'handle');
        }
        $owner = kmpr_fr_by_meta('kmpr_handle', $h);
        if ($owner && $owner !== $uid) {
            return kmpr_acct_err('این شناسه را کس دیگری برداشته.', 409, 'taken');
        }
        update_user_meta($uid, 'kmpr_handle', $h);
    }
    if ($req->get_param('new_link')) {
        delete_user_meta($uid, 'kmpr_fcode'); // old share links stop working
    }
    return kmpr_fr_rest_list($req);
}

function kmpr_fr_rest_invite(WP_REST_Request $req)
{
    $uid = get_current_user_id();
    if (kmpr_acct_count('finv', (string) $uid, 10 * MINUTE_IN_SECONDS) > 30) {
        return kmpr_acct_err('دعوت‌های زیادی فرستادی؛ کمی صبر کن.', 429, 'rate');
    }
    $to   = array_values(array_intersect(array_map('intval', (array) $req->get_param('to')), kmpr_fr_friends($uid)));
    $game = kmpr_fr_game_id($req->get_param('game'));
    if (!$to || !$game) {
        return kmpr_acct_err('دوست یا بازی معتبر نیست.', 400, 'bad');
    }
    $code = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $req->get_param('code')));
    if ($code !== '') {
        if (strlen($code) < 4 || strlen($code) > 12) {
            return kmpr_acct_err('کد اتاق معتبر نیست.', 400, 'code');
        }
        // Built-in rooms: only a player of that room may invite into it.
        if (function_exists('kmpr_room_get') && strlen($code) === 6 && ($room = kmpr_room_get($code))) {
            $pid = kmpr_acct_pid($uid);
            if (!in_array($pid, array_column((array) $room['players'], 'pid'), true)) {
                return kmpr_acct_err('فقط بازیکن‌های همین اتاق می‌توانند دعوت کنند.', 403, 'room');
            }
        }
        foreach (array_slice($to, 0, 10) as $t) {
            if (!kmpr_fr_is_blocked($t, $uid)) {
                kmpr_fr_push_invite($uid, $t, $game, $code);
            }
        }
        return kmpr_acct_no_store(array('ok' => true, 'sent' => count($to)));
    }
    // No room yet: remember the intent; the inviter's next room for this game delivers it.
    set_transient('kmpr_fint_' . $uid, array('to' => array_slice($to, 0, 10), 'game' => $game, 't' => time()), 10 * MINUTE_IN_SECONDS);
    return kmpr_acct_no_store(array('ok' => true, 'url' => add_query_arg('create', '1', get_permalink($game))));
}

function kmpr_fr_rest_dismiss(WP_REST_Request $req)
{
    $uid  = get_current_user_id();
    $code = strtoupper((string) $req->get_param('code'));
    $list = array_values(array_filter(kmpr_fr_invites($uid), fn($i) => $i['code'] !== $code));
    set_transient('kmpr_finv_' . $uid, $list, KMPR_INVITE_TTL);
    return kmpr_acct_no_store(array('ok' => true));
}

/* ---------------------------------------------------------------- hooks into the game REST */

add_filter('rest_post_dispatch', function ($response, $server, $request) {
    if (!kmpr_friends_on() || !is_user_logged_in() || !($response instanceof WP_REST_Response) || $response->get_status() !== 200) {
        return $response;
    }
    $route = $request->get_route();
    $uid   = get_current_user_id();
    if ($route === '/kmpr/v1/games/presence') {
        // Piggyback: friends' online status + pending invites on the 25s heartbeat.
        if (!kmpr_acct_is_visitor($uid)) {
            return $response;
        }
        $data = $response->get_data();
        $pre  = kmpr_fr_presence_map();
        $ids  = kmpr_fr_friends($uid);
        cache_users($ids);
        $data['friends'] = array_values(array_filter(array_map(fn($id) => kmpr_fr_card($id, $pre), $ids)));
        $data['invites'] = kmpr_fr_invites($uid);
        $data['friendReqs'] = count(kmpr_fr_ids($uid, 'kmpr_freq_in'));
        $response->set_data($data);
    } elseif ($route === '/kmpr/v1/games/rooms' && $request->get_method() === 'POST') {
        $intent = get_transient('kmpr_fint_' . $uid);
        $data   = $response->get_data();
        if (is_array($intent) && !empty($data['code']) && (int) $intent['game'] === (int) $request->get_param('game')) {
            delete_transient('kmpr_fint_' . $uid);
            foreach ($intent['to'] as $t) {
                if (in_array((int) $t, kmpr_fr_friends($uid), true) && !kmpr_fr_is_blocked($t, $uid)) {
                    kmpr_fr_push_invite($uid, (int) $t, (int) $intent['game'], (string) $data['code']);
                }
            }
            $data['invited'] = count($intent['to']);
            $response->set_data($data);
        }
    }
    return $response;
}, 10, 3);
