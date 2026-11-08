<?php
/**
 * Game Center bottom tab bar (< lg). On single game pages it only shows inside the installed app.
 */
$games_url = get_post_type_archive_link('game') ?: home_url('/games/');
$on_hub    = is_post_type_archive('game');
$svg       = function ($d) {
    return '<svg width="22" height="22" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $d . '</svg>';
};
?>
<nav class="gdock<?= is_singular('game') ? ' is-single' : ''; ?>" aria-label="ناوبری گیم‌سنتر (موبایل)">
    <a href="<?= esc_url($games_url); ?>"<?= $on_hub ? ' aria-current="page"' : ''; ?>><?= $svg('<rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/>'); // phpcs:ignore ?>هاب</a>
    <a href="<?= esc_url($games_url); ?>#lobby"><?= $svg('<circle cx="9" cy="8" r="3"/><circle cx="17" cy="9" r="2.5"/><path d="M3 20a6 6 0 0 1 12 0M14 20a4.5 4.5 0 0 1 7.5-3.3"/>'); // phpcs:ignore ?>آنلاین</a>
    <a href="<?= esc_url($games_url); ?>#leaders"><?= $svg('<path d="M8 21h8M12 17v4M7 4h10v4a5 5 0 0 1-10 0V4Z"/><path d="M17 5h3v2a3 3 0 0 1-3 3M7 5H4v2a3 3 0 0 0 3 3"/>'); // phpcs:ignore ?>جدول</a>
    <a href="<?= esc_url($games_url); ?>#profile"><?= $svg('<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>'); // phpcs:ignore ?>پروفایل</a>
    <a href="<?= esc_url(home_url('/')); ?>" class="gdock__site" data-external><?= $svg('<path d="M3 11 12 4l9 7"/><path d="M5 10v10h14V10"/>'); // phpcs:ignore ?>سایت</a>
</nav>
