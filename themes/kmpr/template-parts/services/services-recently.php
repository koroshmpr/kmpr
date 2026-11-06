<section class="container-fluid position-relative overflow-visible">
    <div class="container py-5 position-relative">
        <div class="text-center pb-3">
            <?php while (have_rows('cta_title')):
                the_row(); ?>
                <div class="mb-2 fs-4"><?= get_sub_field('first_part_cta'); ?>
                    <span class="fw-bold position-relative fs-3">
                    <?= get_sub_field('underline_word_cta'); ?>
                    <span class="position-absolute top-50 pt-4 start-50 translate-middle">
                        <?php get_template_part('template-parts/svg/word-underline-secondary'); ?></span>
                    </span>
                </div>
                <p><?= get_sub_field('short_description_cta'); ?></p>
            <?php
            endwhile; ?>
        </div>
        <div class="post_swiper swiper">
            <div class="swiper-wrapper">
                <?php
        $args = array(
            'post_type' => 'services',
            'post_status' => 'publish',
            'order' => 'DESC',
            'posts_per_page' => $args['postCount'] ?? '6',
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
                       <?php get_template_part('template-parts/services/services-card');?>
                    </div>
                <?php
            endwhile;
            endif;
            wp_reset_postdata(); ?>
            </div>
        </div>
        <div class="swiper-button-next post__nav">
            <i class="bi bi-arrow-right-circle-fill fs-1 text-white"></i>
        </div>
        <div class="swiper-button-prev post__nav">
            <i class="bi bi-arrow-left-circle-fill fs-1 text-white"></i>
        </div>
    </div>
</section>