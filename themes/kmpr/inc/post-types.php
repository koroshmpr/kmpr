<?php
/**
 * Custom post types & taxonomies (moved from mu-plugins/custom-post-types.php into the theme).
 *
 * Slugs and rewrite bases are unchanged, so every existing URL keeps working:
 *   /portfolios/…  /services/…  /tag-portfolio/…  /tag-services/…
 *
 * @package KMPR
 */

if (!defined('ABSPATH')) {
    exit;
}

function kmpr_register_post_types()
{
    /* ---------- Portfolio ---------- */
    if (!post_type_exists('portfolio')) {
        register_post_type('portfolio', array(
            'labels'        => array(
                'name'               => 'نمونه‌کارها',
                'singular_name'      => 'نمونه‌کار',
                'menu_name'          => 'نمونه‌کارها',
                'all_items'          => 'همه نمونه‌کارها',
                'add_new'            => 'افزودن نمونه‌کار',
                'add_new_item'       => 'افزودن نمونه‌کار جدید',
                'edit_item'          => 'ویرایش نمونه‌کار',
                'view_item'          => 'مشاهده نمونه‌کار',
                'search_items'       => 'جستجوی نمونه‌کارها',
                'not_found'          => 'نمونه‌کاری یافت نشد.',
                'not_found_in_trash' => 'نمونه‌کاری در زباله‌دان نیست.',
            ),
            'public'        => true,
            'has_archive'   => true,
            'show_in_rest'  => true,
            'menu_position' => 5,
            'menu_icon'     => 'dashicons-portfolio',
            'supports'      => array('title', 'editor', 'excerpt', 'thumbnail', 'custom-fields', 'comments', 'revisions', 'page-attributes'),
            'rewrite'       => array('slug' => 'portfolios', 'with_front' => false),
        ));
    }

    if (!taxonomy_exists('portfolio_categories')) {
        register_taxonomy('portfolio_categories', 'portfolio', array(
            'labels'            => array(
                'name'          => 'دسته‌بندی نمونه‌کار',
                'singular_name' => 'دسته‌بندی',
                'menu_name'     => 'دسته‌بندی‌ها',
                'add_new_item'  => 'افزودن دسته‌بندی',
                'edit_item'     => 'ویرایش دسته‌بندی',
            ),
            'hierarchical'      => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'portfolio-category', 'with_front' => false, 'hierarchical' => true),
        ));
    }

    if (!taxonomy_exists('portfolio_tag')) {
        register_taxonomy('portfolio_tag', 'portfolio', array(
            'labels'                => array(
                'name'                       => 'برچسب‌های نمونه‌کار',
                'singular_name'              => 'برچسب',
                'menu_name'                  => 'برچسب‌ها',
                'search_items'               => 'جستجوی برچسب‌ها',
                'add_new_item'               => 'افزودن برچسب',
                'separate_items_with_commas' => 'برچسب‌ها را با کاما جدا کنید',
                'choose_from_most_used'      => 'انتخاب از پرکاربردها',
            ),
            'hierarchical'          => false,
            'show_ui'               => true,
            'show_in_rest'          => true,
            'update_count_callback' => '_update_post_term_count',
            'query_var'             => true,
            'rewrite'               => array('slug' => 'tag-portfolio', 'with_front' => false),
        ));
    }

    /* ---------- Services ---------- */
    if (!post_type_exists('services')) {
        register_post_type('services', array(
            'labels'        => array(
                'name'               => 'خدمات',
                'singular_name'      => 'خدمت',
                'menu_name'          => 'خدمات',
                'all_items'          => 'همه خدمات',
                'add_new'            => 'افزودن خدمت',
                'add_new_item'       => 'افزودن خدمت جدید',
                'edit_item'          => 'ویرایش خدمت',
                'view_item'          => 'مشاهده خدمت',
                'search_items'       => 'جستجوی خدمات',
                'not_found'          => 'خدمتی یافت نشد.',
                'not_found_in_trash' => 'خدمتی در زباله‌دان نیست.',
            ),
            'public'        => true,
            'has_archive'   => true,
            'show_in_rest'  => true,
            'menu_position' => 6,
            'menu_icon'     => 'dashicons-hammer',
            'supports'      => array('title', 'editor', 'excerpt', 'thumbnail', 'custom-fields', 'comments', 'revisions', 'page-attributes'),
            'rewrite'       => array('slug' => 'services', 'with_front' => false),
        ));
    }

    if (!taxonomy_exists('services_categories')) {
        register_taxonomy('services_categories', 'services', array(
            'labels'            => array(
                'name'          => 'دسته‌بندی خدمات',
                'singular_name' => 'دسته‌بندی',
                'menu_name'     => 'دسته‌بندی‌ها',
            ),
            'hierarchical'      => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'service-category', 'with_front' => false),
        ));
    }

    if (!taxonomy_exists('services_tag')) {
        register_taxonomy('services_tag', 'services', array(
            'labels'                => array(
                'name'                       => 'برچسب‌های خدمات',
                'singular_name'              => 'برچسب',
                'menu_name'                  => 'برچسب‌ها',
                'search_items'               => 'جستجوی برچسب‌ها',
                'add_new_item'               => 'افزودن برچسب',
                'separate_items_with_commas' => 'برچسب‌ها را با کاما جدا کنید',
                'choose_from_most_used'      => 'انتخاب از پرکاربردها',
            ),
            'hierarchical'          => false,
            'show_ui'               => true,
            'show_in_rest'          => true,
            'update_count_callback' => '_update_post_term_count',
            'query_var'             => true,
            'rewrite'               => array('slug' => 'tag-services', 'with_front' => false),
        ));
    }

    /* ---------- Ratings (internal storage for the post rating widget) ---------- */
    if (!post_type_exists('rating')) {
        register_post_type('rating', array(
            'labels'             => array(
                'name'          => 'امتیازها',
                'singular_name' => 'امتیاز',
                'menu_name'     => 'امتیاز مقالات',
            ),
            'public'             => false, // storage only — no public /ratings/ archive
            'show_ui'            => true,
            'show_in_menu'       => 'edit.php',
            'publicly_queryable' => false,
            'exclude_from_search'=> true,
            'supports'           => array('title', 'custom-fields'),
        ));
    }
}
// Priority 10 after any legacy mu-plugin hook, so the existence guards prevent double registration during migration.
add_action('init', 'kmpr_register_post_types', 10);

