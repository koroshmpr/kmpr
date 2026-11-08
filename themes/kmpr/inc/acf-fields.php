<?php
/**
 * ACF schema in code (single source of truth, versioned with the theme).
 *
 * Group keys and existing field keys/names are REUSED from the original database groups,
 * so ACF prefers these local definitions and all previously saved values keep working.
 * Every text a visitor sees on the home, services, portfolio, contact and FAQ templates is editable here.
 *
 * Read values with kmpr_field() which falls back to sensible defaults when a field is empty.
 *
 * @package KMPR
 */

if (!defined('ABSPATH')) {
    exit;
}

/* -------------------------------------------------------------------------
 * Helpers
 * ---------------------------------------------------------------------- */

/**
 * get_field() with a default for empty values. Works for options ('option') too.
 */
function kmpr_field($name, $default = null, $post_id = false)
{
    if ($default === null) {
        $default = $GLOBALS['kmpr_acf_defaults'][$name] ?? '';
    }
    if (!function_exists('get_field')) {
        return $default;
    }
    $value = get_field($name, $post_id);
    if ($value === null || $value === false || $value === '' || $value === array()) {
        return $default;
    }
    return $value;
}

/**
 * Normalise an ACF link / url field to array( url, title, target ).
 */
function kmpr_link($value, $fallback_url = '', $fallback_title = '')
{
    if (is_array($value) && !empty($value['url'])) {
        return array('url' => $value['url'], 'title' => $value['title'] ?: $fallback_title, 'target' => $value['target'] ?? '');
    }
    if (is_string($value) && $value !== '') {
        return array('url' => $value, 'title' => $fallback_title, 'target' => '');
    }
    return array('url' => $fallback_url, 'title' => $fallback_title, 'target' => '');
}

/**
 * Textarea "one item per line" → array.
 */
function kmpr_lines($text)
{
    return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $text))));
}

/**
 * Contact values from the «راه‌های ارتباطی» options page (handles legacy field names).
 */
function kmpr_contact($key)
{
    switch ($key) {
        case 'phone':
            return kmpr_field('phone_number', '', 'option') ?: '09358088118';
        case 'linkedin':
            return kmpr_field('linkedIn', '', 'option') ?: kmpr_field('linkedin', '', 'option');
        default:
            return kmpr_field($key, '', 'option');
    }
}

/* -------------------------------------------------------------------------
 * Tiny builders to keep the schema readable
 * ---------------------------------------------------------------------- */

function kmpr_acf_tab($key, $label)
{
    return array('key' => $key, 'label' => $label, 'type' => 'tab', 'placement' => 'top');
}

function kmpr_acf($key, $name, $label, $type = 'text', $extra = array())
{
    if (isset($extra['default_value']) && $extra['default_value'] !== '') {
        $GLOBALS['kmpr_acf_defaults'][$name] = $extra['default_value'];
    }
    return array_merge(array('key' => $key, 'name' => $name, 'label' => $label, 'type' => $type), $extra);
}

/* -------------------------------------------------------------------------
 * Field groups
 * ---------------------------------------------------------------------- */

add_action('acf/include_fields', 'kmpr_register_acf_schema');
add_action('after_setup_theme', function () {
    if (!function_exists('acf_add_local_field_group')) {
        // ACF inactive: still collect defaults so templates render the copy.
        $GLOBALS['kmpr_acf_defaults_only'] = true;
    }
});

