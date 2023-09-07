<?php
/** Template Name: portfolio archive */

get_header(); ?>
<?php
$title = 'نمونه کارها';
$args = array(
    'title' => $title,
);
get_template_part('template-parts/loop/page-title', null, $args); ?>
<div class="container py-5">

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
        <div data-aos="zoom-in" data-aos-delay="<?= $j; ?>0"
             data-aos-anchor-placement="top">
            <?php
            $customClass = 'shadow-sm rounded-3';
            $footerContainer = 'pb-4';
            $args = array (
                'class' => $customClass,
                'footerContainer' => $footerContainer
            );
            get_template_part('template-parts/portfolio/portfolio-card', null , $args); ?>
        </div>
        <?php
        $j+= 20;
        endwhile;
        endif;
        wp_reset_postdata(); ?>
    </div>
</div>
<?php get_footer(); ?>