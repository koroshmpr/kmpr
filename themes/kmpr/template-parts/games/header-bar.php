<?php
/**
 * Game Center app bar.
 * Desktop: «بازگشت به سایت» + wordmark + section nav + install / sound / player.
 * Mobile (< lg) and installed app (standalone): back button (previous screen or hub) + screen title;
 * «سایت اصلی» lives in the overflow menu (and in the mobile dock on hub pages).
 */
$games_url = get_post_type_archive_link('game') ?: home_url('/games/');
$on_hub    = is_post_type_archive('game');
if ($on_hub) {
    $screen = 'گیم‌سنتر';
} elseif (is_tax('game_category')) {
    $screen = single_term_title('', false);
} else {
    $screen = get_the_title();
}
$pad = '<svg width="20" height="20" class="shrink-0" viewBox="0 0 24 24" fill="currentColor"><path d="M7 7h10a5 5 0 0 1 4.9 6l-.8 4a2.6 2.6 0 0 1-4.4 1.3L14.8 16H9.2l-1.9 2.3A2.6 2.6 0 0 1 2.9 17l-.8-4A5 5 0 0 1 7 7Zm0 3v1.25H5.75v1.5H7V14h1.5v-1.25h1.25v-1.5H8.5V10H7Zm9.25.25a1 1 0 1 0 0 2 1 1 0 0 0 0-2Zm2 2a1 1 0 1 0 0 2 1 1 0 0 0 0-2Z"/></svg>';
?>
<header class="gh<?= $on_hub ? ' is-hub' : ''; ?><?= is_singular('game') ? ' is-single' : ''; ?>" data-gh>
    <div class="container mx-auto px-3 sm:px-6">
        <div class="gh__row">
            <a href="<?= esc_url(home_url('/')); ?>" class="gh__back gh__site" aria-label="بازگشت به سایت اصلی">
                <svg width="18" height="18" class="shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h10.64l-4.16-3.96a.75.75 0 1 1 1.04-1.08l5.5 5.25a.75.75 0 0 1 0 1.08l-5.5 5.25a.75.75 0 1 1-1.04-1.08l4.16-3.96H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd"/></svg>
                <span>بازگشت به سایت</span>
            </a>
            <button type="button" class="gh__icon gh__appback" data-gh-appback aria-label="بازگشت به صفحه قبل" data-home="<?= esc_url($games_url); ?>">
                <svg width="20" height="20" class="shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h10.64l-4.16-3.96a.75.75 0 1 1 1.04-1.08l5.5 5.25a.75.75 0 0 1 0 1.08l-5.5 5.25a.75.75 0 1 1-1.04-1.08l4.16-3.96H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd"/></svg>
            </button>
            <a href="<?= esc_url($games_url); ?>" class="gh__brand ms-1 sm:ms-2"<?= $on_hub ? ' aria-current="page"' : ''; ?>>
                <span class="gh__logo" aria-hidden="true"><?= $pad; // phpcs:ignore ?></span>
                <span class="gh__word">KMPR <b>Arcade</b></span>
                <span class="sr-only">گیم‌سنتر</span>
            </a>
            <p class="gh__title" aria-hidden="true"><?= esc_html($screen); ?></p>
            <nav class="gh__nav hidden lg:block ms-4" aria-label="ناوبری گیم‌سنتر">
                <ul class="flex items-center gap-1">
                    <li><a href="<?= esc_url($games_url); ?>"<?= $on_hub ? ' aria-current="page"' : ''; ?>>همه بازی‌ها</a></li>
                    <li><a href="<?= esc_url($games_url); ?>#rail-online-title">آنلاین</a></li>
                    <li><a href="<?= esc_url($games_url); ?>#lobby">لابی و برترین‌ها</a></li>
                    <li><a href="<?= esc_url($games_url); ?>#profile">پروفایل</a></li>
                </ul>
            </nav>
            <div class="ms-auto flex items-center gap-2">
                <button type="button" class="gh__install" data-gh-install hidden>
                    <svg width="16" height="16" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v12M7 10l5 5 5-5M5 21h14"/></svg>
                    <span>نصب اپ</span>
                </button>
                <button type="button" class="gh__icon" data-gh-sound aria-pressed="false" aria-label="روشن کردن صدای بازی‌ها">
                    <svg width="18" height="18" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M11 5 6 9H3v6h3l5 4V5Z" fill="currentColor"/><path d="m22 9-6 6M16 9l6 6"/></svg>
                </button>
                <a href="<?= esc_url($games_url); ?>#profile" class="gh__player" data-gh-player aria-label="پروفایل بازیکن">
                    <span class="gh__player-av" data-gh-avatar></span>
                    <span class="flex flex-col min-w-0 leading-tight">
                        <span class="gh__name hidden sm:block" data-gh-name>بازیکن مهمان</span>
                        <span class="gh__lvl" data-gh-level>سطح ۱</span>
                    </span>
                </a>
                <details class="gh__more" data-gh-more>
                    <summary class="gh__icon" aria-label="منوی بیشتر">
                        <svg width="18" height="18" class="shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="12" cy="5" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="12" cy="19" r="2"/></svg>
                    </summary>
                    <div class="gh__menu" role="menu">
                        <a role="menuitem" href="<?= esc_url(home_url('/')); ?>" data-external>
                            <svg width="18" height="18" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 11 12 4l9 7"/><path d="M5 10v10h14V10"/></svg>
                            سایت اصلی کوروش
                        </a>
                        <a role="menuitem" href="<?= esc_url($games_url); ?>">
                            <?= $pad; // phpcs:ignore ?>
                            خانه گیم‌سنتر
                        </a>
                        <a role="menuitem" href="<?= esc_url($games_url); ?>#leaders">
                            <svg width="18" height="18" class="shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 3h10v2h3v3a4 4 0 0 1-4 4h-.35A5 5 0 0 1 13 14.9V18h3v3H8v-3h3v-3.1A5 5 0 0 1 8.35 12H8a4 4 0 0 1-4-4V5h3V3Z"/></svg>
                            جدول برترین‌ها
                        </a>
                        <button type="button" role="menuitem" data-gh-install-menu hidden>
                            <svg width="18" height="18" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v12M7 10l5 5 5-5M5 21h14"/></svg>
                            نصب اپ گیم‌سنتر
                        </button>
                    </div>
                </details>
            </div>
        </div>
    </div>
</header>