function kmpr_register_acf_schema()
{
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }

    /* ===== Theme settings (options) ===== */
    acf_add_local_field_group(array(
        'key'      => 'group_63bea2ea21de3',
        'title'    => 'تنظیمات کلی سایت',
        'fields'   => array(
            kmpr_acf_tab('field_63beaebd19e22', 'لوگو و برند'),
            kmpr_acf('field_63e9f80da9b1a', 'favicon', 'آیکون سایت (Favicon)', 'image', array('return_format' => 'id', 'preview_size' => 'thumbnail')),
            kmpr_acf('field_63beaed319e23', 'brand_name', 'نام برند'),
            kmpr_acf('field_63da055110c90', 'logo_type', 'نوع لوگو', 'radio', array('choices' => array('default' => 'لوگوی پیش‌فرض قالب', 'svg' => 'کد SVG', 'image' => 'تصویر'), 'layout' => 'horizontal')),
            kmpr_acf('field_63beaeff19e24', 'brand_logo', 'لوگو (کد SVG)', 'textarea', array('conditional_logic' => array(array(array('field' => 'field_63da055110c90', 'operator' => '==', 'value' => 'svg'))))),
            kmpr_acf('field_63da05a410c91', 'brand_logo_img', 'لوگو (تصویر)', 'image', array('return_format' => 'array', 'conditional_logic' => array(array(array('field' => 'field_63da055110c90', 'operator' => '==', 'value' => 'image'))))),
            kmpr_acf('field_64f202cefdd2e', 'author-image-default', 'تصویر پیش‌فرض نویسنده (HTML)', 'textarea', array('rows' => 2)),

            kmpr_acf_tab('field_kmpr_tab_profile', 'پروفایل و آمار'),
            kmpr_acf('field_kmpr_career_start', 'career_start_year', 'سال شروع فعالیت حرفه‌ای (شمسی)', 'number', array('instructions' => 'سال‌های تجربه در کل سایت به‌صورت خودکار از این عدد محاسبه می‌شود.', 'default_value' => 1392, 'min' => 1370, 'max' => 1420)),
            kmpr_acf('field_kmpr_job_title', 'job_title', 'عنوان شغلی (نمایش در سایت)', 'text', array('default_value' => 'طراح و توسعه‌دهنده وب')),
            kmpr_acf('field_kmpr_response_time', 'response_time', 'متن زمان پاسخ‌گویی', 'text', array('default_value' => 'پاسخ‌گویی در چند ساعت')),
            kmpr_acf('field_kmpr_available', 'available_for_work', 'آماده پذیرش پروژه جدید هستم', 'true_false', array('ui' => 1, 'default_value' => 1)),

            kmpr_acf_tab('field_63bea2eafc834', 'فوتر'),
            kmpr_acf('field_63e8966e39055', 'footer_description', 'توضیحات فوتر', 'textarea', array('rows' => 3)),
            kmpr_acf('field_63c4e767d34e0', 'first_menu', 'تیتر منوی اول', 'text', array('default_value' => 'دسترسی سریع')),
            kmpr_acf('field_63c4e794d34e1', 'second_menu', 'تیتر منوی دوم', 'text', array('default_value' => 'خدمات')),
            kmpr_acf('field_63c4e7a8d34e2', 'third_menu', 'تیتر منوی سوم', 'text', array('default_value' => 'مقالات')),
            kmpr_acf('field_63c4ed940984e', 'contact_title', 'تیتر بخش ارتباط', 'text', array('default_value' => 'مشاوره و ارتباط')),
            kmpr_acf('field_63c4e8793fb7f', 'confirmation', 'نمادها و مجوزها', 'repeater', array(
                'layout' => 'table', 'button_label' => 'افزودن نماد',
                'sub_fields' => array(kmpr_acf('field_63c4e8b33fb80', 'conf_items', 'کد نماد (HTML)', 'textarea', array('rows' => 2))),
            )),
        ),
        'location' => array(array(array('param' => 'options_page', 'operator' => '==', 'value' => 'acf-options-theme-settings'))),
        'menu_order' => 0,
    ));

    /* ===== Contact & social (options) ===== */
    acf_add_local_field_group(array(
        'key'      => 'group_63beaa742849e',
        'title'    => 'راه‌های ارتباطی و شبکه‌های اجتماعی',
        'fields'   => array(
            kmpr_acf_tab('field_63bebd9535add', 'تلفن و ایمیل'),
            kmpr_acf('field_63bebda235ade', 'phone_number', 'شماره موبایل', 'text', array('instructions' => 'با فرمت 0935xxxxxxx', 'default_value' => '09358088118')),
            kmpr_acf('field_64f238cfbc79a', 'phone_number_label', 'شماره تلفن نمایشی', 'text', array('default_value' => '۰۹۳۵ ۸۰۸ ۸۱۱۸')),
            kmpr_acf('field_649e0128b5993', 'email', 'ایمیل', 'email'),
            kmpr_acf_tab('field_63beaa74698f8', 'شبکه‌های اجتماعی'),
            kmpr_acf('field_63beaaf3698fa', 'telegram', 'تلگرام', 'url', array('placeholder' => 'https://t.me/username')),
            kmpr_acf('field_63beaac8698f9', 'whatsapp', 'واتساپ', 'url', array('placeholder' => 'https://wa.me/98935xxxxxxx')),
            kmpr_acf('field_642ed830ac6e1', 'linkedIn', 'لینکدین', 'url'),
            kmpr_acf('field_64f24ff922191', 'github', 'گیت‌هاب', 'url'),
            kmpr_acf('field_63beab15698fc', 'instagram', 'اینستاگرام', 'url'),
            kmpr_acf('field_642ed850ac6e2', 'twitter', 'ایکس (توییتر)', 'url'),
            kmpr_acf('field_642ed819ac6e0', 'youtube', 'یوتیوب', 'url'),
            kmpr_acf('field_63beab06698fb', 'aparat', 'آپارات', 'url'),
            kmpr_acf_tab('field_63bebc4e1c351', 'آدرس'),
            kmpr_acf('field_63bebc6e1c352', 'address', 'آدرس / شهر', 'textarea', array('rows' => 2)),
            kmpr_acf('field_645d4be2938fb', 'map-link', 'لینک نقشه', 'url'),
        ),
        'location' => array(array(array('param' => 'options_page', 'operator' => '==', 'value' => 'acf-options-contact-and-social'))),
    ));

    /* ===== Home page ===== */
    $step_fields = array(
        kmpr_acf('field_kmpr_step_t', 't', 'عنوان'),
        kmpr_acf('field_kmpr_step_d', 'd', 'توضیح', 'textarea', array('rows' => 2)),
        kmpr_acf('field_kmpr_step_m', 'm', 'برچسب زمان', 'text', array('placeholder' => 'مثلاً: روز ۱')),
    );
    acf_add_local_field_group(array(
        'key'      => 'group_63c2710475bbb',
        'title'    => 'محتوای صفحه اصلی',
        'fields'   => array(
            kmpr_acf_tab('field_66be3db54ab95', 'هیرو'),
            kmpr_acf('field_66be3dbc4ab96', 'hero-image', 'عکس پرتره', 'image', array('return_format' => 'id', 'preview_size' => 'medium')),
            kmpr_acf('field_kmpr_hero_cutout', 'hero_cutout', 'عکس پرتره بدون پس‌زمینه (PNG/WebP شفاف)', 'image', array('return_format' => 'id', 'preview_size' => 'medium', 'instructions' => 'برای افکت سه‌بعدی: عکس بریده‌شده با پس‌زمینه شفاف. خالی = نسخه آماده قالب.')),
            kmpr_acf('field_kmpr_hero_plate', 'hero_plate', 'پس‌زمینه محو پشت پرتره', 'image', array('return_format' => 'id', 'preview_size' => 'thumbnail', 'instructions' => 'اختیاری؛ نسخه تار و تیره‌شده عکس اصلی.')),
            kmpr_acf('field_kmpr_hero_badge', 'hero_badge', 'متن نشان بالای تیتر', 'text', array('default_value' => 'آماده پذیرش پروژه جدید')),
            kmpr_acf('field_66be3dd44ab97', 'hero-slogan', 'خط اول تیتر (نام و عنوان)', 'text', array('default_value' => 'کوروش محمدپور · طراح و توسعه‌دهنده وب')),
            kmpr_acf('field_kmpr_hero_headline', 'hero_headline', 'تیتر اصلی', 'text', array('default_value' => 'وب‌سایت‌هایی می‌سازم که')),
            kmpr_acf('field_kmpr_hero_rotating', 'hero_rotating', 'کلمات چرخشی (هر خط یک مورد)', 'textarea', array('rows' => 3, 'default_value' => "سریع‌تر لود می‌شوند\nمشتری می‌سازند\nدر گوگل دیده می‌شوند")),
            kmpr_acf('field_66be403257d47', 'hero-subtitle', 'توضیح زیر تیتر', 'textarea', array('rows' => 3, 'default_value' => 'طراحی اختصاصی، کدنویسی تمیز و سرعتی که کاربر و گوگل حسش می‌کنند. از ایده تا تحویل، مستقیم با خودم کار می‌کنید؛ بدون واسطه و بدون قالب‌های آماده سنگین.')),
            kmpr_acf('field_kmpr_hero_btn1', 'hero_primary_btn', 'دکمه اصلی', 'link', array('instructions' => 'خالی = فرم ثبت درخواست')),
            kmpr_acf('field_kmpr_hero_btn2', 'hero_secondary_btn', 'دکمه دوم', 'link', array('instructions' => 'خالی = آرشیو نمونه‌کارها')),
            kmpr_acf('field_kmpr_hero_stack', 'hero_stack', 'تکنولوژی‌های مدار دور عکس (هر خط یک مورد)', 'textarea', array('rows' => 3, 'default_value' => "Next.js\nReact\nWordPress\nTypeScript\nTailwind\nNode.js")),
            kmpr_acf('field_kmpr_hero_card', 'hero_card_subtitle', 'زیرعنوان کارت پرتره', 'text', array('default_value' => 'Web Developer · UI Engineer')),

            kmpr_acf_tab('field_kmpr_tab_marquee', 'نوار ابزارها'),
            kmpr_acf('field_kmpr_marquee_title', 'marquee_title', 'عنوان', 'text', array('default_value' => 'ابزارهایی که هر روز با آن‌ها می‌سازم')),
            kmpr_acf('field_kmpr_marquee_1', 'marquee_row_1', 'ردیف اول (هر خط یک مورد)', 'textarea', array('rows' => 4, 'default_value' => "Next.js\nReact\nTypeScript\nTailwind CSS\nAlpine.js\nVite\nFramer Motion\nSwiper\nFigma")),
            kmpr_acf('field_kmpr_marquee_2', 'marquee_row_2', 'ردیف دوم (هر خط یک مورد)', 'textarea', array('rows' => 4, 'default_value' => "WordPress\nPHP\nNode.js\nREST API\nGraphQL\nMySQL\nRedis\nDocker\nCore Web Vitals\nSchema.org")),

            kmpr_acf_tab('field_kmpr_tab_services', 'بخش خدمات'),
            kmpr_acf('field_kmpr_srv_kicker', 'services_kicker', 'برچسب', 'text', array('default_value' => 'خدمات')),
            kmpr_acf('field_kmpr_srv_title', 'services_title', 'تیتر', 'text', array('default_value' => 'از ایده تا یک محصول آنلاین که کار می‌کند')),
            kmpr_acf('field_kmpr_srv_text', 'services_text', 'توضیح', 'textarea', array('rows' => 2, 'default_value' => 'هر پروژه را مثل محصول خودم می‌سازم: طراحی دقیق، کد تمیز، سئوی فنی و پشتیبانی بعد از تحویل.')),
            kmpr_acf('field_kmpr_srv_items', 'services_items', 'خدمات نمایش‌داده‌شده', 'relationship', array('post_type' => array('services'), 'return_format' => 'id', 'max' => 5, 'instructions' => 'خالی = همه خدمات به ترتیب')),
            kmpr_acf('field_kmpr_srv_card_kicker', 'request_card_kicker', 'کارت درخواست: برچسب', 'text', array('default_value' => 'نمی‌دانید کدام را انتخاب کنید؟')),
            kmpr_acf('field_kmpr_srv_card_title', 'request_card_title', 'کارت درخواست: تیتر', 'text', array('default_value' => 'بریف پروژه را بفرستید؛ من مسیر را پیشنهاد می‌دهم.')),
            kmpr_acf('field_kmpr_srv_card_btn', 'request_card_btn', 'کارت درخواست: متن دکمه', 'text', array('default_value' => 'ثبت درخواست رایگان')),

            kmpr_acf_tab('field_kmpr_tab_work', 'نمونه‌کارهای منتخب'),
            kmpr_acf('field_kmpr_work_kicker', 'work_kicker', 'برچسب', 'text', array('default_value' => 'نمونه‌کارهای منتخب')),
            kmpr_acf('field_kmpr_work_title', 'work_title', 'تیتر', 'text', array('default_value' => 'پروژه‌هایی که با افتخار ساخته‌ام')),
            kmpr_acf('field_kmpr_work_items', 'work_items', 'پروژه‌های منتخب (حداکثر ۴)', 'relationship', array('post_type' => array('portfolio'), 'return_format' => 'id', 'max' => 4, 'filters' => array('search', 'taxonomy'), 'instructions' => 'خالی = آخرین پروژه‌هایی که اسکرین‌شات دارند')),

            kmpr_acf_tab('field_kmpr_tab_process', 'روند همکاری'),
            kmpr_acf('field_kmpr_proc_kicker', 'process_kicker', 'برچسب', 'text', array('default_value' => 'روند همکاری')),
            kmpr_acf('field_kmpr_proc_title', 'process_title', 'تیتر', 'text', array('default_value' => 'شفاف، مرحله‌به‌مرحله و بدون غافلگیری')),
            kmpr_acf('field_kmpr_proc_text', 'process_text', 'توضیح', 'textarea', array('rows' => 2, 'default_value' => 'در تمام مسیر می‌دانید پروژه کجاست. ارتباط مستقیم با خودم است، نه با یک واسطه.')),
            kmpr_acf('field_kmpr_proc_steps', 'process_steps', 'مراحل', 'repeater', array('layout' => 'block', 'button_label' => 'افزودن مرحله', 'sub_fields' => $step_fields)),

            kmpr_acf_tab('field_66b9090e5398c', 'درباره من'),
            kmpr_acf('field_66b9092d5398e', 'about-title', 'برچسب بخش', 'text', array('default_value' => 'درباره من')),
            kmpr_acf('field_66b909465398f', 'about-description', 'متن بیانیه', 'textarea', array('rows' => 4, 'instructions' => 'کلمات داخل *ستاره* با رنگ برند و داخل **دو ستاره** سفید نمایش داده می‌شوند. {years} = سال‌های تجربه.', 'default_value' => '**من کوروش هستم؛** بیش از **{years} سال** است که وب می‌سازم. باور دارم یک سایت خوب باید *سریع*، *قابل اعتماد* و *ساده برای مدیریت* باشد؛ نه فقط زیبا.')),

            kmpr_acf_tab('field_kmpr_tab_articles', 'مقالات'),
            kmpr_acf('field_kmpr_art_kicker', 'articles_kicker', 'برچسب', 'text', array('default_value' => 'نوشته‌ها')),
            kmpr_acf('field_kmpr_art_title', 'articles_title', 'تیتر', 'text', array('default_value' => 'آنچه از ساختن یاد گرفته‌ام')),

            kmpr_acf_tab('field_kmpr_tab_home_games', 'بازی‌ها'),
            kmpr_acf('field_kmpr_home_games_hide', 'home_games_hide', 'مخفی کردن بخش بازی‌ها', 'true_false', array('ui' => 1)),
            kmpr_acf('field_kmpr_home_games_kicker', 'home_games_kicker', 'برچسب', 'text', array('default_value' => 'گیم‌سنتر')),
            kmpr_acf('field_kmpr_home_games_title', 'home_games_title', 'تیتر', 'text', array('default_value' => 'یک استراحت کوتاه؟ بازی‌هایی که خودم کد زده‌ام')),
            kmpr_acf('field_kmpr_home_games_text', 'home_games_text', 'توضیح', 'textarea', array('rows' => 2, 'default_value' => 'از شوتر فضایی و رانر پیکسلی تا شطرنج و ایکس‌او آنلاین با دوستان؛ همه با جاوااسکریپت خالص، روان روی موبایل و با جدول برترین‌ها.')),
            kmpr_acf('field_kmpr_home_games_btn', 'home_games_btn', 'متن دکمه', 'text', array('default_value' => 'ورود به گیم‌سنتر')),
            kmpr_acf('field_kmpr_home_games_items', 'home_games_items', 'بازی‌های نمایش‌داده‌شده (حداکثر ۴)', 'relationship', array('post_type' => array('game'), 'return_format' => 'id', 'max' => 4, 'instructions' => 'خالی = بازی ویژه + جدیدترین‌ها')),

            kmpr_acf_tab('field_66b90407e2665', 'دعوت به همکاری (CTA)'),
            kmpr_acf('field_kmpr_cta_badge', 'cta_badge', 'نشان', 'text', array('default_value' => 'ظرفیت محدود برای پروژه‌های جدید')),
            kmpr_acf('field_kmpr_cta_title', 'cta_heading', 'تیتر', 'text', array('default_value' => 'پروژه بعدی‌تان را با هم بسازیم')),
            kmpr_acf('field_kmpr_cta_text', 'cta_text', 'توضیح', 'textarea', array('rows' => 2, 'default_value' => 'بریف کوتاهی بفرستید؛ معمولاً در چند ساعت کاری پاسخ می‌دهم و یک جلسه رایگان برای بررسی پروژه هماهنگ می‌کنیم.')),
            kmpr_acf('field_kmpr_cta_btn', 'cta_btn_text', 'متن دکمه دایره‌ای', 'text', array('default_value' => 'ثبت درخواست')),
        ),
        'location' => array(array(array('param' => 'page_type', 'operator' => '==', 'value' => 'front_page'))),
        'hide_on_screen' => array('the_content'),
    ));

    /* ===== Services ===== */
    acf_add_local_field_group(array(
        'key'      => 'group_649de6dd5e9e7',
        'title'    => 'اطلاعات خدمت',
        'fields'   => array(
            kmpr_acf_tab('field_649de751c3a97', 'کارت و هدر'),
            kmpr_acf('field_66be267180654', 'service-icon', 'آیکون (کد SVG)', 'textarea', array('rows' => 3, 'instructions' => 'کد SVG با stroke="currentColor"')),
            kmpr_acf('field_649de6de08251', 'services_badge', 'برچسب', 'text'),
            kmpr_acf('field_649de71f20814', 'services_description', 'توضیح کوتاه (کارت و هدر)', 'textarea', array('rows' => 2)),
            kmpr_acf('field_kmpr_srv_price', 'starting_price', 'شروع قیمت', 'text', array('placeholder' => 'مثلاً: از ۳۰ میلیون تومان')),
            kmpr_acf('field_kmpr_srv_duration', 'duration', 'زمان تقریبی تحویل', 'text', array('placeholder' => 'مثلاً: ۳ تا ۶ هفته')),
            kmpr_acf('field_kmpr_srv_type', 'request_type', 'نوع پروژه در فرم درخواست', 'select', array('choices' => array('' => '—', 'website' => 'وب‌سایت', 'ecommerce' => 'فروشگاه', 'web_app' => 'وب‌اپلیکیشن', 'redesign' => 'بازطراحی', 'seo' => 'سئو', 'consult' => 'مشاوره'), 'instructions' => 'فرم انتهای صفحه با این نوع پیش‌انتخاب می‌شود.')),

            kmpr_acf_tab('field_kmpr_tab_srv_deliver', 'چه چیزی دریافت می‌کنید'),
            kmpr_acf('field_kmpr_srv_deliverables', 'deliverables', 'خروجی‌ها و امکانات', 'repeater', array('layout' => 'table', 'button_label' => 'افزودن مورد', 'sub_fields' => array(
                kmpr_acf('field_kmpr_srv_del_t', 'title', 'عنوان'),
                kmpr_acf('field_kmpr_srv_del_d', 'desc', 'توضیح', 'textarea', array('rows' => 2)),
            ))),
            kmpr_acf('field_kmpr_srv_audience', 'audience', 'مناسب چه کسانی است؟ (هر خط یک مورد)', 'textarea', array('rows' => 4)),

            kmpr_acf_tab('field_kmpr_tab_srv_steps', 'مراحل اجرا'),
            kmpr_acf('field_kmpr_srv_steps', 'service_steps', 'مراحل', 'repeater', array('layout' => 'table', 'button_label' => 'افزودن مرحله', 'sub_fields' => array(
                kmpr_acf('field_kmpr_srv_st_t', 't', 'عنوان'),
                kmpr_acf('field_kmpr_srv_st_d', 'd', 'توضیح', 'textarea', array('rows' => 2)),
            ))),

            kmpr_acf_tab('field_6505d06723bca', 'تعرفه‌ها'),
            kmpr_acf('field_6505d2bd42980', 'Proposal_switch', 'نمایش تعرفه‌ها', 'radio', array('choices' => array('on' => 'روشن', 'off' => 'خاموش'), 'default_value' => 'off', 'layout' => 'horizontal')),
            kmpr_acf('field_6505d0f33bf48', 'proposal', 'پلن‌ها', 'repeater', array(
                'layout' => 'block', 'button_label' => 'افزودن پلن',
                'conditional_logic' => array(array(array('field' => 'field_6505d2bd42980', 'operator' => '==', 'value' => 'on'))),
                'sub_fields' => array(
                    kmpr_acf('field_6505d16c3bf4a', 'proposal_offer', 'پیشنهاد ویژه', 'radio', array('choices' => array('on' => 'بله', 'off' => 'خیر'), 'default_value' => 'off', 'layout' => 'horizontal')),
                    kmpr_acf('field_6505d5ffd4793', 'proposal_title', 'نام پلن'),
                    kmpr_acf('field_6505d628d4794', 'proposal_description', 'توضیح / قیمت'),
                    kmpr_acf('field_6505d12e3bf49', 'proposal_row', 'ویژگی‌ها', 'repeater', array('layout' => 'table', 'button_label' => 'افزودن ویژگی', 'sub_fields' => array(
                        kmpr_acf('field_6505d1e63bf4b', 'Property_label', 'ویژگی'),
                        kmpr_acf('field_6505d2283bf4c', 'Property_value', 'مقدار'),
                    ))),
                ),
            )),

            kmpr_acf_tab('field_kmpr_tab_srv_faq', 'سوالات متداول'),
            kmpr_acf('field_kmpr_srv_faq', 'service_faq', 'سوالات', 'repeater', array('layout' => 'block', 'button_label' => 'افزودن سوال', 'sub_fields' => array(
                kmpr_acf('field_kmpr_srv_faq_q', 'question', 'سوال'),
                kmpr_acf('field_kmpr_srv_faq_a', 'answer', 'پاسخ', 'textarea', array('rows' => 3)),
            ))),
        ),
        'location' => array(array(array('param' => 'post_type', 'operator' => '==', 'value' => 'services'))),
    ));

    /* ===== Portfolio ===== */
    acf_add_local_field_group(array(
        'key'      => 'group_644e9b3b64240',
        'title'    => 'اطلاعات نمونه‌کار',
        'fields'   => array(
            kmpr_acf_tab('field_645e166523112', 'مشتری'),
            kmpr_acf('field_kmpr_pf_client', 'client_name', 'نام مشتری / برند'),
            kmpr_acf('field_645e100dd0240', 'brand-logo', 'لوگوی مشتری', 'image', array('return_format' => 'array', 'preview_size' => 'thumbnail')),
            kmpr_acf('field_649e69c54e858', 'logo_background', 'رنگ زمینه لوگو', 'select', array('choices' => array('bg-primary' => 'تیره (سرمه‌ای)', 'bg-black' => 'مشکی', 'bg-white' => 'سفید', 'bg-slate-900' => 'تیره عمیق', 'bg-secondary' => 'نارنجی برند'), 'default_value' => 'bg-primary')),
            kmpr_acf('field_645e28ccfe245', 'portfolio-website', 'آدرس وب‌سایت', 'link'),
            kmpr_acf('field_kmpr_pf_archived', 'site_archived', 'سایت دیگر آنلاین نیست (آرشیو)', 'true_false', array('ui' => 1, 'instructions' => 'روشن باشد: لینک خروجی به سایت نمایش داده نمی‌شود و پروژه با برچسب «آرشیو» نشان داده می‌شود.')),
            kmpr_acf('field_645e15dcc4438', 'social-media', 'شبکه‌های اجتماعی مشتری', 'group', array('layout' => 'table', 'sub_fields' => array(
                kmpr_acf('field_645e1608c4439', 'portfolio-instagram', 'اینستاگرام', 'link'),
                kmpr_acf('field_645e16a91bc4f', 'portfolio-telegram', 'تلگرام', 'link'),
                kmpr_acf('field_645e16ceed7de', 'portfolio-whatsapp', 'واتساپ', 'link'),
                kmpr_acf('field_645e16e4ed7df', 'portfolio-aparat', 'آپارات', 'link'),
            ))),

            kmpr_acf_tab('field_kmpr_tab_pf_case', 'کیس‌استادی'),
            kmpr_acf('field_kmpr_pf_featured', 'is_featured', 'نمایش در صفحه اصلی', 'true_false', array('ui' => 1)),
            kmpr_acf('field_kmpr_pf_year', 'project_year', 'سال اجرا', 'text', array('placeholder' => '۱۴۰۴')),
            kmpr_acf('field_kmpr_pf_role', 'project_role', 'نقش من', 'text', array('placeholder' => 'طراحی UI، توسعه فرانت و بک‌اند')),
            kmpr_acf('field_kmpr_pf_duration', 'project_duration', 'مدت اجرا', 'text', array('placeholder' => '۶ هفته')),
            kmpr_acf('field_kmpr_pf_stack', 'tech_stack', 'تکنولوژی‌ها (هر خط یک مورد)', 'textarea', array('rows' => 3)),
            kmpr_acf('field_kmpr_pf_challenge', 'challenge', 'چالش / مسئله مشتری', 'wysiwyg', array('tabs' => 'visual', 'toolbar' => 'basic', 'media_upload' => 0)),
            kmpr_acf('field_kmpr_pf_solution', 'solution', 'راه‌حل من', 'wysiwyg', array('tabs' => 'visual', 'toolbar' => 'basic', 'media_upload' => 0)),
            kmpr_acf('field_kmpr_pf_results', 'results', 'نتایج قابل اندازه‌گیری', 'repeater', array('layout' => 'table', 'max' => 4, 'button_label' => 'افزودن نتیجه', 'instructions' => 'فقط اعداد واقعی وارد کنید؛ بخش در صورت خالی بودن نمایش داده نمی‌شود.', 'sub_fields' => array(
                kmpr_acf('field_kmpr_pf_res_v', 'value', 'عدد', 'text', array('placeholder' => '۹۸')),
                kmpr_acf('field_kmpr_pf_res_l', 'label', 'عنوان', 'text', array('placeholder' => 'امتیاز PageSpeed موبایل')),
            ))),

            kmpr_acf_tab('field_649dbd4014b2f', 'اطلاعات فنی'),
            kmpr_acf('field_649dbd5014b30', 'theme_type', 'نوع قالب', 'select', array('choices' => array('قالب اختصاصی' => 'قالب اختصاصی', 'قالب سفارشی' => 'قالب سفارشی', 'قالب اختصاصی (Zero-Bloat Architecture)' => 'قالب اختصاصی (Zero-Bloat)', 'Headless / Next.js' => 'Headless / Next.js', 'اپلیکیشن' => 'اپلیکیشن موبایل / وب‌اپ'), 'allow_null' => 1)),
            kmpr_acf('field_649dbdac14b31', 'page_builder', 'صفحه‌ساز', 'select', array('choices' => array('کدنویسی' => 'کدنویسی', 'کدنویسی تمیز (بدون قالب و افزونه آماده)' => 'کدنویسی تمیز (بدون قالب و افزونه آماده)', 'المنتور' => 'المنتور', 'ویژوال کامپوزر' => 'ویژوال کامپوزر', 'گوتنبرگ' => 'گوتنبرگ'), 'allow_null' => 1)),

            kmpr_acf_tab('field_64f36fa034316', 'اسکرین‌شات‌ها'),
            kmpr_acf('field_64f36fb834317', 'screenshot', 'اسکرین‌شات‌ها', 'repeater', array('layout' => 'table', 'button_label' => 'افزودن اسکرین‌شات', 'sub_fields' => array(
                kmpr_acf('field_64f36fe834318', 'screen-size', 'دستگاه', 'select', array('choices' => array('laptop' => 'دسکتاپ', 'tablet' => 'تبلت', 'mobile' => 'موبایل'), 'default_value' => 'laptop')),
                kmpr_acf('field_64f3702734319', 'screenshot-image', 'تصویر (تمام‌صفحه)', 'image', array('return_format' => 'array', 'preview_size' => 'medium')),
            ))),

            kmpr_acf_tab('field_66be25a68a8b0', 'خدمات مرتبط'),
            kmpr_acf('field_66be25bc8a8b1', 'relation-service', 'خدمات مرتبط', 'relationship', array('post_type' => array('services'), 'return_format' => 'object')),
        ),
        'location' => array(array(array('param' => 'post_type', 'operator' => '==', 'value' => 'portfolio'))),
    ));

    /* ===== Contact page ===== */
    acf_add_local_field_group(array(
        'key'      => 'group_649d9bc3c34cb',
        'title'    => 'محتوای صفحه ارتباط با من',
        'fields'   => array(
            kmpr_acf('field_kmpr_ct_title', 'contact_hero_title', 'تیتر', 'text', array('default_value' => 'راه‌های ارتباط مستقیم با من')),
            kmpr_acf('field_kmpr_ct_text', 'contact_hero_text', 'توضیح', 'textarea', array('rows' => 2, 'default_value' => 'بدون تشریفات و واسطه؛ مستقیم با خودم در ارتباط باشید تا ایده‌تان را به یک وب‌سایت سریع و حرفه‌ای تبدیل کنیم.')),
            kmpr_acf('field_kmpr_ct_badge', 'contact_badge', 'نشان وضعیت', 'text', array('default_value' => 'هم‌اکنون آماده گفت‌وگو درباره پروژه شما')),
        ),
        'location' => array(array(array('param' => 'page_template', 'operator' => '==', 'value' => 'page-contact-us.php'))),
    ));

    /* ===== FAQ page ===== */
    acf_add_local_field_group(array(
        'key'      => 'group_64ea5d8105ece',
        'title'    => 'سوالات متداول',
        'fields'   => array(
            kmpr_acf('field_64ea5d818d084', 'faq', 'سوالات', 'repeater', array('layout' => 'block', 'button_label' => 'افزودن سوال', 'sub_fields' => array(
                kmpr_acf('field_64ea5daa8d085', 'question', 'سوال'),
                kmpr_acf('field_64ea5dcd8d086', 'answer', 'پاسخ', 'wysiwyg', array('tabs' => 'visual', 'toolbar' => 'basic', 'media_upload' => 0)),
            ))),
        ),
        'location' => array(array(array('param' => 'page_template', 'operator' => '==', 'value' => 'faq.php'))),
    ));

    /* ===== Menu item icon ===== */
    acf_add_local_field_group(array(
        'key'      => 'group_64ecd46c0bb52',
        'title'    => 'آیکون منو',
        'fields'   => array(kmpr_acf('field_64ecd46afc3e3', 'icon', 'آیکون (SVG)', 'textarea', array('rows' => 2))),
        'location' => array(array(array('param' => 'nav_menu_item', 'operator' => '==', 'value' => 'location/topHeaderMenuLocation'))),
    ));
}

/* -------------------------------------------------------------------------
 * One-time content migration: replace agency-era values with personal-brand copy.
 * Runs once per site (local + live) after deploy; only touches known legacy strings.
 * ---------------------------------------------------------------------- */
add_action('init', function () {
    if (get_option('kmpr_content_migration') === '2' || !function_exists('update_field')) {
        return;
    }
    $front = (int) get_option('page_on_front');
    if ($front) {
        $legacy = array(
            'hero-slogan'   => array('وبکو', 'خدمات دیجیتال'),
            'hero-subtitle' => array('با ما'),
        );
        foreach ($legacy as $name => $needles) {
            $value = (string) get_field($name, $front);
            foreach ($needles as $needle) {
                if ($value !== '' && mb_strpos($value, $needle) !== false) {
                    delete_field($name, $front); // falls back to the new default copy
                    break;
                }
            }
        }
    }
    update_option('kmpr_content_migration', '2', false);
}, 20);
