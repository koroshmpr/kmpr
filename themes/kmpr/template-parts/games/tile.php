<?php
/**
 * Games — console-style tile (used in hub rails and the home teaser).
 *
 * @var array $args ['id' => post id, 'class' => extra <li> classes, 'tag' => 'li'|'div', 'best' => bool]
 */
$g      = kmpr_game_data($args['id'] ?? get_the_ID(), 'medium_large');
$online = !empty(kmpr_game_engines()[$g['engine']]['online']);
$tag    = ($args['tag'] ?? 'li') === 'div' ? 'div' : 'li';
$best   = $args['best'] ?? true;
?>
<<?= $tag; ?> class="<?= esc_attr($args['class'] ?? 'gc-rail__item'); ?>">
    <a href="<?= esc_url($g['url']); ?>" class="gc-tile group" style="--accent: <?= esc_attr($g['accent_hex']); ?>">
        <span class="gc-tile__media">
            <?php if ($g['cover']) : ?>
                <img src="<?= esc_url($g['cover']['url']); ?>" width="<?= (int) $g['cover']['width']; ?>" height="<?= (int) $g['cover']['height']; ?>" alt="<?= esc_attr($g['cover']['alt']); ?>" loading="lazy" decoding="async">
            <?php endif; ?>
        </span>
        <span class="gc-tile__shade" aria-hidden="true"></span>
        <span class="gc-tile__chips">
            <?php if ($online) : ?>
                <span class="gc-chip gc-chip--online"><span class="size-1.5 rounded-full bg-emerald-400 shrink-0"></span>آنلاین</span>
            <?php endif; ?>
            <?php if ($best) : ?>
                <span class="gc-chip gc-chip--best" data-best-wrap hidden><svg width="12" height="12" class="shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2l2.9 6.6 7.1.6-5.4 4.7 1.6 7L12 17.3 5.8 21l1.6-7L2 9.2l7.1-.6Z"/></svg><span data-game-best="<?= esc_attr($g['engine']); ?>"></span></span>
            <?php endif; ?>
        </span>
        <span class="gc-tile__body">
            <span class="gc-tile__genre"><?= esc_html($g['genre'] ?: 'بازی'); ?> · <?= esc_html($g['diff_label']); ?></span>
            <span class="gc-tile__title"><?= esc_html($g['title']); ?></span>
        </span>
        <span class="gc-tile__play" aria-hidden="true"><svg width="18" height="18" class="shrink-0" viewBox="0 0 24 24" fill="currentColor"><path d="M7 5.5v13a1 1 0 0 0 1.5.86l11-6.5a1 1 0 0 0 0-1.72l-11-6.5A1 1 0 0 0 7 5.5Z"/></svg></span>
    </a>
</<?= $tag; ?>>
