<?php
/**
 * Online resume, about page & contact page content (ACF in code).
 *
 * - group_kmpr_resume_settings  → Theme Settings › Online Resume (and the /resume/ page itself)
 * - group_63e0abc0e64d1         → «درباره من» page (same key as the old DB group, so saved values survive)
 * - group_kmpr_contact_extra    → extra copy for the contact page
 *
 * Existing field names/keys are kept, new ones are prefixed field_kmpr_ / field_resume_.
 * Read resume values with kmpr_resume() (page → options → default).
 *
 * @package KMPR
 */

if (!defined('ABSPATH')) {
    exit;
}

/* -------------------------------------------------------------------------
 * Seed data (used as repeater fallbacks in templates and as one-time DB seed)
 * ---------------------------------------------------------------------- */

function kmpr_resume_seed($key)
{
    switch ($key) {
        case 'experiences':
            return array(
                array(
                    'job_title'   => 'توسعه‌دهنده فول‌استک (Fullstack Developer)',
                    'company'     => 'خانه سرمایه',
                    'period'      => 'آذر ۱۴۰۳ – تاکنون',
                    'tags'        => 'Next.js, React, WordPress, PHP, SEO',
                    'description' => '<p>توسعه و بهینه‌سازی عملکردهای پلتفرم با React.js و Next.js برای بهبود سرعت رندر و تجربه کاربری.</p><p>نگهداری، توسعه و سئوی زیرساخت‌های وردپرسی و پیاده‌سازی کدهای سفارشی PHP برای نیازهای پیچیده سازمان.</p>',
                ),
                array(
                    'job_title'   => 'طراح و توسعه‌دهنده وب',
                    'company'     => 'آژانس دیجیتال مارکتینگ ماکان',
                    'period'      => 'اسفند ۱۴۰۱ – آذر ۱۴۰۳',
                    'tags'        => 'WordPress, Elementor, Tailwind CSS, Alpine.js',
                    'description' => '<p>طراحی و پیاده‌سازی ده‌ها وب‌سایت شرکتی و فروشگاهی با المنتور و قالب‌های اختصاصی.</p><p>توسعه توابع اختصاصی PHP و کوئری‌های وردپرس (WP_Query) برای نمایش داینامیک محصولات و محتوا.</p><p>استفاده از Tailwind CSS و Alpine.js برای کاهش حجم فایل‌ها و افزایش سرعت سایت.</p>',
                ),
                array(
                    'job_title'   => 'طراح و توسعه‌دهنده وب',
                    'company'     => 'آژانس دیجیتال مارکتینگ پندپلاس',
                    'period'      => 'خرداد ۱۴۰۰ – اسفند ۱۴۰۰',
                    'tags'        => 'WordPress, SEO',
                    'description' => '<p>ساخت قالب‌های سفارشی وردپرس برای کمپین‌های دیجیتال مارکتینگ و بهینه‌سازی سایت‌ها برای موتورهای جستجو.</p>',
                ),
                array(
                    'job_title'   => 'توسعه‌دهنده وب (Web Developer)',
                    'company'     => 'مرکز تحقیقاتی بیورزونانس',
                    'period'      => 'تیر ۱۳۹۹ – خرداد ۱۴۰۰',
                    'tags'        => 'HTML, CSS, JavaScript',
                    'description' => '<p>پیاده‌سازی رابط کاربری و امکانات تحت وب با تمرکز بر طراحی واکنش‌گرا و استانداردهای روز.</p>',
                ),
                array(
                    'job_title'   => 'طراح سایت و کارشناس IT',
                    'company'     => 'تجارت الکترونیک دایان / پارس سوییچ بورد',
                    'period'      => 'آذر ۱۳۹۲ – تیر ۱۳۹۹',
                    'tags'        => 'IT, Web Design',
                    'description' => '<p>طراحی وب‌سایت‌های تجاری، پیاده‌سازی زیرساخت‌های فناوری اطلاعات و بهینه‌سازی فرآیندهای دیجیتال.</p>',
                ),
            );
        case 'education':
            return array(
                array('period' => '۱۳۹۴ – ۱۳۹۸', 'degree' => 'کارشناسی مهندسی فناوری اطلاعات (IT)', 'institution' => 'دانشگاه آزاد اسلامی'),
                array('period' => '۱۳۹۹ – ۱۴۰۱', 'degree' => 'دوره‌های تخصصی React و Next.js', 'institution' => 'توسعه مهارت‌های فرانت‌اند مدرن'),
                array('period' => '۱۴۰۱ – ۱۴۰۳', 'degree' => 'معماری پیشرفته وردپرس و PHP', 'institution' => 'پرفورمنس و بهینه‌سازی کوئری‌های دیتابیس'),
            );
        case 'focus':
            return array(
                array('label' => 'وردپرس و PHP اختصاصی', 'level' => 95, 'note' => 'قالب و افزونه اختصاصی، ACF، WooCommerce'),
                array('label' => 'Tailwind CSS و رابط کاربری', 'level' => 92, 'note' => 'طراحی واکنش‌گرا، RTL، سیستم دیزاین'),
                array('label' => 'JavaScript و Alpine.js', 'level' => 85, 'note' => 'تعامل سبک بدون فریم‌ورک سنگین'),
                array('label' => 'React و Next.js', 'level' => 78, 'note' => 'اپلیکیشن‌های سریع با رندر سمت سرور'),
                array('label' => 'سئو تکنیکال و پرفورمنس', 'level' => 88, 'note' => 'Core Web Vitals، اسکیما، ساختار محتوا'),
            );
        case 'languages':
            return array(
                array('name' => 'فارسی', 'level_label' => 'زبان مادری', 'level' => 100),
                array('name' => 'English', 'level_label' => 'مطالعه مستندات و منابع فنی', 'level' => 70),
            );
        case 'about_values':
            return array(
                array('tag' => '01 / PERFORMANCE', 'title' => 'سرعت، قبل از هر چیز', 'text' => 'هیچ اسکریپت یا افزونه سنگینی را بدون دلیل فنی وارد پروژه نمی‌کنم؛ چون هر ثانیه تأخیر یعنی از دست دادن مشتری.'),
                array('tag' => '02 / CLEAN CODE', 'title' => 'کد تمیز و قابل توسعه', 'text' => 'به جای قالب‌های آماده و شلوغ، ساختار ماژولار و خوانا می‌نویسم تا سایت شما سال‌ها قابل نگهداری بماند.'),
                array('tag' => '03 / SEO & AI', 'title' => 'دیده‌شدن در گوگل و موتورهای هوش مصنوعی', 'text' => 'ساختار معنایی، اسکیما و llms.txt را از روز اول در نظر می‌گیرم تا محتوای شما درست فهمیده شود.'),
                array('tag' => '04 / DIRECT', 'title' => 'ارتباط مستقیم، بدون واسطه', 'text' => 'از اولین گفت‌وگو تا تحویل، طرف حساب شما خودم هستم؛ همان کسی که کد را می‌نویسد.'),
            );
    }
    return array();
}

