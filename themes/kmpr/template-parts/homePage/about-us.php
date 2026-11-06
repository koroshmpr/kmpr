<?php
/**
 * Dynamic About Me section on Homepage
 * Managed via Homepage ACF Fields (about-title, about-description, about-box-title, about-box-content, about-box-img)
 */
$about_title       = get_field('about-title') ?: 'درباره من | مسیر حرفه‌ای';
$about_description = get_field('about-description') ?: 'فلسفه مهندسی نرم‌افزار و خلق سیستم‌های پایدار';
$box_title         = get_field('about-box-title') ?: 'سرعت، تمیزی کد و توسعه بدون کدهای زائد';
$box_content       = get_field('about-box-content') ?: 'من کوروش محمدپور هستم؛ معمار نرم‌افزار و توسعه‌دهنده پلتفرم‌های مدرن وب. در تمام این سال‌ها باور داشته‌ام که کیفیت ساختار کدهای یک پروژه، مهم‌ترین دارایی فنی یک کسب‌وکار است. سیستم‌ها را به صورت ماژولار، کامپوننت‌محور و اختصاصی خلق می‌کنم تا بالاترین بازدهی تجاری و رضایت کاربر حاصل شود.';
$box_img           = get_field('about-box-img');

if (is_array($box_img) && !empty($box_img['url'])) {
    $image_url = $box_img['url'];
} elseif (is_numeric($box_img)) {
    $image_url = wp_get_attachment_image_url($box_img, 'full') ?: (get_template_directory_uri() . '/public/images/korosh-mpr.webp');
} elseif (is_string($box_img) && !empty($box_img)) {
    $image_url = $box_img;
} else {
    $image_url = get_template_directory_uri() . '/public/images/korosh-mpr.webp';
}
?>
<section class="py-16 lg:py-24 bg-slate-950 overflow-hidden border-b border-white/10 relative">
    <!-- Ambient subtle background glow -->
    <div class="absolute -bottom-24 start-1/4 w-96 h-96 bg-secondary/10 rounded-full blur-[140px] pointer-events-none"></div>

    <div class="container mx-auto px-4 sm:px-6 relative z-10">
        <!-- Section Header -->
        <div class="text-center max-w-2xl mx-auto mb-14">
            <span class="text-secondary text-xs sm:text-sm font-black tracking-widest uppercase block mb-2">
                <?= esc_html($about_title); ?>
            </span>
            <h2 class="text-3xl lg:text-4xl font-black text-white leading-tight">
                <?= esc_html($about_description); ?>
            </h2>
            <div class="h-1.5 w-16 bg-secondary mx-auto rounded-full mt-4"></div>
        </div>

        <div class="max-w-5xl mx-auto grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12 items-center">
            <!-- Portrait Image with Ambient Aura & floating badge -->
            <div class="relative group" data-aos="fade-left" data-aos-duration="800">
                <div class="absolute inset-0 bg-secondary/20 transform translate-x-3 translate-y-3 rounded-3xl transition-transform duration-500 group-hover:translate-x-1.5 group-hover:translate-y-1.5 blur-sm"></div>
                <div class="overflow-hidden rounded-3xl shadow-2xl border border-white/15 bg-slate-900 relative z-10">
                    <img class="w-full h-auto object-cover aspect-[4/3] lg:aspect-square filter grayscale contrast-110 transition-all duration-700 group-hover:grayscale-0 group-hover:contrast-105 pointer-events-none"
                         src="<?= esc_url($image_url); ?>" 
                         alt="<?= esc_attr($box_title); ?>">
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-950/70 via-transparent to-transparent pointer-events-none"></div>
                </div>

                <!-- Floating Achievement Badge -->
                <div class="absolute -bottom-4 -end-4 bg-slate-900/90 backdrop-blur-md border border-white/15 p-3.5 rounded-2xl shadow-2xl z-20 flex items-center gap-3">
                    <span class="w-10 h-10 rounded-xl bg-secondary/20 text-secondary flex items-center justify-center shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </span>
                    <div class="text-start">
                        <span class="text-xs font-black text-white block whitespace-nowrap">استاندارد بین‌المللی مهندسی</span>
                        <span class="text-[11px] text-slate-400 font-bold whitespace-nowrap">توسعه اختصاصی و مقیاس‌پذیر</span>
                    </div>
                </div>
            </div>

            <!-- Content Card (Obsidian Glassmorphism with High Contrast) -->
            <div class="bg-white/[0.02] hover:bg-white/[0.04] text-white rounded-3xl p-8 sm:p-10 lg:p-12 h-full flex flex-col justify-center text-start shadow-2xl border border-white/10 relative overflow-hidden transition-all duration-300" 
                 data-aos="fade-right" data-aos-duration="800" data-aos-delay="200">
                
                <!-- Ambient corner glow inside card -->
                <div class="absolute -top-12 -end-12 w-40 h-40 bg-secondary/15 rounded-full blur-2xl pointer-events-none"></div>

                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-secondary/15 border border-secondary/30 mb-4 w-fit">
                    <span class="w-2 h-2 rounded-full bg-secondary animate-pulse"></span>
                    <span class="text-xs font-black uppercase text-secondary tracking-wider">دیدگاه و رویکرد کاری</span>
                </div>

                <h3 class="text-2xl sm:text-3xl font-black text-white mb-6 leading-tight">
                    <?= esc_html($box_title); ?>
                </h3>

                <div class="text-slate-300 text-sm sm:text-base leading-loose font-medium space-y-3">
                    <?= wp_kses_post($box_content); ?>
                </div>

                <div class="mt-8 pt-6 border-t border-white/10 flex items-center justify-between">
                    <a href="<?= esc_url(home_url('/about-us') ?: home_url('/about')); ?>" 
                       class="inline-flex items-center gap-2 text-secondary hover:text-white font-black text-sm transition-colors">
                        <span>اطلاعات بیشتر و مشاهده جزئیات مسیر حرفه‌ای</span>
                        <span>&larr;</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
