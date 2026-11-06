<?php
$heroImage = get_field('hero-image');
$slogan = get_field('hero-slogan');
$subtitle = get_field('hero-subtitle');
?>
<section class="relative min-h-[72vh] lg:min-h-[82vh] flex items-center justify-center overflow-hidden bg-slate-950 text-white select-none py-16 lg:py-24">
    <!-- Ambient Modern Glow Spheres -->
    <div class="absolute top-1/4 start-1/4 w-96 h-96 bg-secondary/20 rounded-full blur-[120px] pointer-events-none -z-0"></div>
    <div class="absolute bottom-10 end-1/4 w-96 h-96 bg-blue-600/15 rounded-full blur-[120px] pointer-events-none -z-0"></div>

    <?php if ($heroImage) : ?>
        <img data-aos="zoom-out" data-aos-duration="1500" 
             class="absolute inset-0 w-full h-full object-cover object-center opacity-30 scale-105 pointer-events-none transition-transform duration-1000 -z-0" 
             src="<?= esc_url($heroImage['url']); ?>" 
             alt="<?= esc_attr($slogan); ?>">
    <?php endif; ?>

    <!-- Deep cinematic gradient overlay for 100% contrast & legibility -->
    <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/70 to-slate-950/50 -z-0"></div>

    <!-- Content Container (Balanced & Centered) -->
    <div class="container mx-auto px-4 sm:px-6 relative z-10">
        <div class="max-w-3xl mx-auto flex flex-col items-center text-center gap-6">
            
            <!-- Modern Glow Pill Badge -->
            <div data-aos="fade-down" data-aos-duration="700"
                 class="inline-flex items-center gap-2.5 px-4 py-2 rounded-full bg-white/[0.08] hover:bg-white/[0.12] border border-white/15 backdrop-blur-xl shadow-lg shadow-black/20 transition-all duration-300">
                <span class="relative flex w-2.5 h-2.5 shrink-0">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-secondary opacity-75"></span>
                    <span class="relative inline-flex rounded-full w-2.5 h-2.5 bg-secondary"></span>
                </span>
                <span class="text-xs font-black text-slate-200 tracking-wide">خلاقیت، تکنولوژی، توسعه سیستم‌های مدرن</span>
            </div>

            <!-- Main Slogan -->
            <h1 data-aos="fade-up" data-aos-duration="800" data-aos-delay="150" 
                class="text-3xl sm:text-5xl lg:text-6xl font-black leading-tight sm:leading-tight lg:leading-[1.25] tracking-tight text-white drop-shadow-md">
                <?= esc_html($slogan); ?>
            </h1>

            <!-- Subtitle -->
            <p data-aos="fade-up" data-aos-duration="800" data-aos-delay="300" 
               class="text-base sm:text-lg lg:text-xl text-slate-300 leading-relaxed font-medium max-w-2xl">
                <?= esc_html($subtitle); ?>
            </p>

            <!-- CTA Buttons (Clean, Horizontal, Whitespace-Nowrap) -->
            <div data-aos="fade-up" data-aos-duration="800" data-aos-delay="450" 
                 class="mt-2 flex flex-wrap items-center justify-center gap-4 w-full sm:w-auto">
                <a href="#services" 
                   class="w-full sm:w-auto text-center whitespace-nowrap bg-secondary hover:bg-secondary-hover text-white font-black text-sm sm:text-base px-8 py-3.5 rounded-full transition-all duration-300 shadow-xl shadow-secondary/30 hover:scale-105 active:scale-95">
                    مشاهده خدمات تخصصی
                </a>

                <a href="#portfolio" 
                   class="w-full sm:w-auto text-center whitespace-nowrap bg-white/10 hover:bg-white/15 backdrop-blur-xl text-white font-bold text-sm sm:text-base px-8 py-3.5 rounded-full transition-all duration-300 border border-white/20 hover:scale-105 active:scale-95 shadow-md">
                    نمونه‌کارها و پروژه‌ها
                </a>
            </div>

        </div>
    </div>
</section>
