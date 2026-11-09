<?php
/**
 * Blog home — "learning hub": hero + search, courses (one per category), latest lessons (main query, crawlable pagination).
 *
 * @package KMPR
 */

get_header();

$paged       = max(1, (int) get_query_var('paged'));
$all_courses = kmpr_courses();
$courses     = ($paged === 1) ? $all_courses : array();
$total_lessons = 0;
$total_minutes = 0;
$all_ids       = array();
foreach ($all_courses as $c) {
    $total_lessons += $c['count'];
    $total_minutes += $c['minutes'];
    $all_ids        = array_merge($all_ids, $c['lessons']);
}
?>

<section class="relative overflow-hidden border-b border-white/10" aria-labelledby="blog-hub-title">
    <div class="hero-grid absolute inset-0 opacity-60" aria-hidden="true"></div>
    <div class="aurora absolute -top-40 start-1/4 size-[28rem] rounded-full bg-secondary/20 blur-[120px]" aria-hidden="true"></div>
    <div class="aurora absolute -bottom-40 end-10 size-[24rem] rounded-full bg-indigo-600/20 blur-[120px]" aria-hidden="true"></div>

    <div class="container relative mx-auto px-4 sm:px-6 py-16 lg:py-24">
        <div class="grid lg:grid-cols-12 gap-10 lg:gap-14 items-center">
            <div class="lg:col-span-7">
                <span class="inline-flex items-center gap-2 text-xs font-black text-secondary mb-5" data-aos="fade-up">
                    <span class="w-6 h-px bg-secondary"></span><?= esc_html(kmpr_blog_text('blog_hero_kicker')); ?>
                </span>
                <h1 id="blog-hub-title" class="text-4xl sm:text-5xl lg:text-6xl font-black text-white leading-[1.15]" <?= kmpr_split_attr(kmpr_blog_text('blog_hero_title')); ?>>
                    <?= esc_html(kmpr_blog_text('blog_hero_title')); ?><?php if ($paged > 1) : ?> <span class="text-slate-400 text-2xl sm:text-3xl">— صفحه <?= esc_html(kmpr_fa_num($paged)); ?></span><?php endif; ?>
                </h1>
                <p class="mt-6 text-base sm:text-lg text-slate-300 leading-loose max-w-2xl" data-aos="fade-up" data-aos-delay="150">
                    <?= esc_html(kmpr_blog_text('blog_hero_intro')); ?>
                </p>

                <form role="search" method="get" action="<?= esc_url(home_url('/')); ?>" data-aos="fade-up" data-aos-delay="250"
                      class="mt-8 flex items-center gap-2 max-w-xl p-2 rounded-full bg-white/[0.04] border border-white/15 focus-within:border-secondary/60 transition-colors">
                    <input type="hidden" name="post_type" value="post">
                    <label for="blog-search" class="sr-only">جستجو در مقاله‌ها</label>
                    <svg width="18" height="18" class="shrink-0 ms-3 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                    <input id="blog-search" type="search" name="s" value="<?= esc_attr(get_search_query()); ?>"
                           placeholder="<?= esc_attr(kmpr_blog_text('blog_search_placeholder')); ?>"
                           class="flex-1 min-w-0 bg-transparent outline-none text-sm text-white placeholder:text-slate-400 py-2">
                    <button type="submit" class="shrink-0 min-h-11 px-6 py-2.5 rounded-full bg-secondary hover:bg-amber-500 text-slate-950 text-sm font-black whitespace-nowrap transition-colors">جستجو</button>
                </form>
            </div>

            <aside class="lg:col-span-5" aria-label="پیشرفت یادگیری من" data-aos="fade-left" data-aos-delay="200"
                   x-data="{ ids: <?= esc_attr(wp_json_encode(array_map('intval', $all_ids))); ?> }">
                <div class="border-glow relative rounded-3xl bg-slate-900/80 border border-white/10 p-6 sm:p-8 backdrop-blur-xl">
                    <p class="text-sm font-black text-white mb-6">مسیر یادگیری من</p>
                    <div class="flex flex-col sm:flex-row items-center gap-6">
                        <div class="relative size-28 shrink-0">
                            <svg width="112" height="112" viewBox="0 0 120 120" class="shrink-0 -rotate-90" aria-hidden="true">
                                <circle cx="60" cy="60" r="52" fill="none" stroke="currentColor" stroke-width="10" class="text-white/10"/>
                                <circle cx="60" cy="60" r="52" fill="none" stroke="currentColor" stroke-width="10" stroke-linecap="round" class="text-secondary transition-all duration-700"
                                        stroke-dasharray="326.7" stroke-dashoffset="326.7" :stroke-dashoffset="326.7 - 326.7 * $store.lessons.progress(ids) / 100"/>
                            </svg>
                            <span class="absolute inset-0 flex items-center justify-center text-2xl font-black text-white" x-text="$store.lessons.fa($store.lessons.progress(ids)) + '٪'">۰٪</span>
                        </div>
                        <dl class="flex-1 grid grid-cols-2 gap-3 text-sm" data-stagger="70">
                            <div data-aos="zoom-in" class="rounded-2xl bg-white/[0.04] border border-white/10 p-3"><dt class="text-xs text-slate-400 mb-1">دوره</dt><dd class="text-lg font-black text-white"><?= esc_html(kmpr_fa_num(count($all_courses))); ?></dd></div>
                            <div data-aos="zoom-in" class="rounded-2xl bg-white/[0.04] border border-white/10 p-3"><dt class="text-xs text-slate-400 mb-1">درس</dt><dd class="text-lg font-black text-white"><?= esc_html(kmpr_fa_num($total_lessons)); ?></dd></div>
                            <div data-aos="zoom-in" class="rounded-2xl bg-white/[0.04] border border-white/10 p-3"><dt class="text-xs text-slate-400 mb-1">خوانده‌شده</dt><dd class="text-lg font-black text-emerald-400" x-text="$store.lessons.fa($store.lessons.doneCount(ids))">۰</dd></div>
                            <div data-aos="zoom-in" class="rounded-2xl bg-white/[0.04] border border-white/10 p-3"><dt class="text-xs text-slate-400 mb-1">کل محتوا</dt><dd class="text-sm font-black text-white leading-7"><?= esc_html(kmpr_minutes_label($total_minutes)); ?></dd></div>
                        </dl>
                    </div>
                    <p class="mt-6 text-xs text-slate-400 leading-relaxed">پیشرفت فقط روی همین مرورگر ذخیره می‌شود؛ نیازی به ثبت‌نام نیست.</p>
                </div>
            </aside>
        </div>
    </div>
