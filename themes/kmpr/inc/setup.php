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
 * Register ACF Options Pages
 * Uses exact English slugs matching database location rules to ensure zero errors
 */
function my_acf_op_init()
{
    if (function_exists('acf_add_options_page')) {
        $parent = acf_add_options_page(array(
            'page_title' => __('تنظیمات سایت'),
            'menu_title' => __('تنظیمات سایت'),
            'menu_slug'  => 'acf-options-theme-settings',
            'capability' => 'edit_posts',
            'redirect'   => false,
            'icon_url'   => 'dashicons-admin-generic',
        ));

        acf_add_options_sub_page(array(
            'page_title'  => __('Contact and Social'),
            'menu_title'  => __('تماس و شبکه‌های اجتماعی'),
            'menu_slug'   => 'acf-options-contact-and-social',
            'parent_slug' => 'acf-options-theme-settings',
        ));

        acf_add_options_sub_page(array(
            'page_title'  => __('تنظیمات و مدیریت رزومه آنلاین'),
            'menu_title'  => __('تنظیمات رزومه آنلاین'),
            'menu_slug'   => 'acf-options-resume-settings',
            'parent_slug' => 'acf-options-theme-settings',
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

/**
 * SEO Category Slugs Normalizer
 * Automatically converts Persian and percent-encoded category slugs
 * into clean, standard English slugs for maximum Google SEO rankings.
 */
function kmpr_normalize_category_slugs()
{
    $slug_map = array(
        'معماری نرم‌افزار'               => 'software-architecture',
        'پرفورمنس و سئو تکنیکال'        => 'performance-and-technical-seo',
        'وردپرس اختصاصی و بدون پلاگین'   => 'custom-wordpress-development',
        'فرانت‌اند مدرن و ری‌اکت'         => 'modern-frontend-react-nextjs',
        'کسب و کار'                     => 'business',
        'آموزش'                         => 'tutorials',
        'اخبار'                         => 'news',
    );

    foreach ($slug_map as $name => $target_slug) {
        $term = get_term_by('name', $name, 'category');
        if ($term && $term->slug !== $target_slug) {
            wp_update_term($term->term_id, 'category', array('slug' => $target_slug));
        }
    }
}
add_action('init', 'kmpr_normalize_category_slugs');
