<?php
/**
 * Online room handlers for chess and «رنگ‌بازی» (color-race).
 *
 * Chess: compact PHP mirror of the JS rules in javascript/games/engines/chess.js — every move is
 * checked against the full legal-move list (castling through check, en passant, promotion, pins),
 * then mate / stalemate / 50-move / threefold / insufficient material end the game.
 * Colour-race: concurrent painting with server timing (3s countdown + 30s) and a paint-rate cap.
 *
 * @package KMPR
 */

if (!defined('ABSPATH')) {
    exit;
}

add_filter('kmpr_game_room_handlers', function ($handlers) {
    $handlers['chess']      = array('init' => 'kmpr_room_chess_init', 'move' => 'kmpr_room_chess_move', 'view' => null);
    $handlers['color-race'] = array('init' => 'kmpr_room_cr_init', 'move' => 'kmpr_room_cr_move', 'view' => null, 'concurrent' => true);
    return $handlers;
});

/* =========================================================================
 * Chess rules (board: 64-char array, index = row*8+file, row 0 = rank 8, white = uppercase)
 * ====================================================================== */
const KMPR_CHESS_START = 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1';

function kmpr_chess_color($p)
{
    return $p === '.' ? '' : (ctype_upper($p) ? 'w' : 'b');
}

function kmpr_chess_sq($n)
{
    if (!is_string($n) || !preg_match('/^[a-h][1-8]$/', $n)) {
        return -1;
    }
    return (8 - (int) $n[1]) * 8 + (ord($n[0]) - 97);
}

function kmpr_chess_name($i)
{
    return 'abcdefgh'[$i & 7] . (8 - ($i >> 3));
}

function kmpr_chess_parse($fen)
{
    $parts = preg_split('/\s+/', trim($fen));
    $b     = array();
    foreach (str_split($parts[0]) as $ch) {
        if ($ch === '/') {
            continue;
        }
        if (ctype_digit($ch)) {
            for ($i = 0; $i < (int) $ch; $i++) {
                $b[] = '.';
            }
        } else {
            $b[] = $ch;
        }
    }
    return array(
        'b'      => $b,
        'turn'   => $parts[1] ?? 'w',
        'castle' => (($parts[2] ?? '-') === '-') ? '' : $parts[2],
        'ep'     => (($parts[3] ?? '-') === '-') ? -1 : kmpr_chess_sq($parts[3]),
        'half'   => (int) ($parts[4] ?? 0),
        'full'   => (int) ($parts[5] ?? 1),
    );
}

function kmpr_chess_fen($s)
{
    $out = '';
    for ($r = 0; $r < 8; $r++) {
        $n = 0;
        for ($f = 0; $f < 8; $f++) {
            $p = $s['b'][$r * 8 + $f];
            if ($p === '.') {
                $n++;
            } else {
                $out .= ($n ? $n : '') . $p;
                $n    = 0;
            }
        }
        $out .= ($n ? $n : '') . ($r < 7 ? '/' : '');
    }
    return $out . ' ' . $s['turn'] . ' ' . ($s['castle'] ?: '-') . ' ' . ($s['ep'] >= 0 ? kmpr_chess_name($s['ep']) : '-') . ' ' . $s['half'] . ' ' . $s['full'];
}

function kmpr_chess_attacked($b, $sq, $by)
{
    if ($sq < 0) {
        return false;
    }
    $r  = $sq >> 3;
    $f  = $sq & 7;
    $W  = $by === 'w';
    $at = function ($rr, $ff) use ($b) {
        return ($rr >= 0 && $rr < 8 && $ff >= 0 && $ff < 8) ? $b[$rr * 8 + $ff] : null;
    };
    $pr = $W ? $r + 1 : $r - 1;
    $pawn = $W ? 'P' : 'p';
    if ($at($pr, $f - 1) === $pawn || $at($pr, $f + 1) === $pawn) {
        return true;
    }
    foreach (array(array(-2, -1), array(-2, 1), array(-1, -2), array(-1, 2), array(1, -2), array(1, 2), array(2, -1), array(2, 1)) as $d) {
        if ($at($r + $d[0], $f + $d[1]) === ($W ? 'N' : 'n')) {
            return true;
        }
    }
    foreach (array(array(-1, -1), array(-1, 0), array(-1, 1), array(0, -1), array(0, 1), array(1, -1), array(1, 0), array(1, 1)) as $d) {
        if ($at($r + $d[0], $f + $d[1]) === ($W ? 'K' : 'k')) {
            return true;
        }
    }
    $slides = array(
        array(array(array(-1, -1), array(-1, 1), array(1, -1), array(1, 1)), $W ? 'B' : 'b'),
        array(array(array(-1, 0), array(1, 0), array(0, -1), array(0, 1)), $W ? 'R' : 'r'),
    );
    $q = $W ? 'Q' : 'q';
    foreach ($slides as $sl) {
        foreach ($sl[0] as $d) {
            $rr = $r + $d[0];
            $ff = $f + $d[1];
            while ($rr >= 0 && $rr < 8 && $ff >= 0 && $ff < 8) {
                $p = $b[$rr * 8 + $ff];
                if ($p !== '.') {
                    if ($p === $sl[1] || $p === $q) {
                        return true;
                    }
                    break;
                }
                $rr += $d[0];
                $ff += $d[1];
            }
        }
    }
    return false;
}

