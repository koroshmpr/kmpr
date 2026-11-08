<?php
/**
 * Services: archive options page (editable copy for /services/ and shared single-service labels)
 * + small render helpers used by archive-services.php, single-services.php and template-parts/services/*.
 *
 * @package KMPR
 */

if (!defined('ABSPATH')) {
    exit;
}

/* -------------------------------------------------------------------------
 * Options sub-page «صفحه خدمات»
 * ---------------------------------------------------------------------- */

add_action('acf/init', function () {
    if (function_exists('acf_add_options_sub_page')) {
        acf_add_options_sub_page(array(
            'page_title'  => 'صفحه خدمات',
            'menu_title'  => 'صفحه خدمات',
            'menu_slug'   => 'acf-options-services-page',
            'parent_slug' => 'acf-options-theme-settings',
        ));
    }
}, 20);

add_action('acf/include_fields', 'kmpr_register_services_extra_fields', 20);

function kmpr_register_services_extra_fields()
{
    if (!function_exists('acf_add_local_field_group') || !function_exists('kmpr_acf')) {
        return;
    }

    acf_add_local_field_group(array(
        'key'    => 'group_kmpr_services_page',
        'title'  => 'صفحه آرشیو خدمات و برچسب‌های صفحه هر خدمت',
        'fields' => array(
            kmpr_acf_tab('field_kmpr_srvp_tab_hero', 'هدر آرشیو'),
            kmpr_acf('field_kmpr_srvp_badge', 'srvp_badge', 'نشان بالای تیتر', 'text', array('default_value' => 'پذیرش پروژه جدید فعال است')),
            kmpr_acf('field_kmpr_srvp_title', 'srvp_title', 'تیتر اصلی (H1)', 'text', array('default_value' => 'خدماتی که هر کدام را خودم از اول تا آخر انجام می‌دهم')),
            kmpr_acf('field_kmpr_srvp_text', 'srvp_text', 'توضیح', 'textarea', array('rows' => 3, 'default_value' => 'طراحی سایت اختصاصی، سئوی فنی و پشتیبانی؛ بدون واسطه و بدون قالب‌های سنگین. هر پروژه را با دامنه کار مکتوب، زمان‌بندی روشن و گزارش هفتگی جلو می‌برم تا بدانید دقیقاً چه چیزی، کی و با چه کیفیتی تحویل می‌گیرید.')),
            kmpr_acf('field_kmpr_srvp_btn1', 'srvp_btn_primary', 'متن دکمه اصلی', 'text', array('default_value' => 'ثبت درخواست پروژه')),
            kmpr_acf('field_kmpr_srvp_btn2', 'srvp_btn_secondary', 'متن دکمه دوم', 'text', array('default_value' => 'مقایسه خدمات')),
            kmpr_acf('field_kmpr_srvp_trust', 'srvp_trust', 'نکات اعتمادساز زیر دکمه‌ها (هر خط یک مورد)', 'textarea', array('rows' => 3, 'default_value' => "همکاری مستقیم با خود من\nقرارداد و دامنه کار مکتوب\nمالکیت کامل کد و محتوا")),

            kmpr_acf_tab('field_kmpr_srvp_tab_list', 'لیست خدمات'),
            kmpr_acf('field_kmpr_srvp_list_kicker', 'srvp_list_kicker', 'برچسب', 'text', array('default_value' => 'خدمات')),
            kmpr_acf('field_kmpr_srvp_list_title', 'srvp_list_title', 'تیتر', 'text', array('default_value' => 'کدام خدمت به هدف شما نزدیک‌تر است؟')),
            kmpr_acf('field_kmpr_srvp_list_text', 'srvp_list_text', 'توضیح', 'textarea', array('rows' => 2, 'default_value' => 'برای هر خدمت، خروجی‌ها، زمان تقریبی و مراحل کار را شفاف نوشته‌ام. اگر مطمئن نیستید کدام مناسب شماست، فرم انتهای صفحه را پر کنید تا خودم راهنمایی‌تان کنم.')),

            kmpr_acf_tab('field_kmpr_srvp_tab_process', 'روند همکاری'),
            kmpr_acf('field_kmpr_srvp_proc_kicker', 'srvp_process_kicker', 'برچسب', 'text', array('default_value' => 'روند همکاری')),
            kmpr_acf('field_kmpr_srvp_proc_title', 'srvp_process_title', 'تیتر', 'text', array('default_value' => 'از اولین پیام تا تحویل، همه‌چیز شفاف است')),
            kmpr_acf('field_kmpr_srvp_proc_text', 'srvp_process_text', 'توضیح', 'textarea', array('rows' => 2, 'default_value' => 'هر مرحله خروجی مشخص دارد و قبل از رفتن به مرحله بعد، تأیید شما را می‌گیرم. این‌طوری نه هزینه‌ای غافلگیرتان می‌کند و نه زمانی هدر می‌رود.')),
            kmpr_acf('field_kmpr_srvp_proc_steps', 'srvp_process_steps', 'مراحل', 'repeater', array('layout' => 'table', 'button_label' => 'افزودن مرحله', 'sub_fields' => array(
                kmpr_acf('field_kmpr_srvp_proc_t', 't', 'عنوان'),
                kmpr_acf('field_kmpr_srvp_proc_d', 'd', 'توضیح', 'textarea', array('rows' => 2)),
                kmpr_acf('field_kmpr_srvp_proc_m', 'm', 'زمان / برچسب'),
            ))),

            kmpr_acf_tab('field_kmpr_srvp_tab_proof', 'نمونه‌کارها'),
            kmpr_acf('field_kmpr_srvp_proof_kicker', 'srvp_proof_kicker', 'برچسب', 'text', array('default_value' => 'نمونه‌کارها')),
            kmpr_acf('field_kmpr_srvp_proof_title', 'srvp_proof_title', 'تیتر', 'text', array('default_value' => 'پروژه‌هایی که با همین خدمات ساخته‌ام')),

            kmpr_acf_tab('field_kmpr_srvp_tab_faq', 'سوالات متداول'),
            kmpr_acf('field_kmpr_srvp_faq_kicker', 'srvp_faq_kicker', 'برچسب', 'text', array('default_value' => 'سوالات متداول')),
            kmpr_acf('field_kmpr_srvp_faq_title', 'srvp_faq_title', 'تیتر', 'text', array('default_value' => 'قبل از شروع همکاری بپرسید')),
            kmpr_acf('field_kmpr_srvp_faq', 'srvp_faq', 'سوالات آرشیو', 'repeater', array(
                'layout' => 'block', 'button_label' => 'افزودن سوال',
                'instructions' => 'خالی بماند = دو سوال اول هر خدمت به‌صورت خودکار نمایش داده می‌شود.',
                'sub_fields' => array(
                    kmpr_acf('field_kmpr_srvp_faq_q', 'question', 'سوال'),
                    kmpr_acf('field_kmpr_srvp_faq_a', 'answer', 'پاسخ', 'textarea', array('rows' => 3)),
                ),
            )),

            kmpr_acf_tab('field_kmpr_srvp_tab_single', 'برچسب‌های صفحه هر خدمت'),
            kmpr_acf('field_kmpr_srvs_cta', 'srvs_cta', 'دکمه اصلی هدر', 'text', array('default_value' => 'درخواست همین خدمت')),
            kmpr_acf('field_kmpr_srvs_price_fallback', 'srvs_price_fallback', 'متن قیمت وقتی «شروع قیمت» خالی است', 'text', array('default_value' => 'متناسب با محدوده پروژه')),
            kmpr_acf('field_kmpr_srvs_deliver_title', 'srvs_deliver_title', 'تیتر بخش خروجی‌ها', 'text', array('default_value' => 'چه چیزی تحویل می‌گیرید')),
            kmpr_acf('field_kmpr_srvs_audience_title', 'srvs_audience_title', 'تیتر بخش مخاطب', 'text', array('default_value' => 'این خدمت مناسب شماست اگر…')),
            kmpr_acf('field_kmpr_srvs_steps_title', 'srvs_steps_title', 'تیتر بخش مراحل', 'text', array('default_value' => 'مراحل اجرا، قدم‌به‌قدم')),
            kmpr_acf('field_kmpr_srvs_plans_title', 'srvs_plans_title', 'تیتر بخش پلن‌ها', 'text', array('default_value' => 'پلن مناسب خودتان را انتخاب کنید')),
            kmpr_acf('field_kmpr_srvs_plans_note', 'srvs_plans_note', 'یادداشت زیر پلن‌ها', 'text', array('default_value' => 'قیمت نهایی بعد از جلسه اول و مشخص‌شدن دامنه کار، مکتوب اعلام می‌شود.')),
            kmpr_acf('field_kmpr_srvs_work_title', 'srvs_work_title', 'تیتر نمونه‌کارهای مرتبط', 'text', array('default_value' => 'پروژه‌هایی که با این خدمت ساخته‌ام')),
            kmpr_acf('field_kmpr_srvs_faq_title', 'srvs_faq_title', 'تیتر سوالات متداول', 'text', array('default_value' => 'سوالات رایج درباره این خدمت')),
            kmpr_acf('field_kmpr_srvs_other_title', 'srvs_other_title', 'تیتر سایر خدمات', 'text', array('default_value' => 'سایر خدماتی که ارائه می‌دهم')),
        ),
        'location'   => array(array(array('param' => 'options_page', 'operator' => '==', 'value' => 'acf-options-services-page'))),
        'menu_order' => 0,
    ));
}

