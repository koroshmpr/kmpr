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
 * Fonts: preload every weight the layout uses (4 × ~19KB woff2) and declare @font-face inline, so the
 * font-display strategy is switchable from «تنظیمات کلی سایت → سرعت و انیمیشن».
 * Default "optional": text never re-flows when the web font arrives late (the cause of the page-start jump);
 * the font is cached for the next page. Weight map: 400 Regular, 500–600 Medium, 700 Bold, 800–900 Heavy.
 */
function kmpr_font_faces()
{
    return array(
        '400'     => 'Yekan-Bakh-FaNum-Regular',
        '500 600' => 'Yekan_Bakh_FaNum_05_Medium',
        '700'     => 'Yekan_Bakh_FaNum_06_Bold',
        '800 900' => 'Yekan_Bakh_FaNum_07_Heavy',
    );
}

function kmpr_theme_preload_fonts()
{
    $font_dir = get_template_directory_uri() . '/public/fonts/yekanBakh/';
    $display  = function_exists('kmpr_field') ? kmpr_field('font_display', 'optional', 'option') : 'optional';
    $display  = in_array($display, array('optional', 'swap'), true) ? $display : 'optional';
    $css      = '';
    foreach (kmpr_font_faces() as $weight => $file) {
        echo '<link rel="preload" href="' . esc_url($font_dir . $file . '.woff2') . '" as="font" type="font/woff2" crossorigin="anonymous">' . "\n";
        $css .= '@font-face{font-family:yekanBakh;font-style:normal;font-weight:' . $weight . ';font-display:' . $display
            . ';src:url(' . esc_url($font_dir . $file . '.woff2') . ') format("woff2"),url(' . esc_url($font_dir . $file . '.woff') . ') format("woff")}';
    }
    echo '<style id="kmpr-fonts">' . $css . "</style>\n";

    // Dashboard switches for motion (see header.php bootstrap + javascript/motion.js).
    if (function_exists('kmpr_option_on') && !kmpr_option_on('page_transitions')) {
        echo "<style>@view-transition{navigation:none}</style>\n";
    }
    if (function_exists('kmpr_option_on') && !kmpr_option_on('motion_entrances')) {
        echo "<script>window.KMPR_NO_MOTION=true</script>\n";
    }
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
