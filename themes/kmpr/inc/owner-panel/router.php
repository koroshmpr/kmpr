<?php
/**
 * Owner panel — init-level router (same pattern as the arcade PWA in inc/games-pwa.php; no rewrite rules,
 * so nothing to flush on deploy).
 *
 *   /panel/                       SPA shell (any /panel/<screen>/… path renders the same shell)
 *   /panel/manifest.webmanifest   PWA manifest (scope /panel/)
 *   /panel/sw.js                  service worker (static assets only — API data is never cached)
 *   /panel/nonce                  fresh wp_rest nonce for long-lived tabs / installed app
 *
 * Never indexed (X-Robots-Tag + meta robots), never counted as a view (exits before template_redirect),
 * not a post so it never reaches sitemaps / llms.txt; speculation rules exclude it in admin.php.
 *
 * @package KMPR
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('init', 'kmpr_panel_route', 20);

function kmpr_panel_route()
{
    if (is_admin() || wp_doing_ajax() || wp_doing_cron() || (defined('REST_REQUEST') && REST_REQUEST)) {
        return;
    }
    $path = (string) wp_parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    $base = kmpr_panel_base();
    if ($path === rtrim($base, '/')) {
        wp_safe_redirect($base, 301);
        exit;
    }
    if (strpos($path, $base) !== 0) {
        return;
    }
    $rest = substr($path, strlen($base));

    header('X-Robots-Tag: noindex, nofollow, noarchive', true);

    if ($rest === 'manifest.webmanifest') {
        status_header(200);
        header('Content-Type: application/manifest+json; charset=utf-8');
        header('Cache-Control: public, max-age=3600');
        echo wp_json_encode(kmpr_panel_manifest(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($rest === 'sw.js') {
        $src = get_template_directory() . '/public/js/owner-panel-sw.js';
        status_header(200);
        header('Content-Type: application/javascript; charset=utf-8');
        header('Service-Worker-Allowed: ' . $base);
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('X-Content-Type-Options: nosniff');
        echo 'self.__PANEL=' . wp_json_encode(array(
            'v'      => kmpr_panel_asset_version(),
            'scope'  => $base,
            'assets' => (string) wp_parse_url(get_template_directory_uri() . '/public/', PHP_URL_PATH),
        ), JSON_UNESCAPED_SLASHES) . ";\n";
        if (file_exists($src)) {
            readfile($src);
        }
        exit;
    }

    nocache_headers();
    header('Cache-Control: no-store, private, max-age=0', true);
    header('Referrer-Policy: same-origin');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');

    if ($rest === 'nonce') {
        header('Content-Type: application/json; charset=utf-8');
        if (!kmpr_panel_can()) {
            status_header(401);
            echo '{"ok":false}';
            exit;
        }
        status_header(200);
        echo wp_json_encode(array('ok' => true, 'nonce' => wp_create_nonce('wp_rest')));
        exit;
    }

    if (!is_user_logged_in()) {
        $home_path = rtrim((string) wp_parse_url(home_url('/'), PHP_URL_PATH), '/');
        $back      = home_url(substr($path, strlen($home_path)));
        wp_safe_redirect(wp_login_url($back));
        exit;
    }
    if (!kmpr_panel_can()) {
        status_header(403);
        header('Content-Type: text/html; charset=utf-8');
        echo '<!doctype html><html lang="fa" dir="rtl"><meta charset="utf-8"><meta name="robots" content="noindex"><meta name="viewport" content="width=device-width,initial-scale=1"><title>دسترسی ندارید</title><body style="font-family:system-ui;background:#020617;color:#e2e8f0;display:grid;place-items:center;min-height:100vh;margin:0"><p>این بخش فقط برای مدیر سایت است. <a style="color:#fbbf24" href="' . esc_url(home_url('/')) . '">بازگشت به سایت</a></p></body></html>';
        exit;
    }

    status_header(200);
    header('Content-Type: text/html; charset=utf-8');
    kmpr_panel_shell();
    exit;
}

/** Cache-busting version of the built panel assets. */
function kmpr_panel_asset_version()
{
    $dir = get_template_directory() . '/public/';
    $sig = '';
    foreach (array('js/owner-panel.js', 'css/owner-panel.css', 'js/owner-panel-sw.js') as $f) {
        $sig .= file_exists($dir . $f) ? $f . filemtime($dir . $f) : '';
    }
    return substr(md5($sig . KMPR_VERSION), 0, 10);
}

