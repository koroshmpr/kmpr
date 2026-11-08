<?php
/**
 * Owner panel — REST API kmpr/v1/panel/*.
 *
 * Auth: cookie + X-WP-Nonce (wp_rest); every route's permission_callback requires manage_options.
 * Responses are never cached (Cache-Control: no-store) and never indexed.
 * All reads/writes go through the existing modules' functions, options and tables.
 *
 * @package KMPR
 */

if (!defined('ABSPATH')) {
    exit;
}

/* =========================================================================
 * Small utilities
 * ====================================================================== */

function kmpr_panel_err($message, $status = 400, $code = 'kmpr_panel')
{
    return new WP_Error($code, $message, array('status' => $status));
}

/** ACF update by field key (falls back to raw meta/option without ACF). */
function kmpr_panel_set_field($key, $name, $value, $post_id)
{
    if (function_exists('update_field')) {
        return update_field($key, $value, $post_id);
    }
    if ($post_id === 'option') {
        return update_option('options_' . $name, $value);
    }
    return update_post_meta((int) $post_id, $name, $value);
}

function kmpr_panel_get_field($name, $post_id, $default = null)
{
    $v = function_exists('get_field') ? get_field($name, $post_id, false) : ($post_id === 'option' ? get_option('options_' . $name, null) : get_post_meta((int) $post_id, $name, true));
    return ($v === null || $v === '' || $v === false) ? $default : $v;
}

/** Local site midnight (today) as a GMT MySQL datetime, optionally N days earlier. */
function kmpr_panel_day_start($days_back = 0)
{
    $local_midnight = strtotime(gmdate('Y-m-d 00:00:00', time() + kmpr_panel_offset())) - kmpr_panel_offset();
    return gmdate('Y-m-d H:i:s', $local_midnight - $days_back * DAY_IN_SECONDS);
}

/** Sum of the arcade «play started» counters (pl.{game}) in an kmpr_am_sum() result; optional per game. */
function kmpr_panel_am_plays($sums, $game_id = 0)
{
    if ($game_id) {
        return (int) ($sums['pl.' . (int) $game_id] ?? 0);
    }
    $n = 0;
    foreach ((array) $sums as $k => $v) {
        if (preg_match('/^pl\.\d+$/', $k)) {
            $n += (int) $v;
        }
    }
    return $n;
}

function kmpr_panel_am_sum($days, $offset = 0)
{
    return function_exists('kmpr_am_sum') ? kmpr_am_sum($days, $offset) : array();
}

/** Bust every cached leaderboard (games module transients) after a moderation change. */
function kmpr_panel_bust_boards($game_ids = null)
{
    if ($game_ids === null) {
        $game_ids = get_posts(array('post_type' => 'game', 'post_status' => 'any', 'posts_per_page' => 200, 'fields' => 'ids', 'no_found_rows' => true));
    }
    foreach ((array) $game_ids as $gid) {
        kmpr_game_bust_cache((int) $gid);
    }
    delete_transient('kmpr_games_top_players');
}

function kmpr_panel_count_new_requests()
{
    global $wpdb;
    return (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = '_kmpr_status' WHERE p.post_type = %s AND p.post_status = 'publish' AND m.meta_value = 'new'",
        'project_request'
    ));
}

function kmpr_panel_live_rooms_count()
{
    global $wpdb;
    if (!function_exists('kmpr_games_rooms_table')) {
        return 0;
    }
    $now = time();
    return (int) $wpdb->get_var($wpdb->prepare(
        'SELECT COUNT(*) FROM ' . kmpr_games_rooms_table() . " WHERE (status = 'waiting' AND seen0 > %d) OR (status = 'playing' AND GREATEST(seen0, seen1) > %d)", // phpcs:ignore
        $now - 30,
        $now - 60
    ));
}

/** Nav badges (cheap; also embedded in the shell). */
function kmpr_panel_badges()
{
    $c = wp_count_comments();
    return array(
        'requests' => kmpr_panel_count_new_requests(),
        'comments' => (int) ($c->moderated ?? 0),
        'live'     => kmpr_panel_live_rooms_count(),
    );
}

/** Count posts of a type/status created since a GMT datetime. */
function kmpr_panel_count_posts_since($type, $status, $since_gmt = '')
{
    global $wpdb;
    $sql = $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s", $type, $status);
    if ($since_gmt !== '') {
        $sql .= $wpdb->prepare(' AND post_date_gmt >= %s', $since_gmt);
    }
    return (int) $wpdb->get_var($sql); // phpcs:ignore -- prepared above
}

/* =========================================================================
 * Route registration
 * ====================================================================== */

add_action('rest_api_init', function () {
    $r = function ($path, $methods, $cb, $args = array()) {
        register_rest_route('kmpr/v1', '/panel/' . $path, array(
            'methods'             => $methods,
            'callback'            => $cb,
            'permission_callback' => function () {
                return kmpr_panel_can() ? true : kmpr_panel_err('دسترسی ندارید.', rest_authorization_required_code(), 'rest_forbidden');
            },
            'args'                => $args,
        ));
    };
    $pid  = '(?P<pid>[a-fA-F0-9-]{36})';
    $int  = array('type' => 'integer', 'minimum' => 0);

    $r('dashboard', 'GET', 'kmpr_panel_rest_dashboard');
    $r('badges', 'GET', function () {
        return kmpr_panel_badges();
    });
    $r('comments/(?P<id>\d+)', 'POST', 'kmpr_panel_rest_comment', array('action' => array('type' => 'string', 'enum' => array('approve', 'spam', 'trash'), 'required' => true)));

    $r('games', 'GET', 'kmpr_panel_rest_games');
    $r('games/(?P<id>\d+)', 'POST', 'kmpr_panel_rest_game_update');
    $r('games/(?P<id>\d+)/reset', 'POST', 'kmpr_panel_rest_game_reset');
    $r('games/(?P<id>\d+)/logo', 'POST', 'kmpr_panel_rest_game_logo');

    $r('players', 'GET', 'kmpr_panel_rest_players', array(
        'search' => array('type' => 'string', 'default' => ''),
        'offset' => $int + array('default' => 0),
        'order'  => array('type' => 'string', 'enum' => array('last_seen', 'xp', 'plays', 'new'), 'default' => 'last_seen'),
    ));
    $r('players/prune', 'POST', 'kmpr_panel_rest_players_prune', array(
        'days' => array('type' => 'integer', 'enum' => array(30, 60, 90, 180, 365), 'required' => true),
        'dry'  => array('type' => 'boolean', 'default' => true),
    ));
    $r('players/' . $pid, 'GET', 'kmpr_panel_rest_player');
    $r('players/' . $pid, 'POST', 'kmpr_panel_rest_player_update', array(
        'action'   => array('type' => 'string', 'enum' => array('rename', 'reset'), 'required' => true),
        'nickname' => array('type' => 'string', 'default' => ''),
        'xp'       => array('type' => 'boolean', 'default' => false),
    ));
    $r('players/' . $pid, 'DELETE', 'kmpr_panel_rest_player_delete');

    $r('online', 'GET', 'kmpr_panel_rest_online');
    $r('online/(?P<code>[A-Za-z0-9]{6})/close', 'POST', 'kmpr_panel_rest_room_close');

    $r('revenue', 'GET', 'kmpr_panel_rest_revenue', array('range' => array('type' => 'integer', 'enum' => array(7, 30, 90), 'default' => 30)));
    $r('revenue/settings', 'POST', 'kmpr_panel_rest_revenue_settings');
    $r('presignups', 'GET', 'kmpr_panel_rest_presignups', array('offset' => $int + array('default' => 0)));
    $r('presignups/(?P<id>\d+)', 'DELETE', 'kmpr_panel_rest_presignup_delete');

    $r('requests', 'GET', 'kmpr_panel_rest_requests', array(
        'status' => array('type' => 'string', 'default' => 'all'),
        'offset' => $int + array('default' => 0),
    ));
    $r('requests/(?P<id>\d+)', 'POST', 'kmpr_panel_rest_request_update');

    $r('settings', 'GET', 'kmpr_panel_rest_settings');
    $r('settings', 'POST', 'kmpr_panel_rest_settings_update');
    $r('settings/cleanup', 'POST', 'kmpr_panel_rest_cleanup');
    $r('settings/pwa-reset', 'POST', 'kmpr_panel_rest_pwa_reset');
    $r('settings/telegram-test', 'POST', 'kmpr_panel_rest_tg_test');
    $r('settings/llms', 'POST', 'kmpr_panel_rest_llms');
});

