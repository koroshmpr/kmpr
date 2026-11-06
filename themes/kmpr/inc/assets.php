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
    // Main Tailwind CSS Output
    wp_enqueue_style('kmpr-style', get_stylesheet_directory_uri() . '/public/css/style.css', array(), '2.0.0');

    // Bundled Main JS (Alpine.js, Swiper, Axios)
    wp_enqueue_script('kmpr-main-js', get_template_directory_uri() . '/public/js/app.js', array(), '2.0.0', array(
        'in_footer' => true,
        'strategy'  => 'defer'
    ));

    // Localize Script Data for Search & REST API
    wp_localize_script('kmpr-main-js', 'jsData', array(
        'root_url' => get_site_url(),
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
