<?php
/**
 * Standard, SEO-Optimized Blog Archive Template
 * Includes Blog Search, Category Sidebar, Most Visited Posts, and SEO Pagination
 */

get_header(); ?>

<!-- Page Title Header with Integrated Blog Search -->
<section class="bg-slate-950 text-white py-12 lg:py-16 border-b border-slate-800 relative overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/70 to-slate-950"></div>
    <div class="container mx-auto px-4 sm:px-6 relative z-10 text-center max-w-3xl">
        <span class="text-xs font-black uppercase text-secondary tracking-widest block mb-2">وبلاگ و یادداشت‌ها</span>
        <h1 class="text-3xl sm:text-4xl lg:text-5xl font-black text-white leading-tight drop-shadow-md">
            مقالات و اخبار تخصصی
        </h1>
        <div class="h-1.5 w-16 bg-secondary mx-auto rounded-full mt-4 mb-6"></div>
        <p class="text-slate-300 text-sm sm:text-base leading-relaxed mb-8">
            جدیدترین مقالات آموزشی، تحلیل‌های تخصصی و راهنماهای کاربردی دنیای وب و تکنولوژی
        </p>

        <!-- In-Archive Search Form -->
        <form method="get" action="<?php echo esc_url(home_url('/')); ?>" 
              class="flex items-center gap-2 bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-2 shadow-lg max-w-lg mx-auto focus-within:bg-white focus-within:border-secondary transition-all group">
            <input type="hidden" name="post_type" value="post">
            <div class="flex items-center gap-2 flex-1 px-3">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" class="text-slate-400 group-focus-within:text-secondary shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input type="search" 
                       name="s" 
                       class="w-full text-xs sm:text-sm bg-transparent outline-none text-white group-focus-within:text-slate-900 placeholder:text-slate-300 group-focus-within:placeholder:text-slate-400 font-bold" 
                       placeholder="جستجو در مقالات وبلاگ...">
            </div>
            <button type="submit" 
                    class="bg-secondary hover:bg-secondary-hover text-white font-black text-xs px-5 py-2.5 rounded-xl cursor-pointer transition-all shadow-sm shrink-0">
                جستجو
            </button>
        </form>
    </div>
</section>

