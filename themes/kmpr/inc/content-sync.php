<?php
/**
 * Content sync bridge.
 *
 * Remote tools (e.g. the site's MCP connector) can only write non-protected post meta.
 * This module lets them queue changes that need core APIs by writing `kmpr_sync_*` meta:
 *
 *   kmpr_sync_thumbnail   attachment ID         → set_post_thumbnail()
 *   kmpr_sync_excerpt     string                → post_excerpt
 *   kmpr_sync_date        'Y-m-d H:i:s'         → post_date / post_date_gmt
 *   kmpr_sync_menu_order  int                   → menu_order
 *   kmpr_sync_slug        string                → post_name
 *   kmpr_sync_status      publish|draft|private → post_status
 *   kmpr_sync_terms       {"taxonomy":["slug",…]}  → wp_set_object_terms()
 *   kmpr_sync_acf         {"field_name": value, …} → update_field() (repeaters as arrays)
 *   kmpr_sync_yoast       {"title":…, "desc":…, "focuskw":…}
 *
 * Every queued key is applied once and then deleted. Only processes posts that carry the flag
 * `kmpr_sync_pending` = 1, so the cost is a single indexed meta query per request batch.
 *
 * @package KMPR
 */

if (!defined('ABSPATH')) {
    exit;
}

function kmpr_sync_process($limit = 50)
{
    $ids = get_posts(array(
        'post_type'      => 'any',
        'post_status'    => 'any',
        'posts_per_page' => $limit,
        'fields'         => 'ids',
        'meta_key'       => 'kmpr_sync_pending',
        'meta_value'     => '1',
        'no_found_rows'  => true,
        'suppress_filters' => true,
    ));

    foreach ($ids as $id) {
        $post_update = array('ID' => $id);
        $decode      = function ($v) {
            if (is_array($v)) {
                return $v;
            }
            $d = json_decode((string) $v, true);
            return is_array($d) ? $d : array();
        };

        if ($thumb = (int) get_post_meta($id, 'kmpr_sync_thumbnail', true)) {
            if (get_post_type($thumb) === 'attachment') {
                set_post_thumbnail($id, $thumb);
            }
        }
        if (metadata_exists('post', $id, 'kmpr_sync_excerpt')) {
            $post_update['post_excerpt'] = wp_kses_post(get_post_meta($id, 'kmpr_sync_excerpt', true));
        }
        if ($date = get_post_meta($id, 'kmpr_sync_date', true)) {
            $ts = strtotime($date);
            if ($ts) {
                $post_update['post_date']     = gmdate('Y-m-d H:i:s', $ts + (int) (get_option('gmt_offset') * HOUR_IN_SECONDS));
                $post_update['post_date_gmt'] = gmdate('Y-m-d H:i:s', $ts);
                $post_update['edit_date']     = true;
            }
        }
        if (metadata_exists('post', $id, 'kmpr_sync_menu_order')) {
            $post_update['menu_order'] = (int) get_post_meta($id, 'kmpr_sync_menu_order', true);
        }
        if ($slug = get_post_meta($id, 'kmpr_sync_slug', true)) {
            $post_update['post_name'] = sanitize_title($slug);
        }
        if ($status = get_post_meta($id, 'kmpr_sync_status', true)) {
            if (in_array($status, array('publish', 'draft', 'private', 'pending'), true)) {
                $post_update['post_status'] = $status;
            }
        }
        if (count($post_update) > 1) {
            wp_update_post(wp_slash($post_update));
        }

        foreach ($decode(get_post_meta($id, 'kmpr_sync_terms', true)) as $tax => $slugs) {
            if (taxonomy_exists($tax)) {
                wp_set_object_terms($id, array_map('sanitize_title', (array) $slugs), $tax, false);
            }
        }

        if (function_exists('update_field')) {
            foreach ($decode(get_post_meta($id, 'kmpr_sync_acf', true)) as $name => $value) {
                update_field($name, $value, $id);
            }
        }

        $yoast = $decode(get_post_meta($id, 'kmpr_sync_yoast', true));
        if ($yoast) {
            if (isset($yoast['title'])) {
                update_post_meta($id, '_yoast_wpseo_title', sanitize_text_field($yoast['title']));
            }
            if (isset($yoast['desc'])) {
                update_post_meta($id, '_yoast_wpseo_metadesc', sanitize_text_field($yoast['desc']));
            }
            if (isset($yoast['focuskw'])) {
                update_post_meta($id, '_yoast_wpseo_focuskw', sanitize_text_field($yoast['focuskw']));
            }
        }

        foreach (array('thumbnail', 'excerpt', 'date', 'menu_order', 'slug', 'status', 'terms', 'acf', 'yoast', 'pending') as $k) {
            delete_post_meta($id, 'kmpr_sync_' . $k);
        }
        clean_post_cache($id);
    }
    return count($ids);
}

