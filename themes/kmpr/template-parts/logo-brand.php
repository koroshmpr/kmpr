<?php
/**
 * Dynamic Brand Logo with Elegant Personal Monogram Fallback
 */
$sizeLogo = isset($args['sizeLogo']) ? $args['sizeLogo'] : 'h-8 w-auto flex items-center';
$logo_type = get_field('logo_type', 'option');

if ($logo_type == 'image' && ($logo_img = get_field('brand_logo_img', 'option'))) { ?>
    <a class="logo-brand shrink-0 [&_img]:h-full [&_img]:w-auto [&_img]:object-contain <?= esc_attr($sizeLogo); ?>" href="<?= esc_url(home_url('/')); ?>" aria-label="<?= esc_attr(get_bloginfo('name')); ?>">
        <img width="100" height="37" src="<?= esc_url($logo_img['url']); ?>"
             alt="<?= esc_attr($logo_img['title'] ?: get_bloginfo('name')); ?>">
    </a>
<?php } elseif ($logo_type == 'svg' && ($brand_svg = get_field('brand_logo', 'option'))) { ?>
    <a class="logo-brand shrink-0 [&_svg]:h-full [&_svg]:w-auto <?= esc_attr($sizeLogo); ?>" href="<?= esc_url(home_url('/')); ?>" aria-label="<?= esc_attr(get_bloginfo('name')); ?>">
        <span class="flex items-center h-full"><?= $brand_svg; ?></span>
    </a>
<?php } else { ?>
    <a class="logo-brand shrink-0 flex items-center gap-3 <?= esc_attr($sizeLogo); ?>" href="<?= esc_url(home_url('/')); ?>" aria-label="کوروش محمدپور">
        <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-secondary/25 to-white/5 border border-secondary/40 flex items-center justify-center text-secondary font-black text-base shadow-sm">
            K
        </span>
        <span class="font-black text-base text-white tracking-wide">کوروش محمدپور</span>
    </a>
<?php } ?>
