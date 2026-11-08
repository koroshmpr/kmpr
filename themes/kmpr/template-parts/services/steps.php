<?php
/**
 * Services — scroll-drawn steps timeline.
 * $args: 'steps' (array of [t, d, m?]), 'kicker', 'title', 'text', 'id'
 */
$steps = array_values(array_filter((array) ($args['steps'] ?? array()), function ($r) {
    return is_array($r) && !empty($r['t']);
}));
if (!$steps) {
    return;
}
$sid = $args['id'] ?? 'srv-steps-title';
?>
<section class="relative py-20 lg:py-28 border-t border-white/10 overflow-x-clip" aria-labelledby="<?= esc_attr($sid); ?>">
    <div class="aurora w-[30rem] h-[30rem] top-1/3 -end-60 bg-secondary/15 -z-10" aria-hidden="true"></div>
    <div class="container mx-auto px-4 sm:px-6">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-16">
            <div class="lg:col-span-5 lg:sticky lg:top-32 self-start">
                <?php kmpr_srv_head($args['kicker'] ?? '', $args['title'] ?? '', $args['text'] ?? '', $sid); ?>
                <a href="#smart-configurator" data-aos="fade-up" data-aos-delay="150"
                   class="hidden lg:inline-flex items-center gap-2 min-h-11 px-7 py-3.5 rounded-full bg-secondary hover:bg-amber-500 text-slate-950 text-sm font-black whitespace-nowrap transition-colors">
                    همین حالا شروع کنیم
                </a>
            </div>

            <ol class="lg:col-span-7 relative ps-10 sm:ps-14" data-progress>
                <span class="absolute start-[0.95rem] sm:start-[1.45rem] top-2 bottom-2 w-px bg-white/10" aria-hidden="true"></span>
                <span class="absolute start-[0.95rem] sm:start-[1.45rem] top-2 bottom-2 w-px bg-gradient-to-b from-secondary to-amber-300 origin-top" style="transform: scaleY(var(--p, 0))" aria-hidden="true"></span>
                <?php foreach ($steps as $i => $step) : ?>
                    <li class="relative pb-8 sm:pb-10 last:pb-0" data-aos="fade-up">
                        <span class="absolute -start-10 sm:-start-14 top-0 size-8 sm:size-12 rounded-full bg-slate-950 border border-secondary/50 text-secondary font-black text-sm sm:text-base flex items-center justify-center shadow-[0_0_0_6px] shadow-slate-950">
                            <?= esc_html(kmpr_fa_num($i + 1)); ?>
                        </span>
                        <div class="spotlight p-6 sm:p-7 rounded-3xl bg-white/[0.025] border border-white/10 hover:border-white/20 transition-colors">
                            <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
                                <h3 class="text-lg sm:text-xl font-black text-white"><?= esc_html($step['t']); ?></h3>
                                <?php if (!empty($step['m'])) : ?>
                                    <span class="text-xs font-bold text-slate-300 px-2.5 py-1 rounded-full bg-white/[0.05] whitespace-nowrap"><?= esc_html($step['m']); ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($step['d'])) : ?>
                                <p class="text-sm sm:text-base text-slate-300 leading-loose"><?= esc_html($step['d']); ?></p>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ol>
        </div>
    </div>
</section>
