<?php
/**
 * Personal Brand Hero Section: Korosh Mpr
 * High-tech, Ultra-fast, Dark Obsidian Aesthetics
 */
$korosh_photo = get_template_directory_uri() . '/public/images/korosh-mpr.webp';
?>
<section class="relative min-h-[85vh] lg:min-h-[90vh] flex items-center justify-center overflow-hidden bg-slate-950 text-white select-none py-16 lg:py-24 border-b border-white/10">
    <!-- Cyber Subtle Dot Grid Overlay -->
    <div class="absolute inset-0 bg-[radial-gradient(#334155_1px,transparent_1px)] [background-size:24px_24px] opacity-20 pointer-events-none -z-0"></div>

    <!-- Ambient Subtle Modern Glow Spheres -->
    <div class="absolute top-1/4 start-1/4 w-96 h-96 bg-secondary/15 rounded-full blur-[140px] pointer-events-none -z-0"></div>
    <div class="absolute bottom-10 end-1/4 w-96 h-96 bg-blue-600/10 rounded-full blur-[140px] pointer-events-none -z-0"></div>

    <!-- Deep cinematic gradient overlay for seamless contrast -->
    <div class="absolute inset-0 bg-gradient-to-b from-slate-950/40 via-slate-950/80 to-slate-950 -z-0"></div>

    <div class="container mx-auto px-4 sm:px-6 relative z-10">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-8 items-center">
            
            <!-- Right Column: Personal Introduction & Pitch -->
            <div class="lg:col-span-7 flex flex-col items-center lg:items-start text-center lg:text-start gap-6">
                
                <!-- Live Availability Glow Pill Badge -->
                <div data-aos="fade-down" data-aos-duration="700"
                     class="inline-flex items-center gap-2.5 px-4 py-2 rounded-full bg-white/[0.04] hover:bg-white/[0.08] border border-white/10 backdrop-blur-xl shadow-lg shadow-black/30 transition-all duration-300">
                    <span class="relative flex w-2.5 h-2.5 shrink-0">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full w-2.5 h-2.5 bg-emerald-500"></span>
                    </span>
                    <span class="text-xs font-black text-slate-200 tracking-wide">در دسترس برای پروژه‌های مقیاس‌بالا و توسعه اختصاصی (۲۰۲۶)</span>
                </div>

                <!-- Main Personal Title -->
                <div data-aos="fade-up" data-aos-duration="800" data-aos-delay="100" class="space-y-3">
                    <div class="text-sm sm:text-base font-mono text-secondary tracking-wider uppercase font-bold flex items-center justify-center lg:justify-start gap-2">
                        <span>👋</span>
                        <span>سلام، من کوروش محمدپور هستم</span>
                    </div>
                    <h1 class="text-3xl sm:text-5xl lg:text-6xl font-black leading-tight sm:leading-tight lg:leading-[1.2] tracking-tight text-white">
                        معمار نرم‌افزار و
                        <span class="text-transparent bg-clip-text bg-gradient-to-l from-amber-400 via-secondary to-amber-200">
                            توسعه‌دهنده وب
                        </span>
                    </h1>
                </div>

                <!-- Bio & Engineering Value Proposition -->
                <p data-aos="fade-up" data-aos-duration="800" data-aos-delay="200" 
                   class="text-base sm:text-lg text-slate-300 leading-relaxed font-medium max-w-2xl">
                    من به ساخت سیستم‌های تحت وب با <strong class="text-white font-extrabold">نهایت سرعت و پرفورمنس</strong>، کدنویسی اختصاصی بدون صفحه‌سازهای سنگین، و تلفیق زیرساخت‌ها با <strong class="text-secondary font-extrabold">موتورهای هوش مصنوعی (AI-Native)</strong> متعهدم.
                </p>

                <!-- Action CTAs (Whitespaces-nowrap, High Impact) -->
                <div data-aos="fade-up" data-aos-duration="800" data-aos-delay="300" 
                     class="mt-2 flex flex-wrap items-center justify-center lg:justify-start gap-4 w-full sm:w-auto">
                    <a href="<?= esc_url(get_post_type_archive_link('portfolio') ?: home_url('/portfolio')); ?>" 
                       class="w-full sm:w-auto text-center whitespace-nowrap bg-secondary hover:bg-secondary-hover text-white font-black text-sm sm:text-base px-8 py-3.5 rounded-full transition-all duration-300 shadow-xl shadow-secondary/25 hover:scale-105 active:scale-95">
                        مشاهده پروژه‌ها و کدها
                    </a>

                    <a href="<?= esc_url(home_url('/contact-us') ?: home_url('/contact')); ?>" 
                       class="w-full sm:w-auto text-center whitespace-nowrap bg-white/[0.06] hover:bg-white/[0.12] backdrop-blur-xl text-white font-bold text-sm sm:text-base px-8 py-3.5 rounded-full transition-all duration-300 border border-white/15 hover:scale-105 active:scale-95 shadow-md">
                        شروع همکاری و مشاوره اختصاصی
                    </a>
                </div>

                <!-- Developer Quality Badges -->
                <div data-aos="fade-up" data-aos-duration="800" data-aos-delay="400"
                     class="mt-4 pt-6 border-t border-white/10 flex flex-wrap items-center justify-center lg:justify-start gap-4 sm:gap-6 text-xs text-slate-400 font-bold w-full">
                    <span class="flex items-center gap-1.5">
                        <span class="text-emerald-400">⚡</span>
                        <span>پرفورمنس ۱۰۰/۱۰۰ Core Web Vitals</span>
                    </span>
                    <span class="text-slate-700 hidden sm:inline">•</span>
                    <span class="flex items-center gap-1.5">
                        <span class="text-secondary">🛡️</span>
                        <span>کدنویسی تمیز و بدون صفحه‌ساز</span>
                    </span>
                    <span class="text-slate-700 hidden sm:inline">•</span>
                    <span class="flex items-center gap-1.5">
                        <span class="text-sky-400">🤖</span>
                        <span>آماده برای موتورهای هوش مصنوعی</span>
                    </span>
                </div>

            </div>

            <!-- Left Column: Photographic Portrait with High-Tech Frame -->
            <div class="lg:col-span-5 flex justify-center relative" data-aos="fade-right" data-aos-duration="900" data-aos-delay="200">
                <div class="relative w-full max-w-sm sm:max-w-md group">
                    
                    <!-- Ambient Behind Glow -->
                    <div class="absolute -inset-4 bg-gradient-to-tr from-secondary/25 via-blue-600/15 to-transparent rounded-3xl blur-2xl opacity-60 group-hover:opacity-90 transition-opacity duration-700 pointer-events-none"></div>

                    <!-- Image Frame -->
                    <div class="relative rounded-3xl overflow-hidden border border-white/15 bg-slate-900 shadow-2xl transition-transform duration-500 group-hover:scale-[1.01]">
                        <img class="w-full h-auto aspect-square object-cover object-top filter grayscale contrast-110 transition-all duration-700 group-hover:grayscale-0 group-hover:contrast-105" 
                             src="<?= esc_url($korosh_photo); ?>" 
                             alt="کوروش محمدپور | معمار نرم‌افزار و توسعه‌دهنده وب"
                             width="600"
                             height="600"
                             loading="eager"
                             fetchpriority="high">
                        
                        <!-- Subtle Gradient Overlay -->
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-transparent to-transparent pointer-events-none"></div>

                        <!-- Bottom Nameplate inside image -->
                        <div class="absolute bottom-4 inset-x-4 flex items-center justify-between p-3 rounded-2xl bg-slate-950/80 backdrop-blur-md border border-white/10">
                            <div>
                                <span class="block text-xs font-black text-white">کوروش محمدپور</span>
                                <span class="text-[10px] text-slate-400 font-mono">Senior Creative Technologist</span>
                            </div>
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-ping"></span>
                        </div>
                    </div>

                    <!-- Floating Tech Badge 1 (Top End) -->
                    <div class="absolute -top-3 -end-3 bg-slate-900/90 backdrop-blur-md border border-white/15 py-2 px-3.5 rounded-2xl shadow-xl flex items-center gap-2 max-sm:hidden">
                        <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                        <span class="text-[11px] font-bold text-slate-200">Next.js • Tailwind • WordPress</span>
                    </div>

                    <!-- Floating Tech Badge 2 (Bottom Start) -->
                    <div class="absolute -bottom-3 -start-3 bg-slate-900/90 backdrop-blur-md border border-white/15 py-2.5 px-4 rounded-2xl shadow-xl flex items-center gap-3 max-sm:hidden">
                        <span class="w-8 h-8 rounded-xl bg-secondary/20 text-secondary flex items-center justify-center font-black text-sm">
                            ⚡
                        </span>
                        <div class="text-start">
                            <span class="text-xs font-black text-white block">+۷ سال تجربه</span>
                            <span class="text-[10px] text-slate-400 font-medium">معماری سیستم‌های مدرن</span>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</section>
