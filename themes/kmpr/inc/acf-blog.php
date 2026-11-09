<?php
/**
 * Blog "course" ACF schema (code-registered).
 *
 * - group_kmpr_post_lesson : lesson fields on every post (pillar, order, level, TL;DR, takeaways, FAQ)
 * - group_kmpr_course      : course fields on every category (title, intro, colour)
 * - group_kmpr_blog_page   : editable copy of the blog hub + lesson page, on the "Posts page" (page_for_posts)
 *
 * Field names / keys are a shared contract with the content pipeline — do not rename.
 *
 * @package KMPR
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Defaults for blog copy. Read through kmpr_blog_text() so the hub works even without ACF.
 */
function kmpr_blog_defaults()
{
    return array(
        'blog_hero_kicker'      => 'آکادمی کوروش',
        'blog_hero_title'       => 'یاد بگیر، درس به درس',
        'blog_hero_intro'       => 'مقاله‌ها را مثل یک دوره پشت سر هم بخوان: هر موضوع یک مسیر یادگیری است که از مقدمه شروع می‌شود و قدم‌به‌قدم به جزئیات فنی طراحی سایت، سرعت، سئو، وردپرس اختصاصی و امنیت می‌رسد. پیشرفتت روی همین مرورگر ذخیره می‌شود.',
        'blog_search_placeholder' => 'جستجو در درس‌ها و مقاله‌ها...',
        'blog_courses_kicker'   => 'مسیرهای یادگیری',
        'blog_courses_title'    => 'یک دوره را انتخاب کن',
        'blog_latest_kicker'    => 'تازه‌ها',
        'blog_latest_title'     => 'آخرین درس‌های منتشرشده',
        'blog_cta_kicker'       => 'از خواندن تا ساختن',
        'blog_cta_title'        => 'می‌خواهی همین‌ها را روی سایت خودت پیاده کنم؟',
        'blog_cta_text'         => 'اگر وقت پیاده‌سازی نداری، پروژه‌ات را برایم بفرست. خودم بررسی می‌کنم و مسیر دقیق و هزینه را شفاف می‌گویم.',
    );
}

/**
 * Blog copy value (stored on the posts page), with default.
 */
function kmpr_blog_text($name)
{
    $defaults = kmpr_blog_defaults();
    $page_id  = (int) get_option('page_for_posts');
    $value    = ($page_id && function_exists('get_field')) ? get_field($name, $page_id) : '';
    return ($value !== null && $value !== false && $value !== '') ? $value : ($defaults[$name] ?? '');
}

add_action('acf/include_fields', 'kmpr_register_blog_acf');