/* -------------------------------------------------------------------------
 * Readers
 * ---------------------------------------------------------------------- */

/**
 * Resume value: page (if saved there) → options page → code default.
 */
function kmpr_resume($name, $default = null)
{
    if ($default === null) {
        $default = $GLOBALS['kmpr_acf_defaults'][$name] ?? '';
    }
    if (!function_exists('get_field')) {
        return $default;
    }
    $empty = function ($v) {
        return $v === null || $v === false || $v === '' || $v === array();
    };
    $pid = is_page() ? get_queried_object_id() : 0;
    if ($pid && metadata_exists('post', $pid, $name)) {
        $v = get_field($name, $pid);
        if (!$empty($v)) {
            return $v;
        }
    }
    $v = get_field($name, 'option');
    return $empty($v) ? $default : $v;
}

/**
 * Repeater rows for the resume with seed fallback.
 */
function kmpr_resume_rows($name, $seed_key = '')
{
    $rows = kmpr_resume($name, array());
    if (is_array($rows) && $rows) {
        return $rows;
    }
    return $seed_key ? kmpr_resume_seed($seed_key) : array();
}

/**
 * Comma/newline separated list → array.
 */
function kmpr_resume_list($text)
{
    return array_values(array_filter(array_map('trim', preg_split('/[,،\r\n]+/u', (string) $text))));
}

