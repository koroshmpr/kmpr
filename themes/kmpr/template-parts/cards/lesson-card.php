<?php
/**
 * Lesson card (used inside The Loop). Shows course + lesson number, level, reading time and completion state.
 * $args['eager'] = true for above-the-fold cards.
 */
$pid   = get_the_ID();
$pos   = kmpr_lesson_position($pid);
$info  = $pos ? kmpr_course_info($pos['term']) : null;
$pal   = kmpr_course_palette($info['color'] ?? 'amber');
$level = kmpr_lesson_level($pid);
$eager = !empty($args['eager']);
?>
<article data-aos="fade-up" class="h-full">
    <a href="<?php the_permalink(); ?>" class="spotlight group relative flex flex-col h-full rounded-3xl overflow-hidden bg-white/[0.025] border border-white/10 hover:border-secondary/40 transition-colors duration-500">
        <div class="relative aspect-[16/9] overflow-hidden bg-slate-900 shrink-0">
            <?php if (has_post_thumbnail()) :
                the_post_thumbnail('medium_large', array(
                    'class'   => 'absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-700',
                    'loading' => $eager ? 'eager' : 'lazy',
                    'alt'     => esc_attr(get_the_title()),
                ));
            else : ?>
                <div class="absolute inset-0 bg-gradient-to-br <?= esc_attr($pal['glow']); ?> via-slate-900 to-slate-950 flex items-center justify-center" aria-hidden="true">
                    <span class="font-mono text-5xl font-black text-white/10">&lt;/&gt;</span>
                </div>
            <?php endif; ?>
            <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-transparent to-transparent" aria-hidden="true"></div>
            <span x-show="$store.lessons.has(<?= (int) $pid; ?>)" x-cloak
                  class="absolute top-3 start-3 inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-500 text-slate-950 text-xs font-black">
                <svg width="12" height="12" class="shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-8 8a1 1 0 0 1-1.4 0l-4-4a1 1 0 1 1 1.4-1.4L8 12.58l7.3-7.3a1 1 0 0 1 1.4 0Z" clip-rule="evenodd"/></svg>
                خوانده‌شده
            </span>
            <?php if ($pos) : ?>
                <span class="absolute bottom-3 start-3 inline-flex items-center gap-2 px-3 py-1 rounded-full bg-slate-950/85 backdrop-blur border border-white/10 text-xs font-bold text-slate-200">
                    <span class="size-1.5 rounded-full <?= esc_attr($pal['bg']); ?>" aria-hidden="true"></span>
                    درس <?= esc_html(kmpr_fa_num($pos['number'])); ?> از <?= esc_html(kmpr_fa_num($pos['total'])); ?>
                </span>
            <?php endif; ?>
        </div>
        <div class="flex flex-col flex-1 gap-3 p-6">
            <?php if ($info) : ?>
                <span class="text-[13px] font-black <?= esc_attr($pal['text']); ?>"><?= esc_html($info['title']); ?></span>
            <?php endif; ?>
            <h3 class="text-lg font-black text-white leading-snug group-hover:text-secondary transition-colors"><?php the_title(); ?></h3>
            <p class="text-sm text-slate-300 leading-loose line-clamp-2"><?= esc_html(wp_trim_words(get_the_excerpt(), 24)); ?></p>
            <div class="mt-auto pt-4 border-t border-white/10 flex flex-wrap items-center gap-x-4 gap-y-2 text-[13px] text-slate-400">
                <time datetime="<?= esc_attr(get_the_date('c')); ?>"><?= esc_html(shamsi_date('j F Y', get_the_time('U'))); ?></time>
                <span class="inline-flex items-center gap-1">
                    <svg width="13" height="13" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
                    <?= esc_html(kmpr_fa_num(reading_time($pid))); ?> دقیقه
                </span>
                <?php if ($level) : ?>
                    <span class="ms-auto px-2.5 py-0.5 rounded-full border border-white/10 text-slate-300"><?= esc_html($level['label']); ?></span>
                <?php endif; ?>
            </div>
        </div>
    </a>
</article>
