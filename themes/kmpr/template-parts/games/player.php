<?php
/**
 * Games — player shell (HUD, stage, overlay start screen). Hydrated by javascript/games/shell.js.
 *
 * @var array $args ['game' => kmpr_game_data()]
 */
$g = $args['game'] ?? null;
if (!$g) {
    return;
}
$is_match = in_array($g['engine'], array('xo', 'rps15', 'chess', 'color-race'), true);
$cover    = $g['cover'];
?>
<div class="game-shell" data-game-shell data-game-id="<?= (int) $g['id']; ?>" data-engine="<?= esc_attr($g['engine']); ?>" data-url="<?= esc_url($g['url']); ?>" style="--accent: <?= esc_attr($g['accent_hex']); ?>">
    <div class="game-shell__frame" data-frame>
        <div class="game-shell__bar">
            <div class="game-shell__stats">
                <div class="hud-pill hud-pill--score"><span>امتیاز</span><b data-hud="score">۰</b></div>
                <div class="hud-pill"><span>رکورد شما</span><b data-hud="best">۰</b></div>
                <div class="hud-pill" data-hud-wrap="level" hidden><span data-hud="level-label">مرحله</span><b data-hud="level"></b></div>
                <div class="hud-pill hidden sm:flex" data-hud-wrap="lives" hidden><span>جان</span><b data-hud="lives"></b></div>
                <div class="hud-pill hidden md:flex" data-hud-wrap="extra" hidden><span data-hud="extra-label"></span><b data-hud="extra"></b></div>
            </div>
            <div class="game-shell__actions">
                <button type="button" class="game-icon-btn game-icon-btn--text" data-act="mode" hidden aria-label="تغییر حالت بازی">
                    <svg width="18" height="18" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
                    <span class="hidden md:inline">تغییر حالت</span>
                </button>
                <button type="button" class="game-icon-btn" data-act="pause" aria-label="توقف بازی" disabled>
                    <svg width="18" height="18" class="shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><rect x="6" y="5" width="4" height="14" rx="1"/><rect x="14" y="5" width="4" height="14" rx="1"/></svg>
                </button>
                <button type="button" class="game-icon-btn" data-act="restart" aria-label="شروع دوباره">
                    <svg width="18" height="18" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/></svg>
                </button>
                <button type="button" class="game-icon-btn" data-act="sound" aria-pressed="false" aria-label="روشن کردن صدا">
                    <svg width="18" height="18" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M11 5 6 9H3v6h3l5 4V5Z" fill="currentColor"/><path d="m22 9-6 6M16 9l6 6"/></svg>
                </button>
                <button type="button" class="game-icon-btn game-icon-btn--exit" data-act="exit" hidden aria-label="خروج از حالت بازی">
                    <svg width="18" height="18" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
                    <span>خروج</span>
                </button>
                <button type="button" class="game-icon-btn" data-act="fullscreen" aria-label="تمام صفحه">
                    <svg width="18" height="18" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5"/></svg>
                </button>
            </div>
        </div>
        <div class="game-shell__viewport">
            <div class="game-shell__stage" data-stage tabindex="-1" aria-label="<?= esc_attr('صفحه بازی ' . $g['title']); ?>"></div>
            <div class="game-shell__overlay" data-overlay>
                <div class="game-screen">
                    <?php if ($cover) : ?>
                        <img src="<?= esc_url($cover['url']); ?>" width="<?= (int) $cover['width']; ?>" height="<?= (int) $cover['height']; ?>" alt="<?= esc_attr($cover['alt']); ?>" class="game-screen__cover hidden sm:block" fetchpriority="high" decoding="async">
                    <?php endif; ?>
                    <p class="game-screen__kicker"><?= esc_html($g['genre'] ?: 'بازی'); ?> · <?= esc_html($g['diff_label']); ?></p>
                    <p class="game-screen__title"><?= esc_html($g['title']); ?></p>
                    <?php if ($g['tagline']) : ?>
                        <p class="game-screen__text"><?= esc_html($g['tagline']); ?></p>
                    <?php endif; ?>
                    <div class="game-screen__actions">
                        <button type="button" class="game-btn game-btn--primary game-btn--lg" data-act="play">
                            <svg width="20" height="20" class="shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 5.5v13a1 1 0 0 0 1.5.86l11-6.5a1 1 0 0 0 0-1.72l-11-6.5A1 1 0 0 0 7 5.5Z"/></svg>
                            <?= $is_match ? 'ورود به بازی' : 'شروع بازی'; ?>
                        </button>
                    </div>
                    <?php
                    $gstats = kmpr_games_stats()['games'][$g['id']] ?? null;
                    if ($gstats && $gstats['plays'] > 0) : ?>
                        <p class="game-screen__stats"><span><?= esc_html(kmpr_game_num($gstats['plays'])); ?> دست بازی‌شده</span><span><?= esc_html(kmpr_game_num($gstats['players'])); ?> بازیکن</span><?php if (!empty($gstats['top'])) : ?><span>قهرمان: <?= esc_html($gstats['top']['nickname']); ?></span><?php endif; ?></p>
                    <?php endif; ?>
                    <p class="game-screen__hint"><?= $is_match ? 'مقابل هوش مصنوعی، دو نفره یا آنلاین با دوستان' : 'صدا پیش‌فرض خاموش است · Esc برای توقف'; ?></p>
                    <div data-kmpr-slot="game-intro"></div>
                    <noscript><p class="game-screen__status" data-tone="warn">برای بازی، جاوااسکریپت مرورگر را فعال کنید.</p></noscript>
                </div>
            </div>
            <div class="game-shell__toasts" data-toasts aria-live="polite"></div>
        </div>
    </div>
</div>
