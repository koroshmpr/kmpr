<?php
/**
 * Games — admin «بازیکنان» screen (WP_List_Table + per-player detail) and dashboard widget.
 * Reads only the lean counters (players row + per-game counter rows); no activity log exists.
 *
 * @package KMPR
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_menu', function () {
    add_submenu_page('edit.php?post_type=game', 'بازیکنان', 'بازیکنان', 'manage_options', 'kmpr-game-players', 'kmpr_game_players_screen');
});

/**
 * Relative + Jalali time.
 */
function kmpr_game_admin_when($mysql_gmt)
{
    if (!$mysql_gmt || strpos($mysql_gmt, '2000-01-01') === 0) {
        return '—';
    }
    $ts  = strtotime($mysql_gmt . ' UTC');
    $abs = function_exists('shamsi_date') ? shamsi_date('Y/m/d H:i', $ts) : wp_date('Y-m-d H:i', $ts);
    return esc_html(human_time_diff($ts, time()) . ' پیش') . '<br><small style="color:#646970">' . esc_html($abs) . '</small>';
}

function kmpr_game_admin_avatar($key, $nick)
{
    $a = kmpr_game_avatar_clean($key);
    $l = mb_substr(trim((string) $nick) ?: '؟', 0, 1);
    return '<span style="display:inline-grid;place-items:center;width:32px;height:32px;border-radius:9px;background:hsl(' . (int) $a['hue'] . ' 70% 45%);color:#fff;font-weight:700">' . esc_html($l) . '</span>';
}

