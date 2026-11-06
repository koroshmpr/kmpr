<?php
/** * Enqueue scripts and styles. */
//require get_theme_file_path('/inc/filter-route.php');
require get_theme_file_path('/inc/search-route.php');
function theme_scripts()
{
    wp_enqueue_style('style', get_stylesheet_directory_uri() . '/public/css/style.css', array(), '2.0.0');
    wp_enqueue_script('main-js', get_template_directory_uri() . '/public/js/app.js', array(), '2.0.0', array(
        'in_footer' => true,
        'strategy'  => 'defer'
    ));
    wp_localize_script('main-js', 'jsData', array('root_url' => get_site_url(), 'nonce' => wp_create_nonce('my-nonce')));
}

add_action('wp_enqueue_scripts', 'theme_scripts');

function theme_preload_fonts() {
    $font_dir = get_template_directory_uri() . '/public/fonts/yekanBakh/';
    echo '<link rel="preload" href="' . esc_url($font_dir . 'Yekan-Bakh-FaNum-Regular.woff2') . '" as="font" type="font/woff2" crossorigin="anonymous">' . "\n";
    echo '<link rel="preload" href="' . esc_url($font_dir . 'Yekan_Bakh_FaNum_06_Bold.woff2') . '" as="font" type="font/woff2" crossorigin="anonymous">' . "\n";
}
add_action('wp_head', 'theme_preload_fonts', 1);

add_theme_support('title-tag');
add_theme_support('post-thumbnails');
/** * Sets up theme defaults and registers support for various WordPress features. * * Note that this function is hooked into the after_setup_theme hook, which * runs before the init hook. The init hook is too late for some features, such * as indicating support for post thumbnails. */
function theme_setup()
{
    add_theme_support('custom-logo', array('height' => 250, 'width' => 250, 'flex-width' => true, 'flex-height' => true,));
    register_nav_menu('headerMenuLocation', 'منوی اصلی');
    register_nav_menu('topHeaderMenuLocation', 'منوی گوشی');
    register_nav_menu('footerLocationOne', 'منوی اول فوتر');
    register_nav_menu('footerLocationTwo', 'منوی دوم فوتر');
    register_nav_menu('footerLocationThree', 'منوی سوم فوتر');
    add_theme_support('woocommerce');
}

add_action('after_setup_theme', 'theme_setup');
/** * Register widget area.
 * * @link https://developer.wordpress.org/themes/functionality/sidebars/#registering-a-sidebar
 */
/**
 * Performance & Speed Optimizations
 */
function kmpr_disable_emojis() {
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('admin_print_scripts', 'print_emoji_detection_script');
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('admin_print_styles', 'print_emoji_styles');
    remove_filter('the_content_feed', 'wp_staticize_emoji');
    remove_filter('comment_text_rss', 'wp_staticize_emoji');
    remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
}
add_action('init', 'kmpr_disable_emojis');

function kmpr_cleanup_head_scripts() {
    wp_deregister_script('wp-embed');
    remove_action('wp_head', 'wp_generator');
    remove_action('wp_head', 'wlwmanifest_link');
    remove_action('wp_head', 'rsd_link');
    remove_action('wp_head', 'wp_shortlink_wp_head');
    remove_action('wp_head', 'rest_output_link_wp_head');
    remove_action('wp_head', 'wp_oembed_add_discovery_links');
    remove_action('template_redirect', 'rest_output_link_header', 11);
    
    // Dequeue unused default WordPress classic styles
    wp_dequeue_style('classic-theme-styles');
    
    // Remove default block SVG filters from body
    remove_action('wp_body_open', 'wp_global_styles_render_svg_filters');
}
add_action('wp_enqueue_scripts', 'kmpr_cleanup_head_scripts', 99);
add_action('after_setup_theme', function() {
    remove_action('wp_body_open', 'wp_global_styles_render_svg_filters');
});

/**
 * Robust Active Menu Item Filter
 * Correctly marks current-menu-item / active based on current page and post type archives
 */
