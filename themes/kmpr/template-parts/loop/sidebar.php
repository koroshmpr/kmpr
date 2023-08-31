<div class="row justify-content-center g-3 g-lg-2 mx-1 mx-lg-0">
    <h4 class="fw-bold">محبوب ترین مقالات</h4>
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-1 g-2">
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
            $i = 0;
            /* Start the Loop */
            ?>
            <?php while ($loop->have_posts()) :
            $loop->the_post(); ?>
            <?php get_template_part('template-parts/cards/title-side-image'); ?>
        <?php
        endwhile;
        endif;
        wp_reset_postdata(); ?>
    </div>
</div>
<div class="my-5">
    <h4 class="fw-bold">مقالات پیشنهادی</h4>
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-1 g-2 hero-section">
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
        $i = 0;
        /* Start the Loop */
        ?>
        <?php while ($loop->have_posts()) :
        $loop->the_post(); ?>
        <?php get_template_part('template-parts/cards/title-on-image'); ?>
    <?php
    endwhile;
    endif;
    wp_reset_postdata(); ?>
    </div>
</div>