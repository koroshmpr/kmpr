<?php
/** Template Name: portfolio archive */

get_header(); ?>
<?php
$title = 'نمونه کارها';
$args = array(
    'title' => $title,
);
get_template_part('template-parts/loop/page-title', null, $args); ?>

<div class="container mx-auto px-6 py-12">
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
    ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <?php while ($loop->have_posts()) : $loop->the_post(); ?>
            <div data-aos="zoom-in" data-aos-anchor-placement="top" class="h-full">
                <?php
                get_template_part('template-parts/portfolio/portfolio-card', null, array(
                    'class' => 'h-full'
                )); 
                ?>
            </div>
        <?php endwhile; ?>
    </div>
    <?php
    endif;
    wp_reset_postdata(); ?>
</div>

<?php get_footer(); ?>