/* -------------------------------------------------------------------------
 * Helpers
 * ---------------------------------------------------------------------- */

/** Option value of the services page with code default. */
function kmpr_srv_opt($name)
{
    return kmpr_field($name, null, 'option');
}

/** Normalise legacy on/off radio values (old DB rows use yes/no). */
function kmpr_srv_on($value)
{
    return in_array($value, array('on', 'yes', '1', 1, true), true);
}

/** Fallback stroke icons per request type. */
function kmpr_srv_fallback_icon($type = '')
{
    $icons = array(
        'website' => '<rect x="3" y="4" width="18" height="14" rx="2"/><path d="M3 8h18M7 6h.01M10 6h.01M8 21h8M12 18v3"/>',
        'seo'     => '<circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3M8 13l2-2 2 2 3-3"/>',
        'consult' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/>',
    );
    return $icons[$type] ?? '<path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"/>';
}

/**
 * Sanitised service icon SVG with explicit width/height + shrink-0.
 */
function kmpr_service_icon($post_id = null, $size = 28)
{
    $post_id = $post_id ?: get_the_ID();
    $raw     = (string) get_field('service-icon', $post_id);
    $size    = (int) $size;
    $allowed = array(
        'svg'      => array('xmlns' => 1, 'viewbox' => 1, 'fill' => 1, 'stroke' => 1, 'stroke-width' => 1, 'stroke-linecap' => 1, 'stroke-linejoin' => 1),
        'path'     => array('d' => 1, 'fill' => 1, 'stroke' => 1, 'fill-rule' => 1, 'clip-rule' => 1, 'stroke-width' => 1),
        'circle'   => array('cx' => 1, 'cy' => 1, 'r' => 1, 'fill' => 1),
        'rect'     => array('x' => 1, 'y' => 1, 'width' => 1, 'height' => 1, 'rx' => 1, 'ry' => 1, 'fill' => 1),
        'line'     => array('x1' => 1, 'y1' => 1, 'x2' => 1, 'y2' => 1),
        'polyline' => array('points' => 1),
        'polygon'  => array('points' => 1),
        'g'        => array('fill' => 1, 'stroke' => 1),
    );
    $svg = trim(wp_kses($raw, $allowed));
    if (stripos($svg, '<svg') === 0) {
        return preg_replace('/^<svg\b/i', '<svg width="' . $size . '" height="' . $size . '" class="shrink-0" aria-hidden="true" focusable="false"', $svg, 1);
    }
    return '<svg width="' . $size . '" height="' . $size . '" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
        . kmpr_srv_fallback_icon(get_field('request_type', $post_id)) . '</svg>';
}

