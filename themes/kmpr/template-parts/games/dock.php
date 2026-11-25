<?php
/**
 * Game Center bottom tab bar (< lg): frosted-glass pill (same language as the site tab bar),
 * auto-hides while scrolling down, comes back on scroll up / near the end of the page.
 * On single game pages it only shows inside the installed app; hidden while a game is played (game-lock / game-focus).
 * The site's own tab bar is never rendered on game pages (header.php swaps the whole header).
 */
$games_url = get_post_type_archive_link('game') ?: home_url('/games/');
$on_hub    = is_post_type_archive('game');
$svg       = function ($d) {
    return '<svg width="22" height="22" class="gdock__ic shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $d . '</svg>';
};
$tabs = array(
    'games'   => array('بازی‌ها', '<rect x="3.5" y="3.5" width="7" height="7" rx="2.2"/><rect x="13.5" y="3.5" width="7" height="7" rx="2.2"/><rect x="3.5" y="13.5" width="7" height="7" rx="2.2"/><rect x="13.5" y="13.5" width="7" height="7" rx="2.2"/>'),
    'lobby'   => array('لابی', '<circle cx="9" cy="8" r="3"/><circle cx="17" cy="9" r="2.5"/><path d="M3 20a6 6 0 0 1 12 0M14 20a4.5 4.5 0 0 1 7.5-3.3"/>'),
    'ranks'   => array('رتبه‌ها', '<path d="M8 21h8M12 17v4M7 4h10v4a5 5 0 0 1-10 0V4Z"/><path d="M17 5h3v2a3 3 0 0 1-3 3M7 5H4v2a3 3 0 0 0 3 3"/>'),
    'profile' => array('پروفایل', '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>'),
);
?>
<nav class="gdock<?= is_singular('game') ? ' is-single' : ''; ?>" aria-label="ناوبری گیم‌سنتر (موبایل)" data-gdock>
    <?php foreach ($tabs as $k => $t) :
        $cur = $on_hub && $k === 'games'; ?>
        <a href="<?= esc_url($games_url); ?>#<?= esc_attr($k); ?>" class="gdock__tab" data-gh-tab="<?= esc_attr($k); ?>"<?= $cur ? ' aria-current="page"' : ''; ?>>
            <?= $svg($t[1]); // phpcs:ignore ?>
            <span class="gdock__t"><?= esc_html($t[0]); ?></span>
            <?php if ($k === 'lobby') : ?><b data-online-count hidden></b><?php endif; ?>
        </a>
    <?php endforeach; ?>
</nav>
<script>
(() => {
    // Auto-hide: down = hide, up / near the bottom / at the top = show. Transform only (no opacity: keeps the blur).
    const d = document.querySelector('[data-gdock]');
    if (!d) return;
    let last = window.scrollY, acc = 0, ticking = false;
    const set = (hide) => d.classList.toggle('is-away', hide);
    const run = () => {
        ticking = false;
        const y = window.scrollY, dy = y - last;
        last = y;
        const nearEnd = y + innerHeight > document.documentElement.scrollHeight - 140;
        if (y < 80 || nearEnd) { acc = 0; return set(false); }
        acc = (acc > 0) === (dy > 0) ? acc + dy : dy; // distance travelled in the current direction
        if (acc > 24) set(true);
        else if (acc < -12) set(false);
    };
    addEventListener('scroll', () => { if (!ticking) { ticking = true; requestAnimationFrame(run); } }, { passive: true });
    d.addEventListener('focusin', () => set(false));
})();
</script>
