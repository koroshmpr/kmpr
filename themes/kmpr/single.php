<?php
/**
 * Single post = a lesson inside a course (its primary category).
 * Hero · TL;DR · content · takeaways · FAQ · complete/rate/share · next lesson · author · related · comments.
 * Desktop: sticky course + TOC sidebar. Mobile: bottom bar opening a sheet (lessons / TOC).
 *
 * @package KMPR
 */

get_header();

while (have_posts()) :
    the_post();
    $post_id   = get_the_ID();
    $pos       = kmpr_lesson_position($post_id);
    $info      = $pos ? kmpr_course_info($pos['term']) : null;
    $pal       = kmpr_course_palette($info['color'] ?? 'amber');
    $level     = kmpr_lesson_level($post_id);
    $minutes   = reading_time($post_id);
    $views     = kmpr_post_views($post_id);
    $rating    = kmpr_post_rating($post_id);
    $tldr      = trim((string) get_post_meta($post_id, 'tldr', true));
    $takeaways = kmpr_lines(get_post_meta($post_id, 'key_takeaways', true));
    $faq       = kmpr_post_faq($post_id);
    $ids       = $pos ? array_map('intval', $pos['lessons']) : array($post_id);
    $blog_url  = get_permalink(get_option('page_for_posts')) ?: home_url('/blog/');
    $published = (int) get_the_time('U');
    $modified  = (int) get_the_modified_time('U');
    $content   = apply_filters('the_content', get_the_content());
    $has_toc   = (bool) get_headings($content, array(2, 3));
    $next_id   = $pos['next'] ?? 0;
    $prev_id   = $pos['prev'] ?? 0;
    ?>

