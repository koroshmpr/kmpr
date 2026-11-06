<?php get_header(); ?>

<?php
$title = single_cat_title('', false);
$args = array(
    'title' => $title,
);
get_template_part('template-parts/loop/page-title', null, $args); ?>

<section class="container mx-auto px-6 py-12 text-start">
    <?php if ( have_posts() ) : ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php while ( have_posts() ) : the_post(); ?>
                <div class="h-full">
                    <?php get_template_part('template-parts/cards/title-on-image'); ?>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else : ?>
        <p class="py-12 font-bold text-lg text-slate-500 text-center"><?php _e( 'با عرض پوزش، هیچ پستی در این دسته یافت نشد.' ); ?></p>
    <?php endif; ?>
</section>

<?php get_footer(); ?>