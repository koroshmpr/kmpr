<?php
/**
 * Category Archive Template with Sidebar & SEO Pagination - Dark Obsidian Edition
 * Personal Brand: Korosh Mpr | Senior Software Architect
 */

get_header(); 

$current_cat = get_queried_object();
$current_cat_id = $current_cat ? $current_cat->term_id : 0;
$cat_name = single_cat_title('', false);
$cat_desc = category_description();
?>

<!-- Category Header -->
<section class="bg-slate-950 text-white py-12 lg:py-16 border-b border-white/10 relative overflow-hidden">
    <!-- Ambient Subtle Glow Spheres -->
    <div class="absolute top-0 start-1/4 w-96 h-96 bg-secondary/10 rounded-full blur-[140px] pointer-events-none -z-0"></div>
    <div class="absolute bottom-0 end-1/4 w-96 h-96 bg-blue-600/10 rounded-full blur-[140px] pointer-events-none -z-0"></div>

    <div class="container mx-auto px-4 sm:px-6 relative z-10 text-center max-w-3xl">
        <span class="text-xs font-black uppercase text-secondary tracking-widest block mb-2">دسته‌بندی موضوعی</span>
        <h1 class="text-3xl sm:text-4xl lg:text-5xl font-black text-white leading-tight drop-shadow-md">
            <?= esc_html($cat_name); ?>
        </h1>
        <div class="h-1.5 w-16 bg-secondary mx-auto rounded-full mt-4 mb-4"></div>
        <?php if ($cat_desc) : ?>
            <div class="text-slate-300 text-sm sm:text-base leading-relaxed max-w-xl mx-auto">
                <?= wp_kses_post($cat_desc); ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Main Grid + Dark Obsidian Sidebar Section -->
<section class="bg-slate-950 text-slate-100 py-12 lg:py-16 text-start relative overflow-hidden min-h-screen">
    <div class="container mx-auto px-4 sm:px-6">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-start">
            
            <!-- Main Category Posts Column (lg:col-span-8) -->
            <main class="lg:col-span-8 flex flex-col gap-8 order-1">
                <?php if (have_posts()) : ?>
                    <div class="flex items-center justify-between border-b border-white/10 pb-4">
                        <span class="text-sm font-bold text-slate-400">
                            نمایش مقالات دسته‌بندی <strong class="text-white font-black"><?= esc_html($cat_name); ?></strong>
                        </span>
                        <span class="text-xs bg-white/10 text-slate-300 border border-white/10 font-bold px-3 py-1 rounded-full">
                            <?= number_format_i18n($wp_query->found_posts); ?> مقاله
                        </span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 sm:gap-8">
                        <?php while (have_posts()) : the_post();
                            echo '<div class="h-full">';
                            get_template_part('template-parts/cards/title-under-image');
                            echo '</div>';
                        endwhile; ?>
                    </div>

                    <!-- SEO Standard Pagination -->
                    <div class="flex justify-center items-center gap-2 mt-12 py-8 border-t border-white/10">
                        <?php
                        $big = 999999999;
                        echo paginate_links(array(
                            'base'      => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
                            'format'    => '?paged=%#%',
                            'current'   => max(1, get_query_var('paged')),
                            'total'     => $wp_query->max_num_pages,
                            'prev_text' => '&rarr; قبلی',
                            'next_text' => 'بعدی &larr;',
                            'type'      => 'plain',
                            'class'     => 'flex gap-2'
                        ));
                        ?>
                    </div>

                <?php else : ?>
                    <div class="py-16 text-center max-w-md mx-auto bg-slate-900/60 border border-white/10 rounded-3xl p-8">
                        <p class="font-bold text-lg text-slate-300">هیچ مقاله‌ای در این دسته‌بندی یافت نشد.</p>
                        <a href="<?= esc_url(get_permalink(get_option('page_for_posts')) ?: home_url('/blog')); ?>" 
                           class="mt-4 inline-block bg-secondary hover:bg-secondary-hover text-white text-xs font-bold px-6 py-2.5 rounded-full transition-all shadow-md">
                            مشاهده همه مقالات
                        </a>
                    </div>
                <?php endif; ?>
            </main>

            <!-- Sidebar Column (lg:col-span-4) - Dark Obsidian Styling -->
            <aside class="lg:col-span-4 flex flex-col gap-6 sm:gap-8 order-2 lg:sticky lg:top-28">
                
                <!-- Widget 1: Categories -->
                <div class="bg-slate-900/80 backdrop-blur-xl border border-white/10 rounded-3xl p-6 shadow-2xl relative overflow-hidden">
                    <h3 class="text-base font-black text-white border-b border-white/10 pb-3.5 mb-4 flex items-center justify-between">
                        <span>دسته‌بندی‌های مقالات</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="text-secondary" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M2 2a1 1 0 0 1 1-1h4.586a1 1 0 0 1 .707.293l7 7a1 1 0 0 1 0 1.414l-4.586 4.586a1 1 0 0 1-1.414 0l-7-7A1 1 0 0 1 2 6.586V2zm3.5 3.5a1.5 1.5 0 1 0-3 0 1.5 1.5 0 0 0 3 0z"/>
                        </svg>
                    </h3>

                    <ul class="flex flex-col gap-2 list-none p-0 m-0">
                        <?php
                        $categories = get_categories(array('orderby' => 'count', 'order' => 'DESC', 'hide_empty' => true));
                        foreach ($categories as $cat) :
                            $is_current = ($cat->term_id == $current_cat_id);
                        ?>
                            <li>
                                <a href="<?= esc_url(get_category_link($cat->term_id)); ?>" 
                                   class="flex items-center justify-between p-3 rounded-2xl <?= $is_current ? 'bg-secondary/15 text-secondary border-secondary/40 font-black' : 'bg-white/[0.03] hover:bg-white/[0.08] hover:text-secondary text-slate-300 font-bold border-white/5 hover:border-secondary/30'; ?> text-xs sm:text-sm border transition-all group shadow-2xs">
                                    <span class="group-hover:translate-x-1 transition-transform"><?= esc_html($cat->name); ?></span>
                                    <span class="text-xs <?= $is_current ? 'bg-secondary text-white' : 'bg-white/5 border border-white/10 group-hover:bg-secondary group-hover:text-white text-slate-400'; ?> px-2.5 py-0.5 rounded-lg font-bold transition-colors">
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

                <!-- Widget 3: Popular Tags -->
                <div class="bg-slate-900/80 backdrop-blur-xl border border-white/10 rounded-3xl p-6 shadow-2xl relative overflow-hidden">
                    <h3 class="text-base font-black text-white border-b border-white/10 pb-3.5 mb-4">برچسب‌های پرکاربرد</h3>
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
                        <?php endforeach; endif; ?>
                    </div>
                </div>

            </aside>

        </div>
    </div>
</section>

<?php get_footer(); ?>