<div x-data="{
        active: null,
        sheet: null,
        ids: <?= esc_attr(wp_json_encode($ids)); ?>,
        get toc() { return this.sheet === 'toc'; },
        set toc(v) { if (!v) this.sheet = null; }
     }"
     @keydown.escape.window="sheet = null"
     class="relative pb-20 lg:pb-0">

    <!-- ============ Hero ============ -->
    <header class="relative overflow-hidden border-b border-white/10">
        <div class="hero-grid absolute inset-0 opacity-40" aria-hidden="true"></div>
        <div class="aurora absolute -top-40 start-1/4 size-[26rem] rounded-full bg-gradient-to-br <?= esc_attr($pal['glow']); ?> to-transparent blur-[110px]" aria-hidden="true"></div>

        <div class="container relative mx-auto px-4 sm:px-6 pt-10 pb-12 lg:pt-14 lg:pb-16">
            <nav aria-label="مسیر صفحه" class="mb-8">
                <ol class="flex flex-wrap items-center gap-2 text-xs text-slate-400">
                    <li><a href="<?= esc_url(home_url('/')); ?>" class="hover:text-white transition-colors">خانه</a></li>
                    <li aria-hidden="true">/</li>
                    <li><a href="<?= esc_url($blog_url); ?>" class="hover:text-white transition-colors">وبلاگ</a></li>
                    <?php if ($info) : ?>
                        <li aria-hidden="true">/</li>
                        <li><a href="<?= esc_url($info['url']); ?>" class="hover:text-white transition-colors"><?= esc_html($info['title']); ?></a></li>
                    <?php endif; ?>
                </ol>
            </nav>

            <div class="grid lg:grid-cols-12 gap-10 items-end">
            <div class="lg:col-span-8">
                <div class="flex flex-wrap items-center gap-2 mb-6" data-aos="fade-up">
                    <?php if ($info) : ?>
                        <a href="<?= esc_url($info['url']); ?>" class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full <?= esc_attr($pal['soft'] . ' ' . $pal['text']); ?> border <?= esc_attr($pal['border']); ?> text-xs font-black hover:bg-white/10 transition-colors">
                            <span class="size-1.5 rounded-full <?= esc_attr($pal['bg']); ?>" aria-hidden="true"></span><?= esc_html($info['title']); ?>
                        </a>
                        <span class="inline-flex items-center px-3.5 py-1.5 rounded-full bg-white/[0.04] border border-white/10 text-xs font-black text-white">
                            درس <?= esc_html(kmpr_fa_num($pos['number'])); ?> از <?= esc_html(kmpr_fa_num($pos['total'])); ?>
                        </span>
                    <?php endif; ?>
                    <?php if ($level) : ?>
                        <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-white/[0.04] border border-white/10 text-xs font-bold text-slate-300">
                            <svg width="14" height="14" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M4 20v-4M10 20v-9M16 20V4"/></svg>
                            <?= esc_html($level['label']); ?>
                        </span>
                    <?php endif; ?>
                    <span x-show="$store.lessons.has(<?= (int) $post_id; ?>)" x-cloak class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-emerald-500/15 border border-emerald-400/30 text-xs font-black text-emerald-300">
                        <svg width="12" height="12" class="shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-8 8a1 1 0 0 1-1.4 0l-4-4a1 1 0 1 1 1.4-1.4L8 12.58l7.3-7.3a1 1 0 0 1 1.4 0Z" clip-rule="evenodd"/></svg>
                        تکمیل شده
                    </span>
                </div>

                <h1 class="text-3xl sm:text-4xl lg:text-5xl font-black text-white leading-[1.3]" <?= kmpr_split_attr(get_the_title()); ?>><?php the_title(); ?></h1>

                <?php if (has_excerpt()) : ?>
                    <p class="mt-5 text-base sm:text-lg text-slate-300 leading-loose" data-aos="fade-up" data-aos-delay="120"><?= esc_html(get_the_excerpt()); ?></p>
                <?php endif; ?>

                <div class="mt-8 flex flex-wrap items-center gap-x-6 gap-y-4 text-sm text-slate-400" data-aos="fade-up" data-aos-delay="200">
                    <span class="inline-flex items-center gap-3">
                        <img src="<?= esc_url(kmpr_author_photo_url('thumbnail')); ?>" alt="<?= esc_attr(kmpr_author_name()); ?>" width="40" height="40" class="size-10 rounded-full object-cover object-top border border-white/15">
                        <span class="flex flex-col leading-tight">
                            <span class="font-black text-white"><?= esc_html(kmpr_author_name()); ?></span>
                            <time class="text-xs" datetime="<?= esc_attr(get_the_date('c')); ?>"><?= esc_html(shamsi_date('j F Y', $published)); ?></time>
                        </span>
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <svg width="16" height="16" class="shrink-0 text-secondary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
                        <?= esc_html(kmpr_fa_num($minutes)); ?> دقیقه مطالعه
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <svg width="16" height="16" class="shrink-0 text-secondary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                        <?= esc_html(kmpr_fa_num(max(1, $views))); ?> بازدید
                    </span>
                    <?php if ($modified - $published > DAY_IN_SECONDS) : ?>
                        <span class="inline-flex items-center gap-1.5">
                            <svg width="16" height="16" class="shrink-0 text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 12a9 9 0 1 1-3-6.7L21 8"/><path d="M21 3v5h-5"/></svg>
                            به‌روزرسانی: <time datetime="<?= esc_attr(get_the_modified_date('c')); ?>"><?= esc_html(shamsi_date('j F Y', $modified)); ?></time>
                        </span>
                    <?php endif; ?>
                    <?php if ($rating['count']) : ?>
                        <span class="inline-flex items-center gap-1.5">
                            <svg width="16" height="16" class="shrink-0 text-secondary" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10 1.5l2.6 5.3 5.9.9-4.3 4.1 1 5.8L10 14.9l-5.2 2.7 1-5.8L1.5 7.7l5.9-.9L10 1.5z"/></svg>
                            <?= esc_html(kmpr_fa_num($rating['average'])); ?> از ۵
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($pos && $pos['total'] > 1) : ?>
                <div class="hidden lg:block lg:col-span-4" data-aos="fade-left" data-aos-delay="250">
                    <div class="rounded-3xl bg-slate-900/70 border border-white/10 backdrop-blur-xl p-6">
                        <div class="flex items-center justify-between text-xs mb-4">
                            <span class="font-black text-white">مسیر این دوره</span>
                            <span class="text-slate-400"><span x-text="$store.lessons.fa($store.lessons.doneCount(ids))">۰</span> از <?= esc_html(kmpr_fa_num($pos['total'])); ?> تکمیل</span>
                        </div>
                        <ol class="flex items-center gap-1.5 list-none p-0 m-0" aria-label="درس‌های دوره">
                            <?php foreach ($pos['lessons'] as $i => $lid) :
                                $here = ($lid === $post_id); ?>
                                <li class="flex-1">
                                    <a href="<?= esc_url(get_permalink($lid)); ?>" title="<?= esc_attr(kmpr_fa_num($i + 1) . '. ' . get_the_title($lid)); ?>"
                                       class="block h-2.5 rounded-full transition-colors <?= $here ? 'ring-2 ring-offset-2 ring-offset-slate-900 ring-secondary' : ''; ?>"
                                       :class="$store.lessons.has(<?= (int) $lid; ?>) ? 'bg-emerald-400' : '<?= $here ? 'bg-secondary' : 'bg-white/15 hover:bg-white/30'; ?>'">
                                        <span class="sr-only"><?= esc_html(get_the_title($lid)); ?></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                        <?php if ($next_id) : ?>
                            <a href="<?= esc_url(get_permalink($next_id)); ?>" @click="$store.lessons.mark(<?= (int) $post_id; ?>)" class="group mt-5 flex items-center gap-3 text-sm">
                                <span class="text-slate-400 shrink-0">بعدی:</span>
                                <span class="font-bold text-slate-200 group-hover:text-secondary truncate transition-colors"><?= esc_html(get_the_title($next_id)); ?></span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- ============ Body ============ -->
    <div class="container mx-auto px-4 sm:px-6 py-10 lg:py-14">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 xl:gap-14 items-start">

            <article id="lesson" class="lg:col-span-8 min-w-0" aria-label="<?= esc_attr(get_the_title()); ?>">

                <?php if (has_post_thumbnail()) : ?>
                    <figure class="mb-10 rounded-3xl overflow-hidden border border-white/10 bg-slate-900" data-aos="zoom-in">
                        <?php the_post_thumbnail('large', array('class' => 'w-full h-auto', 'alt' => esc_attr(get_the_title()), 'fetchpriority' => 'high', 'loading' => 'eager')); ?>
                    </figure>
                <?php endif; ?>

                <?php if ($tldr) : ?>
                    <aside class="relative mb-10 rounded-3xl border border-secondary/30 bg-secondary/[0.06] p-6 sm:p-7" aria-label="خلاصه سریع" data-aos="fade-up">
                        <p class="inline-flex items-center gap-2 text-xs font-black text-secondary mb-3">
                            <svg width="16" height="16" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M13 2 3 14h9l-1 8 10-12h-9l1-8Z"/></svg>
                            خلاصه در ۳۰ ثانیه (TL;DR)
                        </p>
                        <p class="text-base text-slate-200 leading-loose"><?= esc_html($tldr); ?></p>
                    </aside>
                <?php endif; ?>

                <?php if ($has_toc) : ?>
                    <button type="button" @click="sheet = 'toc'" class="lg:hidden mb-8 w-full flex items-center justify-between gap-3 min-h-12 px-5 py-3 rounded-2xl bg-white/[0.03] border border-white/10 text-sm font-black text-white">
                        <span class="inline-flex items-center gap-2">
                            <svg width="16" height="16" class="shrink-0 text-secondary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>
                            فهرست مطالب این درس
                        </span>
                        <svg width="16" height="16" class="shrink-0 text-slate-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.2 7.2a.75.75 0 0 1 1.06 0L10 10.94l3.74-3.74a.75.75 0 1 1 1.06 1.06l-4.27 4.27a.75.75 0 0 1-1.06 0L5.2 8.26a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/></svg>
                    </button>
                <?php endif; ?>

                <div id="lesson-body" class="lesson-prose">
                    <?= $content; // phpcs:ignore -- filtered post content ?>
                </div>

                <?php wp_link_pages(array('before' => '<nav class="kmpr-pagination mt-10 flex flex-wrap gap-2" aria-label="صفحات درس">', 'after' => '</nav>')); ?>

                <?php if ($takeaways) : ?>
                    <section class="mt-14 rounded-3xl bg-white/[0.025] border border-white/10 p-6 sm:p-8" aria-labelledby="takeaways-title" data-aos="fade-up">
                        <h2 id="takeaways-title" class="flex items-center gap-3 text-xl sm:text-2xl font-black text-white mb-6">
                            <span class="inline-flex items-center justify-center size-10 rounded-2xl bg-emerald-500/15 text-emerald-400">
                                <svg width="20" height="20" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M9 18h6M10 22h4M12 2a7 7 0 0 0-4 12.7V17h8v-2.3A7 7 0 0 0 12 2Z"/></svg>
                            </span>
                            نکات کلیدی این درس
                        </h2>
                        <ul class="list-none p-0 m-0 grid gap-3" data-stagger="70">
                            <?php foreach ($takeaways as $t) : ?>
                                <li data-aos="fade-up" class="flex items-start gap-3 text-slate-200 leading-loose">
                                    <svg width="20" height="20" class="shrink-0 mt-1 text-emerald-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.7-9.3a1 1 0 0 0-1.4-1.4L9 10.58 7.7 9.3a1 1 0 0 0-1.4 1.4l2 2a1 1 0 0 0 1.4 0l4-4Z" clip-rule="evenodd"/></svg>
                                    <span><?= esc_html($t); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </section>
                <?php endif; ?>

                <?php if ($faq) : ?>
                    <section class="mt-14" aria-labelledby="lesson-faq-title">
                        <h2 id="lesson-faq-title" class="text-xl sm:text-2xl font-black text-white mb-6">پرسش‌های متداول</h2>
                        <div class="flex flex-col gap-3" data-stagger="60">
                            <?php foreach ($faq as $i => $row) : ?>
                                <details data-aos="fade-up" class="lesson-faq group rounded-2xl bg-white/[0.025] border border-white/10 open:border-secondary/40 transition-colors" <?= $i === 0 ? 'open' : ''; ?>>
                                    <summary class="flex items-center justify-between gap-4 min-h-14 px-5 py-4 cursor-pointer list-none text-start font-black text-white">
                                        <span><?= esc_html($row['question']); ?></span>
                                        <span class="shrink-0 inline-flex items-center justify-center size-8 rounded-full bg-white/[0.06] text-secondary transition-transform group-open:rotate-45">
                                            <svg width="14" height="14" class="shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10 3a1 1 0 0 1 1 1v5h5a1 1 0 1 1 0 2h-5v5a1 1 0 1 1-2 0v-5H4a1 1 0 1 1 0-2h5V4a1 1 0 0 1 1-1Z"/></svg>
                                        </span>
                                    </summary>
                                    <div class="px-5 pb-5 text-slate-300 leading-loose"><?= esc_html($row['answer']); ?></div>
                                </details>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>

                <!-- Complete · rate · share -->
                <section class="mt-14 rounded-3xl bg-slate-900/80 border border-white/10 p-6 sm:p-8 flex flex-col gap-8" aria-label="پایان درس" data-aos="fade-up"
                         x-data="kmprRating(<?= (int) $post_id; ?>, <?= esc_attr($rating['average']); ?>, <?= (int) $rating['count']; ?>)">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-5">
                        <div>
                            <p class="text-lg font-black text-white" x-text="$store.lessons.has(<?= (int) $post_id; ?>) ? 'این درس را تمام کرده‌ای' : 'این درس را تمام کردی؟'">این درس را تمام کردی؟</p>
                            <p class="text-sm text-slate-400 mt-1">علامتش بزن تا پیشرفتت در دوره ذخیره شود.</p>
                        </div>
                        <button type="button" @click="$store.lessons.toggle(<?= (int) $post_id; ?>)"
                                class="shrink-0 inline-flex items-center justify-center gap-2 min-h-11 px-6 py-3 rounded-full text-sm font-black whitespace-nowrap transition-colors cursor-pointer"
                                :class="$store.lessons.has(<?= (int) $post_id; ?>) ? 'bg-emerald-500 text-slate-950 hover:bg-emerald-400' : 'bg-white/[0.06] border border-white/15 text-white hover:border-emerald-400/60'"
                                :aria-pressed="$store.lessons.has(<?= (int) $post_id; ?>).toString()">
                            <svg width="18" height="18" class="shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-8 8a1 1 0 0 1-1.4 0l-4-4a1 1 0 1 1 1.4-1.4L8 12.58l7.3-7.3a1 1 0 0 1 1.4 0Z" clip-rule="evenodd"/></svg>
                            <span x-text="$store.lessons.has(<?= (int) $post_id; ?>) ? 'تکمیل شد' : 'علامت‌گذاری به‌عنوان تکمیل‌شده'">علامت‌گذاری به‌عنوان تکمیل‌شده</span>
                        </button>
                    </div>

                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 pt-6 border-t border-white/10">
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="text-sm font-bold text-slate-300">امتیاز شما:</span>
                            <div class="flex items-center" @mouseleave="hover = 0" role="radiogroup" aria-label="امتیاز به این درس">
                                <?php for ($i = 1; $i <= 5; $i++) : ?>
                                    <button type="button" role="radio" :aria-checked="(mine === <?= $i; ?>).toString()" aria-label="<?= esc_attr(kmpr_fa_num($i)); ?> ستاره"
                                            @mouseenter="hover = <?= $i; ?>" @focus="hover = <?= $i; ?>" @click="rate(<?= $i; ?>)" :disabled="busy"
                                            class="inline-flex items-center justify-center size-11 cursor-pointer text-secondary transition-transform hover:scale-110">
                                        <svg width="24" height="24" class="shrink-0" viewBox="0 0 20 20" stroke="currentColor" stroke-width="1.2" :fill="shown() >= <?= $i; ?> ? 'currentColor' : 'none'" fill="<?= round($rating['average']) >= $i ? 'currentColor' : 'none'; ?>" aria-hidden="true"><path d="M10 1.5l2.6 5.3 5.9.9-4.3 4.1 1 5.8L10 14.9l-5.2 2.7 1-5.8L1.5 7.7l5.9-.9L10 1.5z"/></svg>
                                    </button>
                                <?php endfor; ?>
                            </div>
                            <span class="text-xs text-slate-400" x-text="count ? $store.lessons.fa(average) + ' از ۵، ' + $store.lessons.fa(count) + ' رأی' : 'هنوز رأیی ثبت نشده'"></span>
                            <span class="w-full text-xs font-bold text-emerald-400" x-show="message" x-text="message" role="status" x-cloak></span>
                        </div>
                        <?php get_template_part('template-parts/share-button'); ?>
                    </div>
                </section>

                <!-- Next / previous lesson -->
                <?php if ($next_id || $prev_id) : ?>
                    <nav class="mt-10 grid grid-cols-1 gap-4 [&>*]:min-w-0" aria-label="درس‌های دوره">
                        <?php if ($next_id) : ?>
                            <a href="<?= esc_url(get_permalink($next_id)); ?>" rel="next" data-aos="fade-up"
                               @click="$store.lessons.mark(<?= (int) $post_id; ?>)"
                               class="border-glow spotlight group relative overflow-hidden flex flex-col sm:flex-row sm:items-center gap-5 rounded-3xl bg-gradient-to-br from-secondary/15 via-slate-900 to-slate-950 border border-secondary/30 p-6 sm:p-8">
                                <div class="min-w-0 flex-1">
                                    <span class="inline-flex items-center gap-2 text-xs font-black text-secondary mb-3">
                                        <span class="w-6 h-px bg-secondary"></span>درس بعدی، <?= esc_html(kmpr_fa_num($pos['number'] + 1)); ?> از <?= esc_html(kmpr_fa_num($pos['total'])); ?>
                                    </span>
                                    <p class="text-xl sm:text-2xl font-black text-white leading-snug group-hover:text-secondary transition-colors"><?= esc_html(get_the_title($next_id)); ?></p>
                                    <p class="mt-3 text-sm text-slate-300 leading-relaxed line-clamp-2"><?= esc_html(get_post_meta($next_id, 'tldr', true) ?: wp_trim_words(get_the_excerpt($next_id), 26)); ?></p>
                                    <p class="mt-3 text-[13px] text-slate-400"><?= esc_html(kmpr_fa_num(reading_time($next_id))); ?> دقیقه مطالعه</p>
                                </div>
                                <span class="shrink-0 self-start sm:self-center inline-flex items-center gap-2 min-h-11 px-6 py-3 rounded-full bg-secondary group-hover:bg-amber-500 text-slate-950 text-sm font-black whitespace-nowrap transition-colors">
                                    برو به درس بعدی
                                    <svg width="16" height="16" class="shrink-0 group-hover:-translate-x-1 transition-transform" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.61l4.16 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.61 9.25h10.64A.75.75 0 0 1 17 10Z" clip-rule="evenodd"/></svg>
                                </span>
                            </a>
                        <?php elseif ($info) : ?>
                            <div data-aos="fade-up" class="rounded-3xl bg-gradient-to-br from-emerald-500/15 via-slate-900 to-slate-950 border border-emerald-400/30 p-6 sm:p-8">
                                <span class="inline-flex items-center gap-2 text-xs font-black text-emerald-400 mb-3"><span class="w-6 h-px bg-emerald-400"></span>آخرین درس دوره</span>
                                <p class="text-xl sm:text-2xl font-black text-white">به پایان دورهٔ «<?= esc_html($info['title']); ?>» رسیدی.</p>
                                <p class="mt-3 text-sm text-slate-300 leading-relaxed">درس‌های تمام‌نشده را در صفحه دوره ببین یا یک دوره دیگر را شروع کن.</p>
                                <a href="<?= esc_url($info['url']); ?>" @click="$store.lessons.mark(<?= (int) $post_id; ?>)" class="mt-5 inline-flex items-center min-h-11 px-6 py-3 rounded-full bg-emerald-500 hover:bg-emerald-400 text-slate-950 text-sm font-black whitespace-nowrap transition-colors">مرور سرفصل‌های دوره</a>
                            </div>
                        <?php endif; ?>
                        <?php if ($prev_id) : ?>
                            <a href="<?= esc_url(get_permalink($prev_id)); ?>" rel="prev" data-aos="fade-up"
                               class="group flex items-center gap-4 rounded-2xl bg-white/[0.025] border border-white/10 hover:border-secondary/40 p-4 sm:p-5 transition-colors">
                                <span class="shrink-0 inline-flex items-center justify-center size-11 rounded-xl bg-white/[0.06] text-slate-300 group-hover:text-secondary transition-colors">
                                    <svg width="18" height="18" class="shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h10.64L10.23 5.29a.75.75 0 1 1 1.04-1.08l5.5 5.25a.75.75 0 0 1 0 1.08l-5.5 5.25a.75.75 0 1 1-1.04-1.08l4.16-3.96H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd"/></svg>
                                </span>
                                <span class="min-w-0">
                                    <span class="block text-[13px] text-slate-400 mb-1">درس قبلی، <?= esc_html(kmpr_fa_num($pos['number'] - 1)); ?></span>
                                    <span class="block text-sm sm:text-base font-black text-slate-200 group-hover:text-white truncate"><?= esc_html(get_the_title($prev_id)); ?></span>
                                </span>
                            </a>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>

                <div class="mt-10">
                    <?php get_template_part('template-parts/cards/post-detail/author-information'); ?>
                </div>

                <?php
                $tags = get_the_tags();
                if ($tags) : ?>
                    <ul class="mt-8 flex flex-wrap gap-2 list-none p-0" aria-label="برچسب‌ها">
                        <?php foreach ($tags as $tag) : ?>
                            <li><a href="<?= esc_url(get_tag_link($tag)); ?>" class="inline-flex items-center min-h-9 px-3.5 py-1.5 rounded-full bg-white/[0.04] border border-white/10 hover:border-secondary/40 text-xs font-bold text-slate-300 hover:text-white transition-colors">#<?= esc_html($tag->name); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <!-- CTA -->
                <section class="mt-10 relative overflow-hidden rounded-3xl border border-white/10 bg-slate-900/80 p-6 sm:p-8" aria-labelledby="lesson-cta-title" data-aos="fade-up">
                    <div class="pointer-events-none absolute -bottom-20 -start-20 size-64 rounded-full bg-indigo-500/15 blur-3xl" aria-hidden="true"></div>
                    <div class="relative flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <div class="max-w-xl">
                            <span class="inline-flex items-center gap-2 text-xs font-black text-secondary mb-3"><span class="w-6 h-px bg-secondary"></span><?= esc_html(kmpr_blog_text('blog_cta_kicker')); ?></span>
                            <h2 id="lesson-cta-title" class="text-xl sm:text-2xl font-black text-white leading-snug"><?= esc_html(kmpr_blog_text('blog_cta_title')); ?></h2>
                            <p class="mt-3 text-sm text-slate-300 leading-loose"><?= esc_html(kmpr_blog_text('blog_cta_text')); ?></p>
                        </div>
                        <a href="<?= esc_url(kmpr_request_url()); ?>" data-magnetic class="shrink-0 self-start md:self-center inline-flex items-center gap-2 min-h-11 px-8 py-3.5 rounded-full bg-secondary hover:bg-amber-500 text-slate-950 text-sm font-black whitespace-nowrap transition-colors">ثبت درخواست پروژه</a>
                    </div>
                </section>

                <div class="mt-14">
                    <?php if (comments_open() || get_comments_number()) {
                        comments_template();
                    } ?>
                </div>
            </article>

            <!-- ============ Desktop sidebar ============ -->
            <aside class="hidden lg:block lg:col-span-4 lg:sticky lg:top-28 self-start" aria-label="دوره و فهرست مطالب">
                <div class="flex flex-col gap-5 max-h-[calc(100vh-8rem)]">
                    <div class="rounded-3xl bg-slate-900/80 border border-white/10 backdrop-blur-xl px-5 py-4" x-data="kmprReading">
                        <div class="flex items-center justify-between text-[13px] mb-2">
                            <span class="font-bold text-slate-300">پیشرفت خواندن این درس</span>
                            <span class="text-base font-black text-secondary" x-text="$store.lessons.fa(pct) + '٪'">۰٪</span>
                        </div>
                        <div class="h-1 rounded-full bg-white/10 overflow-hidden"><div class="h-full bg-secondary rounded-full" style="width:0" :style="'width:' + pct + '%'"></div></div>
                    </div>
                    <?php if ($pos) : ?>
                        <div class="rounded-3xl bg-slate-900/80 border border-white/10 backdrop-blur-xl overflow-hidden flex flex-col min-h-0 shrink">
                            <?php get_template_part('template-parts/blog/course-nav', null, array('pos' => $pos, 'info' => $info)); ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($has_toc) : ?>
                        <div class="rounded-3xl bg-slate-900/80 border border-white/10 backdrop-blur-xl p-5 min-h-0 shrink flex flex-col">
                            <p class="text-sm font-black text-white mb-3 flex items-center gap-2">
                                <svg width="14" height="14" class="shrink-0 text-secondary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>
                                در این درس
                            </p>
                            <div class="overflow-y-auto overscroll-contain pe-1 min-h-0">
                                <?= get_toc($content, array(2, 3)); // phpcs:ignore -- escaped inside ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </aside>
        </div>

        <?php
        // Related lessons from OTHER courses (keep exploring).
        $related = new WP_Query(array(
            'post_type'           => 'post',
            'posts_per_page'      => 3,
            'post__not_in'        => array($post_id),
            'category__not_in'    => $info ? array($info['id']) : array(),
            'ignore_sticky_posts' => true,
            'no_found_rows'       => true,
            'orderby'             => 'rand',
        ));
        if (!$related->have_posts() && $info) {
            $related = new WP_Query(array('post_type' => 'post', 'posts_per_page' => 3, 'post__not_in' => array($post_id), 'ignore_sticky_posts' => true, 'no_found_rows' => true));
        }
        if ($related->have_posts()) : ?>
            <section class="mt-20 pt-14 border-t border-white/10" aria-labelledby="related-title">
                <span class="inline-flex items-center gap-2 text-xs font-black text-secondary mb-4"><span class="w-6 h-px bg-secondary"></span>ادامه کنجکاوی</span>
                <h2 id="related-title" class="text-2xl sm:text-3xl font-black text-white mb-8">از دوره‌های دیگر</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5" data-stagger="100">
                    <?php while ($related->have_posts()) : $related->the_post();
                        get_template_part('template-parts/cards/lesson-card');
                    endwhile;
                    wp_reset_postdata(); ?>
                </div>
            </section>
        <?php endif; ?>
    </div>

    <!-- ============ Mobile: bottom bar + sheet ============ -->
    <div class="lg:hidden fixed bottom-0 inset-x-0 z-40 p-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] pointer-events-none">
        <div class="pointer-events-auto mx-auto max-w-xl flex items-center gap-2 rounded-2xl bg-slate-900/95 border border-white/10 backdrop-blur-xl shadow-2xl p-2" x-data="kmprReading">
            <?php if ($pos) : ?>
                <button type="button" @click="sheet = 'course'" class="flex-1 min-w-0 flex items-center gap-3 min-h-11 px-3 rounded-xl hover:bg-white/[0.05] text-start cursor-pointer" aria-haspopup="dialog">
                    <span class="relative shrink-0 size-9">
                        <svg width="36" height="36" viewBox="0 0 36 36" class="shrink-0 -rotate-90" aria-hidden="true">
                            <circle cx="18" cy="18" r="15" fill="none" stroke="currentColor" stroke-width="3" class="text-white/10"/>
                            <circle cx="18" cy="18" r="15" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" class="text-secondary" stroke-dasharray="94.2" :stroke-dashoffset="94.2 - 94.2 * pct / 100" stroke-dashoffset="94.2"/>
                        </svg>
                        <span class="absolute inset-0 flex items-center justify-center text-xs font-black text-white"><?= esc_html(kmpr_fa_num($pos['number'])); ?></span>
                    </span>
                    <span class="min-w-0">
                        <span class="block text-xs text-slate-400">درس <?= esc_html(kmpr_fa_num($pos['number'])); ?> از <?= esc_html(kmpr_fa_num($pos['total'])); ?></span>
                        <span class="block text-xs font-black text-white truncate"><?= esc_html($info['title']); ?></span>
                    </span>
                </button>
            <?php endif; ?>
            <button type="button" @click="menuOpen = true" class="shrink-0 inline-flex flex-col items-center justify-center gap-0.5 size-11 rounded-xl bg-white/[0.05] text-slate-200 cursor-pointer" aria-label="منوی سایت" aria-controls="kmpr-sheet">
                <svg width="18" height="18" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="5" cy="12" r="1.6" fill="currentColor"/><circle cx="12" cy="12" r="1.6" fill="currentColor"/><circle cx="19" cy="12" r="1.6" fill="currentColor"/></svg>
            </button>
            <?php if ($has_toc) : ?>
                <button type="button" @click="sheet = 'toc'" class="shrink-0 inline-flex items-center justify-center size-11 rounded-xl bg-white/[0.05] text-slate-200 cursor-pointer" aria-label="فهرست مطالب" aria-haspopup="dialog">
                    <svg width="18" height="18" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>
                </button>
            <?php endif; ?>
            <?php if ($next_id) : ?>
                <a href="<?= esc_url(get_permalink($next_id)); ?>" @click="$store.lessons.mark(<?= (int) $post_id; ?>)" class="shrink-0 inline-flex items-center gap-1.5 min-h-11 px-4 rounded-xl bg-secondary text-slate-950 text-xs font-black whitespace-nowrap">
                    بعدی
                    <svg width="14" height="14" class="shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.61l4.16 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.61 9.25h10.64A.75.75 0 0 1 17 10Z" clip-rule="evenodd"/></svg>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="lg:hidden" x-show="sheet" x-cloak>
        <div class="fixed inset-0 z-[60] bg-slate-950/70 backdrop-blur-sm" @click="sheet = null" x-show="sheet" x-transition.opacity></div>
        <div class="fixed inset-x-0 bottom-0 z-[61] max-h-[80vh] flex flex-col rounded-t-3xl bg-slate-900 border-t border-white/10 shadow-2xl"
             role="dialog" aria-modal="true" :aria-label="sheet === 'toc' ? 'فهرست مطالب' : 'درس‌های دوره'"
             x-show="sheet" x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
             x-transition:leave="transition duration-200 ease-in" x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full">
            <div class="flex items-center justify-between gap-3 p-3 border-b border-white/10">
                <div class="flex items-center gap-1 p-1 rounded-full bg-white/[0.04]">
                    <?php if ($pos) : ?>
                        <button type="button" @click="sheet = 'course'" class="min-h-10 px-4 rounded-full text-xs font-black cursor-pointer" :class="sheet === 'course' ? 'bg-secondary text-slate-950' : 'text-slate-300'">درس‌های دوره</button>
                    <?php endif; ?>
                    <?php if ($has_toc) : ?>
                        <button type="button" @click="sheet = 'toc'" class="min-h-10 px-4 rounded-full text-xs font-black cursor-pointer" :class="sheet === 'toc' ? 'bg-secondary text-slate-950' : 'text-slate-300'">فهرست مطالب</button>
                    <?php endif; ?>
                </div>
                <button type="button" @click="sheet = null" class="inline-flex items-center justify-center size-11 rounded-full bg-white/[0.05] text-slate-200 cursor-pointer" aria-label="بستن">
                    <svg width="18" height="18" class="shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M5.3 5.3a1 1 0 0 1 1.4 0L10 8.6l3.3-3.3a1 1 0 1 1 1.4 1.4L11.4 10l3.3 3.3a1 1 0 0 1-1.4 1.4L10 11.4l-3.3 3.3a1 1 0 0 1-1.4-1.4L8.6 10 5.3 6.7a1 1 0 0 1 0-1.4Z"/></svg>
                </button>
            </div>
            <?php if ($pos) : ?>
                <div x-show="sheet === 'course'" class="flex flex-col min-h-0 overflow-hidden">
                    <?php get_template_part('template-parts/blog/course-nav', null, array('pos' => $pos, 'info' => $info)); ?>
                </div>
            <?php endif; ?>
            <?php if ($has_toc) : ?>
                <div x-show="sheet === 'toc'" class="min-h-0 overflow-y-auto overscroll-contain p-4">
                    <?= get_toc($content, array(2, 3)); // phpcs:ignore ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php endwhile;
get_footer();
