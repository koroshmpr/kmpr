<?php
/** Template Name: about us page */

get_header(); ?>
<section class="bg-slate-900 text-white overflow-hidden">
    <div class="grid grid-cols-1 lg:grid-cols-2 items-center min-h-[50vh]">
        <!-- Text content -->
        <div class="p-8 lg:p-16 text-start flex flex-col gap-6" data-aos="fade-left">
            <h1 class="text-3xl lg:text-5xl font-black text-white leading-tight"><?= esc_html(get_field('aboutus-title')); ?></h1>
            <div class="h-1 bg-secondary w-16 rounded-full"></div>
            <p class="text-slate-200/90 text-base lg:text-lg leading-loose text-justify pt-4"><?= wp_kses_post(get_field('aboutus-content')); ?></p>
        </div>
        
        <!-- Image content -->
        <div class="h-[350px] lg:h-full w-full" data-aos="fade-right">
            <img class="w-full h-full object-cover min-h-[400px] pointer-events-none"
                 src="<?= esc_url(get_field('aboutus_image')['url']); ?>"
                 alt="<?= esc_attr(get_field('aboutus_image')['title']); ?>">
        </div>
    </div>
</section>

<!-- Statistics Section -->
<section class="bg-secondary/15 py-16 lg:py-24 border-t border-slate-200/60">
    <div class="container mx-auto px-6">
        <div class="max-w-3xl mx-auto text-center flex flex-col gap-4 text-start">
            <h4 class="text-2xl lg:text-3xl font-black text-slate-900 mb-2 text-center"><?= esc_html(get_field('title_statistics')); ?></h4>
            <p class="text-slate-600 text-base leading-relaxed text-justify mb-8"><?= esc_html(get_field('content_statistics')); ?></p>
            
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-8 py-4">
                <?php while (have_rows('statistics_list')): the_row(); ?>
                    <div class="flex flex-col items-center gap-2 p-6 bg-white border border-slate-200/80 rounded-2xl shadow-sm"
                         data-aos="fade-up" data-aos-anchor-placement="top-bottom">
                        <h5 class="text-3xl lg:text-4xl font-black text-primary leading-none"><?= esc_html(get_sub_field('list_statistics_value')); ?></h5>
                        <p class="text-slate-500 text-sm font-bold mt-1"><?= esc_html(get_sub_field('list_statistics_title')); ?></p>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
</section>

<?php get_footer(); ?>
