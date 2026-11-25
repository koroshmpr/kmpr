<?php
/**
 * «گیم‌سنتر در سایت» — one reusable inline games strip for non-game pages.
 *
 * Placements (each switchable in «بازی‌ها › تنظیمات آرشیو › گیم‌سنتر در سایت»):
 *  - 404 page (template line `kmpr_games_promo('404')`)
 *  - end of blog articles (single.php line `kmpr_games_promo('post')`, optional category filter)
 *  - anywhere via shortcode `[kmpr_games count="3" title="…" games="slug,slug"]`
 * Master switch off → prints nothing. Pure HTML/CSS (no games JS bundle on these pages).
 * In-article game banners stay in inc/article-banners.php (`[kmpr_banner type="game"]`).
 *
 * @package KMPR
 */

if (!defined('ABSPATH')) {
    exit;
}

function kmpr_gp_defaults()
{
    return array(
        'gp_kicker'    => 'وقت استراحت · گیم‌سنتر',
        'gp_title'     => 'یک دست بازی، بعد ادامه!',
        'gp_title_404' => 'این صفحه گم شد، ولی بازی‌ها سر جاشونن!',
        'gp_text'      => 'بازی‌های کوچکی که خودم از صفر ساخته‌ام؛ رایگان، بدون نصب و بدون ثبت‌نام. روی گوشی هم عالی اجرا می‌شوند.',
        'gp_btn'       => 'همه بازی‌ها',
        'gp_count'     => 3,
    );
}

function kmpr_gp_opt($name)
{
    $d = kmpr_gp_defaults();
    return function_exists('kmpr_field') ? kmpr_field($name, $d[$name] ?? '', 'option') : ($d[$name] ?? '');
}

/** true_false read (never saved → $default). */
function kmpr_gp_flag($name, $default = true)
{
    $v = function_exists('get_field') ? get_field($name, 'option') : null;
    return ($v === null || $v === '') ? $default : (bool) $v;
}

function kmpr_gp_place_on($place)
{
    if (!post_type_exists('game') || !kmpr_gp_flag('gp_on')) {
        return false;
    }
    if ($place === 'shortcode') {
        return true;
    }
    if (!kmpr_gp_flag('gp_on_' . $place)) {
        return false;
    }
    if ($place === 'post') {
        $cats = array_filter(array_map('intval', (array) kmpr_field('gp_post_cats', array(), 'option')));
        if ($cats && !has_category($cats, get_queried_object_id())) {
            return false;
        }
    }
    return true;
}

add_action('acf/include_fields', function () {
    if (!function_exists('acf_add_local_field_group') || !function_exists('kmpr_acf')) {
        return;
    }
    $d  = kmpr_gp_defaults();
    $on = fn($name, $label, $w = 25) => kmpr_acf('field_kmpr_' . $name, $name, $label, 'true_false', array('ui' => 1, 'default_value' => 1, 'wrapper' => array('width' => $w)));
    acf_add_local_field_group(array(
        'key'        => 'group_kmpr_games_promo',
        'title'      => 'گیم‌سنتر در سایت (۴۰۴، پایان مقالات، شورت‌کد)',
        'fields'     => array(
            array('key' => 'field_kmpr_gp_msg', 'label' => '', 'name' => '', 'type' => 'message', 'message' => 'یک نوار کوچک «یک دست بازی» در صفحات غیربازی. شورت‌کد: <code>[kmpr_games count="3" title="…" games="neon-snake,xo"]</code>. بنر بازی داخل متن مقاله همان <code>[kmpr_banner type="game"]</code> است.'),
            $on('gp_on', 'روشن (کلید اصلی)'),
            $on('gp_on_404', 'صفحه ۴۰۴'),
            $on('gp_on_post', 'پایان مقالات'),
            kmpr_acf('field_kmpr_gp_count', 'gp_count', 'تعداد بازی', 'number', array('min' => 2, 'max' => 4, 'default_value' => $d['gp_count'], 'wrapper' => array('width' => 25))),
            kmpr_acf('field_kmpr_gp_games', 'gp_games', 'بازی‌ها', 'relationship', array('post_type' => array('game'), 'filters' => array('search'), 'return_format' => 'id', 'max' => 12, 'instructions' => 'خالی = بازی ویژه + محبوب‌ترین‌ها. هر روز چند تای متفاوت از این فهرست نمایش داده می‌شود.')),
            kmpr_acf('field_kmpr_gp_post_cats', 'gp_post_cats', 'فقط در این دسته‌های مقاله', 'taxonomy', array('taxonomy' => 'category', 'field_type' => 'multi_select', 'return_format' => 'id', 'add_term' => 0, 'save_terms' => 0, 'load_terms' => 0, 'allow_null' => 1, 'instructions' => 'خالی = همه مقالات.')),
            kmpr_acf('field_kmpr_gp_kicker', 'gp_kicker', 'برچسب بالا', 'text', array('default_value' => $d['gp_kicker'], 'wrapper' => array('width' => 50))),
            kmpr_acf('field_kmpr_gp_btn', 'gp_btn', 'متن دکمه', 'text', array('default_value' => $d['gp_btn'], 'wrapper' => array('width' => 50))),
            kmpr_acf('field_kmpr_gp_title', 'gp_title', 'تیتر', 'text', array('default_value' => $d['gp_title'], 'wrapper' => array('width' => 50))),
            kmpr_acf('field_kmpr_gp_title_404', 'gp_title_404', 'تیتر در ۴۰۴', 'text', array('default_value' => $d['gp_title_404'], 'wrapper' => array('width' => 50))),
            kmpr_acf('field_kmpr_gp_text', 'gp_text', 'توضیح', 'textarea', array('rows' => 2, 'new_lines' => '', 'default_value' => $d['gp_text'])),
        ),
        'location'   => array(array(array('param' => 'options_page', 'operator' => '==', 'value' => 'kmpr-games-settings'))),
        'menu_order' => 25,
    ));
});

