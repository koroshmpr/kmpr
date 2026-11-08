<?php
/**
 * Games — unified player profiles (XP / level / W-L / avatar) and
 * online multiplayer rooms over plain REST polling (no websockets needed).
 *
 * Rooms: short code + per-seat secret token (only its hash is stored), optimistic
 * concurrency on a `version` column, server-side move validation through per-engine
 * handlers (filter `kmpr_game_room_handlers`), lazy expiry of idle/abandoned rooms + daily cron cleanup.
 *
 * @package KMPR
 */

if (!defined('ABSPATH')) {
    exit;
}

define('KMPR_GAMES_ONLINE_DB', '3.0.0');

function kmpr_games_players_table()
{
    global $wpdb;
    return $wpdb->prefix . 'kmpr_game_players';
}

function kmpr_games_rooms_table()
{
    global $wpdb;
    return $wpdb->prefix . 'kmpr_game_rooms';
}

add_action('init', function () {
    if (get_option('kmpr_games_online_db') === KMPR_GAMES_ONLINE_DB) {
        return;
    }
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $c  = $wpdb->get_charset_collate();
    $pt = kmpr_games_players_table();
    $rt = kmpr_games_rooms_table();
    // v2 → v3: updated_at becomes last_seen_at (+ plays_total, first_seen, user_id, ach_count counters).
    if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $pt)) === $pt) {
        $cols3 = $wpdb->get_col("SHOW COLUMNS FROM {$pt}"); // phpcs:ignore
        if (in_array('updated_at', $cols3, true) && !in_array('last_seen_at', $cols3, true)) {
            $wpdb->query("ALTER TABLE {$pt} CHANGE updated_at last_seen_at datetime NOT NULL"); // phpcs:ignore
        }
    }
    // v1 → v2: players keep only nickname, avatar key ("style:hue"), xp, W/L/D, updated_at.
    if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $pt)) === $pt) {
        $cols = $wpdb->get_col("SHOW COLUMNS FROM {$pt}"); // phpcs:ignore
        if (in_array('achievements', $cols, true)) {
            foreach ((array) $wpdb->get_results("SELECT player_id, avatar FROM {$pt}", ARRAY_A) as $r) { // phpcs:ignore
                $wpdb->update($pt, array('avatar' => kmpr_game_avatar_key($r['avatar'])), array('player_id' => $r['player_id']));
            }
            foreach (array('achievements', 'user_id', 'created_at') as $col) {
                if (in_array($col, $cols, true)) {
                    $wpdb->query("ALTER TABLE {$pt} DROP COLUMN {$col}"); // phpcs:ignore
                }
            }
        }
    }
    dbDelta("CREATE TABLE {$pt} (
  player_id char(36) NOT NULL,
  nickname varchar(32) NOT NULL DEFAULT '',
  avatar varchar(16) NOT NULL DEFAULT '',
  xp int(10) unsigned NOT NULL DEFAULT 0,
  wins int(10) unsigned NOT NULL DEFAULT 0,
  losses int(10) unsigned NOT NULL DEFAULT 0,
  draws int(10) unsigned NOT NULL DEFAULT 0,
  plays_total int(10) unsigned NOT NULL DEFAULT 0,
  ach_count smallint(5) unsigned NOT NULL DEFAULT 0,
  user_id bigint(20) unsigned NULL DEFAULT NULL,
  first_seen datetime NOT NULL DEFAULT '2000-01-01 00:00:00',
  last_seen_at datetime NOT NULL,
  PRIMARY KEY  (player_id),
  KEY xp (xp),
  KEY last_seen (last_seen_at)
) {$c};");
    dbDelta("CREATE TABLE {$rt} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  code char(6) NOT NULL,
  game_id bigint(20) unsigned NOT NULL,
  engine varchar(32) NOT NULL,
  is_public tinyint(1) NOT NULL DEFAULT 0,
  status varchar(16) NOT NULL DEFAULT 'waiting',
  state longtext NULL,
  players longtext NULL,
  version int(10) unsigned NOT NULL DEFAULT 1,
  seen0 int(10) unsigned NOT NULL DEFAULT 0,
  seen1 int(10) unsigned NOT NULL DEFAULT 0,
  created_at datetime NOT NULL,
  updated_at datetime NOT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY code (code),
  KEY lobby (status,is_public,engine),
  KEY updated (updated_at)
) {$c};");
    $wpdb->query("UPDATE {$pt} SET first_seen = last_seen_at WHERE first_seen < '2001-01-01'"); // phpcs:ignore
    update_option('kmpr_games_online_db', KMPR_GAMES_ONLINE_DB, true);
}, 6);

/* =========================================================================
 * Profiles
 * ====================================================================== */
function kmpr_game_level($xp)
{
    $level = (int) floor(sqrt(max(0, (int) $xp) / 40)) + 1;
    return array('level' => $level, 'xp' => (int) $xp, 'floor' => 40 * ($level - 1) * ($level - 1), 'next' => 40 * $level * $level);
}

/**
 * Avatars are stored as a tiny key "style:hue" (e.g. "bot:200"); accepts key, JSON (v1) or array.
 */
function kmpr_game_avatar_clean($avatar)
{
    $styles = array('blocks', 'orbit', 'bot', 'gem');
    if (is_string($avatar) && preg_match('/^([a-z]+):(\d{1,3})$/', $avatar, $m)) {
        $a = array('style' => $m[1], 'hue' => $m[2]);
    } else {
        $a = is_array($avatar) ? $avatar : (array) json_decode((string) $avatar, true);
    }
    return array(
        'style' => in_array($a['style'] ?? '', $styles, true) ? $a['style'] : 'blocks',
        'hue'   => max(0, min(359, (int) ($a['hue'] ?? 32))),
    );
}

/**
 * Get (optionally create) a profile row.
 */
