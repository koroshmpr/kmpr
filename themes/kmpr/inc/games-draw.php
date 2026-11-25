<?php
/**
 * «نقاش‌باشی» — online draw-and-guess for 2–8 players over plain HTTP polling.
 *
 * Storage:
 *  - Room meta + game state live in the shared rooms table (kmpr_game_rooms, engine 'naqqash') so the hub
 *    lobby lists public rooms for free and the hourly cleanup removes old rooms. Never touched by the
 *    generic 2-seat room handlers (no entry in kmpr_game_room_handlers → generic join/move refuse it).
 *  - High-frequency data goes to two small own tables:
 *      kmpr_draw_ev   append-only events (stroke batches, chat, system lines) read with a since-id cursor.
 *      kmpr_draw_seen per-seat heartbeat (presence for N players without bumping the room version).
 *
 * Anti-cheat: words are picked and checked here; the secret word is only ever included in the view for
 * the drawer, for players who already guessed it, and after the turn ends. Near misses are answered
 * privately and never broadcast. Turn timers / hints / skips advance lazily on any request ("tick").
 *
 * @package KMPR
 */

if (!defined('ABSPATH')) {
    exit;
}

define('KMPR_NQ_ENGINE', 'naqqash');
define('KMPR_NQ_DB', '1');

function kmpr_nq_ev_table()
{
    global $wpdb;
    return $wpdb->prefix . 'kmpr_draw_ev';
}
function kmpr_nq_seen_table()
{
    global $wpdb;
    return $wpdb->prefix . 'kmpr_draw_seen';
}

add_action('init', function () {
    if (get_option('kmpr_draw_db') === KMPR_NQ_DB) {
        return;
    }
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $c = $wpdb->get_charset_collate();
    dbDelta('CREATE TABLE ' . kmpr_nq_ev_table() . " (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  room_id bigint(20) unsigned NOT NULL,
  turn smallint(5) unsigned NOT NULL DEFAULT 0,
  k char(1) NOT NULL DEFAULT 'c',
  seat tinyint(3) unsigned NOT NULL DEFAULT 0,
  vis tinyint(1) unsigned NOT NULL DEFAULT 0,
  t int(10) unsigned NOT NULL DEFAULT 0,
  d text NULL,
  PRIMARY KEY  (id),
  KEY rid (room_id,id),
  KEY t (t)
) {$c};");
    dbDelta('CREATE TABLE ' . kmpr_nq_seen_table() . " (
  room_id bigint(20) unsigned NOT NULL,
  seat tinyint(3) unsigned NOT NULL,
  t int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY  (room_id,seat)
) {$c};");
    update_option('kmpr_draw_db', KMPR_NQ_DB, true);
}, 7);

/* =========================================================================
 * Settings (ACF options page «بازی‌ها › تنظیمات آرشیو», own tab)
 * ====================================================================== */
function kmpr_nq_opt($name)
{
    $d = array('nq_on' => 0, 'nq_public' => 1, 'nq_time' => 75, 'nq_rounds' => 3, 'nq_max' => 8);
    $v = function_exists('get_field') ? get_field($name, 'option') : null;
    if ($v === null || $v === '') {
        $v = $d[$name];
    }
    switch ($name) {
        case 'nq_on':
        case 'nq_public':
            return (bool) $v;
        case 'nq_time':
            return max(45, min(120, (int) $v));
        case 'nq_rounds':
            return max(1, min(6, (int) $v));
        case 'nq_max':
            return max(2, min(8, (int) $v));
    }
    return $v;
}

add_action('acf/include_fields', function () {
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }
    $f = function ($name, $label, $type, $extra = array()) {
        return array_merge(array('key' => 'field_kmpr_nq_' . $name, 'name' => $name, 'label' => $label, 'type' => $type), $extra);
    };
    acf_add_local_field_group(array(
        'key'        => 'group_kmpr_naqqash',
        'title'      => 'نقاش‌باشی (نقاشی و حدس آنلاین)',
        'fields'     => array(
            $f('nq_on', 'حالت آنلاین نقاش‌باشی فعال باشد', 'true_false', array('ui' => 1, 'default_value' => 0, 'wrapper' => array('width' => 50), 'instructions' => 'خاموش = صفحه بازی پیام «موقتاً غیرفعال» نشان می‌دهد و سرور هیچ درخواستی قبول نمی‌کند.')),
            $f('nq_public', 'بازی سریع با اتاق‌های عمومی (لابی)', 'true_false', array('ui' => 1, 'default_value' => 1, 'wrapper' => array('width' => 50), 'instructions' => 'خاموش = فقط اتاق خصوصی با کد و لینک دعوت.')),
            $f('nq_time', 'زمان هر نوبت (ثانیه، پیش‌فرض اتاق)', 'number', array('default_value' => 75, 'min' => 45, 'max' => 120, 'wrapper' => array('width' => 33))),
            $f('nq_rounds', 'تعداد دور (پیش‌فرض اتاق)', 'number', array('default_value' => 3, 'min' => 1, 'max' => 6, 'wrapper' => array('width' => 33))),
            $f('nq_max', 'حداکثر بازیکن هر اتاق', 'number', array('default_value' => 8, 'min' => 2, 'max' => 8, 'wrapper' => array('width' => 33))),
        ),
        'location'   => array(array(array('param' => 'options_page', 'operator' => '==', 'value' => 'kmpr-games-settings'))),
        'menu_order' => 30,
    ));
});

/** Client config on the game page (before the games bundle). */
add_action('wp_enqueue_scripts', function () {
    if (!is_singular('game') || !wp_script_is('kmpr-games', 'enqueued')) {
        return;
    }
    if ((string) kmpr_game_meta(get_queried_object_id(), 'game_engine', '') !== KMPR_NQ_ENGINE) {
        return;
    }
    $cfg = array(
        'on'     => kmpr_nq_opt('nq_on'),
        'pub'    => kmpr_nq_opt('nq_public'),
        'time'   => kmpr_nq_opt('nq_time'),
        'rounds' => kmpr_nq_opt('nq_rounds'),
        'max'    => kmpr_nq_opt('nq_max'),
        'cats'   => array_merge(array('mix' => 'همه'), array_map(fn($c) => $c['label'], kmpr_nq_words()['cats'] ?? array())),
    );
    wp_add_inline_script('kmpr-games', 'window.kmprDraw=' . wp_json_encode($cfg) . ';', 'before');
}, 30);

