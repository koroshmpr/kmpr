<?php get_header(); ?>

<section class="container py-5 hero-section">
    <h1 class="fw-bold"><?php single_cat_title(); ?></h1>
    <div class="row row-cols-1 row-cols-lg-3 justify-content-lg-start g-4">
<?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>

    <article class="p-3">
        <?php get_template_part('template-parts/cards/title-on-image'); ?>
    </article>

<?php endwhile; else : ?>

    <p class="py-4 fw-bold fs-5"><?php _e( 'با عرض پوزش، هیچ پستی در این دسته یافت نشد.' ); ?></p>

<?php endif; ?>
    </div>
</section>
<?php get_footer() ?>