function kmpr_game_avatar_key($avatar)
{
    $a = kmpr_game_avatar_clean($avatar);
    return $a['style'] . ':' . $a['hue'];
}

function kmpr_game_profile_row($player_id, $create = false)
{
    global $wpdb;
    $t   = kmpr_games_players_table();
    $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t} WHERE player_id = %s", $player_id), ARRAY_A); // phpcs:ignore
    if (!$row && $create) {
        $now = current_time('mysql', true);
        $wpdb->insert($t, array(
            'player_id'  => $player_id,
            'nickname'   => '',
            'avatar'       => 'blocks:' . (int) (hexdec(substr(md5($player_id), 0, 2)) * 359 / 255),
            'first_seen'   => $now,
            'last_seen_at' => $now,
            'user_id'      => get_current_user_id() ?: null,
        ));
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t} WHERE player_id = %s", $player_id), ARRAY_A); // phpcs:ignore
    }
    return $row;
}

/**
 * Add XP / results to a profile (creates it when missing).
 */
function kmpr_game_profile_add($player_id, $xp, $result = '', $nickname = '')
{
    global $wpdb;
    if (function_exists('kmpr_games_server_scores') && !kmpr_games_server_scores()) {
        $xp = 0; // browser-only mode: keep W/L/D + activity counters, no XP ladder
    }
    if (!kmpr_game_valid_player($player_id)) {
        return;
    }
    kmpr_game_profile_row($player_id, true);
    $t   = kmpr_games_players_table();
    $col = array('win' => 'wins', 'loss' => 'losses', 'draw' => 'draws')[$result] ?? '';
    $sql = "UPDATE {$t} SET xp = xp + %d, last_seen_at = %s" . ($col ? ", {$col} = {$col} + 1" : '') . ($nickname !== '' ? ', nickname = %s' : '') . ' WHERE player_id = %s';
    $args = array(max(0, (int) $xp), current_time('mysql', true));
    if ($nickname !== '') {
        $args[] = $nickname;
    }
    $args[] = $player_id;
    $wpdb->query($wpdb->prepare($sql, $args)); // phpcs:ignore
    delete_transient('kmpr_games_top_players');
}

function kmpr_game_profile_public($row)
{
    if (!$row) {
        return null;
    }
    $lvl = kmpr_game_level($row['xp']);
    return array(
        'nickname'     => $row['nickname'],
        'avatar'       => kmpr_game_avatar_clean($row['avatar']),
        'player'       => substr(hash('sha256', $row['player_id']), 0, 10),
        'xp'           => (int) $row['xp'],
        'level'        => $lvl['level'],
        'level_floor'  => $lvl['floor'],
        'level_next'   => $lvl['next'],
        'wins'         => (int) $row['wins'],
        'losses'       => (int) $row['losses'],
        'draws'        => (int) $row['draws'],
    );
}

/**
 * XP for a single-player run: 10 → 100 depending on how good the score is for that engine.
 */
function kmpr_game_run_xp($engine, $score)
{
    $conf  = kmpr_game_engines()[$engine] ?? null;
    if (!$conf) {
        return 10;
    }
    $ratio = min(1, $score / max(1, $conf['max'] * 0.08));
    return 10 + (int) round(90 * $ratio);
}

/* =========================================================================
 * Rooms — engine handlers
 * ====================================================================== */
function kmpr_game_room_handlers()
{
    $handlers = array(
        'xo'    => array(
            'init' => 'kmpr_room_xo_init',
            'move' => 'kmpr_room_xo_move',
            'view' => null,
        ),
        'rps15' => array(
            'init' => 'kmpr_room_rps_init',
            'move' => 'kmpr_room_rps_move',
            'view' => 'kmpr_room_rps_view',
            // Simultaneous hidden picks: skip the client version check (handler validates).
            'concurrent' => true,
        ),
    );
    return apply_filters('kmpr_game_room_handlers', $handlers);
}

/* --- XO --- */
function kmpr_room_xo_init($opts, $prev = null)
{
    $starter = $prev ? 1 - (int) $prev['starter'] : 0;
    return array(
        'board'   => '.........',
        'turn'    => $starter,
        'starter' => $starter,
        'winner'  => null,
        'line'    => null,
        'over'    => false,
        'moves'   => 0,
        'series'  => $prev['series'] ?? array(0, 0, 0),
    );
}

function kmpr_room_xo_move($s, $seat, $move)
{
    if ($s['over']) {
        return new WP_Error('over', 'این دست تمام شده است.');
    }
    if ((int) $s['turn'] !== $seat) {
        return new WP_Error('turn', 'نوبت حریف است.');
    }
    $cell = isset($move['cell']) ? (int) $move['cell'] : -1;
    if ($cell < 0 || $cell > 8 || $s['board'][$cell] !== '.') {
        return new WP_Error('cell', 'این خانه قابل انتخاب نیست.');
    }
    $mark           = $seat === 0 ? 'X' : 'O';
    $s['board'][$cell] = $mark;
    $s['moves']++;
    $s['last'] = $cell;
    foreach (array(array(0, 1, 2), array(3, 4, 5), array(6, 7, 8), array(0, 3, 6), array(1, 4, 7), array(2, 5, 8), array(0, 4, 8), array(2, 4, 6)) as $l) {
        if ($s['board'][$l[0]] === $mark && $s['board'][$l[1]] === $mark && $s['board'][$l[2]] === $mark) {
            $s['winner'] = $seat;
            $s['line']   = $l;
            $s['over']   = true;
            $s['series'][$seat]++;
            return $s;
        }
    }
    if (strpos($s['board'], '.') === false) {
        $s['winner'] = 'draw';
        $s['over']   = true;
        $s['series'][2]++;
        return $s;
    }
    $s['turn'] = 1 - $seat;
    return $s;
}

