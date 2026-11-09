<?php
/**
 * Category = course page: intro (term fields), pillar lesson, numbered syllabus with completion state.
 * The syllabus is the course order (kmpr_course_lessons) sliced by the main query's page so /page/N stays crawlable.
 *
 * @package KMPR
 */

get_header();

$term    = get_queried_object();
$course  = kmpr_course_info($term);
$pal     = kmpr_course_palette($course['color'] ?? 'amber');
$paged   = max(1, (int) get_query_var('paged'));
$per     = max(1, (int) get_query_var('posts_per_page'));
$lessons = $course ? $course['lessons'] : array();
$slice   = array_slice($lessons, ($paged - 1) * $per, $per, true);
$ids     = array_map('intval', $lessons);
$urls    = array_map('get_permalink', $ids);
$pillar  = $course ? (int) $course['pillar'] : 0;
$blog    = get_permalink(get_option('page_for_posts')) ?: home_url('/blog/');
$levels  = array();
foreach ($lessons as $lid) {
    $lv = kmpr_lesson_level($lid);
    if ($lv) {
        $levels[$lv['key']] = $lv['label'];
    }
}
?>

<div x-data="{ ids: <?= esc_attr(wp_json_encode($ids)); ?>, urls: <?= esc_attr(wp_json_encode($urls)); ?> }">

<section class="relative overflow-hidden border-b border-white/10" aria-labelledby="course-title">
    <div class="hero-grid absolute inset-0 opacity-50" aria-hidden="true"></div>
    <div class="aurora absolute -top-32 start-1/3 size-[26rem] rounded-full bg-gradient-to-br <?= esc_attr($pal['glow']); ?> to-transparent blur-[110px]" aria-hidden="true"></div>

    <div class="container relative mx-auto px-4 sm:px-6 py-14 lg:py-20">
        <nav aria-label="مسیر صفحه" class="mb-8">
            <ol class="flex flex-wrap items-center gap-2 text-xs text-slate-400">
                <li><a href="<?= esc_url(home_url('/')); ?>" class="hover:text-white transition-colors">خانه</a></li>
                <li aria-hidden="true">/</li>
                <li><a href="<?= esc_url($blog); ?>" class="hover:text-white transition-colors">وبلاگ</a></li>
                <li aria-hidden="true">/</li>
                <li class="text-slate-200 font-bold" aria-current="page"><?= esc_html($course['title'] ?? single_cat_title('', false)); ?></li>
            </ol>
        </nav>

        <div class="grid lg:grid-cols-12 gap-10 items-end">
            <div class="lg:col-span-8">
                <span class="inline-flex items-center gap-2 text-xs font-black <?= esc_attr($pal['text']); ?> mb-5" data-aos="fade-up">
                    <span class="w-6 h-px <?= esc_attr($pal['bg']); ?>"></span>دوره آموزشی
                </span>
                <h1 id="course-title" class="text-4xl sm:text-5xl lg:text-6xl font-black text-white leading-[1.25]" <?= kmpr_split_attr($course['title'] ?? ''); ?>>
                    <?= esc_html($course['title'] ?? single_cat_title('', false)); ?>
                </h1>
                <?php if (!empty($course['intro'])) : ?>
                    <p class="mt-6 text-base sm:text-lg text-slate-300 leading-loose max-w-3xl" data-aos="fade-up" data-aos-delay="150"><?= esc_html($course['intro']); ?></p>
                <?php endif; ?>

                <ul class="mt-8 flex flex-wrap gap-3 text-sm" data-stagger="70">
                    <li data-aos="fade-up" class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/[0.04] border border-white/10 text-slate-200">
                        <svg width="16" height="16" class="shrink-0 <?= esc_attr($pal['text']); ?>" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"/></svg>
                        <?= esc_html(kmpr_fa_num($course['count'] ?? 0)); ?> درس
                    </li>
                    <li data-aos="fade-up" class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/[0.04] border border-white/10 text-slate-200">
                        <svg width="16" height="16" class="shrink-0 <?= esc_attr($pal['text']); ?>" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
                        <?= esc_html(kmpr_minutes_label($course['minutes'] ?? 0)); ?>
                    </li>
                    <?php if ($levels) : ?>
                        <li data-aos="fade-up" class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/[0.04] border border-white/10 text-slate-200">
                            <svg width="16" height="16" class="shrink-0 <?= esc_attr($pal['text']); ?>" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 20V14M10 20V9M16 20V4"/></svg>
                            <?= esc_html(implode(' تا ', array_values(array_unique(array_intersect_key(array('beginner' => 'مقدماتی', 'intermediate' => 'متوسط', 'advanced' => 'پیشرفته'), $levels))))); ?>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="lg:col-span-4" data-aos="fade-left" data-aos-delay="200">
                <div class="rounded-3xl bg-slate-900/80 border border-white/10 p-6 backdrop-blur-xl">
                    <div class="flex items-center justify-between text-sm mb-3">
                        <span class="font-black text-white">پیشرفت شما</span>
                        <span class="text-base font-black <?= esc_attr($pal['text']); ?>" x-text="$store.lessons.fa($store.lessons.progress(ids)) + '٪'">۰٪</span>
                    </div>
                    <div class="h-2 rounded-full bg-white/10 overflow-hidden mb-3" role="progressbar" aria-label="پیشرفت دوره" aria-valuemin="0" aria-valuemax="100" :aria-valuenow="$store.lessons.progress(ids)">
                        <div class="h-full rounded-full <?= esc_attr($pal['bg']); ?> transition-all duration-700" style="width:0" :style="'width:' + $store.lessons.progress(ids) + '%'"></div>
                    </div>
                    <p class="text-[13px] text-slate-400 mb-5" x-text="$store.lessons.fa($store.lessons.doneCount(ids)) + ' از ' + $store.lessons.fa(ids.length) + ' درس خوانده شده'"></p>
                    <?php if ($urls) : ?>
                        <a href="<?= esc_url($urls[0]); ?>" :href="$store.lessons.nextUrl(ids, urls)" data-magnetic
                           class="flex items-center justify-center gap-2 w-full min-h-11 px-6 py-3.5 rounded-full bg-secondary hover:bg-amber-500 text-slate-950 text-sm font-black whitespace-nowrap transition-colors">
                            <span x-text="$store.lessons.progress(ids) === 100 ? 'مرور دوباره از اول' : ($store.lessons.started(ids) ? 'ادامه از درس بعدی' : 'شروع از درس اول')">شروع از درس اول</span>
                            <svg width="16" height="16" class="shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.61l4.16 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.61 9.25h10.64A.75.75 0 0 1 17 10Z" clip-rule="evenodd"/></svg>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php if ($pillar && $paged === 1) :
    $pp = get_post($pillar); ?>
