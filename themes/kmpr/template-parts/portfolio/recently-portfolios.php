<section class="container py-4 px-0">
    <div class="row justify-content-center">
        <div class="d-flex justify-content-center align-items-center pb-3">
            <div class="col-11 col-lg-8 py-lg-3 pt-1 pb-4">
                <div class="mb-0 fs-5 text-center">
                    <?php while (have_rows('portfolio_title')):
                        the_row(); ?>
                        <?= get_sub_field('first_part'); ?>
                        <span class="fw-bold position-relative fs-4">
                        <?= get_sub_field('underline_word'); ?>
                    <span class="position-absolute top-50 pt-4 start-50 translate-middle">
                            <?php
                            $mainClass = "fill-secondary";
                            $args = array(
                                'mainClass' => $mainClass
                            );
                            get_template_part('template-parts/svg/word-underline-secondary', null, $args); ?></span>
                    </span>
                        <?= get_sub_field('second_part'); ?>
                    <?php
                    endwhile; ?>
                </div>
            </div>
        </div>
        <?php
        $args = array(
            'post_type' => 'portfolio',
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
        <div class="portfolio-main swiper py-5 col-11 col-lg-12">
            <div class="swiper-wrapper">
                <?php while ($loop->have_posts()) :
                    $loop->the_post(); ?>
                    <div class="swiper-slide">
                        <?php
                        $customClass = 'border border-primary border-opacity-10';
                        $args = array(
                            'class' => $customClass
                        );
                        get_template_part('template-parts/portfolio/portfolio-card', null, $args); ?>
                    </div>
                <?php
                endwhile;
                endif;
                wp_reset_postdata(); ?>
            </div>
            <div class="portfolio-button-next position-absolute top-50 end-0 z-3 d-none d-lg-inline">
                <i class="bi bi-arrow-left-circle-fill fs-1 text-primary ms-5"></i>
            </div>
            <div class="portfolio-button-prev position-absolute top-50 z-3 d-none d-lg-inline">
                <i class="bi bi-arrow-right-circle-fill fs-1 text-primary"></i>
            </div>
            <div class="portfolio-pagination position-static w-auto text-center"></div>
        </div>
    </div>
</section>