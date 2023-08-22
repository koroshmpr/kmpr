<?php
/** Template Name: portfolio archive */

get_header(); ?>
<div class="container pb-5">
    <div class="d-flex justify-content-between align-items-center py-5">
        <div>
            <h1 class="mb-0 fw-bold">نمونه کارها</h1>
        </div>
    </div>
    <?php
    $args = array(
        'post_type' => 'portfolio',
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
    ?>
    <div class="row row-cols-lg-3 row-cols-md-2 row-cols-1 gy-5">
        <?php while ($loop->have_posts()) :
            $loop->the_post(); ?>
        <div data-aos="flip-up" data-aos-delay="<?= $j; ?>0"
             data-aos-anchor-placement="top" >
            <?php get_template_part('template-parts/portfolio/portfolio-card'); ?>
        </div>
        <?php
        $j+= 20;
        endwhile;
        endif;
        wp_reset_postdata(); ?>
    </div>
</div>
<?php get_footer(); ?>