/* =========================================================================
 * Words + normalisation
 * ====================================================================== */
function kmpr_nq_words()
{
    static $w = null;
    if ($w === null) {
        $raw = @file_get_contents(__DIR__ . '/data/naqqash-words.json'); // phpcs:ignore
        $w   = $raw ? json_decode($raw, true) : null;
        if (!is_array($w) || empty($w['cats'])) {
            $w = array('cats' => array('daily' => array('label' => 'روزمره', 'easy' => array('سیب', 'ماشین', 'خانه'), 'medium' => array('چتر'), 'hard' => array('چرخ و فلک'))));
        }
    }
    return $w;
}

/** Comparable form: Arabic → Persian letters, no diacritics/tatweel/ZWNJ/spaces/punctuation, latin digits. */
function kmpr_nq_norm($s)
{
    $s = mb_strtolower(trim((string) $s), 'UTF-8');
    $s = strtr($s, array(
        'ي' => 'ی', 'ى' => 'ی', 'ئ' => 'ی', 'ك' => 'ک', 'ة' => 'ه', 'ۀ' => 'ه', 'أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ٱ' => 'ا', 'ؤ' => 'و',
        '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
    ));
    return (string) preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{0640}\x{200B}-\x{200F}\x{FEFF}\s\p{P}\p{S}]+/u', '', $s);
}

function kmpr_nq_lev($a, $b)
{
    $a = preg_split('//u', $a, -1, PREG_SPLIT_NO_EMPTY);
    $b = preg_split('//u', $b, -1, PREG_SPLIT_NO_EMPTY);
    $n = count($a);
    $m = count($b);
    if (!$n || !$m || abs($n - $m) > 3) {
        return max($n, $m);
    }
    $prev = range(0, $m);
    for ($i = 1; $i <= $n; $i++) {
        $cur = array($i);
        for ($j = 1; $j <= $m; $j++) {
            $cur[$j] = min($prev[$j] + 1, $cur[$j - 1] + 1, $prev[$j - 1] + ($a[$i - 1] === $b[$j - 1] ? 0 : 1));
        }
        $prev = $cur;
    }
    return $prev[$m];
}

/** 'hit' | 'near' | '' */
function kmpr_nq_check($guess, $word)
{
    $g = kmpr_nq_norm($guess);
    if ($g === '') {
        return '';
    }
    foreach ((array) $word['n'] as $n) {
        if ($g === $n) {
            return 'hit';
        }
    }
    foreach ((array) $word['n'] as $n) {
        $len = mb_strlen($n);
        $d   = kmpr_nq_lev($g, $n);
        if ($len >= 3 && $d <= ($len >= 7 ? 2 : 1)) {
            return 'near';
        }
        $gl = mb_strlen($g);
        if ($gl >= 3 && $len >= 4 && (mb_strpos($n, $g) !== false || mb_strpos($g, $n) !== false) && min($gl, $len) / max($gl, $len) >= 0.6) {
            return 'near';
        }
    }
    return '';
}

function kmpr_nq_word($entry, $diff)
{
    $vars = array_values(array_filter(array_map('trim', explode('|', (string) $entry))));
    return array('d' => $vars[0] ?? '', 'n' => array_values(array_unique(array_map('kmpr_nq_norm', $vars))), 'df' => $diff);
}

/** Three choices (easy / medium / hard) not used before in this room. */
function kmpr_nq_choices($cat, $used)
{
    $cats = kmpr_nq_words()['cats'];
    $out  = array();
    foreach (array('easy', 'medium', 'hard') as $diff) {
        $key  = isset($cats[$cat]) ? $cat : array_rand($cats);
        $pool = array_values(array_filter((array) ($cats[$key][$diff] ?? array()), function ($w) use ($used, $out) {
            $d = explode('|', $w)[0];
            return !in_array($d, $used, true) && !in_array($d, array_column($out, 'd'), true);
        }));
        if (!$pool) {
            $pool = (array) ($cats[$key][$diff] ?? array('سیب'));
        }
        $out[] = kmpr_nq_word($pool[random_int(0, count($pool) - 1)], $diff);
    }
    return $out;
}

/** Display mask for guessers: '_' hidden letter, ' ' space, '|' half-space; revealed letters as-is. */
function kmpr_nq_mask($display, $hint)
{
    $chars = preg_split('//u', $display, -1, PREG_SPLIT_NO_EMPTY);
    $out   = '';
    $li    = 0;
    foreach ($chars as $ch) {
        if ($ch === ' ') {
            $out .= ' ';
            continue;
        }
        if ($ch === "\u{200C}") {
            $out .= '|';
            continue;
        }
        $out .= in_array($li, (array) $hint, true) ? $ch : '_';
        $li++;
    }
    return $out;
}
function kmpr_nq_letters($display)
{
    return count(array_filter(preg_split('//u', $display, -1, PREG_SPLIT_NO_EMPTY), fn($c) => $c !== ' ' && $c !== "\u{200C}"));
}

/** Basic chat moderation: strip tags/links, length cap, mask a few rude words. */
function kmpr_nq_clean_chat($text)
{
    $t = wp_strip_all_tags((string) $text);
    $t = preg_replace('~https?://\S+|www\.\S+|\S+\.(com|ir|net|org|xyz|me)\b\S*~iu', '…', $t);
    $t = trim(preg_replace('/\s+/u', ' ', $t));
    $t = mb_substr($t, 0, 60);
    $bad = array('کیر', 'کص', 'کس', 'کسکش', 'جنده', 'مادرجنده', 'حرومزاده', 'حرامزاده', 'گاییدم', 'کونی', 'لاشی', 'جاکش', 'دیوث', 'fuck', 'shit', 'bitch');
    $words = explode(' ', $t);
    foreach ($words as $i => $w) {
        $n = kmpr_nq_norm($w);
        foreach ($bad as $b) {
            if ($n === $b || (mb_strlen($b) >= 4 && mb_strpos($n, $b) === 0)) {
                $words[$i] = '***';
                break;
            }
        }
    }
    return implode(' ', $words);
}