/* --- RPS-15 --- */
function kmpr_room_rps_init($opts, $prev = null)
{
    $best = (int) ($opts['best_of'] ?? ($prev['best_of'] ?? 5));
    $best = in_array($best, array(1, 3, 5, 7), true) ? $best : 5;
    return array(
        'best_of' => $best,
        'need'    => (int) ceil($best / 2),
        'round'   => 1,
        'picks'   => array(null, null),
        'score'   => array(0, 0),
        'history' => array(),
        'winner'  => null,
        'over'    => false,
        'series'  => $prev['series'] ?? array(0, 0, 0),
    );
}

function kmpr_room_rps_move($s, $seat, $move)
{
    if ($s['over']) {
        return new WP_Error('over', 'مسابقه تمام شده است.');
    }
    $pick = isset($move['pick']) ? (int) $move['pick'] : -1;
    if ($pick < 0 || $pick > 14) {
        return new WP_Error('pick', 'حرکت نامعتبر است.');
    }
    if ($s['picks'][$seat] !== null) {
        return new WP_Error('picked', 'انتخاب شما ثبت شده است؛ منتظر حریف بمانید.');
    }
    $s['picks'][$seat] = $pick;
    if ($s['picks'][0] !== null && $s['picks'][1] !== null) {
        $a = (int) $s['picks'][0];
        $b = (int) $s['picks'][1];
        $d = ($b - $a + 15) % 15;
        $w = $d === 0 ? 'draw' : ($d <= 7 ? 0 : 1);
        if ($w !== 'draw') {
            $s['score'][$w]++;
        }
        $s['history'][] = array('p' => array($a, $b), 'w' => $w, 'r' => $s['round']);
        $s['history']   = array_slice($s['history'], -30);
        $s['picks']     = array(null, null);
        $s['round']++;
        foreach (array(0, 1) as $x) {
            if ($s['score'][$x] >= $s['need']) {
                $s['winner'] = $x;
                $s['over']   = true;
                $s['series'][$x]++;
            }
        }
    }
    return $s;
}

function kmpr_room_rps_view($s, $seat)
{
    // Hide the opponent's pending pick; only reveal whether they've locked in.
    $mine          = $s['picks'][$seat];
    $other         = $s['picks'][1 - $seat];
    $s['picks']    = $seat === 0 ? array($mine, $other !== null) : array($other !== null, $mine);
    return $s;
}

/* =========================================================================
 * Rooms — storage helpers
 * ====================================================================== */
function kmpr_room_get($code)
{
    global $wpdb;
    $t   = kmpr_games_rooms_table();
    $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t} WHERE code = %s", strtoupper((string) $code)), ARRAY_A); // phpcs:ignore
    if (!$row) {
        return null;
    }
    $row['state']   = json_decode((string) $row['state'], true);
    $row['players'] = json_decode((string) $row['players'], true) ?: array();
    $row['version'] = (int) $row['version'];
    return $row;
}

/**
 * Optimistic read-modify-write. $fn($room) returns the mutated room, or WP_Error, or false (no change).
 */
function kmpr_room_mutate($code, callable $fn)
{
    global $wpdb;
    $t = kmpr_games_rooms_table();
    for ($i = 0; $i < 4; $i++) {
        $room = kmpr_room_get($code);
        if (!$room) {
            return new WP_Error('no_room', 'اتاق پیدا نشد یا منقضی شده است.');
        }
        $new = $fn($room);
        if (is_wp_error($new) || $new === false) {
            return $new === false ? $room : $new;
        }
        $done = $wpdb->query($wpdb->prepare(
            "UPDATE {$t} SET state = %s, players = %s, status = %s, version = version + 1, updated_at = %s WHERE id = %d AND version = %d", // phpcs:ignore
            wp_json_encode($new['state']),
            wp_json_encode($new['players']),
            $new['status'],
            current_time('mysql', true),
            $room['id'],
            $room['version']
        ));
        if ($done) {
            $new['version'] = $room['version'] + 1;
            // Record finished matches exactly once (on the transition to "over").
            if (!empty($new['state']['over']) && empty($room['state']['over'])) {
                kmpr_room_record_result($new);
            }
            return $new;
        }
        usleep(20000 * ($i + 1));
    }
    return new WP_Error('busy', 'اتاق شلوغ است؛ دوباره تلاش کنید.');
}

function kmpr_room_record_result($room)
{
    $w = $room['state']['winner'] ?? null;
    if ($w === null) {
        return;
    }
    foreach ($room['players'] as $p) {
        $seat   = (int) $p['seat'];
        $result = $w === 'draw' ? 'draw' : ((int) $w === $seat ? 'win' : 'loss');
        kmpr_game_profile_add($p['pid'], array('win' => 60, 'draw' => 25, 'loss' => 10)[$result], $result);
        kmpr_game_count_play((int) $room['game_id'], $p['pid'], '');
    }
    do_action('kmpr_game_room_finished', $room); // owner panel: daily online-match counter
}

function kmpr_room_new_code()
{
    global $wpdb;
    $t     = kmpr_games_rooms_table();
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    for ($i = 0; $i < 10; $i++) {
        $code = '';
        for ($k = 0; $k < 6; $k++) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }
        if (!$wpdb->get_var($wpdb->prepare("SELECT id FROM {$t} WHERE code = %s", $code))) { // phpcs:ignore
            return $code;
        }
    }
    return strtoupper(substr(wp_generate_password(12, false), 0, 6));
}

function kmpr_room_seat_by_token($room, $token)
{
    $hash = hash('sha256', (string) $token);
    foreach ($room['players'] as $p) {
        if (!empty($p['th']) && hash_equals($p['th'], $hash)) {
            return (int) $p['seat'];
        }
    }
    return null;
}

/**
 * Lazy expiry: abandoned opponents forfeit, stale waiting rooms expire.
 */