/** No caching / indexing of any panel response. */
add_filter('rest_post_dispatch', function ($response, $server, $request) {
    if ($response instanceof WP_REST_Response && strpos($request->get_route(), '/kmpr/v1/panel/') === 0) {
        $response->header('Cache-Control', 'no-store, private, max-age=0');
        $response->header('X-Robots-Tag', 'noindex, nofollow');
    }
    return $response;
}, 10, 3);

/* =========================================================================
 * Dashboard
 * ====================================================================== */

function kmpr_panel_rest_dashboard()
{
    global $wpdb;
    $pt     = kmpr_games_players_table();
    $st     = kmpr_games_table();
    $today  = kmpr_panel_day_start(0);
    $week   = kmpr_panel_day_start(6);
    $prev   = kmpr_panel_day_start(13);
    $s1     = kmpr_panel_am_sum(1);
    $s7     = kmpr_panel_am_sum(7);
    $s7p    = kmpr_panel_am_sum(7, 7);
    $cnt    = function ($sql, ...$args) use ($wpdb) {
        return (int) $wpdb->get_var($wpdb->prepare($sql, ...$args)); // phpcs:ignore
    };

    // Pass funnel (7 days): impressions → clicks → pre-signups, both variants and surfaces.
    $funnel = array('i' => 0, 'c' => 0, 's' => 0);
    foreach ($s7 as $k => $v) {
        if (preg_match('/^pass\.(hub|over)\.[AB]\.(i|c|s)$/', $k, $m)) {
            $funnel[$m[2]] += (int) $v;
        }
    }

    // Top games this week: play starts (arcade counters) + active players (scores table).
    $active = array();
    foreach ((array) $wpdb->get_results($wpdb->prepare("SELECT game_id, COUNT(*) n FROM {$st} WHERE last_played_at >= %s GROUP BY game_id", $week), ARRAY_A) as $row) { // phpcs:ignore
        $active[(int) $row['game_id']] = (int) $row['n'];
    }
    $top = array();
    foreach (get_posts(array('post_type' => 'game', 'post_status' => 'publish', 'posts_per_page' => 100, 'fields' => 'ids', 'no_found_rows' => true)) as $gid) {
        $plays = kmpr_panel_am_plays($s7, $gid);
        if ($plays || !empty($active[$gid])) {
            $top[] = array('id' => $gid, 'title' => get_the_title($gid), 'plays' => $plays, 'players' => $active[$gid] ?? 0);
        }
    }
    usort($top, function ($a, $b) {
        return array($b['plays'], $b['players']) <=> array($a['plays'], $a['players']);
    });

    // Daily play starts (14 days) for the trend bars.
    $raw   = function_exists('kmpr_am_stats_raw') ? kmpr_am_stats_raw() : array();
    $trend = array();
    for ($i = 13; $i >= 0; $i--) {
        $ts  = time() - $i * DAY_IN_SECONDS;
        $day = wp_date('Ymd', $ts);
        $trend[] = array('d' => kmpr_panel_date($ts, 'j F'), 'n' => kmpr_panel_am_plays($raw[$day] ?? array()));
    }

    // Pending comments (latest 5) for quick moderation.
    $pending = array();
    foreach (get_comments(array('status' => 'hold', 'number' => 5, 'type' => 'comment')) as $c) {
        $pending[] = array(
            'id'     => (int) $c->comment_ID,
            'author' => $c->comment_author,
            'text'   => wp_trim_words(wp_strip_all_tags($c->comment_content), 30),
            'post'   => get_the_title($c->comment_post_ID),
            'url'    => get_permalink($c->comment_post_ID),
            'date'   => kmpr_panel_when(strtotime($c->comment_date_gmt . ' UTC')),
        );
    }

    $storage = kmpr_games_storage_bytes();
    $verdict = function_exists('kmpr_am_verdict') ? kmpr_am_verdict(kmpr_panel_am_sum(30), kmpr_am_prices()) : null;

    return array(
        'cards'    => array(
            'players'   => array(
                'today' => $cnt("SELECT COUNT(*) FROM {$pt} WHERE first_seen >= %s", $today),
                'week'  => $cnt("SELECT COUNT(*) FROM {$pt} WHERE first_seen >= %s", $week),
                'prev'  => $cnt("SELECT COUNT(*) FROM {$pt} WHERE first_seen >= %s AND first_seen < %s", $prev, $week),
            ),
            'active'    => array(
                'today' => $cnt("SELECT COUNT(*) FROM {$pt} WHERE last_seen_at >= %s", $today),
                'week'  => $cnt("SELECT COUNT(*) FROM {$pt} WHERE last_seen_at >= %s", $week),
            ),
            'plays'     => array('today' => kmpr_panel_am_plays($s1), 'week' => kmpr_panel_am_plays($s7), 'prev' => kmpr_panel_am_plays($s7p)),
            'matches'   => array('today' => kmpr_panel_daily_sum('om', 1), 'week' => kmpr_panel_daily_sum('om', 7), 'live' => kmpr_panel_live_rooms_count()),
            'requests'  => array('today' => kmpr_panel_count_posts_since('project_request', 'publish', $today), 'week' => kmpr_panel_count_posts_since('project_request', 'publish', $week), 'new' => kmpr_panel_count_new_requests()),
            'comments'  => array('pending' => (int) (wp_count_comments()->moderated ?? 0)),
            'signups'   => array('today' => kmpr_panel_count_posts_since('arcade_presignup', 'private', $today), 'week' => kmpr_panel_count_posts_since('arcade_presignup', 'private', $week), 'total' => kmpr_panel_count_posts_since('arcade_presignup', 'private')),
            'funnel'    => $funnel + array('tone' => $verdict['tone'] ?? 'wait'),
        ),
        'top'      => array_slice($top, 0, 6),
        'trend'    => $trend,
        'pending'  => $pending,
        'storage'  => array('total' => $storage['total'], 'human' => size_format($storage['total'], 1) ?: '0 B', 'mode' => kmpr_games_storage_mode()),
        'badges'   => kmpr_panel_badges(),
        'links'    => array(
            'comments' => admin_url('edit-comments.php?comment_status=moderated'),
            'newPost'  => admin_url('post-new.php'),
            'arcade'   => get_post_type_archive_link('game'),
        ),
        'now'      => kmpr_panel_date(time(), 'j F Y') ?: '',
    );
}

