<?php
/**
 * Dynamic Personal Brand Hero Section
 * Fully manageable via WordPress Dashboard (Home Page ACF Fields)
 */
$hero_slogan   = get_field('hero-slogan');
$hero_subtitle = get_field('hero-subtitle');
$hero_image    = get_field('hero-image');

// Default fallback photo if not set in dashboard
if (is_array($hero_image) && !empty($hero_image['url'])) {
    $photo_url = $hero_image['url'];
} elseif (is_numeric($hero_image)) {
    $photo_url = wp_get_attachment_image_url($hero_image, 'full') ?: (get_template_directory_uri() . '/public/images/korosh-mpr.webp');
} elseif (is_string($hero_image) && !empty($hero_image)) {
    $photo_url = $hero_image;
} else {
    $photo_url = get_template_directory_uri() . '/public/images/korosh-mpr.webp';
}

// Slogan & Subtitle with intelligent fallbacks
$title_text = $hero_slogan ? esc_html(wp_strip_all_tags($hero_slogan)) : 'معمار نرم‌افزار و توسعه‌دهنده پلتفرم‌های مدرن وب';
$desc_text  = $hero_subtitle ? esc_html(wp_strip_all_tags($hero_subtitle)) : 'طراحی و پیاده‌سازی سیستم‌های وب با نهایت سرعت و پرفورمنس، معماری استاندارد و راهکارهای پایدار متناسب با مقیاس کسب‌وکار شما.';
?>
<section class="relative min-h-[85vh] lg:min-h-[90vh] flex items-center justify-center overflow-hidden bg-slate-950 text-white select-none py-16 lg:py-24 border-b border-white/10">
    <!-- Subtle Grid Overlay -->
    <div class="absolute inset-0 bg-[radial-gradient(#334155_1px,transparent_1px)] [background-size:24px_24px] opacity-20 pointer-events-none -z-0"></div>

    <!-- Ambient Subtle Glow Spheres -->
    <div class="absolute top-1/4 start-1/4 w-96 h-96 bg-secondary/15 rounded-full blur-[140px] pointer-events-none -z-0"></div>
    <div class="absolute bottom-10 end-1/4 w-96 h-96 bg-blue-600/10 rounded-full blur-[140px] pointer-events-none -z-0"></div>

    <!-- Gradient overlay for seamless contrast -->
    <div class="absolute inset-0 bg-gradient-to-b from-slate-950/40 via-slate-950/80 to-slate-950 -z-0"></div>

    <div class="container mx-auto px-4 sm:px-6 relative z-10">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-8 items-center">
            
            <!-- Right Column: Personal Introduction & Pitch -->
            <div class="lg:col-span-7 flex flex-col items-center lg:items-start text-center lg:text-start gap-6">
                
                <!-- Live Availability Pill Badge -->
                <div data-aos="fade-down" data-aos-duration="700"
                     class="inline-flex items-center gap-2.5 px-4 py-2 rounded-full bg-white/[0.04] hover:bg-white/[0.08] border border-white/10 backdrop-blur-xl shadow-lg shadow-black/30 transition-all duration-300">
                    <span class="relative flex w-2.5 h-2.5 shrink-0">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full w-2.5 h-2.5 bg-emerald-500"></span>
                    </span>
                    <span class="text-xs font-black text-slate-200 tracking-wide">پذیرش پروژه‌های اختصاصی و مشاوره معماری نرم‌افزار (۲۰۲۶)</span>
                </div>

                <!-- Main Personal Title -->
                <div data-aos="fade-up" data-aos-duration="800" data-aos-delay="100" class="space-y-3">
                    <div class="text-sm sm:text-base font-mono text-secondary tracking-wider uppercase font-bold flex items-center justify-center lg:justify-start gap-2">
                        <svg class="size-4.5 text-secondary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="16 18 22 12 16 6"/>
                            <polyline points="8 6 2 12 8 18"/>
                        </svg>
                        <span>کوروش محمدپور | توسعه‌دهنده ارشد و معمار وب</span>
                    </div>
                    <h1 class="text-3xl sm:text-5xl lg:text-6xl font-black leading-tight sm:leading-tight lg:leading-[1.2] tracking-tight text-white">
                        <?= $title_text; ?>
                    </h1>
                </div>

                <!-- Bio & Engineering Value Proposition -->
                <p data-aos="fade-up" data-aos-duration="800" data-aos-delay="200" 
                   class="text-base sm:text-lg text-slate-300 leading-relaxed font-medium max-w-2xl">
                    <?= $desc_text; ?>
                </p>

                <!-- Action CTAs -->
                <div data-aos="fade-up" data-aos-duration="800" data-aos-delay="300" 
                     class="mt-2 flex flex-wrap items-center justify-center lg:justify-start gap-4 w-full sm:w-auto">
                    <a href="<?= esc_url(get_post_type_archive_link('portfolio') ?: home_url('/portfolio')); ?>" 
                       class="w-full sm:w-auto text-center whitespace-nowrap bg-secondary hover:bg-secondary-hover text-white font-black text-sm sm:text-base px-8 py-3.5 rounded-full transition-all duration-300 shadow-xl shadow-secondary/25 hover:scale-105 active:scale-95">
                        مشاهده پروژه‌ها و نمونه‌کارها
                    </a>

                    <a href="<?= esc_url(home_url('/contact-us') ?: home_url('/contact')); ?>" 
                       class="w-full sm:w-auto text-center whitespace-nowrap bg-white/[0.06] hover:bg-white/[0.12] backdrop-blur-xl text-white font-bold text-sm sm:text-base px-8 py-3.5 rounded-full transition-all duration-300 border border-white/15 hover:scale-105 active:scale-95 shadow-md">
                        شروع همکاری و مشاوره اختصاصی
                    </a>

                    <a href="<?= esc_url(home_url('/resume/')); ?>" 
                       class="w-full sm:w-auto text-center whitespace-nowrap bg-secondary/15 hover:bg-secondary/25 text-secondary hover:text-amber-300 font-bold text-sm sm:text-base px-6 py-3.5 rounded-full transition-all duration-300 border border-secondary/30 hover:scale-105 active:scale-95 shadow-md flex items-center justify-center gap-2">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                        </svg>
                        <span>رزومه آنلاین (CV)</span>
                    </a>
                </div>

                <!-- Business-impact Quality Badges -->
                <div data-aos="fade-up" data-aos-duration="800" data-aos-delay="400"
                     class="mt-4 pt-6 border-t border-white/10 flex flex-wrap items-center justify-center lg:justify-start gap-4 sm:gap-6 text-xs text-slate-400 font-bold w-full">
                    <span class="flex items-center gap-1.5">
                        <svg class="size-4 text-emerald-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
                        </svg>
                        <span>پرفورمنس و سرعت فوق‌العاده</span>
                    </span>
                    <span class="text-slate-700 hidden sm:inline">•</span>
                    <span class="flex items-center gap-1.5">
                        <svg class="size-4 text-secondary shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                            <path d="m9 12 2 2 4-4"/>
                        </svg>
                        <span>کدنویسی تمیز، امن و بدون سربار</span>
                    </span>
                    <span class="text-slate-700 hidden sm:inline">•</span>
                    <span class="flex items-center gap-1.5">
                        <svg class="size-4 text-sky-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/>
                            <circle cx="12" cy="12" r="6"/>
                            <circle cx="12" cy="12" r="2"/>
                        </svg>
                        <span>توسعه پایدار و مقیاس‌پذیر</span>
                    </span>
                </div>

            </div>

            <!-- Left Column: Portrait with Architectural Frame -->
            <div class="lg:col-span-5 flex justify-center relative" data-aos="fade-right" data-aos-duration="900" data-aos-delay="200">
                <div class="relative w-full max-w-sm sm:max-w-md group">
                    
                    <!-- Ambient Glow -->
                    <div class="absolute -inset-4 bg-gradient-to-tr from-secondary/25 via-blue-600/15 to-transparent rounded-3xl blur-2xl opacity-60 group-hover:opacity-90 transition-opacity duration-700 pointer-events-none"></div>

                    <!-- Image Frame -->
                    <div class="relative rounded-3xl overflow-hidden border border-white/15 bg-slate-900 shadow-2xl transition-transform duration-500 group-hover:scale-[1.01]">
                        <img class="w-full h-auto aspect-square object-cover object-top filter grayscale contrast-110 transition-all duration-700 group-hover:grayscale-0 group-hover:contrast-105" 
                             src="<?= esc_url($photo_url); ?>" 
                             alt="کوروش محمدپور | معمار نرم‌افزار و توسعه‌دهنده وب"
                             width="600"
                             height="600"
                             loading="eager"
                             fetchpriority="high">
                        
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-transparent to-transparent pointer-events-none"></div>

                        <!-- Bottom Nameplate inside image -->
                        <div class="absolute bottom-4 end-4 flex gap-3 items-center justify-between p-3 rounded-2xl bg-slate-950/80 backdrop-blur-md border border-white/10">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-ping"></span>    
                        <div>
                                <span class="block text-xs font-black text-white">کوروش محمدپور</span>
                                <span class="text-[10px] text-slate-400 font-mono">Senior Software Architect</span>
                            </div>
                            
                        </div>
                    </div>

                    <!-- Floating Quality Badge 1 (Top End) -->
                    <div class="absolute -top-3 -end-3 bg-slate-900/90 backdrop-blur-md border border-white/15 py-2 px-3.5 rounded-2xl shadow-xl flex items-center gap-2 max-sm:hidden">
                        <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                        <span class="text-[11px] font-bold text-slate-200">معماری سیستم‌های مدرن وب</span>
                    </div>

                    <!-- Floating Quality Badge 2 (Bottom Start) -->
                    <div class="absolute -bottom-3 -start-3 bg-slate-900/90 backdrop-blur-md border border-white/15 py-2.5 px-4 rounded-2xl shadow-xl flex items-center gap-3 max-sm:hidden">
                        <span class="w-8 h-8 rounded-xl bg-secondary/20 text-secondary flex items-center justify-center font-black">
                            <svg class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
                            </svg>
                        </span>
                        <div class="text-start">
                            <span class="text-xs font-black text-white block">+۷ سال سابقه تخصصی</span>
                            <span class="text-[10px] text-slate-400 font-medium">طراحی و توسعه پلتفرم‌ها</span>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</section>
