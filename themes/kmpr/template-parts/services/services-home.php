<?php
/**
 * Services list for homepage
 */
?>
<section id="services" class="py-16 lg:py-24 bg-slate-50">
    <div class="container mx-auto px-6">
        <div class="text-center max-w-xl mx-auto mb-12">
            <h4 class="text-3xl lg:text-4xl font-extrabold text-slate-900 mb-4">خدمات ما</h4>
            <div class="h-1.5 w-16 bg-secondary mx-auto rounded-full"></div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php
            $args = array(
                'post_type' => 'services',
                'post_status' => 'publish',
                'order' => 'DESC',
                'posts_per_page' => '3',
                'ignore_sticky_posts' => true
            );
            $loop = new WP_Query($args);
            if ($loop->have_posts()) :
                while ($loop->have_posts()) : $loop->the_post(); ?>
                    <div data-aos="zoom-out" data-aos-duration="600">
                        <?php get_template_part('template-parts/services/services-card'); ?>
                    </div>
                <?php endwhile;
            endif;
            wp_reset_postdata(); ?>
        </div>
    </div>
</section>