function kmpr_room_housekeep($room)
{
    $now = time();
    if ($room['status'] === 'waiting' && $now - (int) $room['seen0'] > 90) {
        return kmpr_room_mutate($room['code'], function ($r) {
            $r['status'] = 'expired';
            return $r;
        });
    }
    if ($room['status'] === 'playing' && empty($room['state']['over'])) {
        foreach (array(0, 1) as $seat) {
            $seen = (int) $room['seen' . $seat];
            if ($seen && $now - $seen > 45) {
                return kmpr_room_mutate($room['code'], function ($r) use ($seat) {
                    if ($r['status'] !== 'playing' || !empty($r['state']['over'])) {
                        return false;
                    }
                    $r['state']['over']    = true;
                    $r['state']['winner']  = 1 - $seat;
                    $r['state']['forfeit'] = $seat;
                    $r['state']['series'][1 - $seat] = ($r['state']['series'][1 - $seat] ?? 0) + 1;
                    return $r;
                });
            }
        }
    }
    return $room;
}

/**
 * Public representation of a room for a seat.
 */
function kmpr_room_view($room, $seat)
{
    $handlers = kmpr_game_room_handlers();
    $h        = $handlers[$room['engine']] ?? null;
    $state    = $room['state'];
    if ($state && isset($state['over']) && $h && !empty($h['view']) && $seat !== null) {
        $state = call_user_func($h['view'], $state, $seat);
    }
    $now     = time();
    $players = array();
    foreach ($room['players'] as $p) {
        $s         = (int) $p['seat'];
        $players[] = array(
            'seat'    => $s,
            'nick'    => $p['nick'],
            'avatar'  => kmpr_game_avatar_clean($p['avatar']),
            'player'  => substr(hash('sha256', $p['pid']), 0, 10),
            'online'  => $now - (int) $room['seen' . $s] < 12,
            'rematch' => !empty($p['rematch']),
            'left'    => !empty($p['left']),
        );
    }
    return array(
        'ok'      => true,
        'changed' => true,
        'code'    => $room['code'],
        'v'       => (int) $room['version'],
        'engine'  => $room['engine'],
        'game'    => (int) $room['game_id'],
        'public'  => (bool) $room['is_public'],
        'status'  => $room['status'],
        'seat'    => $seat,
        'players' => $players,
        'state'   => $state,
        'share'   => add_query_arg('room', $room['code'], get_permalink((int) $room['game_id'])),
        'now'     => $now,
    );
}

function kmpr_room_touch($room, $seat)
{
    if ($seat === null) {
        return;
    }
    $col = 'seen' . (int) $seat;
    if (time() - (int) $room[$col] < 4) {
        return;
    }
    global $wpdb;
    $wpdb->query($wpdb->prepare('UPDATE ' . kmpr_games_rooms_table() . " SET {$col} = %d WHERE id = %d", time(), $room['id'])); // phpcs:ignore
}

function kmpr_room_player_input(WP_REST_Request $req)
{
    $pid = strtolower((string) $req->get_param('player_id'));
    if (!kmpr_game_valid_player($pid)) {
        return new WP_Error('bad_player', 'شناسه بازیکن معتبر نیست.');
    }
    $nick = mb_substr(trim(sanitize_text_field(wp_strip_all_tags((string) $req->get_param('nickname')))), 0, 24);
    $nick = preg_replace('~https?://\S+|www\.\S+~i', '', $nick);
    if ($nick === '') {
        $row  = kmpr_game_profile_row($pid);
        $nick = $row && $row['nickname'] !== '' ? $row['nickname'] : 'مهمان';
    }
    return array('pid' => $pid, 'nick' => $nick, 'avatar' => kmpr_game_avatar_key($req->get_param('avatar')));
}

function kmpr_room_rate($bucket, $limit, $window)
{
    $key = 'kmpr_grl_' . $bucket . '_' . substr(kmpr_game_ip_hash(), 0, 16);
    $n   = (int) get_transient($key);
    if ($n >= $limit) {
        return false;
    }
    set_transient($key, $n + 1, $window);
    return true;
}

/**
 * Create a room and seat the creator at 0.
 */
function kmpr_room_create($game_id, $engine, $public, $me, $opts)
{
    global $wpdb;
    $token = wp_generate_password(32, false);
    $code  = kmpr_room_new_code();
    $now   = current_time('mysql', true);
    $state = array('opts' => $opts);
    $wpdb->insert(kmpr_games_rooms_table(), array(
        'code'       => $code,
        'game_id'    => $game_id,
        'engine'     => $engine,
        'is_public'  => $public ? 1 : 0,
        'status'     => 'waiting',
        'state'      => wp_json_encode($state),
        'players'    => wp_json_encode(array(array('seat' => 0, 'pid' => $me['pid'], 'nick' => $me['nick'], 'avatar' => $me['avatar'], 'th' => hash('sha256', $token)))),
        'version'    => 1,
        'seen0'      => time(),
        'created_at' => $now,
        'updated_at' => $now,
    ));
    $room = kmpr_room_get($code);
    $out  = kmpr_room_view($room, 0);
    $out['token'] = $token;
    return $out;
}

/**
 * Join a room by code (or re-join the same seat with the same player id).
 */
