<?php get_header(); ?>

<section class="container mx-auto px-6 py-8 lg:py-16 text-start">
    <!-- Search Bar -->
    <div class="max-w-2xl mx-auto mb-12">
        <?php get_template_part('template-parts/search-bar', null, array(
            'place' => 'search-page',
            'size' => 'w-full',
        )); ?>
    </div>

    <!-- Search Query Heading -->
    <div class="flex flex-wrap items-center gap-2 border-b border-slate-200/80 pb-4 mb-8">
        <span class="text-slate-400 text-sm font-bold">نتیجه جستجو برای:</span>
        <h1 class="font-black text-2xl lg:text-3xl text-slate-800">
            <?= esc_html(get_search_query()); ?>
        </h1>
        <span class="text-xs bg-secondary/15 text-secondary px-3 py-1.5 rounded-lg font-bold">
            <?php
            $post_type = isset($_GET['post_type']) ? sanitize_text_field($_GET['post_type']) : '';
            if (!empty($post_type)) {
                $post_type_info = get_post_type_object($post_type);
                echo esc_html($post_type_info->labels->name);
            } else {
                echo 'جستجوی کلی';
            }
            ?>
        </span>
    </div>

    <?php
    $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
    $args = array(
        'post_type' => $post_type ? $post_type : array('post', 'portfolio', 'services'),
        's' => get_search_query(),
        'paged' => $paged,
    );
    $post_type_query = new WP_Query($args);
    if ($post_type_query->have_posts()) :
        ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php
            while ($post_type_query->have_posts()) : $post_type_query->the_post();
                $current_post_type = get_post_type();
                if ($current_post_type == 'services') {
                    get_template_part('template-parts/services/services-card');
                } elseif ($current_post_type == 'post') {
                    get_template_part('template-parts/cards/title-under-image');
                } elseif ($current_post_type == 'portfolio') {
                    get_template_part('template-parts/portfolio/portfolio-card');
                }
            endwhile;
            ?>
        </div>

        <!-- Pagination -->
        <div class="flex justify-center items-center gap-2 mt-12 py-4">
            <?php
            $big = 999999999;
            echo paginate_links(array(
                'base' => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
                'format' => '?paged=%#%',
                'current' => max(1, get_query_var('paged')),
                'total' => $post_type_query->max_num_pages,
                'prev_text' => __('قبلی'),
                'next_text' => __('بعدی'),
                'type' => 'plain',
                'class' => 'flex gap-2'
            ));
            ?>
        </div>
    <?php else : ?>
        <div class="py-16 text-center">
            <p class="text-lg font-bold text-slate-500">هیچ موردی یافت نشد!</p>
        </div>
    <?php endif;
    wp_reset_postdata(); ?>
</section>

<?php get_footer(); ?>
