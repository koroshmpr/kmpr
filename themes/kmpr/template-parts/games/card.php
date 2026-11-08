<?php
/**
 * Games — card for the hub grid.
 *
 * @var array $args ['id' => post id, 'stats' => kmpr_games_stats(), 'lazy' => bool, 'heading' => 'h3']
 */
$g     = kmpr_game_data($args['id'] ?? get_the_ID(), 'medium_large');
$stats = $args['stats']['games'][$g['id']] ?? null;
$lazy  = $args['lazy'] ?? true;
$tag   = $args['heading'] ?? 'h3';
$is_online = !empty(kmpr_game_engines()[$g['engine']]['online']);
?>
<article class="group relative flex flex-col rounded-3xl bg-white/[0.025] border border-white/10 hover:border-secondary/40 overflow-hidden transition-[border-color,transform] duration-300 hover:-translate-y-1" style="--accent: <?= esc_attr($g['accent_hex']); ?>" data-aos="fade-up">
    <a href="<?= esc_url($g['url']); ?>" class="relative block aspect-[16/9] overflow-hidden bg-slate-900" tabindex="-1" aria-hidden="true">
        <?php if ($g['cover']) : ?>
            <img src="<?= esc_url($g['cover']['url']); ?>" width="<?= (int) $g['cover']['width']; ?>" height="<?= (int) $g['cover']['height']; ?>" alt="" <?= $lazy ? 'loading="lazy"' : ''; ?> decoding="async" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">
        <?php endif; ?>
        <span class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-transparent to-transparent"></span>
        <span class="absolute top-3 start-3 flex flex-wrap gap-1.5">
            <?php if ($g['genre']) : ?>
                <span class="px-2.5 py-1 rounded-full bg-slate-950/80 backdrop-blur border border-white/10 text-xs font-bold text-slate-100"><?= esc_html($g['genre']); ?></span>
            <?php endif; ?>
            <?php if ($is_online) : ?>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-500/15 backdrop-blur border border-emerald-400/30 text-xs font-bold text-emerald-300"><span class="size-1.5 rounded-full bg-emerald-400"></span>آنلاین</span>
            <?php endif; ?>
        </span>
    </a>
    <div class="flex flex-col flex-1 p-5 gap-3">
        <div class="flex items-start justify-between gap-3">
            <<?= tag_escape($tag); ?> class="text-lg font-black text-white leading-relaxed">
                <a href="<?= esc_url($g['url']); ?>" class="after:absolute after:inset-0 focus:outline-none focus-visible:underline"><?= esc_html($g['title']); ?></a>
            </<?= tag_escape($tag); ?>>
            <span class="shrink-0 flex gap-0.5 mt-2" aria-label="<?= esc_attr('سختی: ' . $g['diff_label']); ?>" title="<?= esc_attr('سختی: ' . $g['diff_label']); ?>">
                <?php for ($i = 1; $i <= 3; $i++) : ?>
                    <span class="w-2 h-4 rounded-sm <?= $i <= $g['diff_level'] ? 'bg-secondary' : 'bg-white/10'; ?>"></span>
                <?php endfor; ?>
            </span>
        </div>
        <p class="text-sm text-slate-300 leading-7 line-clamp-2"><?= esc_html($g['tagline'] ?: wp_strip_all_tags($g['excerpt'])); ?></p>
        <dl class="mt-auto grid grid-cols-2 gap-2 pt-3 border-t border-white/10 text-xs">
            <div data-best-wrap>
                <dt class="text-slate-400 font-bold">رکورد شما</dt>
                <dd class="text-base font-black text-white tabular-nums" data-game-best="<?= esc_attr($g['engine']); ?>">—</dd>
            </div>
            <div class="text-end">
                <dt class="text-slate-400 font-bold">قهرمان</dt>
                <dd class="text-sm font-black text-amber-300 truncate">
                    <?php if ($stats && $stats['top']) : ?>
                        <?= esc_html($stats['top']['nickname']); ?> <span class="text-slate-300 font-bold">(<?= esc_html(kmpr_game_num($stats['top']['score'])); ?>)</span>
                    <?php else : ?>
                        <span class="text-slate-400 font-bold">هنوز کسی نیست</span>
                    <?php endif; ?>
                </dd>
            </div>
        </dl>
    </div>
</article>
