<?php get_header();

while (have_posts()) :
    the_post();
    ?>
    <!-- Hero Header -->
    <section class="relative min-h-[42vh] lg:min-h-[52vh] flex items-center overflow-hidden bg-slate-950 text-white select-none">
        <!-- Ambient Glow -->
        <div class="absolute top-1/3 -start-10 w-80 h-80 bg-secondary/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute bottom-10 end-10 w-80 h-80 bg-blue-600/15 rounded-full blur-[100px] pointer-events-none"></div>

        <img data-aos="fade-down" data-aos-duration="1000" 
             class="absolute inset-0 w-full h-full object-cover pointer-events-none opacity-25 select-none scale-105"
             src="<?= esc_url(get_the_post_thumbnail_url()); ?>"
             alt="<?= esc_attr(get_the_title()); ?>">
        <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/60 to-slate-950/40"></div>
        
        <div class="container mx-auto px-4 sm:px-6 relative z-10 text-center mt-10">
            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-white/10 border border-white/15 backdrop-blur-md mb-4">
                <span class="size-2 rounded-full bg-secondary animate-pulse"></span>
                <span class="text-xs font-black uppercase text-slate-200 tracking-wider">خدمات تخصصی</span>
            </div>
            <h1 class="text-3xl lg:text-5xl font-black text-white tracking-tight drop-shadow-lg max-w-3xl mx-auto leading-tight" data-aos="zoom-in">
                <?= get_the_title(); ?>
            </h1>
        </div>
    </section>

    <!-- Content Area -->
    <section class="container mx-auto px-4 sm:px-6 -mt-16 relative z-20 pb-16">
        <div class="bg-white border border-slate-200/80 rounded-3xl p-6 lg:p-12 shadow-xl max-w-4xl mx-auto text-start">
            <!-- Share buttons -->
            <div class="border-b border-slate-200/60 pb-6 mb-6">
                <?php
                get_template_part('template-parts/share-button', null, array(
                    'headingClass' => 'text-xs font-bold text-slate-500',
                    'class'        => 'gap-3 justify-start',
                ));
                ?>
            </div>
            
            <!-- Body content -->
            <article class="prose max-w-none text-slate-700 leading-loose text-justify text-base lg:text-lg">
                <?= the_content(); ?>
            </article>
        </div>
    </section>

    <!-- Proposal / Pricing Tiers -->
    <?php if (get_field('Proposal_switch') == 'yes') { ?>
        <section class="bg-slate-50 border-y border-slate-200/80 py-16 lg:py-24">
            <div class="container mx-auto px-4 sm:px-6">
                <div class="text-center max-w-2xl mx-auto mb-16">
                    <span class="text-xs font-black uppercase text-secondary tracking-widest block mb-2">تعرفه‌ها و پلن‌ها</span>
                    <h2 class="text-2xl lg:text-4xl font-black text-slate-900 mb-4">طرح‌ها و تعرفه‌ها</h2>
                    <div class="h-1.5 w-16 bg-secondary mx-auto rounded-full"></div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 max-w-6xl mx-auto items-stretch">
                    <?php
                    if (have_rows('proposal')) {
                        while (have_rows('proposal')): the_row();
                            $rowIndex = get_row_index();
                            $offer = get_sub_field('proposal_offer'); 
                            
                            // Define highlight classes for "best offer" tier
                            $card_bg = ($offer == 'yes') ? 'bg-slate-900 text-white scale-105 border-transparent ring-4 ring-secondary/30 shadow-2xl' : 'bg-white text-slate-800 border-slate-200/80 shadow-md';
                            $row_bg = ($offer == 'yes') ? 'bg-white/10 text-slate-100' : 'bg-slate-100 text-slate-700';
                            $title_color = ($offer == 'yes') ? 'text-secondary' : 'text-slate-900';
                            
                            $anim = '';
                            if (($rowIndex) == 1) {
                                $anim = 'data-aos="fade-right" data-aos-delay="100"';
                            } elseif (($rowIndex) == 2) {
                                $anim = 'data-aos="fade-up"';
                            } elseif (($rowIndex) == 3) {
                                $anim = 'data-aos="fade-left" data-aos-delay="100"';
                            }
                            ?>
                            <div <?= $anim; ?> class="rounded-3xl border p-8 flex flex-col justify-between text-start gap-6 transition-all duration-500 hover:shadow-2xl hover:-translate-y-1.5 <?= $card_bg; ?>">
                                <div class="text-center border-b border-slate-200/60 pb-6">
                                    <?php if ($offer == 'yes') : ?>
                                        <span class="inline-block bg-secondary text-white text-[10px] font-black uppercase px-3 py-1 rounded-full mb-3 shadow-xs">
                                            پیشنهاد ویژه
                                        </span>
                                    <?php endif; ?>
                                    <h3 class="text-xl lg:text-2xl font-black mb-2 <?= $title_color; ?>"><?= get_sub_field('proposal_title'); ?></h3>
                                    <?php if (get_sub_field('proposal_description')) { ?>
                                        <p class="text-xs opacity-80 mt-2 font-medium"><?= get_sub_field('proposal_description'); ?></p>
                                    <?php }; ?>
                                </div>
                                
                                <div class="flex flex-col gap-3 flex-1 justify-start">
                                    <?php while (have_rows('proposal_row')): the_row(); ?>
                                        <?php $propertyValue = get_sub_field('Property_value'); ?>
                                        <div class="flex justify-between items-center px-4 py-3 rounded-xl text-xs font-bold <?= $row_bg; ?>">
                                            <span class="opacity-90"><?= get_sub_field('Property_label'); ?></span>
                                            <?php if ($propertyValue) { ?>
                                                <span class="text-secondary font-black"><?= $propertyValue; ?></span>
                                            <?php } ?>
                                        </div>
                                    <?php endwhile; ?>
                                </div>

                                <div class="mt-6 text-center">
                                    <a href="#contact" class="inline-block w-full text-center py-4 rounded-2xl font-black text-xs sm:text-sm transition-all duration-300 <?= ($offer == 'yes') ? 'bg-secondary hover:bg-secondary-hover text-white shadow-lg shadow-secondary/30 hover:scale-[1.02]' : 'bg-primary hover:bg-slate-800 text-white shadow-md hover:scale-[1.02]'; ?>">
                                        سفارش و مشاوره رایگان
                                    </a>
                                </div>
                            </div>
                        <?php endwhile;
                    } ?>
                </div>
            </div>
        </section>
    <?php } ?>

    <!-- Related Portfolios Slider -->
    <section class="container mx-auto px-4 sm:px-6 py-16 overflow-hidden">
        <?php
        $current_service_id = get_the_ID();
        $args = array(
            'post_type'  => 'portfolio',
            'meta_query' => array(
                array(
                    'key'     => 'relation-service',
                    'value'   => '"' . $current_service_id . '"',
                    'compare' => 'LIKE'
                )
            )
        );

        $related_portfolios = new WP_Query($args);

        if ($related_portfolios->have_posts()):
            ?>
            <div class="flex items-center justify-between border-b border-slate-200/80 pb-4 mb-8">
                <div>
                    <span class="text-xs font-black uppercase text-secondary tracking-widest block mb-1">کارهای انجام شده</span>
                    <h3 class="text-2xl font-black text-slate-900 text-start">نمونه کارهای مرتبط با این خدمت</h3>
                </div>
            </div>
            
            <div class="swiper post-slider w-full !overflow-visible cursor-grab active:cursor-grabbing"
                 data-index="related-portfolios" 
                 data-perfix="relport" 
                 data-space="24" 
                 data-loop="1" 
                 data-perpage="3" 
                 data-laptop="3" 
                 data-tablet="2" 
                 data-mobile="1.15">
                <div class="swiper-wrapper">
                    <?php
                    while ($related_portfolios->have_posts()): $related_portfolios->the_post();
                        ?>
                        <div class="swiper-slide h-auto">
                            <?php
                            get_template_part('template-parts/portfolio/portfolio-card', null, array(
                                'class' => 'h-full'
                            ));
                            ?>
                        </div>
                    <?php endwhile; ?>
                </div>
                
                <!-- Swiper Controls -->
                <div class="flex items-center justify-center gap-4 mt-8">
                    <!-- Prev Button (pointing right in RTL) -->
                    <button class="relport-prev-related-portfolios p-2.5 text-slate-700 hover:text-white bg-white hover:bg-secondary border border-slate-200 rounded-full transition-all cursor-pointer shadow-2xs" aria-label="Previous Slide">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                            <path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z"/>
                        </svg>
                    </button>
                    <div class="swiper-pagination relport-pagination-related-portfolios flex gap-1.5 items-center"></div>
                    <!-- Next Button (pointing left in RTL) -->
                    <button class="relport-next-related-portfolios p-2.5 text-slate-700 hover:text-white bg-white hover:bg-secondary border border-slate-200 rounded-full transition-all cursor-pointer shadow-2xs" aria-label="Next Slide">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                            <path fill-rule="evenodd" d="M11.354 1.646a.5.5 0 0 1 0 .708L5.707 8l5.647 5.646a.5.5 0 0 1-.708.708l-6-6a.5.5 0 0 1 0-.708l6-6a.5.5 0 0 1 .708 0z"/>
                        </svg>
                    </button>
                </div>
            </div>
        <?php
        endif;
        wp_reset_postdata();
        ?>
    </section>

    <!-- Other Services Carousel -->
    <section class="container mx-auto px-4 sm:px-6 py-16 overflow-hidden border-t border-slate-200/80">
        <div class="flex items-center justify-between pb-4 mb-8">
            <div>
                <span class="text-xs font-black uppercase text-secondary tracking-widest block mb-1">دیگر خدمات</span>
                <h3 class="text-2xl font-black text-slate-900 text-start">سایر خدمات مجموعه</h3>
            </div>
        </div>
        
        <div class="swiper post-slider w-full !overflow-visible cursor-grab active:cursor-grabbing"
             data-index="other-services" 
             data-perfix="services" 
             data-space="24" 
             data-loop="1" 
             data-perpage="3" 
             data-laptop="3" 
             data-tablet="2" 
             data-mobile="1.15">
            <div class="swiper-wrapper">
                <?php
                global $post;
                $current_post_id = $post->ID;
                $args = array(
                    'post_type'           => 'services',
                    'post_status'         => 'publish',
                    'order'               => 'DESC',
                    'posts_per_page'      => -1,
                    'ignore_sticky_posts' => true,
                    'post__not_in'        => array($current_post_id)
                );
                $loop = new WP_Query($args);
                if ($loop->have_posts()) :
                    while ($loop->have_posts()) : $loop->the_post(); ?>
                        <div class="swiper-slide h-auto">
                            <?php get_template_part('template-parts/services/services-card'); ?>
                        </div>
                    <?php endwhile;
                endif;
                wp_reset_postdata(); ?>
            </div>
            
            <!-- Swiper Controls -->
            <div class="flex items-center justify-center gap-4 mt-8">
                <!-- Prev Button (pointing right in RTL) -->
                <button class="services-prev-other-services p-2.5 text-slate-700 hover:text-white bg-white hover:bg-secondary border border-slate-200 rounded-full transition-all cursor-pointer shadow-2xs" aria-label="Previous Slide">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z"/>
                    </svg>
                </button>
                <div class="swiper-pagination services-pagination-other-services flex gap-1.5 items-center"></div>
                <!-- Next Button (pointing left in RTL) -->
                <button class="services-next-other-services p-2.5 text-slate-700 hover:text-white bg-white hover:bg-secondary border border-slate-200 rounded-full transition-all cursor-pointer shadow-2xs" aria-label="Next Slide">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M11.354 1.646a.5.5 0 0 1 0 .708L5.707 8l5.647 5.646a.5.5 0 0 1-.708.708l-6-6a.5.5 0 0 1 0-.708l6-6a.5.5 0 0 1 .708 0z"/>
                    </svg>
                </button>
            </div>
        </div>
    </section>

    <!-- Gravity Form Contact Section -->
    <section id="contact" class="bg-slate-50 py-16 lg:py-24 border-t border-slate-200/80">
        <div class="container mx-auto px-4 sm:px-6 max-w-xl text-start">
            <span class="text-xs font-black uppercase text-secondary tracking-widest block text-center mb-2">ثبت درخواست پروژه</span>
            <h4 class="text-2xl sm:text-3xl font-black text-slate-900 mb-6 text-center">مشاوره رایگان این خدمت</h4>
            <div class="bg-white border border-slate-200/80 rounded-3xl p-6 sm:p-8 shadow-md">
                <?php 
                echo do_shortcode('[gravityform id="' . get_field('form-id', 'option') . '" title="false" description="false" ajax="true"]');
                ?>
            </div>
        </div>
    </section>
<?php endwhile;
wp_reset_query();
get_footer(); ?>
