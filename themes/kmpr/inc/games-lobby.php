<?php
/**
 * Game Center lobby: live presence («چه کسی آنلاین است») + generated 2D cover art for games without an image.
 *
 * Presence = one short-lived transient (≤ 60 rows, 70s TTL per row). Clients (javascript/games/pwa.js) heartbeat
 * every 25s while the tab is visible and get the online list back in the same request — no extra polling endpoint.
 *
 * @package KMPR
 */

if (!defined('ABSPATH')) {
    exit;
}

const KMPR_PRESENCE_TTL = 70;

add_action('rest_api_init', function () {
    register_rest_route('kmpr/v1', '/games/presence', array(
        'methods'             => 'POST',
        'permission_callback' => '__return_true',
        'callback'            => 'kmpr_games_rest_presence',
    ));
});

function kmpr_games_rest_presence(WP_REST_Request $req)
{
    $pid  = strtolower((string) $req->get_param('player_id'));
    $list = get_transient('kmpr_presence');
    $list = is_array($list) ? $list : array();
    $now  = time();
    foreach ($list as $k => $row) {
        if ($now - (int) $row['t'] > KMPR_PRESENCE_TTL) {
            unset($list[$k]);
        }
    }
    $me = '';
    if (preg_match('/^[0-9a-f-]{36}$/', $pid)) {
        $me    = substr(hash('sha256', $pid), 0, 10); // same public id as leaderboards / lobby
        $nick  = mb_substr(sanitize_text_field((string) $req->get_param('nickname')), 0, 24);
        $where = sanitize_key((string) $req->get_param('where'));
        $where = ($where === 'hub' || isset(kmpr_game_engines()[$where])) ? $where : 'hub';
        if ($nick !== '') {
            $list[$me] = array(
                'n' => $nick,
                'a' => function_exists('kmpr_game_avatar_clean') ? kmpr_game_avatar_clean($req->get_param('avatar')) : array(),
                'w' => $where,
                't' => $now,
            );
        }
        if (count($list) > 60) {
            uasort($list, fn($a, $b) => $b['t'] <=> $a['t']);
            $list = array_slice($list, 0, 60, true);
        }
        set_transient('kmpr_presence', $list, KMPR_PRESENCE_TTL * 2);
    }
    $titles = array();
    $out    = array();
    uasort($list, fn($a, $b) => $b['t'] <=> $a['t']);
    foreach ($list as $k => $row) {
        $w = $row['w'];
        if ($w !== 'hub' && !isset($titles[$w])) {
            $titles[$w] = kmpr_game_engines()[$w]['label'] ?? $w;
            $titles[$w] = preg_replace('/\s*\(.*\)$/u', '', $titles[$w]);
        }
        $out[] = array('id' => $k, 'nick' => $row['n'], 'avatar' => $row['a'], 'where' => $w, 'title' => $w === 'hub' ? 'در لابی' : $titles[$w], 'me' => $k === $me);
    }
    $res = rest_ensure_response(array('ok' => true, 'count' => count($out), 'online' => array_slice($out, 0, 40)));
    $res->header('Cache-Control', 'no-store');
    return $res;
}

/**
 * 2D cover art (inline, no request) for games without a featured image.
 */
function kmpr_game_glyph($engine)
{
    $map = array(
        'korosh-run' => '🕶️', 'tehran-metro' => '🚇', 'qahvechi' => '☕', 'debugger' => '🐛', 'foosball' => '⚽', 'esm-famil' => '✏️', 'rhythm-code' => '🎵', 'math-sprint' => '➗', 'tehran-taxi' => '🚕', 'caspian-fishing' => '🎣', 'jota' => '🚀', 'bug-hunter' => '🐞', 'type-code' => '⌨️', 'stack-memory' => '🧠', 'pixel-runner' => '🏃', 'hex-hunter' => '🎨',
        'snake' => '🐍', 'sky-shooter' => '✈️', 'xo' => '❌', 'rps15' => '✊', 'chess' => '♟️', 'color-race' => '🟧',
        'iran-quiz' => '🧭', 'haft-sin' => '🌱', 'air-hockey' => '🏒', 'penalty' => '⚽', 'basket' => '🏀',
        'slide-puzzle' => '🧩', 'sudoku' => '🔢', 'hanoi' => '🗼',
    );
    return $map[$engine] ?? '🎮';
}