/**
 * Term-level queue: term meta `kmpr_sync_acf` (JSON) on any term → update_field( …, 'term_{id}' ).
 * Option-level queue: option `kmpr_sync_options` (JSON) → update_field( …, 'option' ).
 */
function kmpr_sync_process_options()
{
    $raw = get_option('kmpr_sync_options');
    if (!$raw || !function_exists('update_field')) {
        return;
    }
    $data = is_array($raw) ? $raw : json_decode((string) $raw, true);
    foreach ((array) $data as $name => $value) {
        update_field($name, $value, 'option');
    }
    delete_option('kmpr_sync_options');
}

/**
 * Site-level queue (written remotely through the ACF option tool, stored as option `options_kmpr_sync_site`):
 * {
 *   "options": {"blogname": "…", "blogdescription": "…"},                 // whitelisted core options
 *   "terms": [{"taxonomy":"category","match":"<name or slug>","name":"…","slug":"…","description":"…","parent_slug":"…","acf":{…},"yoast":{"title":"…","desc":"…"}}],
 *   "menu_items": [{"id":123,"title":"…","object":"page","object_slug":"resume"}]
 * }
 */
function kmpr_sync_process_site()
{
    $raw = get_option('options_kmpr_sync_site');
    if (!$raw) {
        return;
    }
    $data = is_array($raw) ? $raw : json_decode(wp_unslash((string) $raw), true);
    if (!is_array($data)) {
        delete_option('options_kmpr_sync_site');
        return;
    }

    $log     = array();
    $allowed = array('blogname', 'blogdescription');
    foreach ((array) ($data['options'] ?? array()) as $key => $value) {
        if (in_array($key, $allowed, true)) {
            update_option($key, sanitize_text_field($value));
        }
    }

    $renamed = (array) get_option('kmpr_term_slug_map', array());
    foreach ((array) ($data['terms'] ?? array()) as $t) {
        $tax = $t['taxonomy'] ?? '';
        if (!taxonomy_exists($tax)) {
            continue;
        }
        $match = (string) ($t['match'] ?? ($t['slug'] ?? ''));
        $term  = get_term_by('slug', sanitize_title($match), $tax) ?: get_term_by('name', $match, $tax);
        $args  = array();
        foreach (array('name', 'description') as $k) {
            if (isset($t[$k])) {
                $args[$k] = $t[$k];
            }
        }
        if (!empty($t['slug'])) {
            $args['slug'] = sanitize_title($t['slug']);
        }
        if (!empty($t['parent_slug']) && ($parent = get_term_by('slug', $t['parent_slug'], $tax))) {
            $args['parent'] = $parent->term_id;
        }
        if ($term) {
            if (!empty($args['slug']) && $term->slug !== $args['slug']) {
                $renamed[$tax][urldecode($term->slug)] = $args['slug'];
            }
            wp_update_term($term->term_id, $tax, $args);
            $term_id = $term->term_id;
        } else {
            $res     = wp_insert_term($t['name'] ?? $match, $tax, $args);
            $term_id = is_wp_error($res) ? 0 : (int) $res['term_id'];
        }
        if ($term_id && !empty($t['acf']) && function_exists('update_field')) {
            foreach ((array) $t['acf'] as $name => $value) {
                update_field($name, $value, $tax . '_' . $term_id);
            }
        }
        if ($term_id && !empty($t['yoast'])) {
            $meta = get_option('wpseo_taxonomy_meta', array());
            $meta[$tax][$term_id]['wpseo_title'] = sanitize_text_field($t['yoast']['title'] ?? '');
            $meta[$tax][$term_id]['wpseo_desc']  = sanitize_text_field($t['yoast']['desc'] ?? '');
            update_option('wpseo_taxonomy_meta', $meta);
        }
    }
    update_option('kmpr_term_slug_map', $renamed, false);

    foreach ((array) ($data['menu_items'] ?? array()) as $mi) {
        $id = (int) ($mi['id'] ?? 0);
        if (!$id || get_post_type($id) !== 'nav_menu_item') {
            continue;
        }
        if (isset($mi['title'])) {
            wp_update_post(array('ID' => $id, 'post_title' => sanitize_text_field($mi['title'])));
        }
        if (!empty($mi['object_slug'])) {
            $target = get_page_by_path(sanitize_title($mi['object_slug']), OBJECT, $mi['object'] ?? 'page');
            if ($target) {
                update_post_meta($id, '_menu_item_type', 'post_type');
                update_post_meta($id, '_menu_item_object', $target->post_type);
                update_post_meta($id, '_menu_item_object_id', $target->ID);
            }
        }
    }

    if (!empty($data['acf_options']) && function_exists('update_field')) {
        foreach ((array) $data['acf_options'] as $name => $value) {
            update_field($name, kmpr_sync_resolve($value), 'option');
        }
    }
    foreach ((array) ($data['posts'] ?? array()) as $p) {
        $res = kmpr_sync_upsert_post((array) $p);
        $log[] = (is_wp_error($res) ? 'ERR ' . $res->get_error_message() : $res) . ' ' . ($p['type'] ?? '') . ':' . ($p['slug'] ?? '');
    }
    foreach ((array) ($data['menus'] ?? array()) as $m) {
        $log[] = kmpr_sync_menu((array) $m);
    }
    if (!empty($data['batch'])) {
        $done = (array) get_option('kmpr_sync_log', array());
        $done[sanitize_key($data['batch'])] = array('at' => gmdate('c'), 'log' => array_slice($log ?? array(), 0, 200));
        update_option('kmpr_sync_log', array_slice($done, -40, null, true), false);
    }

    delete_option('options_kmpr_sync_site');
    delete_option('_options_kmpr_sync_site');
}

