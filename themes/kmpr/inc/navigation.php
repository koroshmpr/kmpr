<?php
/**
 * Navigation & Menu Helpers
 *
 * @package KMPR
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Robust Active Menu Item Filter
 * Correctly marks current-menu-item / active based on current page and post type archives
 */
function kmpr_menu_active_classes($classes, $item)
{
    if (!is_array($classes)) {
        $classes = array();
    }

    $request_uri   = $_SERVER['REQUEST_URI'] ?? '';
    $current_url   = home_url($request_uri);
    $item_url      = untrailingslashit($item->url);
    $current_clean = untrailingslashit($current_url);

    // Exact or prefix URL match
    if ($item_url === $current_clean) {
        $classes[] = 'current-menu-item';
        $classes[] = 'active';
    }

    // Services
    if ((is_post_type_archive('services') || is_singular('services') || strpos($request_uri, '/services') !== false) && (strpos($item->url, 'services') !== false)) {
        $classes[] = 'current-menu-item';
        $classes[] = 'active';
    }

    // Portfolio
    if ((is_post_type_archive('portfolio') || is_singular('portfolio') || strpos($request_uri, '/portfolio') !== false) && (strpos($item->url, 'portfolio') !== false)) {
        $classes[] = 'current-menu-item';
        $classes[] = 'active';
    }

    // Blog / Articles
    if ((is_home() || is_singular('post') || is_category() || is_tag() || strpos($request_uri, '/blog') !== false || strpos($request_uri, '/mag') !== false) && (strpos($item->url, 'blog') !== false || strpos($item->url, 'mag') !== false || $item->object_id == get_option('page_for_posts'))) {
        $classes[] = 'current-menu-item';
        $classes[] = 'active';
    }

    // Front page
    if (is_front_page() && ($item_url === untrailingslashit(home_url('/')) || $item_url === home_url('/'))) {
        $classes[] = 'current-menu-item';
        $classes[] = 'active';
    }

    // About Us
    if (strpos($request_uri, 'about') !== false && strpos($item->url, 'about') !== false) {
        $classes[] = 'current-menu-item';
        $classes[] = 'active';
    }

    // Contact Us
    if (strpos($request_uri, 'contact') !== false && strpos($item->url, 'contact') !== false) {
        $classes[] = 'current-menu-item';
        $classes[] = 'active';
    }

    return array_unique($classes);
}
add_filter('nav_menu_css_class', 'kmpr_menu_active_classes', 10, 2);
