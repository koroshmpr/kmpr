<?php
/**
 * Technical SEO layer on top of Yoast SEO.
 *
 * - Brand-suffixed titles («… | کوروش محمدپور») when they fit in 60 characters.
 * - Meta description fallbacks per template (excerpt, course intro, term description…).
 * - Robots: noindex for search, 404, author/date/tag/attachment and other thin archives.
 * - Open Graph / Twitter image fallback to a branded 1200×630 image (also replaces portrait / tiny images).
 * - XML sitemap scope (post, page, portfolio, services, game, category, portfolio_categories only).
 * - Breadcrumb labels, robots.txt hygiene, web app manifest, theme-color.
 *
 * Content-level values (titles / descriptions per entity) live in Yoast's own fields, not here.
 *
 * @package KMPR
 */

if (!defined('ABSPATH')) {
    exit;
}

/** Brand string appended to titles. */
function kmpr_seo_brand()
{
    return 'کوروش محمدپور';
}

/** Absolute URL of the branded default share image (1200×630 PNG). */
function kmpr_seo_default_image()
{
    return get_template_directory_uri() . '/public/images/og-default.png';
}

/** Trim plain text to a meta-description friendly length on a word boundary. */
function kmpr_seo_trim($text, $max = 155)
{
    $text = trim(preg_replace('/\s+/u', ' ', wp_strip_all_tags(strip_shortcodes((string) $text))));
    $text = preg_replace('/[\s.…]+$/u', '', $text); // multibyte-safe (rtrim() strips single bytes and corrupts UTF-8)
    if (mb_strlen($text) <= $max) {
        return $text;
    }
    $cut = mb_substr($text, 0, $max - 1);
    $pos = mb_strrpos($cut, ' ');
    if ($pos !== false && $pos > $max * 0.6) {
        $cut = mb_substr($cut, 0, $pos);
    }
    return preg_replace('/[\s،,;:؛.\-–—]+$/u', '', $cut) . '…';
}

/** True on thin / utility URLs that must never be indexed. */
function kmpr_seo_is_thin()
{
    if (is_search() || is_404() || is_author() || is_date() || is_tag() || is_attachment()) {
        return true;
    }
    if (is_tax(array('portfolio_tag', 'services_tag', 'services_categories', 'post_format'))) {
        return true;
    }
    // Empty taxonomy archives (e.g. an empty course or portfolio category).
    if ((is_category() || is_tax()) && ($term = get_queried_object()) instanceof WP_Term && (int) $term->count === 0) {
        return true;
    }
    // Untranslated WordPress privacy-policy boilerplate: keep it out of the index until it is rewritten in Persian.
    $privacy = (int) get_option('wp_page_for_privacy_policy');
    if ($privacy && is_page($privacy) && !preg_match('/\p{Arabic}/u', get_post_field('post_content', $privacy))) {
        return true;
    }
    return false;
}

/* =========================================================================
 * Titles
 * ====================================================================== */
add_filter('wpseo_title', function ($title) {
    $title = trim((string) $title);
    if ($title === '') {
        return $title;
    }
    // Persian digits for "صفحه 2 از 5".
    if (function_exists('kmpr_fa_num')) {
        $title = preg_replace_callback('/صفحه \d+ از \d+/u', function ($m) {
            return kmpr_fa_num($m[0]);
        }, $title);
    }
    $brand = kmpr_seo_brand();
    // Paginated archives need unique titles even when a fixed Yoast title is set (e.g. the blog page).
    $paged = (int) get_query_var('paged');
    if ($paged > 1 && mb_strpos($title, 'صفحه') === false) {
        $page_label = 'صفحه ' . (function_exists('kmpr_fa_num') ? kmpr_fa_num($paged) : $paged);
        $title      = mb_strpos($title, ' | ' . $brand) !== false
            ? str_replace(' | ' . $brand, ' – ' . $page_label . ' | ' . $brand, $title)
            : $title . ' – ' . $page_label;
    }
    if (mb_strpos($title, $brand) === false) {
        $candidate = $title . ' | ' . $brand;
        if (mb_strlen($candidate) <= 60) {
            $title = $candidate;
        }
    }
    return $title;
}, 20);

/* =========================================================================
 * Meta descriptions (fallbacks only — a description written in Yoast always wins)
 * ====================================================================== */
