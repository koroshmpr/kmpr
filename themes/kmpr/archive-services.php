<?php
/** Template Name: services archive */
get_header(); ?>
    <section class="container my-5 hero-section">
                <h1 class="fw-bold">خدمات</h1>
        <div class="row row-cols-lg-3 g-3 row-cols-1 pt-3 pt-lg-0 justify-content-lg-start justify-content-center">
            <?php
            $args = array(
                'post_type' => 'services',
                'post_status' => 'publish',
                'order' => 'DESC',
                'posts_per_page' => '-1',
                'ignore_sticky_posts' => true
            );
            $loop = new WP_Query($args);
            if ($loop->have_posts()) :
                $i = 0;
                $j = 0;
                /* Start the Loop */
                while ($loop->have_posts()) :
                    $loop->the_post(); ?>
                    <div data-aos="flip-down" data-aos-delay="<?= $j; ?>0"
                         data-aos-anchor-placement="top" >
                        <?php get_template_part('template-parts/services/services-card'); ?>
                    </div>
                <?php
                 $j+= 20;
                endwhile;
            endif;
            wp_reset_postdata(); ?>
        </div>
    </section>
<?php get_footer(); ?>