<?php
$heroImage = get_field('hero-image');
$slogan = get_field('hero-slogan');
$subtitle = get_field('hero-subtitle');
?>
<section class="relative h-[65vh] lg:h-[75vh] flex items-center overflow-hidden bg-slate-950 text-white select-none">
    <?php if ($heroImage) : ?>
        <img data-aos="zoom-out" data-aos-duration="1500" 
             class="absolute inset-0 w-full h-full object-cover object-bottom opacity-40 scale-105 pointer-events-none transition-transform duration-700" 
             src="<?= esc_url($heroImage['url']); ?>" 
             alt="<?= esc_attr($slogan); ?>">
    <?php endif; ?>

    <!-- Gradient overlays -->
    <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/20 to-transparent"></div>
    <div class="absolute inset-y-0 end-0 w-1/3 bg-gradient-to-l from-slate-950/40 to-transparent max-lg:hidden"></div>

    <!-- Content -->
    <div class="container mx-auto px-6 relative z-10">
        <div class="max-w-3xl flex flex-col items-start gap-6 text-start">
            <h1 data-aos="fade-left" data-aos-duration="800" data-aos-delay="200" 
                class="text-4xl md:text-6xl lg:text-7xl font-black leading-tight tracking-tight text-white drop-shadow-md">
                <?= esc_html($slogan); ?>
            </h1>
            <p data-aos="fade-left" data-aos-duration="800" data-aos-delay="400" 
               class="text-lg md:text-xl text-slate-200/90 leading-relaxed font-medium">
                <?= esc_html($subtitle); ?>
            </p>
            <div data-aos="fade-left" data-aos-duration="800" data-aos-delay="600" class="mt-4 flex gap-4">
                <a href="#services" class="bg-secondary text-primary font-bold text-base px-8 py-3 rounded-full hover:bg-secondary/90 transition-all shadow-lg hover:shadow-secondary/20 hover:scale-105 duration-300">
                    خدمات ما
                </a>
                <a href="#portfolio" class="bg-white/10 backdrop-blur-md text-white font-bold text-base px-8 py-3 rounded-full hover:bg-white/20 transition-all border border-white/20 hover:scale-105 duration-300">
                    نمونه کارها
                </a>
            </div>
        </div>
    </div>
</section>