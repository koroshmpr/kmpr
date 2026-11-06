<?php
$heroImage = get_field('hero-image');
$slogan = get_field('hero-slogan');
$subtitle = get_field('hero-subtitle');
?>
<section class="relative min-h-[70vh] lg:min-h-[82vh] flex items-center overflow-hidden bg-slate-950 text-white select-none pt-12 pb-24 lg:py-24">
    <?php if ($heroImage) : ?>
        <img data-aos="zoom-out" data-aos-duration="1500" 
             class="absolute inset-0 w-full h-full object-cover object-center opacity-35 scale-105 pointer-events-none transition-transform duration-700" 
             src="<?= esc_url($heroImage['url']); ?>" 
             alt="<?= esc_attr($slogan); ?>">
    <?php endif; ?>

    <!-- Gradient overlays for maximum text legibility -->
    <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/60 to-slate-950/30"></div>
    <div class="absolute inset-y-0 end-0 w-1/2 bg-gradient-to-l from-slate-950/80 to-transparent max-lg:hidden"></div>

    <!-- Content -->
    <div class="container mx-auto px-4 sm:px-6 relative z-10">
        <div class="max-w-3xl flex flex-col items-start gap-6 text-start">
            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-secondary/20 border border-secondary/30 backdrop-blur-md">
                <span class="size-2 rounded-full bg-secondary animate-pulse"></span>
                <span class="text-xs font-black text-secondary uppercase tracking-wider">خلاقیت، تکنولوژی، توسعه</span>
            </div>

            <h1 data-aos="fade-left" data-aos-duration="800" data-aos-delay="200" 
                class="text-3xl sm:text-5xl lg:text-6xl font-black leading-tight sm:leading-tight lg:leading-[1.2] tracking-tight text-white drop-shadow-md">
                <?= esc_html($slogan); ?>
            </h1>

            <p data-aos="fade-left" data-aos-duration="800" data-aos-delay="400" 
               class="text-base sm:text-lg lg:text-xl text-slate-300 leading-relaxed font-medium max-w-2xl">
                <?= esc_html($subtitle); ?>
            </p>

            <div data-aos="fade-left" data-aos-duration="800" data-aos-delay="600" class="mt-2 flex flex-wrap gap-4 w-full sm:w-auto">
                <a href="#services" class="w-full sm:w-auto text-center bg-secondary hover:bg-secondary-hover text-white font-black text-sm sm:text-base px-8 py-3.5 rounded-full transition-all duration-300 shadow-lg shadow-secondary/25 hover:scale-105">
                    مشاهده خدمات
                </a>
                <a href="#portfolio" class="w-full sm:w-auto text-center bg-white/10 hover:bg-white/20 backdrop-blur-md text-white font-bold text-sm sm:text-base px-8 py-3.5 rounded-full transition-all duration-300 border border-white/20 hover:scale-105">
                    نمونه‌کارها
                </a>
            </div>
        </div>
    </div>
</section>
