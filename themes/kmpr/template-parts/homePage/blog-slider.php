<section class="container-fluid position-relative overflow-visible bg-secondary p-0">
    <div class="container py-5 hero-section position-relative p-0">
        <div class="text-center pb-3">
            <?php while (have_rows('cta_title')):
                the_row(); ?>
                <h2 class="mb-2 fs-3 text-white"><?= get_sub_field('first_part_cta'); ?>
                    <span class="fw-bold position-relative fs-2">
                    <?= get_sub_field('underline_word_cta'); ?>
                    <span class="position-absolute top-50 pt-4 start-50 translate-middle">
                        <?php
                        $mainClass = "fill-primary";
                        $args = array (
                            'mainClass' => $mainClass
                        );
                        get_template_part('template-parts/svg/word-underline-secondary' , null, $args); ?></span>
                    </span>
                </h2>
                <p><?= get_sub_field('short_description_cta'); ?></p>
            <?php
            endwhile; ?>
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
                        <?php get_template_part('template-parts/cards/title-under-image'); ?>
                    </div>
                <?php
            endwhile;
            endif;
            wp_reset_postdata(); ?>
            </div>
        </div>
        <div class="swiper-button-next text-white">
            <i class="bi bi-arrow-right-circle-fill fs-1 text-primary "></i>
        </div>
        <div class="swiper-button-prev">
            <i class="bi bi-arrow-left-circle-fill fs-1 text-primary"></i>
        </div>
    </div>
</section>