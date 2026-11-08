<?php
/**
 * Portfolio extras: archive options page (hero + CTA copy), archive pagination,
 * shared card/case-study data helper and Schema.org enrichment of the CreativeWork node.
 *
 * Per-project fields live in inc/acf-fields.php (group_644e9b3b64240).
 *
 * @package KMPR
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Defaults for the portfolio archive / case-study copy (used when the option is empty).
 */
function kmpr_pf_defaults()
{
    return array(
        'pfa_kicker'      => 'نمونه‌کارها',
        'pfa_title'       => 'پروژه‌هایی که از صفر طراحی و کدنویسی کرده‌ام',
        'pfa_text'        => 'هر پروژه یک مسئله واقعی کسب‌وکار بوده است؛ از وب‌سایت شرکتی و فروشگاه اینترنتی تا لندینگ و پنل‌های اختصاصی. روی هر کارت بروید تا کل صفحه را ببینید.',
        'pfa_per_page'    => 12,
        'pfa_cta_kicker'  => 'پروژه بعدی',
        'pfa_cta_title'   => 'پروژه بعدی این صفحه می‌تواند مال شما باشد',
        'pfa_cta_text'    => 'چند سوال کوتاه درباره پروژه‌تان جواب دهید؛ خودم بررسی می‌کنم و با یک برآورد شفاف از زمان و هزینه با شما تماس می‌گیرم.',
        'pfa_cta_btn'     => 'ثبت درخواست پروژه',
        'pfs_badge'       => 'کیس‌استادی',
        'pfs_more_title'  => 'پروژه‌های دیگر',
    );
}

/**
 * Read a portfolio option with its default.
 */
function kmpr_pf_opt($name)
{
    $defaults = kmpr_pf_defaults();
    $default  = $defaults[$name] ?? '';
    return function_exists('kmpr_field') ? kmpr_field($name, $default, 'option') : $default;
}

/* -------------------------------------------------------------------------
 * Options sub-page under «نمونه‌کارها» + its fields
 * ---------------------------------------------------------------------- */
add_action('acf/init', function () {
    if (function_exists('acf_add_options_sub_page')) {
        acf_add_options_sub_page(array(
            'page_title'  => 'تنظیمات صفحه نمونه‌کارها',
            'menu_title'  => 'تنظیمات آرشیو',
            'menu_slug'   => 'kmpr-portfolio-settings',
            'parent_slug' => 'edit.php?post_type=portfolio',
            'capability'  => 'edit_posts',
        ));
    }
});

add_action('acf/include_fields', function () {
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }
    $d = kmpr_pf_defaults();
    $f = function ($key, $name, $label, $type = 'text', $extra = array()) use ($d) {
        return array_merge(array(
            'key'           => 'field_kmpr_' . $key,
            'name'          => $name,
            'label'         => $label,
            'type'          => $type,
            'default_value' => $d[$name] ?? '',
        ), $extra);
    };
    $tab = function ($key, $label) {
        return array('key' => 'field_kmpr_' . $key, 'label' => $label, 'type' => 'tab', 'placement' => 'top');
    };

    acf_add_local_field_group(array(
        'key'      => 'group_kmpr_portfolio_archive',
        'title'    => 'محتوای صفحه نمونه‌کارها',
        'fields'   => array(
            $tab('pfa_tab_hero', 'هدر آرشیو'),
            $f('pfa_kicker', 'pfa_kicker', 'برچسب بالای تیتر'),
            $f('pfa_title', 'pfa_title', 'تیتر اصلی (H1)'),
            $f('pfa_text', 'pfa_text', 'توضیح', 'textarea', array('rows' => 3)),
            $f('pfa_per_page', 'pfa_per_page', 'تعداد پروژه در هر صفحه', 'number', array('min' => 3, 'max' => 48)),

            $tab('pfa_tab_cta', 'دعوت به همکاری (پایین صفحه)'),
            $f('pfa_cta_kicker', 'pfa_cta_kicker', 'برچسب'),
            $f('pfa_cta_title', 'pfa_cta_title', 'تیتر'),
            $f('pfa_cta_text', 'pfa_cta_text', 'توضیح', 'textarea', array('rows' => 2)),
            $f('pfa_cta_btn', 'pfa_cta_btn', 'متن دکمه'),

            $tab('pfs_tab_single', 'صفحه هر پروژه'),
            $f('pfs_badge', 'pfs_badge', 'برچسب بالای عنوان پروژه'),
            $f('pfs_more_title', 'pfs_more_title', 'تیتر بخش «پروژه‌های دیگر»'),
        ),
        'location' => array(array(array('param' => 'options_page', 'operator' => '==', 'value' => 'kmpr-portfolio-settings'))),
    ));
});

