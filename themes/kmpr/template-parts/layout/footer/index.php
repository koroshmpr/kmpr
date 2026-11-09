<?php
/**
 * Site footer (non-game pages). Wrapped by the <footer> in footer.php.
 *
 * 1. CTA band (availability + project request)  2. brand / menus / latest posts / game center
 * 3. contact strip  4. bottom bar (copyright.php)
 * Copy: «تنظیمات کلی سایت › فوتر». Link columns: Appearance → Menus (footerLocationOne/Two/Three).
 *
 * @package KMPR
 */

$kf_avail_raw = function_exists('get_field') ? get_field('available_for_work', 'option') : null;
$kf_available = ($kf_avail_raw === null || $kf_avail_raw === '') ? true : (bool) $kf_avail_raw;
$kf_telegram  = kmpr_contact('telegram');
$kf_whatsapp  = kmpr_contact('whatsapp');
$kf_chat      = $kf_telegram ?: $kf_whatsapp;
$kf_phone     = kmpr_contact('phone');
$kf_phone_lbl = kmpr_field('phone_number_label', '', 'option') ?: $kf_phone;
$kf_email     = kmpr_contact('email');
$kf_address   = trim(wp_strip_all_tags((string) kmpr_contact('address')));
$kf_map       = kmpr_contact('map-link');
$kf_bio       = trim(wp_strip_all_tags((string) kmpr_field('footer_description', '', 'option')));
$kf_bio       = $kf_bio ?: kmpr_field('job_title', 'طراح و توسعه‌دهنده وب', 'option');

$kf_count = function ($type) {
    return post_type_exists($type) ? (int) wp_count_posts($type)->publish : 0;
};
$kf_projects = $kf_count('portfolio');
$kf_games_n  = $kf_count('game');
$kf_stats    = array_filter(array(
    array(kmpr_years_experience(), 'سال تجربه', '+'),
    array($kf_projects, 'نمونه‌کار', ''),
    array($kf_games_n, 'بازی', ''),
), fn($s) => $s[0] > 0);

/**
 * Top-level items of a footer menu location, or a fallback list. Each: url, title, current.
 */
$kf_menu_items = function ($location, $fallback) {
    $locations = get_nav_menu_locations();
    $out       = array();
    if (!empty($locations[$location]) && ($items = wp_get_nav_menu_items($locations[$location]))) {
        _wp_menu_item_classes_by_context($items);
        foreach ($items as $it) {
            if ((int) $it->menu_item_parent === 0) {
                $out[] = array('url' => $it->url, 'title' => $it->title, 'current' => !empty($it->current), 'target' => $it->target);
            }
        }
    }
    return $out ?: $fallback;
};
$kf_posts_fallback = function ($type) {
    return array_map(fn($id) => array('url' => get_permalink($id), 'title' => get_the_title($id), 'current' => is_single($id), 'target' => ''), get_posts(array('post_type' => $type, 'post_status' => 'publish', 'posts_per_page' => 5, 'fields' => 'ids', 'no_found_rows' => true, 'orderby' => array('menu_order' => 'ASC', 'date' => 'DESC'))));
};
$kf_columns = array(
    array(
        'title' => kmpr_field('first_menu', 'دسترسی سریع', 'option'),
        'items' => $kf_menu_items('footerLocationOne', array(
            array('url' => home_url('/'), 'title' => 'صفحه اصلی', 'current' => is_front_page(), 'target' => ''),
            array('url' => home_url('/blog/'), 'title' => 'مقالات', 'current' => false, 'target' => ''),
            array('url' => home_url('/games/'), 'title' => 'بازی‌ها', 'current' => false, 'target' => ''),
            array('url' => home_url('/about-us/'), 'title' => 'درباره من', 'current' => false, 'target' => ''),
            array('url' => home_url('/contact-us/'), 'title' => 'ارتباط با من', 'current' => false, 'target' => ''),
        )),
        'all'   => null,
    ),
    array(
        'title' => kmpr_field('second_menu', 'خدمات', 'option'),
        'items' => $kf_menu_items('footerLocationTwo', $kf_posts_fallback('services')),
        'all'   => post_type_exists('services') ? array(get_post_type_archive_link('services'), 'همه خدمات') : null,
    ),
);

$kf_latest = get_posts(array(
    'post_type'           => 'post',
    'post_status'         => 'publish',
    'posts_per_page'      => 3,
    'no_found_rows'       => true,
    'ignore_sticky_posts' => true,
    'post__not_in'        => is_singular('post') ? array(get_the_ID()) : array(),
));

