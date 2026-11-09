<?php
/**
 * In-article banners: [kmpr_banner] shortcode + a «بنر» button in the classic editor (TinyMCE + text tab).
 *
 *   [kmpr_banner type="game"      item="chess"]               → a game from the Game Center (post type game)
 *   [kmpr_banner type="service"   item="seo"]                 → a service page (post type services)
 *   [kmpr_banner type="portfolio" item="psb-co"]              → a portfolio project (post type portfolio)
 *   [kmpr_banner type="course"    item="seo-course"]          → a course (category) — cross-links courses
 *   [kmpr_banner type="ad"        item="<ad slug>"]           → an ad slot from «تنظیمات سایت → بنرهای مقالات»
 *   [kmpr_banner type="ad" title="…" text="…" url="…" cta="…" image="<url or attachment id>" sponsor="…"]
 *
 * Optional overrides on every type: title, text, cta, kicker, image.
 * Internal banners are normal links (internal linking); ads are labelled «تبلیغ» and use rel="sponsored noopener".
 * A banner whose target no longer exists (deleted game, expired/disabled ad) renders nothing.
 *
 * @package KMPR
 */

if (!defined('ABSPATH')) {
    exit;
}

/** Visual + copy defaults per banner type. */
function kmpr_banner_types()
{
    return array(
        'game'      => array('label' => 'بازی',     'kicker' => 'یک استراحت کوتاه · گیم‌سنتر', 'cta' => 'همین حالا بازی کن', 'accent' => '#e879f9'),
        'service'   => array('label' => 'خدمات',    'kicker' => 'خدمات کوروش',                 'cta' => 'جزئیات و سفارش',    'accent' => '#f59e0b'),
        'portfolio' => array('label' => 'نمونه‌کار', 'kicker' => 'نمونه‌کار واقعی',              'cta' => 'دیدن نمونه‌کار',     'accent' => '#38bdf8'),
        'course'    => array('label' => 'دوره',     'kicker' => 'دوره مرتبط',                  'cta' => 'شروع دوره',         'accent' => '#34d399'),
        'ad'        => array('label' => 'تبلیغ',    'kicker' => 'تبلیغ',                       'cta' => 'مشاهده',            'accent' => '#a78bfa'),
    );
}

/* -------------------------------------------------------------------------
 * Ad slots (ACF options): managed centrally, placed by slug, can expire.
 * ---------------------------------------------------------------------- */

add_action('acf/init', function () {
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }
    if (function_exists('acf_add_options_sub_page')) {
        acf_add_options_sub_page(array(
            'page_title'  => 'بنرهای تبلیغاتی مقالات',
            'menu_title'  => 'بنرهای مقالات',
            'menu_slug'   => 'acf-options-article-banners',
            'parent_slug' => 'acf-options-theme-settings',
        ));
    }
    acf_add_local_field_group(array(
        'key'      => 'group_kmpr_article_ads',
        'title'    => 'بنرهای تبلیغاتی',
        'fields'   => array(
            array('key' => 'field_kmpr_ads', 'name' => 'kmpr_article_ads', 'label' => 'بنرها', 'type' => 'repeater', 'layout' => 'block', 'button_label' => 'افزودن بنر',
                'instructions' => 'هر بنر با شناسه‌اش در مقاله قرار می‌گیرد: [kmpr_banner type="ad" item="شناسه"] — یا از دکمه «بنر» در ویرایشگر. بنر غیرفعال یا منقضی‌شده در هیچ مقاله‌ای نمایش داده نمی‌شود.',
                'sub_fields' => array(
                    array('key' => 'field_kmpr_ad_slug', 'name' => 'slug', 'label' => 'شناسه (انگلیسی)', 'type' => 'text', 'required' => 1, 'wrapper' => array('width' => '25')),
                    array('key' => 'field_kmpr_ad_active', 'name' => 'active', 'label' => 'فعال', 'type' => 'true_false', 'ui' => 1, 'default_value' => 1, 'wrapper' => array('width' => '15')),
                    array('key' => 'field_kmpr_ad_until', 'name' => 'until', 'label' => 'نمایش تا تاریخ', 'type' => 'date_picker', 'return_format' => 'Y-m-d', 'wrapper' => array('width' => '30')),
                    array('key' => 'field_kmpr_ad_sponsor', 'name' => 'sponsor', 'label' => 'نام اسپانسر', 'type' => 'text', 'wrapper' => array('width' => '30')),
                    array('key' => 'field_kmpr_ad_title', 'name' => 'title', 'label' => 'عنوان', 'type' => 'text', 'required' => 1, 'wrapper' => array('width' => '50')),
                    array('key' => 'field_kmpr_ad_cta', 'name' => 'cta', 'label' => 'متن دکمه', 'type' => 'text', 'wrapper' => array('width' => '20')),
                    array('key' => 'field_kmpr_ad_url', 'name' => 'url', 'label' => 'لینک', 'type' => 'url', 'required' => 1, 'wrapper' => array('width' => '30')),
                    array('key' => 'field_kmpr_ad_text', 'name' => 'text', 'label' => 'توضیح کوتاه', 'type' => 'textarea', 'rows' => 2, 'new_lines' => '', 'wrapper' => array('width' => '70')),
                    array('key' => 'field_kmpr_ad_image', 'name' => 'image', 'label' => 'تصویر (افقی)', 'type' => 'image', 'return_format' => 'id', 'preview_size' => 'thumbnail', 'wrapper' => array('width' => '30')),
                ),
            ),
        ),
        'location' => array(array(array('param' => 'options_page', 'operator' => '==', 'value' => 'acf-options-article-banners'))),
    ));
});

