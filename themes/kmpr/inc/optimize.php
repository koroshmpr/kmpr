<?php
/**
 * Speed, Performance & Head Cleanup Optimizations
 *
 * @package KMPR
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Completely disable emojis for sub-second page performance
 */
function kmpr_disable_emojis()
{
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('admin_print_scripts', 'print_emoji_detection_script');
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('admin_print_styles', 'print_emoji_styles');
    remove_filter('the_content_feed', 'wp_staticize_emoji');
    remove_filter('comment_text_rss', 'wp_staticize_emoji');
    remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
}
add_action('init', 'kmpr_disable_emojis');

/**
 * Remove emoji DNS prefetch hint
 */
function kmpr_disable_emojis_remove_dns_prefetch($urls, $relation_type)
{
    if ('dns-prefetch' === $relation_type) {
        $emoji_svg_url = apply_filters('emoji_svg_url', 'https://s.w.org/images/core/emoji/2/svg/');
        $urls = array_diff($urls, array($emoji_svg_url));
    }
    return $urls;
}
add_filter('wp_resource_hints', 'kmpr_disable_emojis_remove_dns_prefetch', 10, 2);

/**
 * Cleanup redundant scripts, tags, and default WordPress overhead
 */
function kmpr_cleanup_head_scripts()
{
    wp_deregister_script('wp-embed');
    remove_action('wp_head', 'wp_generator');
    remove_action('wp_head', 'wlwmanifest_link');
    remove_action('wp_head', 'rsd_link');
    remove_action('wp_head', 'wp_shortlink_wp_head');
    remove_action('wp_head', 'rest_output_link_wp_head');
    remove_action('wp_head', 'wp_oembed_add_discovery_links');
    remove_action('template_redirect', 'rest_output_link_header', 11);

    // Dequeue classic theme styles
    wp_dequeue_style('classic-theme-styles');

    // Remove WordPress global block SVG filters from body
    remove_action('wp_body_open', 'wp_global_styles_render_svg_filters');
}
add_action('wp_enqueue_scripts', 'kmpr_cleanup_head_scripts', 99);

add_action('after_setup_theme', function () {
    remove_action('wp_body_open', 'wp_global_styles_render_svg_filters');
});

/**
 * Disable XML preprocessor in WP All Import if active
 */
add_filter('is_xml_preprocess_enabled', function ($is_enabled) {
    return false;
}, 10, 1);