function kmpr_room_join($code, $me)
{
    $token = wp_generate_password(32, false);
    $seat  = null;
    $room  = kmpr_room_mutate($code, function ($r) use ($me, $token, &$seat) {
        foreach ($r['players'] as $i => $p) {
            if ($p['pid'] === $me['pid']) {
                $seat                     = (int) $p['seat'];
                $r['players'][$i]['th']   = hash('sha256', $token);
                $r['players'][$i]['nick'] = $me['nick'];
                $r['players'][$i]['left'] = false;
                return $r;
            }
        }
        if ($r['status'] !== 'waiting' || count($r['players']) >= 2) {
            return new WP_Error('full', 'این اتاق پر است یا بازی آن تمام شده.');
        }
        $handlers = kmpr_game_room_handlers();
        if (empty($handlers[$r['engine']])) {
            return new WP_Error('engine', 'این بازی حالت آنلاین ندارد.');
        }
        $seat           = 1;
        $opts           = (array) ($r['state']['opts'] ?? array());
        $r['players'][] = array('seat' => 1, 'pid' => $me['pid'], 'nick' => $me['nick'], 'avatar' => $me['avatar'], 'th' => hash('sha256', $token));
        $r['status']    = 'playing';
        $r['state']     = call_user_func($handlers[$r['engine']]['init'], $opts, null);
        $r['state']['opts'] = $opts;
        $r['state']['started_at'] = time();
        return $r;
    });
    if (is_wp_error($room)) {
        return $room;
    }
    kmpr_room_touch($room, $seat);
    $out          = kmpr_room_view(kmpr_room_get($code), $seat);
    $out['token'] = $token;
    return $out;
}

/* =========================================================================
 * REST routes
 * ====================================================================== */
function kmpr_room_err($e, $status = 400)
{
    $map = array('no_room' => 404, 'full' => 409, 'turn' => 409, 'busy' => 503, 'stale' => 409, 'rate' => 429, 'auth' => 403);
    return new WP_REST_Response(array('ok' => false, 'code' => $e->get_error_code(), 'message' => $e->get_error_message()), $map[$e->get_error_code()] ?? $status);
}

