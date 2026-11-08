<?php
/**
 * Single game — player shell, how to play, controls, leaderboard, personal stats,
 * game info, tech used + GitHub, other games, VideoGame JSON-LD.
 */
get_header();

while (have_posts()) :
    the_post();
    $g        = kmpr_game_data(get_the_ID(), 'large');
    $stats    = kmpr_games_stats();
    $plays    = $stats['games'][$g['id']]['plays'] ?? 0;
    $players  = $stats['games'][$g['id']]['players'] ?? 0;
    $is_online = !empty(kmpr_game_engines()[$g['engine']]['online']);
    $others   = get_posts(array('post_type' => 'game', 'post_status' => 'publish', 'posts_per_page' => 3, 'post__not_in' => array($g['id']), 'orderby' => 'rand', 'fields' => 'ids', 'no_found_rows' => true));
    ?>
    <article class="relative isolate overflow-hidden" style="--accent: <?= esc_attr($g['accent_hex']); ?>">
        <div class="aurora w-[32rem] h-[32rem] -top-56 -start-40 bg-secondary/20 -z-10" aria-hidden="true"></div>

        <!-- Game-first header: one compact strip, then the stage right away -->
        <header class="gs-head container mx-auto px-3 sm:px-6">
            <nav aria-label="مسیر صفحه" class="gs-crumbs">
                <ol class="flex flex-wrap items-center gap-1.5 text-xs text-slate-400">
                    <li><a href="<?= esc_url(home_url('/')); ?>" class="hover:text-secondary">خانه</a></li>
                    <li aria-hidden="true">/</li>
                    <li><a href="<?= esc_url(get_post_type_archive_link('game')); ?>" class="hover:text-secondary">بازی‌ها</a></li>
                    <li aria-hidden="true">/</li>
                    <li class="text-slate-300 font-bold truncate max-w-[12rem]" aria-current="page"><?= esc_html($g['title']); ?></li>
                </ol>
            </nav>
            <div class="gs-strip">
                <?php if ($g['cover']) : ?>
                    <span class="gs-thumb" aria-hidden="true"><img src="<?= esc_url(get_the_post_thumbnail_url($g['id'], 'thumbnail') ?: $g['cover']['url']); ?>" width="48" height="48" alt="" decoding="async"></span>
                <?php endif; ?>
                <div class="min-w-0 flex-1">
                    <h1 class="gs-title"><?= esc_html($g['title']); ?></h1>
                    <div class="gs-chips">
                        <?php if ($g['genre']) : ?>
                            <a href="<?= esc_url($g['genre_url']); ?>" class="gs-chip gs-chip--accent"><?= esc_html($g['genre']); ?></a>
                        <?php endif; ?>
                        <span class="gs-chip"><?= esc_html($g['diff_label']); ?></span>
                        <?php if ($is_online) : ?>
                            <span class="gs-chip gs-chip--online"><span class="size-1.5 rounded-full bg-emerald-400 shrink-0"></span>آنلاین</span>
                        <?php endif; ?>
                        <span class="gs-chip gs-chip--stat lg:hidden"><?= esc_html(kmpr_game_num($plays)); ?> دست</span>
                    </div>
                </div>
                <dl class="gs-stats">
                    <div><dt>دست بازی‌شده</dt><dd><?= esc_html(kmpr_game_num($plays)); ?></dd></div>
                    <div><dt>بازیکن</dt><dd><?= esc_html(kmpr_game_num($players)); ?></dd></div>
                </dl>
            </div>
        </header>

        <section class="gs-stagewrap container mx-auto px-2 sm:px-6 pb-10" aria-label="صفحه بازی">
            <div class="max-w-6xl mx-auto">
                <?php do_action('kmpr_game_slot', $g['id'], 'before_stage'); ?>
                <?php get_template_part('template-parts/games/player', null, array('game' => $g)); ?>
                <?php do_action('kmpr_game_slot', $g['id'], 'after_stage'); ?>
            </div>
        </section>

        <div class="container mx-auto px-4 sm:px-6 pb-16">
            <div class="max-w-6xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-6">
                <div class="lg:col-span-7 flex flex-col gap-6">
                    <?php if ($g['how']) : ?>
                        <section class="rounded-3xl bg-white/[0.025] border border-white/10 p-6" aria-labelledby="how-title" data-aos="fade-up">
                            <h2 id="how-title" class="text-xl font-black text-white mb-4"><?= esc_html(kmpr_game_opt('gs_how_title')); ?></h2>
                            <ol class="flex flex-col gap-3">
                                <?php foreach ($g['how'] as $i => $step) : ?>
                                    <li class="flex gap-3">
                                        <span class="shrink-0 size-8 rounded-xl bg-secondary/15 border border-secondary/30 text-amber-300 font-black grid place-items-center text-sm"><?= esc_html(kmpr_fa_num($i + 1)); ?></span>
                                        <span class="text-slate-300 leading-8"><?= esc_html($step); ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ol>
                        </section>
                    <?php endif; ?>

                    <?php if ($g['controls'] || $g['mobile']) : ?>
                        <section class="rounded-3xl bg-white/[0.025] border border-white/10 p-6" aria-labelledby="controls-title" data-aos="fade-up">
                            <h2 id="controls-title" class="text-xl font-black text-white mb-4">کنترل‌ها</h2>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                <?php if ($g['controls']) : ?>
                                    <div>
                                        <h3 class="text-sm font-black text-slate-200 mb-3">کیبورد و ماوس</h3>
                                        <ul class="flex flex-col gap-2">
                                            <?php foreach ($g['controls'] as $c) : ?>
                                                <li class="flex items-center justify-between gap-3 text-sm">
                                                    <kbd class="px-2.5 py-1 rounded-lg bg-slate-800 border border-white/15 border-b-2 text-slate-100 font-bold whitespace-nowrap" dir="ltr"><?= esc_html($c['key']); ?></kbd>
                                                    <span class="text-slate-300 text-end"><?= esc_html($c['action']); ?></span>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                                <?php if ($g['mobile']) : ?>
                                    <div>
                                        <h3 class="text-sm font-black text-slate-200 mb-3">موبایل و لمسی</h3>
                                        <ul class="flex flex-col gap-2">
                                            <?php foreach ($g['mobile'] as $m) : ?>
                                                <li class="flex gap-2 text-sm text-slate-300 leading-7"><span class="text-secondary shrink-0" aria-hidden="true">●</span><?= esc_html($m); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </section>
                    <?php endif; ?>

                    <section class="rounded-3xl bg-white/[0.025] border border-white/10 p-6" aria-labelledby="about-title" data-aos="fade-up">
                        <h2 id="about-title" class="text-xl font-black text-white mb-4">درباره این بازی</h2>
                        <div class="prose prose-invert max-w-none text-slate-300 leading-8 prose-p:my-3 prose-headings:text-white prose-strong:text-white">
                            <?php the_content(); ?>
                        </div>
                    </section>

                    <?php if ($g['tech'] || $g['github']) : ?>
                        <section class="rounded-3xl bg-white/[0.025] border border-white/10 p-6" aria-labelledby="tech-title" data-aos="fade-up">
                            <h2 id="tech-title" class="text-xl font-black text-white mb-4"><?= esc_html(kmpr_game_opt('gs_tech_title')); ?></h2>
                            <ul class="flex flex-wrap gap-2">
                                <?php foreach ($g['tech'] as $t) : ?>
                                    <li class="px-3 py-1.5 rounded-full bg-white/[0.05] border border-white/10 text-sm font-bold text-slate-200" dir="auto"><?= esc_html($t); ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <?php if ($g['github']) : ?>
                                <a href="<?= esc_url($g['github']); ?>" target="_blank" rel="noopener" class="mt-5 inline-flex items-center gap-2 px-6 py-3 min-h-11 rounded-full bg-white/[0.05] hover:bg-white/[0.1] border border-white/15 text-slate-100 font-bold text-sm whitespace-nowrap transition-colors">
                                    <svg width="18" height="18" class="shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 .5a11.5 11.5 0 0 0-3.64 22.41c.58.1.79-.25.79-.56v-2c-3.2.7-3.88-1.37-3.88-1.37-.52-1.33-1.28-1.69-1.28-1.69-1.05-.71.08-.7.08-.7 1.16.08 1.77 1.19 1.77 1.19 1.03 1.77 2.7 1.26 3.36.96.1-.75.4-1.26.73-1.55-2.55-.29-5.24-1.28-5.24-5.69 0-1.26.45-2.29 1.19-3.1-.12-.29-.52-1.46.11-3.05 0 0 .97-.31 3.17 1.18a11 11 0 0 1 5.77 0c2.2-1.49 3.17-1.18 3.17-1.18.63 1.59.23 2.76.11 3.05.74.81 1.19 1.84 1.19 3.1 0 4.42-2.7 5.39-5.26 5.68.41.36.78 1.06.78 2.14v3.17c0 .31.21.67.8.56A11.5 11.5 0 0 0 12 .5Z"/></svg>
                                    مشاهده کد روی گیت‌هاب
                                </a>
                            <?php endif; ?>
                        </section>
                    <?php endif; ?>
                </div>

                <aside class="lg:col-span-5 flex flex-col gap-6" aria-label="جدول امتیاز و آمار شما">
                    <?php do_action('kmpr_game_slot', $g['id'], 'sidebar'); ?>
                    <?php get_template_part('template-parts/games/leaderboard', null, array('game' => $g)); ?>
                    <section class="rounded-3xl bg-white/[0.025] border border-white/10 p-5 sm:p-6" aria-labelledby="mystats-title" style="--accent: <?= esc_attr($g['accent_hex']); ?>">
                        <div class="flex items-center justify-between gap-3 mb-4">
                            <h2 id="mystats-title" class="text-lg font-black text-white">آمار و دستاوردهای شما</h2>
                            <a href="<?= esc_url(get_post_type_archive_link('game')); ?>#profile" class="text-sm font-bold text-secondary hover:underline">پروفایل</a>
                        </div>
                        <div data-player-stats data-engine="<?= esc_attr($g['engine']); ?>">
                            <p class="text-sm text-slate-400">در حال بارگذاری آمار…</p>
                        </div>
                    </section>
                </aside>
            </div>
        </div>

        <?php if ($others) : ?>
            <section class="container mx-auto px-4 sm:px-6 pb-20" aria-labelledby="more-games-title">
                <div class="max-w-6xl mx-auto">
                    <div class="flex items-end justify-between gap-4 mb-6">
                        <h2 id="more-games-title" class="text-2xl sm:text-3xl font-black text-white"><?= esc_html(kmpr_game_opt('gs_more_title')); ?></h2>
                        <a href="<?= esc_url(get_post_type_archive_link('game')); ?>" class="shrink-0 inline-flex items-center min-h-11 px-5 rounded-full bg-white/[0.04] border border-white/10 hover:border-white/30 text-sm font-bold text-slate-100 whitespace-nowrap">همه بازی‌ها</a>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                        <?php foreach ($others as $oid) {
                            get_template_part('template-parts/games/card', null, array('id' => $oid, 'stats' => $stats, 'lazy' => true));
                        } ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>
    </article>
    <?= kmpr_game_schema(get_the_ID()); // phpcs:ignore WordPress.Security.EscapeOutput -- JSON-LD via wp_json_encode. ?>
<?php endwhile;

get_footer();