function kmpr_panel_rest_comment(WP_REST_Request $req)
{
    $id = (int) $req['id'];
    if (!get_comment($id)) {
        return kmpr_panel_err('دیدگاه پیدا نشد.', 404);
    }
    $map = array('approve' => 'approve', 'spam' => 'spam', 'trash' => 'trash');
    $ok  = wp_set_comment_status($id, $map[$req['action']]);
    return $ok ? array('ok' => true, 'badges' => kmpr_panel_badges()) : kmpr_panel_err('انجام نشد.', 500);
}

/* =========================================================================
 * Games
 * ====================================================================== */

function kmpr_panel_game_item($post, $agg, $s7, $s30)
{
    $id      = $post->ID;
    $engines = kmpr_game_engines();
    $engine  = (string) kmpr_game_meta($id, 'game_engine', 'jota');
    $logo_id = (int) kmpr_panel_get_field('game_sponsor_logo', $id, 0);
    $logo    = $logo_id ? wp_get_attachment_image_url($logo_id, 'medium') : '';
    $top     = $post->post_status === 'publish' ? kmpr_game_leaderboard($id, 'all', 1) : array();
    $thumb   = get_the_post_thumbnail_url($id, 'thumbnail');
    return array(
        'id'         => $id,
        'title'      => get_the_title($id),
        'status'     => $post->post_status,
        'url'        => get_permalink($id),
        'edit'       => get_edit_post_link($id, 'raw'),
        'thumb'      => $thumb ?: '',
        'engine'     => $engine,
        'engineLabel'=> $engines[$engine]['label'] ?? $engine,
        'online'     => !empty($engines[$engine]['online']),
        'featured'   => (bool) kmpr_game_meta($id, 'game_is_featured', false),
        'tagline'    => (string) kmpr_game_meta($id, 'game_tagline'),
        'difficulty' => (string) kmpr_game_meta($id, 'game_difficulty', 'medium'),
        'plays'      => (int) ($agg[$id]['plays'] ?? 0),
        'players'    => (int) ($agg[$id]['players'] ?? 0),
        'best'       => (int) ($agg[$id]['best'] ?? 0),
        'plays7'     => kmpr_panel_am_plays($s7, $id),
        'champion'   => $top[0]['nickname'] ?? '',
        'sponsor'    => array(
            'name'    => (string) kmpr_panel_get_field('game_sponsor_name', $id, ''),
            'url'     => (string) kmpr_panel_get_field('game_sponsor_url', $id, ''),
            'logo'    => $logo_id,
            'logoUrl' => $logo ?: '',
            'from'    => (string) kmpr_panel_get_field('game_sponsor_from', $id, ''),
            'to'      => (string) kmpr_panel_get_field('game_sponsor_to', $id, ''),
            'active'  => function_exists('kmpr_am_sponsor') && kmpr_am_sponsor($id) !== null,
            'i'       => (int) ($s30['sp.' . $id . '.i'] ?? 0),
            'c'       => (int) ($s30['sp.' . $id . '.c'] ?? 0),
        ),
    );
}

function kmpr_panel_games_agg()
{
    global $wpdb;
    $out = array();
    foreach ((array) $wpdb->get_results('SELECT game_id, SUM(plays) plays, COUNT(*) players, MAX(score) best FROM ' . kmpr_games_table() . ' GROUP BY game_id', ARRAY_A) as $r) { // phpcs:ignore
        $out[(int) $r['game_id']] = $r;
    }
    return $out;
}

function kmpr_panel_rest_games()
{
    $posts = get_posts(array('post_type' => 'game', 'post_status' => array('publish', 'draft', 'pending', 'private', 'future'), 'posts_per_page' => 200, 'orderby' => array('menu_order' => 'ASC', 'title' => 'ASC'), 'no_found_rows' => true));
    $agg   = kmpr_panel_games_agg();
    $s7    = kmpr_panel_am_sum(7);
    $s30   = kmpr_panel_am_sum(30);
    $items = array();
    foreach ($posts as $p) {
        $items[] = kmpr_panel_game_item($p, $agg, $s7, $s30);
    }
    return array(
        'items'        => $items,
        'difficulties' => kmpr_game_difficulties(),
        'sponsorOn'    => function_exists('kmpr_am_on') ? kmpr_am_on('sponsor') : false,
        'mode'         => kmpr_games_storage_mode(),
    );
}

function kmpr_panel_game_or_error($id)
{
    $post = get_post((int) $id);
    if (!$post || $post->post_type !== 'game') {
        return kmpr_panel_err('بازی پیدا نشد.', 404);
    }
    return $post;
}

/** YYYYMMDD (Gregorian, ACF date_picker storage) or ''. */
function kmpr_panel_ymd($v)
{
    $v = preg_replace('/\D/', '', kmpr_latin_digits((string) $v));
    if ($v === '') {
        return '';
    }
    if (strlen($v) !== 8 || !checkdate((int) substr($v, 4, 2), (int) substr($v, 6, 2), (int) substr($v, 0, 4))) {
        return false;
    }
    return $v;
}