add_action('rest_api_init', function () {
    $ns = 'kmpr/v1';

    /* ---------- profiles ---------- */
    register_rest_route($ns, '/games/profile', array(
        'methods'             => 'POST',
        'permission_callback' => '__return_true',
        'callback'            => function (WP_REST_Request $req) {
            $pid = strtolower((string) $req->get_param('player_id'));
            if (!kmpr_game_valid_player($pid)) {
                return kmpr_game_error('شناسه بازیکن معتبر نیست.', 400, 'bad_player');
            }
            if (!kmpr_room_rate('prof', 30, 10 * MINUTE_IN_SECONDS)) {
                return kmpr_game_error('کمی آهسته‌تر!', 429, 'rate');
            }
            global $wpdb;
            kmpr_game_profile_row($pid, true);
            $data = array('last_seen_at' => current_time('mysql', true));
            $nick = $req->get_param('nickname');
            if ($nick !== null) {
                $nick = kmpr_game_clean_nick($nick);
                if ($nick !== '') {
                    $data['nickname'] = $nick;
                }
            }
            if ($req->get_param('avatar') !== null) {
                $data['avatar'] = kmpr_game_avatar_key($req->get_param('avatar'));
            }
            if ($req->get_param('ach_count') !== null) {
                $data['ach_count'] = max(0, min(500, (int) $req->get_param('ach_count')));
            }
            $wpdb->update(kmpr_games_players_table(), $data, array('player_id' => $pid));
            delete_transient('kmpr_games_top_players');
            return array('ok' => true, 'profile' => kmpr_game_profile_public(kmpr_game_profile_row($pid)));
        },
    ));

    register_rest_route($ns, '/games/top-players', array(
        'methods'             => 'GET',
        'permission_callback' => '__return_true',
        'callback'            => function () {
            if (!kmpr_games_server_scores()) {
                return array('ok' => true, 'rows' => array(), 'disabled' => true);
            }
            $rows = get_transient('kmpr_games_top_players');
            if (!is_array($rows)) {
                global $wpdb;
                $t    = kmpr_games_players_table();
                $raw  = $wpdb->get_results("SELECT * FROM {$t} WHERE xp > 0 ORDER BY xp DESC, last_seen_at ASC LIMIT 10", ARRAY_A); // phpcs:ignore
                $rows = array();
                foreach ((array) $raw as $r) {
                    $p = kmpr_game_profile_public($r);
                    $p['badges'] = array_values(array_filter(array_map('strval', (array) apply_filters('kmpr_game_player_badges', array(), $r))));
                    $p['nickname'] = $p['nickname'] !== '' ? $p['nickname'] : 'بازیکن ناشناس';
                    $rows[]        = $p;
                }
                set_transient('kmpr_games_top_players', $rows, 60);
            }
            return array('ok' => true, 'rows' => $rows);
        },
    ));

    /* ---------- rooms ---------- */
    register_rest_route($ns, '/games/rooms', array(
        'methods'             => 'POST',
        'permission_callback' => '__return_true',
        'callback'            => function (WP_REST_Request $req) {
            $game_id = (int) $req->get_param('game');
            $engine  = kmpr_game_engine_for($game_id);
            if (!$engine || empty(kmpr_game_room_handlers()[$engine])) {
                return kmpr_game_error('این بازی حالت آنلاین ندارد.', 400, 'engine');
            }
            $me = kmpr_room_player_input($req);
            if (is_wp_error($me)) {
                return kmpr_room_err($me);
            }
            if (!kmpr_room_rate('room', 20, 10 * MINUTE_IN_SECONDS)) {
                return kmpr_game_error('اتاق‌های زیادی ساختید؛ کمی بعد دوباره تلاش کنید.', 429, 'rate');
            }
            $opts = array();
            foreach ((array) $req->get_param('options') as $k => $v) {
                if (in_array($k, array('best_of', 'color', 'time', 'size'), true)) {
                    $opts[$k] = is_numeric($v) ? (int) $v : sanitize_key((string) $v);
                }
            }
            return kmpr_room_create($game_id, $engine, (bool) $req->get_param('public'), $me, $opts);
        },
    ));

    register_rest_route($ns, '/games/rooms/quick', array(
        'methods'             => 'POST',
        'permission_callback' => '__return_true',
        'callback'            => function (WP_REST_Request $req) {
            global $wpdb;
            $game_id = (int) $req->get_param('game');
            $engine  = kmpr_game_engine_for($game_id);
            if (!$engine || empty(kmpr_game_room_handlers()[$engine])) {
                return kmpr_game_error('این بازی حالت آنلاین ندارد.', 400, 'engine');
            }
            $me = kmpr_room_player_input($req);
            if (is_wp_error($me)) {
                return kmpr_room_err($me);
            }
            if (!kmpr_room_rate('quick', 40, 10 * MINUTE_IN_SECONDS)) {
                return kmpr_game_error('کمی آهسته‌تر!', 429, 'rate');
            }
            $t     = kmpr_games_rooms_table();
            $codes = $wpdb->get_col($wpdb->prepare("SELECT code FROM {$t} WHERE status = 'waiting' AND is_public = 1 AND game_id = %d AND seen0 > %d AND players NOT LIKE %s ORDER BY id ASC LIMIT 5", $game_id, time() - 20, '%' . $wpdb->esc_like($me['pid']) . '%')); // phpcs:ignore
            foreach ((array) $codes as $code) {
                $res = kmpr_room_join($code, $me);
                if (!is_wp_error($res)) {
                    $res['matched'] = true;
                    return $res;
                }
            }
            $res            = kmpr_room_create($game_id, $engine, true, $me, array());
            $res['matched'] = false;
            return $res;
        },
    ));

    register_rest_route($ns, '/games/rooms/lobby', array(
        'methods'             => 'GET',
        'permission_callback' => '__return_true',
        'callback'            => function (WP_REST_Request $req) {
            global $wpdb;
            $t     = kmpr_games_rooms_table();
            $where = $wpdb->prepare("status = 'waiting' AND is_public = 1 AND seen0 > %d", time() - 20);
            if ($req->get_param('game')) {
                $where .= $wpdb->prepare(' AND game_id = %d', (int) $req->get_param('game'));
            }
            $rows = $wpdb->get_results("SELECT code, game_id, engine, players, created_at FROM {$t} WHERE {$where} ORDER BY id DESC LIMIT 12", ARRAY_A); // phpcs:ignore
            $out  = array();
            foreach ((array) $rows as $r) {
                $p     = json_decode($r['players'], true);
                $host  = $p[0] ?? array();
                $out[] = array(
                    'code'   => $r['code'],
                    'game'   => (int) $r['game_id'],
                    'engine' => $r['engine'],
                    'title'  => get_the_title((int) $r['game_id']),
                    'url'    => add_query_arg('room', $r['code'], get_permalink((int) $r['game_id'])),
                    'host'   => $host['nick'] ?? 'مهمان',
                    'avatar' => kmpr_game_avatar_clean($host['avatar'] ?? ''),
                    'player' => isset($host['pid']) ? substr(hash('sha256', $host['pid']), 0, 10) : '',
                    'since'  => mysql_to_rfc3339($r['created_at']),
                );
            }
            $res = rest_ensure_response(array('ok' => true, 'rooms' => $out));
            $res->header('Cache-Control', 'no-store');
            return $res;
        },
    ));

    register_rest_route($ns, '/games/rooms/(?P<code>[A-Za-z0-9]{6})', array(
        'methods'             => 'GET',
        'permission_callback' => '__return_true',
        'callback'            => function (WP_REST_Request $req) {
            $room = kmpr_room_get($req['code']);
            if (!$room) {
                return kmpr_game_error('اتاق پیدا نشد یا منقضی شده است.', 404, 'no_room');
            }
            $seat = kmpr_room_seat_by_token($room, (string) $req->get_header('x-room-token') ?: (string) $req->get_param('token'));
            kmpr_room_touch($room, $seat);
            $room = kmpr_room_housekeep($room);
            if (is_wp_error($room)) {
                $room = kmpr_room_get($req['code']);
            }
            $v = (int) $req->get_param('v');
            if ($v && $v === (int) $room['version']) {
                $now  = time();
                $res  = rest_ensure_response(array(
                    'ok'      => true,
                    'changed' => false,
                    'v'       => $v,
                    'online'  => array($now - (int) $room['seen0'] < 12, $now - (int) $room['seen1'] < 12),
                ));
            } else {
                $res = rest_ensure_response(kmpr_room_view($room, $seat));
            }
            $res->header('Cache-Control', 'no-store');
            return $res;
        },
    ));

    register_rest_route($ns, '/games/rooms/(?P<code>[A-Za-z0-9]{6})/join', array(
        'methods'             => 'POST',
        'permission_callback' => '__return_true',
        'callback'            => function (WP_REST_Request $req) {
            $me = kmpr_room_player_input($req);
            if (is_wp_error($me)) {
                return kmpr_room_err($me);
            }
            if (!kmpr_room_rate('join', 60, 10 * MINUTE_IN_SECONDS)) {
                return kmpr_game_error('کمی آهسته‌تر!', 429, 'rate');
            }
            $res = kmpr_room_join($req['code'], $me);
            return is_wp_error($res) ? kmpr_room_err($res) : $res;
        },
    ));

    $act = function ($action) {
        return function (WP_REST_Request $req) use ($action) {
            $code  = strtoupper($req['code']);
            $token = (string) ($req->get_header('x-room-token') ?: $req->get_param('token'));
            $seat  = null;
            $room  = kmpr_room_mutate($code, function ($r) use ($req, $token, $action, &$seat) {
                $seat = kmpr_room_seat_by_token($r, $token);
                if ($seat === null) {
                    return new WP_Error('auth', 'شما عضو این اتاق نیستید.');
                }
                $handlers = kmpr_game_room_handlers();
                $h        = $handlers[$r['engine']] ?? null;
                if ($action === 'move') {
                    if ($r['status'] !== 'playing') {
                        return new WP_Error('status', $r['status'] === 'waiting' ? 'هنوز حریفی وارد نشده است.' : 'بازی تمام شده است.');
                    }
                    $cv = (int) $req->get_param('v');
                    if ($cv && $cv !== (int) $r['version'] && empty($h['concurrent'])) {
                        return new WP_Error('stale', 'وضعیت بازی تغییر کرده؛ دوباره تلاش کنید.');
                    }
                    $move = (array) $req->get_param('move');
                    $s    = call_user_func($h['move'], $r['state'], $seat, $move, $r);
                    if (is_wp_error($s)) {
                        return $s;
                    }
                    $s['opts']  = $r['state']['opts'] ?? array();
                    $r['state'] = $s;
                    return $r;
                }
                if ($action === 'rematch') {
                    if (empty($r['state']['over'])) {
                        return new WP_Error('status', 'بازی هنوز تمام نشده است.');
                    }
                    foreach ($r['players'] as $i => $p) {
                        if ((int) $p['seat'] === $seat) {
                            $r['players'][$i]['rematch'] = true;
                        }
                    }
                    $all = count($r['players']) === 2 && count(array_filter($r['players'], fn($p) => !empty($p['rematch']) && empty($p['left']))) === 2;
                    if ($all) {
                        $opts       = $r['state']['opts'] ?? array();
                        $r['state'] = call_user_func($h['init'], $opts, $r['state']);
                        $r['state']['opts']       = $opts;
                        $r['state']['started_at'] = time();
                        $r['status'] = 'playing';
                        foreach ($r['players'] as $i => $p) {
                            $r['players'][$i]['rematch'] = false;
                        }
                    }
                    return $r;
                }
                if ($action === 'leave') {
                    foreach ($r['players'] as $i => $p) {
                        if ((int) $p['seat'] === $seat) {
                            $r['players'][$i]['left'] = true;
                        }
                    }
                    if ($r['status'] === 'waiting') {
                        $r['status'] = 'closed';
                    } elseif ($r['status'] === 'playing' && empty($r['state']['over'])) {
                        $r['state']['over']    = true;
                        $r['state']['winner']  = 1 - $seat;
                        $r['state']['forfeit'] = $seat;
                        $r['state']['series'][1 - $seat] = ($r['state']['series'][1 - $seat] ?? 0) + 1;
                        $r['status'] = 'finished';
                    } else {
                        $r['status'] = 'finished';
                    }
                    return $r;
                }
                return false;
            });
            if (is_wp_error($room)) {
                return kmpr_room_err($room);
            }
            kmpr_room_touch($room, $seat);
            return kmpr_room_view($room, $seat);
        };
    };
    foreach (array('move', 'rematch', 'leave') as $a) {
        register_rest_route($ns, '/games/rooms/(?P<code>[A-Za-z0-9]{6})/' . $a, array(
            'methods'             => 'POST',
            'permission_callback' => '__return_true',
            'callback'            => $act($a),
        ));
    }
});

