<?php

function register_new_search()
{
    register_rest_route('search/v1', 'search', array(
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'GoldSearchResults'
    ));
}

function GoldSearchResults($data)
{
    $mainQuery = new WP_Query(array(
        'post_type' => array('post', 'portfolio', 'services'),
        's' => sanitize_text_field($data['term'])
    ));
    $mainResults = array(
        'post' => array(),
        'podcast' => array()
    );

    while ($mainQuery->have_posts()) {
        $mainQuery->the_post();
        if (get_post_type() == 'post') {
            $mainResults['post'][] = array(
                'title' => get_the_title(),
                'url' => get_the_permalink(),
                'img' => get_the_post_thumbnail_url(),
            );
        } elseif (get_post_type() == 'portfolio') {
            $mainResults['podcast'][] = array(
                'title' => get_the_title(),
                'url' => get_the_permalink(),
                'img' => get_the_post_thumbnail_url(),
            );
        }elseif (get_post_type() == 'services') {
            $mainResults['podcast'][] = array(
                'title' => get_the_title(),
                'url' => get_the_permalink(),
                'img' => get_the_post_thumbnail_url(),
            );
        }
    }

    return $mainResults;
}

add_action('rest_api_init', 'register_new_search');
