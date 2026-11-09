<?php
/**
 * Course card (blog hub). $args['course'] = kmpr_course_info(), $args['index'] = int, $args['class'] = grid span classes.
 * Progress / continue link are computed client-side from $store.lessons.
 */
$c = $args['course'] ?? null;
if (!$c) {
    return;
}
$p    = kmpr_course_palette($c['color']);
$ids  = array_map('intval', $c['lessons']);
$urls = array_map('get_permalink', $ids);
$idx  = (int) ($args['index'] ?? 0);
?>
<article data-aos="fade-up" class="h-full <?= esc_attr($args['class'] ?? ''); ?>"
         x-data="{ ids: <?= esc_attr(wp_json_encode($ids)); ?>, urls: <?= esc_attr(wp_json_encode($urls)); ?> }">
    <div class="spotlight relative h-full flex flex-col rounded-3xl bg-white/[0.025] border border-white/10 <?= esc_attr($p['hover']); ?> transition-colors duration-500 overflow-hidden p-6 sm:p-7">
        <div class="pointer-events-none absolute -top-24 -end-24 size-64 rounded-full bg-gradient-to-br <?= esc_attr($p['glow']); ?> to-transparent blur-2xl" aria-hidden="true"></div>

        <div class="relative flex items-start justify-between gap-4 mb-5">
            <span class="inline-flex items-center justify-center size-12 rounded-2xl <?= esc_attr($p['soft'] . ' ' . $p['text']); ?> border <?= esc_attr($p['border']); ?> text-xl font-black">
                <?= esc_html(kmpr_fa_num($idx + 1)); ?>
            </span>
            <span class="text-[13px] font-bold text-slate-400 whitespace-nowrap"
                  x-text="$store.lessons.doneCount(ids) ? $store.lessons.fa($store.lessons.doneCount(ids)) + ' از ' + $store.lessons.fa(ids.length) + ' درس' : ''"></span>
        </div>

        <h3 class="relative text-xl sm:text-2xl font-black text-white leading-snug mb-3">
            <a href="<?= esc_url($c['url']); ?>" class="hover:underline decoration-2 underline-offset-8 <?= esc_attr($p['deco']); ?>"><?= esc_html($c['title']); ?></a>
        </h3>
        <?php if ($c['intro']) : ?>
            <p class="relative text-sm text-slate-300 leading-loose mb-6 line-clamp-3"><?= esc_html($c['intro']); ?></p>
        <?php endif; ?>

        <div class="relative mt-auto">
            <ul class="flex flex-wrap items-center gap-x-4 gap-y-2 text-[13px] text-slate-400 mb-4">
                <li class="inline-flex items-center gap-1.5">
                    <svg width="14" height="14" class="shrink-0 <?= esc_attr($p['text']); ?>" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"/></svg>
                    <?= esc_html(kmpr_fa_num($c['count'])); ?> درس
                </li>
                <li class="inline-flex items-center gap-1.5">
                    <svg width="14" height="14" class="shrink-0 <?= esc_attr($p['text']); ?>" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
                    <?= esc_html(kmpr_minutes_label($c['minutes'])); ?> مطالعه
                </li>
            </ul>

            <div class="h-1.5 rounded-full bg-white/10 overflow-hidden mb-5" role="progressbar" aria-label="پیشرفت دوره" aria-valuemin="0" aria-valuemax="100"
                 :aria-valuenow="$store.lessons.progress(ids)">
                <div class="h-full rounded-full <?= esc_attr($p['bg']); ?> transition-all duration-700" style="width:0" :style="'width:' + $store.lessons.progress(ids) + '%'"></div>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <a href="<?= esc_url($urls[0] ?? $c['url']); ?>" :href="$store.lessons.nextUrl(ids, urls)"
                   class="inline-flex items-center justify-center gap-2 min-h-11 px-6 py-3 rounded-full bg-secondary hover:bg-amber-500 text-slate-950 text-sm font-black whitespace-nowrap transition-colors">
                    <span x-text="$store.lessons.progress(ids) === 100 ? 'مرور دوبارهٔ دوره' : ($store.lessons.started(ids) ? 'ادامه یادگیری' : 'شروع دوره')">شروع دوره</span>
                    <svg width="16" height="16" class="shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.61l4.16 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.61 9.25h10.64A.75.75 0 0 1 17 10Z" clip-rule="evenodd"/></svg>
                </a>
                <a href="<?= esc_url($c['url']); ?>" class="inline-flex items-center min-h-11 px-4 py-3 text-sm font-bold text-slate-300 hover:text-white whitespace-nowrap transition-colors">
                    سرفصل‌ها
                </a>
            </div>
        </div>
    </div>
</article>