$kf_games_url = post_type_exists('game') ? (get_post_type_archive_link('game') ?: home_url('/games/')) : '';
$kf_game_covers = array();
if ($kf_games_url) {
    foreach (get_posts(array('post_type' => 'game', 'post_status' => 'publish', 'posts_per_page' => 3, 'fields' => 'ids', 'no_found_rows' => true, 'orderby' => array('menu_order' => 'ASC', 'date' => 'DESC'), 'meta_key' => '_thumbnail_id')) as $gid) {
        $src = wp_get_attachment_image_src(get_post_thumbnail_id($gid), 'medium_large');
        if ($src) {
            $kf_game_covers[] = array('src' => $src[0], 'alt' => get_the_title($gid));
        }
    }
}

$kf_arrow = '<svg width="14" height="14" class="shrink-0 transition-transform duration-300 group-hover:-translate-x-1 motion-reduce:transition-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M11 18l-6-6 6-6"/></svg>';
$kf_tg_svg = '<svg width="18" height="18" class="shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M21.9 4.6 18.7 19.7c-.2 1-.9 1.3-1.8.8l-4.9-3.6-2.4 2.3c-.3.3-.5.5-1 .5l.3-5 9.2-8.3c.4-.4-.1-.6-.6-.2L6.2 13.4l-4.9-1.5c-1-.3-1.1-1 .2-1.6L20.6 3c.9-.3 1.6.2 1.3 1.6Z"/></svg>';
$kf_wa_svg = '<svg width="18" height="18" class="shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm0 18.2a8.2 8.2 0 0 1-4.2-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2Zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8-.2-.1-.4-.1-.6.1l-.8 1c-.1.2-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.3-.4.2-.4.7-1.4.1-.2 0-.3 0-.4l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.1 5.1 0 0 0 1.1 2.7 11.7 11.7 0 0 0 4.5 4c1.7.7 2.3.8 3.2.6.5-.1 1.5-.6 1.7-1.2.2-.6.2-1.1.2-1.2-.1-.1-.2-.2-.5-.3Z"/></svg>';
?>
<div class="kf relative isolate bg-slate-950 text-slate-100 border-t border-slate-900 overflow-x-clip">
    <div class="kf__glow" aria-hidden="true"></div>
    <div class="container mx-auto px-4 sm:px-6">

        <!-- 1. CTA band -->
        <section class="kf-cta relative -mt-px pt-12 lg:pt-16" aria-labelledby="kf-cta-title" data-aos="fade-up">
            <div class="kf-cta__card relative overflow-hidden rounded-3xl border border-white/10 bg-slate-900/70 px-5 py-7 sm:px-8 sm:py-8 lg:px-10 flex flex-col lg:flex-row lg:items-center justify-between gap-6 lg:gap-10">
                <div class="min-w-0 max-w-2xl">
                    <p class="inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-bold <?= $kf_available ? 'border-emerald-400/25 bg-emerald-400/10 text-emerald-200' : 'border-amber-400/25 bg-amber-400/10 text-amber-200'; ?>">
                        <span class="relative flex size-2 shrink-0" aria-hidden="true">
                            <?php if ($kf_available) : ?><span class="absolute inset-0 rounded-full bg-emerald-400 animate-ping opacity-60 motion-reduce:animate-none"></span><?php endif; ?>
                            <span class="relative size-2 rounded-full <?= $kf_available ? 'bg-emerald-400' : 'bg-amber-400'; ?>"></span>
                        </span>
                        <span><?= esc_html($kf_available ? 'آماده پذیرش پروژه جدید' : kmpr_field('footer_busy_text', null, 'option')); ?></span>
                        <?php if ($kf_available && ($rt = kmpr_field('response_time', '', 'option'))) : ?>
                            <span class="text-emerald-200/50" aria-hidden="true">·</span>
                            <span class="font-medium text-slate-200"><?= esc_html($rt); ?></span>
                        <?php endif; ?>
                    </p>
                    <h2 id="kf-cta-title" class="mt-4 text-xl sm:text-2xl lg:text-[1.7rem] font-black leading-snug text-white"><?= esc_html(kmpr_field('footer_cta_title', null, 'option')); ?></h2>
                    <p class="mt-2 text-sm leading-7 text-slate-300"><?= esc_html(kmpr_field('footer_cta_text', null, 'option')); ?></p>
                </div>
                <div class="flex flex-col min-[420px]:flex-row gap-3 shrink-0">
                    <a href="<?= esc_url(kmpr_request_url()); ?>" class="group inline-flex items-center justify-center gap-2.5 min-h-12 px-7 py-3 rounded-full bg-secondary hover:bg-amber-500 text-slate-950 font-black text-sm whitespace-nowrap shadow-[0_10px_40px_-12px] shadow-secondary/70 transition-colors">
                        <svg width="18" height="18" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4.5 16.5c-1.5 1.3-2 5-2 5s3.7-.5 5-2c.7-.8.7-2.1-.1-2.9a2.2 2.2 0 0 0-2.9-.1Z"/><path d="m12 15-3-3a22 22 0 0 1 2-3.9A12.9 12.9 0 0 1 22 2c0 2.7-.8 7.5-6 11a22.4 22.4 0 0 1-4 2Z"/><path d="M9 12H4s.6-3 2-4c1.6-1.1 5 0 5 0M12 15v5s3-.6 4-2c1.1-1.6 0-5 0-5"/></svg>
                        <?= esc_html(kmpr_field('footer_cta_btn', null, 'option')); ?>
                    </a>
                    <?php if ($kf_chat) : ?>
                        <a href="<?= esc_url($kf_chat); ?>" target="_blank" rel="noopener" class="inline-flex items-center justify-center gap-2.5 min-h-12 px-7 py-3 rounded-full border border-white/15 bg-white/5 hover:bg-white/10 hover:border-sky-400/40 text-slate-100 font-bold text-sm whitespace-nowrap transition-colors">
                            <span class="<?= $kf_telegram ? 'text-sky-400' : 'text-emerald-400'; ?>"><?= $kf_telegram ? $kf_tg_svg : $kf_wa_svg; // phpcs:ignore ?></span>
                            <?= esc_html($kf_telegram ? kmpr_field('footer_cta_btn2', null, 'option') : 'مشاوره در واتساپ'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- 2. Main grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-x-8 gap-y-10 xl:gap-x-12 pt-12 lg:pt-14 pb-10">

            <!-- Brand -->
            <section class="md:col-span-2 lg:col-span-4 lg:col-start-1 lg:row-start-1 flex flex-col gap-5 md:flex-row md:items-start md:justify-between lg:flex-col lg:justify-start" aria-label="درباره کوروش محمدپور">
                <div class="flex flex-col gap-4 md:max-w-sm lg:max-w-none">
                    <a href="<?= esc_url(home_url('/')); ?>" class="group inline-flex items-center gap-3 self-start" aria-label="<?= esc_attr(get_bloginfo('name')); ?> — صفحه اصلی">
                        <svg width="48" height="48" viewBox="0 0 40 40" class="shrink-0 size-12 transition-transform duration-500 group-hover:-rotate-6 motion-reduce:transition-none" aria-hidden="true">
                            <defs>
                                <linearGradient id="kf-mark-amber" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#fbbf24"/><stop offset="1" stop-color="#d97706"/></linearGradient>
                                <linearGradient id="kf-mark-edge" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#f59e0b" stop-opacity=".9"/><stop offset=".5" stop-color="#334155"/><stop offset="1" stop-color="#38bdf8" stop-opacity=".8"/></linearGradient>
                            </defs>
                            <rect x="1" y="1" width="38" height="38" rx="11" fill="#0b1224" stroke="url(#kf-mark-edge)" stroke-width="1.5"/>
                            <path d="M14.5 13.5 9 20l5.5 6.5" fill="none" stroke="#38bdf8" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M19.5 11v18" fill="none" stroke="url(#kf-mark-amber)" stroke-width="2.8" stroke-linecap="round"/>
                            <path d="m29.5 11.5-8 8.5 8 8.5" fill="none" stroke="url(#kf-mark-amber)" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span class="flex flex-col gap-1 leading-none">
                            <span class="text-lg font-black text-white group-hover:text-secondary transition-colors"><?= esc_html(get_bloginfo('name')); ?></span>
                            <span class="text-xs font-bold text-slate-400"><span dir="ltr" class="text-secondary">KMPR</span> · <?= esc_html(kmpr_field('job_title', 'طراح و توسعه‌دهنده وب', 'option')); ?></span>
                        </span>
                    </a>
                    <p class="text-sm leading-7 text-slate-300"><?= esc_html($kf_bio); ?></p>
                </div>
                <div class="flex flex-col gap-5 lg:items-stretch">
                    <?php if ($kf_stats) : ?>
                        <dl class="grid grid-cols-3 gap-2 w-full md:w-auto md:min-w-[19rem] lg:min-w-0">
                            <?php foreach ($kf_stats as $s) : ?>
                                <div class="flex flex-col items-center justify-center gap-1 rounded-2xl border border-white/[.07] bg-white/[.03] px-2 py-3">
                                    <dt class="order-last text-[11px] font-medium text-slate-400 whitespace-nowrap"><?= esc_html($s[1]); ?></dt>
                                    <dd class="text-xl font-black text-white leading-none"><?= esc_html(kmpr_fa_num($s[0])); ?><span class="text-secondary"><?= esc_html($s[2]); ?></span></dd>
                                </div>
                            <?php endforeach; ?>
                        </dl>
                    <?php endif; ?>
                    <?php get_template_part('template-parts/social-media', null, array(
                        'class'    => 'flex-wrap gap-2 justify-start',
                        'size'     => 'size-11',
                        'brand'    => true,
                        'sizeSvgX' => '18',
                        'sizeSvgY' => '18',
                    )); ?>
                </div>
            </section>

            <!-- Link columns (menu-driven) -->
            <div class="md:col-span-1 lg:col-span-4 lg:row-start-1 lg:row-span-2 grid grid-cols-2 gap-x-6 gap-y-8">
                <?php foreach ($kf_columns as $ci => $col) :
                    if (!$col['items']) {
                        continue;
                    }
                    $icons    = array_map(fn($it) => kmpr_nav_icon_for($it['url']), $col['items']);
                    $per_item = count(array_unique($icons)) > 1; // distinct icons per link, otherwise one icon on the heading
                    $nav_id   = 'kf-nav-' . $ci;
                    ?>
                    <nav class="min-w-0 <?= $ci === 2 ? 'col-span-2 sm:col-span-1' : ''; ?>" aria-labelledby="<?= esc_attr($nav_id); ?>">
                        <h2 id="<?= esc_attr($nav_id); ?>" class="flex items-center gap-2 text-sm font-black text-white">
                            <span class="grid place-items-center size-7 rounded-lg bg-secondary/10 text-secondary"><?= kmpr_nav_svg($per_item ? 'link' : $icons[0], 15); // phpcs:ignore ?></span>
                            <?= esc_html($col['title']); ?>
                        </h2>
                        <ul class="mt-3 <?= $ci === 2 ? 'grid grid-cols-2 sm:grid-cols-1 gap-x-4' : 'flex flex-col'; ?>">
                            <?php foreach ($col['items'] as $ii => $it) : ?>
                                <li class="min-w-0">
                                    <a href="<?= esc_url($it['url']); ?>" <?= $it['target'] ? 'target="' . esc_attr($it['target']) . '" rel="noopener"' : ''; ?> <?= $it['current'] ? 'aria-current="page"' : ''; ?>
                                       class="kf-link group flex items-center gap-2 min-h-10 text-sm font-medium <?= $it['current'] ? 'text-secondary' : 'text-slate-300 hover:text-white'; ?> transition-colors"
                                       title="<?= esc_attr($it['title']); ?>">
                                        <?php if ($per_item) : ?>
                                            <span class="text-slate-500 group-hover:text-secondary transition-colors"><?= kmpr_nav_svg($icons[$ii], 16); // phpcs:ignore ?></span>
                                        <?php else : ?>
                                            <span class="size-1.5 shrink-0 rounded-full bg-slate-600 group-hover:bg-secondary group-hover:scale-125 transition" aria-hidden="true"></span>
                                        <?php endif; ?>
                                        <span class="truncate"><?= esc_html(trim(explode('|', $it['title'])[0])); ?></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php if ($col['all'] && $col['all'][0]) : ?>
                            <a href="<?= esc_url($col['all'][0]); ?>" class="group mt-1 inline-flex items-center gap-1.5 min-h-10 text-xs font-bold text-secondary hover:text-amber-400 transition-colors">
                                <?= esc_html($col['all'][1]); ?><?= $kf_arrow; // phpcs:ignore ?>
                            </a>
                        <?php endif; ?>
                    </nav>
                <?php endforeach; ?>
            </div>

            <!-- Latest posts (+ game center below / beside) -->
                <?php if ($kf_latest) : ?>
                    <section class="min-w-0 md:col-span-1 lg:col-span-4 lg:row-start-1 lg:row-span-2" aria-labelledby="kf-latest-title">
                        <h2 id="kf-latest-title" class="flex items-center gap-2 text-sm font-black text-white">
                            <span class="grid place-items-center size-7 rounded-lg bg-secondary/10 text-secondary"><?= kmpr_nav_svg('blog', 15); // phpcs:ignore ?></span>
                            <?= esc_html(kmpr_field('footer_posts_title', null, 'option')); ?>
                        </h2>
                        <ul class="mt-3 flex flex-col gap-1">
                            <?php foreach ($kf_latest as $kf_i => $p) :
                                $thumb = get_the_post_thumbnail($p, 'thumbnail', array('class' => 'size-full object-cover', 'loading' => 'lazy', 'decoding' => 'async', 'alt' => esc_attr(get_the_title($p)), 'width' => 48, 'height' => 48));
                                ?>
                                <li class="<?= $kf_i > 1 ? 'max-md:hidden' : ''; ?>">
                                    <a href="<?= esc_url(get_permalink($p)); ?>" class="group flex items-center gap-3 rounded-2xl p-1.5 -mx-1.5 hover:bg-white/[.04] transition-colors">
                                        <span class="grid place-items-center size-10 shrink-0 rounded-xl border border-secondary/25 bg-secondary/10 text-sm font-black text-secondary tabular-nums group-hover:bg-secondary group-hover:text-slate-950 transition-colors" aria-hidden="true"><?= esc_html(kmpr_fa_num($kf_i + 1)); ?></span>
                                        <span class="min-w-0 flex flex-col gap-1">
                                            <span class="text-[13px] font-bold leading-6 text-slate-200 group-hover:text-secondary transition-colors line-clamp-2"><?= esc_html(get_the_title($p)); ?></span>
                                            <time class="text-[11px] text-slate-400" datetime="<?= esc_attr(get_the_date('c', $p)); ?>"><?= esc_html(shamsi_date('j F Y', get_post_time('U', true, $p))); ?></time>
                                        </span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php $kf_blog = get_option('page_for_posts') ? get_permalink(get_option('page_for_posts')) : home_url('/blog/'); ?>
                        <a href="<?= esc_url($kf_blog); ?>" class="group mt-1 inline-flex items-center gap-1.5 min-h-10 text-xs font-bold text-secondary hover:text-amber-400 transition-colors">همه مقالات<?= $kf_arrow; // phpcs:ignore ?></a>
                    </section>
                <?php endif; ?>

                <?php if ($kf_games_url && $kf_games_n) : ?>
                    <a href="<?= esc_url($kf_games_url); ?>" class="kf-game group relative isolate flex items-center gap-4 min-h-[7.5rem] overflow-hidden rounded-3xl border border-fuchsia-400/20 bg-slate-900 p-4 md:p-6 lg:p-5 self-start md:self-stretch lg:self-start lg:col-span-4 lg:col-start-1 lg:row-start-2 hover:border-fuchsia-400/45 transition-colors" aria-label="<?= esc_attr(kmpr_field('footer_games_title', null, 'option') . ' — ورود به گیم‌سنتر (' . kmpr_fa_num($kf_games_n) . ' بازی)'); ?>">
                        <span class="kf-game__pad relative grid place-items-center size-16 shrink-0 rounded-2xl bg-fuchsia-500/10 text-fuchsia-300" aria-hidden="true">
                            <svg width="44" height="44" class="shrink-0" viewBox="0 0 48 48" fill="none">
                                <path d="M15 14h18a10 10 0 0 1 9.8 12l-1.5 7.4a4.6 4.6 0 0 1-8 2.1L30 32H18l-3.3 3.5a4.6 4.6 0 0 1-8-2.1L5.2 26A10 10 0 0 1 15 14Z" fill="currentColor" fill-opacity=".14" stroke="currentColor" stroke-width="2"/>
                                <path d="M15 20v8M11 24h8" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/>
                                <circle class="kf-game__btn" cx="32" cy="21.5" r="2.2" fill="#fbbf24"/>
                                <circle class="kf-game__btn kf-game__btn--2" cx="36.5" cy="26" r="2.2" fill="#f0abfc"/>
                            </svg>
                        </span>
                        <span class="min-w-0 flex-1 flex flex-col gap-1 me-28 sm:me-32">
                            <span class="text-[11px] font-black text-fuchsia-300">گیم‌سنتر · <?= esc_html(kmpr_fa_num($kf_games_n)); ?> بازی</span>
                            <span class="text-base font-black text-white"><?= esc_html(kmpr_field('footer_games_title', null, 'option')); ?></span>
                            <span class="text-xs leading-5 text-slate-300"><?= esc_html(kmpr_field('footer_games_text', null, 'option')); ?></span>
                            <span class="inline-flex items-center gap-1.5 mt-1.5 text-xs font-bold text-fuchsia-300">ورود به گیم‌سنتر<?= $kf_arrow; // phpcs:ignore ?></span>
                        </span>
                        <?php if ($kf_game_covers) : ?>
                            <span class="kf-game__art pointer-events-none absolute inset-y-0 end-0 -z-10 w-32 sm:w-36 overflow-hidden" aria-hidden="true">
                                <img src="<?= esc_url($kf_game_covers[0]['src']); ?>" alt="<?= esc_attr($kf_game_covers[0]['alt']); ?>" width="768" height="432" loading="lazy" decoding="async"
                                     class="absolute top-0 left-0 h-full w-auto max-w-none opacity-85 transition duration-500 group-hover:scale-105 group-hover:opacity-100 motion-reduce:transition-none origin-left">
                            </span>
                        <?php endif; ?>
                    </a>
                <?php endif; ?>
        </div>

        <!-- 3. Contact strip -->
        <section class="border-y border-white/[.06] py-5" aria-label="راه‌های ارتباطی">
            <ul class="grid grid-cols-2 md:flex md:flex-wrap md:items-center gap-2.5 md:gap-3">
                <?php if ($kf_phone) : ?>
                    <li class="col-span-2 min-[420px]:col-span-1">
                        <a href="tel:<?= esc_attr(preg_replace('/[^\d+]/', '', strtr($kf_phone, array('۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9')))); ?>" class="kf-chip group">
                            <span class="kf-chip__ico bg-secondary/10 text-secondary"><svg width="16" height="16" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2Z"/></svg></span>
                            <span class="min-w-0"><span class="sr-only">تماس: </span><span dir="ltr" class="font-bold text-slate-100"><?= esc_html(kmpr_fa_num($kf_phone_lbl)); ?></span></span>
                        </a>
                    </li>
                <?php endif; ?>
                <?php if ($kf_email) : ?>
                    <li class="col-span-2 min-[420px]:col-span-1 min-w-0">
                        <a href="mailto:<?= esc_attr($kf_email); ?>" class="kf-chip group">
                            <span class="kf-chip__ico bg-secondary/10 text-secondary"><svg width="16" height="16" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg></span>
                            <span class="min-w-0 truncate font-bold text-slate-100" dir="ltr"><?= esc_html($kf_email); ?></span>
                        </a>
                    </li>
                <?php endif; ?>
                <?php if ($kf_telegram) : ?>
                    <li>
                        <a href="<?= esc_url($kf_telegram); ?>" target="_blank" rel="noopener" class="kf-chip group hover:!border-sky-400/40">
                            <span class="kf-chip__ico bg-sky-400/10 text-sky-400"><?= $kf_tg_svg; // phpcs:ignore ?></span>
                            <span class="font-bold text-slate-100">تلگرام</span>
                        </a>
                    </li>
                <?php endif; ?>
                <?php if ($kf_whatsapp) : ?>
                    <li>
                        <a href="<?= esc_url($kf_whatsapp); ?>" target="_blank" rel="noopener" class="kf-chip group hover:!border-emerald-400/40">
                            <span class="kf-chip__ico bg-emerald-400/10 text-emerald-400"><?= $kf_wa_svg; // phpcs:ignore ?></span>
                            <span class="font-bold text-slate-100">واتساپ</span>
                        </a>
                    </li>
                <?php endif; ?>
                <?php if ($kf_address) : ?>
                    <li class="col-span-2 md:ms-auto">
                        <?php $kf_loc_tag = $kf_map ? 'a' : 'span'; ?>
                        <<?= $kf_loc_tag; ?> <?= $kf_map ? 'href="' . esc_url($kf_map) . '" target="_blank" rel="noopener"' : ''; ?> class="kf-chip <?= $kf_map ? 'group' : '!bg-transparent !border-transparent md:!px-0'; ?>">
                            <span class="kf-chip__ico bg-white/5 text-slate-300"><svg width="16" height="16" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg></span>
                            <span class="text-slate-300"><?= esc_html($kf_address); ?></span>
                        </<?= $kf_loc_tag; ?>>
                    </li>
                <?php endif; ?>
            </ul>
        </section>

        <!-- 4. Bottom bar -->
        <?php get_template_part('template-parts/layout/footer/copyright'); ?>
    </div>
</div>
