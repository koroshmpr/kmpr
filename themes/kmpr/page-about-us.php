<?php
/** Template Name: about us page */

get_header(); ?>
<section class="bg-slate-950 text-white overflow-hidden">
    <div class="grid grid-cols-1 lg:grid-cols-2 items-center min-h-[55vh]">
        <!-- Text content -->
        <div class="p-8 sm:p-12 lg:p-20 text-start flex flex-col gap-6" data-aos="fade-left">
            <span class="text-xs font-black uppercase text-secondary tracking-widest block">درباره ما</span>
            <h1 class="text-3xl sm:text-4xl lg:text-5xl font-black text-white leading-tight">
                <?= esc_html(get_field('aboutus-title')); ?>
            </h1>
            <div class="h-1.5 bg-secondary w-16 rounded-full"></div>
            <div class="text-slate-300 text-sm sm:text-base lg:text-lg leading-loose text-justify font-medium pt-2">
                <?= wp_kses_post(get_field('aboutus-content')); ?>
            </div>
        </div>
        
        <!-- Image content -->
        <div class="h-[350px] sm:h-[450px] lg:h-full w-full relative" data-aos="fade-right">
            <?php $about_image = get_field('aboutus_image'); ?>
            <?php if ($about_image) : ?>
                <img class="w-full h-full object-cover min-h-[400px] pointer-events-none opacity-85"
                     src="<?= esc_url($about_image['url']); ?>"
                     alt="<?= esc_attr($about_image['title'] ?? ''); ?>">
                <div class="absolute inset-0 bg-gradient-to-t lg:bg-gradient-to-l from-transparent via-transparent to-slate-950/80"></div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Statistics Section -->
<section class="bg-slate-50 py-16 lg:py-24 border-t border-slate-200/80">
    <div class="container mx-auto px-4 sm:px-6">
        <div class="max-w-3xl mx-auto text-center flex flex-col gap-4">
            <span class="text-xs font-black uppercase text-secondary tracking-widest block">آمار و دستاوردها</span>
            <h2 class="text-2xl sm:text-3xl lg:text-4xl font-black text-slate-900 mb-2">
                <?= esc_html(get_field('title_statistics')); ?>
            </h2>
            <div class="h-1.5 w-16 bg-secondary mx-auto rounded-full mb-3"></div>
            <p class="text-slate-600 text-sm sm:text-base leading-relaxed text-center mb-8">
                <?= esc_html(get_field('content_statistics')); ?>
            </p>
            
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 py-4">
                <?php while (have_rows('statistics_list')): the_row(); ?>
                    <div class="flex flex-col items-center gap-2 p-6 bg-white border border-slate-200/80 rounded-3xl shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300"
                         data-aos="fade-up" data-aos-anchor-placement="top-bottom">
                        <span class="text-3xl lg:text-4xl font-black text-secondary leading-none">
                            <?= esc_html(get_sub_field('list_statistics_value')); ?>
                        </span>
                        <p class="text-slate-600 text-xs sm:text-sm font-bold mt-1 text-center">
                            <?= esc_html(get_sub_field('list_statistics_title')); ?>
                        </p>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
</section>

<?php get_footer(); ?>
