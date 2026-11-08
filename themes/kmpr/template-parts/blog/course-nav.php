<?php
/**
 * Course lesson list (lesson page sidebar + mobile sheet).
 * $args: pos (kmpr_lesson_position), info (kmpr_course_info), compact (bool, no header link)
 * Requires an ancestor x-data exposing `ids` (lesson ids) — progress comes from $store.lessons.
 */
$pos  = $args['pos'];
$info = $args['info'];
$pal  = kmpr_course_palette($info['color']);
$cur  = (int) get_the_ID();
?>
<div class="flex flex-col min-h-0">
    <div class="p-5 border-b border-white/10">
        <a href="<?= esc_url($info['url']); ?>" class="group inline-flex items-center gap-2 text-xs font-black <?= esc_attr($pal['text']); ?> mb-2">
            <span class="size-1.5 rounded-full <?= esc_attr($pal['bg']); ?>" aria-hidden="true"></span>
            <span class="text-sm group-hover:underline underline-offset-4"><?= esc_html($info['title']); ?></span>
        </a>
        <div class="flex items-center justify-between gap-3 text-[13px] text-slate-400 mb-2">
            <span><span x-text="$store.lessons.fa($store.lessons.doneCount(ids))">۰</span> از <?= esc_html(kmpr_fa_num($pos['total'])); ?> درس تکمیل شده</span>
            <span class="text-base font-black text-white" x-text="$store.lessons.fa($store.lessons.progress(ids)) + '٪'">۰٪</span>
        </div>
        <div class="h-1.5 rounded-full bg-white/10 overflow-hidden" role="progressbar" aria-label="پیشرفت دوره" aria-valuemin="0" aria-valuemax="100" :aria-valuenow="$store.lessons.progress(ids)">
            <div class="h-full rounded-full <?= esc_attr($pal['bg']); ?> transition-all duration-700" style="width:0" :style="'width:' + $store.lessons.progress(ids) + '%'"></div>
        </div>
    </div>
    <ol class="relative list-none m-0 px-2 py-3 overflow-y-auto overscroll-contain course-nav-list"
        x-init="$nextTick(() => { const a = $el.querySelector('[aria-current]'); if (!a) return; const li = a.parentElement; if (li.offsetTop + li.offsetHeight > $el.clientHeight - 16) $el.scrollTop = Math.max(0, li.offsetTop - 24); })">
        <?php foreach ($pos['lessons'] as $i => $lid) :
            $is_cur = ($lid === $cur); ?>
            <li>
                <a href="<?= esc_url(get_permalink($lid)); ?>" <?= $is_cur ? 'aria-current="page"' : ''; ?>
                   class="group flex items-start gap-3 p-3 rounded-2xl transition-colors <?= $is_cur ? 'bg-secondary/15 border border-secondary/30' : 'border border-transparent hover:bg-white/[0.04]'; ?>">
                    <span class="shrink-0 inline-flex items-center justify-center size-8 rounded-lg text-sm font-black transition-colors"
                          :class="$store.lessons.has(<?= (int) $lid; ?>) ? 'bg-emerald-500 text-slate-950' : '<?= $is_cur ? 'bg-secondary text-slate-950' : 'bg-white/[0.06] text-slate-300'; ?>'">
                        <span x-show="!$store.lessons.has(<?= (int) $lid; ?>)"><?= esc_html(kmpr_fa_num($i + 1)); ?></span>
                        <svg x-show="$store.lessons.has(<?= (int) $lid; ?>)" x-cloak width="14" height="14" class="shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-8 8a1 1 0 0 1-1.4 0l-4-4a1 1 0 1 1 1.4-1.4L8 12.58l7.3-7.3a1 1 0 0 1 1.4 0Z" clip-rule="evenodd"/></svg>
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm sm:text-[15px] leading-7 <?= $is_cur ? 'font-black text-white' : 'font-bold text-slate-300 group-hover:text-white'; ?>"><?= esc_html(get_the_title($lid)); ?></span>
                        <span class="mt-1 flex flex-wrap items-center gap-2 text-[13px] text-slate-400">
                            <?php if ($is_cur) : ?><span class="px-2 py-0.5 rounded-full bg-secondary/20 text-amber-300 text-xs font-bold">در حال مطالعه</span><?php endif; ?>
                            <span><?= esc_html(kmpr_fa_num(reading_time($lid))); ?> دقیقه</span>
                        </span>
                    </span>
                </a>
            </li>
        <?php endforeach; ?>
    </ol>
</div>
