<?php
/**
 * Performance: app-like navigation without extra JS weight.
 * - Core speculative loading (WP 6.8+): prefetch same-site links on hover (moderate eagerness).
 * - Speculative (prefetch/prerender) requests never count as views.
 * - Cross-document View Transitions are enabled in CSS (tailwind/custom/motion.css).
 *
 * @package KMPR
 */

if (!defined('ABSPATH')) {
    exit;
}

function kmpr_is_speculative_request()
{
    $purpose = strtolower($_SERVER['HTTP_SEC_PURPOSE'] ?? ($_SERVER['HTTP_PURPOSE'] ?? ''));
    return $purpose !== '' && strpos($purpose, 'prefetch') !== false;
}

add_filter('wp_speculation_rules_configuration', function ($config) {
    if (!is_array($config) || is_user_logged_in()) {
        return $config; // keep core defaults (or disabled) for logged-in users
    }
    return array('mode' => 'prefetch', 'eagerness' => 'moderate');
});

// Never prefetch action/utility URLs.
add_filter('wp_speculation_rules_href_exclude_paths', function ($paths) {
    return array_merge((array) $paths, array('/wp-json/*', '/*?*s=*', '/feed/*', '/*/feed/*'));
});

/**
 * Disk budget (1 GB host): don't generate the two largest intermediate sizes.
 * The theme never requests them by name; responsive srcset still falls back to the original upload.
 */
add_filter('intermediate_image_sizes_advanced', function ($sizes) {
    unset($sizes['1536x1536'], $sizes['2048x2048']);
    return $sizes;
});

/**
 * Full-page screenshots (portfolio) are very tall: WordPress would shrink them to 2560px tall,
 * making them unreadably narrow. Keep the original only for images taller than 2× their width.
 */
add_filter('big_image_size_threshold', function ($threshold, $imagesize) {
    if (is_array($imagesize) && !empty($imagesize[0]) && !empty($imagesize[1]) && $imagesize[1] > 2 * $imagesize[0]) {
        return false;
    }
    return $threshold;
}, 10, 2);
