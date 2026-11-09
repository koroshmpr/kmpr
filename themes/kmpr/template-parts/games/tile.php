<?php
/**
 * Games — console-style tile (used in hub rails and the home teaser).
 *
 * @var array $args ['id' => post id, 'class' => extra <li> classes, 'tag' => 'li'|'div', 'best' => bool,
 *                   'context' => 'hub' (default, art-only overlay tile) | 'home' (card: art on top, full title below, one chip)]
 */
$g      = kmpr_game_data($args['id'] ?? get_the_ID(), 'medium_large');
$online = !empty(kmpr_game_engines()[$g['engine']]['online']);
$tag    = ($args['tag'] ?? 'li') === 'div' ? 'div' : 'li';
$best   = $args['best'] ?? true;

if (($args['context'] ?? 'hub') === 'home') : ?>
<<?= $tag; ?> class="<?= esc_attr($args['class'] ?? 'hg-rail__item'); ?>">
    <a href="<?= esc_url($g['url']); ?>" class="gc-tile gc-tile--home group" style="--accent: <?= esc_attr($g['accent_hex']); ?>">
        <span class="gc-tile__art">
            <span class="gc-tile__media">
                <?php if ($g['cover']) : ?>
                    <img src="<?= esc_url($g['cover']['url']); ?>" width="<?= (int) $g['cover']['width']; ?>" height="<?= (int) $g['cover']['height']; ?>" alt="<?= esc_attr($g['cover']['alt']); ?>" loading="lazy" decoding="async">
                <?php else : echo kmpr_game_art_html($g); // phpcs:ignore ?>
                <?php endif; ?>
            </span>
            <span class="gc-tile__shade" aria-hidden="true"></span>
            <?php if ($online) : ?>
                <span class="gc-chip gc-chip--online gc-tile__chip"><span class="size-1.5 rounded-full bg-emerald-400 shrink-0"></span>آنلاین</span>
            <?php elseif ($g['genre']) : ?>
                <span class="gc-chip gc-tile__chip"><?= esc_html($g['genre']); ?></span>
            <?php endif; ?>
        </span>
        <span class="gc-tile__info">
            <span class="gc-tile__title"><?= esc_html($g['title']); ?></span>
            <span class="gc-tile__genre">سطح <?= esc_html($g['diff_label']); ?></span>
        </span>
        <span class="gc-tile__play" aria-hidden="true"><svg width="16" height="16" class="shrink-0" viewBox="0 0 24 24" fill="currentColor"><path d="M7 5.5v13a1 1 0 0 0 1.5.86l11-6.5a1 1 0 0 0 0-1.72l-11-6.5A1 1 0 0 0 7 5.5Z"/></svg></span>
    </a>
</<?= $tag; ?>>
<?php return;
endif;
?>
<<?= $tag; ?> class="<?= esc_attr($args['class'] ?? 'gc-rail__item'); ?>">
    <a href="<?= esc_url($g['url']); ?>" class="gc-card group" style="--accent: <?= esc_attr($g['accent_hex']); ?>">
        <span class="gc-card__chips">
            <?php if ($online) : ?>
                <span class="gc-chip gc-chip--online"><span class="size-1.5 rounded-full bg-emerald-400 shrink-0"></span>آنلاین</span>
            <?php endif; ?>
            <?php if ($best) : ?>
                <span class="gc-chip gc-chip--best" data-best-wrap hidden>★ <span data-game-best="<?= esc_attr($g['engine']); ?>"></span></span>
            <?php endif; ?>
        </span>
        <span class="gc-card__icon"><?= kmpr_game_icon_svg($g['engine']); // phpcs:ignore ?></span>
        <span class="gc-card__title"><?= esc_html($g['title']); ?></span>
        <span class="gc-card__meta"><?= esc_html($g['genre'] ?: 'بازی'); ?> · <?= esc_html($g['diff_label']); ?></span>
        <span class="gc-card__play"><svg width="14" height="14" class="shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 5.5v13a1 1 0 0 0 1.5.86l11-6.5a1 1 0 0 0 0-1.72l-11-6.5A1 1 0 0 0 7 5.5Z"/></svg>بازی کن</span>
    </a>
</<?= $tag; ?>>
