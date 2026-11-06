<?php
$sizeLogo = isset($args['sizeLogo']) ? $args['sizeLogo'] : 'h-8 w-auto flex items-center';
if (get_field('logo_type', 'option') == 'image') {
    $logo_img = get_field('brand_logo_img', 'option');
    if ($logo_img) : ?>
    <a class="logo-brand shrink-0 [&_img]:h-full [&_img]:w-auto [&_img]:object-contain <?= esc_attr($sizeLogo); ?>" href="<?= esc_url(home_url('/')); ?>" aria-label="<?= esc_attr(get_bloginfo('name')); ?>">
        <img width="100" height="37" src="<?= esc_url($logo_img['url']); ?>"
             alt="<?= esc_attr($logo_img['title'] ?: get_bloginfo('name')); ?>">
    </a>
<?php endif;
}
if (get_field('logo_type', 'option') == 'svg') { ?>
    <a class="logo-brand shrink-0 [&_svg]:h-full [&_svg]:w-auto <?= esc_attr($sizeLogo); ?>" href="<?= esc_url(home_url('/')); ?>" aria-label="<?= esc_attr(get_bloginfo('name')); ?>">
        <span class="flex items-center h-full"><?= get_field('brand_logo', 'option'); ?></span>
    </a>
<?php } ?>