function kmpr_panel_rest_game_update(WP_REST_Request $req)
{
    $post = kmpr_panel_game_or_error($req['id']);
    if (is_wp_error($post)) {
        return $post;
    }
    $id = $post->ID;
    $p  = $req->get_json_params() ?: array();

    if (isset($p['status'])) {
        $status = in_array($p['status'], array('publish', 'draft'), true) ? $p['status'] : null;
        if (!$status) {
            return kmpr_panel_err('وضعیت نامعتبر است.');
        }
        if ($status !== $post->post_status) {
            $res = wp_update_post(array('ID' => $id, 'post_status' => $status), true);
            if (is_wp_error($res)) {
                return kmpr_panel_err($res->get_error_message(), 500);
            }
        }
    }
    if (array_key_exists('featured', $p)) {
        kmpr_panel_set_field('field_kmpr_game_featured', 'game_is_featured', $p['featured'] ? 1 : 0, $id);
    }
    if (array_key_exists('tagline', $p)) {
        kmpr_panel_set_field('field_kmpr_game_tagline', 'game_tagline', mb_substr(sanitize_text_field((string) $p['tagline']), 0, 90), $id);
    }
    if (array_key_exists('difficulty', $p)) {
        if (!isset(kmpr_game_difficulties()[$p['difficulty']])) {
            return kmpr_panel_err('سختی نامعتبر است.');
        }
        kmpr_panel_set_field('field_kmpr_game_difficulty', 'game_difficulty', $p['difficulty'], $id);
    }
    if (isset($p['sponsor']) && is_array($p['sponsor'])) {
        $sp = $p['sponsor'];
        if (array_key_exists('name', $sp)) {
            kmpr_panel_set_field('field_kmpr_gsp_name', 'game_sponsor_name', mb_substr(sanitize_text_field((string) $sp['name']), 0, 80), $id);
        }
        if (array_key_exists('url', $sp)) {
            $url = trim((string) $sp['url']);
            if ($url !== '' && !wp_http_validate_url($url)) {
                return kmpr_panel_err('لینک حامی معتبر نیست (باید با https:// شروع شود).', 400, 'url');
            }
            kmpr_panel_set_field('field_kmpr_gsp_url', 'game_sponsor_url', esc_url_raw($url), $id);
        }
        if (array_key_exists('logoUrl', $sp) && !array_key_exists('logo', $sp)) {
            $url = trim((string) $sp['logoUrl']);
            $aid = 0;
            if ($url !== '') {
                $aid = attachment_url_to_postid($url);
                if (!$aid) {
                    if (!wp_http_validate_url($url)) {
                        return kmpr_panel_err('لینک لوگو معتبر نیست.', 400, 'logo');
                    }
                    require_once ABSPATH . 'wp-admin/includes/media.php';
                    require_once ABSPATH . 'wp-admin/includes/file.php';
                    require_once ABSPATH . 'wp-admin/includes/image.php';
                    $aid = media_sideload_image(esc_url_raw($url), $id, 'لوگوی حامی ' . get_the_title($id), 'id');
                    if (is_wp_error($aid)) {
                        return kmpr_panel_err('دانلود لوگو ناموفق بود: ' . $aid->get_error_message(), 400, 'logo');
                    }
                }
            }
            kmpr_panel_set_field('field_kmpr_gsp_logo', 'game_sponsor_logo', (int) $aid, $id);
        }
        if (array_key_exists('logo', $sp)) {
            $aid = (int) $sp['logo'];
            if ($aid && !wp_attachment_is_image($aid)) {
                return kmpr_panel_err('لوگو باید تصویر باشد.', 400, 'logo');
            }
            kmpr_panel_set_field('field_kmpr_gsp_logo', 'game_sponsor_logo', $aid, $id);
        }
        foreach (array('from' => 'field_kmpr_gsp_from', 'to' => 'field_kmpr_gsp_to') as $k => $key) {
            if (array_key_exists($k, $sp)) {
                $ymd = kmpr_panel_ymd($sp[$k]);
                if ($ymd === false) {
                    return kmpr_panel_err('تاریخ نامعتبر است.', 400, $k);
                }
                kmpr_panel_set_field($key, 'game_sponsor_' . $k, $ymd, $id);
            }
        }
    }
    clean_post_cache($id);
    kmpr_game_bust_cache($id);
    return kmpr_panel_game_item(get_post($id), kmpr_panel_games_agg(), kmpr_panel_am_sum(7), kmpr_panel_am_sum(30));
}

/** Reset a game's leaderboard: scores → 0, play counters are kept. */
function kmpr_panel_rest_game_reset(WP_REST_Request $req)
{
    global $wpdb;
    $post = kmpr_panel_game_or_error($req['id']);
    if (is_wp_error($post)) {
        return $post;
    }
    $n = (int) $wpdb->query($wpdb->prepare('UPDATE ' . kmpr_games_table() . ' SET score = 0, duration_ms = 0 WHERE game_id = %d AND score > 0', $post->ID)); // phpcs:ignore
    kmpr_panel_bust_boards(array($post->ID));
    return array('ok' => true, 'reset' => $n);
}

function kmpr_panel_rest_game_logo(WP_REST_Request $req)
{
    $post = kmpr_panel_game_or_error($req['id']);
    if (is_wp_error($post)) {
        return $post;
    }
    $files = $req->get_file_params();
    if (empty($files['file']['tmp_name'])) {
        return kmpr_panel_err('فایلی انتخاب نشده است.');
    }
    if ((int) $files['file']['size'] > 2 * MB_IN_BYTES) {
        return kmpr_panel_err('حجم لوگو حداکثر ۲ مگابایت.');
    }
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';
    $aid = media_handle_upload('file', $post->ID, array(), array(
        'test_form' => false,
        'mimes'     => array('png' => 'image/png', 'jpg|jpeg' => 'image/jpeg', 'webp' => 'image/webp'),
    ));
    if (is_wp_error($aid)) {
        return kmpr_panel_err($aid->get_error_message());
    }
    kmpr_panel_set_field('field_kmpr_gsp_logo', 'game_sponsor_logo', (int) $aid, $post->ID);
    return array('ok' => true, 'logo' => (int) $aid, 'logoUrl' => wp_get_attachment_image_url($aid, 'medium'));
}

/* =========================================================================
 * Players
 * ====================================================================== */

function kmpr_panel_player_item($row)
{
    $lvl = kmpr_game_level($row['xp']);
    $u   = !empty($row['user_id']) ? get_userdata((int) $row['user_id']) : null;
    return array(
        'pid'       => $row['player_id'],
        'short'     => kmpr_panel_short_id($row['player_id']),
        'nickname'  => (string) $row['nickname'],
        'avatar'    => kmpr_game_avatar_clean($row['avatar']),
        'level'     => $lvl['level'],
        'xp'        => (int) $row['xp'],
        'next'      => $lvl['next'],
        'floor'     => $lvl['floor'],
        'wins'      => (int) $row['wins'],
        'losses'    => (int) $row['losses'],
        'draws'     => (int) $row['draws'],
        'plays'     => (int) $row['plays_total'],
        'ach'       => (int) ($row['ach_count'] ?? 0),
        'lastSeen'  => kmpr_panel_when(kmpr_panel_ts($row['last_seen_at'])),
        'firstSeen' => kmpr_panel_when(kmpr_panel_ts($row['first_seen'] ?? '')),
        'user'      => $u ? $u->display_name : '',
    );
}

