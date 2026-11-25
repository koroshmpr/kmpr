<?php
/**
 * «یک دست بازی» strip for non-game pages (404, end of articles, [kmpr_games]).
 * Rendered by kmpr_games_promo() in inc/games-promo.php — don't include directly.
 *
 * @var array $args ['ids','place','kicker','title','text','btn']
 */
$ids  = $args['ids'] ?? array();
$uid  = 'gp-' . wp_unique_id();
$all  = get_post_type_archive_link('game') ?: home_url('/games/');
$cols = min(4, max(2, count($ids)));
?>
<section class="gp gp--<?= esc_attr($args['place'] ?? 'post'); ?>" aria-labelledby="<?= esc_attr($uid); ?>">
    <div class="gp__fx" aria-hidden="true"></div>
    <div class="gp__head">
        <div class="min-w-0">
            <p class="gp__kicker">
                <svg width="16" height="16" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><rect x="2" y="6" width="20" height="12" rx="6"/><path d="M6 12h4M8 10v4"/><circle cx="15.5" cy="11" r=".9" fill="currentColor"/><circle cx="17.5" cy="13" r=".9" fill="currentColor"/></svg>
                <?= esc_html($args['kicker'] ?? ''); ?>
            </p>
            <h2 id="<?= esc_attr($uid); ?>" class="gp__title"><?= esc_html($args['title'] ?? ''); ?></h2>
            <?php if (!empty($args['text'])) : ?><p class="gp__text"><?= esc_html($args['text']); ?></p><?php endif; ?>
        </div>
        <a href="<?= esc_url($all); ?>" class="gp__all">
            <?= esc_html($args['btn'] ?? 'همه بازی‌ها'); ?>
            <svg width="16" height="16" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 6l-6 6 6 6"/></svg>
        </a>
    </div>
    <ul class="gp__grid" style="--gp-cols: <?= (int) $cols; ?>">
        <?php foreach ($ids as $id) :
            $g = kmpr_game_data($id, 'medium_large'); ?>
            <li class="gp__item">
                <a href="<?= esc_url($g['url']); ?>" class="gp-card" style="--accent: <?= esc_attr($g['accent_hex']); ?>">
                    <span class="gp-card__art">
                        <?php if ($g['cover']) : ?>
                            <img src="<?= esc_url($g['cover']['url']); ?>" width="<?= (int) $g['cover']['width']; ?>" height="<?= (int) $g['cover']['height']; ?>" alt="<?= esc_attr($g['cover']['alt']); ?>" loading="lazy" decoding="async">
                        <?php else : ?>
                            <span class="gp-card__icon" aria-hidden="true"><?= kmpr_game_icon_svg($g['engine']); // phpcs:ignore ?></span>
                        <?php endif; ?>
                    </span>
                    <span class="gp-card__info">
                        <span class="gp-card__title"><?= esc_html($g['title']); ?></span>
                        <span class="gp-card__meta"><?= esc_html(trim(($g['genre'] ? $g['genre'] . ' · ' : '') . 'سطح ' . $g['diff_label'])); ?></span>
                    </span>
                    <span class="gp-card__play" aria-hidden="true"><svg width="16" height="16" class="shrink-0" viewBox="0 0 24 24" fill="currentColor"><path d="M7 5.5v13a1 1 0 0 0 1.5.86l11-6.5a1 1 0 0 0 0-1.72l-11-6.5A1 1 0 0 0 7 5.5Z"/></svg></span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</section>
