<?php
/** Template Name: Blog Page */

get_header(); ?>
<?php
$title = 'مقالات';
$args = array(
    'title' => $title,
);
get_template_part('template-parts/loop/page-title', null, $args); ?>
    <section class="container pt-lg-5 pb-5 hero-section">
        <div class="row row-cols-lg-3 g-3 row-cols-1 pt-3 pt-lg-0 justify-content-lg-between justify-content-center">
            <?php
            $args = array(
                'post_type' => 'post',
                'post_status' => 'publish',
                'order' => 'DESC',
                'posts_per_page' => '3',
                'ignore_sticky_posts' => true
            );
            $loop = new WP_Query($args);
            if ($loop->have_posts()) :
                $i = 0;
                /* Start the Loop */
                while ($loop->have_posts()) :
                    $loop->the_post(); ?>
                    <div data-aos="fade-up" >
                        <?php get_template_part('template-parts/cards/title-on-image'); ?>
                    </div>
                <?php
                endwhile;
            endif;
            wp_reset_postdata(); ?>
        </div>
        <div class="row row-cols-lg-3 row-cols-md-2 row-cols-1 px-3 px-lg-0 gx-5 gy-2 g-lg-0 justify-content-lg-between justify-content-center pt-4">
            <?php
            $args2 = array(
                'post_type' => 'post',
                'post_status' => 'publish',
                'order' => 'DESC',
                'posts_per_page' => '3',
                'ignore_sticky_posts' => true,
                'offset' => 3
            );
            $loop = new WP_Query($args2);
            if ($loop->have_posts()) :
                $i = 0;
                /* Start the Loop */
                while ($loop->have_posts()) :
                    $loop->the_post();  get_template_part('template-parts/cards/title-side-image');
                endwhile;
            endif;
            wp_reset_postdata(); ?>
        </div>
    </section>
        <?php
        /// most visited post
        get_template_part('template-parts/blog/most-visited-post');
        // recetly added post
        get_template_part('template-parts/blog/post-loop-sidebar');
        ?>
<?php get_footer(); ?>