function kmpr_panel_rest_players(WP_REST_Request $req)
{
    global $wpdb;
    $pt     = kmpr_games_players_table();
    $per    = 30;
    $offset = (int) $req['offset'];
    $search = trim(sanitize_text_field((string) $req['search']));
    $orders = array('last_seen' => 'last_seen_at DESC', 'xp' => 'xp DESC', 'plays' => 'plays_total DESC', 'new' => 'first_seen DESC');
    $order  = $orders[$req['order']] ?? $orders['last_seen'];
    $where  = '';
    if ($search !== '') {
        $like  = '%' . $wpdb->esc_like($search) . '%';
        $where = preg_match('/^[a-f0-9-]{4,36}$/i', $search)
            ? $wpdb->prepare('WHERE nickname LIKE %s OR player_id LIKE %s', $like, $wpdb->esc_like(strtolower($search)) . '%')
            : $wpdb->prepare('WHERE nickname LIKE %s', $like);
    }
    $rows  = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$pt} {$where} ORDER BY {$order}, player_id ASC LIMIT %d OFFSET %d", $per + 1, $offset), ARRAY_A); // phpcs:ignore
    $more  = count($rows) > $per;
    $items = array_map('kmpr_panel_player_item', array_slice((array) $rows, 0, $per));
    $out   = array('items' => $items, 'next' => $more ? $offset + $per : null);
    if ($offset === 0) {
        $out['total'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$pt} {$where}"); // phpcs:ignore
    }
    return $out;
}

function kmpr_panel_player_row($pid)
{
    $pid = strtolower((string) $pid);
    if (!kmpr_game_valid_player($pid)) {
        return null;
    }
    return kmpr_game_profile_row($pid);
}

function kmpr_panel_rest_player(WP_REST_Request $req)
{
    global $wpdb;
    $row = kmpr_panel_player_row($req['pid']);
    if (!$row) {
        return kmpr_panel_err('بازیکن پیدا نشد.', 404);
    }
    $games = array();
    $rows  = $wpdb->get_results($wpdb->prepare('SELECT game_id, score, plays, created_at, last_played_at FROM ' . kmpr_games_table() . ' WHERE player_id = %s ORDER BY plays DESC', $row['player_id']), ARRAY_A); // phpcs:ignore
    foreach ((array) $rows as $r) {
        $games[] = array(
            'id'      => (int) $r['game_id'],
            'title'   => get_the_title((int) $r['game_id']) ?: '#' . (int) $r['game_id'],
            'plays'   => (int) $r['plays'],
            'best'    => (int) $r['score'],
            'bestAt'  => (int) $r['score'] > 0 ? kmpr_panel_when(kmpr_panel_ts($r['created_at'])) : null,
            'last'    => kmpr_panel_when(kmpr_panel_ts($r['last_played_at'])),
        );
    }
    $item          = kmpr_panel_player_item($row);
    $item['games'] = $games;
    return $item;
}

function kmpr_panel_rest_player_update(WP_REST_Request $req)
{
    global $wpdb;
    $row = kmpr_panel_player_row($req['pid']);
    if (!$row) {
        return kmpr_panel_err('بازیکن پیدا نشد.', 404);
    }
    $pid      = $row['player_id'];
    $game_ids = array_map('intval', (array) $wpdb->get_col($wpdb->prepare('SELECT game_id FROM ' . kmpr_games_table() . ' WHERE player_id = %s', $pid))); // phpcs:ignore

    if ($req['action'] === 'rename') {
        $nick = kmpr_game_clean_nick($req['nickname']);
        $nick = mb_substr($nick, 0, 24);
        if (mb_strlen($nick) < 2) {
            return kmpr_panel_err('نام مستعار باید حداقل ۲ حرف باشد (لینک و تگ مجاز نیست).', 400, 'nickname');
        }
        $wpdb->update(kmpr_games_players_table(), array('nickname' => $nick), array('player_id' => $pid));
        $wpdb->update(kmpr_games_table(), array('nickname' => $nick), array('player_id' => $pid));
    } else {
        $wpdb->query($wpdb->prepare('UPDATE ' . kmpr_games_table() . ' SET score = 0, duration_ms = 0 WHERE player_id = %s', $pid)); // phpcs:ignore
        if ($req['xp']) {
            $wpdb->update(kmpr_games_players_table(), array('xp' => 0, 'wins' => 0, 'losses' => 0, 'draws' => 0), array('player_id' => $pid));
        }
    }
    kmpr_panel_bust_boards($game_ids);
    return kmpr_panel_rest_player($req);
}

function kmpr_panel_rest_player_delete(WP_REST_Request $req)
{
    $pid = strtolower((string) $req['pid']);
    if (!kmpr_game_valid_player($pid)) {
        return kmpr_panel_err('شناسه نامعتبر است.');
    }
    return array('ok' => true, 'deleted' => kmpr_game_delete_players(array($pid)));
}

/** Bulk delete players inactive for N days (dry run returns the count only). */
function kmpr_panel_rest_players_prune(WP_REST_Request $req)
{
    global $wpdb;
    $pt     = kmpr_games_players_table();
    $cut    = gmdate('Y-m-d H:i:s', time() - (int) $req['days'] * DAY_IN_SECONDS);
    $count  = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$pt} WHERE last_seen_at < %s", $cut)); // phpcs:ignore
    if ($req['dry']) {
        return array('ok' => true, 'count' => $count, 'dry' => true);
    }
    $deleted = 0;
    for ($i = 0; $i < 50; $i++) { // ≤ 25k rows per call
        $ids = $wpdb->get_col($wpdb->prepare("SELECT player_id FROM {$pt} WHERE last_seen_at < %s LIMIT 500", $cut)); // phpcs:ignore
        if (!$ids) {
            break;
        }
        $deleted += kmpr_game_delete_players($ids);
    }
    return array('ok' => true, 'deleted' => $deleted, 'dry' => false);
}

/* =========================================================================
 * Online rooms
 * ====================================================================== */

