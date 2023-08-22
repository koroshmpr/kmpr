<div class="row gap-2 gap-lg-0">
    <!--                table of content-->
    <div class="pb-4">
        <h3 class="fw-bold py-3">سر تیترها</h3>
        <div class="d-flex align-items-center">
            <?php echo do_shortcode('[TOC]') ?>
        </div>
    </div>
    <h4 class="fw-bold mb-0 px-0">آخرین مقالات</h4>
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-1 g-2">
    <?php
    global $post;
    // Get the ID of the current post
    $current_post_id = $post->ID;
    $args = array(
        'post_type' => 'post',
        'post_status' => 'publish',
        'order' => 'DESC',
        'posts_per_page' => '3',
        'ignore_sticky_posts' => true,
        'post__not_in' => array( $current_post_id )
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
<div class="row gap-2 gap-lg-0 mt-5">
    <h4 class="fw-bold mb-0 px-0">پر بازدیدترین مقالات</h4>
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-1 g-2">
    <?php
    $args2 = array(
        'post_type' => 'post',
        'post_status' => 'publish',
        'orderby' => 'meta_value_num',
        'meta_key' => 'post_views',
        'order' => 'DESC',
        'posts_per_page' => '3',
        'ignore_sticky_posts' => true,
        'post__not_in' => array( $current_post_id )
    );
    $loop2 = new WP_Query($args2);
    if ($loop2->have_posts()) :
        $i = 0;
        /* Start the Loop */
        ?>
        <?php while ($loop2->have_posts()) :
        $loop2->the_post(); ?>
        <?php get_template_part('template-parts/cards/title-side-image'); ?>
    <?php
    endwhile;
    endif;
    wp_reset_postdata(); ?>
    </div>
</div>
<div class="mt-5 px-0">
    <h4 class="fw-bold hero-section">مقالات پیشنهادی</h4>
    <?php
    $categories = wp_get_post_categories($post->ID);
    if ($categories) {
        $category_ids = array();
        foreach ($categories as $category) {
            $category_ids[] = $category;
        }
        $args = array(
            'category__in' => $category_ids,
            'post__not_in' => array($post->ID),
            'posts_per_page' => 1,
            'ignore_sticky_posts' => true
        );
        $related_query = new WP_Query($args);
        if ($related_query->have_posts()) :
            while ($related_query->have_posts()) :
                $related_query->the_post();?>
            <div class="hero-section">
                <?php get_template_part('template-parts/cards/title-on-image');?>
            </div>
                <?php endwhile;
        endif;
        wp_reset_postdata();
    }
    ?>
</div>
