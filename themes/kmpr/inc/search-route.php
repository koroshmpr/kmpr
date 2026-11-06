<?php
/**
 * Register custom REST API route for real-time live search across posts, portfolio, and services
 */

function register_new_search()
{
    register_rest_route('search/v1', 'search', array(
        'methods'  => WP_REST_Server::READABLE,
        'callback' => 'GoldSearchResults',
        'permission_callback' => '__return_true'
    ));
}
add_action('rest_api_init', 'register_new_search');

function GoldSearchResults($data)
{
    $term = isset($data['term']) ? sanitize_text_field($data['term']) : '';

    $empty_response = array(
        'all'       => array(),
        'post'      => array(),
        'portfolio' => array(),
        'services'  => array(),
        'counts'    => array(
            'all'       => 0,
            'post'      => 0,
            'portfolio' => 0,
            'services'  => 0,
        )
    );

    if (empty($term) || mb_strlen($term) < 2) {
        return $empty_response;
    }

    $mainQuery = new WP_Query(array(
        'post_type'      => array('post', 'portfolio', 'services'),
        'post_status'    => 'publish',
        'posts_per_page' => 50,
        's'              => $term,
    ));

    $results = array(
        'all'       => array(),
        'post'      => array(),
        'portfolio' => array(),
        'services'  => array(),
    );

    while ($mainQuery->have_posts()) {
        $mainQuery->the_post();
        $id = get_the_ID();
        $type = get_post_type();
        $title = html_entity_decode(get_the_title());
        $url = get_the_permalink();
        $img = get_the_post_thumbnail_url($id, 'medium_large') ?: (get_the_post_thumbnail_url($id, 'large') ?: '');

        if ($type === 'post') {
            $cat = get_the_category();
            $category_name = !empty($cat) ? $cat[0]->name : 'مقاله';
            $views = get_post_meta($id, 'post_views', true) ?: '0';
            $raw_content = get_the_excerpt() ?: get_the_content();
            $excerpt = wp_strip_all_tags(wp_trim_words($raw_content, 18, '...'));

            $item = array(
                'id'       => $id,
                'type'     => 'post',
                'typeName' => 'مقاله',
                'title'    => $title,
                'url'      => $url,
                'img'      => $img,
                'excerpt'  => $excerpt,
                'category' => $category_name,
                'date'     => get_the_date(),
                'views'    => $views,
            );

            $results['post'][] = $item;
            $results['all'][] = $item;

        } elseif ($type === 'portfolio') {
            $brand_logo = function_exists('get_field') ? get_field('brand-logo', $id) : null;
            $logo_bg = function_exists('get_field') ? get_field('logo_background', $id) : '';
            $raw_content = get_the_excerpt() ?: get_the_content();
            $excerpt = wp_strip_all_tags(wp_trim_words($raw_content, 16, '...'));

            $item = array(
                'id'          => $id,
                'type'        => 'portfolio',
                'typeName'    => 'نمونه کار',
                'title'       => $title,
                'url'         => $url,
                'img'         => $img,
                'excerpt'     => $excerpt,
                'brand_logo'  => (!empty($brand_logo) && is_array($brand_logo)) ? ($brand_logo['url'] ?? '') : '',
                'brand_title' => (!empty($brand_logo) && is_array($brand_logo)) ? ($brand_logo['title'] ?? '') : '',
                'logo_bg'     => $logo_bg ?: '',
            );

            $results['portfolio'][] = $item;
            $results['all'][] = $item;

        } elseif ($type === 'services') {
            $badge = function_exists('get_field') ? get_field('services_badge', $id) : '';
            $desc = function_exists('get_field') ? get_field('services_description', $id) : '';
            $icon = function_exists('get_field') ? get_field('service-icon', $id) : '';
            if (empty($desc)) {
                $raw_content = get_the_excerpt() ?: get_the_content();
                $desc = wp_strip_all_tags(wp_trim_words($raw_content, 14, '...'));
            }

            $item = array(
                'id'          => $id,
                'type'        => 'services',
                'typeName'    => 'خدمات',
                'title'       => $title,
                'url'         => $url,
                'img'         => $img,
                'badge'       => $badge ?: '',
                'description' => $desc,
                'icon'        => $icon ?: '',
            );

            $results['services'][] = $item;
            $results['all'][] = $item;
        }
    }
    wp_reset_postdata();

    $results['counts'] = array(
        'all'       => count($results['all']),
        'post'      => count($results['post']),
        'portfolio' => count($results['portfolio']),
        'services'  => count($results['services']),
    );

    return $results;
}