function kmpr_panel_rest_online()
{
    global $wpdb;
    $rt      = kmpr_games_rooms_table();
    $now     = time();
    $engines = kmpr_game_engines();
    $rows    = $wpdb->get_results($wpdb->prepare(
        "SELECT id, code, game_id, engine, is_public, status, players, seen0, seen1, created_at, updated_at, (state LIKE %s) AS is_over FROM {$rt} WHERE status IN ('waiting','playing') OR updated_at >= %s ORDER BY (status IN ('waiting','playing')) DESC, id DESC LIMIT 60", // phpcs:ignore
        '%"over":true%',
        gmdate('Y-m-d H:i:s', $now - 30 * MINUTE_IN_SECONDS)
    ), ARRAY_A);
    $rooms  = array();
    $online = 0;
    $lobby  = 0;
    $active = 0;
    foreach ((array) $rows as $r) {
        $players = array();
        foreach ((array) json_decode((string) $r['players'], true) as $p) {
            $seat      = (int) ($p['seat'] ?? 0);
            $is_on     = in_array($r['status'], array('waiting', 'playing'), true) && !$r['is_over'] && $now - (int) $r['seen' . $seat] < 12 && empty($p['left']);
            $online   += $is_on ? 1 : 0;
            $players[] = array('nick' => (string) ($p['nick'] ?? 'مهمان'), 'avatar' => kmpr_game_avatar_clean($p['avatar'] ?? ''), 'online' => $is_on, 'left' => !empty($p['left']), 'pid' => (string) ($p['pid'] ?? ''));
        }
        $beat  = max((int) $r['seen0'], (int) $r['seen1']);
        $live  = ($r['status'] === 'waiting' && $now - (int) $r['seen0'] < 30) || ($r['status'] === 'playing' && $now - $beat < 60);
        $state = $r['status'];
        if ($state === 'playing' && $r['is_over']) {
            $state = 'over';
        }
        if ($r['status'] === 'waiting' && $r['is_public'] && $now - (int) $r['seen0'] < 20) {
            $lobby++;
        }
        if ($r['status'] === 'playing' && !$r['is_over'] && $live) {
            $active++;
        }
        $rooms[] = array(
            'code'    => $r['code'],
            'game'    => get_the_title((int) $r['game_id']),
            'engine'  => $engines[$r['engine']]['label'] ?? $r['engine'],
            'public'  => (bool) $r['is_public'],
            'status'  => $state,
            'live'    => $live,
            'players' => $players,
            'created' => kmpr_panel_ts($r['created_at']),
            'beat'    => $beat,
            'url'     => add_query_arg('room', $r['code'], get_permalink((int) $r['game_id'])),
        );
    }
    return array(
        'rooms' => $rooms,
        'lobby' => array('waiting' => $lobby, 'active' => $active, 'online' => $online, 'today' => kmpr_panel_daily_sum('om', 1), 'week' => kmpr_panel_daily_sum('om', 7)),
        'now'   => $now,
    );
}

/** Close a room (admin moderation): waiting → closed; playing → over without a result (no XP/W/L recorded). */
function kmpr_panel_rest_room_close(WP_REST_Request $req)
{
    $room = kmpr_room_mutate(strtoupper($req['code']), function ($r) {
        if (in_array($r['status'], array('closed', 'expired'), true)) {
            return false;
        }
        $r['state']                 = is_array($r['state']) ? $r['state'] : array();
        $r['state']['over']         = true;
        $r['state']['winner']       = null;
        $r['state']['admin_closed'] = true;
        $r['status']                = 'closed';
        return $r;
    });
    if (is_wp_error($room)) {
        return kmpr_panel_err($room->get_error_message(), $room->get_error_code() === 'no_room' ? 404 : 409);
    }
    return array('ok' => true, 'status' => $room['status']);
}

/* =========================================================================
 * Revenue (arcade monetisation)
 * ====================================================================== */

function kmpr_panel_rest_revenue(WP_REST_Request $req)
{
    if (!function_exists('kmpr_am_sum')) {
        return kmpr_panel_err('ماژول درآمد فعال نیست.', 404);
    }
    $range   = (int) $req['range'];
    $s       = kmpr_am_sum($range);
    $prices  = kmpr_am_prices();
    $verdict = kmpr_am_verdict($s, $prices);
    $n       = function ($k) use ($s) {
        return (int) ($s[$k] ?? 0);
    };
    $variants = array();
    foreach (array('A', 'B') as $v) {
        $surfaces = array();
        foreach (array('hub', 'over') as $sf) {
            $surfaces[$sf] = array('i' => $n("pass.$sf.$v.i"), 'c' => $n("pass.$sf.$v.c"), 's' => $n("pass.$sf.$v.s"));
        }
        $variants[$v] = array('price' => $prices[$v], 'total' => $verdict['rows'][$v], 'surfaces' => $surfaces);
    }
    $sponsors = array();
    foreach (get_posts(array('post_type' => 'game', 'post_status' => 'publish', 'posts_per_page' => 100, 'orderby' => array('menu_order' => 'ASC', 'title' => 'ASC'), 'no_found_rows' => true)) as $g) {
        $sp         = kmpr_am_sponsor($g->ID);
        $sponsors[] = array('id' => $g->ID, 'title' => get_the_title($g), 'sponsor' => $sp ? $sp['name'] : '', 'plays' => kmpr_panel_am_plays($s, $g->ID), 'i' => $n('sp.' . $g->ID . '.i'), 'c' => $n('sp.' . $g->ID . '.c'));
    }
    return array(
        'range'    => $range,
        'verdict'  => array('tone' => $verdict['tone'], 'text' => $verdict['text']),
        'variants' => $variants,
        'totals'   => array(
            'plays'   => kmpr_panel_am_plays($s),
            'support' => array('i' => $n('sup.hub.i') + $n('sup.over.i') + $n('sup.side.i'), 'c' => $n('sup.hub.c') + $n('sup.over.c') + $n('sup.side.c')),
            'adv'     => array('i' => $n('adv.i'), 'c' => $n('adv.c')),
            'spc'     => array('i' => $n('spc.i'), 'c' => $n('spc.c')),
            'signups' => kmpr_panel_count_posts_since(KMPR_AM_CPT, 'private'),
        ),
        'sponsors' => $sponsors,
        'settings' => array(
            'price_a'     => $prices['A'],
            'price_b'     => $prices['B'],
            'period'      => (string) kmpr_am_opt('am_price_period'),
            'support_url' => (string) kmpr_am_opt('am_support_url'),
            'pay_url'     => (string) kmpr_am_opt('am_pay_url'),
            'on'          => array('pass' => kmpr_am_on('pass'), 'support' => kmpr_am_on('support'), 'sponsor' => kmpr_am_on('sponsor'), 'adv' => kmpr_am_on('adv')),
        ),
        'csv'      => add_query_arg('_wpnonce', wp_create_nonce('kmpr_am_export'), kmpr_am_admin_url(array('am_action' => 'export'))),
        'admin'    => kmpr_am_admin_url(),
    );
}

function kmpr_panel_rest_revenue_settings(WP_REST_Request $req)
{
    $p = $req->get_json_params() ?: array();
    foreach (array('price_a', 'price_b') as $k) {
        if (array_key_exists($k, $p)) {
            $v = (int) kmpr_latin_digits((string) $p[$k]);
            if ($v < 0 || $v > 100000000) {
                return kmpr_panel_err('قیمت نامعتبر است.', 400, $k);
            }
            kmpr_panel_set_field('field_kmpr_am_am_' . $k, 'am_' . $k, $v, 'option');
        }
    }
    if (array_key_exists('support_url', $p)) {
        $url = trim((string) $p['support_url']);
        if ($url !== '' && !wp_http_validate_url($url)) {
            return kmpr_panel_err('لینک حمایت معتبر نیست.', 400, 'support_url');
        }
        kmpr_panel_set_field('field_kmpr_am_am_support_url', 'am_support_url', esc_url_raw($url), 'option');
    }
    if (isset($p['on']) && is_array($p['on'])) {
        foreach (array('pass', 'support', 'sponsor', 'adv') as $sf) {
            if (array_key_exists($sf, $p['on'])) {
                kmpr_panel_set_field('field_kmpr_am_on_' . $sf, 'am_on_' . $sf, $p['on'][$sf] ? 1 : 0, 'option');
            }
        }
    }
    return array('ok' => true);
}

