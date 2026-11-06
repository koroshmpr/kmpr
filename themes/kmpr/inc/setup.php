<?php
/**
 * Theme Setup & Core Configurations
 *
 * @package KMPR
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Sets up theme defaults and registers support for various WordPress features.
 */
function theme_setup()
{
    // Add default title tag support
    add_theme_support('title-tag');

    // Enable support for Post Thumbnails on posts and pages
    add_theme_support('post-thumbnails');

    // Add support for custom logo
    add_theme_support('custom-logo', array(
        'height'      => 250,
        'width'       => 250,
        'flex-width'  => true,
        'flex-height' => true,
    ));

    // Register Navigation Menus
    register_nav_menu('headerMenuLocation', 'منوی اصلی');
    register_nav_menu('topHeaderMenuLocation', 'منوی گوشی');
    register_nav_menu('footerLocationOne', 'منوی اول فوتر');
    register_nav_menu('footerLocationTwo', 'منوی دوم فوتر');
    register_nav_menu('footerLocationThree', 'منوی سوم فوتر');

    // Enable WooCommerce support
    add_theme_support('woocommerce');
}
add_action('after_setup_theme', 'theme_setup');

/**
 * Register ACF Options Pages (Exact original configuration to preserve all data)
 */
function my_acf_op_init()
{
    if (function_exists('acf_add_options_page')) {
        $parent = acf_add_options_page(array(
            'page_title' => __('Theme General Settings'),
            'menu_title' => __('Theme Settings'),
            'redirect'   => false,
        ));
        acf_add_options_page(array(
            'page_title'  => __('Contact and Social'),
            'menu_title'  => __('Contact and Social'),
            'parent_slug' => $parent['menu_slug'],
        ));
        acf_add_options_page(array(
            'page_title'  => __('مدیریت رزومه آنلاین'),
            'menu_title'  => __('رزومه من'),
            'parent_slug' => $parent['menu_slug'],
        ));
    }
}
add_action('acf/init', 'my_acf_op_init');

/**
 * Populate ACF select field options with Gravity Forms
 */
function acf_populate_gf_forms_ids($field)
{
    if (class_exists('GFFormsModel')) {
        $choices = [];
        foreach (\GFFormsModel::get_forms() as $form) {
            $choices[$form->id] = $form->title;
        }
        $field['choices'] = $choices;
    }
    return $field;
}
add_filter('acf/load_field/name=gravity_choices', 'acf_populate_gf_forms_ids');

/**
 * Helper to add custom CSS classes to nav menu link attributes
 */
function add_menu_link_class($classes, $item, $args)
{
    if (isset($args->link_class)) {
        $classes['class'] = $args->link_class;
    }
    return $classes;
}
add_filter('nav_menu_link_attributes', 'add_menu_link_class', 1, 3);

/**
 * Helper function to find a menu item in an array of items
 */
function wpd_get_menu_item($field, $object_id, $items)
{
    foreach ($items as $item) {
        if ($item->$field == $object_id) {
            return $item;
        }
    }
    return false;
}