/* =========================================================================
 * Room helpers
 * ====================================================================== */
function kmpr_nq_seen_map($room_id)
{
    global $wpdb;
    $out = array();
    foreach ((array) $wpdb->get_results($wpdb->prepare('SELECT seat, t FROM ' . kmpr_nq_seen_table() . ' WHERE room_id = %d', $room_id), ARRAY_A) as $r) { // phpcs:ignore
        $out[(int) $r['seat']] = (int) $r['t'];
    }
    return $out;
}

function kmpr_nq_touch($room, $seat, &$seen)
{
    global $wpdb;
    $now = time();
    if ($seat !== null && ($seen[$seat] ?? 0) < $now - 3) {
        $wpdb->query($wpdb->prepare('REPLACE INTO ' . kmpr_nq_seen_table() . ' (room_id, seat, t) VALUES (%d, %d, %d)', $room['id'], $seat, $now)); // phpcs:ignore
        $seen[$seat] = $now;
    }
    // Room-level activity for the hub lobby (lists rooms with seen0 in the last 20 s).
    if ((int) $room['seen0'] < $now - 4) {
        $wpdb->query($wpdb->prepare('UPDATE ' . kmpr_games_rooms_table() . ' SET seen0 = %d WHERE id = %d', $now, $room['id'])); // phpcs:ignore
    }
}

/** Seats that count as present: not left and seen recently (or just joined). */
function kmpr_nq_active($room, $seen)
{
    $now = time();
    $out = array();
    foreach ($room['players'] as $p) {
        $s = (int) $p['seat'];
        if (empty($p['left']) && (($seen[$s] ?? 0) > $now - 20 || (int) ($p['j'] ?? 0) > $now - 15)) {
            $out[] = $s;
        }
    }
    sort($out);
    return $out;
}

function kmpr_nq_status($room, $seen)
{
    $st = $room['state'];
    if (($st['ph'] ?? '') === 'over') {
        return 'finished';
    }
    $active = kmpr_nq_active($room, $seen);
    if (!$active && !array_filter($room['players'], fn($p) => empty($p['left']))) {
        return 'closed';
    }
    return count($active) < kmpr_nq_opt('nq_max') ? 'waiting' : 'playing';
}

/** Optimistic read-modify-write on the shared rooms table. $fn($room) → room | WP_Error | false. */
function kmpr_nq_mutate($room, callable $fn, $seen)
{
    global $wpdb;
    $t = kmpr_games_rooms_table();
    for ($i = 0; $i < 5; $i++) {
        if ($i > 0) {
            $room = kmpr_room_get($room['code']);
            if (!$room) {
                return new WP_Error('no_room', 'اتاق پیدا نشد یا منقضی شده است.');
            }
        }
        $new = $fn($room);
        if (is_wp_error($new) || $new === false) {
            return $new === false ? $room : $new;
        }
        $status = kmpr_nq_status($new, $seen);
        $done   = $wpdb->query($wpdb->prepare(
            "UPDATE {$t} SET state = %s, players = %s, status = %s, version = version + 1, updated_at = %s WHERE id = %d AND version = %d", // phpcs:ignore
            wp_json_encode($new['state']),
            wp_json_encode($new['players']),
            $status,
            current_time('mysql', true),
            $room['id'],
            $room['version']
        ));
        if ($done) {
            $new['version'] = $room['version'] + 1;
            $new['status']  = $status;
            if (($new['state']['ph'] ?? '') === 'over' && ($room['state']['ph'] ?? '') !== 'over') {
                kmpr_nq_finished($new);
            }
            return $new;
        }
        usleep(15000 * ($i + 1));
    }
    return new WP_Error('busy', 'اتاق شلوغ است؛ دوباره تلاش کنید.');
}

function kmpr_nq_event($room_id, $turn, $k, $seat, $d = '', $vis = 0)
{
    global $wpdb;
    $wpdb->insert(kmpr_nq_ev_table(), array('room_id' => $room_id, 'turn' => (int) $turn, 'k' => $k, 'seat' => (int) $seat, 'vis' => $vis, 't' => time(), 'd' => (string) $d), array('%d', '%d', '%s', '%d', '%d', '%d', '%s'));
}

/** XP + play counters once per finished game; strokes are no longer needed. */
function kmpr_nq_finished($room)
{
    global $wpdb;
    $sc = (array) ($room['state']['sc'] ?? array());
    arsort($sc);
    $top = $sc ? max($sc) : 0;
    foreach ($room['players'] as $p) {
        $s = (int) $p['seat'];
        if (!isset($sc[$s])) {
            continue;
        }
        $win = $top > 0 && (int) $sc[$s] === (int) $top;
        if (function_exists('kmpr_game_profile_add')) {
            kmpr_game_profile_add($p['pid'], $win ? 60 : 15, $win ? 'win' : 'loss');
        }
        if (function_exists('kmpr_game_count_play')) {
            kmpr_game_count_play((int) $room['game_id'], $p['pid'], '');
        }
    }
    $wpdb->query($wpdb->prepare('DELETE FROM ' . kmpr_nq_ev_table() . " WHERE room_id = %d AND k = 's'", $room['id'])); // phpcs:ignore
    do_action('kmpr_game_room_finished', $room);
}

/* =========================================================================
 * Game flow (pure state transitions)
 * ====================================================================== */
