<?php
/**
 * Standard, SEO-Optimized Blog Archive Template - Dark Obsidian Edition
 * Personal Brand: Korosh Mpr | Senior Software Architect
 * Includes Blog Search, Category Sidebar, Most Visited Posts, and SEO Pagination
 */

get_header(); ?>

<!-- Page Title Header with Integrated Blog Search -->
<section class="bg-slate-950 text-white py-12 lg:py-16 border-b border-white/10 relative overflow-hidden">
    <!-- Ambient Subtle Glow Spheres -->
    <div class="absolute top-0 start-1/4 w-96 h-96 bg-secondary/10 rounded-full blur-[140px] pointer-events-none -z-0"></div>
    <div class="absolute bottom-0 end-1/4 w-96 h-96 bg-blue-600/10 rounded-full blur-[140px] pointer-events-none -z-0"></div>

    <div class="container mx-auto px-4 sm:px-6 relative z-10 text-center max-w-3xl">
        <span class="text-xs font-black uppercase text-secondary tracking-widest block mb-2">یادداشت‌ها و مقالات مهندسی</span>
        <h1 class="text-3xl sm:text-4xl lg:text-5xl font-black text-white leading-tight drop-shadow-md">
            مقالات و تحلیل‌های تخصصی وب
        </h1>
        <div class="h-1.5 w-16 bg-secondary mx-auto rounded-full mt-4 mb-6"></div>
        <p class="text-slate-300 text-sm sm:text-base leading-relaxed mb-8">
            بررسی عمیق الگوهای معماری نرم‌افزار، بهینه‌سازی عملکرد وب (Core Web Vitals)، توسعه مدرن با وردپرس و مهندسی سیستم‌های مقیاس‌پذیر
        </p>

        <!-- In-Archive Search Form -->
        <form method="get" action="<?php echo esc_url(home_url('/')); ?>" 
              class="flex items-center gap-2 bg-white/[0.04] hover:bg-white/[0.07] backdrop-blur-md border border-white/15 rounded-2xl p-2 shadow-xl max-w-1/2 mx-auto focus-within:border-secondary/60 focus-within:bg-slate-900/90 transition-all group">
            <input type="hidden" name="post_type" value="post">
            <div class="flex items-center gap-2 flex-1 px-3">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" class="text-slate-400 group-focus-within:text-secondary shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input type="search" 
                       name="s" 
                       class="w-full text-xs sm:text-sm bg-transparent outline-none text-white placeholder:text-slate-400 font-bold" 
                       placeholder="جستجو در مقالات تخصصی...">
            </div>
            <button type="submit" 
                    class="bg-secondary hover:bg-secondary-hover text-white font-black text-xs px-5 py-2.5 rounded-xl cursor-pointer transition-all shadow-md shrink-0">
                جستجو
            </button>
        </form>
    </div>
</section>