/* =========================================================================
 * Retention + storage (host has ~1 GB: keep server data minimal)
 * ====================================================================== */
add_action('init', function () {
    // Rooms are ephemeral → hourly cleanup (re-schedule an older daily event once).
    $next = wp_get_scheduled_event('kmpr_games_cleanup');
    if ($next && $next->schedule !== 'hourly') {
        wp_clear_scheduled_hook('kmpr_games_cleanup');
        $next = false;
    }
    if (!$next) {
        wp_schedule_event(time() + 300, 'hourly', 'kmpr_games_cleanup');
    }
});
add_action('kmpr_games_cleanup', 'kmpr_games_cleanup_run');

/**
 * Retention policy. Returns number of deleted rows per table.
 */
function kmpr_games_cleanup_run()
{
    global $wpdb;
    $st   = kmpr_games_table();
    $pt   = kmpr_games_players_table();
    $rt   = kmpr_games_rooms_table();
    $keep = max(10, (int) (function_exists('get_field') ? get_field('gs_keep_top', 'option') : 0) ?: 200);
    $out  = array('scores' => 0, 'players' => 0, 'rooms' => 0);

    $d30  = gmdate('Y-m-d H:i:s', time() - 30 * DAY_IN_SECONDS);
    $d180 = gmdate('Y-m-d H:i:s', time() - 180 * DAY_IN_SECONDS);
    // 1) Per game keep the top N scores; rows below the cut survive only while the player is active (30 days).
    foreach ((array) $wpdb->get_col("SELECT game_id FROM {$st} WHERE score > 0 GROUP BY game_id HAVING COUNT(*) > {$keep}") as $gid) { // phpcs:ignore
        $cut = $wpdb->get_var($wpdb->prepare("SELECT score FROM {$st} WHERE game_id = %d AND score > 0 ORDER BY score DESC LIMIT 1 OFFSET %d", $gid, $keep - 1)); // phpcs:ignore
        if ($cut !== null) {
            $out['scores'] += (int) $wpdb->query($wpdb->prepare("DELETE FROM {$st} WHERE game_id = %d AND score < %d AND last_played_at < %s", $gid, $cut, $d30)); // phpcs:ignore
        }
    }
    // Counter-only rows (online games / browser-only mode) of players inactive for 180 days.
    $out['scores'] += (int) $wpdb->query($wpdb->prepare("DELETE FROM {$st} WHERE score = 0 AND last_played_at < %s", $d180)); // phpcs:ignore
    // 2) Inactive (>180 days) casual players: low XP, few plays, no leaderboard score left.
    $out['players'] = (int) $wpdb->query($wpdb->prepare("DELETE p FROM {$pt} p LEFT JOIN {$st} s ON s.player_id = p.player_id AND s.score > 0 WHERE s.id IS NULL AND p.last_seen_at < %s AND p.xp < 100 AND p.plays_total < 20", $d180)); // phpcs:ignore
    $wpdb->query($wpdb->prepare("DELETE s FROM {$st} s LEFT JOIN {$pt} p ON p.player_id = s.player_id WHERE p.player_id IS NULL AND s.last_played_at < %s", $d180)); // phpcs:ignore
    // 3) Rooms: finished/closed after 1h, anything (waiting/abandoned) after 6h.
    $out['rooms'] = (int) $wpdb->query($wpdb->prepare(
        "DELETE FROM {$rt} WHERE updated_at < %s OR (updated_at < %s AND (status IN ('finished','closed','expired') OR state LIKE %s))", // phpcs:ignore
        gmdate('Y-m-d H:i:s', time() - 6 * HOUR_IN_SECONDS),
        gmdate('Y-m-d H:i:s', time() - HOUR_IN_SECONDS),
        '%"over":true%'
    ));
    delete_transient('kmpr_games_stats');
    delete_transient('kmpr_games_top_players');
    return $out;
}