function kmpr_register_blog_acf()
{
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }

    /* ===== Lesson fields (post) ===== */
    acf_add_local_field_group(array(
        'key'      => 'group_kmpr_post_lesson',
        'title'    => 'درس / دوره آموزشی',
        'position' => 'normal',
        'style'    => 'default',
        'fields'   => array(
            array('key' => 'field_kmpr_post_pillar', 'name' => 'is_pillar', 'label' => 'مقاله ستون (Pillar) این دوره', 'type' => 'true_false', 'ui' => 1, 'default_value' => 0,
                'instructions' => 'مقاله اصلی و جامع دسته‌بندی؛ در صفحه دوره بالای سرفصل‌ها نمایش داده می‌شود.', 'wrapper' => array('width' => '33')),
            array('key' => 'field_kmpr_post_order', 'name' => 'lesson_order', 'label' => 'شماره درس در دوره', 'type' => 'number', 'min' => 0, 'step' => 1,
                'instructions' => 'ترتیب درس داخل دسته اصلی (کوچک‌تر = زودتر). خالی = بر اساس تاریخ.', 'wrapper' => array('width' => '33')),
            array('key' => 'field_kmpr_post_level', 'name' => 'difficulty', 'label' => 'سطح', 'type' => 'select', 'allow_null' => 1, 'ui' => 0,
                'choices' => array('beginner' => 'مقدماتی', 'intermediate' => 'متوسط', 'advanced' => 'پیشرفته'), 'wrapper' => array('width' => '34')),
            array('key' => 'field_kmpr_post_tldr', 'name' => 'tldr', 'label' => 'خلاصه سریع (TL;DR)', 'type' => 'textarea', 'rows' => 3, 'new_lines' => '',
                'instructions' => '۲ تا ۳ جمله؛ بالای مقاله نمایش داده می‌شود.'),
            array('key' => 'field_kmpr_post_takeaways', 'name' => 'key_takeaways', 'label' => 'نکات کلیدی', 'type' => 'textarea', 'rows' => 5, 'new_lines' => '',
                'instructions' => 'هر نکته در یک خط.'),
            array('key' => 'field_kmpr_post_faq', 'name' => 'post_faq', 'label' => 'پرسش‌های متداول این درس', 'type' => 'repeater', 'layout' => 'block', 'button_label' => 'افزودن پرسش',
                'sub_fields' => array(
                    array('key' => 'field_kmpr_post_faq_q', 'name' => 'question', 'label' => 'پرسش', 'type' => 'text'),
                    array('key' => 'field_kmpr_post_faq_a', 'name' => 'answer', 'label' => 'پاسخ', 'type' => 'textarea', 'rows' => 3, 'new_lines' => ''),
                )),
        ),
        'location' => array(array(array('param' => 'post_type', 'operator' => '==', 'value' => 'post'))),
    ));

    /* ===== Course fields (category) ===== */
    acf_add_local_field_group(array(
        'key'      => 'group_kmpr_course',
        'title'    => 'تنظیمات دوره',
        'fields'   => array(
            array('key' => 'field_kmpr_course_title', 'name' => 'course_title', 'label' => 'عنوان دوره', 'type' => 'text',
                'instructions' => 'خالی = نام دسته‌بندی.'),
            array('key' => 'field_kmpr_course_intro', 'name' => 'course_intro', 'label' => 'معرفی دوره', 'type' => 'textarea', 'rows' => 4, 'new_lines' => ''),
            array('key' => 'field_kmpr_course_color', 'name' => 'course_color', 'label' => 'رنگ دوره', 'type' => 'select', 'default_value' => 'amber',
                'choices' => array('amber' => 'کهربایی', 'sky' => 'آبی آسمانی', 'emerald' => 'زمردی', 'violet' => 'بنفش', 'rose' => 'رز')),
        ),
        'location' => array(array(array('param' => 'taxonomy', 'operator' => '==', 'value' => 'category'))),
    ));

    /* ===== Blog hub copy (posts page) ===== */
    $d = kmpr_blog_defaults();
    $f = function ($key, $name, $label, $type = 'text') use ($d) {
        $field = array('key' => $key, 'name' => $name, 'label' => $label, 'type' => $type, 'placeholder' => $d[$name], 'instructions' => 'پیش‌فرض: ' . $d[$name]);
        if ($type === 'textarea') {
            $field['rows'] = 3;
            $field['new_lines'] = '';
        }
        return $field;
    };
    acf_add_local_field_group(array(
        'key'      => 'group_kmpr_blog_page',
        'title'    => 'متن‌های صفحه وبلاگ و درس‌ها',
        'fields'   => array(
            array('key' => 'field_kmpr_blog_tab_hero', 'label' => 'هیرو وبلاگ', 'type' => 'tab'),
            $f('field_kmpr_blog_hero_kicker', 'blog_hero_kicker', 'برچسب بالای تیتر'),
            $f('field_kmpr_blog_hero_title', 'blog_hero_title', 'تیتر اصلی (H1)'),
            $f('field_kmpr_blog_hero_intro', 'blog_hero_intro', 'متن معرفی', 'textarea'),
            $f('field_kmpr_blog_search_ph', 'blog_search_placeholder', 'متن جستجو'),
            array('key' => 'field_kmpr_blog_tab_sections', 'label' => 'بخش‌ها', 'type' => 'tab'),
            $f('field_kmpr_blog_courses_kicker', 'blog_courses_kicker', 'برچسب بخش دوره‌ها'),
            $f('field_kmpr_blog_courses_title', 'blog_courses_title', 'تیتر بخش دوره‌ها'),
            $f('field_kmpr_blog_latest_kicker', 'blog_latest_kicker', 'برچسب آخرین مقالات'),
            $f('field_kmpr_blog_latest_title', 'blog_latest_title', 'تیتر آخرین مقالات'),
            array('key' => 'field_kmpr_blog_tab_cta', 'label' => 'دعوت به همکاری (صفحه درس)', 'type' => 'tab'),
            $f('field_kmpr_blog_cta_kicker', 'blog_cta_kicker', 'برچسب'),
            $f('field_kmpr_blog_cta_title', 'blog_cta_title', 'تیتر'),
            $f('field_kmpr_blog_cta_text', 'blog_cta_text', 'متن', 'textarea'),
        ),
        'location' => array(array(array('param' => 'page_type', 'operator' => '==', 'value' => 'posts_page'))),
    ));
}

