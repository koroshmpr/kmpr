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
                <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="currentColor" class="bi bi-arrow-left-circle-fill text-primary" viewBox="0 0 16 16">
                    <path d="M8 0a8 8 0 1 0 0 16A8 8 0 0 0 8 0zm3.5 7.5a.5.5 0 0 1 0 1H5.707l2.147 2.146a.5.5 0 0 1-.708.708l-3-3a.5.5 0 0 1 0-.708l3-3a.5.5 0 1 1 .708.708L5.707 7.5H11.5z"/>
                </svg>
            </div>
            <div class="swiper-button-prev post__nav" data-aos="fade-right">
                <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="currentColor" class="bi bi-arrow-right-circle-fill text-primary" viewBox="0 0 16 16">
                    <path d="M8 0a8 8 0 1 1 0 16A8 8 0 0 1 8 0zM4.5 7.5a.5.5 0 0 0 0 1h5.793l-2.147 2.146a.5.5 0 0 0 .708.708l3-3a.5.5 0 0 0 0-.708l-3-3a.5.5 0 1 0-.708.708L10.293 7.5H4.5z"/>
                </svg>
            </div>
        </div>
    </div>
</section>