function kmpr_chess_king($b, $c)
{
    $k = array_search($c === 'w' ? 'K' : 'k', $b, true);
    return $k === false ? -1 : (int) $k;
}

function kmpr_chess_pseudo($s)
{
    $b    = $s['b'];
    $turn = $s['turn'];
    $W    = $turn === 'w';
    $out  = array();
    $add  = function ($from, $to, $flag = '', $promo = '') use (&$out) {
        $out[] = array('from' => $from, 'to' => $to, 'flag' => $flag, 'promo' => $promo);
    };
    $KN = array(array(-2, -1), array(-2, 1), array(-1, -2), array(-1, 2), array(1, -2), array(1, 2), array(2, -1), array(2, 1));
    $KG = array(array(-1, -1), array(-1, 0), array(-1, 1), array(0, -1), array(0, 1), array(1, -1), array(1, 0), array(1, 1));
    $DI = array(array(-1, -1), array(-1, 1), array(1, -1), array(1, 1));
    $OR = array(array(-1, 0), array(1, 0), array(0, -1), array(0, 1));
    for ($sq = 0; $sq < 64; $sq++) {
        $p = $b[$sq];
        if ($p === '.' || kmpr_chess_color($p) !== $turn) {
            continue;
        }
        $r     = $sq >> 3;
        $f     = $sq & 7;
        $P     = strtoupper($p);
        $enemy = function ($t) use ($turn) {
            return $t !== '.' && kmpr_chess_color($t) !== $turn;
        };
        if ($P === 'P') {
            $dr   = $W ? -1 : 1;
            $r1   = $r + $dr;
            $last = $W ? 0 : 7;
            $push = function ($to, $flag) use ($r1, $last, $sq, $add) {
                if ($r1 === $last) {
                    foreach (array('q', 'r', 'b', 'n') as $pp) {
                        $add($sq, $to, $flag, $pp);
                    }
                } else {
                    $add($sq, $to, $flag);
                }
            };
            if ($r1 >= 0 && $r1 < 8 && $b[$r1 * 8 + $f] === '.') {
                $push($r1 * 8 + $f, '');
                if ($r === ($W ? 6 : 1) && $b[($r + 2 * $dr) * 8 + $f] === '.') {
                    $add($sq, ($r + 2 * $dr) * 8 + $f, 'd');
                }
            }
            foreach (array(-1, 1) as $df) {
                $ff = $f + $df;
                if ($ff < 0 || $ff > 7 || $r1 < 0 || $r1 > 7) {
                    continue;
                }
                $to = $r1 * 8 + $ff;
                if ($enemy($b[$to])) {
                    $push($to, 'c');
                } elseif ($to === $s['ep']) {
                    $add($sq, $to, 'e');
                }
            }
        } elseif ($P === 'N' || $P === 'K') {
            foreach ($P === 'N' ? $KN : $KG as $d) {
                $rr = $r + $d[0];
                $ff = $f + $d[1];
                if ($rr < 0 || $rr > 7 || $ff < 0 || $ff > 7) {
                    continue;
                }
                $t = $b[$rr * 8 + $ff];
                if ($t === '.') {
                    $add($sq, $rr * 8 + $ff);
                } elseif ($enemy($t)) {
                    $add($sq, $rr * 8 + $ff, 'c');
                }
            }
            if ($P === 'K') {
                $home = $W ? 60 : 4;
                $o    = $W ? 'b' : 'w';
                if ($sq === $home && !kmpr_chess_attacked($b, $home, $o)) {
                    if (strpos($s['castle'], $W ? 'K' : 'k') !== false && $b[$home + 1] === '.' && $b[$home + 2] === '.' && $b[$home + 3] === ($W ? 'R' : 'r') && !kmpr_chess_attacked($b, $home + 1, $o) && !kmpr_chess_attacked($b, $home + 2, $o)) {
                        $add($sq, $home + 2, 'k');
                    }
                    if (strpos($s['castle'], $W ? 'Q' : 'q') !== false && $b[$home - 1] === '.' && $b[$home - 2] === '.' && $b[$home - 3] === '.' && $b[$home - 4] === ($W ? 'R' : 'r') && !kmpr_chess_attacked($b, $home - 1, $o) && !kmpr_chess_attacked($b, $home - 2, $o)) {
                        $add($sq, $home - 2, 'q');
                    }
                }
            }
        } else {
            $dirs = $P === 'B' ? $DI : ($P === 'R' ? $OR : array_merge($DI, $OR));
            foreach ($dirs as $d) {
                $rr = $r + $d[0];
                $ff = $f + $d[1];
                while ($rr >= 0 && $rr < 8 && $ff >= 0 && $ff < 8) {
                    $t = $b[$rr * 8 + $ff];
                    if ($t === '.') {
                        $add($sq, $rr * 8 + $ff);
                    } else {
                        if ($enemy($t)) {
                            $add($sq, $rr * 8 + $ff, 'c');
                        }
                        break;
                    }
                    $rr += $d[0];
                    $ff += $d[1];
                }
            }
        }
    }
    return $out;
}