<section class="pt-14 lg:pt-20" aria-labelledby="pillar-title">
    <div class="container mx-auto px-4 sm:px-6">
        <a href="<?= esc_url(get_permalink($pp)); ?>" data-aos="zoom-in"
           class="spotlight group grid md:grid-cols-5 rounded-3xl overflow-hidden bg-white/[0.025] border border-white/10 <?= esc_attr($pal['hover']); ?> transition-colors duration-500">
            <div class="relative md:col-span-2 aspect-[16/9] md:aspect-auto md:min-h-72 bg-slate-900 overflow-hidden">
                <?php if (has_post_thumbnail($pp)) :
                    echo get_the_post_thumbnail($pp, 'large', array('class' => 'absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-700', 'alt' => esc_attr(get_the_title($pp)), 'fetchpriority' => 'high', 'loading' => 'eager'));
                else : ?>
                    <div class="absolute inset-0 bg-gradient-to-br <?= esc_attr($pal['glow']); ?> via-slate-900 to-slate-950" aria-hidden="true"></div>
                <?php endif; ?>
            </div>
            <div class="md:col-span-3 p-6 sm:p-8 lg:p-10 flex flex-col justify-center gap-4">
                <span class="inline-flex self-start items-center gap-2 px-3 py-1 rounded-full <?= esc_attr($pal['soft'] . ' ' . $pal['text']); ?> border <?= esc_attr($pal['border']); ?> text-xs font-black">
                    <svg width="12" height="12" class="shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10 1.5l2.6 5.3 5.9.9-4.3 4.1 1 5.8L10 14.9l-5.2 2.7 1-5.8L1.5 7.7l5.9-.9L10 1.5z"/></svg>
                    مقاله ستون این دوره — از اینجا شروع کن
                </span>
                <h2 id="pillar-title" class="text-2xl sm:text-3xl font-black text-white leading-snug group-hover:text-secondary transition-colors"><?= esc_html(get_the_title($pp)); ?></h2>
                <p class="text-slate-300 leading-loose line-clamp-3"><?= esc_html(get_post_meta($pillar, 'tldr', true) ?: wp_trim_words(get_the_excerpt($pp), 40)); ?></p>
                <span class="inline-flex items-center gap-2 text-sm font-black text-slate-200 group-hover:text-secondary transition-colors">
                    خواندن مقاله، <?= esc_html(kmpr_fa_num(reading_time($pillar))); ?> دقیقه
                    <svg width="16" height="16" class="shrink-0 group-hover:-translate-x-1 transition-transform" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.61l4.16 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.61 9.25h10.64A.75.75 0 0 1 17 10Z" clip-rule="evenodd"/></svg>
                </span>
            </div>
        </a>
    </div>
</section>
<?php endif; ?>

