<?php
/**
 * Grid of portfolio cards (homepage / case-study "more projects").
 *
 * Args:
 *   postCount (int, default 3)  exclude (int[])  term (WP_Term|null, preferred category first)
 *   kicker, title (strings)     id (section id)
 */
$count   = max(1, (int) ($args['postCount'] ?? 3));
$exclude = array_map('intval', (array) ($args['exclude'] ?? array()));
$term    = $args['term'] ?? null;
$kicker  = $args['kicker'] ?? 'نمونه‌کارها';
$title   = $args['title'] ?? 'پروژه‌های اخیر';
$sid     = $args['id'] ?? 'pf-recent';

$ids = array();
if ($term instanceof WP_Term) {
    $ids = get_posts(array(
        'post_type'      => 'portfolio',
        'posts_per_page' => $count,
        'post__not_in'   => $exclude,
        'fields'         => 'ids',
        'tax_query'      => array(array('taxonomy' => 'portfolio_categories', 'terms' => $term->term_id)),
    ));
}
if (count($ids) < $count) {
    $ids = array_merge($ids, get_posts(array(
        'post_type'      => 'portfolio',
        'posts_per_page' => $count - count($ids),
        'post__not_in'   => array_merge($exclude, $ids),
        'fields'         => 'ids',
    )));
}
if (!$ids) {
    return;
}
?>
<section class="relative py-16 lg:py-24 border-t border-white/10" aria-labelledby="<?= esc_attr($sid); ?>-title">
    <div class="container mx-auto px-4 sm:px-6">
        <header class="flex flex-col sm:flex-row sm:items-end justify-between gap-6 mb-10 lg:mb-12">
            <div>
                <span class="inline-flex items-center gap-2 text-xs font-black text-secondary mb-4" data-aos="fade-up">
                    <span class="w-6 h-px bg-secondary"></span><?= esc_html($kicker); ?>
                </span>
                <h2 id="<?= esc_attr($sid); ?>-title" class="text-3xl sm:text-4xl font-black text-white leading-tight" data-split><?= esc_html($title); ?></h2>
            </div>
            <a href="<?= esc_url(get_post_type_archive_link('portfolio') ?: home_url('/portfolios/')); ?>"
               class="self-start sm:self-auto inline-flex items-center gap-2 min-h-11 px-6 py-3 rounded-full border border-white/15 hover:border-secondary/50 text-sm font-bold text-slate-200 hover:text-white whitespace-nowrap transition-colors">
                همه نمونه‌کارها
                <svg width="16" height="16" class="shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.61l4.16 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.61 9.25h10.64A.75.75 0 0 1 17 10Z" clip-rule="evenodd"/></svg>
            </a>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 lg:gap-8" data-stagger="90">
            <?php foreach ($ids as $i => $pid) : ?>
                <div data-aos="fade-up" class="<?= $i === 2 ? 'md:hidden xl:block' : ''; ?>">
                    <?php get_template_part('template-parts/portfolio/portfolio-card', null, array('class' => 'h-full', 'post_id' => $pid)); ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
