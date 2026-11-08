<?php
/**
 * Enqueue scripts, styles, and font preloading
 *
 * @package KMPR
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Enqueue styles and scripts
 */
function kmpr_theme_scripts()
{
    // Main Tailwind CSS Output (handle: 'style' as in original theme)
    wp_enqueue_style('style', get_stylesheet_directory_uri() . '/public/css/style.css', array(), (string) filemtime(get_template_directory() . '/public/css/style.css'));

    // Bundled Main JS (Alpine.js, Swiper, Axios)
    $ver = (string) filemtime(get_template_directory() . '/public/js/app.js'); // cache-bust on every build
    wp_enqueue_script('main-js', get_template_directory_uri() . '/public/js/app.js', array(), $ver, array(
        'in_footer' => true,
        'strategy'  => 'defer'
    ));

    // Localize Script Data for Search & REST API
    wp_localize_script('main-js', 'jsData', array(
        'root_url' => get_site_url(),
        'theme_url' => get_template_directory_uri(),
        'nonce'    => wp_create_nonce('my-nonce')
    ));
}
add_action('wp_enqueue_scripts', 'kmpr_theme_scripts');

/**
 * Preload critical high-performance WOFF2 fonts for 100/100 Core Web Vitals
 */
function kmpr_theme_preload_fonts()
{
    $font_dir = get_template_directory_uri() . '/public/fonts/yekanBakh/';
    echo '<link rel="preload" href="' . esc_url($font_dir . 'Yekan-Bakh-FaNum-Regular.woff2') . '" as="font" type="font/woff2" crossorigin="anonymous">' . "\n";
    echo '<link rel="preload" href="' . esc_url($font_dir . 'Yekan_Bakh_FaNum_06_Bold.woff2') . '" as="font" type="font/woff2" crossorigin="anonymous">' . "\n";
}
add_action('wp_head', 'kmpr_theme_preload_fonts', 1);

/**
 * app.js is an ES module (code-split chunks are loaded on demand).
 */
add_filter('script_loader_tag', function ($tag, $handle) {
    if ($handle === 'main-js' && strpos($tag, 'type="module"') === false) {
        $tag = str_replace('<script ', '<script type="module" ', $tag);
    }
    return $tag;
}, 10, 2);

/**
 * Motion layer inline at the end of <body>: runs synchronously right after the content is parsed,
 * so split headings / counters / reveal targets are prepared before the first paint (≈4KB, 1.7KB gz).
 */
add_action('wp_footer', function () {
    $file = get_template_directory() . '/public/js/motion.js';
    if (is_readable($file)) {
        echo '<script id="kmpr-motion">' . file_get_contents($file) . "</script>\n";
    }
}, 1);