/** Active ad slots keyed by slug. */
function kmpr_banner_ads()
{
    static $ads = null;
    if ($ads !== null) {
        return $ads;
    }
    $ads   = array();
    $rows  = function_exists('get_field') ? get_field('kmpr_article_ads', 'option') : array();
    $today = wp_date('Y-m-d');
    foreach ((array) $rows as $r) {
        $slug = sanitize_title($r['slug'] ?? '');
        if ($slug === '' || empty($r['active']) || empty($r['url']) || (!empty($r['until']) && $r['until'] < $today)) {
            continue;
        }
        $ads[$slug] = $r;
    }
    return $ads;
}

/* -------------------------------------------------------------------------
 * Data resolution
 * ---------------------------------------------------------------------- */

/**
 * Resolve a banner to {type, title, text, url, image (attachment id|url), cta, kicker, external, sponsor} or null.
 */
function kmpr_banner_data(array $a)
{
    $type  = sanitize_key($a['type'] ?? '');
    $types = kmpr_banner_types();
    if (!isset($types[$type])) {
        return null;
    }
    $item = sanitize_title($a['item'] ?? '');
    $d    = array('type' => $type, 'title' => '', 'text' => '', 'url' => '', 'image' => 0, 'external' => false, 'sponsor' => '', 'accent' => $types[$type]['accent']);
    $trim = function ($s, $words = 22) {
        return wp_trim_words(wp_strip_all_tags(strip_shortcodes((string) $s)), $words, '…');
    };

    if (in_array($type, array('game', 'service', 'portfolio'), true)) {
        $pt   = array('game' => 'game', 'service' => 'services', 'portfolio' => 'portfolio')[$type];
        $post = $item !== '' ? get_page_by_path($item, OBJECT, $pt) : null;
        if (!$post || $post->post_status !== 'publish') {
            return null;
        }
        $d['title'] = get_the_title($post);
        $d['text']  = $trim(has_excerpt($post) ? $post->post_excerpt : $post->post_content);
        $d['url']   = get_permalink($post);
        $d['image'] = (int) get_post_thumbnail_id($post);
    } elseif ($type === 'course') {
        $term = $item !== '' ? get_term_by('slug', $item, 'category') : null;
        $info = $term ? kmpr_course_info($term) : null;
        if (!$info || !$info['count']) {
            return null;
        }
        $d['title']  = $info['title'];
        $d['text']   = $trim($info['intro'], 24);
        $d['url']    = $info['url'];
        $d['image']  = $info['pillar'] ? (int) get_post_thumbnail_id($info['pillar']) : 0;
        $d['accent'] = kmpr_course_palette($info['color'])['hex'] ?? $d['accent'];
        $d['meta']   = kmpr_fa_num($info['count']) . ' درس · ' . kmpr_minutes_label($info['minutes']);
    } else { // ad
        if ($item !== '') {
            $ad = kmpr_banner_ads()[$item] ?? null;
            if (!$ad) {
                return null;
            }
            $a += array('title' => $ad['title'], 'text' => $ad['text'] ?? '', 'url' => $ad['url'], 'cta' => $ad['cta'] ?? '', 'image' => $ad['image'] ?? '', 'sponsor' => $ad['sponsor'] ?? '');
        }
        if (empty($a['title']) || empty($a['url'])) {
            return null;
        }
        $d['url']      = esc_url_raw($a['url']);
        $d['external'] = wp_parse_url($d['url'], PHP_URL_HOST) && wp_parse_url($d['url'], PHP_URL_HOST) !== wp_parse_url(home_url(), PHP_URL_HOST);
        $d['sponsor']  = sanitize_text_field($a['sponsor'] ?? '');
    }

    foreach (array('title', 'text', 'cta', 'kicker') as $k) {
        if (!empty($a[$k])) {
            $d[$k] = sanitize_text_field($a[$k]);
        }
    }
    if (!empty($a['image'])) {
        $d['image'] = is_numeric($a['image']) ? (int) $a['image'] : esc_url_raw($a['image']);
    }
    $d['cta']    = $d['cta'] ?? $types[$type]['cta'];
    $d['kicker'] = $d['kicker'] ?? ($type === 'ad' && $d['sponsor'] ? 'تبلیغ · ' . $d['sponsor'] : $types[$type]['kicker']);
    return $d;
}

