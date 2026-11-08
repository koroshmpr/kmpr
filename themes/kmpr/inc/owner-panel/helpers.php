<?php
/**
 * Owner panel — shared helpers.
 *
 * @package KMPR
 */

if (!defined('ABSPATH')) {
    exit;
}

/** Site-relative base path of the panel, e.g. "/panel/". */
function kmpr_panel_base()
{
    return rtrim((string) wp_parse_url(home_url('/'), PHP_URL_PATH), '/') . '/' . KMPR_PANEL_SLUG . '/';
}

function kmpr_panel_url($sub = '')
{
    return home_url('/' . KMPR_PANEL_SLUG . '/' . ltrim((string) $sub, '/'));
}

function kmpr_panel_can()
{
    return current_user_can(KMPR_PANEL_CAP);
}

/** Site UTC offset in seconds (gmt_offset; the site has no named timezone). */
function kmpr_panel_offset()
{
    return (int) round((float) get_option('gmt_offset') * HOUR_IN_SECONDS);
}

/** Jalali date/time in site time (shamsi_date() formats with PHP's UTC date()). */
function kmpr_panel_date($ts, $format = 'j F Y · H:i')
{
    $ts = (int) $ts;
    if ($ts <= 0) {
        return '';
    }
    if (!function_exists('shamsi_date')) {
        return wp_date('Y-m-d H:i', $ts);
    }
    return shamsi_date($format, $ts + kmpr_panel_offset());
}

/** MySQL GMT datetime → unix ts (0 for the "never" sentinel). */
function kmpr_panel_ts($mysql_gmt)
{
    if (!$mysql_gmt || strpos((string) $mysql_gmt, '2000-01-01') === 0 || strpos((string) $mysql_gmt, '0000') === 0) {
        return 0;
    }
    return (int) strtotime($mysql_gmt . ' UTC');
}

/** Date + ts pair for the client (relative time is computed in the browser). */
function kmpr_panel_when($ts)
{
    $ts = (int) $ts;
    return array('ts' => $ts, 'fa' => kmpr_panel_date($ts));
}

/* =========================================================================
 * Tiny daily counters (one non-autoloaded option, ≤ 120 day buckets)
 * Used for things no other module keeps a dated count of (online matches).
 * ====================================================================== */
const KMPR_PANEL_DAILY = 'kmpr_panel_daily';

function kmpr_panel_bump($key, $n = 1)
{
    $all = get_option(KMPR_PANEL_DAILY, array());
    $all = is_array($all) ? $all : array();
    $day = wp_date('Ymd');
    $all[$day][$key] = (int) ($all[$day][$key] ?? 0) + (int) $n;
    if (count($all) > 120) {
        ksort($all);
        $all = array_slice($all, -120, null, true);
    }
    update_option(KMPR_PANEL_DAILY, $all, false);
}

/** Sum of a daily counter over the last $days days (today included). */
function kmpr_panel_daily_sum($key, $days)
{
    $all  = get_option(KMPR_PANEL_DAILY, array());
    $from = (int) wp_date('Ymd', time() - ($days - 1) * DAY_IN_SECONDS);
    $sum  = 0;
    foreach ((array) $all as $day => $b) {
        if ((int) $day >= $from) {
            $sum += (int) ($b[$key] ?? 0);
        }
    }
    return $sum;
}

add_action('kmpr_game_room_finished', function () {
    kmpr_panel_bump('om');
});

/** Short public label for a player id (never expose the full id in lists). */
function kmpr_panel_short_id($pid)
{
    return substr((string) $pid, 0, 8);
}
