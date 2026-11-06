<?php
/**
 * Native Lightweight Persian Shamsi / Jalali Date Converter
 * Zero-Plugin, Super-fast, SEO & Core Web Vitals friendly
 *
 * @package KMPR
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Convert Gregorian date to Jalali
 */
function gregorian_to_jalali($gy, $gm, $gd)
{
    $g_days_in_month = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
    $j_days_in_month = array(31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29);

    if ($gy > 1600) {
        $jy = 979;
        $gy -= 1600;
    } else {
        $jy = 0;
        $gy -= 621;
    }

    $gy2 = ($gm > 2) ? ($gy + 1) : $gy;
    $days = (365 * $gy) + (int)(($gy2 + 3) / 4) - (int)(($gy2 + 99) / 100) + (int)(($gy2 + 399) / 400) - 80 + $gd;

    for ($i = 0; $i < ($gm - 1); ++$i) {
        $days += $g_days_in_month[$i];
    }

    $jy += 33 * (int)($days / 12053);
    $days %= 12053;
    $jy += 4 * (int)($days / 1461);
    $days %= 1461;

    if ($days > 365) {
        $jy += (int)(($days - 1) / 365);
        $days = ($days - 1) % 365;
    }

    for ($i = 0; ($i < 11) && ($days >= $j_days_in_month[$i]); ++$i) {
        $days -= $j_days_in_month[$i];
    }

    $jm = $i + 1;
    $jd = $days + 1;

    return array($jy, $jm, $jd);
}

/**
 * Convert ASCII numbers to Persian numerals
 */
function kmpr_to_persian_num($string)
{
    $persian = array('۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹');
    $english = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
    return str_replace($english, $persian, (string)$string);
}

/**
 * Format timestamp as Persian Shamsi date
 */
function shamsi_date($format = 'd F Y', $timestamp = null, $persian_digits = true)
{
    if (!$timestamp) {
        $timestamp = time();
    }

    $g_date = date("Y-m-d", (int)$timestamp);
    list($gy, $gm, $gd) = explode('-', $g_date);
    list($jy, $jm, $jd) = gregorian_to_jalali((int)$gy, (int)$gm, (int)$gd);

    $persian_months = array(
        'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور',
        'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'
    );

    $formatted = str_replace('Y', $jy, $format);
    $formatted = str_replace('y', substr((string)$jy, 2), $formatted);
    $formatted = str_replace('m', str_pad($jm, 2, '0', STR_PAD_LEFT), $formatted);
    $formatted = str_replace('n', $jm, $formatted);
    $formatted = str_replace('d', str_pad($jd, 2, '0', STR_PAD_LEFT), $formatted);
    $formatted = str_replace('j', $jd, $formatted);

    if (strpos($formatted, 'F') !== false) {
        $formatted = str_replace('F', $persian_months[$jm - 1], $formatted);
    }

    $formatted = str_replace('H', date('H', (int)$timestamp), $formatted);
    $formatted = str_replace('i', date('i', (int)$timestamp), $formatted);
    $formatted = str_replace('s', date('s', (int)$timestamp), $formatted);

    if ($persian_digits) {
        return kmpr_to_persian_num($formatted);
    }

    return $formatted;
}

/**
 * Automatically hook into standard WordPress date filters
 */
function kmpr_filter_wp_date($the_date, $format, $post)
{
    $timestamp = get_post_time('U', false, $post);
    if (!$timestamp) {
        return $the_date;
    }
    return shamsi_date($format ?: 'd F Y', $timestamp, true);
}
add_filter('get_the_date', 'kmpr_filter_wp_date', 10, 3);
add_filter('get_the_modified_date', 'kmpr_filter_wp_date', 10, 3);
add_filter('get_comment_date', 'kmpr_filter_wp_date', 10, 3);
