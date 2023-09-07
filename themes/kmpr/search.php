<?php get_header(); ?>

<section class="container py-3">
    <div class="w-100 pb-3">
        <?php
        $place = 'search-page';
        $sizeSearch = 'col';
        $inputClass = 'py-3 bg-white';
        $buttonClass = "px-4";
        $dropdownClass = 'px-3';
        $args = array(
            'place' => $place,
            'size' => $sizeSearch,
            'inputClass' => $inputClass,
            'buttonClass' => $buttonClass,
            'dropdownClass' => $dropdownClass
        );
        get_template_part('template-parts/search-bar', null, $args); ?>
    </div>
    <div class="d-grid column-gap-3 d-lg-flex align-items-center">
        نتیجه جستجو برای :
        <h1 class="fw-bold ms-3 mt-3 mt-lg-0"> <?= the_search_query(); ?> </h1>
    </div>
    <h2 class="mt-lg-5 my-3 pb-3 text-center text-lg-start border-bottom border-2 border-primary border-opacity-50">
        <?php
        // Get the selected post type from the URL query parameters
        $post_type = isset($_GET['post_type']) ? sanitize_text_field($_GET['post_type']) : '';
        // Check if a post type filter is applied
        if (!empty($post_type)) {
            $post_type_info = get_post_type_object($post_type);
            $post_type_label = $post_type_info->labels->name;
            echo ' جستحو در ' . $post_type_label;
        } else {
            echo ' نتایج جستجو کلی';
        }
        ?> </h2>
    <?php
    // Create a new WP_Query for the current post type (if filter is applied)
    $args = array(
        'post_type' => $post_type ? $post_type : array('post', 'portfolio', 'services'),
        's' => get_search_query(),
    );
    $post_type_query = new WP_Query($args);
    if ($post_type_query->have_posts()) {
        echo '<div class="row m-0 row-cols-lg-3 justify-content-lg-between justify-content-center row-cols-1 gy-5">';

        while ($post_type_query->have_posts()) {
            $post_type_query->the_post();
            $current_post_type = get_post_type();
            if ($current_post_type == 'services') { get_template_part('template-parts/services/services-card');}
            elseif ($current_post_type == 'post') { get_template_part('template-parts/cards/title-under-image');}
            elseif ($current_post_type == 'portfolio') { get_template_part('template-parts/portfolio/portfolio-card');}
        }
        echo '</div>';
    } else {
        echo '<p class="fs-2">موردی یافت نشد !</p>';
    }
    wp_reset_postdata(); // Reset the post data
    ?>
    <div class="pagination d-flex py-2 justify-content-center gap-3 align-items-center">
        <?php
        global $wp_query;
        $big = 999999999; // need an unlikely integer
        echo paginate_links(array(
            'base' => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
            'format' => '?paged=%#%',
            'current' => max(1, get_query_var('paged')),
            'total' => $wp_query->max_num_pages,
            'prev_text' => __('&laquo; Previous'),
            'next_text' => __('Next &raquo;'),
        ));
        ?>
    </div>

</section>
<?php get_footer(); ?>
