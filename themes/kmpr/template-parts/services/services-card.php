<?php
/**
 * Service card (rich): icon, badge, short description, price / duration, deliverable highlights, CTAs.
 * Used in the services archive, "other services" on single pages and search results.
 *
 * $args: 'index' (int) for the mono counter, 'heading' (h2|h3), 'request' (bool) show the request shortcut.
 */
$post_id  = get_the_ID();
$index    = isset($args['index']) ? (int) $args['index'] : -1;
$heading  = (isset($args['heading']) && $args['heading'] === 'h2') ? 'h2' : 'h3';
$request  = !empty($args['request']);
$badge    = get_field('services_badge', $post_id);
$desc     = get_field('services_description', $post_id) ?: wp_trim_words(get_the_excerpt($post_id), 24);
$price    = get_field('starting_price', $post_id) ?: kmpr_srv_opt('srvs_price_fallback');
$duration = get_field('duration', $post_id);
$type     = get_field('request_type', $post_id);
$items    = array_slice(kmpr_srv_rows('deliverables', $post_id), 0, 3);
?>
<article class="spotlight group relative h-full flex flex-col p-6 sm:p-8 rounded-3xl bg-white/[0.025] border border-white/10 hover:border-secondary/40 transition-colors duration-500 overflow-hidden">
    <div class="flex items-start justify-between gap-4 mb-7">
        <span class="size-14 rounded-2xl bg-secondary/10 border border-secondary/25 text-secondary flex items-center justify-center shrink-0 group-hover:scale-110 group-hover:-rotate-6 transition-transform duration-500">
            <?= kmpr_service_icon($post_id, 28); // sanitised in helper ?>
        </span>
        <?php if ($index >= 0) : ?>
            <span class="text-base font-black text-slate-400 group-hover:text-secondary transition-colors"><?= esc_html(kmpr_fa_num(sprintf('%02d', $index + 1))); ?></span>
        <?php endif; ?>
    </div>

    <?php if ($badge) : ?>
        <span class="self-start mb-3 px-2.5 py-1 rounded-full bg-secondary/15 text-secondary text-xs font-black"><?= esc_html($badge); ?></span>
    <?php endif; ?>

    <<?= $heading; ?> class="text-xl lg:text-2xl font-black text-white mb-3">
        <a href="<?php the_permalink(); ?>" class="after:absolute after:inset-0 after:content-[''] focus-visible:outline-none group-hover:text-secondary transition-colors"><?php the_title(); ?></a>
    </<?= $heading; ?>>
    <p class="text-sm text-slate-300 leading-relaxed mb-6"><?= esc_html($desc); ?></p>

    <?php if ($items) : ?>
        <ul class="space-y-2.5 mb-7">
            <?php foreach ($items as $item) : ?>
                <li class="flex items-start gap-2.5 text-sm text-slate-200">
                    <svg width="18" height="18" class="shrink-0 mt-0.5 text-secondary" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.7 4.15a.75.75 0 0 1 .15 1.05l-8 10.5a.75.75 0 0 1-1.13.08l-4.5-4.5a.75.75 0 1 1 1.06-1.06l3.9 3.9 7.47-9.82a.75.75 0 0 1 1.05-.15Z" clip-rule="evenodd"/></svg>
                    <span><?= esc_html($item['title']); ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <dl class="mt-auto grid grid-cols-2 gap-3 pt-5 border-t border-white/10 mb-6">
        <div>
            <dt class="text-xs font-bold text-slate-400 mb-1">شروع هزینه</dt>
            <dd class="text-base sm:text-lg font-black text-white"><?= esc_html($price); ?></dd>
        </div>
        <div>
            <dt class="text-xs font-bold text-slate-400 mb-1">زمان تقریبی</dt>
            <dd class="text-base sm:text-lg font-black text-white"><?= esc_html($duration ?: 'بعد از بررسی'); ?></dd>
        </div>
    </dl>

    <div class="flex flex-wrap items-center gap-3">
        <span class="inline-flex items-center gap-2 text-sm font-black text-secondary">
            جزئیات و مراحل
            <svg width="16" height="16" class="shrink-0 group-hover:-translate-x-1.5 transition-transform" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.61l4.16 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.61 9.25h10.64A.75.75 0 0 1 17 10Z" clip-rule="evenodd"/></svg>
        </span>
        <?php if ($request) : ?>
            <a href="#smart-configurator" data-srv-pick data-type="<?= esc_attr($type); ?>" data-service="<?= esc_attr(get_the_title()); ?>"
               class="relative z-10 ms-auto inline-flex items-center justify-center min-h-11 px-5 py-2.5 rounded-full bg-white/[0.04] hover:bg-secondary border border-white/10 hover:border-secondary text-slate-100 hover:text-slate-950 text-xs font-black whitespace-nowrap transition-colors">
                درخواست این خدمت
            </a>
        <?php endif; ?>
    </div>
</article>