if (is_admin() && !class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

if (class_exists('WP_List_Table')) {
    class KMPR_Game_Players_Table extends WP_List_Table
    {
        public function __construct()
        {
            parent::__construct(array('singular' => 'player', 'plural' => 'players', 'ajax' => false));
        }

        public function get_columns()
        {
            return array(
                'cb'         => '<input type="checkbox">',
                'nickname'   => 'بازیکن',
                'level'      => 'سطح / XP',
                'record'     => 'برد / باخت / مساوی',
                'plays'      => 'دفعات بازی',
                'favourite'  => 'بازی محبوب',
                'last_seen'  => 'آخرین فعالیت',
                'first_seen' => 'اولین بازدید',
                'wp_user'    => 'کاربر وردپرس',
            );
        }

        protected function get_sortable_columns()
        {
            return array('level' => array('xp', true), 'plays' => array('plays_total', true), 'last_seen' => array('last_seen_at', true));
        }

        protected function get_bulk_actions()
        {
            return array('delete' => 'حذف بازیکن و رکوردهایش');
        }

        protected function column_cb($item)
        {
            return '<input type="checkbox" name="player[]" value="' . esc_attr($item['player_id']) . '">';
        }

        protected function column_nickname($item)
        {
            $url     = add_query_arg(array('post_type' => 'game', 'page' => 'kmpr-game-players', 'player' => $item['player_id']), admin_url('edit.php'));
            $del     = wp_nonce_url(add_query_arg(array('action' => 'kmpr_game_player_delete', 'player' => $item['player_id']), admin_url('admin-post.php')), 'kmpr_game_player_delete_' . $item['player_id']);
            $actions = array(
                'view'   => '<a href="' . esc_url($url) . '">جزئیات</a>',
                'delete' => '<a href="' . esc_url($del) . '" style="color:#b32d2e" onclick="return confirm(\'بازیکن و رکوردهایش حذف شود؟\')">حذف</a>',
            );
            $name = $item['nickname'] !== '' ? $item['nickname'] : '(بدون نام)';
            return '<span style="display:flex;gap:10px;align-items:center">' . kmpr_game_admin_avatar($item['avatar'], $name) . '<span><strong><a href="' . esc_url($url) . '">' . esc_html($name) . '</a></strong><br><code style="font-size:11px">' . esc_html(substr($item['player_id'], 0, 8)) . '</code></span></span>' . $this->row_actions($actions);
        }

        protected function column_default($item, $col)
        {
            switch ($col) {
                case 'level':
                    $l = kmpr_game_level($item['xp']);
                    return esc_html('سطح ' . number_format_i18n($l['level']) . ' · ' . number_format_i18n($item['xp']) . ' XP');
                case 'record':
                    return esc_html(number_format_i18n($item['wins']) . ' / ' . number_format_i18n($item['losses']) . ' / ' . number_format_i18n($item['draws']));
                case 'plays':
                    return esc_html(number_format_i18n($item['plays_total']));
                case 'favourite':
                    return $item['fav_game'] ? esc_html(get_the_title($item['fav_game']) . ' (' . number_format_i18n($item['fav_plays']) . ')') : '—';
                case 'last_seen':
                    return kmpr_game_admin_when($item['last_seen_at']);
                case 'first_seen':
                    return kmpr_game_admin_when($item['first_seen']);
                case 'wp_user':
                    if (!$item['user_id']) {
                        return '—';
                    }
                    $u = get_userdata($item['user_id']);
                    return $u ? '<a href="' . esc_url(get_edit_user_link($u->ID)) . '">' . esc_html($u->display_name) . '</a>' : '—';
            }
            return '';
        }

        public function prepare_items()
        {
            global $wpdb;
            $pt       = kmpr_games_players_table();
            $st       = kmpr_games_table();
            $per_page = 30;
            $paged    = $this->get_pagenum();
            $orderby  = in_array($_GET['orderby'] ?? '', array('xp', 'plays_total', 'last_seen_at'), true) ? $_GET['orderby'] : 'last_seen_at'; // phpcs:ignore
            $order    = strtolower($_GET['order'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC'; // phpcs:ignore
            $search   = isset($_REQUEST['s']) ? sanitize_text_field(wp_unslash($_REQUEST['s'])) : ''; // phpcs:ignore
            $where    = $search !== '' ? $wpdb->prepare('WHERE nickname LIKE %s', '%' . $wpdb->esc_like($search) . '%') : '';
            $total    = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$pt} {$where}"); // phpcs:ignore
            $items    = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$pt} {$where} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d", $per_page, ($paged - 1) * $per_page), ARRAY_A); // phpcs:ignore
            // Favourite game per player on this page (one query).
            $fav = array();
            if ($items) {
                $ids  = wp_list_pluck($items, 'player_id');
                $in   = implode(',', array_fill(0, count($ids), '%s'));
                $rows = $wpdb->get_results($wpdb->prepare("SELECT player_id, game_id, plays FROM {$st} WHERE player_id IN ({$in}) ORDER BY plays DESC", $ids), ARRAY_A); // phpcs:ignore
                foreach ((array) $rows as $r) {
                    if (!isset($fav[$r['player_id']])) {
                        $fav[$r['player_id']] = $r;
                    }
                }
            }
            foreach ($items as &$it) {
                $it['fav_game']  = isset($fav[$it['player_id']]) ? (int) $fav[$it['player_id']]['game_id'] : 0;
                $it['fav_plays'] = isset($fav[$it['player_id']]) ? (int) $fav[$it['player_id']]['plays'] : 0;
            }
            unset($it);
            $this->items           = $items;
            $this->_column_headers = array($this->get_columns(), array(), $this->get_sortable_columns());
            $this->set_pagination_args(array('total_items' => $total, 'per_page' => $per_page, 'total_pages' => (int) ceil($total / $per_page)));
        }

        public function no_items()
        {
            echo 'هنوز بازیکنی ثبت نشده است.';
        }
    }
}

/**
 * Delete players (+ their score/counter rows).
 */
function kmpr_game_delete_players($ids)
{
    global $wpdb;
    $ids = array_values(array_filter(array_map('strtolower', (array) $ids), 'kmpr_game_valid_player'));
    if (!$ids) {
        return 0;
    }
    $in = implode(',', array_fill(0, count($ids), '%s'));
    $wpdb->query($wpdb->prepare('DELETE FROM ' . kmpr_games_table() . " WHERE player_id IN ({$in})", $ids)); // phpcs:ignore
    $n = (int) $wpdb->query($wpdb->prepare('DELETE FROM ' . kmpr_games_players_table() . " WHERE player_id IN ({$in})", $ids)); // phpcs:ignore
    delete_transient('kmpr_games_stats');
    delete_transient('kmpr_games_top_players');
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient%kmpr_glb_%'"); // phpcs:ignore
    return $n;
}

add_action('admin_post_kmpr_game_player_delete', function () {
    $pid = isset($_GET['player']) ? sanitize_text_field(wp_unslash($_GET['player'])) : '';
    if (!current_user_can('manage_options')) {
        wp_die('دسترسی ندارید.');
    }
    check_admin_referer('kmpr_game_player_delete_' . $pid);
    kmpr_game_delete_players(array($pid));
    wp_safe_redirect(add_query_arg(array('post_type' => 'game', 'page' => 'kmpr-game-players', 'deleted' => 1), admin_url('edit.php')));
    exit;
});

function kmpr_game_players_screen()
{
    if (!current_user_can('manage_options')) {
        return;
    }
    global $wpdb;
    $base = admin_url('edit.php?post_type=game&page=kmpr-game-players');

    // Bulk delete.
    if (isset($_POST['player'], $_POST['_wpnonce']) && (($_POST['action'] ?? '') === 'delete' || ($_POST['action2'] ?? '') === 'delete') && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'bulk-players')) { // phpcs:ignore
        $n = kmpr_game_delete_players(array_map('sanitize_text_field', wp_unslash((array) $_POST['player'])));
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html(number_format_i18n($n)) . ' بازیکن حذف شد.</p></div>';
    }

    $pid = isset($_GET['player']) ? strtolower(sanitize_text_field(wp_unslash($_GET['player']))) : ''; // phpcs:ignore
    if ($pid && kmpr_game_valid_player($pid)) {
        $p = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . kmpr_games_players_table() . ' WHERE player_id = %s', $pid), ARRAY_A); // phpcs:ignore
        $rows = $wpdb->get_results($wpdb->prepare('SELECT game_id, score, plays, created_at, last_played_at FROM ' . kmpr_games_table() . ' WHERE player_id = %s ORDER BY plays DESC', $pid), ARRAY_A); // phpcs:ignore
        echo '<div class="wrap"><h1>' . esc_html($p ? ($p['nickname'] ?: '(بدون نام)') : 'بازیکن') . ' <a class="page-title-action" href="' . esc_url($base) . '">همه بازیکنان</a></h1>';
        if (!$p) {
            echo '<p>بازیکن پیدا نشد.</p></div>';
            return;
        }
        $l = kmpr_game_level($p['xp']);
        echo '<table class="widefat striped" style="max-width:720px;margin:1em 0"><tbody>';
        $info = array(
            'شناسه'               => '<code>' . esc_html($p['player_id']) . '</code>',
            'سطح / XP'            => esc_html('سطح ' . number_format_i18n($l['level']) . ' · ' . number_format_i18n($p['xp']) . ' XP'),
            'بازی آنلاین (ب/ب/م)' => esc_html(number_format_i18n($p['wins']) . ' / ' . number_format_i18n($p['losses']) . ' / ' . number_format_i18n($p['draws'])),
            'کل دفعات بازی'       => esc_html(number_format_i18n($p['plays_total'])),
            'دستاوردها'           => esc_html(number_format_i18n($p['ach_count'])),
            'آخرین فعالیت'        => kmpr_game_admin_when($p['last_seen_at']),
            'اولین بازدید'        => kmpr_game_admin_when($p['first_seen']),
        );
        foreach ($info as $k => $v) {
            echo '<tr><th style="width:200px">' . esc_html($k) . '</th><td>' . $v . '</td></tr>'; // phpcs:ignore -- values escaped above
        }
        echo '</tbody></table><h2>بازی‌ها</h2><table class="widefat striped" style="max-width:900px"><thead><tr><th>بازی</th><th>دفعات بازی</th><th>بهترین امتیاز</th><th>تاریخ رکورد</th><th>آخرین بازی</th></tr></thead><tbody>';
        if (!$rows) {
            echo '<tr><td colspan="5">هنوز بازی‌ای ثبت نشده است.</td></tr>';
        }
        foreach ((array) $rows as $r) {
            echo '<tr><td>' . esc_html(get_the_title($r['game_id']) ?: '#' . $r['game_id']) . '</td><td>' . esc_html(number_format_i18n($r['plays'])) . '</td><td>' . ((int) $r['score'] > 0 ? esc_html(number_format_i18n($r['score'])) : '—') . '</td><td>' . ((int) $r['score'] > 0 ? kmpr_game_admin_when($r['created_at']) : '—') . '</td><td>' . kmpr_game_admin_when($r['last_played_at']) . '</td></tr>'; // phpcs:ignore
        }
        echo '</tbody></table><p style="color:#646970">تاریخچه کامل، دستاوردها و تنظیمات فقط در مرورگر خود بازیکن نگه‌داری می‌شوند (برای سبک ماندن دیتابیس).</p></div>';
        return;
    }

    $table = new KMPR_Game_Players_Table();
    $table->prepare_items();
    echo '<div class="wrap"><h1 class="wp-heading-inline">بازیکنان</h1><hr class="wp-header-end">';
    if (!empty($_GET['deleted'])) { // phpcs:ignore
        echo '<div class="notice notice-success is-dismissible"><p>بازیکن حذف شد.</p></div>';
    }
    kmpr_games_storage_box();
    echo '<form method="get"><input type="hidden" name="post_type" value="game"><input type="hidden" name="page" value="kmpr-game-players">';
    $table->search_box('جستجوی نام مستعار', 'kmpr-player');
    echo '</form><form method="post">';
    $table->display();
    echo '</form></div>';
}