/* -------------------------------------------------------------------------
 * Real pagination for the archive + category archives
 * ---------------------------------------------------------------------- */
add_action('pre_get_posts', function ($query) {
    if (is_admin() || !$query->is_main_query()) {
        return;
    }
    if ($query->is_post_type_archive('portfolio') || $query->is_tax('portfolio_categories')) {
        $per_page = (int) kmpr_pf_opt('pfa_per_page');
        $query->set('posts_per_page', $per_page > 0 ? $per_page : 12);
        $query->set('ignore_sticky_posts', true);
    }
});

/* -------------------------------------------------------------------------
 * Data helper shared by cards and the case study
 * ---------------------------------------------------------------------- */

/**
 * Normalised data for a portfolio item. Only real field values — never invented.
 */
function kmpr_pf_data($post_id = null, $cover_size = 'medium_large')
{
    $post_id = $post_id ?: get_the_ID();
    $get     = function ($name) use ($post_id) {
        return function_exists('get_field') ? get_field($name, $post_id) : get_post_meta($post_id, $name, true);
    };

    $site_raw = $get('portfolio-website');
    $site     = function_exists('kmpr_link') ? kmpr_link($site_raw) : array('url' => is_array($site_raw) ? ($site_raw['url'] ?? '') : (string) $site_raw);
    $site_url = (string) ($site['url'] ?? '');
    // Ignore links that point back to this local/dev install (placeholder URLs).
    if ($site_url && strpos($site_url, 'localhost') !== false) {
        $site_url = '';
    }
    // Archived projects: the client site is offline or no longer ours — never link out to it.
    $archived = (bool) $get('site_archived');
    if ($archived) {
        $site_url = '';
    }

    $logo = $get('brand-logo');
    if (is_numeric($logo)) {
        $src  = wp_get_attachment_image_src((int) $logo, 'thumbnail');
        $logo = $src ? array('url' => $src[0], 'alt' => '') : null;
    }

    $theme_labels   = array('custom' => 'قالب اختصاصی', 'ready' => 'قالب آماده', 'headless' => 'Headless / Next.js');
    $builder_labels = array('none' => 'بدون صفحه‌ساز', 'elementor' => 'المنتور', 'gutenberg' => 'گوتنبرگ', 'acf' => 'ACF');
    $theme_type     = (string) $get('theme_type');
    $page_builder   = (string) $get('page_builder');

    $term  = function_exists('kmpr_primary_term') ? kmpr_primary_term($post_id, 'portfolio_categories') : null;
    $title = get_the_title($post_id);

    $results = array();
    foreach ((array) $get('results') as $row) {
        if (is_array($row) && trim((string) ($row['value'] ?? '')) !== '' && trim((string) ($row['label'] ?? '')) !== '') {
            $results[] = array('value' => trim((string) $row['value']), 'label' => trim((string) $row['label']));
        }
    }

    $shots = array();
    foreach ((array) $get('screenshot') as $row) {
        $img = is_array($row) ? ($row['screenshot-image'] ?? null) : null;
        $dev = is_array($row) ? ($row['screen-size'] ?? 'laptop') : 'laptop';
        if (!$img || isset($shots[$dev])) {
            continue;
        }
        $id = is_array($img) ? (int) ($img['ID'] ?? $img['id'] ?? 0) : (int) $img;
        if ($id) {
            $full = wp_get_attachment_image_src($id, 'full');
            if ($full) {
                $shots[$dev] = array('id' => $id, 'url' => $full[0], 'width' => (int) $full[1], 'height' => (int) $full[2]);
            }
        } elseif (is_array($img) && !empty($img['url'])) {
            $shots[$dev] = array('id' => 0, 'url' => $img['url'], 'width' => (int) ($img['width'] ?? 1440), 'height' => (int) ($img['height'] ?? 900));
        }
    }

    $client = trim((string) $get('client_name'));

    return array(
        'id'         => $post_id,
        'title'      => $title,
        'link'       => get_permalink($post_id),
        'client'     => $client,
        'logo'       => (is_array($logo) && !empty($logo['url'])) ? $logo : null,
        'logo_bg'    => (string) ($get('logo_background') ?: 'bg-slate-900'),
        'year'       => trim((string) $get('project_year')),
        'role'       => trim((string) $get('project_role')),
        'duration'   => trim((string) $get('project_duration')),
        'stack'      => function_exists('kmpr_lines') ? kmpr_lines($get('tech_stack')) : array(),
        'challenge'  => (string) $get('challenge'),
        'solution'   => (string) $get('solution'),
        'results'    => $results,
        'theme'      => $theme_labels[$theme_type] ?? '',
        'builder'    => $builder_labels[$page_builder] ?? '',
        'term'       => $term,
        'archived'   => $archived,
        'site'       => $site_url,
        'host'       => $site_url ? preg_replace('#^https?://(www\.)?#', '', untrailingslashit($site_url)) : '',
        'cover'      => function_exists('kmpr_portfolio_cover') ? kmpr_portfolio_cover($post_id, $cover_size, 'laptop') : null,
        'shots'      => $shots,
        'initial'    => function_exists('mb_substr') ? mb_substr($client ?: $title, 0, 1) : substr($client ?: $title, 0, 1),
        'excerpt'    => has_excerpt($post_id) ? get_the_excerpt($post_id) : wp_trim_words(wp_strip_all_tags(strip_shortcodes(get_post_field('post_content', $post_id))), 22, '…'),
    );
}