<!-- Main Content Area: Articles Grid + Standard Sidebar -->
<section class="container mx-auto px-4 sm:px-6 py-12 lg:py-16 text-start">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-start">
        
        <!-- Main Articles Column (lg:col-span-8) -->
        <main class="lg:col-span-8 flex flex-col gap-8 order-1">
            <?php
            $paged = (get_query_var('paged')) ? get_query_var('paged') : (get_query_var('page') ? get_query_var('page') : 1);
            $main_args = array(
                'post_type'           => 'post',
                'post_status'         => 'publish',
                'paged'               => $paged,
                'posts_per_page'      => 10,
                'ignore_sticky_posts' => false
            );
            $blog_query = new WP_Query($main_args);

            if ($blog_query->have_posts()) :
            ?>
                <!-- Section Header -->
                <div class="flex items-center justify-between border-b border-slate-200/80 pb-3">
                    <h2 class="text-xl sm:text-2xl font-black text-slate-900 flex items-center gap-2">
                        <span>همه مقالات</span>
                        <span class="text-xs bg-slate-100 text-slate-600 font-extrabold px-2.5 py-0.5 rounded-md">
                            صفحه <?= $paged; ?> از <?= $blog_query->max_num_pages; ?>
                        </span>
                    </h2>
                </div>

                <!-- Articles Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 sm:gap-8">
                    <?php
                    while ($blog_query->have_posts()) : $blog_query->the_post();
                        echo '<div class="h-full">';
                        get_template_part('template-parts/cards/title-under-image');
                        echo '</div>';
                    endwhile;
                    ?>
                </div>

                <!-- SEO Standard Pagination -->
                <div class="flex justify-center items-center gap-2 mt-12 py-6 border-t border-slate-200/80">
                    <?php
                    $big = 999999999;
                    echo paginate_links(array(
                        'base'      => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
                        'format'    => '?paged=%#%',
                        'current'   => max(1, $paged),
                        'total'     => $blog_query->max_num_pages,
                        'prev_text' => '&rarr; قبلی',
                        'next_text' => 'بعدی &larr;',
                        'type'      => 'plain',
                        'class'     => 'flex gap-2'
                    ));
                    ?>
                </div>

            <?php else : ?>
                <div class="py-16 text-center max-w-md mx-auto">
                    <p class="font-bold text-lg text-slate-500">مقاله‌ای یافت نشد.</p>
                </div>
            <?php endif;
            wp_reset_postdata(); ?>
        </main>

        <!-- Sidebar Column (lg:col-span-4) -->
        <aside class="lg:col-span-4 flex flex-col gap-8 order-2 lg:sticky lg:top-24">
            
            <!-- Widget 1: Categories with Counts -->
            <div class="bg-white border border-slate-200/80 rounded-3xl p-6 shadow-sm">
                <h3 class="text-base font-black text-slate-900 border-b border-slate-100 pb-3 mb-4 flex items-center justify-between">
                    <span>دسته‌بندی‌های مقالات</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="text-secondary" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M2 2a1 1 0 0 1 1-1h4.586a1 1 0 0 1 .707.293l7 7a1 1 0 0 1 0 1.414l-4.586 4.586a1 1 0 0 1-1.414 0l-7-7A1 1 0 0 1 2 6.586V2zm3.5 3.5a1.5 1.5 0 1 0-3 0 1.5 1.5 0 0 0 3 0z"/>
                    </svg>
                </h3>

                <ul class="flex flex-col gap-2 list-none p-0 m-0">
                    <?php
                    $categories = get_categories(array(
                        'orderby'    => 'count',
                        'order'      => 'DESC',
                        'hide_empty' => true,
                    ));
                    foreach ($categories as $cat) :
                    ?>
                        <li>
                            <a href="<?= esc_url(get_category_link($cat->term_id)); ?>" 
                               class="flex items-center justify-between p-3 rounded-xl bg-slate-50 hover:bg-slate-100 hover:text-secondary text-slate-700 text-xs sm:text-sm font-bold transition-all group">
                                <span class="group-hover:translate-x-1 transition-transform"><?= esc_html($cat->name); ?></span>
                                <span class="text-xs bg-white border border-slate-200 group-hover:bg-secondary group-hover:text-white px-2 py-0.5 rounded-md font-bold transition-colors">
                                    <?= number_format_i18n($cat->count); ?>
                                </span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Widget 2: Most Visited Posts -->
            <div class="bg-white border border-slate-200/80 rounded-3xl p-6 shadow-sm">
                <h3 class="text-base font-black text-slate-900 border-b border-slate-100 pb-3 mb-4 flex items-center justify-between">
                    <span>پر بازدیدترین مقالات</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="text-secondary" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M10.5 8a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0z"/>
                        <path d="M0 8s3-5.5 8-5.5S16 8 16 8s-3 5.5-8 5.5S0 8 0 8zm8 3.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7z"/>
                    </svg>
                </h3>

                <div class="flex flex-col gap-3">
                    <?php
                    $popular_args = array(
                        'post_type'           => 'post',
                        'post_status'         => 'publish',
                        'orderby'             => 'meta_value_num',
                        'meta_key'            => 'post_views',
                        'order'               => 'DESC',
                        'posts_per_page'      => 4,
                        'ignore_sticky_posts' => true
                    );
                    $pop_query = new WP_Query($popular_args);
                    if ($pop_query->have_posts()) :
                        while ($pop_query->have_posts()) : $pop_query->the_post();
                            get_template_part('template-parts/cards/title-side-image');
                        endwhile;
                    endif;
                    wp_reset_postdata();
                    ?>
                </div>
            </div>

            <!-- Widget 3: Popular Tags Cloud -->
            <div class="bg-white border border-slate-200/80 rounded-3xl p-6 shadow-sm">
                <h3 class="text-base font-black text-slate-900 border-b border-slate-100 pb-3 mb-4 flex items-center justify-between">
                    <span>برچسب‌های محبوب</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="text-secondary" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M2 1a1 1 0 0 0-1 1v4.586a1 1 0 0 0 .293.707l7 7a1 1 0 0 0 1.414 0l4.586-4.586a1 1 0 0 0 0-1.414l-7-7A1 1 0 0 0 6.586 1H2zm4 3.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                    </svg>
                </h3>

                <div class="flex flex-wrap gap-2">
                    <?php
                    $tags = get_tags(array('orderby' => 'count', 'order' => 'DESC', 'number' => 12));
                    if ($tags) :
                        foreach ($tags as $tag) :
                    ?>
                        <a href="<?= esc_url(get_tag_link($tag->term_id)); ?>" 
                           class="text-xs bg-slate-50 hover:bg-secondary hover:text-white border border-slate-200/70 text-slate-600 px-3 py-1.5 rounded-xl font-bold transition-all">
                            <?= esc_html($tag->name); ?>
                        </a>
                    <?php
                        endforeach;
                    endif;
                    ?>
                </div>
            </div>

            <!-- Widget 4: Quick CTA Box -->
            <div class="bg-slate-900 text-white rounded-3xl p-6 sm:p-7 shadow-xl border border-slate-800 text-center relative overflow-hidden">
                <span class="text-xs font-black uppercase text-secondary tracking-widest block mb-2">مشاوره تخصصی</span>
                <h4 class="text-lg font-black text-white mb-2">نیاز به راهنمایی در پروژه‌تان دارید؟</h4>
                <p class="text-xs text-slate-300 leading-relaxed mb-5">
                    تیم کارشناسان ما آماده پاسخگویی و ارائه مشاوره تخصصی به شما هستند.
                </p>
                <a href="<?= esc_url(home_url('/contact-us') ?: home_url('/contact')); ?>" 
                   class="inline-block w-full bg-secondary hover:bg-secondary-hover text-white font-black text-xs py-3 rounded-2xl transition-all shadow-md">
                    تماس با کارشناسان
                </a>
            </div>

        </aside>

    </div>
</section>

<?php get_footer(); ?>
