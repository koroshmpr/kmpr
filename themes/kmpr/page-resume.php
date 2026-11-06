<?php
/**
 * Template Name: Resume / رزومه آنلاین
 * Personal Brand: Korosh Mpr
 * 
 * High-performance, responsive, interactive and print-ready online resume.
 * Features automatic live portfolio integration and ACF dashboard support.
 */

get_header(); 

$korosh_photo = get_template_directory_uri() . '/public/images/korosh-mpr.webp';

// Dynamic ACF fields with complete PDF fallbacks
$summary = get_field('resume_summary', 'option') ?: 'توسعه‌دهنده فول‌استک و طراح سایت با سابقه گسترده در طراحی و توسعه صفر تا صد وب‌سایت‌های شرکتی و پلتفرم‌های فروشگاهی. متخصص در برنامه‌نویسی اختصاصی و بهینه‌سازی قالب‌های وردپرس (PHP) و مسلط به ساختارهای المنتور و سایر صفحه‌سازها. دارای تجربه عملی در توسعه فرانت‌اند مدرن با استفاده از React و Next.js جهت خلق تجربه‌های کاربری روان و سریع. متعهد به پیاده‌سازی دقیق اصول سئو (SEO)، ارتقای عملکرد سایت و ارائه راهکارهای پایدار فنی.';

$phone    = get_field('phone', 'option') ?: '09358088118';
$email    = get_field('email', 'option') ?: 'korosh.mpr@gmail.com';
$github   = get_field('github', 'option') ?: 'https://github.com/koroshmpr';
$linkedin = get_field('linkedin', 'option') ?: 'https://linkedin.com/in/korosh-mpr-475739205';
$location = 'تهران، نارمک';
$website  = 'kmpr.ir';
?>