function kmpr_nq_init_state($opts)
{
    return array(
        'g'     => KMPR_NQ_ENGINE,
        'opts'  => $opts,
        'ph'    => 'lobby',
        'host'  => 0,
        'round' => 0,
        'turn'  => 0,
        'queue' => array(),
        'dr'    => null,
        'ch'    => array(),
        'w'     => null,
        'hint'  => array(),
        'end'   => 0,
        't0'    => 0,
        'gs'    => array(),
        'sc'    => array('0' => 0),
        'last'  => null,
        'used'  => array(),
    );
}

function kmpr_nq_now()
{
    return round(microtime(true), 2);
}

/** Start the next turn (or end the game). */
function kmpr_nq_next_turn($r, $active)
{
    $s   = $r['state'];
    $now = kmpr_nq_now();
    if (count($active) < 2) {
        $s['ph']  = array_sum(array_map('intval', $s['sc'])) > 0 ? 'over' : 'lobby';
        $s['end'] = 0;
        $s['w']   = null;
        $s['ch']  = array();
        $r['state'] = $s;
        return $r;
    }
    $dr = null;
    for ($guard = 0; $guard < 20 && $dr === null; $guard++) {
        if (!$s['queue']) {
            $s['round']++;
            if ($s['round'] > (int) $s['opts']['rounds']) {
                $s['ph']  = 'over';
                $s['end'] = 0;
                $s['w']   = null;
                $s['ch']  = array();
                $r['state'] = $s;
                return $r;
            }
            $s['queue'] = $active;
        }
        $cand = (int) array_shift($s['queue']);
        if (in_array($cand, $active, true)) {
            $dr = $cand;
        }
    }
    $s['ph']   = 'pick';
    $s['dr']   = $dr;
    $s['turn'] = (int) $s['turn'] + 1;
    $s['ch']   = kmpr_nq_choices($s['opts']['cat'] ?? 'mix', (array) $s['used']);
    $s['w']    = null;
    $s['hint'] = array();
    $s['gs']   = array();
    $s['t0']   = $now;
    $s['end']  = $now + 15;
    $r['state'] = $s;
    return $r;
}

function kmpr_nq_reveal($r, $why)
{
    $s     = $r['state'];
    $gains = array();
    foreach ((array) $s['gs'] as $seat => $pts) {
        $gains[(string) $seat] = (int) $pts;
    }
    $dg = (int) ($s['dg'] ?? 0);
    if ($s['dr'] !== null) {
        $gains[(string) $s['dr']] = $dg;
    }
    $s['last'] = array('w' => $s['w']['d'] ?? '', 'dr' => $s['dr'], 'gains' => (object) $gains, 'why' => $why, 'turn' => $s['turn']);
    if (!empty($s['w']['d'])) {
        $s['used'][] = $s['w']['d'];
        $s['used']   = array_slice($s['used'], -60);
    }
    $s['ph']  = 'reveal';
    $s['end'] = kmpr_nq_now() + 6;
    $s['dg']  = 0;
    $r['state'] = $s;
    return $r;
}

/** Time-based transitions. Returns a new room or false. */
function kmpr_nq_tick($r, $seen)
{
    $s      = $r['state'];
    $now    = kmpr_nq_now();
    $active = kmpr_nq_active($r, $seen);
    $ch     = false;
    // Host left/offline → next present player becomes host.
    if ($active && !in_array((int) $s['host'], $active, true)) {
        $s['host'] = $active[0];
        $ch        = true;
    }
    $r['state'] = $s;
    $ph         = $s['ph'];
    $dr         = $s['dr'];
    $drGone     = $dr !== null && !in_array((int) $dr, $active, true);
    if ($ph === 'pick') {
        if ($drGone) {
            return kmpr_nq_reveal($r, 'left');
        }
        if ($now >= $s['end']) {
            $s['w']   = $s['ch'][random_int(0, 2)];
            $s['ch']  = array();
            $s['ph']  = 'draw';
            $s['t0']  = $now;
            $s['end'] = $now + (int) $s['opts']['time'];
            $r['state'] = $s;
            return $r;
        }
    } elseif ($ph === 'draw') {
        if ($drGone) {
            return kmpr_nq_reveal($r, 'left');
        }
        if ($now >= $s['end']) {
            return kmpr_nq_reveal($r, 'time');
        }
        $guessers = array_diff($active, array((int) $dr));
        if ($guessers && !array_diff($guessers, array_map('intval', array_keys((array) $s['gs'])))) {
            return kmpr_nq_reveal($r, 'all');
        }
        // Hint letters at 45 / 65 / 82 % of the turn (at least two letters stay hidden).
        $L    = kmpr_nq_letters($s['w']['d']);
        $maxH = min($L <= 3 ? 1 : ($L <= 6 ? 2 : 3), max(0, $L - 2));
        $frac = ($now - $s['t0']) / max(1, $s['end'] - $s['t0']);
        $want = 0;
        foreach (array(0.45, 0.65, 0.82) as $th) {
            $want += $frac >= $th ? 1 : 0;
        }
        $want = min($want, $maxH);
        if (count($s['hint']) < $want) {
            $free = array_values(array_diff(range(0, $L - 1), $s['hint']));
            if ($free) {
                $s['hint'][] = $free[random_int(0, count($free) - 1)];
                $r['state']  = $s;
                return $r;
            }
        }
    } elseif ($ph === 'reveal') {
        if ($now >= $s['end']) {
            return kmpr_nq_next_turn($r, $active);
        }
    }
    return $ch ? $r : false;
}

/* =========================================================================
 * Views + responses
 * ====================================================================== */
