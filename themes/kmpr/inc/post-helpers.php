<?php
/**
 * Post Utilities, Metrics, Taxonomies & Breadcrumbs
 *
 * @package KMPR
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Calculate estimated reading time in Persian minutes
 */
function reading_time($post_id = null)
{
    if (!$post_id) {
        $post_id = get_the_ID();
    }
    $content       = get_post_field('post_content', $post_id);
    $clean_content = wp_strip_all_tags(strip_shortcodes($content));
    $word_count    = count(preg_split('/\s+/u', trim($clean_content), -1, PREG_SPLIT_NO_EMPTY));
    $minutes       = ceil($word_count / 200);
    return max(1, (int)$minutes);
}

/**
 * Track unique post views with cookie debounce
 */
function track_post_views($post_id)
{
    if (!isset($_COOKIE['post_viewed_' . $post_id])) {
        $current_views = get_post_meta($post_id, 'post_views', true);
        if ($current_views == '') {
            $current_views = 0;
        }
        $new_views = $current_views + 1;
        update_post_meta($post_id, 'post_views', $new_views);
        if (!headers_sent()) {
            setcookie('post_viewed_' . $post_id, 'true', time() + 3600 * 24, '/');
        }
    }
}

/**
 * AJAX Star rating handler
 */
function save_rating()
{
    $post_id      = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
    $rating_value = isset($_POST['rating_value']) ? (int) $_POST['rating_value'] : 0;

    if (!$post_id || 'post' !== get_post_type($post_id) || 'publish' !== get_post_status($post_id) || $rating_value < 1 || $rating_value > 5) {
        wp_send_json_error(array('message' => 'invalid'), 400);
    }

    $total_ratings      = (int) get_post_meta($post_id, 'total_ratings', true) + 1;
    $total_rating_value = (float) get_post_meta($post_id, 'total_rating_value', true) + $rating_value;

    update_post_meta($post_id, 'rating_value', $rating_value);
    update_post_meta($post_id, 'total_ratings', $total_ratings);
    update_post_meta($post_id, 'total_rating_value', $total_rating_value);

    wp_send_json_success(array(
        'count'   => $total_ratings,
        'average' => round($total_rating_value / $total_ratings, 1),
    ));
}
add_action('wp_ajax_save_rating', 'save_rating');
add_action('wp_ajax_nopriv_save_rating', 'save_rating');

/**
 * Pre get posts hook for search customization
 */
function modify_search_query($query)
{
    if (!is_admin() && $query->is_search) {
        // Query customizations if needed
    }
}
add_action('pre_get_posts', 'modify_search_query');

/**
 * Remove front prefixes from custom post types rewrite
 */
function custom_post_type_args($args, $post_type)
{
    if ('portfolio' === $post_type || 'services' === $post_type) {
        $args['rewrite']['with_front'] = false;
    }
    return $args;
}
add_filter('register_post_type_args', 'custom_post_type_args', 10, 2);

/**
 * Clean & SEO-friendly breadcrumbs helper
 */
function the_breadcrumb()
{
    global $post;
    echo '<ul class="breadcrumb my-0 py-4 flex items-center gap-2 text-xs text-slate-500">';
    if (!is_home()) {
        echo '<li class="breadcrumb-item"><a class="text-secondary hover:underline" href="' . esc_url(get_post_type_archive_link('post')) . '">مقاله</a></li>';
        if (is_category() || is_single()) {
            echo '<li class="breadcrumb-item separator">/</li><li class="breadcrumb-item">';
            the_category(' </li><li class="breadcrumb-item separator">/</li><li class="breadcrumb-item"> ');
            if (is_single()) {
                echo '</li><li class="breadcrumb-item separator">/</li><li class="breadcrumb-item font-semibold text-slate-700">';
                the_title();
                echo '</li>';
            }
        } elseif (is_page()) {
            if ($post && $post->post_parent) {
                $anc   = get_post_ancestors($post->ID);
                $title = get_the_title();
                $output = '';
                foreach ($anc as $ancestor) {
                    $output = '<li><a class="breadcrumb-item text-secondary hover:underline" href="' . esc_url(get_permalink($ancestor)) . '" title="' . esc_attr(get_the_title($ancestor)) . '">' . esc_html(get_the_title($ancestor)) . '</a></li> <li class="separator">/</li>';
                }
                echo $output;
                echo '<li class="breadcrumb-item font-semibold text-slate-700" title="' . esc_attr($title) . '">' . esc_html($title) . '</li>';
            } else {
                echo '<li class="breadcrumb-item font-semibold text-slate-700">' . esc_html(get_the_title()) . '</li>';
            }
        }
    }
    echo '</ul>';
}