function kmpr_game_art_html($g, $class = '')
{
    return '<span class="gc-art ' . esc_attr($class) . '" style="--accent:' . esc_attr($g['accent_hex']) . '" aria-hidden="true"><span class="gc-art__grid"></span><span class="gc-art__glyph">' . kmpr_game_icon_svg($g['engine']) . '</span></span>';
}

/**
 * Cartoon 2D icons (inline SVG, 64×64, thick dark outline, flat bright fills) — one per engine.
 * Rendered inside .gc-card__icon (accent gradient tile). Emoji glyph stays as the text fallback.
 */
function kmpr_game_icon_svg($engine)
{
    $o = 'stroke="#16120f" stroke-width="3" stroke-linejoin="round" stroke-linecap="round"';
    $i = array(
        'korosh-run'   => '<circle cx="32" cy="34" r="17" fill="#e0a77f" ' . $o . '/><path d="M15 30c0-12 8-17 17-17s17 5 17 17c-4-5-9-7-17-7s-13 2-17 7z" fill="#2a1f1a" ' . $o . '/><rect x="17" y="31" width="12" height="8" rx="3" fill="#111" ' . $o . '/><rect x="35" y="31" width="12" height="8" rx="3" fill="#111" ' . $o . '/><path d="M29 34h6" ' . $o . '/><path d="M19 42c3 8 8 10 13 10s10-2 13-10c-4 3-8 4-13 4s-9-1-13-4z" fill="#3a2a20" ' . $o . '/><circle cx="21" cy="33" r="1.5" fill="#fff"/>',
        'jota'         => '<circle cx="32" cy="30" r="16" fill="#f1f5f9" ' . $o . '/><rect x="21" y="23" width="22" height="13" rx="6" fill="#38bdf8" ' . $o . '/><path d="M24 27c2-2 5-2 7 0" stroke="#fff" stroke-width="2.5" fill="none" stroke-linecap="round"/><path d="M20 44l-4 10h32l-4-10" fill="#f1f5f9" ' . $o . '/><circle cx="50" cy="12" r="3" fill="#fde047"/>',
        'bug-hunter'   => '<ellipse cx="32" cy="35" rx="17" ry="15" fill="#ef4444" ' . $o . '/><path d="M32 20v30" ' . $o . '/><circle cx="32" cy="18" r="7" fill="#16120f"/><circle cx="24" cy="32" r="3" fill="#16120f"/><circle cx="40" cy="38" r="3" fill="#16120f"/><path d="M26 13l-4-5M38 13l4-5M15 30l-6-3M49 30l6-3M15 42l-6 3M49 42l6 3" ' . $o . '/>',
        'type-code'    => '<rect x="8" y="16" width="48" height="32" rx="6" fill="#e2e8f0" ' . $o . '/><rect x="14" y="22" width="8" height="7" rx="2" fill="#fff" ' . $o . '/><rect x="28" y="22" width="8" height="7" rx="2" fill="#fbbf24" ' . $o . '/><rect x="42" y="22" width="8" height="7" rx="2" fill="#fff" ' . $o . '/><rect x="18" y="36" width="28" height="7" rx="2" fill="#fff" ' . $o . '/>',
        'stack-memory' => '<rect x="10" y="18" width="26" height="34" rx="5" fill="#a78bfa" ' . $o . ' transform="rotate(-10 23 35)"/><rect x="28" y="14" width="26" height="34" rx="5" fill="#fff" ' . $o . ' transform="rotate(8 41 31)"/><path d="M37 26l4 6-4 6M45 26l-4 0" stroke="#7c3aed" stroke-width="3" fill="none" stroke-linecap="round"/>',
        'pixel-runner' => '<circle cx="38" cy="14" r="6" fill="#fbbf24" ' . $o . '/><path d="M36 22l-8 12 8 6-4 14M28 34l-10 4M34 28l10 4 6-6" fill="none" ' . $o . ' stroke-width="5"/><path d="M8 56h48" ' . $o . '/>',
        'hex-hunter'   => '<path d="M32 10c14 0 24 9 24 20 0 7-6 9-11 9-4 0-6 3-4 7 2 5-2 8-9 8-14 0-24-10-24-22S18 10 32 10z" fill="#fde68a" ' . $o . '/><circle cx="22" cy="24" r="4" fill="#ef4444" ' . $o . '/><circle cx="34" cy="19" r="4" fill="#22c55e" ' . $o . '/><circle cx="45" cy="26" r="4" fill="#3b82f6" ' . $o . '/><circle cx="21" cy="38" r="4" fill="#a855f7" ' . $o . '/>',
        'snake'        => '<path d="M14 46c0-8 8-10 16-10s16-2 16-10-8-10-14-10" fill="none" stroke="#16120f" stroke-width="13" stroke-linecap="round"/><path d="M14 46c0-8 8-10 16-10s16-2 16-10-8-10-14-10" fill="none" stroke="#4ade80" stroke-width="8" stroke-linecap="round"/><circle cx="28" cy="14" r="2" fill="#16120f"/><path d="M22 16l-5 2" stroke="#ef4444" stroke-width="2.5" stroke-linecap="round"/>',
        'sky-shooter'  => '<path d="M32 8l6 16 16 10v6l-16-4-2 12 6 6v4l-10-3-10 3v-4l6-6-2-12-16 4v-6l16-10z" fill="#e2e8f0" ' . $o . '/><circle cx="32" cy="22" r="3" fill="#38bdf8"/>',
        'xo'           => '<path d="M12 14l16 16M28 14L12 30" stroke="#16120f" stroke-width="9" stroke-linecap="round"/><path d="M12 14l16 16M28 14L12 30" stroke="#ef4444" stroke-width="5" stroke-linecap="round"/><circle cx="42" cy="42" r="10" fill="none" stroke="#16120f" stroke-width="9"/><circle cx="42" cy="42" r="10" fill="none" stroke="#38bdf8" stroke-width="5"/>',
        'rps15'        => '<path d="M18 30c0-6 4-8 7-8h14c4 0 7 3 7 7v9c0 9-6 14-14 14h-3c-7 0-11-5-11-12z" fill="#fcd34d" ' . $o . '/><path d="M25 22v-6c0-2 2-3 3.5-3S32 14 32 16v6M32 22v-7c0-2 2-3 3.5-3s3.5 1 3.5 3v7" fill="#fcd34d" ' . $o . '/><path d="M18 34h10" ' . $o . '/>',
        'chess'        => '<circle cx="32" cy="16" r="7" fill="#f8fafc" ' . $o . '/><path d="M26 23h12l3 18H23z" fill="#f8fafc" ' . $o . '/><rect x="17" y="41" width="30" height="9" rx="3" fill="#f8fafc" ' . $o . '/>',
        'color-race'   => '<path d="M22 10c6 10 12 16 12 23a12 12 0 0 1-24 0c0-7 6-13 12-23z" fill="#fb923c" ' . $o . '/><path d="M44 22c4 7 8 11 8 16a8 8 0 0 1-16 0c0-5 4-9 8-16z" fill="#2dd4bf" ' . $o . '/>',
        'iran-quiz'    => '<circle cx="32" cy="32" r="22" fill="#fef3c7" ' . $o . '/><path d="M32 14l6 18-6 18-6-18z" fill="#ef4444" ' . $o . '/><path d="M32 32l6 0-6 18-6-18z" fill="#94a3b8" stroke="#16120f" stroke-width="3" stroke-linejoin="round"/><circle cx="32" cy="32" r="3" fill="#16120f"/>',
        'haft-sin'     => '<path d="M14 36h36l-4 18H18z" fill="#c084fc" ' . $o . '/><path d="M20 36c0-10 2-18 6-24M28 36c0-12 2-20 4-26M36 36c0-12 0-20 2-26M44 36c0-10-2-18-4-24" stroke="#16120f" stroke-width="6" stroke-linecap="round" fill="none"/><path d="M20 36c0-10 2-18 6-24M28 36c0-12 2-20 4-26M36 36c0-12 0-20 2-26M44 36c0-10-2-18-4-24" stroke="#4ade80" stroke-width="3" stroke-linecap="round" fill="none"/>',
        'air-hockey'   => '<ellipse cx="34" cy="44" rx="16" ry="7" fill="#f43f5e" ' . $o . '/><ellipse cx="34" cy="40" rx="16" ry="7" fill="#fb7185" ' . $o . '/><circle cx="34" cy="34" r="6" fill="#fda4af" ' . $o . '/><ellipse cx="16" cy="18" rx="9" ry="4" fill="#1e293b" ' . $o . '/>',
        'penalty'      => '<circle cx="32" cy="32" r="20" fill="#fff" ' . $o . '/><path d="M32 22l8 6-3 9h-10l-3-9z" fill="#16120f"/><path d="M32 12v10M40 28l9-4M37 37l6 8M27 37l-6 8M24 28l-9-4" stroke="#16120f" stroke-width="2.5"/>',
        'basket'       => '<circle cx="30" cy="34" r="18" fill="#f97316" ' . $o . '/><path d="M12 34h36M30 16v36M17 21c8 6 8 20 0 26M43 21c-8 6-8 20 0 26" fill="none" stroke="#16120f" stroke-width="2.5"/><path d="M40 10h18v5H40z" fill="#ef4444" ' . $o . '/>',
        'slide-puzzle' => '<rect x="10" y="10" width="20" height="20" rx="4" fill="#a78bfa" ' . $o . '/><rect x="34" y="10" width="20" height="20" rx="4" fill="#a78bfa" ' . $o . '/><rect x="10" y="34" width="20" height="20" rx="4" fill="#a78bfa" ' . $o . '/><rect x="34" y="34" width="20" height="20" rx="4" fill="#1e1b4b" stroke="#16120f" stroke-width="3" stroke-dasharray="4 4"/><path d="M44 18v4M18 42v4" stroke="#fff" stroke-width="3" stroke-linecap="round"/>',
        'sudoku'       => '<rect x="10" y="10" width="44" height="44" rx="6" fill="#e0f2fe" ' . $o . '/><path d="M24.7 10v44M39.3 10v44M10 24.7h44M10 39.3h44" stroke="#16120f" stroke-width="2"/><text x="17.3" y="22" font-size="10" font-weight="900" text-anchor="middle" fill="#0369a1" font-family="monospace">4</text><text x="32" y="37" font-size="10" font-weight="900" text-anchor="middle" fill="#0369a1" font-family="monospace">1</text><text x="46.7" y="51" font-size="10" font-weight="900" text-anchor="middle" fill="#0369a1" font-family="monospace">6</text>',
        'hanoi'        => '<path d="M10 54h44" ' . $o . '/><path d="M32 14v40" ' . $o . '/><rect x="14" y="44" width="36" height="9" rx="4" fill="#ef4444" ' . $o . '/><rect x="19" y="35" width="26" height="9" rx="4" fill="#f59e0b" ' . $o . '/><rect x="24" y="26" width="16" height="9" rx="4" fill="#22c55e" ' . $o . '/>',
        'tehran-metro' => '<rect x="12" y="10" width="40" height="38" rx="10" fill="#e2e8f0" ' . $o . '/><rect x="18" y="16" width="28" height="14" rx="4" fill="#38bdf8" ' . $o . '/><circle cx="22" cy="38" r="3.5" fill="#fde047" ' . $o . '/><circle cx="42" cy="38" r="3.5" fill="#fde047" ' . $o . '/><path d="M18 48l-5 8M46 48l5 8" ' . $o . '/><rect x="12" y="30" width="40" height="3" fill="#ef4444"/>',
        'qahvechi'     => '<path d="M14 24h32v14c0 9-7 16-16 16s-16-7-16-16z" fill="#fff" ' . $o . '/><path d="M46 28h4a6 6 0 0 1 0 12h-4" fill="none" ' . $o . '/><path d="M18 30h24" stroke="#92400e" stroke-width="5"/><path d="M24 8c-3 4 3 6 0 10M34 8c-3 4 3 6 0 10" fill="none" ' . $o . '/>',
        'debugger'     => '<rect x="8" y="12" width="36" height="28" rx="5" fill="#1e293b" ' . $o . '/><path d="M15 21l-4 5 4 5M25 21l4 5-4 5" stroke="#22d3ee" stroke-width="3" fill="none" stroke-linecap="round"/><circle cx="42" cy="40" r="10" fill="#e0f2fe" ' . $o . '/><path d="M49 47l7 7" ' . $o . ' stroke-width="5"/><circle cx="42" cy="40" r="3" fill="#ef4444"/>',
        'foosball'     => '<path d="M6 30h52" stroke="#16120f" stroke-width="7" stroke-linecap="round"/><path d="M6 30h52" stroke="#cbd5e1" stroke-width="3.5" stroke-linecap="round"/><circle cx="22" cy="20" r="6" fill="#ef4444" ' . $o . '/><rect x="17" y="25" width="10" height="16" rx="3" fill="#ef4444" ' . $o . '/><circle cx="42" cy="20" r="6" fill="#ef4444" ' . $o . '/><rect x="37" y="25" width="10" height="16" rx="3" fill="#ef4444" ' . $o . '/><circle cx="32" cy="52" r="5" fill="#fff" ' . $o . '/>',
        'rhythm-code' => '<path d="M24 46V16l26-6v30" fill="none" ' . $o . '/><ellipse cx="18" cy="46" rx="8" ry="6" fill="#a855f7" ' . $o . '/><ellipse cx="44" cy="40" rx="8" ry="6" fill="#ec4899" ' . $o . '/><path d="M24 22l26-6" ' . $o . '/>',
        'math-sprint' => '<rect x="10" y="8" width="44" height="48" rx="8" fill="#e0f2fe" ' . $o . '/><rect x="16" y="14" width="32" height="12" rx="3" fill="#0f172a" ' . $o . '/><path d="M20 36h8M24 32v8M38 36h8M20 48l6-6M26 48l-6-6M38 46h8M42 43v.1M42 49v.1" ' . $o . '/>',
        'tehran-taxi' => '<rect x="10" y="24" width="44" height="22" rx="7" fill="#facc15" ' . $o . '/><path d="M18 24l5-10h18l5 10" fill="#facc15" ' . $o . '/><rect x="25" y="17" width="14" height="6" rx="1" fill="#93c5fd"/><rect x="26" y="8" width="12" height="6" rx="2" fill="#16120f"/><circle cx="20" cy="47" r="5" fill="#334155" ' . $o . '/><circle cx="44" cy="47" r="5" fill="#334155" ' . $o . '/><path d="M10 34h44" stroke="#16120f" stroke-width="2" stroke-dasharray="4 4"/>',
        'caspian-fishing' => '<path d="M6 40c8-4 12 4 20 0s12-4 20 0 10 2 12 0v16H6z" fill="#38bdf8" ' . $o . '/><path d="M44 8v24" ' . $o . '/><path d="M44 32a5 5 0 1 1-5 5" fill="none" ' . $o . '/><path d="M14 30c6-6 16-6 22 0-6 6-16 6-22 0zM36 30l7-5v10z" fill="#fb923c" ' . $o . '/><circle cx="20" cy="29" r="1.6" fill="#16120f"/>',
        'esm-famil'    => '<rect x="12" y="8" width="34" height="46" rx="4" fill="#fff" ' . $o . '/><path d="M18 20h20M18 28h20M18 36h12" stroke="#94a3b8" stroke-width="3" stroke-linecap="round"/><path d="M40 50l14-26 5 3-14 26-6 3z" fill="#fbbf24" ' . $o . '/>',
    );
    $body = $i[$engine] ?? '<rect x="10" y="18" width="44" height="28" rx="12" fill="#e2e8f0" ' . $o . '/><circle cx="22" cy="32" r="4" fill="#16120f"/><circle cx="42" cy="28" r="3" fill="#ef4444"/><circle cx="46" cy="35" r="3" fill="#22c55e"/>';
    return '<svg class="gc-ico shrink-0" width="44" height="44" viewBox="0 0 64 64" aria-hidden="true" focusable="false">' . $body . '</svg>';
}