function kmpr_nq_view($room, $seat, $seen)
{
    $s      = $room['state'];
    $now    = time();
    $active = kmpr_nq_active($room, $seen);
    $ph     = $s['ph'];
    $isDr   = $seat !== null && $s['dr'] !== null && (int) $s['dr'] === (int) $seat;
    $gs     = array_map('intval', array_keys((array) $s['gs']));
    $guessed = $seat !== null && in_array((int) $seat, $gs, true);
    $pl     = array();
    foreach ($room['players'] as $p) {
        $ps   = (int) $p['seat'];
        $pl[] = array(
            's'  => $ps,
            'n'  => $p['nick'],
            'a'  => kmpr_game_avatar_clean($p['avatar']),
            'p'  => substr(hash('sha256', $p['pid']), 0, 10),
            'on' => in_array($ps, $active, true),
            'l'  => !empty($p['left']),
        );
    }
    $v = array(
        'code'   => $room['code'],
        'share'  => add_query_arg('room', $room['code'], get_permalink((int) $room['game_id'])),
        'pub'    => (bool) $room['is_public'],
        'me'     => $seat,
        'host'   => (int) $s['host'],
        'ph'     => $ph,
        'round'  => (int) $s['round'],
        'rounds' => (int) $s['opts']['rounds'],
        'time'   => (int) $s['opts']['time'],
        'cat'    => (string) ($s['opts']['cat'] ?? 'mix'),
        'turn'   => (int) $s['turn'],
        'dr'     => $s['dr'],
        'end'    => (float) $s['end'],
        'gs'     => $gs,
        'sc'     => (object) array_map('intval', (array) $s['sc']),
        'pl'     => $pl,
        'max'    => kmpr_nq_opt('nq_max'),
        'last'   => $ph === 'reveal' || $ph === 'over' ? $s['last'] : null,
    );
    if ($ph === 'pick' && $isDr) {
        $v['ch'] = array_map(fn($w) => array('w' => $w['d'], 'df' => $w['df']), (array) $s['ch']);
    }
    if ($ph === 'draw' && !empty($s['w'])) {
        if ($isDr || $guessed) {
            $v['word'] = $s['w']['d'];
        } else {
            $v['mask'] = kmpr_nq_mask($s['w']['d'], $s['hint']);
        }
    }
    return $v;
}

/** Events after $since (current-turn strokes, chat, system lines), filtered for this seat. */
function kmpr_nq_events($room, $seat, $since)
{
    global $wpdb;
    $t    = kmpr_nq_ev_table();
    $s    = $room['state'];
    $turn = (int) $s['turn'];
    if ($since <= 0) {
        $strokes = $wpdb->get_results($wpdb->prepare("SELECT id, k, seat, vis, turn, d FROM {$t} WHERE room_id = %d AND k = 's' AND turn = %d ORDER BY id ASC LIMIT 1500", $room['id'], $turn), ARRAY_A); // phpcs:ignore
        $chat    = $wpdb->get_results($wpdb->prepare("SELECT id, k, seat, vis, turn, d FROM {$t} WHERE room_id = %d AND k <> 's' ORDER BY id DESC LIMIT 40", $room['id']), ARRAY_A); // phpcs:ignore
        $rows    = array_merge((array) $strokes, array_reverse((array) $chat));
        usort($rows, fn($a, $b) => (int) $a['id'] - (int) $b['id']);
    } else {
        $rows = $wpdb->get_results($wpdb->prepare("SELECT id, k, seat, vis, turn, d FROM {$t} WHERE room_id = %d AND id > %d AND (k <> 's' OR turn = %d) ORDER BY id ASC LIMIT 400", $room['id'], $since, $turn), ARRAY_A); // phpcs:ignore
    }
    $privileged = $seat !== null && (((int) $s['dr'] === (int) $seat) || array_key_exists((string) $seat, (array) $s['gs']) || array_key_exists((int) $seat, (array) $s['gs']));
    $out = array();
    $cur = $since;
    foreach ((array) $rows as $r) {
        $cur = max($cur, (int) $r['id']);
        if ((int) $r['vis'] === 1 && (int) $r['turn'] === $turn && in_array($s['ph'], array('pick', 'draw'), true) && !$privileged) {
            continue;
        }
        $e = array((int) $r['id'], $r['k'], (int) $r['seat'], (string) $r['d']);
        if ((int) $r['vis'] === 1) {
            $e[] = 1;
        }
        $out[] = $e;
    }
    return array($out, $cur);
}

function kmpr_nq_reply($room, $seat, $seen, $since, $cv, $extra = array())
{
    list($ev, $cur) = kmpr_nq_events($room, $seat, $since);
    $out = array_merge(array(
        'ok'  => true,
        'v'   => (int) $room['version'],
        'now' => kmpr_nq_now(),
        'cur' => $cur,
        'ev'  => $ev,
        'on'  => kmpr_nq_active($room, $seen),
    ), $extra);
    if ((int) $cv !== (int) $room['version']) {
        $out['st'] = kmpr_nq_view($room, $seat, $seen);
    }
    $res = rest_ensure_response($out);
    $res->header('Cache-Control', 'no-store');
    return $res;
}

function kmpr_nq_err($code, $msg, $status = 400)
{
    return new WP_REST_Response(array('ok' => false, 'code' => $code, 'message' => $msg), $status);
}

/** Per-seat event rate (strokes / chat) from the events table itself — no extra writes. */
function kmpr_nq_rate_ok($room_id, $seat, $k, $limit, $window)
{
    global $wpdb;
    $n = (int) $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM ' . kmpr_nq_ev_table() . ' WHERE room_id = %d AND seat = %d AND k = %s AND t >= %d', $room_id, $seat, $k, time() - $window)); // phpcs:ignore
    return $n < $limit;
}

function kmpr_nq_create($game_id, $public, $me, $opts)
{
    global $wpdb;
    $token = wp_generate_password(32, false);
    $code  = kmpr_room_new_code();
    $now   = current_time('mysql', true);
    $state = kmpr_nq_init_state($opts);
    $wpdb->insert(kmpr_games_rooms_table(), array(
        'code'       => $code,
        'game_id'    => $game_id,
        'engine'     => KMPR_NQ_ENGINE,
        'is_public'  => $public ? 1 : 0,
        'status'     => 'waiting',
        'state'      => wp_json_encode($state),
        'players'    => wp_json_encode(array(array('seat' => 0, 'pid' => $me['pid'], 'nick' => $me['nick'], 'avatar' => $me['avatar'], 'th' => hash('sha256', $token), 'j' => time()))),
        'version'    => 1,
        'seen0'      => time(),
        'created_at' => $now,
        'updated_at' => $now,
    ));
    $room = kmpr_room_get($code);
    $seen = array();
    kmpr_nq_touch($room, 0, $seen);
    return array($room, 0, $token, $seen);
}

