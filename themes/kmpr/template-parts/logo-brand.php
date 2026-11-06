<?php
/**
 * Dynamic Brand Logo with Software Architecture Monogram
 */
$sizeLogo = isset($args['sizeLogo']) ? $args['sizeLogo'] : 'h-9 w-auto flex items-center';
$logo_type = get_field('logo_type', 'option') ?: 'svg';

if ($logo_type == 'image' && ($logo_img = get_field('brand_logo_img', 'option'))) { 
    $img_src = is_array($logo_img) ? ($logo_img['url'] ?? '') : (is_numeric($logo_img) ? wp_get_attachment_image_url($logo_img, 'full') : $logo_img);
?>
    <a class="logo-brand shrink-0 [&_img]:h-full [&_img]:w-auto [&_img]:object-contain <?= esc_attr($sizeLogo); ?>" href="<?= esc_url(home_url('/')); ?>" aria-label="<?= esc_attr(get_bloginfo('name')); ?>">
        <img width="140" height="36" src="<?= esc_url($img_src ?: get_template_directory_uri() . '/public/images/kmpr-logo.svg'); ?>"
             alt="<?= esc_attr(get_bloginfo('name') ?: 'KMPR | کوروش محمدپور'); ?>">
    </a>
<?php } elseif ($logo_type == 'svg' && ($brand_svg = get_field('brand_logo', 'option')) && !empty(trim($brand_svg))) { ?>
    <a class="logo-brand shrink-0 [&_svg]:h-full [&_svg]:w-auto <?= esc_attr($sizeLogo); ?>" href="<?= esc_url(home_url('/')); ?>" aria-label="<?= esc_attr(get_bloginfo('name')); ?>">
        <span class="flex items-center h-full"><?= $brand_svg; ?></span>
    </a>
<?php } else { ?>
    <a class="logo-brand shrink-0 flex items-center gap-3.5 group <?= esc_attr($sizeLogo); ?>" href="<?= esc_url(home_url('/')); ?>" aria-label="کوروش محمدپور | KMPR">
        <span class="size-9 rounded-xl bg-slate-900/90 border border-secondary/35 flex items-center justify-center shadow-lg group-hover:border-secondary transition-all">
            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M7 8L3 12L7 16" stroke="#38bdf8" />
                <path d="M12 4V20" stroke="#f59e0b" />
                <path d="M12 12L17 7" stroke="#f59e0b" />
                <path d="M14 10.5L18 17" stroke="#f59e0b" />
                <path d="M19 9L22 12L19 15" stroke="#38bdf8" stroke-opacity="0.7" />
            </svg>
        </span>
        <div class="flex flex-col text-start leading-tight">
            <span class="font-black text-sm text-white tracking-wide group-hover:text-secondary transition-colors">کوروش محمدپور</span>
            <span class="text-[10px] font-mono text-slate-400 tracking-wider">SOFTWARE ARCHITECT</span>
        </div>
    </a>
<?php } ?>
