<?php
/**
 * KMPR Arcade PWA — scoped to /games/ only.
 *  - /games/manifest.webmanifest  (separate manifest; replaces the site manifest on game pages)
 *  - /games/sw.js                 (service worker, scope /games/, source: public/js/arcade-sw.js + injected precache list)
 *  - /games/offline/              (tiny self-contained offline page)
 *  - POST kmpr/v1/games/pwa-stat  (install funnel counters: prompt / accepted / dismissed / installed)
 * Served from PHP at `init` priority 0 (same approach as /site.webmanifest) → no rewrite rules or flush needed.
 *
 * @package KMPR
 */

if (!defined('ABSPATH')) {
    exit;
}

function kmpr_arcade_base()
{
    return rtrim((string) parse_url(home_url('/'), PHP_URL_PATH), '/') . '/games/';
}

/**
 * Cache version: changes whenever any arcade asset changes.
 */
function kmpr_arcade_assets()
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $dir   = get_template_directory();
    $uri   = get_template_directory_uri();
    $files = array('/public/js/games/index.js', '/public/css/style.css', '/public/js/arcade-sw.js');
    foreach ((array) glob($dir . '/public/js/games/chunks/*.js') as $f) {
        $files[] = '/public/js/games/chunks/' . basename($f);
    }
    foreach (array('Yekan-Bakh-FaNum-Regular', 'Yekan_Bakh_FaNum_06_Bold', 'Yekan_Bakh_FaNum_07_Heavy') as $font) {
        $files[] = '/public/fonts/yekanBakh/' . $font . '.woff2';
    }
    foreach (array('icon-192', 'maskable-192', 'apple-touch-icon') as $i) {
        $files[] = '/public/images/arcade/' . $i . '.png';
    }
    $urls = array();
    $sig  = '';
    foreach ($files as $f) {
        if (file_exists($dir . $f)) {
            // Same URL the page enqueues (style.css / index.js carry ?ver=filemtime).
            $urls[] = $uri . $f . (in_array($f, array('/public/js/games/index.js', '/public/css/style.css'), true) ? '?ver=' . filemtime($dir . $f) : '');
            $sig   .= $f . filemtime($dir . $f);
        }
    }
    $cache = array('urls' => $urls, 'version' => substr(md5($sig . KMPR_GAMES_DB_VERSION), 0, 10));
    return $cache;
}

