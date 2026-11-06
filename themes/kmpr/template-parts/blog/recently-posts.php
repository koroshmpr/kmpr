<?php
/**
 * Recently published blog articles
 */
?>
<div class="w-full">
    <div class="border-b border-slate-200/80 pb-3 mb-6 flex justify-between items-center">
        <h4 class="text-xl lg:text-2xl font-black text-slate-800">آخرین مقالات</h4>
    </div>
    
    <?php
    $args = array(
        'post_type' => 'post',
        'post_status' => 'publish',
        'order' => 'DESC',
        'posts_per_page' => '-1',
        'ignore_sticky_posts' => true
    );
    $loop = new WP_Query($args);
    if ($loop->have_posts()) :
    ?>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <?php while ($loop->have_posts()) : $loop->the_post(); ?>
            <article data-aos="fade-up" class="h-full">
                <?php get_template_part('template-parts/cards/title-under-image'); ?>
            </article>
        <?php endwhile; ?>
    </div>
    <?php
    endif;
    wp_reset_postdata(); ?>
</div>