function kmpr_panel_contact_links($contact)
{
    $contact = (string) $contact;
    if (preg_match('/^09\d{9}$/', $contact)) {
        return array('kind' => 'phone', 'tel' => 'tel:' . $contact, 'wa' => 'https://wa.me/98' . substr($contact, 1), 'tg' => '');
    }
    if (preg_match('/^@([A-Za-z0-9_]{5,32})$/', $contact, $m)) {
        return array('kind' => 'telegram', 'tel' => '', 'wa' => '', 'tg' => 'https://t.me/' . $m[1]);
    }
    return array('kind' => '', 'tel' => '', 'wa' => '', 'tg' => '');
}

function kmpr_panel_rest_presignups(WP_REST_Request $req)
{
    $per    = 30;
    $offset = (int) $req['offset'];
    $ids    = get_posts(array('post_type' => KMPR_AM_CPT, 'post_status' => 'private', 'posts_per_page' => $per + 1, 'offset' => $offset, 'orderby' => 'date', 'order' => 'DESC', 'fields' => 'ids', 'no_found_rows' => true));
    $items  = array();
    foreach (array_slice($ids, 0, $per) as $id) {
        $m       = function ($k) use ($id) {
            return (string) get_post_meta($id, $k, true);
        };
        $game    = (int) $m('_am_game');
        $items[] = array(
            'id'      => $id,
            'nick'    => $m('_am_nick'),
            'contact' => $m('_am_contact'),
            'links'   => kmpr_panel_contact_links($m('_am_contact')),
            'variant' => $m('_am_variant'),
            'price'   => (int) $m('_am_price'),
            'surface' => $m('_am_surface') === 'over' ? 'پایان بازی' : 'هاب گیم‌سنتر',
            'game'    => $game ? get_the_title($game) : '',
            'tg'      => $m('_am_tg'),
            'date'    => kmpr_panel_when(get_post_time('U', true, $id)),
        );
    }
    return array('items' => $items, 'next' => count($ids) > $per ? $offset + $per : null);
}

function kmpr_panel_rest_presignup_delete(WP_REST_Request $req)
{
    $id = (int) $req['id'];
    if (get_post_type($id) !== KMPR_AM_CPT) {
        return kmpr_panel_err('پیدا نشد.', 404);
    }
    wp_delete_post($id, true);
    delete_transient('kmpr_am_supporters');
    return array('ok' => true);
}

/* =========================================================================
 * Project requests
 * ====================================================================== */

function kmpr_panel_request_item($id)
{
    $o   = kmpr_request_options();
    $get = function ($k) use ($id) {
        return get_post_meta($id, '_kmpr_' . $k, true);
    };
    $phone    = (string) $get('phone');
    $telegram = ltrim((string) $get('telegram'), '@');
    $status   = $get('status') ?: 'new';
    return array(
        'id'        => $id,
        'code'      => (string) $get('code'),
        'name'      => (string) $get('name'),
        'business'  => (string) $get('business'),
        'phone'     => $phone,
        'email'     => (string) $get('email'),
        'telegram'  => $telegram,
        'channel'   => $o['channels'][$get('channel')] ?? '',
        'type'      => $o['types'][$get('type')] ?? '',
        'features'  => array_values(array_map(function ($f) use ($o) {
            return $o['features'][$f] ?? $f;
        }, (array) $get('features'))),
        'budget'    => $o['budgets'][$get('budget')] ?? '',
        'timeline'  => $o['timelines'][$get('timeline')] ?? '',
        'message'   => (string) $get('message'),
        'reference' => (string) $get('reference'),
        'source'    => $get('source') ? urldecode((string) $get('source')) : '',
        'service'   => (string) $get('service'),
        'status'    => $status,
        'note'      => (string) $get('note'),
        'seen'      => (bool) get_post_meta($id, '_kmpr_panel_seen', true),
        'tgSent'    => (bool) $get('tg_sent'),
        'date'      => kmpr_panel_when(get_post_time('U', true, $id)),
        'links'     => array(
            'tel' => $phone ? 'tel:' . $phone : '',
            'wa'  => preg_match('/^09\d{9}$/', $phone) ? 'https://wa.me/98' . substr($phone, 1) : '',
            'tg'  => $telegram && preg_match('/^[A-Za-z0-9_]{4,40}$/', $telegram) ? 'https://t.me/' . $telegram : '',
            'mail'=> $get('email') ? 'mailto:' . $get('email') : '',
        ),
        'edit'      => get_edit_post_link($id, 'raw'),
    );
}

function kmpr_panel_rest_requests(WP_REST_Request $req)
{
    global $wpdb;
    $o      = kmpr_request_options();
    $per    = 20;
    $offset = (int) $req['offset'];
    $status = sanitize_key((string) $req['status']);
    $args   = array('post_type' => 'project_request', 'post_status' => 'publish', 'posts_per_page' => $per + 1, 'offset' => $offset, 'orderby' => 'date', 'order' => 'DESC', 'fields' => 'ids', 'no_found_rows' => true);
    if (isset($o['statuses'][$status])) {
        $args['meta_query'] = array(array('key' => '_kmpr_status', 'value' => $status));
    }
    $ids    = get_posts($args);
    $counts = array('all' => 0);
    foreach ((array) $wpdb->get_results($wpdb->prepare("SELECT m.meta_value s, COUNT(*) n FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = '_kmpr_status' WHERE p.post_type = %s AND p.post_status = 'publish' GROUP BY m.meta_value", 'project_request'), ARRAY_A) as $r) {
        $counts[$r['s']]  = (int) $r['n'];
        $counts['all']   += (int) $r['n'];
    }
    $statuses = array();
    foreach ($o['statuses'] as $k => $s) {
        $statuses[] = array('key' => $k, 'label' => $s['label'], 'color' => $s['color'], 'count' => $counts[$k] ?? 0);
    }
    return array(
        'items'    => array_map('kmpr_panel_request_item', array_slice($ids, 0, $per)),
        'next'     => count($ids) > $per ? $offset + $per : null,
        'statuses' => $statuses,
        'total'    => $counts['all'],
        'tg'       => (bool) (kmpr_tg_token() && kmpr_tg_chat_id()),
    );
}

