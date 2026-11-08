<?php
/**
 * Client social links from the portfolio «social-media» group (instagram / telegram / whatsapp / aparat).
 * Args: post_id (int), class (string). Renders nothing when the client has no links.
 */
$post_id = $args['post_id'] ?? get_the_ID();
$group   = function_exists('get_field') ? get_field('social-media', $post_id) : array();
$map     = array(
    'portfolio-instagram' => array('instagram', 'اینستاگرام'),
    'portfolio-telegram'  => array('telegram', 'تلگرام'),
    'portfolio-whatsapp'  => array('whatsapp', 'واتساپ'),
    'portfolio-aparat'    => array('aparat', 'آپارات'),
);
$items = array();
foreach ($map as $key => $meta) {
    $value = is_array($group) ? ($group[$key] ?? null) : null;
    $url   = is_array($value) ? ($value['url'] ?? '') : (string) $value;
    if ($url) {
        $items[] = array('url' => $url, 'icon' => $meta[0], 'label' => $meta[1]);
    }
}
if (!$items) {
    return;
}
?>
<ul class="flex flex-wrap items-center gap-2 <?= esc_attr($args['class'] ?? ''); ?>">
    <?php foreach ($items as $item) : ?>
        <li>
            <a href="<?= esc_url($item['url']); ?>" target="_blank" rel="noopener noreferrer nofollow"
               aria-label="<?= esc_attr($item['label'] . ' کارفرما'); ?>"
               class="flex items-center justify-center size-11 rounded-full border border-white/10 bg-white/[0.04] text-slate-300 hover:bg-secondary hover:text-slate-950 hover:border-transparent transition-colors [&_svg]:shrink-0">
                <?php get_template_part('template-parts/svg/social/' . $item['icon'], null, array('sizeSvgX' => '18', 'sizeSvgY' => '18')); ?>
            </a>
        </li>
    <?php endforeach; ?>
</ul>