</section>

<?php if ($courses) : ?>
<section class="py-16 lg:py-24" aria-labelledby="courses-title">
    <div class="container mx-auto px-4 sm:px-6">
        <header class="max-w-2xl mb-10 lg:mb-14">
            <span class="inline-flex items-center gap-2 text-xs font-black text-secondary mb-4" data-aos="fade-up">
                <span class="w-6 h-px bg-secondary"></span><?= esc_html(kmpr_blog_text('blog_courses_kicker')); ?>
            </span>
            <h2 id="courses-title" class="text-3xl sm:text-4xl lg:text-5xl font-black text-white leading-tight" <?= kmpr_split_attr(kmpr_blog_text('blog_courses_title')); ?>><?= esc_html(kmpr_blog_text('blog_courses_title')); ?></h2>
        </header>
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-6 gap-5" data-stagger="100">
            <?php foreach ($courses as $i => $course) {
                get_template_part('template-parts/blog/course-card', null, array('course' => $course, 'index' => $i, 'class' => kmpr_course_grid_span($i, count($courses))));
            } ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="py-16 lg:py-24 border-t border-white/10" aria-labelledby="latest-title">
    <div class="container mx-auto px-4 sm:px-6">
        <header class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-10">
            <div class="max-w-2xl">
                <span class="inline-flex items-center gap-2 text-xs font-black text-secondary mb-4" data-aos="fade-up">
                    <span class="w-6 h-px bg-secondary"></span><?= esc_html(kmpr_blog_text('blog_latest_kicker')); ?>
                </span>
                <h2 id="latest-title" class="text-3xl sm:text-4xl font-black text-white leading-tight"><?= esc_html(kmpr_blog_text('blog_latest_title')); ?></h2>
            </div>
            <?php if ($GLOBALS['wp_query']->max_num_pages > 1) : ?>
                <p class="text-sm text-slate-400">صفحه <?= esc_html(kmpr_fa_num($paged)); ?> از <?= esc_html(kmpr_fa_num($GLOBALS['wp_query']->max_num_pages)); ?></p>
            <?php endif; ?>
        </header>

        <?php if (have_posts()) : ?>
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5" data-stagger="80">
                <?php while (have_posts()) : the_post();
                    get_template_part('template-parts/cards/lesson-card');
                endwhile; ?>
            </div>
            <?php get_template_part('template-parts/loop/pagination'); ?>
        <?php else : ?>
            <div class="py-16 text-center max-w-md mx-auto rounded-3xl bg-white/[0.025] border border-white/10 p-8">
                <p class="font-black text-lg text-white">هنوز درسی منتشر نشده است.</p>
                <p class="text-sm text-slate-400 mt-2">به‌زودی اولین درس‌ها اینجا قرار می‌گیرند.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php get_footer();