<div class="resume-wrapper min-h-screen bg-slate-950 text-slate-100 py-10 lg:py-16 selection:bg-secondary/30 selection:text-secondary">
    <div class="container mx-auto px-4 sm:px-6 max-w-5xl">

        <!-- Top Navigation / Action Bar (Hidden on Print) -->
        <div class="no-print flex flex-wrap items-center justify-between gap-4 mb-8 pb-6 border-b border-white/10">
            <a href="<?= esc_url(home_url('/')); ?>" 
               class="inline-flex items-center gap-2 text-xs sm:text-sm font-bold text-slate-400 hover:text-white transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="rotate-180" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8z"/>
                </svg>
                <span>بازگشت به صفحه اصلی</span>
            </a>

            <!-- Action Buttons: Print PDF & Direct Chat -->
            <div class="flex items-center gap-3 flex-wrap">
                <button type="button" 
                        onclick="window.print()" 
                        class="inline-flex items-center gap-2 bg-secondary hover:bg-secondary-hover text-white text-xs sm:text-sm font-black px-5 py-2.5 rounded-2xl transition-all shadow-lg shadow-secondary/25 hover:scale-105 active:scale-95 cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>دانلود رزومه (PDF / Print)</span>
                </button>

                <a href="https://wa.me/989358088118?text=<?= urlencode('سلام کوروش عزیز، رزومه شما را در سایت بررسی کردم.'); ?>"
                   target="_blank"
                   class="inline-flex items-center gap-2 bg-emerald-500/15 hover:bg-emerald-500/25 text-emerald-400 border border-emerald-500/30 text-xs sm:text-sm font-bold px-4 py-2.5 rounded-2xl transition-all">
                    <span>چت واتساپ</span>
                </a>

                <a href="https://t.me/korosh_mpr"
                   target="_blank"
                   class="inline-flex items-center gap-2 bg-sky-500/15 hover:bg-sky-500/25 text-sky-400 border border-sky-500/30 text-xs sm:text-sm font-bold px-4 py-2.5 rounded-2xl transition-all">
                    <span>تلگرام</span>
                </a>
            </div>
        </div>

        <!-- Printable Resume Document Container -->
        <div class="resume-sheet bg-slate-900/90 border border-white/10 rounded-3xl p-6 sm:p-10 lg:p-12 shadow-2xl backdrop-blur-xl relative overflow-hidden">
            
            <!-- Ambient Top-End Glow (Hidden on Print) -->
            <div class="no-print absolute -top-20 -end-20 w-80 h-80 bg-secondary/15 rounded-full blur-[100px] pointer-events-none"></div>

            <!-- Header: Profile & Contact Grid -->
            <header class="flex flex-col md:flex-row items-center md:items-start justify-between gap-6 pb-8 border-b border-white/10 text-center md:text-start">
                <div class="flex flex-col sm:flex-row items-center md:items-start gap-6">
                    <!-- Avatar / Portrait -->
                    <div class="relative shrink-0">
                        <div class="w-24 h-24 sm:w-28 sm:h-28 rounded-2xl overflow-hidden border-2 border-secondary/40 shadow-xl bg-slate-950">
                            <img class="w-full h-full object-cover object-top filter grayscale contrast-110" 
                                 src="<?= esc_url($korosh_photo); ?>" 
                                 alt="کوروش محمدپور">
                        </div>
                        <span class="no-print absolute -bottom-1 -end-1 w-4 h-4 rounded-full bg-emerald-400 border-2 border-slate-900 shadow" title="آنلاین و در دسترس"></span>
                    </div>

                    <!-- Names & Title -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-center md:justify-start gap-3 flex-wrap">
                            <h1 class="text-2xl sm:text-3xl lg:text-4xl font-black text-white tracking-tight">کوروش محمدپور</h1>
                            <span class="no-print text-xs font-mono px-3 py-1 rounded-full bg-secondary/15 text-secondary border border-secondary/30 font-bold">Korosh Mpr</span>
                        </div>
                        <p class="text-sm sm:text-base font-bold text-slate-300">
                            توسعه‌دهنده فول‌استک | طراح سایت <span class="text-secondary font-mono">(WordPress / React / Next.js)</span>
                        </p>
                        <p class="text-xs text-slate-400 font-medium">
                            متخصص معماری کدهای اختصاصی، پرفورمنس ۱۰۰/۱۰۰ و سئو تکنیکال
                        </p>
                    </div>
                </div>

                <!-- Contact Details Card -->
                <div class="flex flex-col gap-2.5 text-xs text-slate-300 font-mono text-start w-full md:w-auto bg-white/[0.02] md:bg-transparent p-4 md:p-0 rounded-2xl border border-white/5 md:border-0">
                    <div class="flex items-center gap-2">
                        <svg class="size-3.5 text-secondary shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/>
                            <circle cx="12" cy="10" r="3"/>
                        </svg>
                        <span class="text-secondary font-sans font-bold">موقعیت:</span>
                        <span class="font-sans"><?= esc_html($location); ?></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="size-3.5 text-secondary shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                        </svg>
                        <span class="text-secondary font-sans font-bold">تماس:</span>
                        <a href="tel:<?= esc_attr($phone); ?>" class="hover:text-secondary dir-ltr transition-colors"><?= esc_html($phone); ?></a>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="size-3.5 text-secondary shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect width="20" height="16" x="2" y="4" rx="2"/>
                            <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
                        </svg>
                        <span class="text-secondary font-sans font-bold">ایمیل:</span>
                        <a href="mailto:<?= esc_attr($email); ?>" class="hover:text-secondary dir-ltr transition-colors"><?= esc_html($email); ?></a>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="size-3.5 text-secondary shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/>
                            <path d="M2 12h20"/>
                        </svg>
                        <span class="text-secondary font-sans font-bold">وب‌سایت:</span>
                        <a href="<?= esc_url(home_url('/')); ?>" class="hover:text-secondary dir-ltr transition-colors"><?= esc_html($website); ?></a>
                    </div>
                    <div class="flex items-center gap-3 pt-1 text-slate-400">
                        <a href="<?= esc_url($github); ?>" target="_blank" class="hover:text-white underline">GitHub</a>
                        <span>•</span>
                        <a href="<?= esc_url($linkedin); ?>" target="_blank" class="hover:text-white underline">LinkedIn</a>
                    </div>
                </div>
            </header>

            <!-- Section 1: Professional Summary -->
            <section class="py-8 border-b border-white/10 text-start">
                <div class="flex items-center gap-2.5 mb-4">
                    <span class="size-6 rounded-lg bg-secondary/15 text-secondary flex items-center justify-center">
                        <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/>
                        </svg>
                    </span>
                    <h2 class="text-lg sm:text-xl font-black text-white">خلاصه حرفه‌ای</h2>
                </div>
                <p class="text-xs sm:text-sm text-slate-300 leading-loose font-medium text-justify">
                    <?= wp_kses_post($summary); ?>
                </p>
            </section>

            <!-- Section 2: Technical Skills Grid -->
            <section class="py-8 border-b border-white/10 text-start">
                <div class="flex items-center gap-2.5 mb-6">
                    <span class="size-6 rounded-lg bg-secondary/15 text-secondary flex items-center justify-center">
                        <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
                        </svg>
                    </span>
                    <h2 class="text-lg sm:text-xl font-black text-white">مهارت‌های تخصصی</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Skill Box 1 -->
                    <div class="p-4 rounded-2xl bg-white/[0.02] border border-white/10 space-y-2">
                        <div class="flex items-center gap-2 text-secondary font-black text-xs sm:text-sm">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect width="20" height="14" x="2" y="3" rx="2"/>
                                <line x1="8" x2="16" y1="21" y2="21"/>
                                <line x1="12" x2="12" y1="17" y2="21"/>
                            </svg>
                            <span>توسعه فرانت‌اند (Frontend)</span>
                        </div>
                        <p class="text-xs text-slate-300 leading-relaxed font-mono">
                            React JS, Next.js, JavaScript (ESNext), Tailwind CSS v4, Alpine.js, Bootstrap, Responsive Design
                        </p>
                    </div>

                    <!-- Skill Box 2 -->
                    <div class="p-4 rounded-2xl bg-white/[0.02] border border-white/10 space-y-2">
                        <div class="flex items-center gap-2 text-secondary font-black text-xs sm:text-sm">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="3"/>
                                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                            </svg>
                            <span>بک‌اند و سیستم‌های محتوا</span>
                        </div>
                        <p class="text-xs text-slate-300 leading-relaxed font-mono">
                            WordPress (Custom Theme & Plugin Development), PHP, Laravel, REST API, MySQL Optimization
                        </p>
                    </div>

                    <!-- Skill Box 3 -->
                    <div class="p-4 rounded-2xl bg-white/[0.02] border border-white/10 space-y-2">
                        <div class="flex items-center gap-2 text-secondary font-black text-xs sm:text-sm">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
                            </svg>
                            <span>ابزارها و مفاهیم کلیدی</span>
                        </div>
                        <p class="text-xs text-slate-300 leading-relaxed font-mono">
                            SEO & Performance (Core Web Vitals), Git / GitHub, Elementor, WooCommerce, AI & llms.txt
                        </p>
                    </div>
                </div>
            </section>

            <!-- Section 3: Work Experience Timeline -->
            <section class="py-8 border-b border-white/10 text-start">
                <div class="flex items-center gap-2.5 mb-6">
                    <span class="size-6 rounded-lg bg-secondary/15 text-secondary flex items-center justify-center">
                        <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect width="20" height="14" x="2" y="7" rx="2" ry="2"/>
                            <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                        </svg>
                    </span>
                    <h2 class="text-lg sm:text-xl font-black text-white">سوابق شغلی و تجربیات کلیدی</h2>
                </div>

                <div class="space-y-6 relative border-s-2 border-white/10 ps-5 sm:ps-7 ms-2">
                    
                    <!-- Job 1 -->
                    <div class="relative group">
                        <span class="absolute -start-[27px] sm:-start-[35px] top-1.5 w-3.5 h-3.5 rounded-full bg-secondary border-2 border-slate-900 group-hover:scale-125 transition-transform"></span>
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1 mb-2">
                            <h3 class="text-sm sm:text-base font-black text-white">توسعه‌دهنده فول‌استک (Fullstack Developer)</h3>
                            <span class="text-xs font-mono text-secondary font-bold">آذر ۱۴۰۳ – تاکنون</span>
                        </div>
                        <div class="text-xs font-bold text-slate-400 mb-2">خانه سرمایه</div>
                        <ul class="list-disc list-inside space-y-1.5 text-xs text-slate-300 leading-relaxed">
                            <li>توسعه و بهینه‌سازی عملکردهای پلتفرم با استفاده از React.js و Next.js جهت بهبود سرعت رندرینگ و تجربه کاربری.</li>
                            <li>نگهداری، توسعه و سئوی زیرساخت‌های وردپرسی و پیاده‌سازی کدهای سفارشی PHP برای رفع نیازهای پیچیده سازمان.</li>
                        </ul>
                    </div>

                    <!-- Job 2 -->
                    <div class="relative group">
                        <span class="absolute -start-[27px] sm:-start-[35px] top-1.5 w-3.5 h-3.5 rounded-full bg-slate-600 border-2 border-slate-900 group-hover:scale-125 transition-transform"></span>
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1 mb-2">
                            <h3 class="text-sm sm:text-base font-black text-white">طراح و توسعه‌دهنده وب</h3>
                            <span class="text-xs font-mono text-slate-400">اسفند ۱۴۰۱ – آذر ۱۴۰۳</span>
                        </div>
                        <div class="text-xs font-bold text-slate-400 mb-2">آژانس دیجیتال مارکتینگ ماکان</div>
                        <ul class="list-disc list-inside space-y-1.5 text-xs text-slate-300 leading-relaxed">
                            <li>طراحی و پیاده‌سازی بیش از ده‌ها وب‌سایت شرکتی و فروشگاهی مدرن با استفاده از المنتور و کدنویسی قالب‌های اختصاصی.</li>
                            <li>توسعه توابع اختصاصی با PHP و مدیریت کوئری‌های وردپرس (WP_Query) برای نمایش داینامیک محصولات و محتوا.</li>
                            <li>استفاده از Tailwind CSS و Alpine.js در توسعه فرانت‌اند جهت کاهش چشمگیر حجم فایل‌ها و ارتقای سرعت سایت.</li>
                        </ul>
                    </div>

                    <!-- Job 3 -->
                    <div class="relative group">
                        <span class="absolute -start-[27px] sm:-start-[35px] top-1.5 w-3.5 h-3.5 rounded-full bg-slate-600 border-2 border-slate-900 group-hover:scale-125 transition-transform"></span>
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1 mb-2">
                            <h3 class="text-sm sm:text-base font-black text-white">طراح و توسعه‌دهنده وب</h3>
                            <span class="text-xs font-mono text-slate-400">خرداد ۱۴۰۰ – اسفند ۱۴۰۰</span>
                        </div>
                        <div class="text-xs font-bold text-slate-400 mb-2">آژانس دیجیتال مارکتینگ پندپلاس</div>
                        <ul class="list-disc list-inside space-y-1.5 text-xs text-slate-300 leading-relaxed">
                            <li>ایجاد قالب‌های سفارشی وردپرس متناسب با نیازهای دیجیتال مارکتینگ و بهینه‌سازی کامل سایت‌ها برای موتورهای جستجو (SEO).</li>
                        </ul>
                    </div>

                    <!-- Job 4 -->
                    <div class="relative group">
                        <span class="absolute -start-[27px] sm:-start-[35px] top-1.5 w-3.5 h-3.5 rounded-full bg-slate-600 border-2 border-slate-900 group-hover:scale-125 transition-transform"></span>
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1 mb-2">
                            <h3 class="text-sm sm:text-base font-black text-white">توسعه‌دهنده وب (Web Developer)</h3>
                            <span class="text-xs font-mono text-slate-400">تیر ۱۳۹۹ – خرداد ۱۴۰۰</span>
                        </div>
                        <div class="text-xs font-bold text-slate-400 mb-2">مرکز تحقیقاتی بیورزونانس</div>
                        <ul class="list-disc list-inside space-y-1.5 text-xs text-slate-300 leading-relaxed">
                            <li>پیاده‌سازی رابط کاربری و توسعه امکانات نرم‌افزاری تحت وب با تمرکز بر Responsive Design و استانداردهای روز.</li>
                        </ul>
                    </div>

                    <!-- Job 5 -->
                    <div class="relative group">
                        <span class="absolute -start-[27px] sm:-start-[35px] top-1.5 w-3.5 h-3.5 rounded-full bg-slate-600 border-2 border-slate-900 group-hover:scale-125 transition-transform"></span>
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1 mb-2">
                            <h3 class="text-sm sm:text-base font-black text-white">طراح سایت و کارشناس IT</h3>
                            <span class="text-xs font-mono text-slate-400">آذر ۱۳۹۲ – تیر ۱۳۹۹ (در دو مقطع)</span>
                        </div>
                        <div class="text-xs font-bold text-slate-400 mb-2">پارس سوییچ بورد</div>
                        <ul class="list-disc list-inside space-y-1.5 text-xs text-slate-300 leading-relaxed">
                            <li>مدیریت زیرساخت‌های IT سازمان و طراحی و پشتیبانی پلتفرم تحت وب شرکت.</li>
                        </ul>
                    </div>

                </div>
            </section>

            <!-- Section 4: Dynamic Automated Projects Integration -->
            <section class="py-8 border-b border-white/10 text-start">
                <div class="flex items-center justify-between mb-6 flex-wrap gap-2">
                    <div class="flex items-center gap-2.5">
                        <span class="size-6 rounded-lg bg-secondary/15 text-secondary flex items-center justify-center">
                            <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m12 15-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"/>
                                <path d="M9 12H4s.55-3.03 2-4c1.62-1.08 5 0 5 0"/>
                                <path d="M12 15v5s3.03-.55 4-2c1.08-1.62 0-5 0-5"/>
                            </svg>
                        </span>
                        <h2 class="text-lg sm:text-xl font-black text-white">پروژه‌های شاخص و اجرایی</h2>
                    </div>
                    <span class="text-xs font-mono text-slate-400">همگام‌سازی خودکار با دیتابیس پروژه‌ها</span>
                </div>

                <!-- Live Query of WordPress Portfolio Custom Post Type -->
                <?php
                $portfolio_query = new WP_Query(array(
                    'post_type'      => 'portfolio',
                    'post_status'    => 'publish',
                    'posts_per_page' => 6,
                    'order'          => 'DESC',
                ));
                if ($portfolio_query->have_posts()) :
                ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                        <?php while ($portfolio_query->have_posts()) : $portfolio_query->the_post(); 
                            $terms = wp_get_post_terms(get_the_ID(), 'portfolio_categories');
                            $term_name = !empty($terms) ? $terms[0]->name : 'پروژه';
                        ?>
                            <a href="<?php the_permalink(); ?>" 
                               class="group block p-3.5 rounded-2xl bg-white/[0.02] hover:bg-white/[0.06] border border-white/10 hover:border-secondary/40 transition-all">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-[10px] font-mono px-2 py-0.5 rounded-md bg-secondary/15 text-secondary border border-secondary/25">
                                        <?= esc_html($term_name); ?>
                                    </span>
                                    <span class="text-xs text-slate-400 group-hover:text-white transition-colors">&larr;</span>
                                </div>
                                <h4 class="text-xs sm:text-sm font-black text-white group-hover:text-secondary transition-colors truncate">
                                    <?= get_the_title(); ?>
                                </h4>
                                <p class="text-[11px] text-slate-400 line-clamp-1 mt-1 font-medium">
                                    <?= esc_html(wp_strip_all_tags(get_the_excerpt() ?: get_the_content())); ?>
                                </p>
                            </a>
                        <?php endwhile; wp_reset_postdata(); ?>
                    </div>
                <?php endif; ?>

                <!-- Landmark Project Domains Summary -->
                <div class="p-5 rounded-2xl bg-white/[0.02] border border-white/5 space-y-3 text-xs leading-relaxed text-slate-300">
                    <p><strong class="text-white">● مالی و سرمایه‌گذاری:</strong> خانه سرمایه، فینوست ۲۰۲۶، استعلام شبا خانه سرمایه، صرافی کوینکس</p>
                    <p><strong class="text-white">● سلامت و پزشکی:</strong> پزشکینو، رایان هلت، بیوتکنولب، آراسیما</p>
                    <p><strong class="text-white">● صنعتی و شرکتی:</strong> گروه صنعتی ایرا، باربری رادمان، گروه ریسمان، متاویژن، آگِر تریدینگ، هشتک، مدرسه تکاپو</p>
                    <p><strong class="text-white">● تبلیغات و رسانه:</strong> آژانس بلوباکس، کانون مات، هوگر، ارتباطات محیطی آرمانی، رای فیلم، آژانس پندپلاس، آژانس ماکان</p>
                    <p><strong class="text-white">● فروشگاهی و تولیدی:</strong> تولیدی نیل موتی، پارچه رادوینا، اوه آنلاین، رانی مانیا، روکارنو، دنون، اصالت، فروشگاه گیلدا، رجبی یدک، پنتا هوم، شارپ ایران</p>
                    <p><strong class="text-white">● کمپین‌ها:</strong> ماست سون کاله، واتا، ایکس ویژن، حکمرانی، قرعه‌کشی رانی، ولنتاین روژا، یلدا روژا</p>
                    <p><strong class="text-white">● وب‌اپلیکیشن و وب ۳:</strong> تترچند، ویچ گیم</p>
                </div>
            </section>

            <!-- Section 5: Education & Certifications -->
            <section class="pt-8 text-start">
                <div class="flex items-center gap-2.5 mb-6">
                    <span class="size-6 rounded-lg bg-secondary/15 text-secondary flex items-center justify-center">
                        <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
                            <path d="M6 12v5c3 3 9 3 12 0v-5"/>
                        </svg>
                    </span>
                    <h2 class="text-lg sm:text-xl font-black text-white">تحصیلات و دوره‌های تخصصی</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="p-4 rounded-2xl bg-white/[0.02] border border-white/10 space-y-1">
                        <span class="text-xs font-mono text-secondary font-bold">۱۳۸۹ – ۱۳۹۳</span>
                        <h4 class="text-xs sm:text-sm font-black text-white">کارشناسی مهندسی صنایع</h4>
                        <p class="text-[11px] text-slate-400">دانشگاه آزاد اسلامی واحد فیروزکوه</p>
                    </div>

                    <div class="p-4 rounded-2xl bg-white/[0.02] border border-white/10 space-y-1">
                        <span class="text-xs font-mono text-secondary font-bold">مهر ۱۴۰۰</span>
                        <h4 class="text-xs sm:text-sm font-black text-white">دوره تخصصی React.js</h4>
                        <p class="text-[11px] text-slate-400">مجتمع فنی تهران</p>
                    </div>

                    <div class="p-4 rounded-2xl bg-white/[0.02] border border-white/10 space-y-1">
                        <span class="text-xs font-mono text-secondary font-bold">اسفند ۱۳۹۸</span>
                        <h4 class="text-xs sm:text-sm font-black text-white">دوره تخصصی Web Design</h4>
                        <p class="text-[11px] text-slate-400">مجتمع فنی تهران</p>
                    </div>
                </div>
            </section>

        </div>

    </div>
</div>

<!-- Print Stylesheet for Flawless A4 PDF Export -->
<style>
@media print {
    /* Hide all global UI elements */
    header, footer, nav, .no-print, #header, .footer-panel, .fixed, .modal-search {
        display: none !important;
    }

    body, main, .resume-wrapper {
        background: #ffffff !important;
        color: #0f172a !important;
        padding: 0 !important;
        margin: 0 !important;
    }

    .resume-sheet {
        background: #ffffff !important;
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
        color: #0f172a !important;
    }

    h1, h2, h3, h4, strong {
        color: #0f172a !important;
    }

    p, li, span {
        color: #334155 !important;
    }

    .text-white {
        color: #0f172a !important;
    }

    .text-slate-300, .text-slate-400 {
        color: #475569 !important;
    }

    .border-white\/10, .border-white\/5 {
        border-color: #e2e8f0 !important;
    }

    .bg-white\/\[0\.02\], .bg-white\/\[0\.03\] {
        background: #f8fafc !important;
        border-color: #e2e8f0 !important;
    }

    a {
        text-decoration: none !important;
        color: #0f172a !important;
    }

    @page {
        size: A4 portrait;
        margin: 1.2cm;
    }
}
</style>

<?php get_footer(); ?>
