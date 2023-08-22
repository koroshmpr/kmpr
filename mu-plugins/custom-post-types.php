<?php
// portfolio
function portfolio_post_types(){
    register_post_type('portfolio',
        array('supports' =>
            array( 'title', 'editor', 'comments', 'excerpt', 'custom-fields', 'thumbnail' ),
            'rewrite' => array('slug' => 'portfolio'),
            'has_archive' => true,
            'public' => true,
            'labels' => array(
                'name' => 'نمونه کار',
                'add_new' => 'افزودن نمونه کار جدید',
                'add_new_item' => 'افزودن نمونه کار جدید',
                'edit_item' => 'ویرایش نمونه کار',
                'all_items' => 'همه ی نمونه کارها',
                'singular_name' => 'نمونه کار'),
            'menu_icon' => 'dashicons-portfolio'
        ));
    register_taxonomy(
        'portfolio_categories',
        'portfolio',
        array('hierarchical' => true,
            'label' => 'دسته بندی نمونه کار',
            'query_var' => true,
        )
    );
    $labels = array('name' => _x( 'Tags', 'taxonomy general name' ),
        'singular_name' => _x( 'Tag', 'taxonomy singular name' ),
        'search_items' =>  __( 'Search Tags' ),
        'popular_items' => __( 'Popular Tags' ),
        'all_items' => __( 'All Tags' ),
        'parent_item' => null,
        'parent_item_colon' => null,
        'edit_item' => __( 'Edit Tag' ),
        'update_item' => __( 'Update Tag' ),
        'add_new_item' => __( 'Add New Tag' ),
        'new_item_name' => __( 'New Tag Name' ),
        'separate_items_with_commas' => __( 'Separate tags with commas' ),
        'add_or_remove_items' => __( 'Add or remove tags' ),
        'choose_from_most_used' => __( 'Choose from the most used tags' ),
        'menu_name' => __( 'برچسب نمونه کار' ),    );
    register_taxonomy('portfolio_tag','portfolio',
        array('hierarchical' => false,
            'labels' => $labels,
            'show_ui' => true,
            'update_count_callback' =>
                '_update_post_term_count',
            'query_var' => true,
            'rewrite' => array( 'slug' => 'tag-portfolio' ),
        ));
}
add_action('init', 'portfolio_post_types');
// services
function services_post_types(){
    register_post_type('services',
        array(
            'supports' => array( 'title', 'editor', 'comments', 'excerpt', 'custom-fields', 'thumbnail' ),
            'rewrite' => array('slug' => 'services'),
            'has_archive' => true,
            'public' => true,
            'labels' => array(
                'name' => 'خدمات ما',
                'singular_name' => 'خدمت',
                'add_new' => 'افزودن خدمت جدید',
                'add_new_item' => 'افزودن خدمت جدید',
                'edit_item' => 'ویرایش خدمت',
                'all_items' => 'همه‌ی خدمات',
                'menu_name' => 'خدمات ما',
            ),
            'menu_icon' => 'dashicons-admin-generic',
        )
    );
    register_taxonomy(
        'services_categories',
        'services',
        array(
            'hierarchical' => true,
            'label' => 'دسته بندی خدمات',
            'query_var' => true,
        )
    );
    $labels = array(
        'name' => _x( 'برچسب‌ها', 'taxonomy general name' ),
        'singular_name' => _x( 'برچسب', 'taxonomy singular name' ),
        'search_items' =>  __( 'جستجوی برچسب‌ها' ),
        'popular_items' => __( 'برچسب‌های محبوب' ),
        'all_items' => __( 'همه‌ی برچسب‌ها' ),
        'parent_item' => null,
        'parent_item_colon' => null,
        'edit_item' => __( 'ویرایش برچسب' ),
        'update_item' => __( 'به‌روزرسانی برچسب' ),
        'add_new_item' => __( 'افزودن برچسب جدید' ),
        'new_item_name' => __( 'نام برچسب جدید' ),
        'separate_items_with_commas' => __( 'جدا کردن برچسب‌ها با کاما' ),
        'add_or_remove_items' => __( 'افزودن یا حذف برچسب‌ها' ),
        'choose_from_most_used' => __( 'انتخاب از بیشترین استفاده شدها' ),
        'menu_name' => __( 'برچسب خدمات' ),
    );
    register_taxonomy(
        'services_tag',
        'services',
        array(
            'hierarchical' => false,
            'labels' => $labels,
            'show_ui' => true,
            'update_count_callback' => '_update_post_term_count',
            'query_var' => true,
            'rewrite' => array( 'slug' => 'tag-services' ),
        )
    );
}
add_action('init', 'services_post_types');

// rating
function create_rating_post_type() {
    $labels = array(
        'name' => 'Ratings',
        'singular_name' => 'Rating',
        'menu_name' => 'Ratings',
        'add_new_item' => 'Add New Rating',
        'edit_item' => 'Edit Rating',
        'new_item' => 'New Rating',
        'view_item' => 'View Rating',
        'search_items' => 'Search Ratings',
        'not_found' => 'No ratings found',
        'not_found_in_trash' => 'No ratings found in trash'
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'has_archive' => true,
        'supports' => array('title'),
        'rewrite' => array('slug' => 'ratings')
    );

    register_post_type('rating', $args);
}
add_action('init', 'create_rating_post_type');
