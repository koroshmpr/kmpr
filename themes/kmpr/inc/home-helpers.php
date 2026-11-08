<?php
/**
 * Shared presentation helpers (home page, archives, cards).
 *
 * @package KMPR
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Years of professional experience, computed from one source of truth.
 * Set ACF option `career_start_year` (Jalali, e.g. 1392) to change it.
 */
function kmpr_years_experience()
{
    $start = function_exists('get_field') ? (int) get_field('career_start_year', 'option') : 0;
    $start = $start > 1300 ? $start : 1392;
    $now   = (int) wp_date('Y') - 621 - ((int) wp_date('n') < 3 || ((int) wp_date('n') === 3 && (int) wp_date('j') < 21) ? 1 : 0);
    return max(1, $now - $start);
}

/**
 * Persian digits.
 */
function kmpr_fa_num($value)
{
    return strtr((string) $value, array('0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹'));
}

/**
 * URL of the project request wizard.
 */
function kmpr_request_url()
{
    $contact = get_page_by_path('contact-us');
    $base    = $contact ? get_permalink($contact) : home_url('/contact-us/');
    return $base . '#smart-configurator';
}

/**
 * Best cover image for a portfolio item: desktop/laptop screenshot → featured image → null.
 * Returns array( url, width, height, alt ) or null.
 */
function kmpr_portfolio_cover($post_id = null, $size = 'large', $device = 'laptop')
{
    $post_id = $post_id ?: get_the_ID();
    $pick    = null;

    if (function_exists('have_rows') && have_rows('screenshot', $post_id)) {
        $fallback = null;
        while (have_rows('screenshot', $post_id)) {
            the_row();
            $img = get_sub_field('screenshot-image');
            $sz  = get_sub_field('screen-size') ?: 'laptop';
            if (!$img) {
                continue;
            }
            if ($sz === $device && !$pick) {
                $pick = $img;
            }
            $fallback = $fallback ?: $img;
        }
        $pick = $pick ?: $fallback;
    }

    if (!$pick && has_post_thumbnail($post_id)) {
        $pick = get_post_thumbnail_id($post_id);
    }
    if (!$pick) {
        return null;
    }

    $id = is_array($pick) ? ($pick['ID'] ?? $pick['id'] ?? 0) : (is_numeric($pick) ? (int) $pick : attachment_url_to_postid($pick));
    if ($id) {
        $src = wp_get_attachment_image_src($id, $size);
        if ($src) {
            return array(
                'url'    => $src[0],
                'width'  => $src[1],
                'height' => $src[2],
                'alt'    => get_post_meta($id, '_wp_attachment_image_alt', true) ?: get_the_title($post_id),
            );
        }
    }
    $url = is_array($pick) ? ($pick['url'] ?? '') : (string) $pick;
    return $url ? array('url' => $url, 'width' => 1440, 'height' => 900, 'alt' => get_the_title($post_id)) : null;
}

/**
 * First category / term name for a post-like item.
 */
function kmpr_primary_term($post_id, $taxonomy)
{
    $terms = get_the_terms($post_id, $taxonomy);
    return ($terms && !is_wp_error($terms)) ? $terms[0] : null;
}
