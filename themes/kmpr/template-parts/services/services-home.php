<section class="container py-5">
    <div class="row justify-content-center g-3 g-lg-2">
        <h4 class="fw-bold fs-2 text-center">خدمات ما</h4>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-2 hero-section">
            <?php
            $args = array(
                'post_type' => 'services',
                'post_status' => 'publish',
                'order' => 'DESC',
                'posts_per_page' => '3',
                'ignore_sticky_posts' => true
            );
            $loop = new WP_Query($args);
            if ($loop->have_posts()) :
                $i = 0;
                /* Start the Loop */
                ?>
                <?php while ($loop->have_posts()) :
                $loop->the_post(); ?>
                <div data-aos="zoom-out">
                    <?php get_template_part('template-parts/services/services-card'); ?>
                </div>
            <?php
            endwhile;
            endif;
            wp_reset_postdata(); ?>
        </div>
    </div>
</section>