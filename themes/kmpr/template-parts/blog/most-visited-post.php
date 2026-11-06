<?php
/**
 * Most Visited Post Slider
 */
?>
<section class="py-16 bg-slate-50 border-y border-slate-200/60 overflow-hidden">
    <div class="container mx-auto px-6">
        <div class="text-center max-w-2xl mx-auto mb-12">
            <h4 class="text-2xl lg:text-3xl font-black text-slate-800 mb-4">مقالات پیشنهادی</h4>
            <div class="h-1.5 w-12 bg-secondary mx-auto rounded-full"></div>
        </div>

        <div class="swiper post-slider w-full !overflow-visible cursor-grab active:cursor-grabbing"
             data-index="most-visited" 
             data-perfix="visited" 
             data-space="24" 
             data-loop="1" 
             data-perpage="3" 
             data-laptop="3" 
             data-tablet="2" 
             data-mobile="1.15">
            <div class="swiper-wrapper">
                <?php
                $args = array(
                    'post_type' => 'post',
                    'post_status' => 'publish',
                    'orderby' => 'meta_value_num',
                    'meta_key' => 'post_views',
                    'order' => 'DESC',
                    'posts_per_page' => '8',
                    'ignore_sticky_posts' => true
                );
                $loop = new WP_Query($args);
                if ($loop->have_posts()) :
                    while ($loop->have_posts()) : $loop->the_post(); ?>
                        <div class="swiper-slide h-auto">
                            <?php get_template_part('template-parts/cards/title-on-image'); ?>
                        </div>
                    <?php endwhile;
                endif;
                wp_reset_postdata(); ?>
            </div>
            
            <!-- Custom Swiper Controls (RTL direction-aware) -->
            <div class="flex items-center justify-center gap-4 mt-8">
                <!-- Prev Button (pointing right in RTL) -->
                <button class="visited-prev-most-visited p-2 text-slate-700 hover:text-secondary hover:bg-slate-100 rounded-full transition-all cursor-pointer" aria-label="Previous Slide">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z"/>
                    </svg>
                </button>
                
                <!-- Pagination Bullets -->
                <div class="swiper-pagination visited-pagination-most-visited flex gap-1 items-center"></div>

                <!-- Next Button (pointing left in RTL) -->
                <button class="visited-next-most-visited p-2 text-slate-700 hover:text-secondary hover:bg-slate-100 rounded-full transition-all cursor-pointer" aria-label="Next Slide">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M11.354 1.646a.5.5 0 0 1 0 .708L5.707 8l5.647 5.646a.5.5 0 0 1-.708.708l-6-6a.5.5 0 0 1 0-.708l6-6a.5.5 0 0 1 .708 0z"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</section>