/**
 * Resolve portable tokens inside imported values (recursively):
 *   "media:<file name>"      → attachment ID (matched on the uploaded file name, "-scaled"/"-1" suffixes ignored)
 *   "media-url:<file name>"  → attachment URL
 *   "post:<type>:<slug>"     → post ID
 *   "term:<taxonomy>:<slug>" → term ID
 * and rewrites local dev URLs (KMPR_SYNC_LOCAL_URL / http://localhost:10039) to this site's home URL.
 */
function kmpr_sync_resolve($v)
{
    if (is_array($v)) {
        return array_map('kmpr_sync_resolve', $v);
    }
    if (!is_string($v)) {
        return $v;
    }
    if (preg_match('/^media:(.+)$/', $v, $m)) {
        return kmpr_sync_media_id($m[1]) ?: 0;
    }
    if (preg_match('/^media-url:(.+)$/', $v, $m)) {
        $id = kmpr_sync_media_id($m[1]);
        return $id ? (string) wp_get_attachment_url($id) : '';
    }
    if (preg_match('/^post:([a-z0-9_-]+):(.+)$/', $v, $m)) {
        $post = get_page_by_path($m[2], OBJECT, $m[1]);
        return $post ? (int) $post->ID : 0;
    }
    if (preg_match('/^term:([a-z0-9_-]+):(.+)$/', $v, $m)) {
        $t = get_term_by('slug', $m[2], $m[1]);
        return $t ? (int) $t->term_id : 0;
    }
    $local = defined('KMPR_SYNC_LOCAL_URL') ? KMPR_SYNC_LOCAL_URL : 'http://localhost:10039';
    if (strpos($v, $local) !== false) {
        $v = str_replace($local, untrailingslashit(home_url()), $v);
    }
    // Inline content images: {{media-url:file.webp}}
    return preg_replace_callback('/\{\{media-url:([^}]+)\}\}/', function ($m) {
        $id = kmpr_sync_media_id($m[1]);
        return $id ? (string) wp_get_attachment_url($id) : '';
    }, $v);
}

