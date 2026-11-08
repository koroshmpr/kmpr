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
    $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;

    $types_list = array(
        ''          => 'همه نتایج',
        'post'      => 'مقالات',
        'portfolio' => 'نمونه‌کارها',
        'services'  => 'خدمات',
    );
    $allowed_types = array_values(array_filter(array_keys($types_list)));

    // Whitelist the requested post type; anything unknown falls back to all types.
    $current_type = isset($_GET['post_type']) ? sanitize_key(wp_unslash($_GET['post_type'])) : '';
    if (!in_array($current_type, $allowed_types, true)) {
        $current_type = '';
    }

    $args = array(
        'post_type'      => $current_type ? $current_type : $allowed_types,
        'post_status'    => 'publish',
        's'              => $search_term,
        'paged'          => $paged,
        'posts_per_page' => 12,
    );
    $post_type_query = new WP_Query($args);
    $total_found = $post_type_query->found_posts;
    ?>

    <!-- Query Info & Filter Tabs -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-white/10 pb-6 mb-10">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-xs font-black uppercase text-secondary tracking-wider">نتایج جستجو</span>
                <span class="text-xs bg-secondary/15 text-secondary border border-secondary/30 font-extrabold px-2.5 py-1 rounded-full whitespace-nowrap">
                    <?= esc_html(number_format_i18n($total_found)); ?> مورد یافته شد
                </span>
            </div>
            <h1 class="text-2xl sm:text-3xl lg:text-4xl font-black text-white mt-2 break-words">
                <?= !empty($search_term) ? '«' . esc_html($search_term) . '»' : 'همه موارد'; ?>
            </h1>
        </div>

        <!-- Filter Pills -->
        <div class="flex flex-wrap gap-2">
            <?php foreach ($types_list as $type_slug => $type_label) : 
                $is_active = ($current_type === $type_slug);
                $tab_url = add_query_arg(array('s' => rawurlencode(get_search_query(false)), 'post_type' => $type_slug), home_url('/'));
                if (empty($type_slug)) {
                    $tab_url = remove_query_arg('post_type', $tab_url);
                }
            ?>
                <a href="<?= esc_url($tab_url); ?>" 
                   <?= $is_active ? 'aria-current="page"' : ''; ?>
                   class="inline-flex items-center min-h-11 px-4 py-2 rounded-full border whitespace-nowrap text-xs sm:text-sm font-extrabold transition-all <?= $is_active ? 'bg-secondary text-slate-950 border-secondary shadow-sm' : 'bg-white/[0.03] border-white/10 text-slate-300 hover:text-white hover:border-secondary/40'; ?>">
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
        <div class="flex justify-center items-center gap-2 mt-16 py-6 border-t border-white/10 flex-wrap [&_.page-numbers]:inline-flex [&_.page-numbers]:items-center [&_.page-numbers]:justify-center [&_.page-numbers]:min-h-11 [&_.page-numbers]:min-w-11 [&_.page-numbers]:px-4 [&_.page-numbers]:rounded-xl [&_.page-numbers]:whitespace-nowrap [&_.page-numbers]:text-xs sm:[&_.page-numbers]:text-sm [&_.page-numbers]:font-bold [&_a.page-numbers]:bg-white/[0.03] [&_a.page-numbers]:border [&_a.page-numbers]:border-white/10 [&_a.page-numbers]:text-slate-300 [&_a.page-numbers:hover]:border-secondary/40 [&_a.page-numbers:hover]:text-white [&_.page-numbers.current]:bg-secondary [&_.page-numbers.current]:text-slate-950 [&_.page-numbers.current]:font-black [&_.page-numbers.dots]:text-slate-400 transition-colors">
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
            <div class="size-16 rounded-3xl bg-secondary/15 text-secondary flex items-center justify-center border border-secondary/30">
                <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" class="shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            <h2 class="text-xl font-black text-white">هیچ نتیجه‌ای یافت نشد!</h2>
            <p class="text-slate-300 text-sm leading-relaxed">
                برای عبارت جستجو شده موردی پیدا نشد. لطفاً از املای صحیح کلمات مطمئن شده یا تب‌های دیگر را امتحان کنید.
            </p>
            <a href="<?= esc_url(home_url('/')); ?>" class="mt-2 inline-flex items-center justify-center min-h-11 whitespace-nowrap bg-secondary hover:bg-secondary-hover text-slate-950 font-black text-sm px-6 py-3 rounded-full transition-all shadow-sm">
                بازگشت به صفحه اصلی
            </a>
        </div>
    <?php endif;
    wp_reset_postdata(); ?>
</section>

<?php get_footer(); ?>