<!-- Main Content Area: Articles Grid + Dark Obsidian Sidebar -->
<section class="bg-slate-950 text-slate-100 py-12 lg:py-16 text-start relative overflow-hidden min-h-screen">
    <div class="container mx-auto px-4 sm:px-6">
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
                    <div class="flex items-center justify-between border-b border-white/10 pb-4">
                        <h2 class="text-xl sm:text-2xl font-black text-white flex items-center gap-3">
                            <span>همه مقالات</span>
                            <span class="text-xs bg-white/10 text-slate-300 border border-white/10 font-bold px-3 py-1 rounded-full">
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
                    <div class="flex justify-center items-center gap-2 mt-12 py-8 border-t border-white/10">
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
                    <div class="py-16 text-center max-w-md mx-auto bg-slate-900/60 border border-white/10 rounded-3xl p-8">
                        <p class="font-bold text-lg text-slate-300">مقاله‌ای یافت نشد.</p>
                        <p class="text-xs text-slate-400 mt-2">به زودی مقالات جدید منتشر خواهد شد.</p>
                    </div>
                <?php endif;
                wp_reset_postdata(); ?>
            </main>

            <!-- Sidebar Column (lg:col-span-4) - Dark Obsidian Styling -->
            <aside class="lg:col-span-4 flex flex-col gap-6 sm:gap-8 order-2 lg:sticky lg:top-28">
                
                <!-- Widget 1: Categories with Counts -->
                <div class="bg-slate-900/80 backdrop-blur-xl border border-white/10 rounded-3xl p-6 shadow-2xl relative overflow-hidden">
                    <h3 class="text-base font-black text-white border-b border-white/10 pb-3.5 mb-4 flex items-center justify-between">
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
                                   class="flex items-center justify-between p-3 rounded-2xl bg-white/[0.03] hover:bg-white/[0.08] hover:text-secondary text-slate-300 text-xs sm:text-sm font-bold border border-white/5 hover:border-secondary/30 transition-all group shadow-2xs">
                                    <span class="group-hover:translate-x-1 transition-transform"><?= esc_html($cat->name); ?></span>
                                    <span class="text-xs bg-white/5 border border-white/10 group-hover:bg-secondary group-hover:text-white px-2.5 py-0.5 rounded-lg font-bold text-slate-400 transition-colors">
                                        <?= number_format_i18n($cat->count); ?>
                                    </span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Widget 2: Most Visited Posts -->
                <div class="bg-slate-900/80 backdrop-blur-xl border border-white/10 rounded-3xl p-6 shadow-2xl relative overflow-hidden">
                    <h3 class="text-base font-black text-white border-b border-white/10 pb-3.5 mb-4 flex items-center justify-between">
                        <span>پربازدیدترین مقالات</span>
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
                <div class="bg-slate-900/80 backdrop-blur-xl border border-white/10 rounded-3xl p-6 shadow-2xl relative overflow-hidden">
                    <h3 class="text-base font-black text-white border-b border-white/10 pb-3.5 mb-4 flex items-center justify-between">
                        <span>برچسب‌های پرکاربرد</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="text-secondary" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M2 1a1 1 0 0 0-1 1v4.586a1 1 0 0 0 .293.707l7 7a1 1 0 0 0 1.414 0l4.586-4.586a1 1 0 0 0 0-1.414l-7-7A1 1 0 0 0 6.586 1H2zm4 3.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                        </svg>
                    </h3>

                    <div class="flex flex-wrap gap-2">
                        <?php
                        $tags = get_tags(array('orderby' => 'count', 'order' => 'DESC', 'number' => 14));
                        if ($tags) :
                            foreach ($tags as $tag) :
                        ?>
                            <a href="<?= esc_url(get_tag_link($tag->term_id)); ?>" 
                               class="text-xs bg-white/[0.04] hover:bg-secondary hover:text-white border border-white/10 hover:border-secondary text-slate-300 px-3 py-1.5 rounded-xl font-bold transition-all shadow-2xs">
                                <?= esc_html($tag->name); ?>
                            </a>
                        <?php
                            endforeach;
                        endif;
                        ?>
                    </div>
                </div>

                <!-- Widget 4: Personal Technical Consultation CTA -->
                <div class="bg-gradient-to-br from-slate-900 via-slate-900/90 to-slate-950 text-white rounded-3xl p-6 sm:p-7 shadow-2xl border border-secondary/25 text-center relative overflow-hidden group">
                    <div class="absolute -top-12 -end-12 size-36 bg-secondary/15 rounded-full blur-2xl pointer-events-none"></div>
                    <span class="text-xs font-black uppercase text-secondary tracking-widest block mb-2">مشاوره فنی و معماری</span>
                    <h4 class="text-lg font-black text-white mb-2">نیاز به ارزیابی یا توسعه پروژه دارید؟</h4>
                    <p class="text-xs text-slate-300 leading-relaxed mb-5">
                        مشاوره مستقیم معماری نرم‌افزار، بهینه‌سازی پرفورمنس و پیاده‌سازی پلتفرم‌های مقیاس‌پذیر وب.
                    </p>
                    <a href="<?= esc_url(home_url('/contact-us') ?: home_url('/contact')); ?>" 
                       class="inline-flex items-center justify-center gap-2 w-full bg-secondary hover:bg-secondary-hover text-white font-black text-xs py-3 rounded-2xl transition-all shadow-lg shadow-secondary/25 hover:scale-105 active:scale-95">
                        <span>شروع گفتگو و ارتباط با من</span>
                        <span>&larr;</span>
                    </a>
                </div>

            </aside>

        </div>
    </div>
</section>

<?php get_footer(); ?>