function kmpr_menu_active_classes($classes, $item) {
    if (!is_array($classes)) {
        $classes = array();
    }
    
    $request_uri = $_SERVER['REQUEST_URI'] ?? '';
    $current_url = home_url($request_uri);
    $item_url = untrailingslashit($item->url);
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
/** * Custom template tags for this theme. */
//require get_template_directory() . '/inc/template-tags.php';
add_action('acf/init', 'my_acf_op_init');
function my_acf_op_init()
{
    // Check function exists.
    if (function_exists('acf_add_options_page')) {
        $parent = acf_add_options_page(array('page_title' => __('Theme General Settings'),
            'menu_title' => __('Theme Settings'), 'redirect' => false,));
        $child = acf_add_options_page(array('page_title' => __('Contact and Social'), 'menu_title' => __('Contact and Social'), 'parent_slug' => $parent['menu_slug'],));
    }
}

function add_menu_link_class($classes, $item, $args)
{
    if (isset($args->link_class)) {
        $classes['class'] = $args->link_class;
    }
    return $classes;
}

add_filter('nav_menu_link_attributes', 'add_menu_link_class', 1, 3);
//populate gravity form
/** * Populate ACF select field options with Gravity Forms */
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
// helper function to find a menu item in an array of items
function wpd_get_menu_item($field, $object_id, $items)
{
    foreach ($items as $item) {
        if ($item->$field == $object_id) {
            return $item;
        }
    }
    return false;
}

// Custom Breadcrumbs helper

function the_breadcrumb()
{
    global $post;
    echo '<ul class="breadcrumb my-0 py-4">';
    if (!is_home()) {
        echo '<li class="breadcrumb-item"><a class="text-decoration-none text-semi-light" href="';
        echo get_post_type_archive_link('post');
        echo '">';
        echo 'مقاله';
        echo '</a></li>';
        if (is_category() || is_single()) {
            echo '<li class="breadcrumb-item">';
            the_category(' </li><li class="breadcrumb-item"> ');
            if (is_single()) {
                echo '</li><li class="breadcrumb-item">';
                the_title();
                echo '</li>';
            }
        } elseif (is_page()) {
            if ($post->post_parent) {
                $anc = get_post_ancestors($post->ID);
                $title = get_the_title();
                foreach ($anc as $ancestor) {
                    $output = '<li><a class="breadcrumb-item" href="' . get_permalink($ancestor) . '" title="' . get_the_title($ancestor) . '">' . get_the_title($ancestor) . '</a></li> <li class="separator">/</li>';
                }
                echo $output;
                echo '<strong title="' . $title . '"> ' . $title . '</strong>';
            } else {
                echo '<li><strong> ' . get_the_title() . '</strong></li>';
            }
        }
    }
    echo '</ul>';
}


/** * Remove emoji CDN hostname from DNS prefetching hints. * * @param array $urls URLs to print for resource hints. * @param string $relation_type The relation type the URLs are printed for. * * @return array Difference betwen the two arrays. */
function disable_emojis_remove_dns_prefetch($urls, $relation_type)
{
    if ('dns-prefetch' == $relation_type) {
        /** This filter is documented in wp-includes/formatting.php */
        $emoji_svg_url = apply_filters('emoji_svg_url', 'https://s.w.org/images/core/emoji/2/svg/');
        $urls = array_diff($urls, array($emoji_svg_url));
    }
    return $urls;
}

add_filter('is_xml_preprocess_enabled', 'wpai_is_xml_preprocess_enabled', 10, 1);
function wpai_is_xml_preprocess_enabled($is_enabled)
{
    return false;
}

// table of contents/** * Automatically add IDs to headings such as <h2></h2> */
function auto_id_headings($content)
{
    $content = preg_replace_callback('/(\<h[1-6](.*?))\>(.*)(<\/h[1-6]>)/i', function ($matches) {
        if (!stripos($matches[0], 'id=')) {
            $matches[0] = $matches[1] . $matches[2] . ' id="' . sanitize_title($matches[3]) . '">' . $matches[3] . $matches[4];
        }
        return $matches[0];
    }, $content);
    return $content;
}

add_filter('the_content', 'auto_id_headings');
function get_toc($content)
{
    // get headlines
    $headings = get_headings($content, 1, 6);
    // parse toc
    ob_start();
    echo "<div class='table-of-contents'>";
    echo "<!-- Table of contents by webdeasy.de -->";
    parse_toc($headings, 0, 0);
    echo "</div>";
    return ob_get_clean();
}

function parse_toc($headings, $index, $recursive_counter)
{
    // prevent errors
    if ($recursive_counter > 60 || !count($headings)) return;
    // get all needed elements
    $last_element = $index > 0 ? $headings[$index - 1] : NULL;
    $current_element = $headings[$index];
    $next_element = NULL;
    if ($index < count($headings) && isset($headings[$index + 1])) {
        $next_element = $headings[$index + 1];
    }
    // end recursive calls
    if ($current_element == NULL) return;
    // get all needed variables
    $tag = intval($headings[$index]["tag"]);
    $id = $headings[$index]["id"];
    $classes = isset($headings[$index]["classes"]) ? $headings[$index]["classes"] : array();
    $name = $headings[$index]["name"];
    // element not in toc
    if (isset($current_element["classes"]) && $current_element["classes"] && in_array("nitoc", $current_element["classes"])) {
        parse_toc($headings, $index + 1, $recursive_counter + 1);
        return;
    }
    // parse toc begin or toc subpart begin
    if ($last_element == NULL) echo "<ul class='list-unstyled'>";
    if ($last_element != NULL && $last_element["tag"] < $tag) {
        for ($i = 0; $i < $tag - $last_element["tag"]; $i++) {
            echo "<ul class=''>";
        }
    }
    // build li class
    $li_classes = "";
    if (isset($current_element["classes"]) && $current_element["classes"] && in_array("toc-bold", $current_element["classes"])) $li_classes = " class='bold'";
    // parse line begin
    echo "<li" . $li_classes . ">";
    // only parse name, when li is not bold
    if (isset($current_element["classes"]) && $current_element["classes"] && in_array("toc-bold", $current_element["classes"])) {
        echo $name;
    } else {
        echo "<a class='' href='#" . $id . "'>" . $name . "</a>";
    }
    if ($next_element && intval($next_element["tag"]) > $tag) {
        parse_toc($headings, $index + 1, $recursive_counter + 1);
    }
    // parse line end
    echo "</li>";
    // parse next line
    if ($next_element && intval($next_element["tag"]) == $tag) {
        parse_toc($headings, $index + 1, $recursive_counter + 1);
    }
    // parse toc end or toc subpart end
    if ($next_element == NULL || ($next_element && $next_element["tag"] < $tag)) {
        echo "</ul>";
        if ($next_element && $tag - intval($next_element["tag"]) >= 2) {
            echo "</li>";
            for ($i = 1; $i < $tag - intval($next_element["tag"]); $i++) {
                echo "</ul>";
            }
        }
    }
    // parse top subpart
    if ($next_element != NULL && $next_element["tag"] < $tag) {
        parse_toc($headings, $index + 1, $recursive_counter + 1);
    }
}

function get_headings($content, $from_tag = 1, $to_tag = 6)
{
    $headings = array();
    preg_match_all("/<h([" . $from_tag . "-" . $to_tag . "])([^<]*)>(.*)<\/h[" . $from_tag . "-" . $to_tag . "]>/", $content, $matches);
    for ($i = 0; $i < count($matches[1]); $i++) {
        $headings[$i]["tag"] = $matches[1][$i];
        // get id
        $att_string = $matches[2][$i];
        preg_match("/id=\"([^\"]*)\"/", $att_string, $id_matches);
        $headings[$i]["id"] = $id_matches[1];
        // get classes
        $att_string = $matches[2][$i];
        preg_match_all("/class=\"([^\"]*)\"/", $att_string, $class_matches);
        for ($j = 0; $j < count($class_matches[1]); $j++) {
            $headings[$i]["classes"] = explode(" ", $class_matches[1][$j]);
        }
        $headings[$i]["name"] = strip_tags($matches[3][$i]);
    }
    return $headings;
}

// TOC (from webdeasy.de)
function toc_shortcode()
{
    return get_toc(auto_id_headings(get_the_content()));
}

add_shortcode('TOC', 'toc_shortcode');
// estimated reading time
function reading_time($post_id = null)
{
    if (!$post_id) {
        $post_id = get_the_ID();
    }
    $content = get_post_field('post_content', $post_id);
    $clean_content = wp_strip_all_tags(strip_shortcodes($content));
    $word_count = count(preg_split('/\s+/u', trim($clean_content), -1, PREG_SPLIT_NO_EMPTY));
    $minutes = ceil($word_count / 200);
    return max(1, (int)$minutes);
}

function track_post_views($post_id)
{
    if (!isset($_COOKIE['post_viewed_' . $post_id])) {
        $current_views = get_post_meta($post_id, 'post_views', true);
        if ($current_views == '') {
            $current_views = 0;
        }
        $new_views = $current_views + 1;
        update_post_meta($post_id, 'post_views', $new_views);
        setcookie('post_viewed_' . $post_id, 'true', time() + 3600 * 24, '/');
    }
}

function save_rating() {
    if(isset($_POST['post_id']) && isset($_POST['rating_value'])) {
        $post_id = $_POST['post_id'];
        $rating_value = $_POST['rating_value'];
        $total_ratings = get_post_meta($post_id, 'total_ratings', true);
        $total_rating_value = get_post_meta($post_id, 'total_rating_value', true);
        if(!$total_ratings) {
            $total_ratings = 0;
        }
        if(!$total_rating_value) {
            $total_rating_value = 0;
        }
        update_post_meta($post_id, 'rating_value', $rating_value);
        update_post_meta($post_id, 'total_ratings', ++$total_ratings);
        update_post_meta($post_id, 'total_rating_value', ($total_rating_value + $rating_value));
    }
    wp_die();
}

add_action('wp_ajax_save_rating', 'save_rating');
add_action('wp_ajax_nopriv_save_rating', 'save_rating');
function modify_search_query($query) {
    if (!is_admin() && $query->is_search) {
        // Modify the query as needed
    }
}
add_action('pre_get_posts', 'modify_search_query');

function custom_post_type_args( $args, $post_type ) {

    // Change 'project' to the slug of your custom post type

    if ( 'portfolio' === $post_type ) {

        // Set the with_front parameter to false

        $args['rewrite']['with_front'] = false;

    }

    if ( 'services' === $post_type ) {

        // Set the with_front parameter to false

        $args['rewrite']['with_front'] = false;

    }

    return $args;

}

add_filter( 'register_post_type_args', 'custom_post_type_args', 10, 2 );

function gregorian_to_jalali($gy, $gm, $gd) {
	$g_days_in_month = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
	$j_days_in_month = array(31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29);

	if ($gy > 1600) {
		$jy = 979;
		$gy -= 1600;
	} else {
		$jy = 0;
		$gy -= 621;
	}

	$gy2 = ($gm > 2) ? ($gy + 1) : $gy;
	$days = (365 * $gy) + (int)(($gy2 + 3) / 4) - (int)(($gy2 + 99) / 100) + (int)(($gy2 + 399) / 400) - 80 + $gd;

	for ($i = 0; $i < ($gm - 1); ++$i) {
		$days += $g_days_in_month[$i];
	}

	$jy += 33 * (int)($days / 12053);
	$days %= 12053;
	$jy += 4 * (int)($days / 1461);
	$days %= 1461;

	if ($days > 365) {
		$jy += (int)(($days - 1) / 365);
		$days = ($days - 1) % 365;
	}

	for ($i = 0; ($i < 11) && ($days >= $j_days_in_month[$i]); ++$i) {
		$days -= $j_days_in_month[$i];
	}

	$jm = $i + 1;
	$jd = $days + 1;

	return array($jy, $jm, $jd);
}

function kmpr_to_persian_num($string) {
	$persian = array('۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹');
	$english = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
	return str_replace($english, $persian, (string)$string);
}

function shamsi_date($format = 'd F Y', $timestamp = null, $persian_digits = true) {
	if (!$timestamp) {
		$timestamp = time();
	}

	$g_date = date("Y-m-d", (int)$timestamp);
	list($gy, $gm, $gd) = explode('-', $g_date);
	list($jy, $jm, $jd) = gregorian_to_jalali((int)$gy, (int)$gm, (int)$gd);

	$persian_months = array(
		'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور',
		'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'
	);

	$formatted = str_replace('Y', $jy, $format);
	$formatted = str_replace('y', substr((string)$jy, 2), $formatted);
	$formatted = str_replace('m', str_pad($jm, 2, '0', STR_PAD_LEFT), $formatted);
	$formatted = str_replace('n', $jm, $formatted);
	$formatted = str_replace('d', str_pad($jd, 2, '0', STR_PAD_LEFT), $formatted);
	$formatted = str_replace('j', $jd, $formatted);

	if (strpos($formatted, 'F') !== false) {
		$formatted = str_replace('F', $persian_months[$jm - 1], $formatted);
	}

	if ($persian_digits) {
		return kmpr_to_persian_num($formatted);
	}

	return $formatted;
}

// Automatically convert get_the_date / get_the_modified_date to Persian Shamsi without plugins!
function kmpr_filter_wp_date($the_date, $format, $post) {
	$timestamp = get_post_time('U', false, $post);
	if (!$timestamp) return $the_date;
	return shamsi_date($format ?: 'd F Y', $timestamp, true);
}
add_filter('get_the_date', 'kmpr_filter_wp_date', 10, 3);
add_filter('get_the_modified_date', 'kmpr_filter_wp_date', 10, 3);
add_filter('get_comment_date', 'kmpr_filter_wp_date', 10, 3);