function kmpr_nq_join($room, $me)
{
    $token = wp_generate_password(32, false);
    $seat  = null;
    $seen  = kmpr_nq_seen_map($room['id']);
    $isNew = false;
    $new   = kmpr_nq_mutate($room, function ($r) use ($me, $token, &$seat, &$isNew, $seen) {
        $isNew = false;
        foreach ($r['players'] as $i => $p) {
            if ($p['pid'] === $me['pid']) {
                $seat                     = (int) $p['seat'];
                $r['players'][$i]['th']   = hash('sha256', $token);
                $r['players'][$i]['nick'] = $me['nick'];
                $r['players'][$i]['left'] = false;
                $r['players'][$i]['j']    = time();
                return $r;
            }
        }
        if ($r['status'] === 'closed' || count(kmpr_nq_active($r, $seen)) >= kmpr_nq_opt('nq_max')) {
            return new WP_Error('full', 'این اتاق پر است یا بسته شده.');
        }
        $seat           = max(array_map(fn($p) => (int) $p['seat'], $r['players'])) + 1;
        $r['players'][] = array('seat' => $seat, 'pid' => $me['pid'], 'nick' => $me['nick'], 'avatar' => $me['avatar'], 'th' => hash('sha256', $token), 'j' => time());
        $r['state']['sc'][(string) $seat] = 0;
        $isNew = true;
        return $r;
    }, $seen);
    if (is_wp_error($new)) {
        return $new;
    }
    kmpr_nq_event($new['id'], $new['state']['turn'], 'j', $seat, $me['nick']);
    kmpr_nq_touch($new, $seat, $seen);
    return array($new, $seat, $token, $seen);
}

function kmpr_nq_guard($req)
{
    if (!kmpr_nq_opt('nq_on')) {
        return kmpr_nq_err('off', 'حالت آنلاین نقاش‌باشی موقتاً خاموش است.', 503);
    }
    return null;
}

/* =========================================================================
 * REST
 * ====================================================================== */