/**
 * Flush rewrite rules once after this module ships (CPT args changed).
 */
add_action('init', function () {
    if (get_option('kmpr_cpt_version') !== '3') {
        flush_rewrite_rules(false);
        update_option('kmpr_cpt_version', '3', false);
    }
}, 99);

/**
 * One-time cleanup: remove the legacy mu-plugin now that the theme owns the post types.
 * Only deletes that exact file, and only if it is the known legacy CPT file.
 */
add_action('init', function () {
    if (get_option('kmpr_mu_cleanup') === '1' || !defined('WPMU_PLUGIN_DIR')) {
        return;
    }
    $legacy = trailingslashit(WPMU_PLUGIN_DIR) . 'custom-post-types.php';
    if (file_exists($legacy)) {
        $source = (string) file_get_contents($legacy, false, null, 0, 4000);
        if (strpos($source, 'portfolio_post_types') !== false && strpos($source, 'services_post_types') !== false) {
            if (!@unlink($legacy)) {
                error_log('[kmpr] Could not delete legacy mu-plugin: ' . $legacy);
                return; // retry next request
            }
        }
    }
    update_option('kmpr_mu_cleanup', '1', false);
}, 100);

/**
 * 301 old taxonomy URLs (previously nested under the /blog/ front) to their new homes.
 */
add_action('template_redirect', function () {
    if (!is_404()) {
        return;
    }
    $path = trim((string) wp_parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
    $map  = array(
        'blog/portfolio_categories/' => 'portfolio-category/',
        'blog/tag-portfolio/'        => 'tag-portfolio/',
        'blog/services_categories/'  => 'service-category/',
        'blog/tag-services/'         => 'tag-services/',
    );
    foreach ($map as $old => $new) {
        if (strpos($path . '/', $old) === 0) {
            $path = trim($new . substr($path . '/', strlen($old)), '/'); // continue: the term slug itself may also have changed
            break;
        }
    }
    // Renamed term slugs (Persian → English): option kmpr_term_slug_map[taxonomy][old] = new.
    $bases = array('portfolio-category' => 'portfolio_categories', 'service-category' => 'services_categories', 'tag-portfolio' => 'portfolio_tag', 'tag-services' => 'services_tag');
    $parts = explode('/', $path);
    if (count($parts) >= 2 && isset($bases[$parts[0]])) {
        $renamed = (array) get_option('kmpr_term_slug_map', array());
        $old     = urldecode($parts[1]);
        if (!empty($renamed[$bases[$parts[0]]][$old])) {
            $parts[1] = $renamed[$bases[$parts[0]]][$old];
        }
    }
    $target = '/' . implode('/', $parts) . '/';
    $current = '/' . trim((string) wp_parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/') . '/';
    if (urldecode($target) !== urldecode($current)) {
        wp_safe_redirect(home_url($target), 301);
        exit;
    }
});