function kmpr_panel_rest_request_update(WP_REST_Request $req)
{
    $id = (int) $req['id'];
    if (get_post_type($id) !== 'project_request') {
        return kmpr_panel_err('درخواست پیدا نشد.', 404);
    }
    $p = $req->get_json_params() ?: array();
    if (isset($p['status'])) {
        $status = sanitize_key((string) $p['status']);
        if (!isset(kmpr_request_options()['statuses'][$status])) {
            return kmpr_panel_err('وضعیت نامعتبر است.');
        }
        update_post_meta($id, '_kmpr_status', $status);
    }
    if (array_key_exists('note', $p)) {
        update_post_meta($id, '_kmpr_note', sanitize_textarea_field(mb_substr((string) $p['note'], 0, 4000)));
    }
    if (!empty($p['seen'])) {
        update_post_meta($id, '_kmpr_panel_seen', 1);
    }
    $item           = kmpr_panel_request_item($id);
    $item['badges'] = kmpr_panel_badges();
    return $item;
}

/* =========================================================================
 * Settings & maintenance
 * ====================================================================== */

function kmpr_panel_settings_data()
{
    $u     = kmpr_games_storage_bytes();
    $llms  = class_exists('KMPR_LLMs_Manager') ? KMPR_LLMs_Manager::state() : array();
    $tabs  = array();
    foreach ($u['tables'] as $t => $x) {
        $tabs[] = array('name' => preg_replace('/^.*kmpr_game_/', '', $t), 'rows' => $x['rows'], 'bytes' => $x['bytes'], 'human' => size_format($x['bytes'], 1) ?: '0 B');
    }
    $pwa = (array) get_option('kmpr_arcade_pwa_stats', array());
    return array(
        'storage'  => array('mode' => kmpr_games_storage_mode(), 'keep' => (int) kmpr_panel_get_field('gs_keep_top', 'option', 200), 'snap' => (float) kmpr_panel_get_field('gs_snapshot_hours', 'option', 12)),
        'usage'    => array('total' => $u['total'], 'human' => size_format($u['total'], 1) ?: '0 B', 'tables' => $tabs),
        'nudge'    => function_exists('kmpr_nudge_opt') ? (bool) kmpr_nudge_opt('nudge_enabled') : false,
        'pwa'      => array_map('intval', array_merge(array('prompt' => 0, 'accepted' => 0, 'dismissed' => 0, 'installed' => 0, 'ios_sheet' => 0), $pwa)),
        'telegram' => (bool) (kmpr_tg_token() && kmpr_tg_chat_id()),
        'llms'     => array('generated' => isset($llms['generated']) ? kmpr_panel_when($llms['generated']) : null, 'error' => (string) ($llms['error'] ?? ''), 'bytes' => (int) ($llms['txt']['bytes'] ?? 0), 'fullBytes' => (int) ($llms['full']['bytes'] ?? 0)),
        'links'    => array(
            'admin'      => admin_url(),
            'games'      => admin_url('edit.php?post_type=game'),
            'gamesOpts'  => admin_url('edit.php?post_type=game&page=kmpr-games-settings'),
            'players'    => admin_url('edit.php?post_type=game&page=kmpr-game-players'),
            'revenue'    => function_exists('kmpr_am_admin_url') ? kmpr_am_admin_url() : '',
            'requests'   => admin_url('edit.php?post_type=project_request'),
            'telegram'   => admin_url('edit.php?post_type=project_request&page=kmpr-requests-settings'),
            'llms'       => admin_url('admin.php?page=kmpr-ai-llms'),
            'comments'   => admin_url('edit-comments.php'),
        ),
    );
}

function kmpr_panel_rest_settings()
{
    return kmpr_panel_settings_data();
}

function kmpr_panel_rest_settings_update(WP_REST_Request $req)
{
    $p = $req->get_json_params() ?: array();
    if (isset($p['mode'])) {
        if (!in_array($p['mode'], array('light', 'local'), true)) {
            return kmpr_panel_err('حالت نامعتبر است.');
        }
        kmpr_panel_set_field('field_kmpr_game_opt_gs_storage', 'gs_storage', $p['mode'], 'option');
        delete_transient('kmpr_games_stats');
    }
    if (isset($p['keep'])) {
        kmpr_panel_set_field('field_kmpr_game_opt_gs_keep', 'gs_keep_top', max(10, min(5000, (int) $p['keep'])), 'option');
    }
    if (isset($p['snap'])) {
        kmpr_panel_set_field('field_kmpr_game_opt_gs_snap', 'gs_snapshot_hours', max(0, min(720, (int) $p['snap'])), 'option');
    }
    if (isset($p['nudge'])) {
        kmpr_panel_set_field('field_kmpr_nudge_on', 'nudge_enabled', $p['nudge'] ? 1 : 0, 'option');
    }
    return kmpr_panel_settings_data();
}

/** Run the games retention cleanup now; reports deleted rows and an estimate of freed bytes. */
function kmpr_panel_rest_cleanup()
{
    global $wpdb;
    $before = kmpr_games_storage_bytes();
    $res    = kmpr_games_cleanup_run();
    $map    = array('scores' => kmpr_games_table(), 'players' => kmpr_games_players_table(), 'rooms' => kmpr_games_rooms_table());
    $freed  = 0;
    foreach ($map as $k => $t) {
        $x = $before['tables'][$t] ?? null;
        if ($x && $x['rows'] > 0 && !empty($res[$k])) {
            $freed += (int) round($x['bytes'] / max(1, $x['rows']) * min((int) $res[$k], $x['rows']));
        }
    }
    foreach ($map as $t) {
        $wpdb->query("ANALYZE TABLE {$t}"); // phpcs:ignore -- refresh information_schema estimates
    }
    $data            = kmpr_panel_settings_data();
    $data['cleanup'] = array('rows' => $res, 'freed' => $freed, 'freedHuman' => size_format($freed, 1) ?: '0 B');
    return $data;
}

function kmpr_panel_rest_pwa_reset()
{
    delete_option('kmpr_arcade_pwa_stats');
    return kmpr_panel_settings_data();
}

function kmpr_panel_rest_tg_test()
{
    if (!kmpr_tg_token() || !kmpr_tg_chat_id()) {
        return kmpr_panel_err('توکن یا شناسه چت تلگرام تنظیم نشده است.', 400, 'tg_config');
    }
    $ok = kmpr_tg_send("✅ <b>اتصال برقرار است</b>\nپیام آزمایشی از پنل مدیریت " . esc_html(wp_parse_url(home_url(), PHP_URL_HOST)) . '.');
    return $ok ? array('ok' => true) : kmpr_panel_err('ارسال پیام ناموفق بود (لاگ خطای PHP را ببینید).', 502, 'tg_failed');
}

function kmpr_panel_rest_llms()
{
    if (empty($GLOBALS['kmpr_llms']) || !method_exists($GLOBALS['kmpr_llms'], 'rebuild')) {
        return kmpr_panel_err('ماژول LLMs در دسترس نیست.', 404);
    }
    $GLOBALS['kmpr_llms']->rebuild();
    return kmpr_panel_settings_data();
}