function kmpr_chess_make($s, $m)
{
    $b   = $s['b'];
    $p   = $b[$m['from']];
    $W   = $s['turn'] === 'w';
    $cap = $m['flag'] === 'e' ? $b[$m['to'] + ($W ? 8 : -8)] : $b[$m['to']];
    $b[$m['to']]   = $m['promo'] ? ($W ? strtoupper($m['promo']) : $m['promo']) : $p;
    $b[$m['from']] = '.';
    if ($m['flag'] === 'e') {
        $b[$m['to'] + ($W ? 8 : -8)] = '.';
    }
    if ($m['flag'] === 'k') {
        $b[$m['from'] + 1] = $b[$m['from'] + 3];
        $b[$m['from'] + 3] = '.';
    }
    if ($m['flag'] === 'q') {
        $b[$m['from'] - 1] = $b[$m['from'] - 4];
        $b[$m['from'] - 4] = '.';
    }
    $castle = $s['castle'];
    if ($p === 'K') {
        $castle = str_replace(array('K', 'Q'), '', $castle);
    }
    if ($p === 'k') {
        $castle = str_replace(array('k', 'q'), '', $castle);
    }
    $rights = array(63 => 'K', 56 => 'Q', 7 => 'k', 0 => 'q');
    foreach (array($m['from'], $m['to']) as $x) {
        if (isset($rights[$x])) {
            $castle = str_replace($rights[$x], '', $castle);
        }
    }
    return array(
        'b'      => $b,
        'turn'   => $W ? 'b' : 'w',
        'castle' => $castle,
        'ep'     => $m['flag'] === 'd' ? (int) (($m['from'] + $m['to']) / 2) : -1,
        'half'   => (strtoupper($p) === 'P' || $cap !== '.') ? 0 : $s['half'] + 1,
        'full'   => $s['full'] + ($W ? 0 : 1),
    );
}

function kmpr_chess_legal($s)
{
    $out = array();
    foreach (kmpr_chess_pseudo($s) as $m) {
        $n = kmpr_chess_make($s, $m);
        if (!kmpr_chess_attacked($n['b'], kmpr_chess_king($n['b'], $s['turn']), $n['turn'])) {
            $out[] = $m;
        }
    }
    return $out;
}

function kmpr_chess_key($s)
{
    return implode('', $s['b']) . $s['turn'] . $s['castle'] . $s['ep'];
}

function kmpr_chess_insufficient($b)
{
    $rest = array_values(array_filter($b, function ($p) {
        return $p !== '.' && strtoupper($p) !== 'K';
    }));
    if (!$rest) {
        return true;
    }
    if (count($rest) === 1 && strpos('NBnb', $rest[0]) !== false) {
        return true;
    }
    $cols = array();
    foreach ($b as $i => $p) {
        if ($p !== '.' && strtoupper($p) !== 'K') {
            if (strtoupper($p) !== 'B') {
                return false;
            }
            $cols[(($i >> 3) + ($i & 7)) % 2] = true;
        }
    }
    return count($cols) === 1;
}

/* ---------- handler ---------- */
function kmpr_room_chess_init($opts, $prev = null)
{
    $s = kmpr_chess_parse(KMPR_CHESS_START);
    return array(
        'fen'    => KMPR_CHESS_START,
        'turn'   => 'w',
        'white'  => $prev ? 1 - (int) ($prev['white'] ?? 0) : 0,
        'uci'    => array(),
        'keys'   => array(kmpr_chess_key($s)),
        'over'   => false,
        'winner' => null,
        'reason' => '',
        'series' => $prev['series'] ?? array(0, 0, 0),
    );
}

