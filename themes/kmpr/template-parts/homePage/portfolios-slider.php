<?php
/**
 * Modern Portfolios Slider
 */
?>
<section id="portfolio" class="overflow-hidden bg-white border-y border-slate-200/80">
    <div class="grid grid-cols-1 lg:grid-cols-12 min-h-[520px]">
        <!-- Slide details column -->
        <div class="lg:col-span-5 flex flex-col justify-between p-6 sm:p-10 lg:p-16 relative bg-slate-50/80 order-2 lg:order-1 overflow-hidden">
            <div class="hero_swiper swiper w-full my-auto">
                <div class="swiper-wrapper">
                    <?php while (have_rows('hero-slider')): the_row();
                        $slide_img = get_sub_field('image');
                        $btn_link = get_sub_field('button_link');
                        ?>
                        <div class="swiper-slide text-start overflow-hidden flex flex-col items-start gap-4"
                             data-image="<?= esc_url($slide_img['url'] ?? ''); ?>">
                            
                            <?php if (get_sub_field('badge')) : ?>
                                <span class="inline-block bg-secondary/15 text-secondary border border-secondary/30 text-xs font-black px-4 py-1.5 rounded-full">
                                    <?= esc_html(get_sub_field('badge')); ?>
                                </span>
                            <?php endif; ?>

                            <h3 class="text-2xl sm:text-3xl lg:text-4xl font-black text-slate-900 leading-tight mt-1">
                                <?= esc_html(get_sub_field('title')); ?>
                            </h3>

                            <p class="text-slate-600 text-sm lg:text-base leading-relaxed mt-1 max-w-md">
                                <?= esc_html(wp_trim_words(get_sub_field('content'), 24)); ?>
                            </p>

                            <?php if ($btn_link && !empty($btn_link['url'])) : ?>
                                <a href="<?= esc_url($btn_link['url']); ?>"
                                   class="inline-flex items-center gap-2 bg-primary text-white font-bold text-sm px-7 py-3 rounded-full hover:bg-secondary hover:text-white transition-all duration-300 shadow-md mt-3">
                                    <span>مشاهده و ادامه</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                                    </svg>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>

            <!-- Navigation & Pagination controls -->
            <div class="flex items-center justify-between sm:justify-start gap-4 mt-8 border-t border-slate-200/80 pt-5">
                <!-- Prev Button (pointing right in RTL) -->
                <button class="hero-prev p-2.5 text-slate-700 hover:text-white bg-white hover:bg-secondary border border-slate-200 rounded-full transition-all cursor-pointer shadow-2xs" aria-label="Previous Slide">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z"/>
                    </svg>
                </button>
                
                <!-- Pagination Bullets -->
                <div class="hero-pagination flex gap-1.5 items-center"></div>

                <!-- Next Button (pointing left in RTL) -->
                <button class="hero-next p-2.5 text-slate-700 hover:text-white bg-white hover:bg-secondary border border-slate-200 rounded-full transition-all cursor-pointer shadow-2xs" aria-label="Next Slide">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M11.354 1.646a.5.5 0 0 1 0 .708L5.707 8l5.647 5.646a.5.5 0 0 1-.708.708l-6-6a.5.5 0 0 1 0-.708l6-6a.5.5 0 0 1 .708 0z"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Big Background Image Section (Updates on slide change) -->
        <div id="hero-section"
             class="lg:col-span-7 bg-cover bg-center transition-all duration-700 min-h-[300px] sm:min-h-[400px] lg:min-h-full order-1 lg:order-2 bg-slate-900"></div>
    </div>
</section>