/* -------------------------------------------------------------------------
 * Rendering
 * ---------------------------------------------------------------------- */

function kmpr_banner_icon($type)
{
    $paths = array(
        'game'      => '<rect x="2.5" y="6.5" width="19" height="11" rx="5.5"/><path d="M7.5 10.5v3M6 12h3"/><circle cx="15.5" cy="11" r=".9" fill="currentColor"/><circle cx="17.5" cy="13" r=".9" fill="currentColor"/>',
        'service'   => '<path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18l3 3 6.3-6.3a4 4 0 0 0 5.4-5.4l-2.6 2.6-2.4-.6-.6-2.4z"/>',
        'portfolio' => '<rect x="3" y="4" width="18" height="16" rx="3"/><path d="M3 9h18"/><circle cx="6.5" cy="6.5" r=".6" fill="currentColor"/><circle cx="9" cy="6.5" r=".6" fill="currentColor"/>',
        'course'    => '<path d="M2 7l10-4 10 4-10 4z"/><path d="M6 9v5c0 1.7 2.7 3 6 3s6-1.3 6-3V9"/>',
        'ad'        => '<path d="M3 11v2a1 1 0 0 0 1 1h3l6 4V6L7 10H4a1 1 0 0 0-1 1z"/><path d="M17 9a4 4 0 0 1 0 6"/>',
    );
    return '<svg width="14" height="14" class="kb-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($paths[$type] ?? '') . '</svg>';
}

function kmpr_banner_html(array $d)
{
    $img = '';
    if (is_int($d['image']) && $d['image']) {
        $img = wp_get_attachment_image($d['image'], 'medium_large', false, array(
            'class' => 'kb-img', 'alt' => $d['title'], 'loading' => 'lazy', 'decoding' => 'async', 'fetchpriority' => 'low',
            'sizes' => '(min-width: 640px) 220px, 96px',
        ));
    } elseif (is_string($d['image']) && $d['image'] !== '') {
        $img = '<img class="kb-img" src="' . esc_url($d['image']) . '" alt="' . esc_attr($d['title']) . '" width="640" height="400" loading="lazy" decoding="async">';
    }
    $rel = $d['type'] === 'ad' ? ' rel="sponsored noopener"' . ($d['external'] ? ' target="_blank"' : '') : '';

    ob_start(); ?>
<aside class="kb-banner kb-banner--<?= esc_attr($d['type']); ?>" style="--kb:<?= esc_attr($d['accent']); ?>" aria-label="<?= esc_attr(kmpr_banner_types()[$d['type']]['label'] . ': ' . $d['title']); ?>" data-kb-type="<?= esc_attr($d['type']); ?>">
    <a class="kb-link" href="<?= esc_url($d['url']); ?>"<?= $rel; // phpcs:ignore -- static attribute string ?>>
        <span class="kb-media"><?= $img ?: kmpr_banner_icon($d['type']); // phpcs:ignore -- core-escaped image / static svg ?></span>
        <span class="kb-body">
            <span class="kb-kicker"><?= kmpr_banner_icon($d['type']); // phpcs:ignore ?><?= esc_html($d['kicker']); ?></span>
            <span class="kb-title"><?= esc_html($d['title']); ?></span>
            <?php if ($d['text']) : ?><span class="kb-text"><?= esc_html($d['text']); ?></span><?php endif; ?>
            <?php if (!empty($d['meta'])) : ?><span class="kb-meta"><?= esc_html($d['meta']); ?></span><?php endif; ?>
        </span>
        <span class="kb-cta"><?= esc_html($d['cta']); ?><svg width="16" height="16" class="kb-arrow" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.61l4.16 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.61 9.25h10.64A.75.75 0 0 1 17 10Z" clip-rule="evenodd"/></svg></span>
    </a>
</aside>
    <?php
    return trim(ob_get_clean());
}