<section class="py-14 lg:py-20" aria-labelledby="syllabus-title">
    <div class="container mx-auto px-4 sm:px-6">
        <div class="grid lg:grid-cols-12 gap-10">
            <header class="lg:col-span-4">
                <div class="lg:sticky lg:top-28">
                    <span class="inline-flex items-center gap-2 text-xs font-black text-secondary mb-4" data-aos="fade-up">
                        <span class="w-6 h-px bg-secondary"></span>سرفصل‌ها
                    </span>
                    <h2 id="syllabus-title" class="text-3xl sm:text-4xl font-black text-white leading-tight mb-4">درس به درس، به ترتیب</h2>
                    <p class="text-slate-300 leading-loose">درس‌ها را به همین ترتیب بخوان؛ هر درس روی درس قبلی بنا شده است. تیک سبز یعنی آن درس را تمام کرده‌ای.</p>
                </div>
            </header>

            <div class="lg:col-span-8">
                <?php if ($slice) : ?>
                    <div class="relative">
                    <span class="absolute top-6 bottom-6 start-[2.2rem] sm:start-[2.45rem] w-px bg-white/10" aria-hidden="true"></span>
                    <ol class="relative list-none p-0 m-0 flex flex-col gap-3" data-stagger="60">
                        <?php foreach ($slice as $n => $lid) :
                            $lv  = kmpr_lesson_level($lid);
                            $ex  = get_post_meta($lid, 'tldr', true) ?: wp_trim_words(get_the_excerpt($lid), 22); ?>
                            <li data-aos="fade-up" class="relative">
                                <a href="<?= esc_url(get_permalink($lid)); ?>"
                                   class="group flex items-start gap-4 sm:gap-5 p-4 sm:p-5 rounded-2xl bg-white/[0.025] border border-white/10 hover:border-secondary/40 hover:bg-white/[0.04] transition-colors">
                                    <span class="relative z-10 shrink-0 inline-flex items-center justify-center size-11 rounded-2xl border font-black text-base transition-colors"
                                          :class="$store.lessons.has(<?= (int) $lid; ?>) ? 'bg-emerald-500 border-emerald-400 text-slate-950' : 'bg-slate-950 border-white/15 text-slate-200 group-hover:border-secondary/50'">
                                        <span x-show="!$store.lessons.has(<?= (int) $lid; ?>)"><?= esc_html(kmpr_fa_num($n + 1)); ?></span>
                                        <svg x-show="$store.lessons.has(<?= (int) $lid; ?>)" x-cloak width="18" height="18" class="shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-8 8a1 1 0 0 1-1.4 0l-4-4a1 1 0 1 1 1.4-1.4L8 12.58l7.3-7.3a1 1 0 0 1 1.4 0Z" clip-rule="evenodd"/></svg>
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <?php if ($lid === $pillar) : ?><span class="inline-block mb-1.5 text-xs font-black px-2 py-0.5 rounded-full <?= esc_attr($pal['soft'] . ' ' . $pal['text']); ?>">ستون</span><?php endif; ?>
                                        <span class="sr-only" x-text="$store.lessons.has(<?= (int) $lid; ?>) ? 'خوانده‌شده' : ''"></span>
                                        <h3 class="text-base sm:text-lg font-black text-white leading-snug group-hover:text-secondary transition-colors"><?= esc_html(get_the_title($lid)); ?></h3>
                                        <p class="mt-2 text-sm text-slate-300 leading-7 line-clamp-2"><?= esc_html($ex); ?></p>
                                        <span class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-[13px] text-slate-400">
                                            <span><?= esc_html(kmpr_fa_num(reading_time($lid))); ?> دقیقه مطالعه</span>
                                            <?php if ($lv) : ?><span>سطح: <?= esc_html($lv['label']); ?></span><?php endif; ?>
                                        </span>
                                    </div>
                                    <svg width="18" height="18" class="hidden sm:block shrink-0 self-center text-slate-400 group-hover:text-secondary group-hover:-translate-x-1 transition-all" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M12.8 4.2a.75.75 0 0 1 0 1.06L8.06 10l4.74 4.74a.75.75 0 1 1-1.06 1.06l-5.27-5.27a.75.75 0 0 1 0-1.06l5.27-5.27a.75.75 0 0 1 1.06 0Z" clip-rule="evenodd"/></svg>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                    </div>
                    <?php get_template_part('template-parts/loop/pagination'); ?>
                <?php else : ?>
                    <div class="py-16 text-center rounded-3xl bg-white/[0.025] border border-white/10 p-8">
                        <p class="font-black text-lg text-white">هنوز درسی در این دوره منتشر نشده است.</p>
                        <a href="<?= esc_url($blog); ?>" class="mt-6 inline-flex items-center min-h-11 px-6 py-3 rounded-full bg-secondary hover:bg-amber-500 text-slate-950 text-sm font-black whitespace-nowrap transition-colors">همه دوره‌ها</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php
$others = array_values(array_filter(kmpr_courses(), function ($c) use ($course) {
    return $c['id'] !== ($course['id'] ?? 0);
}));
if ($others) : ?>
<section class="py-14 lg:py-20 border-t border-white/10" aria-labelledby="other-courses-title">
    <div class="container mx-auto px-4 sm:px-6">
        <h2 id="other-courses-title" class="text-2xl sm:text-3xl font-black text-white mb-8">دوره‌های دیگر</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-6 gap-5" data-stagger="100">
            <?php foreach ($others as $i => $oc) {
                get_template_part('template-parts/blog/course-card', null, array('course' => $oc, 'index' => $i, 'class' => kmpr_course_grid_span($i, count($others))));
            } ?>
        </div>
    </div>
</section>
<?php endif; ?>

</div>

<?php get_footer();
