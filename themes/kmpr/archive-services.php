<?php
/** Template Name: services archive */
get_header(); ?>
<?php
$title = 'خدمات ما';
$args = array(
    'title' => $title,
);
get_template_part('template-parts/loop/page-title', null, $args); ?>

<section class="container mx-auto px-6 py-12">
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
        $j = 0;
    ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <?php while ($loop->have_posts()) : $loop->the_post(); ?>
            <div data-aos="flip-down" data-aos-delay="<?= $j; ?>" data-aos-anchor-placement="top" class="h-full">
                <?php get_template_part('template-parts/services/services-card'); ?>
            </div>
        <?php 
        $j += 100;
        endwhile; ?>
    </div>
    <?php endif;
    wp_reset_postdata(); ?>
</section>

<?php get_footer(); ?>