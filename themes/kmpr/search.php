<?php get_header(); ?>

<div class="container py-5">
    <div class="w-100 mb-5 mx-auto">
        <!-- Your search form here -->
    </div>

    <div class="d-block d-lg-flex align-items-center">
        نتیجه جستجو برای :
        <h1 class="fw-bold ms-3"> <?= the_search_query(); ?> </h1>
    </div>

    <?php
    $post_types = array('post', 'portfolio', 'services');

    foreach ($post_types as $post_type) {
        $post_type_label = ucfirst($post_type); // Capitalize the post type label

        // Create a new WP_Query for the current post type
        $post_type_query = new WP_Query(array(
            'post_type' => $post_type,
            's' => get_search_query(),
        ));

        if ($post_type_query->have_posts()) {
            $post_type_info = get_post_type_object($post_type); // Get the post type object
            $post_type_label = $post_type_info->labels->name; // Get the custom post type name
            echo '<h2 class="mt-5 mb-3"> جستحو در ' . $post_type_label . '</h2>';
            echo '<div class="row my-2 row-cols-lg-3 justify-content-lg-between row-cols-1">';

            while ($post_type_query->have_posts()) {
                $post_type_query->the_post();
                $current_post_type = get_post_type();

                if ($current_post_type == 'services') {
                    get_template_part('template-parts/services/services-card');
                } elseif ($current_post_type == 'post') {
                    get_template_part('template-parts/cards/title-under-image');
                }elseif ($current_post_type == 'portfolio') {
                    get_template_part('template-parts/portfolio/portfolio-card');
                }
            }

            echo '</div>';
        }


        wp_reset_postdata(); // Reset the post data for each post type
    }

    $links = paginate_links(array(
        'type' => 'array',
        'prev_next' => false,
    ));

    if ($links) : ?>
        <nav aria-label="Page navigation example">
            <?php
            echo '<ul class="pagination justify-content-center align-items-center gap-3">';
            if ($prev_posts_link = get_previous_posts_link(__('قبلی'))) :
                echo '<li class="prev-list-item page-item me-4 bg-primary py-2 px-3 rounded text-white">';
                echo $prev_posts_link;
                echo '</li>';
            endif;

            echo '<li class="page-item me-4 ">';
            echo join('</li><li class="page-item me-4">', $links);
            echo '</li>';

            if ($next_posts_link = get_next_posts_link(__('بعدی'))) :
                echo '<li class="next-list-item page-item me-4 bg-primary py-2 px-3 rounded text-white">';
                echo $next_posts_link;
                echo '</li>';
            endif;

            echo '</ul>';
            ?>
        </nav>
    <?php endif; ?>

</div>

<?php get_footer(); ?>