add_action('init', function () {
    $path = (string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    $base = kmpr_arcade_base();
    if (strpos($path, $base) !== 0) {
        return;
    }
    $rest = substr($path, strlen($base));

    if ($rest === 'manifest.webmanifest') {
        status_header(200);
        header('Content-Type: application/manifest+json; charset=utf-8');
        header('Cache-Control: public, max-age=3600');
        echo wp_json_encode(kmpr_arcade_manifest(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($rest === 'sw.js') {
        $src = get_template_directory() . '/public/js/arcade-sw.js';
        status_header(200);
        header('Content-Type: application/javascript; charset=utf-8');
        header('Service-Worker-Allowed: ' . $base);
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('X-Content-Type-Options: nosniff');
        $a = kmpr_arcade_assets();
        echo 'self.__ARCADE = ' . wp_json_encode(array(
            'version'  => $a['version'],
            'scope'    => $base,
            'offline'  => $base . 'offline/',
            'precache' => array_merge($a['urls'], array($base . 'offline/', $base)),
            'theme'    => (string) parse_url(get_template_directory_uri(), PHP_URL_PATH),
            'uploads'  => (string) parse_url(wp_get_upload_dir()['baseurl'], PHP_URL_PATH),
        ), JSON_UNESCAPED_SLASHES) . ";\n";
        if (file_exists($src)) {
            readfile($src);
        }
        exit;
    }

    if ($rest === 'offline' || $rest === 'offline/') {
        status_header(200);
        header('Content-Type: text/html; charset=utf-8');
        header('Cache-Control: public, max-age=3600');
        kmpr_arcade_offline_page();
        exit;
    }
}, 0);

function kmpr_arcade_manifest()
{
    $base  = kmpr_arcade_base();
    $img   = get_template_directory_uri() . '/public/images/arcade/';
    $icons = array(
        array('src' => $img . 'icon-192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'),
        array('src' => $img . 'icon-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'),
        array('src' => $img . 'maskable-192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'maskable'),
        array('src' => $img . 'maskable-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'),
    );
    $shortcuts = array();
    $games     = get_posts(array('post_type' => 'game', 'post_status' => 'publish', 'posts_per_page' => 4, 'orderby' => array('menu_order' => 'ASC', 'date' => 'DESC'), 'fields' => 'ids', 'no_found_rows' => true));
    foreach ($games as $id) {
        $shortcuts[] = array(
            'name'       => get_the_title($id),
            'short_name' => mb_substr(get_the_title($id), 0, 12),
            'url'        => add_query_arg('source', 'pwa', wp_make_link_relative(get_permalink($id))),
            'icons'      => array(array('src' => $img . 'icon-96.png', 'sizes' => '96x96', 'type' => 'image/png')),
        );
    }
    $shots = array();
    foreach (array(array('screen-narrow.png', '780x1688', 'narrow', 'گیم‌سنتر روی موبایل'), array('screen-wide.png', '1440x900', 'wide', 'گیم‌سنتر روی دسکتاپ')) as $s) {
        if (file_exists(get_template_directory() . '/public/images/arcade/' . $s[0])) {
            $shots[] = array('src' => $img . $s[0], 'sizes' => $s[1], 'type' => 'image/png', 'form_factor' => $s[2], 'label' => $s[3]);
        }
    }
    return array(
        'id'               => $base,
        'name'             => 'KMPR Arcade',
        'short_name'       => 'KMPR Arcade',
        'description'      => 'گیم‌سنتر کوروش محمدپور: بازی‌های مرورگری، آنلاین با دوستان و جدول برترین‌ها.',
        'lang'             => 'fa-IR',
        'dir'              => 'rtl',
        'start_url'        => $base . '?source=pwa',
        'scope'            => $base,
        'display'          => 'standalone',
        'display_override' => array('window-controls-overlay', 'standalone'),
        'orientation'      => 'any',
        'background_color' => '#020617',
        'theme_color'      => '#020617',
        'categories'       => array('games', 'entertainment'),
        'icons'            => $icons,
        'shortcuts'        => $shortcuts,
        'screenshots'      => $shots,
    );
}

/**
 * Game pages: swap the site manifest for the arcade one (theme-color from seo.php is kept, not duplicated).
 */
add_action('wp_head', function () {
    if (function_exists('kmpr_is_games_page') && kmpr_is_games_page()) {
        ob_start();
    }
}, 2);
add_action('wp_head', function () {
    if (!function_exists('kmpr_is_games_page') || !kmpr_is_games_page()) {
        return;
    }
    $html = (string) ob_get_clean();
    echo preg_replace('~<link rel="manifest"[^>]*site\.webmanifest[^>]*>\s*~', '', $html); // phpcs:ignore -- core/theme head output
    $img = get_template_directory_uri() . '/public/images/arcade/';
    echo '<link rel="manifest" href="' . esc_url(home_url('/games/manifest.webmanifest')) . '">' . "\n";
    echo '<link rel="apple-touch-icon" href="' . esc_url($img . 'apple-touch-icon.png') . '">' . "\n";
    echo '<meta name="apple-mobile-web-app-capable" content="yes">' . "\n";
    echo '<meta name="mobile-web-app-capable" content="yes">' . "\n";
    echo '<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">' . "\n";
    echo '<meta name="apple-mobile-web-app-title" content="KMPR Arcade">' . "\n";
}, 4);

function kmpr_arcade_offline_page()
{
    $base = esc_url(home_url('/games/'));
    $font = esc_url(get_template_directory_uri() . '/public/fonts/yekanBakh/Yekan_Bakh_FaNum_06_Bold.woff2');
    ?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex">
<meta name="theme-color" content="#020617">
<title>آفلاین — KMPR Arcade</title>
<style>
@font-face{font-family:yb;src:url(<?= $font; ?>) format('woff2');font-weight:700;font-display:swap}
*{box-sizing:border-box;margin:0}
body{min-height:100svh;display:grid;place-items:center;padding:24px;background:radial-gradient(ellipse at 30% 0%,rgba(217,119,6,.25),transparent 60%),#020617;color:#e2e8f0;font-family:yb,Tahoma,sans-serif;text-align:center}
.c{max-width:26rem;display:flex;flex-direction:column;gap:14px;align-items:center}
.i{width:72px;height:72px;border-radius:22px;display:grid;place-items:center;background:linear-gradient(135deg,#fbbf24,#d97706);color:#020617;box-shadow:0 0 40px -6px #f59e0b}
h1{font-size:1.6rem;color:#fff}
p{color:#cbd5e1;line-height:1.9}
a,button{display:inline-flex;align-items:center;justify-content:center;min-height:48px;padding:0 28px;border-radius:999px;font:inherit;font-weight:700;text-decoration:none;border:0;cursor:pointer}
.p{background:#f59e0b;color:#020617}.g{background:rgba(255,255,255,.06);color:#e2e8f0;border:1px solid rgba(255,255,255,.15)}
.r{display:flex;gap:10px;flex-wrap:wrap;justify-content:center}
</style>
</head>
<body>
<main class="c">
    <span class="i" aria-hidden="true"><svg width="40" height="40" viewBox="0 0 24 24" fill="currentColor"><path d="M7 7h10a5 5 0 0 1 4.9 6l-.8 4a2.6 2.6 0 0 1-4.4 1.3L14.8 16H9.2l-1.9 2.3A2.6 2.6 0 0 1 2.9 17l-.8-4A5 5 0 0 1 7 7Zm0 3v1.25H5.75v1.5H7V14h1.5v-1.25h1.25v-1.5H8.5V10H7Zm9.25.25a1 1 0 1 0 0 2 1 1 0 0 0 0-2Zm2 2a1 1 0 1 0 0 2 1 1 0 0 0 0-2Z"/></svg></span>
    <h1>فعلاً آفلاین هستید</h1>
    <p>به اینترنت وصل نیستید. بازی‌های تک‌نفره‌ای که قبلاً باز کرده‌اید آفلاین هم کار می‌کنند؛ جدول برترین‌ها و بازی آنلاین بعد از اتصال برمی‌گردند.</p>
    <div class="r">
        <button class="p" onclick="location.reload()">تلاش دوباره</button>
        <a class="g" href="<?= $base; ?>">خانه گیم‌سنتر</a>
    </div>
</main>
</body>
</html>
    <?php
}

/* =========================================================================
 * Install funnel counters (tiny option, no per-event rows)
 * ====================================================================== */
add_action('rest_api_init', function () {
    register_rest_route('kmpr/v1', '/games/pwa-stat', array(
        'methods'             => 'POST',
        'permission_callback' => '__return_true',
        'callback'            => function (WP_REST_Request $req) {
            $ev = sanitize_key((string) $req->get_param('event'));
            if (!in_array($ev, array('prompt', 'accepted', 'dismissed', 'installed', 'ios_sheet'), true)) {
                return new WP_REST_Response(array('ok' => false), 400);
            }
            if (function_exists('kmpr_room_rate') && !kmpr_room_rate('pwa', 30, HOUR_IN_SECONDS)) {
                return new WP_REST_Response(array('ok' => false, 'code' => 'rate'), 429);
            }
            $s      = (array) get_option('kmpr_arcade_pwa_stats', array());
            $s[$ev] = (int) ($s[$ev] ?? 0) + 1;
            update_option('kmpr_arcade_pwa_stats', $s, false);
            return array('ok' => true);
        },
    ));
});

function kmpr_arcade_pwa_stats_line()
{
    $s = (array) get_option('kmpr_arcade_pwa_stats', array());
    $n = fn($k) => number_format_i18n((int) ($s[$k] ?? 0));
    return 'نصب اپ (PWA): پیشنهاد نصب ' . $n('prompt') . ' · پذیرفته ' . $n('accepted') . ' · رد شده ' . $n('dismissed') . ' · نصب‌شده ' . $n('installed') . ' · راهنمای iOS ' . $n('ios_sheet');
}

/**
 * Instant navigation inside the Game Center:
 * - Speculation Rules: prefetch game-center links on hover, prerender on hover-intent (Chromium; ignored elsewhere).
 * - Cross-document View Transitions: a short cross-fade instead of a white flash (Chromium 126+, Safari 18.2+).
 * - modulepreload of the arcade entry so it starts with the HTML.
 * Analytics in arcade-monetize.js wait for `prerenderingchange`, so prerendered pages are not counted until shown.
 */
add_action('wp_head', function () {
    if (!function_exists('kmpr_is_games_page') || !kmpr_is_games_page() || is_user_logged_in()) {
        return;
    }
    $base  = wp_parse_url(get_post_type_archive_link('game'), PHP_URL_PATH) ?: '/games/';
    $rules = array(
        'prerender' => array(array('where' => array('and' => array(array('href_matches' => $base . '*'), array('not' => array('href_matches' => '*\\?*')), array('not' => array('href_matches' => '*.webmanifest')))), 'eagerness' => 'moderate')),
        'prefetch'  => array(array('where' => array('href_matches' => $base . '*'), 'eagerness' => 'moderate')),
    );
    echo '<script type="speculationrules">' . wp_json_encode($rules, JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
}, 3);
add_action('wp_head', function () {
    if (!function_exists('kmpr_is_games_page') || !kmpr_is_games_page()) {
        return;
    }
    echo '<style>@view-transition{navigation:auto}::view-transition-old(root){animation:kvt-out .22s cubic-bezier(.4,0,1,1) both}::view-transition-new(root){animation:kvt-in .38s cubic-bezier(.22,1,.36,1) .04s both}@keyframes kvt-out{to{opacity:0;transform:scale(.985);filter:blur(4px)}}@keyframes kvt-in{from{opacity:0;transform:scale(1.015)}}@media (prefers-reduced-motion:reduce){@view-transition{navigation:none}}</style>' . "\n";
    $entry = get_template_directory() . '/public/js/games/index.js';
    if (file_exists($entry)) {
        echo '<link rel="modulepreload" href="' . esc_url(get_template_directory_uri() . '/public/js/games/index.js?ver=' . filemtime($entry)) . '">' . "\n";
    }
}, 4);