/** Deliverables repeater rows with a title. */
function kmpr_srv_rows($name, $post_id = null, $key = 'title')
{
    $rows = get_field($name, $post_id ?: get_the_ID());
    return array_values(array_filter((array) $rows, function ($r) use ($key) {
        return is_array($r) && !empty($r[$key]);
    }));
}

/** Portfolio IDs related to a service (ACF relationship is stored serialized). */
function kmpr_srv_portfolio_ids($service_ids, $limit = 6)
{
    $service_ids = array_filter(array_map('intval', (array) $service_ids));
    if (!$service_ids) {
        return array();
    }
    $meta = array('relation' => 'OR');
    foreach ($service_ids as $sid) {
        $meta[] = array('key' => 'relation-service', 'value' => '"' . $sid . '"', 'compare' => 'LIKE');
    }
    return get_posts(array(
        'post_type'      => 'portfolio',
        'post_status'    => 'publish',
        'posts_per_page' => $limit,
        'fields'         => 'ids',
        'meta_query'     => $meta,
        'orderby'        => array('menu_order' => 'ASC', 'date' => 'DESC'),
        'no_found_rows'  => true,
    ));
}

/** Section header (kicker + h2 + optional text). */
function kmpr_srv_head($kicker, $title, $text = '', $id = '', $center = false)
{
    ?>
    <header class="<?= $center ? 'text-center mx-auto' : ''; ?> max-w-[42rem] mb-10 lg:mb-14">
        <?php if ($kicker) : ?>
            <span class="inline-flex items-center gap-2 text-xs font-black text-secondary mb-4" data-aos="fade-up">
                <span class="w-6 h-px bg-secondary"></span><?= esc_html($kicker); ?>
            </span>
        <?php endif; ?>
        <h2<?= $id ? ' id="' . esc_attr($id) . '"' : ''; ?> class="text-3xl sm:text-4xl lg:text-5xl font-black text-white leading-tight" data-split><?= esc_html($title); ?></h2>
        <?php if ($text) : ?>
            <p class="mt-5 text-slate-300 leading-loose" data-aos="fade-up" data-aos-delay="100"><?= esc_html($text); ?></p>
        <?php endif; ?>
    </header>
    <?php
}
