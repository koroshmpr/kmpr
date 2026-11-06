<?php
$heroImage = get_field('hero-image');
$slogan = get_field('hero-slogan');
$subtitle = get_field('hero-subtitle');
?>
<section class="relative min-h-[75vh] lg:min-h-[88vh] flex items-center overflow-hidden bg-slate-950 text-white select-none pt-12 pb-20 lg:py-28">
    <!-- Ambient Modern Glow Spheres -->
    <div class="absolute top-1/4 -start-20 w-80 h-80 sm:w-96 sm:h-96 bg-secondary/20 rounded-full blur-3xl pointer-events-none -z-0"></div>
    <div class="absolute bottom-10 end-10 w-96 h-96 bg-blue-600/15 rounded-full blur-[100px] pointer-events-none -z-0"></div>
    <div class="absolute top-10 end-1/3 w-64 h-64 bg-amber-500/10 rounded-full blur-[90px] pointer-events-none -z-0"></div>

    <?php if ($heroImage) : ?>
        <img data-aos="zoom-out" data-aos-duration="1500" 
             class="absolute inset-0 w-full h-full object-cover object-center opacity-25 scale-105 pointer-events-none transition-transform duration-1000 -z-0" 
             src="<?= esc_url($heroImage['url']); ?>" 
             alt="<?= esc_attr($slogan); ?>">
    <?php endif; ?>

    <!-- Gradient overlays for maximum text legibility and rich contrast -->
    <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/70 to-slate-950/40 -z-0"></div>
    <div class="absolute inset-y-0 end-0 w-2/3 bg-gradient-to-l from-slate-950/90 via-slate-950/40 to-transparent max-lg:hidden -z-0"></div>

    <!-- Content Container -->
    <div class="container mx-auto px-4 sm:px-6 relative z-10">
        <div class="max-w-3xl flex flex-col items-start gap-6 text-start">
            
            <!-- Modern Glow Badge -->
            <div data-aos="fade-down" data-aos-duration="700"
                 class="inline-flex items-center gap-2.5 px-4 py-2 rounded-full bg-white/[0.07] hover:bg-white/[0.12] border border-white/15 backdrop-blur-xl shadow-lg shadow-black/20 transition-all duration-300">
                <span class="relative flex size-2.5">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-secondary opacity-75"></span>
                    <span class="relative inline-flex rounded-full size-2.5 bg-secondary"></span>
                </span>
                <span class="text-xs font-black text-slate-200 tracking-wide">خلاقیت، تکنولوژی، توسعه سیستم‌های مدرن</span>
            </div>

            <!-- Main Slogan -->
            <h1 data-aos="fade-left" data-aos-duration="800" data-aos-delay="200" 
                class="text-3xl sm:text-5xl lg:text-6xl font-black leading-tight sm:leading-tight lg:leading-[1.2] tracking-tight text-white drop-shadow-md">
                <?= esc_html($slogan); ?>
            </h1>

            <!-- Subtitle -->
            <p data-aos="fade-left" data-aos-duration="800" data-aos-delay="400" 
               class="text-base sm:text-lg lg:text-xl text-slate-300/90 leading-relaxed font-medium max-w-2xl">
                <?= esc_html($subtitle); ?>
            </p>

            <!-- CTA Buttons -->
            <div data-aos="fade-left" data-aos-duration="800" data-aos-delay="600" 
                 class="mt-3 flex flex-wrap items-center gap-4 w-full sm:w-auto">
                <a href="#services" 
                   class="group w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-secondary hover:bg-secondary-hover text-white font-black text-sm sm:text-base px-8 py-4 rounded-2xl transition-all duration-300 shadow-xl shadow-secondary/30 hover:shadow-secondary/50 hover:scale-105 active:scale-95">
                    <span>مشاهده خدمات تخصصی</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4 transition-transform duration-300 group-hover:-translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>

                <a href="#portfolio" 
                   class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-white/10 hover:bg-white/15 backdrop-blur-xl text-white font-bold text-sm sm:text-base px-8 py-4 rounded-2xl transition-all duration-300 border border-white/20 hover:border-white/40 hover:scale-105 active:scale-95 shadow-md">
                    <span>نمونه‌کارها و پروژه‌ها</span>
                </a>
            </div>

            <!-- Value Highlights Micro-Cards -->
            <div data-aos="fade-up" data-aos-duration="800" data-aos-delay="800"
                 class="grid grid-cols-2 sm:grid-cols-3 gap-3 sm:gap-4 mt-6 pt-6 border-t border-white/10 w-full text-start">
                <div class="flex items-center gap-2.5">
                    <span class="p-2 rounded-xl bg-secondary/15 text-secondary border border-secondary/25 shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                    </span>
                    <span class="text-xs font-bold text-slate-300">تحویل سریع و تضمینی</span>
                </div>

                <div class="flex items-center gap-2.5">
                    <span class="p-2 rounded-xl bg-secondary/15 text-secondary border border-secondary/25 shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </span>
                    <span class="text-xs font-bold text-slate-300">سرعت و کدنویسی بهینه</span>
                </div>

                <div class="flex items-center gap-2.5 col-span-2 sm:col-span-1">
                    <span class="p-2 rounded-xl bg-secondary/15 text-secondary border border-secondary/25 shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </span>
                    <span class="text-xs font-bold text-slate-300">پشتیبانی و توسعه اختصاصی</span>
                </div>
            </div>

        </div>
    </div>
</section>
