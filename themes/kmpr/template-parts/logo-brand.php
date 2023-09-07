<?php
$sizeLogo = isset($args['sizeLogo']) ? $args['sizeLogo'] : ' '; // set the default value of $size to 30 if it's not set
 if (get_field('logo_type', 'option') == 'image') { ?>
    <a class="logo-brand <?= $sizeLogo;?>" href="/" aria-label="logo">
        <img width="100" height="37" src="<?= get_field('brand_logo_img', 'option')['url']; ?>"
             alt="<?= get_field('brand_logo_img', 'option')['title']; ?>">
    </a>
<?php }
if (get_field('logo_type', 'option') == 'svg') { ?>
    <a class="logo-brand <?= $sizeLogo;?>" href="/" aria-label="logo">
        <span><?= get_field('brand_logo', 'option'); ?></span>
    </a>
<?php } ?>