/**
 * Game ids to show: picked list (rotating daily) or featured + most played.
 */
function kmpr_gp_pick($count, $slugs = array())
{
    $count = max(1, min(4, (int) $count));
    if ($slugs) {
        $ids = get_posts(array('post_type' => 'game', 'post_status' => 'publish', 'post_name__in' => $slugs, 'orderby' => 'post_name__in', 'fields' => 'ids', 'posts_per_page' => $count, 'no_found_rows' => true));
        return array_slice($ids, 0, $count);
    }
    $ids = array_values(array_filter(array_map('intval', (array) kmpr_field('gp_games', array(), 'option')), fn($id) => get_post_status($id) === 'publish'));
    if ($ids) {
        $off = count($ids) > $count ? abs(crc32('gp' . wp_date('Y-m-d'))) % count($ids) : 0;
        return array_slice(array_merge(array_slice($ids, $off), array_slice($ids, 0, $off)), 0, $count);
    }
    $all = get_posts(array('post_type' => 'game', 'post_status' => 'publish', 'posts_per_page' => 60, 'fields' => 'ids', 'no_found_rows' => true));
    if (!$all) {
        return array();
    }
    $stats = function_exists('kmpr_games_stats') ? kmpr_games_stats() : array('games' => array());
    usort($all, function ($a, $b) use ($stats) {
        $fa = (int) (bool) kmpr_game_meta($a, 'game_is_featured', false);
        $fb = (int) (bool) kmpr_game_meta($b, 'game_is_featured', false);
        return $fb <=> $fa ?: (($stats['games'][$b]['plays'] ?? 0) <=> ($stats['games'][$a]['plays'] ?? 0));
    });
    return array_slice($all, 0, $count);
}

/**
 * Render the strip. $place: '404' | 'post' | 'shortcode'.
 */
function kmpr_games_promo($place = 'post', $args = array())
{
    if (!kmpr_gp_place_on($place) || !function_exists('kmpr_game_data')) {
        return;
    }
    $ids = kmpr_gp_pick($args['count'] ?? kmpr_gp_opt('gp_count'), $args['games'] ?? array());
    if (!$ids) {
        return;
    }
    $GLOBALS['kmpr_gp_shown'] = true;
    get_template_part('template-parts/games/promo', null, array(
        'ids'    => $ids,
        'place'  => $place,
        'kicker' => kmpr_gp_opt('gp_kicker'),
        'title'  => ($args['title'] ?? '') !== '' ? $args['title'] : kmpr_gp_opt($place === '404' ? 'gp_title_404' : 'gp_title'),
        'text'   => kmpr_gp_opt('gp_text'),
        'btn'    => kmpr_gp_opt('gp_btn'),
    ));
}

add_shortcode('kmpr_games', function ($atts) {
    $a     = shortcode_atts(array('count' => '', 'title' => '', 'games' => ''), $atts, 'kmpr_games');
    $slugs = array_filter(array_map('sanitize_title', explode(',', (string) $a['games'])));
    ob_start();
    kmpr_games_promo('shortcode', array_filter(array('count' => $a['count'] !== '' ? (int) $a['count'] : null, 'title' => sanitize_text_field($a['title']), 'games' => $slugs)));
    return ob_get_clean();
});