/**
 * Bytes used by the three game tables (data + indexes).
 */
function kmpr_games_storage_bytes()
{
    global $wpdb;
    $rows = $wpdb->get_results($wpdb->prepare(
        'SELECT table_name t, table_rows r, data_length + index_length b FROM information_schema.TABLES WHERE table_schema = %s AND table_name IN (%s, %s, %s)',
        DB_NAME,
        kmpr_games_table(),
        kmpr_games_players_table(),
        kmpr_games_rooms_table()
    ), ARRAY_A);
    $out = array('total' => 0, 'tables' => array());
    foreach ((array) $rows as $r) {
        $out['tables'][$r['t']] = array('rows' => (int) $r['r'], 'bytes' => (int) $r['b']);
        $out['total']          += (int) $r['b'];
    }
    return $out;
}

/**
 * Storage panel (scores admin screen + settings page): usage + "clean up now".
 */
function kmpr_games_storage_box()
{
    $u    = kmpr_games_storage_bytes();
    $url  = wp_nonce_url(admin_url('admin-post.php?action=kmpr_games_purge'), 'kmpr_games_purge');
    $mode = kmpr_games_storage_mode() === 'local' ? 'فقط مرورگر (هیچ امتیازی روی سرور ذخیره نمی‌شود)' : 'سبک: فقط رکوردها و جدول برترین‌ها روی سرور';
    echo '<div class="notice notice-info inline" style="margin:1em 0;padding:12px 14px">';
    echo '<p style="margin:0 0 6px"><strong>فضای اشغال‌شده:</strong> ' . esc_html(size_format($u['total'], 1) ?: '0 B') . ' — ';
    $parts = array();
    foreach ($u['tables'] as $t => $x) {
        $parts[] = esc_html(preg_replace('/^.*kmpr_game_/', '', $t) . ': ' . number_format_i18n($x['rows']) . ' ردیف، ' . size_format($x['bytes'], 1));
    }
    echo implode(' · ', $parts) . '</p>'; // phpcs:ignore
    echo '<p style="margin:0 0 8px">حالت ذخیره‌سازی: ' . esc_html($mode) . '</p>';
    if (function_exists('kmpr_arcade_pwa_stats_line')) {
        echo '<p style="margin:0 0 8px">' . esc_html(kmpr_arcade_pwa_stats_line()) . '</p>';
    }
    echo '<a class="button button-secondary" href="' . esc_url($url) . '">پاکسازی الان</a>';
    if (!empty($_GET['purged'])) { // phpcs:ignore
        echo ' <span style="color:#008a20">پاکسازی انجام شد.</span>';
    }
    echo '</div>';
}

add_action('admin_post_kmpr_games_purge', function () {
    if (!current_user_can('manage_options')) {
        wp_die('دسترسی ندارید.');
    }
    check_admin_referer('kmpr_games_purge');
    kmpr_games_cleanup_run();
    $back = wp_get_referer() ?: admin_url('edit.php?post_type=game&page=kmpr-game-scores');
    wp_safe_redirect(add_query_arg('purged', 1, $back));
    exit;
});


/**
 * Count one play: players row (plays_total, last_seen_at, nickname) + per-(game, player) counter row.
 * Works in both storage modes; never touches the score.
 */
function kmpr_game_count_play($game_id, $player_id, $nickname = '')
{
    global $wpdb;
    if (!kmpr_game_valid_player($player_id)) {
        return;
    }
    $nick = kmpr_game_clean_nick($nickname);
    $now  = current_time('mysql', true);
    $row  = kmpr_game_profile_row($player_id, true);
    $pt   = kmpr_games_players_table();
    $uid  = get_current_user_id();
    $wpdb->query($wpdb->prepare(
        "UPDATE {$pt} SET plays_total = plays_total + 1, last_seen_at = %s" . ($nick !== '' ? ', nickname = %s' : '') . ($uid ? ', user_id = %d' : '') . ' WHERE player_id = %s', // phpcs:ignore
        ...array_values(array_filter(array($now, $nick !== '' ? $nick : null, $uid ?: null, $player_id), fn($v) => $v !== null))
    ));
    $st = kmpr_games_table();
    $wpdb->query($wpdb->prepare(
        "INSERT INTO {$st} (game_id, player_id, nickname, score, duration_ms, plays, created_at, last_played_at) VALUES (%d, %s, %s, 0, 0, 1, %s, %s)
        ON DUPLICATE KEY UPDATE plays = plays + 1, last_played_at = VALUES(last_played_at)" . ($nick !== '' ? ', nickname = VALUES(nickname)' : ''), // phpcs:ignore
        (int) $game_id, $player_id, $nick !== '' ? $nick : (string) ($row['nickname'] ?? ''), $now, $now
    ));
    delete_transient('kmpr_games_stats');
}

/**
 * Sanitize a nickname: strip tags/URLs, collapse spaces, max 32 chars.
 */
function kmpr_game_clean_nick($nick)
{
    $nick = wp_strip_all_tags((string) $nick);
    $nick = preg_replace('~(https?://|www\.)\S+|\S+\.(com|ir|net|org|io)\b~i', '', $nick);
    $nick = trim(preg_replace('/\s+/u', ' ', sanitize_text_field($nick)));
    return mb_substr($nick, 0, 32);
}
