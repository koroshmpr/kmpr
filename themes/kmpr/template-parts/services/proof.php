<?php
/**
 * Services — related portfolio proof grid (screenshot covers).
 * $args: 'ids' (portfolio IDs), 'kicker', 'title', 'id'
 */
$ids = array_filter(array_map('intval', (array) ($args['ids'] ?? array())));
if (!$ids) {
    return;
}
$sid     = $args['id'] ?? 'srv-proof-title';
$archive = get_post_type_archive_link('portfolio') ?: home_url('/portfolios/');
?>
<section class="relative py-20 lg:py-28 border-t border-white/10" aria-labelledby="<?= esc_attr($sid); ?>">
    <div class="container mx-auto px-4 sm:px-6">
        <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-2 lg:gap-6">
            <?php kmpr_srv_head($args['kicker'] ?? '', $args['title'] ?? '', '', $sid); ?>
            <a href="<?= esc_url($archive); ?>" data-aos="fade-up"
               class="self-start lg:self-auto mb-10 lg:mb-14 inline-flex items-center gap-2 min-h-11 px-6 py-3 rounded-full bg-white/[0.04] hover:bg-white/[0.08] border border-white/15 text-slate-100 text-sm font-bold whitespace-nowrap transition-colors">
                همه نمونه‌کارها
                <svg width="16" height="16" class="shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.61l4.16 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.61 9.25h10.64A.75.75 0 0 1 17 10Z" clip-rule="evenodd"/></svg>
            </a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 lg:gap-6" data-stagger="90">
            <?php foreach (array_values($ids) as $n => $pid) :
                $cover = kmpr_portfolio_cover($pid, 'large', 'laptop');
                $year  = get_field('project_year', $pid);
                $term  = kmpr_primary_term($pid, 'portfolio_categories');
                ?>
                <article class="group relative rounded-3xl bg-white/[0.025] border border-white/10 hover:border-secondary/40 overflow-hidden transition-colors duration-500<?= $n > 2 ? ' max-sm:hidden' : ''; ?>" data-aos="fade-up">
                    <div class="relative aspect-[16/10] overflow-hidden bg-slate-900">
                        <?php if ($cover) : ?>
                            <img src="<?= esc_url($cover['url']); ?>" alt="<?= esc_attr($cover['alt']); ?>" width="<?= (int) $cover['width']; ?>" height="<?= (int) $cover['height']; ?>"
                                 loading="lazy" decoding="async" class="w-full h-full object-cover object-top transition-transform duration-[1.6s] ease-out group-hover:scale-105">
                        <?php else : ?>
                            <div class="w-full h-full hero-grid" aria-hidden="true"></div>
                        <?php endif; ?>
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-transparent to-transparent" aria-hidden="true"></div>
                    </div>
                    <div class="p-5 sm:p-6 flex items-center justify-between gap-4">
                        <div class="min-w-0">
                            <h3 class="text-base sm:text-lg font-black text-white truncate">
                                <a href="<?= esc_url(get_permalink($pid)); ?>" class="after:absolute after:inset-0 after:content-[''] group-hover:text-secondary transition-colors"><?= esc_html(get_the_title($pid)); ?></a>
                            </h3>
                            <p class="text-xs text-slate-400 mt-1 truncate">
                                <?= esc_html(implode(' · ', array_filter(array($term ? $term->name : '', $year ? kmpr_fa_num($year) : '')))); ?>
                            </p>
                        </div>
                        <span class="size-10 shrink-0 rounded-full bg-white/[0.05] border border-white/10 group-hover:bg-secondary group-hover:border-secondary group-hover:text-slate-950 text-slate-300 flex items-center justify-center transition-colors">
                            <svg width="16" height="16" class="shrink-0 rotate-45" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.61l4.16 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.61 9.25h10.64A.75.75 0 0 1 17 10Z" clip-rule="evenodd"/></svg>
                        </span>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
