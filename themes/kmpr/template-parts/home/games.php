<?php
/**
 * Home — Game Center teaser (arcade strip). Copy in «صفحه اصلی › بازی‌ها».
 * Pure HTML/CSS: the games JS bundle is NOT loaded on the home page.
 */
if (!post_type_exists('game') || kmpr_field('home_games_hide', false)) {
    return;
}
$ids = array_filter(array_map('intval', (array) kmpr_field('home_games_items', array())));
if (!$ids) {
    $all      = get_posts(array('post_type' => 'game', 'post_status' => 'publish', 'posts_per_page' => 20, 'orderby' => array('menu_order' => 'ASC', 'date' => 'DESC'), 'fields' => 'ids', 'no_found_rows' => true));
    $featured = array_values(array_filter($all, fn($id) => kmpr_game_meta($id, 'game_is_featured', false)));
    $ids      = array_slice(array_values(array_unique(array_merge($featured, $all))), 0, 4);
}
if (!$ids) {
    return;
}
$games_url = get_post_type_archive_link('game') ?: home_url('/games/');
$count     = (int) wp_count_posts('game')->publish;
?>
<section class="py-16 lg:py-24" aria-labelledby="home-games-title">
    <div class="container mx-auto px-4 sm:px-6">
        <div class="hg px-5 py-8 sm:p-10 lg:p-12" data-aos="fade-up">
            <div class="hg__dots" aria-hidden="true"></div>
            <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-6 mb-8">
                <div class="max-w-2xl">
                    <span class="inline-flex items-center gap-2 text-xs font-black text-secondary"><span class="w-6 h-px bg-secondary"></span><?= esc_html(kmpr_field('home_games_kicker')); ?></span>
                    <h2 id="home-games-title" class="mt-3 text-3xl sm:text-4xl lg:text-5xl font-black text-white leading-snug"><?= esc_html(kmpr_field('home_games_title')); ?></h2>
                    <p class="mt-4 text-sm sm:text-base text-slate-300 leading-8"><?= esc_html(kmpr_field('home_games_text')); ?></p>
                </div>
                <div class="flex flex-col sm:flex-row lg:flex-col items-stretch sm:items-center lg:items-end gap-3 shrink-0">
                    <a href="<?= esc_url($games_url); ?>" class="inline-flex items-center justify-center gap-2.5 px-8 py-3.5 rounded-full bg-secondary hover:bg-amber-500 text-slate-950 font-black whitespace-nowrap shadow-[0_10px_40px_-10px] shadow-secondary/60 transition-colors" data-magnetic>
                        <svg width="20" height="20" class="shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 7h10a5 5 0 0 1 4.9 6l-.8 4a2.6 2.6 0 0 1-4.4 1.3L14.8 16H9.2l-1.9 2.3A2.6 2.6 0 0 1 2.9 17l-.8-4A5 5 0 0 1 7 7Zm0 3v1.25H5.75v1.5H7V14h1.5v-1.25h1.25v-1.5H8.5V10H7Zm9.25.25a1 1 0 1 0 0 2 1 1 0 0 0 0-2Zm2 2a1 1 0 1 0 0 2 1 1 0 0 0 0-2Z"/></svg>
                        <?= esc_html(kmpr_field('home_games_btn')); ?>
                    </a>
                    <span class="text-sm text-slate-300 text-center lg:text-end"><?= esc_html(kmpr_fa_num($count)); ?> بازی رایگان · بدون نصب · آنلاین با دوستان</span>
                </div>
            </div>
            <ul class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4" data-stagger="90">
                <?php foreach ($ids as $id) {
                    get_template_part('template-parts/games/tile', null, array('id' => $id, 'class' => 'min-w-0', 'best' => false));
                } ?>
            </ul>
        </div>
    </div>
</section>
