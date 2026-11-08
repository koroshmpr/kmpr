<?php
/**
 * Games — leaderboard panel (server-rendered top 10; JS refreshes + week tab).
 *
 * @var array $args ['game' => kmpr_game_data()]
 */
$g = $args['game'] ?? null;
if (!$g) {
    return;
}
if (!kmpr_games_server_scores()) : ?>
<section class="rounded-3xl bg-white/[0.025] border border-white/10 p-5 sm:p-6" aria-label="ذخیره‌سازی رکوردها">
    <p class="text-sm text-slate-300 leading-7">رکوردهای این بازی فقط روی مرورگر خودتان ذخیره می‌شوند؛ جدول برترین‌ها فعلاً غیرفعال است.</p>
</section>
<?php return;
endif;
$rows = kmpr_game_leaderboard($g['id'], 'all', 10);
?>
<section class="rounded-3xl bg-white/[0.025] border border-white/10 p-5 sm:p-6" data-leaderboard data-game-id="<?= (int) $g['id']; ?>" aria-labelledby="lb-title-<?= (int) $g['id']; ?>" style="--accent: <?= esc_attr($g['accent_hex']); ?>">
    <div class="flex items-center justify-between gap-3 mb-4">
        <h2 id="lb-title-<?= (int) $g['id']; ?>" class="flex items-center gap-2 text-lg font-black text-white">
            <svg width="20" height="20" class="shrink-0 text-secondary" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 3h10v2h3v3a4 4 0 0 1-4 4h-.35A5 5 0 0 1 13 14.9V18h3v3H8v-3h3v-3.1A5 5 0 0 1 8.35 12H8a4 4 0 0 1-4-4V5h3V3Zm0 4H6v1a2 2 0 0 0 1 1.73V7Zm10 0v2.73A2 2 0 0 0 18 8V7h-1Z"/></svg>
            <?= esc_html(kmpr_game_opt('gs_lb_title')); ?>
        </h2>
    </div>
    <div class="game-lb__tabs mb-4" role="group" aria-label="بازه زمانی">
        <button type="button" class="game-lb__tab" data-period="all" aria-pressed="true">همه زمان‌ها</button>
        <button type="button" class="game-lb__tab" data-period="week" aria-pressed="false">این هفته</button>
    </div>
    <ol class="game-lb__list" data-lb-list>
        <?php if (!$rows) : ?>
            <li class="game-lb__empty">هنوز کسی رکورد نزده؛ اولین نفر باشید!</li>
        <?php endif; ?>
        <?php foreach ($rows as $i => $r) : ?>
            <li class="game-lb__row">
                <span class="game-lb__rank"><?= esc_html(array('🥇', '🥈', '🥉')[$i] ?? kmpr_fa_num($i + 1)); ?></span>
                <span class="game-lb__name"><?= esc_html($r['nickname']); ?><?= kmpr_game_badges_html($r['badges'] ?? array()); // phpcs:ignore -- escaped inside ?></span>
                <span class="game-lb__score"><?= esc_html(kmpr_game_num($r['score'])); ?></span>
            </li>
        <?php endforeach; ?>
    </ol>
    <p class="mt-4 text-xs text-slate-400 leading-relaxed"><?= esc_html(kmpr_game_opt('gs_privacy')); ?></p>
</section>