add_filter('wpseo_metadesc', function ($desc) {
    if (is_search() || is_404()) {
        return $desc;
    }
    $desc = trim((string) $desc);

    if ($desc === '') {
        if (is_singular()) {
            $id   = get_queried_object_id();
            $desc = has_excerpt($id) ? get_the_excerpt($id) : get_post_field('post_content', $id);
            if (!trim(wp_strip_all_tags((string) $desc)) && is_singular('services') && function_exists('get_field')) {
                $desc = (string) get_field('services_description', $id);
            }
        } elseif (is_category()) {
            $term = get_queried_object();
            $desc = (function_exists('get_field') ? (string) get_field('course_intro', $term) : '') ?: term_description($term);
        } elseif (is_tax()) {
            $term = get_queried_object();
            $desc = term_description($term);
            if (!trim(wp_strip_all_tags((string) $desc)) && $term instanceof WP_Term && $term->taxonomy === 'portfolio_categories') {
                $desc = sprintf('نمونه‌کارهای %s که خودم طراحی و کدنویسی کرده‌ام؛ با جزئیات چالش، راه‌حل، تکنولوژی‌ها و نتیجه هر پروژه در نمونه‌کارهای کوروش محمدپور.', $term->name);
            }
        }
        $desc = $desc ? kmpr_seo_trim($desc) : '';
    }

    // Paginated archives: keep the description unique per page.
    $paged = (int) get_query_var('paged');
    if ($desc !== '' && $paged > 1) {
        $suffix = ' (صفحه ' . (function_exists('kmpr_fa_num') ? kmpr_fa_num($paged) : $paged) . ')';
        $desc   = kmpr_seo_trim($desc, 155 - mb_strlen($suffix)) . $suffix;
    }
    return $desc;
}, 20);

/* =========================================================================
 * Robots meta
 * ====================================================================== */
add_filter('wpseo_robots_array', function ($robots) {
    if (kmpr_seo_is_thin()) {
        $robots['index']  = 'noindex';
        $robots['follow'] = 'follow';
        unset($robots['max-snippet'], $robots['max-image-preview'], $robots['max-video-preview']);
    }
    return $robots;
}, 20);

/* Canonical is meaningless on noindexed utility pages. */
add_filter('wpseo_canonical', function ($canonical) {
    return kmpr_seo_is_thin() ? false : $canonical;
}, 20);

/* =========================================================================
 * Open Graph / Twitter image fallback
 * ====================================================================== */
add_filter('wpseo_frontend_presentation', function ($presentation) {
    if (is_404() || is_search()) {
        return $presentation;
    }
    $fallback = array(
        'url'    => kmpr_seo_default_image(),
        'width'  => 1200,
        'height' => 630,
        'type'   => 'image/png',
        'alt'    => kmpr_seo_brand() . ' | طراح و توسعه‌دهنده وب',
    );

    $images = (array) $presentation->open_graph_images;
    $first  = $images ? reset($images) : null;
    $url    = is_array($first) ? ($first['url'] ?? '') : '';
    $w      = is_array($first) ? (int) ($first['width'] ?? 0) : 0;
    $h      = is_array($first) ? (int) ($first['height'] ?? 0) : 0;

    $legacy   = $url && preg_match('~/(webco|kmpr\.com_)[^/]*$~i', $url);
    $portrait = $w && $h && $h > $w; // full-page screenshots, portraits
    $tiny     = $w && $w < 600;

    if (!$url || !$w || $url === $fallback['url'] || $legacy || $portrait || $tiny || is_front_page()) {
        $presentation->open_graph_images = array($fallback['url'] => $fallback);
        $presentation->twitter_image     = $fallback['url'];
    }
    return $presentation;
}, 20);

/* Twitter "written by" label: never empty (posts whose author account no longer exists). */
add_filter('wpseo_enhanced_slack_data', function ($data) {
    foreach ((array) $data as $label => $value) {
        if ($value === '' || $value === null) {
            $data[$label] = kmpr_seo_brand();
        }
    }
    return $data;
}, 20);

/* =========================================================================
 * Schema: posts are blog posts
 * ====================================================================== */
add_filter('wpseo_schema_article_type', function ($type) {
    return is_singular('post') ? 'BlogPosting' : $type;
}, 20);

/* =========================================================================
 * XML sitemaps
 * ====================================================================== */
add_filter('wpseo_sitemap_exclude_post_type', function ($exclude, $post_type) {
    return in_array($post_type, array('rating', 'project_request', 'attachment'), true) ? true : $exclude;
}, 20, 2);

add_filter('wpseo_sitemap_exclude_taxonomy', function ($exclude, $taxonomy) {
    return in_array($taxonomy, array('post_tag', 'post_format', 'portfolio_tag', 'services_tag', 'services_categories'), true) ? true : $exclude;
}, 20, 2);

/* Pages that shadow a post type archive URL (/portfolios/, /services/) and the boilerplate privacy page. */
add_filter('wpseo_exclude_from_sitemap_by_post_ids', function ($ids) {
    $ids = (array) $ids;
    foreach (array('portfolio', 'services', 'game') as $pt) {
        $obj = get_post_type_object($pt);
        if ($obj && $obj->has_archive) {
            $slug = is_string($obj->has_archive) ? $obj->has_archive : ($obj->rewrite['slug'] ?? $pt);
            $page = get_page_by_path($slug);
            if ($page) {
                $ids[] = (int) $page->ID;
            }
        }
    }
    $privacy = (int) get_option('wp_page_for_privacy_policy');
    if ($privacy && !preg_match('/\p{Arabic}/u', (string) get_post_field('post_content', $privacy))) {
        $ids[] = $privacy;
    }
    return array_values(array_unique(array_filter($ids)));
});

/* =========================================================================
 * Breadcrumbs (Yoast) — Persian labels for archives
 * ====================================================================== */