add_shortcode('kmpr_banner', function ($atts) {
    $d = kmpr_banner_data((array) $atts);
    if (!$d) {
        return '';
    }
    // Plain-text contexts (feeds, llms.txt / .md exports): a single readable link, no card markup.
    if (is_feed() || !empty($GLOBALS['kmpr_llms_rendering'])) {
        return '<p><a href="' . esc_url($d['url']) . '">' . esc_html($d['title']) . '</a></p>';
    }
    return kmpr_banner_html($d);
});

/* Shortcode on its own line: wpautop wraps it in <p>…</p>, which is invalid around <aside>. */
add_filter('the_content', function ($html) {
    return preg_replace('#<p>\s*(<aside class="kb-banner[\s\S]*?</aside>)\s*</p>#', '$1', $html);
}, 20);

/* -------------------------------------------------------------------------
 * Editor: «بنر» button (TinyMCE visual tab + Quicktags text tab)
 * ---------------------------------------------------------------------- */

/** Everything the editor dialog can pick from: "type:item" => label. */
function kmpr_banner_choices()
{
    $types = kmpr_banner_types();
    $out   = array();
    foreach (array('service' => 'services', 'portfolio' => 'portfolio', 'game' => 'game') as $type => $pt) {
        foreach (get_posts(array('post_type' => $pt, 'numberposts' => 200, 'orderby' => 'title', 'order' => 'ASC', 'suppress_filters' => true)) as $p) {
            $out[] = array('value' => $type . ':' . $p->post_name, 'text' => $types[$type]['label'] . ' — ' . get_the_title($p));
        }
    }
    if (function_exists('kmpr_courses')) {
        foreach (kmpr_courses() as $c) {
            $out[] = array('value' => 'course:' . get_term($c['id'])->slug, 'text' => 'دوره — ' . $c['title']);
        }
    }
    foreach (kmpr_banner_ads() as $slug => $ad) {
        $out[] = array('value' => 'ad:' . $slug, 'text' => 'تبلیغ — ' . $ad['title']);
    }
    $out[] = array('value' => 'ad:', 'text' => 'تبلیغ سفارشی (فیلدهای پایین را پر کنید)');
    return $out;
}

function kmpr_banner_editor_screen()
{
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    return $screen && $screen->base === 'post' && post_type_supports($screen->post_type, 'editor') && current_user_can('edit_posts');
}

add_filter('mce_external_plugins', function ($plugins) {
    if (kmpr_banner_editor_screen()) {
        $plugins['kmpr_banner'] = get_template_directory_uri() . '/public/js/editor-banner.js?v=' . filemtime(get_template_directory() . '/public/js/editor-banner.js');
    }
    return $plugins;
});

add_filter('mce_buttons', function ($buttons) {
    if (kmpr_banner_editor_screen()) {
        $buttons[] = 'kmpr_banner';
    }
    return $buttons;
});

add_action('admin_print_footer_scripts', function () {
    if (!kmpr_banner_editor_screen()) {
        return;
    }
    $data = array('choices' => kmpr_banner_choices());
    echo '<script>window.kmprBanner=' . wp_json_encode($data, JSON_UNESCAPED_UNICODE) . ';</script>';
    if (wp_script_is('quicktags')) {
        // Text tab: same dialog is not available there, so insert a ready-to-edit shortcode.
        echo "<script>QTags&&QTags.addButton('kmpr_banner','بنر','[kmpr_banner type=\"service\" item=\"seo\"]','','','درج بنر داخل مقاله',200);</script>";
    }
}, 50);
