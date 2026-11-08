<?php
/**
 * Games — Game Center (/games/ and genre archives).
 * Immersive arcade space: starfield + neon grid, player HUD, rotating spotlight,
 * console-style snap rails, daily challenge, online lobby, top players, badges.
 * Every game stays a plain crawlable link; ItemList JSON-LD at the end.
 *
 * @var array $args ['term' => WP_Term|null]
 */
global $wp_query;
$term    = $args['term'] ?? null;
$stats   = kmpr_games_stats();
$ids     = wp_list_pluck($wp_query->posts, 'ID');
$daily   = $term ? null : kmpr_games_daily();
$genres  = get_terms(array('taxonomy' => 'game_category', 'hide_empty' => true));
$engines = kmpr_game_engines();

// Spotlight: featured first, then online games, max 4.
$spot = array();
foreach ($ids as $id) {
    if (kmpr_game_meta($id, 'game_is_featured', false)) {
        $spot[] = $id;
    }
}
foreach ($ids as $id) {
    if (count($spot) >= 4) {
        break;
    }
    if (!in_array($id, $spot, true) && !empty($engines[(string) kmpr_game_meta($id, 'game_engine')]['online'])) {
        $spot[] = $id;
    }
}

// Rails.
$in_terms = function ($id, $slugs) {
    $t = wp_get_post_terms($id, 'game_category', array('fields' => 'slugs'));
    return !is_wp_error($t) && array_intersect($slugs, $t);
};
$rails = array();
if ($term) {
    $rails[] = array('id' => 'rail-term', 'title' => $term->name, 'text' => $term->description, 'ids' => $ids);
} else {
    $online_ids = array_values(array_filter($ids, fn($id) => !empty($engines[(string) kmpr_game_meta($id, 'game_engine')]['online'])));
    $arcade_ids = array_values(array_filter($ids, fn($id) => !in_array($id, $online_ids, true) && $in_terms($id, array('arcade'))));
    $mind_ids   = array_values(array_filter($ids, fn($id) => !in_array($id, $online_ids, true) && !in_array($id, $arcade_ids, true)));
    $rails[]    = array('id' => 'rail-online', 'title' => 'آنلاین با دوستان', 'text' => 'مقابل هوش مصنوعی، دو نفره یا با لینک دعوت', 'ids' => $online_ids, 'online' => true);
    $rails[]    = array('id' => 'rail-arcade', 'title' => 'آرکید', 'text' => 'رکورد بزنید و اسم‌تان را به جدول برسانید', 'ids' => $arcade_ids);
    $rails[]    = array('id' => 'rail-mind', 'title' => 'مهارتی و فکری', 'text' => 'سرعت، دقت و حافظه؛ با حال‌وهوای کدنویسی', 'ids' => $mind_ids);
}

