<?php
/**
 * Online Resume Manager & ACF Field Group
 *
 * Provides complete WordPress Dashboard management for the interactive
 * online resume (/resume/) under Theme Settings > Online Resume.
 *
 * @package KMPR
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register ACF Field Group for Resume Settings Options Page
 */
function kmpr_register_resume_field_group()
{
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }

    acf_add_local_field_group(array(
        'key'    => 'group_kmpr_resume_settings',
        'title'  => 'مدیریت محتوای رزومه آنلاین (/resume/)',
        'fields' => array(
            // Notice / Preview Link Tab
            array(
                'key'   => 'field_resume_tab_info',
                'label' => 'مشخصات و اطلاعات پایه',
                'name'  => '',
                'type'  => 'tab',
            ),
            array(
                'key'          => 'field_resume_notice_banner',
                'label'        => 'لینک دسترسی به رزومه آنلاین',
                'name'         => '',
                'type'         => 'message',
                'message'      => '<div style="background: #0f172a; color: #f8fafc; padding: 16px 20px; border-radius: 12px; border: 1px solid #334155;">
                    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
                        <div>
                            <strong style="color: #f59e0b; font-size: 15px; display: block; margin-bottom: 4px;">صفحه اختصاصی رزومه آنلاین فعال است:</strong>
                            <span style="font-family: monospace; color: #94a3b8; font-size: 13px;">' . esc_url(home_url('/resume/')) . '</span>
                        </div>
                        <a href="' . esc_url(home_url('/resume/')) . '" target="_blank" class="button button-primary" style="background: #d97706; border-color: #b45309; padding: 6px 16px; height: auto; font-weight: bold;">
                            مشاهده آنلاین رزومه &larr;
                        </a>
                    </div>
                    <p style="margin: 10px 0 0; color: #cbd5e1; font-size: 12px; line-height: 1.6;">
                        * تمامی فیلدهای زیر دارای مقادیر پیش‌فرض استاندارد استخراج‌شده از رزومه هستند. در صورتی که هر فیلدی را پر کنید، محتوای آن مستقیماً در صفحه آنلاین و نسخه چاپی جایگزین می‌شود.
                    </p>
                </div>',
                'new_lines'    => '',
                'esc_html'     => 0,
            ),
            array(
                'key'          => 'field_resume_photo',
                'label'        => 'تصویر پرسنلی رزومه',
                'name'         => 'resume_photo',
                'type'         => 'image',
                'instructions' => 'در صورت خالی بودن، تصویر اصلی تنظیم‌شده در صفحه اصلی استفاده می‌شود.',
                'return_format'=> 'array',
                'preview_size' => 'medium',
                'library'      => 'all',
            ),
            array(
                'key'          => 'field_resume_full_name',
                'label'        => 'نام و نام خانوادگی',
                'name'         => 'resume_full_name',
                'type'         => 'text',
                'default_value'=> 'کوروش محمدپور',
            ),
            array(
                'key'          => 'field_resume_job_title',
                'label'        => 'عنوان شغلی اصلی',
                'name'         => 'resume_job_title',
                'type'         => 'text',
                'default_value'=> 'توسعه‌دهنده ارشد وب و معمار نرم‌افزار (Full-Stack Developer)',
            ),
            array(
                'key'          => 'field_resume_location',
                'label'        => 'موقعیت مکانی',
                'name'         => 'resume_location',
                'type'         => 'text',
                'default_value'=> 'تهران، نارمک',
            ),
            array(
                'key'          => 'field_resume_pdf_file',
                'label'        => 'فایل دانلودی PDF رزومه (اختیاری)',
                'name'         => 'resume_pdf_file',
                'type'         => 'file',
                'instructions' => 'در صورت آپلود، دکمه دانلود PDF مستقیماً این فایل را دانلود خواهد کرد؛ در غیر این صورت از خروجی چاپی پیشرفته مرورگر استفاده می‌شود.',
                'return_format'=> 'array',
            ),

            // Tab 2: Summary
            array(
                'key'   => 'field_resume_tab_summary',
                'label' => 'خلاصه حرفه‌ای',
                'name'  => '',
                'type'  => 'tab',
            ),
            array(
                'key'          => 'field_resume_summary',
                'label'        => 'متن خلاصه حرفه‌ای و سوابق',
                'name'         => 'resume_summary',
                'type'         => 'textarea',
                'rows'         => 6,
                'default_value'=> 'توسعه‌دهنده فول‌استک و طراح سایت با سابقه گسترده در طراحی و توسعه صفر تا صد وب‌سایت‌های شرکتی و پلتفرم‌های فروشگاهی. متخصص در برنامه‌نویسی اختصاصی و بهینه‌سازی قالب‌های وردپرس (PHP) و مسلط به ساختارهای المنتور و سایر صفحه‌سازها. دارای تجربه عملی در توسعه فرانت‌اند مدرن با استفاده از React و Next.js جهت خلق تجربه‌های کاربری روان و سریع. متعهد به پیاده‌سازی دقیق اصول سئو (SEO)، ارتقای عملکرد سایت و ارائه راهکارهای پایدار فنی.',
            ),

            // Tab 3: Skills
            array(
                'key'   => 'field_resume_tab_skills',
                'label' => 'مهارت‌های تخصصی',
                'name'  => '',
                'type'  => 'tab',
            ),
            array(
                'key'          => 'field_resume_skills_frontend',
                'label'        => 'توسعه فرانت‌اند (Frontend)',
                'name'         => 'resume_skills_frontend',
                'type'         => 'textarea',
                'rows'         => 3,
                'default_value'=> 'React JS, Next.js, JavaScript (ESNext), Tailwind CSS v4, Alpine.js, Bootstrap, Responsive Design',
            ),
            array(
                'key'          => 'field_resume_skills_backend',
                'label'        => 'بک‌اند و سیستم‌های محتوا (Backend & CMS)',
                'name'         => 'resume_skills_backend',
                'type'         => 'textarea',
                'rows'         => 3,
                'default_value'=> 'WordPress (Custom Theme & Plugin Development), PHP, Laravel, REST API, MySQL Optimization',
            ),
            array(
                'key'          => 'field_resume_skills_tools',
                'label'        => 'ابزارها و استانداردهای کلیدی',
                'name'         => 'resume_skills_tools',
                'type'         => 'textarea',
                'rows'         => 3,
                'default_value'=> 'SEO & Performance (Core Web Vitals), Git / GitHub, Elementor, WooCommerce, AI & llms.txt',
            ),

            // Tab 4: Work Experiences
            array(
                'key'   => 'field_resume_tab_experience',
                'label' => 'سوابق شغلی',
                'name'  => '',
                'type'  => 'tab',
            ),
            array(
                'key'          => 'field_resume_experiences',
                'label'        => 'لیست سوابق شغلی و مسئولیت‌ها',
                'name'         => 'resume_experiences',
                'type'         => 'repeater',
                'instructions' => 'در صورت خالی بودن این لیست، ۵ تجربه ثبت‌شده در فایل رزومه رسمی به صورت خودکار نمایش داده می‌شود.',
                'button_label' => 'افزودن موقعیت شغلی جدید',
                'layout'       => 'block',
                'sub_fields'   => array(
                    array(
                        'key'   => 'field_exp_title',
                        'label' => 'سمت شغلی',
                        'name'  => 'job_title',
                        'type'  => 'text',
                        'wrapper'=> array('width' => '40'),
                    ),
                    array(
                        'key'   => 'field_exp_company',
                        'label' => 'شرکت / سازمان',
                        'name'  => 'company',
                        'type'  => 'text',
                        'wrapper'=> array('width' => '35'),
                    ),
                    array(
                        'key'   => 'field_exp_period',
                        'label' => 'بازه زمانی',
                        'name'  => 'period',
                        'type'  => 'text',
                        'wrapper'=> array('width' => '25'),
                    ),
                    array(
                        'key'   => 'field_exp_desc',
                        'label' => 'توضیحات و دستاوردها',
                        'name'  => 'description',
                        'type'  => 'wysiwyg',
                        'media_upload' => 0,
                        'toolbar'      => 'basic',
                    ),
                ),
            ),

            // Tab 5: Education
            array(
                'key'   => 'field_resume_tab_edu',
                'label' => 'تحصیلات و دوره‌ها',
                'name'  => '',
                'type'  => 'tab',
            ),
            array(
                'key'          => 'field_resume_education',
                'label'        => 'مدارک و دوره‌های تخصصی',
                'name'         => 'resume_education',
                'type'         => 'repeater',
                'button_label' => 'افزودن مدرک / دوره',
                'layout'       => 'table',
                'sub_fields'   => array(
                    array(
                        'key'   => 'field_edu_period',
                        'label' => 'بازه زمانی / تاریخ',
                        'name'  => 'period',
                        'type'  => 'text',
                        'wrapper'=> array('width' => '25'),
                    ),
                    array(
                        'key'   => 'field_edu_degree',
                        'label' => 'عنوان مدرک یا دوره',
                        'name'  => 'degree',
                        'type'  => 'text',
                        'wrapper'=> array('width' => '45'),
                    ),
                    array(
                        'key'   => 'field_edu_inst',
                        'label' => 'دانشگاه / موسسه',
                        'name'  => 'institution',
                        'type'  => 'text',
                        'wrapper'=> array('width' => '30'),
                    ),
                ),
            ),
        ),
        'location' => array(
            array(
                array(
                    'param'    => 'options_page',
                    'operator' => '==',
                    'value'    => 'acf-options-resume-settings',
                ),
            ),
        ),
        'menu_order' => 10,
        'position'   => 'normal',
        'style'      => 'default',
    ));
}
add_action('acf/init', 'kmpr_register_resume_field_group');

