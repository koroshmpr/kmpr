<?php
/**
 * Home — Courses (the blog as pillar-cluster courses).
 *
 * Course switcher tabs (role=tablist) + per course: a compact summary and a CSS scroll-snap slider of
 * numbered lesson cards in lesson order (pillar first). Completion comes from $store.lessons (javascript/blog.js).
 * Without JS: tabs stay hidden and every course renders stacked; cards are plain links in a scrollable row.
 * Copy in «صفحه اصلی › مقالات».
 */
if (!function_exists('kmpr_courses')) {
    return;
}
$courses = kmpr_courses();
if (!$courses) {
    return;
}
$blog_url = get_permalink(get_option('page_for_posts')) ?: home_url('/blog/');
$levels   = array('beginner' => 'مقدماتی', 'intermediate' => 'متوسط', 'advanced' => 'پیشرفته');
$lvl_cls  = array(
    'beginner'     => 'text-emerald-300 bg-emerald-400/10 border-emerald-400/25',
    'intermediate' => 'text-amber-300 bg-amber-400/10 border-amber-400/25',
    'advanced'     => 'text-rose-300 bg-rose-400/10 border-rose-400/25',
);
$arrow = '<svg width="16" height="16" class="shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.61l4.16 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.61 9.25h10.64A.75.75 0 0 1 17 10Z" clip-rule="evenodd"/></svg>';
$check = '<svg width="12" height="12" class="shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-8 8a1 1 0 0 1-1.4 0l-4-4a1 1 0 1 1 1.4-1.4L8 12.58l7.3-7.3a1 1 0 0 1 1.4 0Z" clip-rule="evenodd"/></svg>';
$multi = count($courses) > 1;
?>
<section class="hc relative py-20 lg:py-28 border-t border-white/10" aria-labelledby="articles-title" x-data="kmprCourses(<?= (int) count($courses); ?>)">
    <div class="container mx-auto px-4 sm:px-6">
        <header class="flex flex-col lg:flex-row lg:items-end justify-between gap-6 mb-8 lg:mb-10">
            <div class="max-w-2xl">
                <span class="inline-flex items-center gap-2 text-xs font-black text-secondary mb-4" data-aos="fade-up">
                    <span class="w-6 h-px bg-secondary"></span><?= esc_html(kmpr_field('articles_kicker')); ?>
                </span>
                <h2 id="articles-title" class="text-3xl sm:text-4xl lg:text-5xl font-black text-white leading-tight" <?= kmpr_split_attr(kmpr_field('articles_title')); ?>>
                    <?= esc_html(kmpr_field('articles_title')); ?>
                </h2>
                <?php if (kmpr_field('articles_text')) : ?>
                    <p class="mt-4 text-sm sm:text-base text-slate-300 leading-8" data-aos="fade-up"><?= esc_html(kmpr_field('articles_text')); ?></p>
                <?php endif; ?>
            </div>
            <a href="<?= esc_url($blog_url); ?>" data-aos="fade-up"
               class="self-start lg:self-auto inline-flex items-center gap-2 min-h-11 px-6 py-3 rounded-full border border-white/15 hover:border-secondary/50 text-sm font-bold text-slate-200 hover:text-white whitespace-nowrap transition-colors">
                <?= esc_html(kmpr_field('articles_btn')); ?>
                <?= $arrow; // phpcs:ignore ?>
            </a>
        </header>

        <?php if ($multi) : ?>
            <div class="hc-tabs" role="tablist" aria-label="انتخاب دوره" x-cloak @keydown="onKey($event)">
                <?php foreach ($courses as $i => $c) :
                    $p = kmpr_course_palette($c['color']); ?>
                    <button type="button" role="tab" id="hc-tab-<?= (int) $c['id']; ?>" aria-controls="hc-panel-<?= (int) $c['id']; ?>"
                            aria-selected="<?= $i === 0 ? 'true' : 'false'; ?>" tabindex="<?= $i === 0 ? '0' : '-1'; ?>"
                            :aria-selected="active === <?= (int) $i; ?> ? 'true' : 'false'" :tabindex="active === <?= (int) $i; ?> ? 0 : -1"
                            @click="select(<?= (int) $i; ?>)" class="hc-tab">
                        <span class="size-2 rounded-full shrink-0 <?= esc_attr($p['bg']); ?>" aria-hidden="true"></span>
                        <span class="truncate"><?= esc_html($c['title']); ?></span>
                        <span class="hc-tab__count"><?= esc_html(kmpr_fa_num($c['count'])); ?> درس</span>
                    </button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php foreach ($courses as $i => $c) :
            $p   = kmpr_course_palette($c['color']);
            $ids = array_map('intval', $c['lessons']);
            // Same order + numbering as the lesson page («درس X از Y» in single.php = kmpr_lesson_position()):
            // kmpr_course_lessons() order (lesson_order ASC, pillar = 0 first), number = index + 1, total = count.
            $total = count($ids);
            $urls  = array_map('get_permalink', $ids);
            $found = array();
            foreach ($ids as $id) {
                $k = get_post_meta($id, 'difficulty', true);
                if (isset($levels[$k])) {
                    $found[$k] = true;
                }
            }
            $keys  = array_values(array_intersect(array_keys($levels), array_keys($found)));
            $range = $keys ? ($levels[$keys[0]] . (count($keys) > 1 ? ' تا ' . $levels[end($keys)] : '')) : '';
            $pid   = (int) $c['id'];
            ?>
            <div class="hc-panel" id="hc-panel-<?= $pid; ?>" <?= $multi ? 'role="tabpanel" aria-labelledby="hc-tab-' . $pid . '"' : ''; ?>
                 x-show="active === <?= (int) $i; ?>"
                 x-data="{ ids: <?= esc_attr(wp_json_encode($ids)); ?>, urls: <?= esc_attr(wp_json_encode($urls)); ?> }">

                <aside class="hc-summary" aria-label="خلاصه دوره">
                    <div class="pointer-events-none absolute -top-24 -end-24 size-64 rounded-full bg-gradient-to-br <?= esc_attr($p['glow']); ?> to-transparent blur-2xl" aria-hidden="true"></div>
                    <span class="relative inline-flex items-center gap-2 text-[13px] font-black <?= esc_attr($p['text']); ?>">
                        <svg width="16" height="16" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M22 10 12 5 2 10l10 5 10-5Z"/><path d="M6 12v5c3 2 9 2 12 0v-5"/></svg>
                        دوره رایگان
                    </span>
                    <h3 class="relative mt-2 text-xl sm:text-2xl font-black text-white leading-snug">
                        <a href="<?= esc_url($c['url']); ?>" class="hover:underline decoration-2 underline-offset-8 <?= esc_attr($p['deco']); ?>"><?= esc_html($c['title']); ?></a>
                    </h3>
                    <?php if ($c['intro']) : ?>
                        <p class="relative mt-3 text-sm text-slate-300 leading-7 line-clamp-2"><?= esc_html($c['intro']); ?></p>
                    <?php endif; ?>

                    <ul class="relative mt-5 grid grid-cols-3 gap-2 text-center">
                        <li class="hc-stat"><span class="hc-stat__v"><?= esc_html(kmpr_fa_num($total)); ?></span><span class="hc-stat__k">درس</span></li>
                        <li class="hc-stat"><span class="hc-stat__v"><?= esc_html(kmpr_fa_num($c['minutes'])); ?></span><span class="hc-stat__k">دقیقه مطالعه</span></li>
                        <li class="hc-stat"><span class="hc-stat__v hc-stat__v--sm"><?= esc_html($range ?: '—'); ?></span><span class="hc-stat__k">سطح</span></li>
                    </ul>

                    <div class="relative mt-5">
                        <div class="flex items-center justify-between gap-3 mb-2 text-xs font-bold text-slate-400">
                            <span>پیشرفت شما</span>
                            <span x-text="$store.lessons.fa($store.lessons.doneCount(ids)) + ' از ' + $store.lessons.fa(ids.length) + ' درس'"><?= esc_html(kmpr_fa_num(0) . ' از ' . kmpr_fa_num($total) . ' درس'); ?></span>
                        </div>
                        <div class="h-1.5 rounded-full bg-white/10 overflow-hidden" role="progressbar" aria-label="پیشرفت دوره <?= esc_attr($c['title']); ?>" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"
                             :aria-valuenow="$store.lessons.progress(ids)">
                            <div class="h-full rounded-full <?= esc_attr($p['bg']); ?> transition-[width] duration-700 motion-reduce:transition-none" style="width:0" :style="'width:' + $store.lessons.progress(ids) + '%'"></div>
                        </div>
                    </div>

                    <div class="relative mt-6 flex flex-wrap items-center gap-2">
                        <a href="<?= esc_url($urls[0] ?? $c['url']); ?>" :href="$store.lessons.nextUrl(ids, urls)"
                           class="inline-flex items-center justify-center gap-2 min-h-11 px-6 py-3 rounded-full bg-secondary hover:bg-amber-500 text-slate-950 text-sm font-black whitespace-nowrap transition-colors">
                            <span x-text="$store.lessons.progress(ids) === 100 ? 'مرور دوبارهٔ دوره' : ($store.lessons.started(ids) ? 'ادامه یادگیری' : 'شروع دوره')">شروع دوره</span>
                            <?= $arrow; // phpcs:ignore ?>
                        </a>
                        <a href="<?= esc_url($c['url']); ?>" class="inline-flex items-center justify-center min-h-11 px-5 py-3 rounded-full border border-white/15 hover:border-white/30 text-sm font-bold text-slate-200 hover:text-white whitespace-nowrap transition-colors">
                            صفحه دوره
                        </a>
                    </div>
                </aside>

                <div class="hc-rail" x-data="kmprRail" :data-start="String(atStart)" :data-end="String(atEnd)">
                    <div class="flex items-center justify-between gap-3 mb-3">
                        <p class="text-sm font-black text-slate-200">سرفصل‌های دوره</p>
                        <div class="hc-rail__nav" x-cloak>
                            <button type="button" class="hc-rail__btn" @click="go(-1)" :disabled="atStart" aria-label="درس‌های قبلی">
                                <svg width="18" height="18" class="shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h10.64l-4.16-3.96a.75.75 0 1 1 1.04-1.08l5.5 5.25a.75.75 0 0 1 0 1.08l-5.5 5.25a.75.75 0 1 1-1.04-1.08l4.16-3.96H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd"/></svg>
                            </button>
                            <button type="button" class="hc-rail__btn" @click="go(1)" :disabled="atEnd" aria-label="درس‌های بعدی">
                                <svg width="18" height="18" class="shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.61l4.16 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.61 9.25h10.64A.75.75 0 0 1 17 10Z" clip-rule="evenodd"/></svg>
                            </button>
                        </div>
                    </div>
                    <div class="hc-rail__view">
                        <ol class="hc-track" x-ref="track" @scroll.passive="update()" tabindex="0" aria-label="درس‌های <?= esc_attr($c['title']); ?>">
                            <?php foreach ($ids as $n => $lid) :
                                $lv     = get_post_meta($lid, 'difficulty', true);
                                $pillar = (bool) get_post_meta($lid, 'is_pillar', true);
                                $title  = get_the_title($lid);
                                $lpos   = kmpr_lesson_position($lid);
                                // A lesson whose primary course is another category falls back to its place in this list.
                                $own    = $lpos && (int) $lpos['term']->term_id === $pid;
                                $num    = $own ? (int) $lpos['number'] : $n + 1;
                                $of     = $own ? (int) $lpos['total'] : $total;
                                $label  = 'درس ' . kmpr_fa_num($num) . ' از ' . kmpr_fa_num($of);
                                ?>
                                <li class="hc-item">
                                    <a href="<?= esc_url($urls[$n]); ?>" class="hc-card group<?= $n === 0 ? ' is-next' : ''; ?>"
                                       :class="{ 'is-done': $store.lessons.has(<?= (int) $lid; ?>), 'is-next': ids.find((i) => !$store.lessons.has(i)) === <?= (int) $lid; ?> }">
                                        <span class="hc-card__ghost" aria-hidden="true"><?= esc_html(kmpr_fa_num($num)); ?></span>
                                        <div class="hc-card__head">
                                            <span class="hc-card__num" aria-hidden="true">
                                                <?php if ($pillar) : ?>
                                                    <svg width="24" height="24" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 7v14M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3Z"/></svg>
                                                <?php else : ?>
                                                    <?= esc_html(kmpr_fa_num($num)); ?>
                                                <?php endif; ?>
                                            </span>
                                            <span class="flex flex-col min-w-0">
                                                <?php if ($pillar) : ?><span class="text-xs font-black text-amber-300">راهنمای جامع دوره</span><?php endif; ?>
                                                <span class="text-xs font-bold text-slate-400"><?= esc_html($label); ?></span>
                                                <span class="hc-card__next" x-show="!$store.lessons.has(<?= (int) $lid; ?>) && ids.find((i) => !$store.lessons.has(i)) === <?= (int) $lid; ?> && $store.lessons.started(ids)" x-cloak>درس بعدی شما</span>
                                            </span>
                                        </div>
                                        <h4 class="hc-card__title"><span class="sr-only"><?= esc_html($label); ?>: </span><?= esc_html($title); ?></h4>
                                        <p class="hc-card__excerpt"><?= esc_html(wp_trim_words(get_the_excerpt($lid), 20)); ?></p>
                                        <span class="hc-card__meta">
                                            <span class="inline-flex items-center gap-1.5 whitespace-nowrap">
                                                <svg width="14" height="14" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
                                                <?= esc_html(kmpr_fa_num(reading_time($lid))); ?> دقیقه
                                            </span>
                                            <?php if (isset($levels[$lv])) : ?>
                                                <span class="hc-chip <?= esc_attr($lvl_cls[$lv]); ?>"><?= esc_html($levels[$lv]); ?></span>
                                            <?php endif; ?>
                                            <span class="hc-card__done" x-show="$store.lessons.has(<?= (int) $lid; ?>)" x-cloak><?= $check; // phpcs:ignore ?>تمام شد</span>
                                        </span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                            <li class="hc-item hc-item--more">
                                <a href="<?= esc_url($c['url']); ?>" class="hc-more">
                                    <span class="inline-flex items-center justify-center size-12 rounded-full border <?= esc_attr($p['border'] . ' ' . $p['text']); ?>"><?= $arrow; // phpcs:ignore ?></span>
                                    <span class="text-base font-black text-white">صفحه کامل دوره</span>
                                    <span class="text-xs text-slate-400">سرفصل‌ها، پیش‌نیازها و پرسش‌ها</span>
                                </a>
                            </li>
                        </ol>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
