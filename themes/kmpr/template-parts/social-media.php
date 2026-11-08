<?php
/**
 * Social links from «راه‌های ارتباطی و شبکه‌های اجتماعی».
 *
 * $args: class (ul classes), sizeSvgX/sizeSvgY (icon size),
 *        size (link size classes, default size-9), brand (true = each network's brand color on hover).
 */
$size  = $args['size'] ?? 'size-9';
$brand = !empty($args['brand']);
$nets  = array(
    // key => array( url, label, brand hover classes )
    'instagram' => array(get_field('instagram', 'option'), 'Instagram', 'hover:bg-gradient-to-tr hover:from-[#f58529] hover:via-[#dd2a7b] hover:to-[#8134af] hover:text-white'),
    'telegram'  => array(get_field('telegram', 'option'), 'Telegram', 'hover:bg-[#229ED9] hover:text-white'),
    'whatsapp'  => array(get_field('whatsapp', 'option'), 'WhatsApp', 'hover:bg-[#25D366] hover:text-slate-950'),
    'aparat'    => array(get_field('aparat', 'option'), 'Aparat', 'hover:bg-[#ED145B] hover:text-white'),
    'youtube'   => array(get_field('youtube', 'option'), 'YouTube', 'hover:bg-[#FF0000] hover:text-white'),
    'twitter'   => array(get_field('twitter', 'option'), 'X (Twitter)', 'hover:bg-white hover:text-slate-950'),
    'linkedin'  => array(kmpr_contact('linkedin'), 'LinkedIn', 'hover:bg-[#0A66C2] hover:text-white'),
    'github'    => array(get_field('github', 'option'), 'GitHub', 'hover:bg-white hover:text-slate-950'),
);
?>
<ul class="social-media flex items-center gap-2 m-0 <?= esc_attr($args['class'] ?? 'justify-center lg:justify-start'); ?>">
    <?php foreach ($nets as $key => $net) :
        if (!$net[0]) {
            continue;
        }
        $hover = $brand ? $net[2] . ' hover:-translate-y-0.5 focus-visible:-translate-y-0.5' : 'hover:bg-secondary hover:text-white';
        ?>
        <li>
            <a class="flex items-center justify-center <?= esc_attr($size); ?> rounded-full border border-white/10 bg-white/5 text-slate-300 <?= esc_attr($hover); ?> hover:border-transparent transition-all duration-300 motion-reduce:transition-none motion-reduce:hover:translate-y-0"
               href="<?= esc_url($net[0]); ?>"
               aria-label="<?= esc_attr($net[1]); ?>" target="_blank" rel="noopener me">
                <?php get_template_part('template-parts/svg/social/' . $key, null, $args); ?>
            </a>
        </li>
    <?php endforeach; ?>
</ul>
