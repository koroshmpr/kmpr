<?php
/**
 * Blog Slider section on Homepage
 */
?>
<section class="py-16 lg:py-24 bg-slate-50 overflow-hidden border-b border-slate-200/60">
    <div class="container mx-auto px-4 sm:px-6 relative">
        <div class="text-center max-w-xl mx-auto mb-14">
            <span class="text-xs font-black uppercase text-secondary tracking-widest block mb-2">وبلاگ و یادداشت‌ها</span>
            <?php while (have_rows('cta_title')): the_row(); ?>
                <h2 class="text-3xl lg:text-4xl font-black text-slate-900 mb-3">
                    <?= esc_html(get_sub_field('first_part_cta')); ?>
                    <span class="text-secondary relative">
                        <?= esc_html(get_sub_field('underline_word_cta')); ?>
                    </span>
                </h2>
                <div class="h-1.5 w-16 bg-secondary mx-auto rounded-full mb-3"></div>
                <p class="text-slate-500 text-sm sm:text-base leading-relaxed">
                    <?= esc_html(get_sub_field('short_description_cta')); ?>
                </p>
            <?php endwhile; ?>
        </div>

        <!-- Swiper Container -->
        <div class="swiper post-slider w-full !overflow-visible cursor-grab active:cursor-grabbing"
             data-index="blog" 
             data-perfix="blog" 
             data-space="24" 
             data-loop="1" 
             data-autoplay="6000" 
             data-perpage="3" 
             data-laptop="3" 
             data-tablet="2" 
             data-mobile="1.15">
            <div class="swiper-wrapper">
                <?php
                $args = array(
                    'post_type'           => 'post',
                    'post_status'         => 'publish',
                    'orderby'             => 'date',
                    'order'               => 'DESC',
                    'posts_per_page'      => '8',
                    'ignore_sticky_posts' => true
                );
                $loop = new WP_Query($args);
                if ($loop->have_posts()) :
                    while ($loop->have_posts()) : $loop->the_post(); ?>
                        <div class="swiper-slide h-auto">
                            <?php get_template_part('template-parts/cards/title-under-image'); ?>
                        </div>
                    <?php endwhile;
                endif;
                wp_reset_postdata(); ?>
            </div>

            <!-- Swiper Controls -->
            <div class="flex items-center justify-center gap-4 mt-10">
                <!-- Prev Button (pointing right in RTL) -->
                <button class="blog-prev-blog p-2.5 text-slate-700 hover:text-white bg-white hover:bg-secondary border border-slate-200 rounded-full transition-all cursor-pointer shadow-2xs" aria-label="Previous Slide">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z"/>
                    </svg>
                </button>
                <div class="swiper-pagination blog-pagination-blog flex gap-1.5 items-center"></div>
                <!-- Next Button (pointing left in RTL) -->
                <button class="blog-next-blog p-2.5 text-slate-700 hover:text-white bg-white hover:bg-secondary border border-slate-200 rounded-full transition-all cursor-pointer shadow-2xs" aria-label="Next Slide">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M11.354 1.646a.5.5 0 0 1 0 .708L5.707 8l5.647 5.646a.5.5 0 0 1-.708.708l-6-6a.5.5 0 0 1 0-.708l6-6a.5.5 0 0 1 .708 0z"/>
                    </svg>
                </button>
            </div>
        </div>

        <div class="text-center mt-12">
            <a href="<?= esc_url(get_permalink(get_option('page_for_posts')) ?: home_url('/blog')); ?>" 
               class="inline-flex items-center gap-2 bg-white hover:bg-slate-100 text-slate-800 hover:text-secondary border border-slate-200 font-bold text-sm px-6 py-3 rounded-full transition-all duration-300 shadow-2xs">
                <span>مشاهده همه مقالات</span>
                <span>&larr;</span>
            </a>
        </div>
    </div>
</section>