/**
 * WhatsApp link with a prefilled message (strips placeholder query strings from the option).
 */
function kmpr_whatsapp_url($text = '')
{
    $base = strtok((string) kmpr_contact_url('whatsapp'), '?');
    if (!$base) {
        return '';
    }
    $base = str_replace('wa.me/+', 'wa.me/', $base);
    return $text !== '' ? $base . '?text=' . rawurlencode($text) : $base;
}

/**
 * Escape text and isolate Latin parentheticals, e.g. «... (Full-Stack Developer)», so RTL doesn't flip the brackets.
 */
function kmpr_bidi($text)
{
    return preg_replace('/\(([A-Za-z][^()]*)\)/', '<bdi dir="ltr">($1)</bdi>', esc_html((string) $text));
}

/**
 * Display phone as ۰۹۳۵ ۸۰۸ ۸۱۱۸ (Persian digits, Iranian mobile grouping).
 */
function kmpr_phone_display($phone)
{
    $d = preg_replace('/\D/', '', (string) $phone);
    if (strpos($d, '98') === 0 && strlen($d) === 12) {
        $d = '0' . substr($d, 2);
    }
    if (strlen($d) === 11) {
        $d = substr($d, 0, 4) . ' ' . substr($d, 4, 3) . ' ' . substr($d, 7);
    }
    return kmpr_fa_num($d);
}

/**
 * Image field (array|id|url) → array(url, width, height) with fallback.
 */
function kmpr_image_src($value, $size = 'large')
{
    $id = 0;
    if (is_array($value)) {
        $id = (int) ($value['ID'] ?? $value['id'] ?? 0);
    } elseif (is_numeric($value)) {
        $id = (int) $value;
    } elseif (is_string($value) && $value !== '') {
        return array('url' => $value, 'width' => 800, 'height' => 800);
    }
    if ($id && ($src = wp_get_attachment_image_src($id, $size))) {
        return array('url' => $src[0], 'width' => $src[1], 'height' => $src[2]);
    }
    return array('url' => get_template_directory_uri() . '/public/images/korosh-mpr.webp', 'width' => 800, 'height' => 800);
}

/* -------------------------------------------------------------------------
 * Field groups
 * ---------------------------------------------------------------------- */

add_action('acf/include_fields', 'kmpr_register_resume_field_group');