add_action('rest_api_init', function () {
    $ns = 'kmpr/v1';

    // Create (private or public) and quick-match.
    register_rest_route($ns, '/games/draw/(?P<mode>create|quick)', array(
        'methods'             => 'POST',
        'permission_callback' => '__return_true',
        'callback'            => function (WP_REST_Request $req) {
            if ($g = kmpr_nq_guard($req)) {
                return $g;
            }
            $game_id = (int) $req->get_param('game');
            if (kmpr_game_engine_for($game_id) !== KMPR_NQ_ENGINE) {
                return kmpr_nq_err('engine', 'بازی نامعتبر است.');
            }
            $me = kmpr_room_player_input($req);
            if (is_wp_error($me)) {
                return kmpr_nq_err('bad_player', $me->get_error_message());
            }
            if (!kmpr_room_rate('nqroom', 30, 10 * MINUTE_IN_SECONDS)) {
                return kmpr_nq_err('rate', 'اتاق‌های زیادی ساختید؛ کمی بعد دوباره تلاش کنید.', 429);
            }
            $quick = $req['mode'] === 'quick';
            if ($quick && !kmpr_nq_opt('nq_public')) {
                return kmpr_nq_err('off', 'بازی سریع خاموش است؛ یک اتاق خصوصی بسازید.', 403);
            }
            if ($quick) {
                global $wpdb;
                $t     = kmpr_games_rooms_table();
                $codes = $wpdb->get_col($wpdb->prepare("SELECT code FROM {$t} WHERE engine = %s AND status = 'waiting' AND is_public = 1 AND game_id = %d AND seen0 > %d ORDER BY id ASC LIMIT 5", KMPR_NQ_ENGINE, $game_id, time() - 20)); // phpcs:ignore
                foreach ((array) $codes as $code) {
                    $room = kmpr_room_get($code);
                    if (!$room) {
                        continue;
                    }
                    $res = kmpr_nq_join($room, $me);
                    if (!is_wp_error($res)) {
                        list($room, $seat, $token, $seen) = $res;
                        return kmpr_nq_reply($room, $seat, $seen, 0, -1, array('token' => $token, 'seat' => $seat, 'matched' => true));
                    }
                }
            }
            $o    = (array) $req->get_param('opts');
            $cats = array_keys(kmpr_nq_words()['cats']);
            $opts = array(
                'rounds' => max(1, min(6, (int) ($o['rounds'] ?? kmpr_nq_opt('nq_rounds')))),
                'time'   => in_array((int) ($o['time'] ?? 0), array(60, 75, 90), true) ? (int) $o['time'] : kmpr_nq_opt('nq_time'),
                'cat'    => in_array(($o['cat'] ?? ''), $cats, true) ? $o['cat'] : 'mix',
            );
            list($room, $seat, $token, $seen) = kmpr_nq_create($game_id, $quick || (bool) $req->get_param('public'), $me, $opts);
            return kmpr_nq_reply($room, $seat, $seen, 0, -1, array('token' => $token, 'seat' => $seat, 'matched' => false));
        },
    ));

    register_rest_route($ns, '/games/draw/(?P<code>[A-Za-z0-9]{6})/join', array(
        'methods'             => 'POST',
        'permission_callback' => '__return_true',
        'callback'            => function (WP_REST_Request $req) {
            if ($g = kmpr_nq_guard($req)) {
                return $g;
            }
            $me = kmpr_room_player_input($req);
            if (is_wp_error($me)) {
                return kmpr_nq_err('bad_player', $me->get_error_message());
            }
            if (!kmpr_room_rate('nqjoin', 60, 10 * MINUTE_IN_SECONDS)) {
                return kmpr_nq_err('rate', 'کمی آهسته‌تر!', 429);
            }
            $room = kmpr_room_get($req['code']);
            if (!$room || $room['engine'] !== KMPR_NQ_ENGINE || in_array($room['status'], array('closed', 'expired'), true)) {
                return kmpr_nq_err('no_room', 'اتاق پیدا نشد یا بسته شده است.', 404);
            }
            $res = kmpr_nq_join($room, $me);
            if (is_wp_error($res)) {
                return kmpr_nq_err($res->get_error_code(), $res->get_error_message(), 409);
            }
            list($room, $seat, $token, $seen) = $res;
            return kmpr_nq_reply($room, $seat, $seen, 0, -1, array('token' => $token, 'seat' => $seat));
        },
    ));

    // Poll (GET) and every in-game action (POST) share one handler.
    $handler = function (WP_REST_Request $req) {
        if ($g = kmpr_nq_guard($req)) {
            return $g;
        }
        $room = kmpr_room_get($req['code']);
        if (!$room || $room['engine'] !== KMPR_NQ_ENGINE) {
            return kmpr_nq_err('no_room', 'اتاق پیدا نشد یا منقضی شده است.', 404);
        }
        $seat = kmpr_room_seat_by_token($room, (string) ($req->get_header('x-room-token') ?: $req->get_param('token')));
        if ($seat === null) {
            return kmpr_nq_err('auth', 'شما عضو این اتاق نیستید.', 403);
        }
        $seen = kmpr_nq_seen_map($room['id']);
        kmpr_nq_touch($room, $seat, $seen);
        $since = max(0, (int) $req->get_param('since'));
        $cv    = (int) $req->get_param('v');
        $a     = $req->get_method() === 'POST' ? sanitize_key((string) $req->get_param('a')) : '';
        $extra = array();
        $post  = array(); // events to insert after a successful state write

        if ($a === 'draw') {
            // Stroke batch: no state change, just an event row (cheap; drawer posts every ~400 ms).
            $ops = (string) $req->get_param('ops');
            $s   = $room['state'];
            if ($s['ph'] !== 'draw' || (int) $s['dr'] !== $seat || (int) $req->get_param('turn') !== (int) $s['turn']) {
                $extra['drop'] = true;
            } elseif (strlen($ops) > 6000 || !preg_match('/^(?:(?:p\d{1,5}(?:,-?\d{1,4}){4,}|a\d{1,5}(?:,-?\d{1,4}){2,}|u\d{1,5}|x)(?:;|$))+$/', $ops)) {
                return kmpr_nq_err('bad_ops', 'داده نقاشی نامعتبر است.');
            } elseif (!kmpr_nq_rate_ok($room['id'], $seat, 's', 14, 3)) {
                return kmpr_nq_err('rate', 'کمی آهسته‌تر!', 429);
            } else {
                global $wpdb;
                $bytes = (int) $wpdb->get_var($wpdb->prepare('SELECT COALESCE(SUM(LENGTH(d)),0) FROM ' . kmpr_nq_ev_table() . " WHERE room_id = %d AND k = 's' AND turn = %d", $room['id'], $s['turn'])); // phpcs:ignore
                if ($bytes > 300000) {
                    return kmpr_nq_err('full', 'این نقاشی بیش از حد شلوغ شده؛ پاکش کنید.', 413);
                }
                kmpr_nq_event($room['id'], $s['turn'], 's', $seat, $ops);
            }
            $a = '';
        }

        $hit = '';
        $fn  = function ($r) use ($a, $req, $seat, $seen, &$post, &$hit, &$extra) {
            $post  = array();
            $hit   = '';
            $s     = $r['state'];
            $dirty = false;
            $now   = kmpr_nq_now();
            if ($a === 'guess') {
                $text = kmpr_nq_clean_chat($req->get_param('text'));
                if ($text === '' || !kmpr_nq_rate_ok($r['id'], $seat, 'c', 6, 5)) {
                    return new WP_Error('rate', $text === '' ? 'پیام خالی است.' : 'کمی آهسته‌تر!');
                }
                $isDr    = $s['dr'] !== null && (int) $s['dr'] === $seat;
                $guessed = array_key_exists((string) $seat, (array) $s['gs']) || array_key_exists($seat, (array) $s['gs']);
                if ($s['ph'] === 'draw' && !$isDr && !$guessed) {
                    $res = kmpr_nq_check($text, $s['w']);
                    if ($res === 'hit') {
                        $left  = max(0, $s['end'] - $now);
                        $order = count((array) $s['gs']);
                        $bonus = array('easy' => 0, 'medium' => 10, 'hard' => 20)[$s['w']['df']] ?? 0;
                        $pts   = 40 + (int) round(160 * $left / max(1, (int) $s['opts']['time'])) + (array(30, 15, 5)[$order] ?? 0) + $bonus;
                        $s['gs'] = (array) $s['gs'];
                        $s['gs'][(string) $seat] = $pts;
                        $s['sc'][(string) $seat] = (int) ($s['sc'][(string) $seat] ?? 0) + $pts;
                        $dg = 20 + (int) round($bonus / 2) + ($order === 0 ? 10 : 0);
                        $s['sc'][(string) $s['dr']] = (int) ($s['sc'][(string) $s['dr']] ?? 0) + $dg;
                        $s['dg'] = (int) ($s['dg'] ?? 0) + $dg;
                        $post[]  = array('g', $seat, (string) $pts, 0);
                        $hit     = 'hit';
                        $extra['pts'] = $pts;
                        $dirty   = true;
                    } elseif ($res === 'near') {
                        $hit = 'near'; // private answer only; never broadcast a near miss
                    } else {
                        $post[] = array('c', $seat, $text, 0);
                    }
                } elseif (in_array($s['ph'], array('pick', 'draw'), true) && ($isDr || $guessed)) {
                    // Drawer + players who already know the word: private side-chat, never containing the word.
                    if (!empty($s['w']) && kmpr_nq_check($text, $s['w']) !== '' || (!empty($s['w']) && mb_strpos(kmpr_nq_norm($text), $s['w']['n'][0]) !== false)) {
                        return new WP_Error('spoiler', 'نمی‌شود کلمه را لو داد!');
                    }
                    if ($isDr && $s['ph'] === 'draw') {
                        return new WP_Error('drawer', 'نقاش فقط می‌کشد؛ حرف نزن!');
                    }
                    $post[] = array('c', $seat, $text, 1);
                } else {
                    $post[] = array('c', $seat, $text, 0);
                }
            } elseif ($a === 'start' || $a === 'again') {
                if ((int) $s['host'] !== $seat) {
                    return new WP_Error('host', 'فقط میزبان می‌تواند بازی را شروع کند.');
                }
                if ($a === 'again') {
                    if ($s['ph'] !== 'over') {
                        return false;
                    }
                    $fresh = kmpr_nq_init_state($s['opts']);
                    $fresh['host'] = $s['host'];
                    $fresh['used'] = $s['used'];
                    $fresh['turn'] = $s['turn'];
                    $fresh['sc']   = array();
                    foreach ($r['players'] as $p) {
                        if (empty($p['left'])) {
                            $fresh['sc'][(string) $p['seat']] = 0;
                        }
                    }
                    $r['state'] = $fresh;
                    return $r;
                }
                if ($s['ph'] !== 'lobby') {
                    return false;
                }
                $active = kmpr_nq_active($r, $seen);
                if (count($active) < 2) {
                    return new WP_Error('few', 'حداقل دو بازیکن لازم است.');
                }
                $s['round'] = 0;
                $s['queue'] = array();
                foreach ($active as $as) {
                    $s['sc'][(string) $as] = 0;
                }
                $r['state'] = $s;
                return kmpr_nq_next_turn($r, $active);
            } elseif ($a === 'pick') {
                $i = (int) $req->get_param('i');
                if ($s['ph'] !== 'pick' || (int) $s['dr'] !== $seat || !isset($s['ch'][$i])) {
                    return false;
                }
                $s['w']   = $s['ch'][$i];
                $s['ch']  = array();
                $s['ph']  = 'draw';
                $s['t0']  = $now;
                $s['end'] = $now + (int) $s['opts']['time'];
                $r['state'] = $s;
                return $r;
            } elseif ($a === 'opts') {
                if ((int) $s['host'] !== $seat || $s['ph'] !== 'lobby') {
                    return false;
                }
                $cats = array_keys(kmpr_nq_words()['cats']);
                $rd   = (int) $req->get_param('rounds');
                $tm   = (int) $req->get_param('time');
                $ct   = (string) $req->get_param('cat');
                if ($rd >= 1 && $rd <= 6) {
                    $s['opts']['rounds'] = $rd;
                }
                if (in_array($tm, array(60, 75, 90), true)) {
                    $s['opts']['time'] = $tm;
                }
                if ($ct === 'mix' || in_array($ct, $cats, true)) {
                    $s['opts']['cat'] = $ct;
                }
                $r['state'] = $s;
                return $r;
            } elseif ($a === 'leave') {
                foreach ($r['players'] as $i => $p) {
                    if ((int) $p['seat'] === $seat) {
                        $r['players'][$i]['left'] = true;
                    }
                }
                $post[] = array('l', $seat, '', 0);
                $dirty  = true;
                $seen2  = $seen;
                $seen2[$seat] = 0;
                $r['state'] = $s;
                $t = kmpr_nq_tick($r, $seen2);
                return $t ?: $r;
            }
            $r['state'] = $s;
            $t = kmpr_nq_tick($r, $seen);
            if ($t) {
                return $t;
            }
            return $dirty ? $r : false;
        };

        // Fast path for plain polls: only write when a timer/presence transition is due.
        $new = kmpr_nq_mutate($room, $fn, $seen);
        if (is_wp_error($new)) {
            $codes = array('no_room' => 404, 'busy' => 503, 'rate' => 429, 'host' => 403);
            return kmpr_nq_err($new->get_error_code(), $new->get_error_message(), $codes[$new->get_error_code()] ?? 400);
        }
        foreach ($post as $e) {
            kmpr_nq_event($new['id'], $new['state']['turn'], $e[0], $e[1], $e[2], $e[3]);
        }
        if ($hit) {
            $extra['hit'] = $hit;
        }
        return kmpr_nq_reply($new, $seat, $seen, $since, $cv, $extra);
    };
    register_rest_route($ns, '/games/draw/(?P<code>[A-Za-z0-9]{6})', array(
        array('methods' => 'GET', 'permission_callback' => '__return_true', 'callback' => $handler),
        array('methods' => 'POST', 'permission_callback' => '__return_true', 'callback' => $handler),
    ));
});

