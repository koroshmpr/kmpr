<?php
/** Template Name: Blog Page */

get_header(); ?>

<?php
$title = 'مقالات و اخبار';
$args = array(
    'title' => $title,
);
get_template_part('template-parts/loop/page-title', null, $args); ?>

<!-- Main Grid Section -->
<section class="container mx-auto px-6 py-8 lg:py-16 text-start">
    <!-- Top 3 Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
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
            while ($loop->have_posts()) :
                $loop->the_post(); ?>
                <div data-aos="fade-up" class="h-full">
                    <?php get_template_part('template-parts/cards/title-on-image'); ?>
                </div>
            <?php
            endwhile;
        endif;
        wp_reset_postdata(); ?>
    </div>
    
    <!-- Next 3 Side Images List -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 pt-8 mt-8 border-t border-slate-100">
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
            while ($loop->have_posts()) :
                $loop->the_post();  
                get_template_part('template-parts/cards/title-side-image');
            endwhile;
        endif;
        wp_reset_postdata(); ?>
    </div>
</section>

<?php
// most visited post slider
get_template_part('template-parts/blog/most-visited-post');

// recent added post layout with sidebar
get_template_part('template-parts/blog/post-loop-sidebar');
?>

<?php get_footer(); ?>