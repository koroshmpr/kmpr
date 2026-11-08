<?php
/**
 * Home — infinite tech marquee (two rows, opposite directions)
 */
$rows = array_values(array_filter(array(kmpr_lines(kmpr_field('marquee_row_1')), kmpr_lines(kmpr_field('marquee_row_2')))));
if (!$rows) {
    return;
}
?>
<section class="relative py-10 lg:py-14 border-y border-white/10 bg-slate-950/60" aria-label="ابزارها و فناوری‌ها">
    <p class="text-center text-xs font-bold text-slate-400 mb-6" data-aos="fade-up"><?= esc_html(kmpr_field('marquee_title')); ?></p>
    <div class="flex flex-col gap-4" dir="ltr">
        <?php foreach ($rows as $r => $items) : ?>
            <div class="marquee <?= $r ? 'marquee--reverse' : ''; ?>" style="--speed:<?= $r ? 55 : 45; ?>s; --gap:0.75rem">
                <?php for ($copy = 0; $copy < 2; $copy++) : ?>
                    <ul class="marquee__track" <?= $copy ? 'aria-hidden="true"' : ''; ?>>
                        <?php foreach ($items as $item) : ?>
                            <li class="flex items-center gap-2.5 px-5 py-3 rounded-2xl bg-white/[0.03] border border-white/10 text-sm font-mono font-bold text-slate-200 whitespace-nowrap">
                                <span class="size-1.5 rounded-full bg-secondary shrink-0"></span><?= esc_html($item); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endfor; ?>
            </div>
        <?php endforeach; ?>
    </div>
</section>
