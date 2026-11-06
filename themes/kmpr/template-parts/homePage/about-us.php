<?php
/**
 * About Us section on Homepage
 */
?>
<section class="py-16 lg:py-24 bg-white overflow-hidden">
    <div class="container mx-auto px-6">
        <div class="text-center max-w-2xl mx-auto mb-16">
            <h3 class="text-secondary text-sm font-extrabold tracking-widest uppercase mb-2">
                <?= wp_kses_post(get_field('about-title')); ?>
            </h3>
            <div class="text-3xl lg:text-4xl font-extrabold text-slate-800 leading-tight">
                <?= wp_kses_post(get_field('about-description')); ?>
            </div>
        </div>

        <div class="max-w-5xl mx-auto grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12 items-center">
            <!-- Image with offset border -->
            <div class="relative group" data-aos="fade-left" data-aos-duration="800">
                <div class="absolute inset-0 bg-secondary/10 transform translate-x-4 translate-y-4 rounded-2xl transition-transform duration-500 group-hover:translate-x-2 group-hover:translate-y-2"></div>
                <div class="overflow-hidden rounded-2xl shadow-md border border-slate-100 bg-white">
                    <img class="w-full h-auto object-cover aspect-[4/3] lg:aspect-square transition-transform duration-1000 group-hover:scale-105 pointer-events-none"
                         src="<?= esc_url(get_field('about-box-img')['url']); ?>" 
                         alt="<?= esc_attr(get_field('about-box-img')['title']); ?>">
                </div>
            </div>

            <!-- Content Card -->
            <div class="bg-secondary/15 rounded-2xl p-8 lg:p-12 h-full flex flex-col justify-center text-start" 
                 data-aos="fade-right" data-aos-duration="800" data-aos-delay="200">
                <h4 class="text-2xl lg:text-3xl font-extrabold text-slate-900 mb-6">
                    <?= esc_html(get_field('about-box-title')); ?>
                </h4>
                <div class="text-slate-700 text-base leading-loose font-medium">
                    <?= wp_kses_post(get_field('about-box-content')); ?>
                </div>
            </div>
        </div>
    </div>
</section>