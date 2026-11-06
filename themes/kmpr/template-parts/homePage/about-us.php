<?php
/**
 * About Us section on Homepage
 */
?>
<section class="py-16 lg:py-24 bg-white overflow-hidden border-b border-slate-200/60">
    <div class="container mx-auto px-4 sm:px-6">
        <div class="text-center max-w-2xl mx-auto mb-14">
            <span class="text-secondary text-xs sm:text-sm font-black tracking-widest uppercase block mb-2">
                <?= wp_kses_post(get_field('about-title')); ?>
            </span>
            <h2 class="text-3xl lg:text-4xl font-black text-slate-900 leading-tight">
                <?= wp_kses_post(get_field('about-description')); ?>
            </h2>
            <div class="h-1.5 w-16 bg-secondary mx-auto rounded-full mt-4"></div>
        </div>

        <div class="max-w-5xl mx-auto grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12 items-center">
            <!-- Image with offset border -->
            <div class="relative group" data-aos="fade-left" data-aos-duration="800">
                <div class="absolute inset-0 bg-secondary/20 transform translate-x-3 translate-y-3 rounded-3xl transition-transform duration-500 group-hover:translate-x-1.5 group-hover:translate-y-1.5"></div>
                <div class="overflow-hidden rounded-3xl shadow-lg border border-slate-200/80 bg-white relative z-10">
                    <?php $about_img = get_field('about-box-img'); ?>
                    <?php if ($about_img) : ?>
                        <img class="w-full h-auto object-cover aspect-[4/3] lg:aspect-square transition-transform duration-1000 group-hover:scale-105 pointer-events-none"
                             src="<?= esc_url($about_img['url']); ?>" 
                             alt="<?= esc_attr($about_img['title'] ?? ''); ?>">
                    <?php endif; ?>
                </div>
            </div>

            <!-- Content Card (Clean Dark Card for high visual contrast) -->
            <div class="bg-slate-900 text-white rounded-3xl p-8 sm:p-10 lg:p-12 h-full flex flex-col justify-center text-start shadow-xl border border-slate-800" 
                 data-aos="fade-right" data-aos-duration="800" data-aos-delay="200">
                <span class="text-secondary text-xs font-black uppercase tracking-wider block mb-2">داستان ما</span>
                <h3 class="text-2xl sm:text-3xl font-black text-white mb-6 leading-tight">
                    <?= esc_html(get_field('about-box-title')); ?>
                </h3>
                <div class="text-slate-300 text-sm sm:text-base leading-loose font-medium">
                    <?= wp_kses_post(get_field('about-box-content')); ?>
                </div>
                <div class="mt-8">
                    <a href="<?= esc_url(home_url('/about-us') ?: home_url('/about')); ?>" 
                       class="inline-flex items-center gap-2 text-secondary hover:text-white font-black text-sm transition-colors">
                        <span>اطلاعات بیشتر درباره ما</span>
                        <span>&larr;</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