/* Hourly cleanup (piggybacks on the shared games cron): events/heartbeats older than 3 h or of deleted rooms. */
add_action('kmpr_games_cleanup', function () {
    global $wpdb;
    $ev = kmpr_nq_ev_table();
    $se = kmpr_nq_seen_table();
    $rt = kmpr_games_rooms_table();
    $cut = time() - 3 * HOUR_IN_SECONDS;
    $wpdb->query($wpdb->prepare("DELETE FROM {$ev} WHERE t < %d", $cut)); // phpcs:ignore
    $wpdb->query($wpdb->prepare("DELETE FROM {$se} WHERE t < %d", $cut)); // phpcs:ignore
    $wpdb->query("DELETE e FROM {$ev} e LEFT JOIN {$rt} r ON r.id = e.room_id WHERE r.id IS NULL"); // phpcs:ignore
}, 20);

/* Smaller polls: raw UTF-8 instead of \uXXXX escapes (Persian chat/names are 3× smaller). */
add_filter('rest_json_encode_options', function ($options, $request) {
    if (strpos((string) $request->get_route(), '/kmpr/v1/games/draw') === 0) {
        $options |= JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
    }
    return $options;
}, 10, 2);

/* Friends «invite to room» (inc/accounts-friends.php): this game has online rooms too. */
add_filter('kmpr_friends_invite_engines', function ($engines) {
    if (kmpr_nq_opt('nq_on')) {
        $engines[] = KMPR_NQ_ENGINE;
    }
    return array_values(array_unique((array) $engines));
});