function kmpr_sync_media_id($file)
{
    global $wpdb;
    static $cache = array();
    $file = basename(trim($file));
    if ($file === '') {
        return 0;
    }
    if (isset($cache[$file])) {
        return $cache[$file];
    }
    $info = pathinfo($file);
    $base = preg_replace('/(-scaled|-\d+)$/', '', $info['filename']);
    $like = '%' . $wpdb->esc_like($base) . '%.' . $wpdb->esc_like($info['extension'] ?? '');
    $rows = $wpdb->get_results($wpdb->prepare("SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value LIKE %s ORDER BY post_id DESC", $like)); // phpcs:ignore
    $best = 0;
    foreach ($rows as $r) {
        $name = pathinfo($r->meta_value, PATHINFO_FILENAME);
        if (preg_replace('/(-scaled|-\d+)$/', '', $name) === $base) {
            $best = (int) $r->post_id;
            break;
        }
    }
    return $cache[$file] = $best;
}

/**
 * Create or update one post from a portable record:
 * { type, slug, match:[alt slugs], id, title, content, excerpt, status, date, menu_order, parent:"post:type:slug",
 *   terms:{tax:[slugs]}, meta:{key:value}, acf:{name:value}, yoast:{title,desc,focuskw}, thumbnail:"media:file", alt_by_media:{file:alt} }
 */
function kmpr_sync_upsert_post(array $p)
{
    $type = sanitize_key($p['type'] ?? 'post');
    $slug = sanitize_title($p['slug'] ?? '');
    if (!post_type_exists($type) || $slug === '') {
        return new WP_Error('bad', 'invalid type/slug');
    }
    $post = null;
    if (!empty($p['id']) && get_post_type((int) $p['id']) === $type) {
        $post = get_post((int) $p['id']);
    }
    foreach (array_merge(array($slug), (array) ($p['match'] ?? array())) as $s) {
        if ($post) {
            break;
        }
        $found = get_posts(array('post_type' => $type, 'name' => sanitize_title(urldecode($s)), 'post_status' => 'any', 'numberposts' => 1, 'suppress_filters' => true));
        $post  = $found ? $found[0] : null;
    }

    $arr = array('post_type' => $type, 'post_name' => $slug);
    foreach (array('title' => 'post_title', 'content' => 'post_content', 'excerpt' => 'post_excerpt', 'status' => 'post_status', 'menu_order' => 'menu_order') as $k => $col) {
        if (array_key_exists($k, $p)) {
            $arr[$col] = is_string($p[$k]) ? kmpr_sync_resolve($p[$k]) : $p[$k];
        }
    }
    if (!empty($p['date']) && ($ts = strtotime($p['date'] . ' UTC'))) {
        $arr['post_date_gmt'] = gmdate('Y-m-d H:i:s', $ts);
        $arr['post_date']     = get_date_from_gmt($arr['post_date_gmt']);
        $arr['edit_date']     = true;
    }
    if (!empty($p['parent'])) {
        $arr['post_parent'] = (int) kmpr_sync_resolve($p['parent']);
    }
    if ($post) {
        $arr['ID'] = $post->ID;
        $id        = wp_update_post(wp_slash($arr), true);
        $verb      = 'updated';
    } else {
        $arr['post_status'] = $arr['post_status'] ?? 'publish';
        $id                 = wp_insert_post(wp_slash($arr), true);
        $verb               = 'created';
    }
    if (is_wp_error($id)) {
        return $id;
    }

    foreach ((array) ($p['terms'] ?? array()) as $tax => $slugs) {
        if (taxonomy_exists($tax)) {
            wp_set_object_terms($id, array_map('sanitize_title', (array) $slugs), $tax, false);
        }
    }
    foreach ((array) ($p['meta'] ?? array()) as $k => $v) {
        update_post_meta($id, sanitize_key($k), kmpr_sync_resolve($v));
    }
    if (function_exists('update_field')) {
        foreach ((array) ($p['acf'] ?? array()) as $name => $value) {
            update_field($name, kmpr_sync_resolve($value), $id);
        }
    }
    if (!empty($p['thumbnail'])) {
        $thumb = (int) kmpr_sync_resolve($p['thumbnail']);
        if ($thumb) {
            set_post_thumbnail($id, $thumb);
        }
    }
    foreach ((array) ($p['alt_by_media'] ?? array()) as $file => $alt) {
        if ($mid = kmpr_sync_media_id($file)) {
            update_post_meta($mid, '_wp_attachment_image_alt', sanitize_text_field($alt));
        }
    }
    $y = (array) ($p['yoast'] ?? array());
    foreach (array('title' => '_yoast_wpseo_title', 'desc' => '_yoast_wpseo_metadesc', 'focuskw' => '_yoast_wpseo_focuskw') as $k => $mk) {
        if (isset($y[$k])) {
            update_post_meta($id, $mk, sanitize_text_field($y[$k]));
        }
    }
    clean_post_cache($id);
    return $verb . ' #' . $id;
}

