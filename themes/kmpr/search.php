<?php get_header(); ?>

<section class="container mx-auto px-4 sm:px-6 py-8 lg:py-16 text-start">
    <!-- Search Bar Box -->
    <div class="max-w-3xl mx-auto mb-10">
        <?php get_template_part('template-parts/search-bar', null, array(
            'place' => 'search-page',
            'size' => 'w-full',
        )); ?>
    </div>

    <?php
    $search_term = get_search_query();
    $current_type = isset($_GET['post_type']) ? sanitize_text_field($_GET['post_type']) : '';
    $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;

    $types_list = array(
        ''          => 'همه نتایج',
        'post'      => 'مقالات',
        'portfolio' => 'نمونه‌کارها',
        'services'  => 'خدمات',
    );

    $args = array(
        'post_type'      => $current_type ? $current_type : array('post', 'portfolio', 'services'),
        'post_status'    => 'publish',
        's'              => $search_term,
        'paged'          => $paged,
        'posts_per_page' => 12,
    );
    $post_type_query = new WP_Query($args);
    $total_found = $post_type_query->found_posts;
    ?>

    <!-- Query Info & Filter Tabs -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-200/80 pb-6 mb-10">
        <div>
            <div class="flex items-center gap-2">
                <span class="text-xs font-black uppercase text-secondary tracking-wider">نتایج جستجو</span>
                <span class="text-xs bg-slate-100 text-slate-600 font-extrabold px-2.5 py-1 rounded-md">
                    <?= number_format_i18n($total_found); ?> مورد یافته شد
                </span>
            </div>
            <h1 class="text-2xl sm:text-3xl lg:text-4xl font-black text-slate-900 mt-2">
                <?= !empty($search_term) ? '«' . esc_html($search_term) . '»' : 'همه موارد'; ?>
            </h1>
        </div>

        <!-- Filter Pills -->
        <div class="flex flex-wrap gap-2">
            <?php foreach ($types_list as $type_slug => $type_label) : 
                $is_active = ($current_type === $type_slug);
                $tab_url = add_query_arg(array('s' => $search_term, 'post_type' => $type_slug), home_url('/'));
                if (empty($type_slug)) {
                    $tab_url = remove_query_arg('post_type', $tab_url);
                }
            ?>
                <a href="<?= esc_url($tab_url); ?>" 
                   class="px-4 py-2 rounded-xl text-xs sm:text-sm font-extrabold transition-all <?= $is_active ? 'bg-primary text-white shadow-sm' : 'bg-slate-100 hover:bg-slate-200 text-slate-700'; ?>">
                    <?= esc_html($type_label); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Results Grid -->
    <?php if ($post_type_query->have_posts()) : ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php
            while ($post_type_query->have_posts()) : $post_type_query->the_post();
                $post_t = get_post_type();
                echo '<div class="h-full">';
                if ($post_t === 'services') {
                    get_template_part('template-parts/services/services-card');
                } elseif ($post_t === 'portfolio') {
                    get_template_part('template-parts/portfolio/portfolio-card', null, array('class' => 'h-full'));
                } else {
                    get_template_part('template-parts/cards/title-under-image');
                }
                echo '</div>';
            endwhile;
            ?>
        </div>

        <!-- Pagination -->
        <div class="flex justify-center items-center gap-2 mt-16 py-6 border-t border-slate-100">
            <?php
            $big = 999999999;
            echo paginate_links(array(
                'base'      => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
                'format'    => '?paged=%#%',
                'current'   => max(1, get_query_var('paged')),
                'total'     => $post_type_query->max_num_pages,
                'prev_text' => '&rarr; قبلی',
                'next_text' => 'بعدی &larr;',
                'type'      => 'plain',
                'class'     => 'flex gap-2'
            ));
            ?>
        </div>
    <?php else : ?>
        <!-- Empty State -->
        <div class="py-20 text-center max-w-md mx-auto flex flex-col items-center gap-4">
            <div class="size-16 rounded-3xl bg-amber-50 text-secondary flex items-center justify-center border border-amber-200/60 shadow-xs">
                <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            <h3 class="text-xl font-black text-slate-800">هیچ نتیجه‌ای یافت نشد!</h3>
            <p class="text-slate-500 text-sm leading-relaxed">
                برای عبارت جستجو شده موردی پیدا نشد. لطفاً از املای صحیح کلمات مطمئن شده یا تب‌های دیگر را امتحان کنید.
            </p>
            <a href="<?= esc_url(home_url('/')); ?>" class="mt-2 bg-primary hover:bg-slate-800 text-white font-bold text-xs px-6 py-3 rounded-full transition-all shadow-sm">
                بازگشت به صفحه اصلی
            </a>
        </div>
    <?php endif;
    wp_reset_postdata(); ?>
</section>

<?php get_footer(); ?>
