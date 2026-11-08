<?php
/**
 * Owner panel — entry points in WordPress: admin-bar link, dashboard widget, speculation-rules exclusion.
 *
 * @package KMPR
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_bar_menu', function ($bar) {
    if (!kmpr_panel_can()) {
        return;
    }
    $bar->add_node(array(
        'id'    => 'kmpr-panel',
        'title' => '<span class="ab-icon dashicons dashicons-smartphone" aria-hidden="true" style="top:2px"></span><span class="ab-label">پنل مدیریت</span>',
        'href'  => kmpr_panel_url(),
        'meta'  => array('title' => 'پنل مدیریت موبایل (گیم‌سنتر، درخواست‌ها، تنظیمات)'),
    ));
}, 70);

add_action('wp_dashboard_setup', function () {
    if (!kmpr_panel_can()) {
        return;
    }
    wp_add_dashboard_widget('kmpr_panel_widget', 'پنل مدیریت (موبایل)', function () {
        $b = kmpr_panel_badges();
        echo '<p style="margin-top:0">مدیریت سریع گیم‌سنتر، بازیکنان، اتاق‌های آنلاین، درآمد و درخواست‌های پروژه — روی موبایل قابل نصب است.</p>';
        echo '<p style="display:flex;flex-wrap:wrap;gap:14px;margin:0 0 12px">'
            . '<span>درخواست جدید: <strong>' . esc_html(number_format_i18n($b['requests'])) . '</strong></span>'
            . '<span>دیدگاه در انتظار: <strong>' . esc_html(number_format_i18n($b['comments'])) . '</strong></span>'
            . '<span>اتاق آنلاین فعال: <strong>' . esc_html(number_format_i18n($b['live'])) . '</strong></span></p>';
        echo '<p style="margin:0"><a class="button button-primary" href="' . esc_url(kmpr_panel_url()) . '">باز کردن پنل مدیریت</a></p>';
    });
}, 1);

// Never prefetch/prerender the panel from public pages (logged-in admins browsing the site).
add_filter('wp_speculation_rules_href_exclude_paths', function ($paths) {
    $paths[] = '/' . KMPR_PANEL_SLUG . '/*';
    return $paths;
});
