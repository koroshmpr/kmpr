<?php
/**
 * Modern blog post sidebar
 */
$current_post_id = get_the_ID();
?>
<div class="flex flex-col gap-6 text-start">
    <!-- Recent Posts -->
    <div>
        <h4 class="font-extrabold text-slate-800 text-sm mb-4">آخرین مقالات</h4>
        <div class="flex flex-col gap-3">
            <?php
            $args = array(
                'post_type' => 'post',
                'post_status' => 'publish',
                'order' => 'DESC',
                'posts_per_page' => '3',
                'ignore_sticky_posts' => true,
                'post__not_in' => array($current_post_id)
            );
            $loop = new WP_Query($args);
            if ($loop->have_posts()) :
                while ($loop->have_posts()) : $loop->the_post();
                    get_template_part('template-parts/cards/title-side-image');
                endwhile;
            endif;
            wp_reset_postdata(); ?>
        </div>
    </div>

    <!-- Most Visited Posts -->
    <div>
        <h4 class="font-extrabold text-slate-800 text-sm mb-4">پربازدیدترین مقالات</h4>
        <div class="flex flex-col gap-3">
            <?php
            $args2 = array(
                'post_type' => 'post',
                'post_status' => 'publish',
                'orderby' => 'meta_value_num',
                'meta_key' => 'post_views',
                'order' => 'DESC',
                'posts_per_page' => '3',
                'ignore_sticky_posts' => true,
                'post__not_in' => array($current_post_id)
            );
            $loop2 = new WP_Query($args2);
            if ($loop2->have_posts()) :
                while ($loop2->have_posts()) : $loop2->the_post();
                    get_template_part('template-parts/cards/title-side-image');
                endwhile;
            endif;
            wp_reset_postdata(); ?>
        </div>
    </div>

    <!-- Recommended post -->
    <?php
    $categories = wp_get_post_categories($current_post_id);
    if ($categories) {
        $args = array(
            'category__in' => $categories,
            'post__not_in' => array($current_post_id),
            'posts_per_page' => 1,
            'ignore_sticky_posts' => true
        );
        $related_query = new WP_Query($args);
        if ($related_query->have_posts()) :
            ?>
            <div>
                <h4 class="font-extrabold text-slate-800 text-sm mb-4">مقاله پیشنهادی</h4>
                <?php while ($related_query->have_posts()) : $related_query->the_post(); ?>
                    <?php get_template_part('template-parts/cards/title-on-image'); ?>
                <?php endwhile; ?>
            </div>
            <?php
        endif;
        wp_reset_postdata();
    }
    ?>
</div>
