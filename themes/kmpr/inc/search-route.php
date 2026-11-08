<?php
/**
 * Live search REST route: GET /wp-json/search/v1/search?term=…
 * Searches articles, services, portfolio, games and key pages; returns one normalised item shape:
 * { id, type, typeName, title, url, img, excerpt, meta }
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Searchable types → Persian label. Only types that exist on this install are used.
 */
function kmpr_search_types()
{
    $types = array(
        'post'      => 'مقاله',
        'services'  => 'خدمت',
        'portfolio' => 'نمونه‌کار',
        'game'      => 'بازی',
        'page'      => 'صفحه',
    );
    return array_filter($types, function ($t) {
        return post_type_exists($t);
    }, ARRAY_FILTER_USE_KEY);
}

function register_new_search()
{
    register_rest_route('search/v1', 'search', array(
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => 'GoldSearchResults',
        'permission_callback' => '__return_true',
        'args'                => array('term' => array('type' => 'string', 'required' => false)),
    ));
}
add_action('rest_api_init', 'register_new_search');

function GoldSearchResults($data)
{
    $term  = isset($data['term']) ? sanitize_text_field($data['term']) : '';
    $types = kmpr_search_types();

    $results = array('all' => array());
    $counts  = array('all' => 0);
    foreach ($types as $t => $label) {
        $results[$t] = array();
        $counts[$t]  = 0;
    }

    if ($term === '' || mb_strlen($term) < 2) {
        $results['counts'] = $counts;
        return $results;
    }

    $excluded = array_filter(array((int) get_option('wp_page_for_privacy_policy')));
    $query    = new WP_Query(array(
        'post_type'           => array_keys($types),
        'post_status'         => 'publish',
        'posts_per_page'      => 60,
        's'                   => $term,
        'post__not_in'        => $excluded,
        'no_found_rows'       => true,
        'ignore_sticky_posts' => true,
    ));

    foreach ($query->posts as $p) {
        $id   = $p->ID;
        $type = $p->post_type;
        $raw  = has_excerpt($id) ? get_the_excerpt($id) : strip_shortcodes($p->post_content);
        $meta = '';

        if ($type === 'post') {
            $cat  = get_the_category($id);
            $meta = !empty($cat) ? $cat[0]->name : '';
        } elseif ($type === 'services') {
            $meta = function_exists('get_field') ? (string) (get_field('duration', $id) ?: get_field('services_badge', $id)) : '';
        } elseif ($type === 'portfolio') {
            $term_obj = function_exists('kmpr_primary_term') ? kmpr_primary_term($id, 'portfolio_categories') : null;
            $meta     = $term_obj ? $term_obj->name : '';
        } elseif ($type === 'game') {
            $terms = get_the_terms($id, 'game_category');
            $meta  = ($terms && !is_wp_error($terms)) ? $terms[0]->name : '';
        }

        $item = array(
            'id'       => $id,
            'type'     => $type,
            'typeName' => $types[$type],
            'title'    => html_entity_decode(get_the_title($id), ENT_QUOTES, 'UTF-8'),
            'url'      => get_permalink($id),
            'img'      => get_the_post_thumbnail_url($id, 'thumbnail') ?: '',
            'excerpt'  => html_entity_decode(wp_strip_all_tags(wp_trim_words($raw, 22, '…')), ENT_QUOTES, 'UTF-8'),
            'meta'     => html_entity_decode((string) $meta, ENT_QUOTES, 'UTF-8'),
        );
        $results[$type][] = $item;
        $results['all'][] = $item;
    }

    // Titles that contain the term first, then the type order above.
    $order = array_flip(array_keys($types));
    usort($results['all'], function ($a, $b) use ($term, $order) {
        $ta = mb_stripos($a['title'], $term) !== false ? 0 : 1;
        $tb = mb_stripos($b['title'], $term) !== false ? 0 : 1;
        return $ta <=> $tb ?: $order[$a['type']] <=> $order[$b['type']];
    });

    foreach ($results as $k => $list) {
        $counts[$k] = count($list);
    }
    $results['counts'] = $counts;
    return $results;
}
