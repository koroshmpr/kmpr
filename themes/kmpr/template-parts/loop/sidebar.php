<?php
/**
 * Sidebar Loop Widgets
 */
?>
<div class="flex flex-col gap-8 text-start w-full">
    <!-- Popular Posts Widget -->
    <div class="w-full">
        <h4 class="text-base font-black text-slate-800 border-b border-slate-200 pb-3 mb-4">محبوب‌ترین مقالات</h4>
        
        <div class="flex flex-col gap-3">
            <?php
            $args = array(
                'post_type' => 'post',
                'post_status' => 'publish',
                'order' => 'DESC',
                'posts_per_page' => '4',
                'ignore_sticky_posts' => true
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

    <!-- Suggested Post Widget -->
    <div class="w-full">
        <h4 class="text-base font-black text-slate-800 border-b border-slate-200 pb-3 mb-4">پیشنهاد سردبیر</h4>
        
        <div class="w-full">
            <?php
            $args2 = array(
                'post_type' => 'post',
                'post_status' => 'publish',
                'order' => 'DESC',
                'posts_per_page' => '1',
                'ignore_sticky_posts' => true
            );
            $loop = new WP_Query($args2);
            if ($loop->have_posts()) :
                while ($loop->have_posts()) : $loop->the_post();
                    get_template_part('template-parts/cards/title-on-image');
                endwhile;
            endif;
            wp_reset_postdata(); ?>
        </div>
    </div>
</div>