/**
 * Replace a menu's items: { location, name, items:[{title, url | object:"page|services|…", slug, archive:"post_type"}] }
 */
function kmpr_sync_menu(array $m)
{
    $locs = get_nav_menu_locations();
    $loc  = sanitize_key($m['location'] ?? '');
    $menu = !empty($locs[$loc]) ? (int) $locs[$loc] : 0;
    if (!$menu) {
        $menu = wp_create_nav_menu(sanitize_text_field($m['name'] ?? $loc));
        if (is_wp_error($menu)) {
            return 'menu ERR ' . $loc;
        }
        $locs[$loc] = $menu;
        set_theme_mod('nav_menu_locations', $locs);
    }
    foreach ((array) wp_get_nav_menu_items($menu) as $old) {
        wp_delete_post($old->ID, true);
    }
    $n = 0;
    foreach ((array) ($m['items'] ?? array()) as $i => $it) {
        $args = array('menu-item-title' => sanitize_text_field($it['title'] ?? ''), 'menu-item-status' => 'publish', 'menu-item-position' => $i + 1);
        if (!empty($it['archive']) && post_type_exists($it['archive'])) {
            $args += array('menu-item-type' => 'post_type_archive', 'menu-item-object' => $it['archive']);
        } elseif (!empty($it['object']) && !empty($it['slug']) && ($obj = get_page_by_path($it['slug'], OBJECT, $it['object']))) {
            $args += array('menu-item-type' => 'post_type', 'menu-item-object' => $obj->post_type, 'menu-item-object-id' => $obj->ID);
        } else {
            $args += array('menu-item-type' => 'custom', 'menu-item-url' => esc_url_raw(kmpr_sync_resolve((string) ($it['url'] ?? home_url('/')))));
        }
        if (!is_wp_error(wp_update_nav_menu_item($menu, 0, $args))) {
            $n++;
        }
    }
    return 'menu ' . $loc . ': ' . $n . ' items';
}

add_action('wp_loaded', function () {
    // Cheap guard: run at most once every 20s unless something is queued.
    if (get_transient('kmpr_sync_lock')) {
        return;
    }
    set_transient('kmpr_sync_lock', 1, 20);
    kmpr_sync_process();
    kmpr_sync_process_options();
    kmpr_sync_process_site();
});
