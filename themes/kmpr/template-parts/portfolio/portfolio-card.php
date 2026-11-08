<?php
/**
 * Portfolio card — browser-frame screenshot that auto-scrolls on hover.
 * Must be called inside the loop (or pass 'post_id').
 *
 * Args: class (string), post_id (int), eager (bool), heading ('h2'|'h3').
 */
$card_class = $args['class'] ?? '';
$pf         = kmpr_pf_data($args['post_id'] ?? get_the_ID(), 'medium_large');
$eager      = !empty($args['eager']);
$heading    = (isset($args['heading']) && $args['heading'] === 'h2') ? 'h2' : 'h3';
$cover      = $pf['cover'];
$ratio      = ($cover && $cover['width']) ? $cover['height'] / $cover['width'] : 0.625;
$duration   = (int) max(1200, min(9000, ($ratio - 0.625) * 2200));
$chips      = array_slice($pf['stack'] ?: array_filter(array($pf['theme'], $pf['builder'])), 0, 4);
?>
<article class="pf-card spotlight group relative flex flex-col h-full rounded-3xl bg-slate-900/70 border border-white/10 hover:border-secondary/40 overflow-hidden transition-[border-color,transform,box-shadow] duration-500 hover:-translate-y-1 hover:shadow-[0_30px_80px_-30px_rgba(217,119,6,0.35)] <?= esc_attr($card_class); ?>">

    <!-- Browser frame -->
    <div class="relative px-3 pt-3 sm:px-4 sm:pt-4 bg-gradient-to-b from-white/[0.05] to-transparent">
        <div class="rounded-t-2xl overflow-hidden border border-white/10 border-b-0 bg-slate-950 shadow-2xl">
            <div class="flex items-center gap-1.5 px-3 h-7 bg-slate-800/80 border-b border-white/5" dir="ltr" aria-hidden="true">
                <span class="size-2 rounded-full bg-rose-400/70"></span>
                <span class="size-2 rounded-full bg-amber-400/70"></span>
                <span class="size-2 rounded-full bg-emerald-400/70"></span>
                <?php if ($pf['host']) : ?>
                    <span class="ms-2 min-w-0 flex-1 max-w-[70%] truncate rounded-full bg-slate-950/70 px-2.5 py-0.5 text-xs font-mono text-slate-400"><?= esc_html($pf['host']); ?></span>
                <?php endif; ?>
            </div>
            <div class="pf-screen relative aspect-[16/10] overflow-hidden">
                <?php if ($cover) : ?>
                    <img src="<?= esc_url($cover['url']); ?>"
                         alt="<?= esc_attr('نمای صفحه وب‌سایت ' . $pf['title']); ?>"
                         width="<?= (int) $cover['width']; ?>" height="<?= (int) $cover['height']; ?>"
                         loading="<?= $eager ? 'eager' : 'lazy'; ?>" decoding="async"
                         class="pf-shot absolute inset-0 w-full h-full object-cover object-top"
                         style="--pf-dur:<?= (int) $duration; ?>ms">
                <?php else : ?>
                    <div class="pf-fallback absolute inset-0 flex items-center justify-center">
                        <?php if ($pf['logo']) : ?>
                            <span class="relative flex items-center justify-center size-20 rounded-3xl border border-white/15 shadow-2xl <?= esc_attr($pf['logo_bg']); ?>">
                                <img src="<?= esc_url($pf['logo']['url']); ?>" alt="<?= esc_attr('لوگوی ' . ($pf['client'] ?: $pf['title'])); ?>" width="56" height="56" loading="lazy" class="size-14 object-contain">
                            </span>
                        <?php else : ?>
                            <span class="relative text-6xl font-black text-white/90" aria-hidden="true"><?= esc_html($pf['initial']); ?></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <span class="pointer-events-none absolute inset-x-0 bottom-0 h-16 bg-gradient-to-t from-slate-950/70 to-transparent opacity-100 group-hover:opacity-0 transition-opacity duration-500" aria-hidden="true"></span>
            </div>
        </div>
    </div>

    <!-- Details -->
    <div class="relative flex flex-col flex-1 gap-3 p-5 sm:p-6 border-t border-white/10">
        <div class="flex items-center gap-3 min-w-0">
            <?php if ($pf['logo']) : ?>
                <span class="flex items-center justify-center size-11 shrink-0 rounded-2xl border border-white/10 <?= esc_attr($pf['logo_bg']); ?>">
                    <img src="<?= esc_url($pf['logo']['url']); ?>" alt="<?= esc_attr('لوگوی ' . ($pf['client'] ?: $pf['title'])); ?>" width="28" height="28" loading="lazy" class="size-7 object-contain">
                </span>
            <?php else : ?>
                <span class="flex items-center justify-center size-11 shrink-0 rounded-2xl bg-secondary/15 border border-secondary/30 text-secondary font-black" aria-hidden="true"><?= esc_html($pf['initial']); ?></span>
            <?php endif; ?>
            <div class="min-w-0 flex flex-col">
                <span class="text-xs font-bold text-slate-400 truncate">
                    <?= esc_html($pf['term'] ? $pf['term']->name : 'نمونه‌کار'); ?><?php if ($pf['year']) : ?> <span class="text-slate-600" aria-hidden="true">•</span> <?= esc_html(kmpr_fa_num($pf['year'])); ?><?php endif; ?>
                </span>
                <?php if ($pf['client']) : ?>
                    <span class="text-xs text-slate-300 truncate"><?= esc_html($pf['client']); ?></span>
                <?php endif; ?>
            </div>
        </div>

        <<?= $heading; ?> class="text-lg sm:text-xl font-black text-white leading-snug group-hover:text-secondary transition-colors">
            <a href="<?= esc_url($pf['link']); ?>" class="after:absolute after:inset-0 after:z-10 after:content-[''] focus:outline-none focus-visible:after:ring-2 focus-visible:after:ring-secondary focus-visible:after:rounded-3xl">
                <?= esc_html($pf['title']); ?>
            </a>
        </<?= $heading; ?>>

        <?php if ($pf['excerpt']) : ?>
            <p class="text-sm text-slate-300 leading-relaxed line-clamp-2"><?= esc_html(wp_strip_all_tags($pf['excerpt'])); ?></p>
        <?php endif; ?>

        <?php if ($chips) : ?>
            <ul class="flex flex-wrap gap-1.5" aria-label="تکنولوژی‌ها">
                <?php foreach ($chips as $chip) : ?>
                    <li class="px-2.5 py-1 rounded-lg bg-white/[0.04] border border-white/10 text-xs font-bold text-slate-300"><?= esc_html($chip); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <div class="mt-auto pt-4 border-t border-white/10 flex items-center justify-between gap-3">
            <span class="inline-flex items-center gap-1.5 text-sm font-black text-secondary whitespace-nowrap">
                مطالعه کیس‌استادی
                <svg width="16" height="16" class="shrink-0 transition-transform duration-300 group-hover:-translate-x-1" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.61l4.16 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.61 9.25h10.64A.75.75 0 0 1 17 10Z" clip-rule="evenodd"/></svg>
            </span>
            <?php if ($cover && $ratio > 1.2) : ?>
                <span class="hidden sm:inline-flex items-center gap-1 text-xs font-bold text-slate-400 whitespace-nowrap">
                    <svg width="14" height="14" class="shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 3a.75.75 0 0 1 .75.75v10.638l3.96-4.158a.75.75 0 1 1 1.08 1.04l-5.25 5.5a.75.75 0 0 1-1.08 0l-5.25-5.5a.75.75 0 1 1 1.08-1.04l3.96 4.158V3.75A.75.75 0 0 1 10 3Z" clip-rule="evenodd"/></svg>
                    صفحه کامل
                </span>
            <?php endif; ?>
        </div>
    </div>
</article>
