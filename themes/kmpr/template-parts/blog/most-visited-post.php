<section class="py-5 hero-section bg-secondary">
    <div class="container">
        <div class="text-center pb-3">
            <h4 class="mb-2 fs-2 fw-bold">مقالات پیشنهادی</h4>
        </div>
        <div class="post_swiper swiper">
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
                    $i = 0;
                    /* Start the Loop */
                    ?>
                    <?php while ($loop->have_posts()) :
                    $loop->the_post(); ?>
                    <div class="swiper-slide">
                        <?php get_template_part('template-parts/cards/title-on-image'); ?>
                    </div>
                <?php
                endwhile;
                endif;
                wp_reset_postdata(); ?>
            </div>
            <div class="swiper-button-next post__nav" data-aos="fade-left">
                <i class="bi bi-arrow-left-circle-fill fs-1 text-white"></i>
            </div>
            <div class="swiper-button-prev post__nav" data-aos="fade-right">
                <i class="bi bi-arrow-right-circle-fill fs-1 text-white"></i>
            </div>
        </div>
    </div>
</section>