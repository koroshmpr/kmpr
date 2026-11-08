<?php
/**
 * Home — Latest articles (editorial grid: 1 feature + 2 compact)
 */
$posts = new WP_Query(array(
    'post_type'           => 'post',
    'posts_per_page'      => 3,
    'ignore_sticky_posts' => true,
    'no_found_rows'       => true,
));
if (!$posts->have_posts()) {
    return;
}
$blog_url = get_permalink(get_option('page_for_posts')) ?: home_url('/blog/');
?>
<section class="relative py-20 lg:py-28 border-t border-white/10" aria-labelledby="articles-title">
    <div class="container mx-auto px-4 sm:px-6">
        <header class="flex flex-col sm:flex-row sm:items-end justify-between gap-6 mb-12">
            <div class="max-w-2xl">
                <span class="inline-flex items-center gap-2 text-xs font-black text-secondary mb-4" data-aos="fade-up">
                    <span class="w-6 h-px bg-secondary"></span><?= esc_html(kmpr_field('articles_kicker')); ?>
                </span>
                <h2 id="articles-title" class="text-3xl sm:text-4xl lg:text-5xl font-black text-white leading-tight" data-split>
                    <?= esc_html(kmpr_field('articles_title')); ?>
                </h2>
            </div>
            <a href="<?= esc_url($blog_url); ?>" data-aos="fade-up"
               class="self-start sm:self-auto inline-flex items-center gap-2 px-6 py-3 rounded-full border border-white/15 hover:border-secondary/50 text-sm font-bold text-slate-200 hover:text-white whitespace-nowrap transition-colors">
                همه مقالات
            </a>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5" data-stagger="120">
            <?php $i = 0;
            while ($posts->have_posts()) : $posts->the_post();
                $cat   = kmpr_primary_term(get_the_ID(), 'category');
                $first = ($i === 0);
                ?>
                <article data-aos="fade-up" class="<?= $first ? 'lg:row-span-2' : ''; ?>">
                    <a href="<?php the_permalink(); ?>" class="spotlight group flex <?= $first ? 'flex-col' : 'flex-col sm:flex-row'; ?> h-full rounded-3xl overflow-hidden bg-white/[0.025] border border-white/10 hover:border-secondary/40 transition-colors duration-500">
                        <div class="relative overflow-hidden bg-slate-900 shrink-0 <?= $first ? 'aspect-[16/9]' : 'aspect-[16/9] sm:aspect-auto sm:w-56 lg:w-64'; ?>">
                            <?php if (has_post_thumbnail()) :
                                the_post_thumbnail($first ? 'large' : 'medium_large', array('class' => 'absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-700', 'loading' => 'lazy', 'alt' => esc_attr(get_the_title())));
                            else : ?>
                                <div class="absolute inset-0 bg-gradient-to-br from-secondary/30 via-slate-900 to-indigo-900/40 flex items-center justify-center" aria-hidden="true">
                                    <span class="font-mono text-5xl font-black text-white/10">&lt;/&gt;</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="flex flex-col justify-between gap-5 p-6 sm:p-7 flex-1">
                            <div>
                                <div class="flex items-center gap-3 text-xs text-slate-400 mb-3">
                                    <?php if ($cat) : ?><span class="font-black text-secondary"><?= esc_html($cat->name); ?></span><span aria-hidden="true">•</span><?php endif; ?>
                                    <time datetime="<?= esc_attr(get_the_date('c')); ?>"><?= esc_html(function_exists('shamsi_date') ? shamsi_date('j F Y', get_the_time('U')) : get_the_date()); ?></time>
                                </div>
                                <h3 class="<?= $first ? 'text-2xl lg:text-3xl' : 'text-lg'; ?> font-black text-white leading-snug group-hover:text-secondary transition-colors"><?php the_title(); ?></h3>
                                <?php if ($first) : ?>
                                    <p class="mt-4 text-slate-300 leading-loose"><?= esc_html(wp_trim_words(get_the_excerpt(), 34)); ?></p>
                                <?php endif; ?>
                            </div>
                            <span class="inline-flex items-center gap-2 text-sm font-black text-slate-200 group-hover:text-secondary transition-colors">
                                ادامه مطلب
                                <svg width="16" height="16" class="shrink-0 group-hover:-translate-x-1 transition-transform" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.61l4.16 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.61 9.25h10.64A.75.75 0 0 1 17 10Z" clip-rule="evenodd"/></svg>
                            </span>
                        </div>
                    </a>
                </article>
            <?php $i++;
            endwhile;
            wp_reset_postdata(); ?>
        </div>
    </div>
</section>