function kmpr_panel_asset($rel)
{
    $file = get_template_directory() . '/public/' . $rel;
    return get_template_directory_uri() . '/public/' . $rel . '?v=' . (file_exists($file) ? filemtime($file) : KMPR_VERSION);
}

function kmpr_panel_manifest()
{
    $base = kmpr_panel_base();
    $img  = get_template_directory_uri() . '/public/images/panel/';
    return array(
        'id'               => $base,
        'name'             => 'پنل مدیریت KMPR',
        'short_name'       => 'پنل KMPR',
        'description'      => 'مدیریت گیم‌سنتر، درخواست‌ها و تنظیمات kmpr.ir',
        'lang'             => 'fa-IR',
        'dir'              => 'rtl',
        'start_url'        => $base,
        'scope'            => $base,
        'display'          => 'standalone',
        'orientation'      => 'any',
        'background_color' => '#020617',
        'theme_color'      => '#020617',
        'icons'            => array(
            array('src' => $img . 'icon-192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'),
            array('src' => $img . 'icon-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'),
            array('src' => $img . 'maskable-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'),
        ),
        'shortcuts'        => array(
            array('name' => 'بازیکنان', 'url' => $base . 'players/'),
            array('name' => 'آنلاین', 'url' => $base . 'online/'),
            array('name' => 'درخواست‌ها', 'url' => $base . 'requests/'),
        ),
    );
}

/** Data the SPA needs before its first API call. */
function kmpr_panel_boot()
{
    $u = wp_get_current_user();
    return array(
        'base'     => kmpr_panel_base(),
        'rest'     => esc_url_raw(rest_url('kmpr/v1/panel/')),
        'nonce'    => wp_create_nonce('wp_rest'),
        'nonceUrl' => kmpr_panel_url('nonce'),
        'sw'       => kmpr_panel_url('sw.js'),
        'site'     => home_url('/'),
        'admin'    => admin_url(),
        'logout'   => wp_logout_url(kmpr_panel_url()),
        'user'     => $u->display_name,
        'avatar'   => get_avatar_url($u->ID, array('size' => 64)),
        'badges'   => kmpr_panel_badges(),
    );
}

function kmpr_panel_shell()
{
    $boot = kmpr_panel_boot();
    $font = get_template_directory_uri() . '/public/fonts/yekanBakh/';
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
<meta name="apple-mobile-web-app-title" content="پنل KMPR">
<title>پنل مدیریت</title>
<link rel="manifest" href="<?= esc_url(kmpr_panel_url('manifest.webmanifest')); ?>">
<link rel="icon" href="<?= esc_url(get_template_directory_uri() . '/public/images/panel/icon.svg'); ?>" type="image/svg+xml">
<link rel="apple-touch-icon" href="<?= esc_url(get_template_directory_uri() . '/public/images/panel/apple-touch-icon.png'); ?>">
<link rel="preload" href="<?= esc_url($font . 'Yekan-Bakh-FaNum-Regular.woff2'); ?>" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="<?= esc_url($font . 'Yekan_Bakh_FaNum_06_Bold.woff2'); ?>" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="<?= esc_url(kmpr_panel_asset('css/owner-panel.css')); ?>">
<script id="op-boot" type="application/json"><?= wp_json_encode($boot, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
<script src="<?= esc_url(kmpr_panel_asset('js/owner-panel.js')); ?>" defer></script>
</head>
<body>
<div id="op-app" class="op-app" aria-busy="true">
    <div class="op-boot-splash" role="status"><span class="op-spinner" aria-hidden="true"></span><span>در حال بارگذاری پنل…</span></div>
</div>
<noscript><p style="padding:24px;color:#e2e8f0">برای استفاده از پنل، جاوااسکریپت مرورگر را فعال کنید. <a style="color:#fbbf24" href="<?= esc_url(admin_url()); ?>">پیشخوان وردپرس</a></p></noscript>
</body>
</html>
    <?php
}