/**
 * Split a result value like "+۴۰٪" into prefix / integer / suffix for the counter.
 * Returns null when the value is not a plain integer (e.g. "1.2s") so it is printed as-is.
 */
function kmpr_pf_counter($value)
{
    $latin = function_exists('kmpr_latin_digits') ? kmpr_latin_digits($value) : $value;
    if (preg_match('/^(\D*?)(\d{1,9})(\D*)$/u', trim($latin), $m)) {
        return array('prefix' => $m[1], 'number' => (int) $m[2], 'suffix' => $m[3]);
    }
    return null;
}

/* -------------------------------------------------------------------------
 * Schema.org: enrich Yoast's CreativeWork node (added by inc/schema-manager.php)
 * ---------------------------------------------------------------------- */
add_filter('wpseo_schema_graph', function ($graph) {
    if (!is_singular('portfolio') || !is_array($graph)) {
        return $graph;
    }
    $data = kmpr_pf_data(get_queried_object_id(), 'full');
    foreach ($graph as &$piece) {
        if (!is_array($piece) || ($piece['@type'] ?? '') !== 'CreativeWork') {
            continue;
        }
        $piece['genre']        = $data['term'] ? $data['term']->name : null;
        $piece['keywords']     = $data['stack'] ? implode(', ', $data['stack']) : null;
        $piece['dateCreated']  = get_the_date('c', $data['id']);
        $piece['dateModified'] = get_the_modified_date('c', $data['id']);
        if ($data['client']) {
            $piece['sourceOrganization'] = array_filter(array('@type' => 'Organization', 'name' => $data['client'], 'url' => $data['site'] ?: null));
        }
        if ($data['site']) {
            $piece['sameAs'] = $data['site'];
        }
        if ($data['shots']) {
            $piece['image'] = array_values(array_map(function ($s) {
                return $s['url'];
            }, $data['shots']));
        }
        $piece = array_filter($piece, function ($v) {
            return $v !== null && $v !== '';
        });
    }
    unset($piece);
    return $graph;
}, 30);