function kmpr_register_resume_field_group()
{
    if (!function_exists('acf_add_local_field_group') || !function_exists('kmpr_acf')) {
        return;
    }

    $resume_url = esc_url(home_url('/resume/'));

    acf_add_local_field_group(array(
        'key'    => 'group_kmpr_resume_settings',
        'title'  => 'مدیریت محتوای رزومه آنلاین (/resume/)',
        'fields' => array(
            kmpr_acf_tab('field_resume_tab_info', 'مشخصات پایه'),
            array(
                'key'       => 'field_resume_notice_banner',
                'label'     => 'لینک رزومه',
                'name'      => '',
                'type'      => 'message',
                'message'   => '<div style="background:#0f172a;color:#f8fafc;padding:14px 18px;border-radius:12px;border:1px solid #334155"><strong style="color:#f59e0b">رزومه آنلاین:</strong> <a href="' . $resume_url . '" target="_blank" style="color:#fbbf24">' . $resume_url . '</a><p style="margin:8px 0 0;color:#cbd5e1;font-size:12px">همه فیلدها مقدار پیش‌فرض دارند؛ هر فیلدی که پر شود، در نسخه آنلاین و چاپی جایگزین می‌شود.</p></div>',
                'new_lines' => '',
                'esc_html'  => 0,
            ),
            kmpr_acf('field_resume_photo', 'resume_photo', 'تصویر پرسنلی', 'image', array('return_format' => 'array', 'preview_size' => 'medium', 'instructions' => 'خالی = تصویر بخش اول صفحه اصلی.')),
            kmpr_acf('field_resume_full_name', 'resume_full_name', 'نام و نام خانوادگی', 'text', array('default_value' => 'کوروش محمدپور', 'wrapper' => array('width' => '50'))),
            kmpr_acf('field_kmpr_resume_latin', 'resume_latin_name', 'نام لاتین', 'text', array('default_value' => 'Korosh Mpr', 'wrapper' => array('width' => '50'))),
            kmpr_acf('field_resume_job_title', 'resume_job_title', 'عنوان شغلی', 'text', array('default_value' => 'توسعه‌دهنده ارشد وب و معمار نرم‌افزار (Full-Stack Developer)')),
            kmpr_acf('field_kmpr_resume_tagline', 'resume_tagline', 'جمله معرفی کوتاه', 'textarea', array('rows' => 2, 'default_value' => 'وب‌سایت‌های سریع، تمیز و سئو‌محور می‌سازم؛ از طراحی رابط کاربری تا کدنویسی اختصاصی وردپرس و Next.js.')),
            kmpr_acf('field_resume_location', 'resume_location', 'موقعیت مکانی', 'text', array('default_value' => 'تهران، نارمک', 'wrapper' => array('width' => '50'))),
            kmpr_acf('field_kmpr_resume_available', 'resume_available', 'آماده پذیرش پروژه هستم', 'true_false', array('ui' => 1, 'default_value' => 1, 'wrapper' => array('width' => '50'))),
            kmpr_acf('field_kmpr_resume_avail_on', 'resume_available_text', 'متن وضعیت (در دسترس)', 'text', array('default_value' => 'برای پروژه فریلنس جدید در دسترسم', 'wrapper' => array('width' => '50'))),
            kmpr_acf('field_kmpr_resume_avail_off', 'resume_busy_text', 'متن وضعیت (مشغول)', 'text', array('default_value' => 'فعلاً ظرفیت پروژه جدید ندارم', 'wrapper' => array('width' => '50'))),
            kmpr_acf('field_resume_pdf_file', 'resume_pdf_file', 'فایل PDF رزومه (اختیاری)', 'file', array('return_format' => 'array', 'instructions' => 'خالی = دکمه دانلود، نسخه چاپی صفحه را می‌سازد.')),

            kmpr_acf_tab('field_resume_tab_summary', 'خلاصه و تمرکز'),
            kmpr_acf('field_kmpr_resume_t_summary', 'resume_title_summary', 'تیتر بخش خلاصه', 'text', array('default_value' => 'کمی درباره من')),
            kmpr_acf('field_resume_summary', 'resume_summary', 'متن خلاصه حرفه‌ای', 'textarea', array('rows' => 6, 'default_value' => 'توسعه‌دهنده فول‌استک و طراح سایت هستم و صفر تا صد وب‌سایت‌های شرکتی و فروشگاهی را طراحی و پیاده‌سازی می‌کنم. تخصص اصلی‌ام برنامه‌نویسی اختصاصی و بهینه‌سازی قالب‌های وردپرس (PHP) است و با React و Next.js تجربه‌های کاربری روان و سریع می‌سازم. برایم سئوی فنی، سرعت و کدی که بعداً هم قابل نگهداری باشد، به اندازه ظاهر زیبا مهم است.')),
            kmpr_acf('field_kmpr_resume_focus', 'resume_focus', 'مهارت‌های کلیدی (نوار سطح)', 'repeater', array(
                'layout'       => 'table',
                'button_label' => 'افزودن مهارت',
                'instructions' => 'خالی = مقادیر پیش‌فرض.',
                'sub_fields'   => array(
                    kmpr_acf('field_kmpr_resume_focus_l', 'label', 'مهارت'),
                    kmpr_acf('field_kmpr_resume_focus_v', 'level', 'سطح (۰ تا ۱۰۰)', 'number', array('min' => 0, 'max' => 100)),
                    kmpr_acf('field_kmpr_resume_focus_n', 'note', 'توضیح کوتاه'),
                ),
            )),

            kmpr_acf_tab('field_resume_tab_skills', 'مهارت‌ها و ابزارها'),
            kmpr_acf('field_kmpr_resume_t_skills', 'resume_title_skills', 'تیتر بخش مهارت‌ها', 'text', array('default_value' => 'جعبه‌ابزار من')),
            kmpr_acf('field_resume_skills_frontend', 'resume_skills_frontend', 'فرانت‌اند (با کاما جدا کنید)', 'textarea', array('rows' => 3, 'default_value' => 'React JS, Next.js, JavaScript (ESNext), Tailwind CSS v4, Alpine.js, Bootstrap, Responsive Design')),
            kmpr_acf('field_resume_skills_backend', 'resume_skills_backend', 'بک‌اند و CMS (با کاما جدا کنید)', 'textarea', array('rows' => 3, 'default_value' => 'WordPress (Custom Theme & Plugin Development), PHP, Laravel, REST API, MySQL Optimization')),
            kmpr_acf('field_resume_skills_tools', 'resume_skills_tools', 'ابزارها و استانداردها (با کاما جدا کنید)', 'textarea', array('rows' => 3, 'default_value' => 'SEO & Performance (Core Web Vitals), Git / GitHub, Elementor, WooCommerce, AI & llms.txt')),
            kmpr_acf('field_kmpr_resume_marquee', 'resume_tools_marquee', 'نوار متحرک ابزارها (هر خط یک مورد)', 'textarea', array('rows' => 6, 'default_value' => "WordPress\nPHP\nNext.js\nReact\nTailwind CSS\nAlpine.js\nACF\nWooCommerce\nLaravel\nMySQL\nGit\nFigma\nesbuild\nGoogle Search Console")),

            kmpr_acf_tab('field_resume_tab_experience', 'سوابق شغلی'),
            kmpr_acf('field_kmpr_resume_t_timeline', 'resume_title_timeline', 'تیتر بخش سوابق', 'text', array('default_value' => 'مسیر حرفه‌ای من')),
            array(
                'key'          => 'field_resume_experiences',
                'label'        => 'سوابق شغلی',
                'name'         => 'resume_experiences',
                'type'         => 'repeater',
                'instructions' => 'اولین ردیف به عنوان «موقعیت فعلی» برجسته می‌شود.',
                'button_label' => 'افزودن موقعیت شغلی',
                'layout'       => 'block',
                'sub_fields'   => array(
                    array('key' => 'field_exp_title', 'label' => 'سمت شغلی', 'name' => 'job_title', 'type' => 'text', 'wrapper' => array('width' => '40')),
                    array('key' => 'field_exp_company', 'label' => 'شرکت / سازمان', 'name' => 'company', 'type' => 'text', 'wrapper' => array('width' => '35')),
                    array('key' => 'field_exp_period', 'label' => 'بازه زمانی', 'name' => 'period', 'type' => 'text', 'wrapper' => array('width' => '25')),
                    array('key' => 'field_kmpr_exp_tags', 'label' => 'تکنولوژی‌ها (با کاما)', 'name' => 'tags', 'type' => 'text'),
                    array('key' => 'field_exp_desc', 'label' => 'توضیحات و دستاوردها', 'name' => 'description', 'type' => 'wysiwyg', 'media_upload' => 0, 'toolbar' => 'basic'),
                ),
            ),

            kmpr_acf_tab('field_kmpr_resume_tab_projects', 'پروژه‌ها'),
            kmpr_acf('field_kmpr_resume_t_projects', 'resume_title_projects', 'تیتر بخش پروژه‌ها', 'text', array('default_value' => 'چند پروژه منتخب')),
            kmpr_acf('field_kmpr_resume_proj_n', 'resume_projects_count', 'تعداد نمونه‌کار', 'number', array('default_value' => 6, 'min' => 2, 'max' => 12, 'instructions' => 'از نمونه‌کارهای سایت خوانده می‌شود؛ موارد «ویژه» اول نمایش داده می‌شوند.')),
            kmpr_acf('field_kmpr_resume_domains', 'resume_project_domains', 'حوزه‌ها و مشتریان (هر خط: عنوان: نام‌ها)', 'textarea', array('rows' => 7, 'default_value' => "مالی و سرمایه‌گذاری: خانه سرمایه، فینوست ۲۰۲۶، استعلام شبا خانه سرمایه، صرافی کوینکس\nسلامت و پزشکی: پزشکینو، رایان هلت، بیوتکنولب، آراسیما\nصنعتی و شرکتی: گروه صنعتی ایرا، باربری رادمان، گروه ریسمان، متاویژن، آگِر تریدینگ، هشتک، مدرسه تکاپو\nتبلیغات و رسانه: آژانس بلوباکس، کانون مات، هوگر، ارتباطات محیطی آرمانی، رای فیلم، آژانس پندپلاس، آژانس ماکان\nفروشگاهی و تولیدی: تولیدی نیل موتی، پارچه رادوینا، اوه آنلاین، رانی مانیا، روکارنو، دنون، اصالت، فروشگاه گیلدا، رجبی یدک، پنتا هوم، شارپ ایران\nکمپین‌ها: ماست سون کاله، واتا، ایکس ویژن، حکمرانی، قرعه‌کشی رانی، ولنتاین روژا، یلدا روژا\nوب‌اپلیکیشن و وب ۳: تترچند، ویچ گیم")),

            kmpr_acf_tab('field_resume_tab_edu', 'تحصیلات و زبان'),
            kmpr_acf('field_kmpr_resume_t_edu', 'resume_title_education', 'تیتر بخش تحصیلات', 'text', array('default_value' => 'تحصیلات و دوره‌ها')),
            array(
                'key'          => 'field_resume_education',
                'label'        => 'مدارک و دوره‌ها',
                'name'         => 'resume_education',
                'type'         => 'repeater',
                'button_label' => 'افزودن مدرک / دوره',
                'layout'       => 'table',
                'sub_fields'   => array(
                    array('key' => 'field_edu_period', 'label' => 'بازه زمانی', 'name' => 'period', 'type' => 'text', 'wrapper' => array('width' => '25')),
                    array('key' => 'field_edu_degree', 'label' => 'عنوان مدرک یا دوره', 'name' => 'degree', 'type' => 'text', 'wrapper' => array('width' => '45')),
                    array('key' => 'field_edu_inst', 'label' => 'دانشگاه / موسسه', 'name' => 'institution', 'type' => 'text', 'wrapper' => array('width' => '30')),
                ),
            ),
            kmpr_acf('field_kmpr_resume_t_lang', 'resume_title_languages', 'تیتر بخش زبان‌ها', 'text', array('default_value' => 'زبان‌ها')),
            kmpr_acf('field_kmpr_resume_langs', 'resume_languages', 'زبان‌ها', 'repeater', array(
                'layout'       => 'table',
                'button_label' => 'افزودن زبان',
                'instructions' => 'خالی = مقادیر پیش‌فرض.',
                'sub_fields'   => array(
                    kmpr_acf('field_kmpr_resume_lang_n', 'name', 'زبان'),
                    kmpr_acf('field_kmpr_resume_lang_l', 'level_label', 'سطح (متن)'),
                    kmpr_acf('field_kmpr_resume_lang_v', 'level', 'سطح (۰ تا ۱۰۰)', 'number', array('min' => 0, 'max' => 100)),
                ),
            )),

            kmpr_acf_tab('field_kmpr_resume_tab_testi', 'توصیه‌نامه‌ها'),
            kmpr_acf('field_kmpr_resume_t_testi', 'resume_title_testimonials', 'تیتر بخش توصیه‌نامه‌ها', 'text', array('default_value' => 'از زبان همکاران و کارفرماها')),
            kmpr_acf('field_kmpr_resume_testi', 'resume_testimonials', 'توصیه‌نامه‌ها', 'repeater', array(
                'layout'       => 'block',
                'button_label' => 'افزودن توصیه‌نامه',
                'instructions' => 'فقط نقل‌قول‌های واقعی. اگر خالی باشد، این بخش نمایش داده نمی‌شود.',
                'sub_fields'   => array(
                    kmpr_acf('field_kmpr_resume_testi_q', 'quote', 'متن', 'textarea', array('rows' => 3)),
                    kmpr_acf('field_kmpr_resume_testi_n', 'name', 'نام', 'text', array('wrapper' => array('width' => '50'))),
                    kmpr_acf('field_kmpr_resume_testi_r', 'role', 'سمت / شرکت', 'text', array('wrapper' => array('width' => '50'))),
                ),
            )),

            kmpr_acf_tab('field_kmpr_resume_tab_cta', 'دعوت به همکاری'),
            kmpr_acf('field_kmpr_resume_cta_t', 'resume_cta_title', 'تیتر', 'text', array('default_value' => 'پروژه بعدی را با هم بسازیم؟')),
            kmpr_acf('field_kmpr_resume_cta_d', 'resume_cta_text', 'توضیح', 'textarea', array('rows' => 2, 'default_value' => 'اگر برای سایت جدید، بازطراحی یا همکاری بلندمدت دنبال یک توسعه‌دهنده مستقل هستید، چند سؤال کوتاه را جواب بدهید تا خودم بررسی کنم و برآورد زمان و هزینه را برایتان بفرستم.')),
        ),
        'location' => array(
            array(array('param' => 'options_page', 'operator' => '==', 'value' => 'acf-options-resume-settings')),
            array(array('param' => 'page_template', 'operator' => '==', 'value' => 'page-resume.php')),
        ),
        'menu_order' => 10,
    ));

    /* ===== About page (same key as the original DB group) ===== */
    acf_add_local_field_group(array(
        'key'      => 'group_63e0abc0e64d1',
        'title'    => 'درباره من',
        'fields'   => array(
            kmpr_acf_tab('field_kmpr_about_tab_hero', 'معرفی'),
            kmpr_acf('field_kmpr_about_badge', 'about_badge', 'نشان بالای تیتر', 'text', array('default_value' => 'مسیر من، فلسفه کاری و چیزهایی که به آن پایبندم')),
            kmpr_acf('field_63e9d6d9cbb6f', 'aboutus-title', 'تیتر', 'text', array('default_value' => 'وب‌سایت‌هایی می‌سازم که سریع‌اند، درست دیده می‌شوند و کار می‌کنند')),
            kmpr_acf('field_63e9d6fdcbb70', 'aboutus-content', 'متن معرفی', 'wysiwyg', array('toolbar' => 'basic', 'media_upload' => 0, 'default_value' => '<p>من کوروش محمدپور هستم؛ طراح و توسعه‌دهنده وب. سال‌هاست که برای کسب‌وکارها وب‌سایت شرکتی، فروشگاه اینترنتی و وب‌اپلیکیشن می‌سازم و هر خط کد را خودم می‌نویسم.</p><p>باورم این است که یک وب‌سایت خوب نباید زیر بار قالب‌های آماده و افزونه‌های سنگین کند شود. برای همین روی کد اختصاصی، سرعت و سئوی فنی تمرکز می‌کنم تا سایت شما هم زیبا باشد و هم نتیجه بدهد.</p>')),
            kmpr_acf('field_63e9d6b4cbb6e', 'aboutus_image', 'تصویر', 'image', array('return_format' => 'array', 'preview_size' => 'medium')),
            kmpr_acf('field_kmpr_about_img_cap', 'about_image_caption', 'زیرنویس تصویر', 'text', array('default_value' => 'Web Developer & Designer')),
            kmpr_acf_tab('field_kmpr_about_tab_values', 'اصول کاری'),
            kmpr_acf('field_kmpr_about_values_t', 'about_values_title', 'تیتر', 'text', array('default_value' => 'چرا همکاری مستقیم با من فرق دارد؟')),
            kmpr_acf('field_kmpr_about_values_d', 'about_values_text', 'توضیح', 'text', array('default_value' => 'اصولی که در هر پروژه، از اولین جلسه تا تحویل، به آن‌ها پایبندم.')),
            kmpr_acf('field_kmpr_about_values', 'about_values', 'اصول', 'repeater', array(
                'layout'       => 'block',
                'button_label' => 'افزودن اصل',
                'instructions' => 'خالی = مقادیر پیش‌فرض.',
                'sub_fields'   => array(
                    kmpr_acf('field_kmpr_about_v_tag', 'tag', 'برچسب لاتین', 'text', array('wrapper' => array('width' => '30'))),
                    kmpr_acf('field_kmpr_about_v_t', 'title', 'عنوان', 'text', array('wrapper' => array('width' => '70'))),
                    kmpr_acf('field_kmpr_about_v_d', 'text', 'توضیح', 'textarea', array('rows' => 2)),
                ),
            )),
            kmpr_acf_tab('field_kmpr_about_tab_journey', 'مسیر و دعوت'),
            kmpr_acf('field_kmpr_about_journey_t', 'about_journey_title', 'تیتر مسیر حرفه‌ای', 'text', array('default_value' => 'مسیری که طی کرده‌ام', 'instructions' => 'موارد از «سوابق شغلی» رزومه خوانده می‌شوند.')),
            kmpr_acf('field_kmpr_about_cta_t', 'about_cta_title', 'تیتر دعوت', 'text', array('default_value' => 'ایده‌ای در ذهن دارید؟ بیایید درباره‌اش حرف بزنیم')),
            kmpr_acf('field_kmpr_about_cta_d', 'about_cta_text', 'توضیح دعوت', 'textarea', array('rows' => 2, 'default_value' => 'چند سؤال کوتاه درباره پروژه‌تان جواب بدهید تا خودم بررسی کنم و مسیر، زمان و هزینه را شفاف برایتان بنویسم.')),
        ),
        'location' => array(array(array('param' => 'page_template', 'operator' => '==', 'value' => 'page-about-us.php'))),
    ));

    /* ===== Contact page extras ===== */
    acf_add_local_field_group(array(
        'key'      => 'group_kmpr_contact_extra',
        'title'    => 'ارتباط با من — بخش‌های تکمیلی',
        'fields'   => array(
            kmpr_acf('field_kmpr_ct_channels_t', 'contact_channels_title', 'تیتر راه‌های ارتباطی', 'text', array('default_value' => 'از هر راهی که راحت‌ترید پیام بدهید')),
            kmpr_acf('field_kmpr_ct_response', 'contact_response_note', 'زمان پاسخ‌گویی', 'text', array('default_value' => 'معمولاً همان روز کاری جواب می‌دهم')),
            kmpr_acf('field_kmpr_ct_hours', 'contact_hours', 'ساعات کاری', 'text', array('default_value' => 'شنبه تا پنج‌شنبه، ۱۰ تا ۱۹')),
            kmpr_acf('field_kmpr_ct_faq_t', 'contact_faq_title', 'تیتر سؤالات (۳ سؤال اول صفحه سؤالات متداول)', 'text', array('default_value' => 'قبل از شروع همکاری')),
            kmpr_acf('field_kmpr_ct_promise_t', 'contact_promise_title', 'تیتر تعهد', 'text', array('default_value' => 'تعهد من به شما')),
            kmpr_acf('field_kmpr_ct_promise_d', 'contact_promise_text', 'متن تعهد', 'textarea', array('rows' => 2, 'default_value' => 'کد تمیز، تحویل سورس کامل به نام خودتان و پاسخ‌گویی مستقیم؛ بدون واسطه و بدون هزینه پنهان.')),
        ),
        'location' => array(array(array('param' => 'page_template', 'operator' => '==', 'value' => 'page-contact-us.php'))),
        'menu_order' => 20,
    ));
}

/**
 * One-time seed of the resume repeaters into the options page (admin only, cheap check).
 */
function kmpr_auto_seed_resume_defaults()
{
    if (!function_exists('update_field') || get_option('kmpr_resume_seeded_v2')) {
        return;
    }
    if (empty(get_field('resume_experiences', 'option'))) {
        update_field('resume_experiences', kmpr_resume_seed('experiences'), 'option');
    }
    if (empty(get_field('resume_education', 'option'))) {
        update_field('resume_education', kmpr_resume_seed('education'), 'option');
    }
    update_option('kmpr_resume_seeded_v2', 1, false);
}
add_action('admin_init', 'kmpr_auto_seed_resume_defaults', 20);