/* ---------- dashboard widget ---------- */
add_action('wp_dashboard_setup', function () {
    if (!current_user_can('manage_options')) {
        return;
    }
    wp_add_dashboard_widget('kmpr_games_players_widget', 'بازیکنان امروز / این هفته', function () {
        global $wpdb;
        $pt    = kmpr_games_players_table();
        $st    = kmpr_games_table();
        $day   = gmdate('Y-m-d H:i:s', time() - DAY_IN_SECONDS);
        $week  = gmdate('Y-m-d H:i:s', time() - WEEK_IN_SECONDS);
        $today = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$pt} WHERE last_seen_at >= %s", $day)); // phpcs:ignore
        $wk    = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$pt} WHERE last_seen_at >= %s", $week)); // phpcs:ignore
        $new   = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$pt} WHERE first_seen >= %s", $week)); // phpcs:ignore
        // Approximation from counters: plays of rows active this week.
        $top = $wpdb->get_results($wpdb->prepare("SELECT game_id, SUM(plays) p, COUNT(*) n FROM {$st} WHERE last_played_at >= %s GROUP BY game_id ORDER BY n DESC, p DESC LIMIT 5", $week), ARRAY_A); // phpcs:ignore
        echo '<p style="display:flex;gap:18px;font-size:14px"><span>امروز: <strong>' . esc_html(number_format_i18n($today)) . '</strong></span><span>این هفته: <strong>' . esc_html(number_format_i18n($wk)) . '</strong></span><span>بازیکن جدید هفته: <strong>' . esc_html(number_format_i18n($new)) . '</strong></span></p>';
        echo '<h4 style="margin:12px 0 6px">پرطرفدارترین بازی‌های هفته</h4><ol style="margin:0 1.5em">';
        if (!$top) {
            echo '<li>هنوز بازی‌ای ثبت نشده است.</li>';
        }
        foreach ((array) $top as $t) {
            echo '<li>' . esc_html(get_the_title($t['game_id'])) . ' — ' . esc_html(number_format_i18n($t['n'])) . ' بازیکن فعال</li>';
        }
        echo '</ol><p><a href="' . esc_url(admin_url('edit.php?post_type=game&page=kmpr-game-players')) . '">همه بازیکنان</a></p>';
    });
});