/* -------------------------------------------------------------------------
 * Article images live in the Media Library (dashboard), not in theme code:
 * «تنظیمات سایت → تصاویر مقالات»: author photo (byline, author box, author replies) and the
 * fallback cover for posts without a featured image. On first admin visit the theme's bundled photos are
 * imported into the Media Library once and set here, so the owner can swap them from the dashboard.
 * ---------------------------------------------------------------------- */

add_action('acf/include_fields', function () {
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }
    acf_add_local_field_group(array(
        'key'      => 'group_kmpr_article_images',
        'title'    => 'تصاویر مقالات',
        'fields'   => array(
            array('key' => 'field_kmpr_author_photo', 'name' => 'author_photo', 'label' => 'عکس نویسنده', 'type' => 'image', 'return_format' => 'id', 'preview_size' => 'thumbnail', 'library' => 'all', 'instructions' => 'کنار نام نویسنده در مقاله، باکس نویسنده و پاسخ‌های نویسنده در دیدگاه‌ها.'),
            array('key' => 'field_kmpr_post_default_cover', 'name' => 'post_default_cover', 'label' => 'کاور پیش‌فرض مقاله', 'type' => 'image', 'return_format' => 'id', 'preview_size' => 'medium', 'library' => 'all', 'instructions' => 'برای مقاله‌هایی که تصویر شاخص ندارند.'),
        ),
        'location' => array(array(array('param' => 'options_page', 'operator' => '==', 'value' => 'acf-options-theme-settings'))),
        'menu_order' => 30,
    ));
});

/** Attachment URL of an «تصاویر مقالات» option; the bundled file is only a last-resort fallback. */
function kmpr_article_image_url($field, $size = 'thumbnail')
{
    $id = function_exists('get_field') ? (int) get_field($field, 'option', false) : 0;
    $url = $id ? wp_get_attachment_image_url($id, $size) : '';
    return $url ?: get_template_directory_uri() . '/public/images/korosh-mpr.webp';
}
function kmpr_author_photo_url($size = 'thumbnail')
{
    return kmpr_article_image_url('author_photo', $size);
}
function kmpr_default_cover_url($size = 'thumbnail')
{
    return kmpr_article_image_url('post_default_cover', $size);
}

/** One-time: move the bundled article photos into the Media Library and select them in the options. */
add_action('admin_init', function () {
    if (get_option('kmpr_article_images_v1') || !function_exists('update_field') || !current_user_can('upload_files')) {
        return;
    }
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';
    $import = function ($rel, $title, $alt) {
        $src = get_template_directory() . $rel;
        if (!is_readable($src)) {
            return 0;
        }
        $tmp = wp_tempnam(basename($src));
        if (!$tmp || !copy($src, $tmp)) {
            return 0;
        }
        $id = media_handle_sideload(array('name' => basename($src), 'tmp_name' => $tmp), 0, $title);
        if (is_wp_error($id)) {
            @unlink($tmp);
            return 0;
        }
        update_post_meta($id, '_wp_attachment_image_alt', $alt);
        return (int) $id;
    };
    $name = function_exists('kmpr_author_name') ? kmpr_author_name() : 'کوروش محمدپور';
    if (!get_field('author_photo', 'option', false)) {
        $id = $import('/public/images/korosh-mpr.webp', 'عکس نویسنده', $name);
        if ($id) {
            update_field('author_photo', $id, 'option');
        }
    }
    if (!get_field('post_default_cover', 'option', false)) {
        $id = $import('/public/images/og-default.png', 'کاور پیش‌فرض مقاله', get_bloginfo('name'));
        if ($id) {
            update_field('post_default_cover', $id, 'option');
        }
    }
    update_option('kmpr_article_images_v1', 1, false);
});