$catalog = array();
foreach ($ids as $id) {
    $d         = kmpr_game_data($id, 'medium_large');
    $catalog[] = array('id' => $id, 'engine' => $d['engine'], 'title' => $d['title'], 'url' => $d['url'], 'cover' => $d['cover']['url'] ?? '', 'accent' => $d['accent_hex'], 'genre' => $d['genre'], 'online' => !empty($engines[$d['engine']]['online']));
}
$title = $term ? ($term->name . ' — گیم‌سنتر') : kmpr_game_opt('ga_title');
$text  = $term ? ($term->description ?: 'بازی‌های ژانر ' . $term->name . '؛ همه را خودم از صفر با جاوااسکریپت ساخته‌ام.') : kmpr_game_opt('ga_text');
$arrow = '<svg width="18" height="18" class="shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7.2 14.8a.75.75 0 0 1 0-1.06L10.94 10 7.2 6.26a.75.75 0 1 1 1.06-1.06l4.27 4.27a.75.75 0 0 1 0 1.06L8.26 14.8a.75.75 0 0 1-1.06 0Z" clip-rule="evenodd"/></svg>';
?>
<div class="gc relative isolate" data-arcade-hub>
    <script type="application/json" data-arcade-games><?= wp_json_encode($catalog, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG); ?></script>

    <!-- Atmosphere -->
    <div class="gc-bg" aria-hidden="true">
        <canvas class="gc-stars" data-gc-stars width="10" height="10"></canvas>
        <div class="gc-glow gc-glow--a"></div>
        <div class="gc-glow gc-glow--b"></div>
        <div class="gc-floor"></div>
    </div>

    <div class="container mx-auto px-4 sm:px-6 pt-6 lg:pt-8">
        <nav aria-label="مسیر صفحه" class="mb-4">
            <ol class="flex flex-wrap items-center gap-2 text-sm text-slate-400">
                <li><a href="<?= esc_url(home_url('/')); ?>" class="hover:text-secondary">خانه</a></li>
                <li aria-hidden="true">/</li>
                <?php if ($term) : ?>
                    <li><a href="<?= esc_url(get_post_type_archive_link('game')); ?>" class="hover:text-secondary">گیم‌سنتر</a></li>
                    <li aria-hidden="true">/</li>
                    <li class="text-slate-200 font-bold" aria-current="page"><?= esc_html($term->name); ?></li>
                <?php else : ?>
                    <li class="text-slate-200 font-bold" aria-current="page">گیم‌سنتر</li>
                <?php endif; ?>
            </ol>
        </nav>

        <!-- Player HUD -->
        <section class="gc-hud" id="profile" aria-label="پروفایل بازیکن" data-arcade-profile>
            <div class="flex items-center gap-3 min-w-0">
                <span class="gc-hud__avatar" data-profile="avatar"></span>
                <div class="min-w-0">
                    <p class="text-base sm:text-lg font-black text-white truncate leading-tight" data-profile="name">بازیکن مهمان</p>
                    <p class="text-xs sm:text-sm text-slate-300 truncate">سطح <b class="text-amber-300" data-profile="level">۱</b> · <span data-profile="xp-text">۰ XP</span></p>
                </div>
            </div>
            <div class="gc-hud__xp">
                <div class="game-xp" role="progressbar" aria-label="پیشرفت تا سطح بعد" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" data-profile="xp-bar"><span style="width:0%"></span></div>
            </div>
            <dl class="gc-hud__stats">
                <div><dt>بازی</dt><dd data-profile="plays">۰</dd></div>
                <div><dt>رکورد</dt><dd data-profile="records">۰</dd></div>
                <div><dt>نشان</dt><dd data-profile="ach">۰</dd></div>
                <div><dt>برد</dt><dd data-profile="wl">۰</dd></div>
            </dl>
            <button type="button" class="gc-hud__btn" data-profile-edit>
                <svg width="18" height="18" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
                <span>پروفایل</span>
            </button>
        </section>

        <!-- Title -->
        <header class="mt-8 lg:mt-10 flex flex-col lg:flex-row lg:items-end justify-between gap-4">
            <div class="max-w-3xl">
                <span class="inline-flex items-center gap-2 text-xs font-black text-secondary"><span class="w-6 h-px bg-secondary"></span><?= esc_html($term ? 'ژانر' : kmpr_game_opt('ga_kicker')); ?></span>
                <h1 class="mt-3 text-[1.9rem] leading-[1.45] sm:text-4xl lg:text-5xl lg:leading-[1.35] font-black text-white tracking-tight"><?= esc_html($title); ?></h1>
                <p class="mt-3 text-sm sm:text-base text-slate-300 leading-8"><?= esc_html($text); ?></p>
            </div>
            <dl class="flex gap-2 shrink-0">
                <div class="gc-stat"><dt>بازی</dt><dd><?= esc_html(kmpr_fa_num(count($ids))); ?></dd></div>
                <div class="gc-stat"><dt>دست بازی‌شده</dt><dd><?= esc_html(kmpr_game_num($stats['plays'])); ?></dd></div>
                <div class="gc-stat"><dt>بازیکن</dt><dd><?= esc_html(kmpr_game_num($stats['players'])); ?></dd></div>
            </dl>
        </header>

        <?php if ($spot) : ?>
            <!-- Spotlight -->
            <section class="gc-spot mt-6" aria-label="بازی‌های ویژه" aria-roledescription="carousel" data-gc-spot>
                <?php foreach ($spot as $i => $sid) :
                    $sg     = kmpr_game_data($sid, 'full');
                    $online = !empty($engines[$sg['engine']]['online']); ?>
                    <article class="gc-spot__slide<?= $i === 0 ? ' is-active' : ''; ?>" style="--accent: <?= esc_attr($sg['accent_hex']); ?>" data-gc-slide aria-roledescription="slide" aria-label="<?= esc_attr(kmpr_fa_num($i + 1) . ' از ' . kmpr_fa_num(count($spot))); ?>"<?= $i ? ' aria-hidden="true"' : ''; ?>>
                        <?php if ($sg['cover']) : ?>
                            <img class="gc-spot__img" src="<?= esc_url($sg['cover']['url']); ?>" width="<?= (int) $sg['cover']['width']; ?>" height="<?= (int) $sg['cover']['height']; ?>" alt="" <?= $i === 0 ? 'fetchpriority="high"' : 'loading="lazy"'; ?> decoding="async">
                        <?php endif; ?>
                        <div class="gc-spot__shade" aria-hidden="true"></div>
                        <div class="gc-spot__body">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="gc-chip gc-chip--accent"><?= $i === 0 && kmpr_game_meta($sid, 'game_is_featured', false) ? esc_html(kmpr_game_opt('ga_featured')) : 'پیشنهاد گیم‌سنتر'; ?></span>
                                <?php if ($sg['genre']) : ?><span class="gc-chip"><?= esc_html($sg['genre']); ?></span><?php endif; ?>
                                <?php if ($online) : ?><span class="gc-chip gc-chip--online"><span class="size-1.5 rounded-full bg-emerald-400 shrink-0"></span>چندنفره آنلاین</span><?php endif; ?>
                            </div>
                            <h2 class="gc-spot__title"><?= esc_html($sg['title']); ?></h2>
                            <p class="gc-spot__text"><?= esc_html($sg['tagline'] ?: wp_strip_all_tags($sg['excerpt'])); ?></p>
                            <p class="gc-spot__pitch"><?= esc_html(wp_strip_all_tags($sg['excerpt'])); ?></p>
                            <div class="flex flex-wrap items-center gap-3 mt-1">
                                <a href="<?= esc_url($sg['url']); ?>" class="gc-play" data-magnetic<?= $i ? ' tabindex="-1"' : ''; ?>>
                                    <svg width="22" height="22" class="shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 5.5v13a1 1 0 0 0 1.5.86l11-6.5a1 1 0 0 0 0-1.72l-11-6.5A1 1 0 0 0 7 5.5Z"/></svg>
                                    بازی کن
                                </a>
                                <span class="text-sm text-slate-300 hidden sm:inline">رکورد شما: <b class="text-white" data-game-best="<?= esc_attr($sg['engine']); ?>">—</b></span>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
                <?php if (count($spot) > 1) : ?>
                    <div class="gc-spot__dots" role="tablist" aria-label="انتخاب بازی ویژه">
                        <?php foreach ($spot as $i => $sid) : ?>
                            <button type="button" role="tab" class="gc-spot__dot" data-gc-dot="<?= (int) $i; ?>" aria-selected="<?= $i === 0 ? 'true' : 'false'; ?>" aria-label="<?= esc_attr(get_the_title($sid)); ?>"><span></span></button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <?php do_action('kmpr_games_hub_slot', 'after_hero', $term); ?>

        <?php if ($genres && !is_wp_error($genres)) : ?>
            <nav aria-label="ژانرها" class="mt-8 -mx-4 px-4 overflow-x-auto gc-noscroll">
                <ul class="flex gap-2 w-max">
                    <li><a href="<?= esc_url(get_post_type_archive_link('game')); ?>" class="gc-pill<?= !$term ? ' is-on' : ''; ?>"<?= !$term ? ' aria-current="page"' : ''; ?>>همه بازی‌ها</a></li>
                    <?php foreach ($genres as $gt) : $on = $term && $term->term_id === $gt->term_id; ?>
                        <li><a href="<?= esc_url(get_term_link($gt)); ?>" class="gc-pill<?= $on ? ' is-on' : ''; ?>"<?= $on ? ' aria-current="page"' : ''; ?>><?= esc_html($gt->name); ?> <span class="opacity-70"><?= esc_html(kmpr_fa_num($gt->count)); ?></span></a></li>
                    <?php endforeach; ?>
                </ul>
            </nav>
        <?php endif; ?>

        <div class="mt-6 grid grid-cols-1 xl:grid-cols-12 gap-8 xl:gap-6 items-start">
            <!-- Rails -->
            <div class="xl:col-span-8 min-w-0 flex flex-col gap-9" id="all-games">
                <?php if (!$term) : ?>
                    <section class="gc-rail" aria-labelledby="rail-continue-title" data-continue-rail hidden>
                        <div class="gc-rail__head">
                            <div><h2 id="rail-continue-title" class="gc-rail__title">ادامه بازی</h2><p class="gc-rail__text">آخرین بازی‌هایی که روی همین دستگاه انجام دادید</p></div>
                        </div>
                        <ul class="gc-rail__track gc-noscroll" data-continue></ul>
                    </section>
                <?php endif; ?>

                <?php foreach ($rails as $rail) :
                    if (!$rail['ids']) {
                        continue;
                    } ?>
                    <section class="gc-rail" aria-labelledby="<?= esc_attr($rail['id']); ?>-title" data-gc-rail>
                        <div class="gc-rail__head">
                            <div>
                                <h2 id="<?= esc_attr($rail['id']); ?>-title" class="gc-rail__title">
                                    <?php if (!empty($rail['online'])) : ?><span class="relative flex size-2.5 shrink-0"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span><span class="relative inline-flex rounded-full size-2.5 bg-emerald-500"></span></span><?php endif; ?>
                                    <?= esc_html($rail['title']); ?> <span class="gc-rail__count"><?= esc_html(kmpr_fa_num(count($rail['ids']))); ?></span>
                                </h2>
                                <?php if (!empty($rail['text'])) : ?><p class="gc-rail__text"><?= esc_html($rail['text']); ?></p><?php endif; ?>
                            </div>
                            <div class="gc-rail__nav" aria-hidden="true">
                                <button type="button" class="gc-rail__btn" data-rail-dir="-1" tabindex="-1"><?= $arrow; // phpcs:ignore ?></button>
                                <button type="button" class="gc-rail__btn" data-rail-dir="1" tabindex="-1"><span class="rotate-180 inline-flex"><?= $arrow; // phpcs:ignore ?></span></button>
                            </div>
                        </div>
                        <ul class="gc-rail__track gc-noscroll">
                            <?php foreach ($rail['ids'] as $rid) {
                                get_template_part('template-parts/games/tile', null, array('id' => $rid));
                            } ?>
                        </ul>
                    </section>
                <?php endforeach; ?>

                <?php if (!$ids) : ?>
                    <p class="text-center text-slate-300 py-16 rounded-3xl border border-dashed border-white/10">هنوز بازی‌ای در این بخش منتشر نشده است.</p>
                <?php endif; ?>

                <?php do_action('kmpr_games_hub_slot', 'after_rails', $term); ?>

                <?php if (!$term) : ?>
                    <!-- Badges + recent -->
                    <section class="gc-panel" aria-label="نشان‌ها و بازی‌های اخیر">
                        <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
                            <div class="lg:col-span-3">
                                <div class="flex items-center justify-between gap-3 mb-3">
                                    <h2 class="text-lg font-black text-white">نشان‌های من</h2>
                                    <span class="text-sm text-slate-300" data-badges-count></span>
                                </div>
                                <ul class="grid grid-cols-8 sm:grid-cols-12 gap-1.5" data-badges></ul>
                            </div>
                            <div class="lg:col-span-2">
                                <h2 class="text-lg font-black text-white mb-3">بازی‌های اخیر</h2>
                                <ul class="flex flex-col gap-2" data-recent>
                                    <li class="text-sm text-slate-400 py-5 text-center rounded-2xl border border-dashed border-white/10">هنوز سابقه‌ای ثبت نشده است.</li>
                                </ul>
                            </div>
                        </div>
                    </section>
                <?php endif; ?>
            </div>

            <!-- Side panels -->
            <aside class="xl:col-span-4 flex flex-col gap-5 xl:sticky xl:top-24" aria-label="چالش روزانه، لابی و برترین‌ها">
                <?php do_action('kmpr_games_hub_slot', 'sidebar', $term); ?>
                <?php if ($daily) :
                    $dg = kmpr_game_data($daily['id'], 'medium'); ?>
                    <div class="gc-panel gc-panel--daily spotlight" data-daily data-engine="<?= esc_attr($daily['engine']); ?>" data-target="<?= (int) $daily['target']; ?>">
                        <p class="inline-flex items-center gap-2 text-xs font-black text-secondary"><svg width="16" height="16" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><rect x="3" y="4" width="18" height="17" rx="3"/><path d="M8 2v4M16 2v4M3 10h18"/></svg> چالش امروز · <?= esc_html(shamsi_date('j F', time())); ?></p>
                        <h2 class="mt-2 text-lg font-black text-white leading-8">در «<?= esc_html($dg['title']); ?>» به <span class="text-amber-300"><?= esc_html(kmpr_game_num($daily['target'])); ?></span> امتیاز برسید</h2>
                        <div class="mt-4 flex items-center justify-between gap-3 flex-wrap">
                            <p class="text-sm text-slate-300">امروز: <b class="text-white text-base" data-daily-best>—</b> <span class="text-emerald-300 font-black" data-daily-done hidden>✓ انجام شد</span></p>
                            <a href="<?= esc_url($dg['url']); ?>" class="inline-flex items-center justify-center gap-2 px-6 py-3 min-h-11 rounded-full bg-secondary hover:bg-amber-500 text-slate-950 font-black text-sm whitespace-nowrap transition-colors">قبول چالش</a>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!$term) : ?>
                    <div class="gc-panel" id="lobby">
                        <div class="flex items-center justify-between gap-3 mb-3">
                            <h2 class="flex items-center gap-2 text-lg font-black text-white"><span class="size-2.5 rounded-full bg-emerald-400 shrink-0"></span> لابی آنلاین</h2>
                            <span class="text-xs text-slate-400">اتاق‌های منتظر حریف</span>
                        </div>
                        <ul class="flex flex-col gap-2" data-lobby aria-live="polite">
                            <li class="text-sm text-slate-400 py-5 text-center rounded-2xl border border-dashed border-white/10">در حال بررسی اتاق‌ها…</li>
                        </ul>
                        <p class="mt-3 text-xs text-slate-400 leading-6">اتاقی نیست؟ در یک بازی آنلاین «حریف تصادفی» را بزنید تا اتاق شما اینجا دیده شود.</p>
                    </div>
                    <?php if (kmpr_games_server_scores()) : ?>
                    <div class="gc-panel scroll-mt-24" id="leaders">
                        <div class="flex items-center justify-between gap-3 mb-3">
                            <h2 class="flex items-center gap-2 text-lg font-black text-white"><svg width="18" height="18" class="shrink-0 text-secondary" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 3h10v2h3v3a4 4 0 0 1-4 4h-.35A5 5 0 0 1 13 14.9V18h3v3H8v-3h3v-3.1A5 5 0 0 1 8.35 12H8a4 4 0 0 1-4-4V5h3V3Z"/></svg> برترین بازیکن‌ها</h2>
                            <span class="text-xs text-slate-400">بر اساس XP</span>
                        </div>
                        <ol class="flex flex-col gap-2" data-top-players>
                            <li class="text-sm text-slate-400 py-5 text-center rounded-2xl border border-dashed border-white/10">در حال بارگذاری…</li>
                        </ol>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </aside>
        </div>
    </div>

    <!-- CTA -->
    <section class="container mx-auto px-4 sm:px-6 py-16">
        <div class="relative overflow-hidden rounded-[2rem] border border-white/10 bg-gradient-to-br from-slate-900 via-slate-900 to-indigo-950/60 p-8 sm:p-12 text-center">
            <div class="aurora w-[26rem] h-[26rem] -top-40 start-1/2 bg-secondary/20" aria-hidden="true"></div>
            <h2 class="relative text-2xl sm:text-4xl font-black text-white leading-relaxed"><?= esc_html(kmpr_game_opt('ga_cta_title')); ?></h2>
            <p class="relative mt-4 max-w-2xl mx-auto text-slate-300 leading-8"><?= esc_html(kmpr_game_opt('ga_cta_text')); ?></p>
            <a href="<?= esc_url(kmpr_request_url()); ?>" class="relative mt-8 inline-flex items-center justify-center gap-2 px-8 py-3.5 rounded-full bg-secondary hover:bg-amber-500 text-slate-950 font-black whitespace-nowrap transition-colors"><?= esc_html(kmpr_game_opt('ga_cta_btn')); ?></a>
        </div>
    </section>

    <!-- Profile editor -->
    <dialog class="arcade-dialog" data-profile-dialog aria-labelledby="profile-dialog-title">
        <form method="dialog" class="flex flex-col gap-5" data-profile-form>
            <div class="flex items-center justify-between gap-3">
                <h2 id="profile-dialog-title" class="text-xl font-black text-white">پروفایل بازیکن</h2>
                <button value="cancel" class="inline-flex items-center justify-center size-11 rounded-2xl bg-white/[0.05] border border-white/10 text-slate-200" aria-label="بستن">
                    <svg width="18" height="18" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
                </button>
            </div>
            <div class="flex items-center gap-4">
                <span class="shrink-0" data-edit-preview></span>
                <div class="flex-1 min-w-0">
                    <label for="arcade-nick" class="block text-sm font-bold text-slate-300 mb-1.5">نام مستعار</label>
                    <input id="arcade-nick" name="nickname" maxlength="24" class="game-nick__input w-full" autocomplete="nickname" placeholder="مثلاً: کدنویس شب">
                </div>
            </div>
            <fieldset>
                <legend class="text-sm font-bold text-slate-300 mb-2">سبک آواتار</legend>
                <div class="grid grid-cols-4 gap-2" data-edit-styles></div>
            </fieldset>
            <fieldset>
                <legend class="text-sm font-bold text-slate-300 mb-2">رنگ</legend>
                <div class="flex flex-wrap gap-2" data-edit-hues></div>
            </fieldset>
            <p class="text-xs text-slate-400 leading-6">پروفایل با یک شناسه تصادفی روی همین مرورگر ساخته می‌شود؛ بدون ثبت‌نام و بدون اطلاعات شخصی.</p>
            <button value="save" class="inline-flex items-center justify-center px-8 py-3.5 rounded-full bg-secondary hover:bg-amber-500 text-slate-950 font-black whitespace-nowrap transition-colors">ذخیره پروفایل</button>
        </form>
    </dialog>
</div>
<?= kmpr_games_archive_schema($ids); // phpcs:ignore WordPress.Security.EscapeOutput -- JSON-LD built with wp_json_encode. ?>