add_filter('wpseo_breadcrumb_links', function ($links) {
    $labels = array('portfolio' => 'نمونه‌کارها', 'services' => 'خدمات', 'game' => 'بازی‌ها');
    foreach ($links as $i => $link) {
        if (!empty($link['ptarchive']) && isset($labels[$link['ptarchive']])) {
            $links[$i]['text'] = $labels[$link['ptarchive']];
        }
        if ($i === 0) {
            $links[$i]['text'] = 'خانه';
        }
    }

    // Course / project category crumb before the current item (Yoast only adds it when indexables are built).
    $map = array('post' => 'category', 'portfolio' => 'portfolio_categories');
    if (is_singular(array_keys($map)) && count($links) >= 2) {
        $id  = get_queried_object_id();
        $tax = $map[get_post_type($id)];
        foreach ($links as $link) {
            if (!empty($link['term_id']) || (!empty($link['term']) && is_object($link['term']))) {
                return $links;
            }
        }
        $term    = null;
        $primary = (int) get_post_meta($id, '_yoast_wpseo_primary_' . $tax, true);
        if ($primary) {
            $term = get_term($primary, $tax);
        }
        if (!$term || is_wp_error($term)) {
            $term = function_exists('kmpr_primary_term') ? kmpr_primary_term($id, $tax) : null;
        }
        if ($term && !is_wp_error($term) && $term->slug !== 'uncategorized') {
            $text = $tax === 'category' && function_exists('get_field') ? (get_field('course_title', $term) ?: $term->name) : $term->name;
            array_splice($links, count($links) - 1, 0, array(array('url' => get_term_link($term), 'text' => $text, 'term_id' => $term->term_id)));
        }
    }
    return $links;
}, 20);

/* =========================================================================
 * robots.txt — keep it strictly valid (Lighthouse "robots-txt" audit)
 * ====================================================================== */
add_filter('robots_txt', function ($output, $public = '1') {
    $lines = array();
    foreach (preg_split('/\r\n|\r|\n/', (string) $output) as $line) {
        $trim = trim($line);
        // Non-standard directives (e.g. llms-txt:) become comments.
        if ($trim !== '' && $trim[0] !== '#' && !preg_match('/^(user-agent|allow|disallow|sitemap|crawl-delay|host|clean-param)\s*:/i', $trim)) {
            $line = '# ' . $trim;
        }
        $lines[] = $line;
    }
    $output = implode("\n", $lines);

    // Admin area is never useful to crawlers (admin-ajax stays open for rendering).
    if (stripos($output, 'Disallow: /wp-admin/') === false) {
        $output = preg_replace('/^User-agent: \*\s*$/mi', "User-agent: *\nDisallow: /wp-admin/\nAllow: /wp-admin/admin-ajax.php", $output, 1);
    }

    // AI crawler groups + pointer comments to /llms.txt (inc/llms-manager.php). Only when the site is public.
    if ($public && function_exists('kmpr_llms_robots_block')) {
        $output = rtrim($output) . "\n" . kmpr_llms_robots_block($output);
    }
    return rtrim($output) . "\n";
}, PHP_INT_MAX, 2);

/* =========================================================================
 * Web app manifest + theme colour
 * ====================================================================== */
add_action('init', function () {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    if ($path !== '/site.webmanifest') {
        return;
    }
    $icons   = array();
    $site_id = (int) get_option('site_icon');
    foreach (array(192, 512) as $size) {
        $img = $site_id ? wp_get_attachment_image_src($site_id, array($size, $size)) : false;
        if ($img && !isset($icons[$img[0]])) {
            $icons[$img[0]] = array('src' => $img[0], 'sizes' => (int) $img[1] . 'x' . (int) $img[2], 'type' => 'image/png');
        }
    }
    $icons = array_values($icons);
    $icons[] = array('src' => get_template_directory_uri() . '/public/images/kmpr-favicon.svg', 'sizes' => 'any', 'type' => 'image/svg+xml');

    header('Content-Type: application/manifest+json; charset=utf-8');
    header('Cache-Control: public, max-age=86400');
    echo wp_json_encode(array(
        'name'             => kmpr_seo_brand() . ' | طراح و توسعه‌دهنده وب',
        'short_name'       => kmpr_seo_brand(),
        'lang'             => 'fa-IR',
        'dir'              => 'rtl',
        'start_url'        => '/',
        'scope'            => '/',
        'display'          => 'browser',
        'background_color' => '#020617',
        'theme_color'      => '#020617',
        'icons'            => $icons,
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}, 0);

add_action('wp_head', function () {
    echo '<meta name="theme-color" content="#020617">' . "\n";
    echo '<link rel="manifest" href="' . esc_url(home_url('/site.webmanifest')) . '">' . "\n";
}, 3);

/* Perfmatters "critical images": never pick Alpine templates (<img :src>) — they produced <link rel="preload" href="">. */
add_filter('perfmatters_critical_image_exclusions', function ($exclusions) {
    $exclusions[] = ':src=';
    $exclusions[] = 'x-bind:src';
    return $exclusions;
});
