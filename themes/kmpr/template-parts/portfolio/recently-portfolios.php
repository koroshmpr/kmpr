<section class="container py-4 px-0">
    <div class="row justify-content-center">
        <div class="d-flex justify-content-center align-items-center">
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
        <div class="portfolio-main swiper py-lg-5 pb-5 pt-3 col-11 col-lg-12">
            <div class="swiper-wrapper">
                <?php while ($loop->have_posts()) :
                    $loop->the_post(); ?>
                    <div class="swiper-slide rounded-3 overflow-hidden">
                        <?php
                        $customClass = 'shadow-sm rounded-3';
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
                <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="currentColor" class="bi bi-arrow-left-circle-fill text-primary ms-5" viewBox="0 0 16 16">
                    <path d="M8 0a8 8 0 1 0 0 16A8 8 0 0 0 8 0zm3.5 7.5a.5.5 0 0 1 0 1H5.707l2.147 2.146a.5.5 0 0 1-.708.708l-3-3a.5.5 0 0 1 0-.708l3-3a.5.5 0 1 1 .708.708L5.707 7.5H11.5z"/>
                </svg>
            </div>
            <div class="portfolio-button-prev position-absolute top-50 z-3 d-none d-lg-inline">
                <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="currentColor" class="bi bi-arrow-right-circle-fill text-primary" viewBox="0 0 16 16">
                    <path d="M8 0a8 8 0 1 1 0 16A8 8 0 0 1 8 0zM4.5 7.5a.5.5 0 0 0 0 1h5.793l-2.147 2.146a.5.5 0 0 0 .708.708l3-3a.5.5 0 0 0 0-.708l-3-3a.5.5 0 1 0-.708.708L10.293 7.5H4.5z"/>
                </svg>
            </div>
            <div class="portfolio-pagination position-static w-auto text-center pt-3"></div>
        </div>
    </div>
</section>