<?php
/**
 * Home — Selected work: sticky stacking case-study cards.
 * Picks the latest portfolio items that actually have a screenshot or thumbnail.
 */
$picked     = array_filter((array) get_field('work_items'));
$candidates = new WP_Query($picked ? array(
    'post_type'      => 'portfolio',
    'post__in'       => $picked,
    'orderby'        => 'post__in',
    'posts_per_page' => 4,
    'no_found_rows'  => true,
) : array(
    'post_type'      => 'portfolio',
    'posts_per_page' => 14,
    'no_found_rows'  => true,
    'meta_query'     => array(
        'relation'  => 'OR',
        'featured'  => array('key' => 'is_featured', 'type' => 'NUMERIC'),
        'no_flag'   => array('key' => 'is_featured', 'compare' => 'NOT EXISTS'),
    ),
    'orderby'        => array('featured' => 'DESC', 'date' => 'DESC'),
));
$items = array();
while ($candidates->have_posts() && count($items) < 4) {
    $candidates->the_post();
    $cover = kmpr_portfolio_cover(get_the_ID(), 'large');
    if (!$cover) {
        continue;
    }
    $term    = kmpr_primary_term(get_the_ID(), 'portfolio_categories');
    $items[] = array(
        'title'   => get_the_title(),
        'link'    => get_permalink(),
        'excerpt' => wp_trim_words(get_the_excerpt(), 26),
        'cover'   => $cover,
        'term'    => $term ? $term->name : '',
        'site'    => (function ($l) { return is_array($l) ? ($l['url'] ?? '') : (string) $l; })(get_field('portfolio-website')),
        'stack'   => get_field('theme_type'),
    );
}
wp_reset_postdata();
if (!$items) {
    return;
}
$accents = array('from-amber-500/25', 'from-sky-500/25', 'from-emerald-500/25', 'from-fuchsia-500/25');
?>
<section class="relative py-20 lg:py-28 border-t border-white/10" aria-labelledby="work-title">
    <div class="container mx-auto px-4 sm:px-6">
        <header class="flex flex-col lg:flex-row lg:items-end justify-between gap-6 mb-12 lg:mb-16">
            <div class="max-w-2xl">
                <span class="inline-flex items-center gap-2 text-xs font-black text-secondary mb-4" data-aos="fade-up">
                    <span class="w-6 h-px bg-secondary"></span><?= esc_html(kmpr_field('work_kicker')); ?>
                </span>
                <h2 id="work-title" class="text-3xl sm:text-4xl lg:text-5xl font-black text-white leading-tight" data-split>
                    <?= esc_html(kmpr_field('work_title')); ?>
                </h2>
            </div>
            <a href="<?= esc_url(get_post_type_archive_link('portfolio') ?: home_url('/portfolios/')); ?>" data-aos="fade-up"
               class="self-start lg:self-auto inline-flex items-center gap-2 px-6 py-3 rounded-full border border-white/15 hover:border-secondary/50 text-sm font-bold text-slate-200 hover:text-white whitespace-nowrap transition-colors">
                همه نمونه‌کارها
                <svg width="16" height="16" class="shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.61l4.16 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.61 9.25h10.64A.75.75 0 0 1 17 10Z" clip-rule="evenodd"/></svg>
            </a>
        </header>

        <div class="flex flex-col gap-8 lg:gap-10">
            <?php foreach ($items as $i => $item) : ?>
                <article class="stack-card" style="--i:<?= (int) $i; ?>">
                    <a href="<?= esc_url($item['link']); ?>"
                       class="group grid grid-cols-1 lg:grid-cols-12 gap-0 rounded-[2rem] overflow-hidden bg-slate-900 border border-white/10 hover:border-secondary/40 shadow-[0_-20px_60px_-20px_rgba(0,0,0,0.8)] transition-colors duration-500">
                        <div class="lg:col-span-5 p-7 sm:p-10 flex flex-col justify-between gap-8 order-2 lg:order-1 bg-gradient-to-br <?= esc_attr($accents[$i % 4]); ?> to-transparent">
                            <div>
                                <div class="flex items-center gap-3 mb-6">
                                    <span class="font-mono text-sm font-bold text-slate-400" dir="ltr">0<?= (int) $i + 1; ?> / 0<?= count($items); ?></span>
                                    <?php if ($item['term']) : ?>
                                        <span class="px-3 py-1 rounded-full bg-white/[0.06] border border-white/10 text-[11px] font-bold text-slate-200"><?= esc_html($item['term']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <h3 class="text-2xl sm:text-3xl font-black text-white leading-snug mb-4 group-hover:text-secondary transition-colors"><?= esc_html($item['title']); ?></h3>
                                <?php if ($item['excerpt']) : ?>
                                    <p class="text-sm sm:text-base text-slate-300 leading-loose"><?= esc_html($item['excerpt']); ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="flex flex-wrap items-center gap-3">
                                <span class="inline-flex items-center gap-2 px-6 py-3 rounded-full bg-white text-slate-950 text-sm font-black whitespace-nowrap group-hover:bg-secondary transition-colors">
                                    مطالعه کیس‌استادی
                                    <svg width="16" height="16" class="shrink-0 group-hover:-translate-x-1 transition-transform" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.61l4.16 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.61 9.25h10.64A.75.75 0 0 1 17 10Z" clip-rule="evenodd"/></svg>
                                </span>
                                <?php if ($item['site']) : ?>
                                    <span class="text-xs font-mono text-slate-400 truncate max-w-[12rem]" dir="ltr"><?= esc_html(preg_replace('#^https?://(www\.)?#', '', untrailingslashit($item['site']))); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="lg:col-span-7 order-1 lg:order-2 relative aspect-[16/10] lg:aspect-auto lg:min-h-[30rem] overflow-hidden bg-slate-950">
                            <!-- Browser chrome -->
                            <div class="absolute inset-x-6 top-6 sm:inset-x-10 sm:top-10 bottom-0 rounded-t-2xl overflow-hidden border border-white/10 border-b-0 bg-slate-900 shadow-2xl group-hover:-translate-y-2 transition-transform duration-700">
                                <div class="flex items-center gap-1.5 px-4 h-8 bg-slate-800/80 border-b border-white/5" dir="ltr">
                                    <span class="size-2.5 rounded-full bg-rose-400/70"></span><span class="size-2.5 rounded-full bg-amber-400/70"></span><span class="size-2.5 rounded-full bg-emerald-400/70"></span>
                                </div>
                                <img src="<?= esc_url($item['cover']['url']); ?>" alt="<?= esc_attr('نمای سایت ' . $item['title']); ?>"
                                     width="<?= (int) $item['cover']['width']; ?>" height="<?= (int) $item['cover']['height']; ?>" loading="lazy" decoding="async"
                                     class="w-full h-auto object-cover object-top transition-transform duration-[4000ms] ease-in-out group-hover:-translate-y-[35%]">
                            </div>
                        </div>
                    </a>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