function kmpr_room_chess_move($st, $seat, $move)
{
    if (!empty($st['over'])) {
        return new WP_Error('over', 'بازی تمام شده است.');
    }
    $white = (int) ($st['white'] ?? 0);
    $color = $seat === $white ? 'w' : 'b';
    $s     = kmpr_chess_parse($st['fen']);
    if ($s['turn'] !== $color) {
        return new WP_Error('turn', 'نوبت حریف است.');
    }
    $from  = kmpr_chess_sq($move['from'] ?? '');
    $to    = kmpr_chess_sq($move['to'] ?? '');
    $promo = strtolower((string) ($move['promo'] ?? ''));
    $promo = in_array($promo, array('q', 'r', 'b', 'n'), true) ? $promo : '';
    $found = null;
    foreach (kmpr_chess_legal($s) as $m) {
        if ($m['from'] === $from && $m['to'] === $to && $m['promo'] === $promo) {
            $found = $m;
            break;
        }
    }
    if (!$found) {
        return new WP_Error('illegal', 'این حرکت مجاز نیست.');
    }
    $n            = kmpr_chess_make($s, $found);
    $st['fen']    = kmpr_chess_fen($n);
    $st['turn']   = $n['turn'];
    $st['uci'][]  = kmpr_chess_name($from) . kmpr_chess_name($to) . $promo;
    $key          = kmpr_chess_key($n);
    $st['keys'][] = $key;
    $st['keys']   = array_slice($st['keys'], -120);

    $legal  = kmpr_chess_legal($n);
    $reason = '';
    if (!$legal) {
        $reason = kmpr_chess_attacked($n['b'], kmpr_chess_king($n['b'], $n['turn']), $s['turn']) ? 'mate' : 'stalemate';
    } elseif ($n['half'] >= 100) {
        $reason = 'fifty';
    } elseif (kmpr_chess_insufficient($n['b'])) {
        $reason = 'material';
    } elseif (count(array_keys($st['keys'], $key, true)) >= 3) {
        $reason = 'repetition';
    }
    if ($reason) {
        $st['over']   = true;
        $st['reason'] = $reason;
        if ($reason === 'mate') {
            $st['winner'] = $seat;
            $st['series'][$seat]++;
        } else {
            $st['winner'] = 'draw';
            $st['series'][2]++;
        }
    }
    return $st;
}

/* =========================================================================
 * Colour race — shared grid, concurrent painting, server-timed rounds
 * ====================================================================== */
const KMPR_CR_COUNTDOWN = 3;
const KMPR_CR_DURATION  = 30;
const KMPR_CR_RATE      = 12; // max paints per second per player

function kmpr_room_cr_init($opts, $prev = null)
{
    return array(
        'cols'   => 6,
        'rows'   => 6,
        'cells'  => str_repeat('.', 36),
        'counts' => array(0, 0),
        'paints' => array(0, 0),
        'cd'     => KMPR_CR_COUNTDOWN,
        'dur'    => KMPR_CR_DURATION,
        'over'   => false,
        'winner' => null,
        'series' => $prev['series'] ?? array(0, 0, 0),
    );
}

function kmpr_room_cr_finish($st)
{
    $a = substr_count($st['cells'], '0');
    $b = substr_count($st['cells'], '1');
    $st['counts'] = array($a, $b);
    $st['over']   = true;
    $st['winner'] = $a === $b ? 'draw' : ($a > $b ? 0 : 1);
    $st['series'][$a === $b ? 2 : ($a > $b ? 0 : 1)]++;
    return $st;
}

function kmpr_room_cr_move($st, $seat, $move)
{
    if (!empty($st['over'])) {
        return new WP_Error('over', 'این دور تمام شده است.');
    }
    $now   = microtime(true);
    $start = (int) ($st['started_at'] ?? time()) + (int) $st['cd'];
    $end   = $start + (int) $st['dur'];
    if ($now > $end + 0.6) {
        return kmpr_room_cr_finish($st);
    }
    if ($now < $start - 0.4) {
        return new WP_Error('early', 'هنوز شمارش معکوس تمام نشده است.');
    }
    $cells = array_slice((array) ($move['cells'] ?? array()), 0, 16);
    // Allowance: never more than KMPR_CR_RATE paints per elapsed second (+1s burst).
    $allow = (int) floor((max(0, $now - $start) + 1) * KMPR_CR_RATE) - (int) $st['paints'][$seat];
    $mark  = (string) $seat;
    $total = (int) $st['cols'] * (int) $st['rows'];
    foreach ($cells as $c) {
        if ($allow <= 0) {
            break;
        }
        $c = (int) $c;
        if ($c < 0 || $c >= $total || $st['cells'][$c] === $mark) {
            continue;
        }
        $st['cells'][$c] = $mark;
        $st['paints'][$seat]++;
        $allow--;
    }
    $st['counts'] = array(substr_count($st['cells'], '0'), substr_count($st['cells'], '1'));
    return $st;
}
