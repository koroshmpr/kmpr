<?php
/**
 * Brand logo.
 * logo_type (theme settings): default = built-in KMPR mark + wordmark (recommended) | svg = custom SVG code | image = uploaded image.
 *
 * $args['sizeLogo'] optional wrapper classes, $args['compact'] hides the subline.
 */
$size_logo = $args['sizeLogo'] ?? 'h-10 flex items-center';
$compact   = !empty($args['compact']);
$logo_type = kmpr_field('logo_type', 'default', 'option');
$name      = kmpr_field('brand_name', 'KMPR', 'option');
$name      = (mb_strpos($name, 'وبکو') !== false) ? 'KMPR' : $name; // legacy agency name guard
$subline   = kmpr_field('job_title', 'طراح و توسعه‌دهنده وب', 'option');
$home      = esc_url(home_url('/'));
$label     = esc_attr('کوروش محمدپور | ' . $name);

if ($logo_type === 'image' && ($logo_img = get_field('brand_logo_img', 'option'))) {
    $img_src = is_array($logo_img) ? ($logo_img['url'] ?? '') : (is_numeric($logo_img) ? wp_get_attachment_image_url($logo_img, 'full') : $logo_img); ?>
    <a class="logo-brand shrink-0 [&_img]:h-full [&_img]:w-auto [&_img]:object-contain <?= esc_attr($size_logo); ?>" href="<?= $home; ?>" aria-label="<?= $label; ?>">
        <img width="160" height="40" src="<?= esc_url($img_src); ?>" alt="<?= $label; ?>">
    </a>
<?php } elseif ($logo_type === 'svg' && ($brand_svg = get_field('brand_logo', 'option')) && trim($brand_svg)) { ?>
    <a class="logo-brand shrink-0 [&_svg]:h-full [&_svg]:w-auto <?= esc_attr($size_logo); ?>" href="<?= $home; ?>" aria-label="<?= $label; ?>">
        <span class="flex items-center h-full"><?= $brand_svg; // admin-provided SVG ?></span>
    </a>
<?php } else { ?>
    <a class="logo-brand group shrink-0 inline-flex items-center gap-2.5 lg:gap-3 <?= esc_attr($size_logo); ?>" href="<?= $home; ?>" aria-label="<?= $label; ?>" dir="ltr">
        <svg width="40" height="40" viewBox="0 0 40 40" class="shrink-0 size-9 lg:size-10 transition-transform duration-500 group-hover:rotate-[-8deg]" aria-hidden="true">
            <defs>
                <linearGradient id="kmpr-mark-amber" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#fbbf24"/><stop offset="1" stop-color="#d97706"/></linearGradient>
                <linearGradient id="kmpr-mark-edge" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#f59e0b" stop-opacity=".9"/><stop offset=".5" stop-color="#334155"/><stop offset="1" stop-color="#38bdf8" stop-opacity=".8"/></linearGradient>
            </defs>
            <rect x="1" y="1" width="38" height="38" rx="11" fill="#0b1224" stroke="url(#kmpr-mark-edge)" stroke-width="1.5"/>
            <path d="M14.5 13.5 9 20l5.5 6.5" fill="none" stroke="#38bdf8" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M19.5 11v18" fill="none" stroke="url(#kmpr-mark-amber)" stroke-width="2.8" stroke-linecap="round"/>
            <path d="m29.5 11.5-8 8.5 8 8.5" fill="none" stroke="url(#kmpr-mark-amber)" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <span class="flex flex-col leading-none">
            <span class="text-base lg:text-[1.15rem] font-black tracking-[0.12em] text-white group-hover:text-secondary transition-colors"><?= esc_html($name); ?><span class="text-secondary">.</span></span>
            <?php if (!$compact) : ?>
                <span class="mt-1 text-[10px] font-bold text-slate-400 leading-none whitespace-nowrap" dir="rtl"><?= esc_html($subline); ?></span>
            <?php endif; ?>
        </span>
    </a>
<?php } ?>
