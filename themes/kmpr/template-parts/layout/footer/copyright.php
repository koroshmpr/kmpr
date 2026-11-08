<?php
/**
 * Footer bottom bar: © (Jalali year), credit, utility links, trust badges (confirmation repeater).
 */
$kf_links = array_filter(array(
    array(home_url('/resume/'), 'رزومه آنلاین', ''),
    array(home_url('/llms.txt'), 'برای هوش مصنوعی', 'llms.txt'),
    array(defined('WPSEO_VERSION') ? home_url('/sitemap_index.xml') : home_url('/wp-sitemap.xml'), 'نقشه سایت', ''),
    array(get_privacy_policy_url(), 'حریم خصوصی', ''),
), fn($l) => !empty($l[0]));
$kf_badges = array();
if (function_exists('have_rows') && have_rows('confirmation', 'option')) {
    while (have_rows('confirmation', 'option')) {
        the_row();
        if ($b = trim((string) get_sub_field('conf_items'))) {
            $kf_badges[] = $b;
        }
    }
}
?>
<div class="flex flex-col lg:flex-row items-center justify-between gap-4 py-6 text-xs text-slate-400">
    <p class="text-center lg:text-start leading-6">
        <span>© <?= esc_html(shamsi_date('Y')); ?> <?= esc_html(kmpr_field('brand_name', 'KMPR', 'option')); ?> · تمامی حقوق محفوظ است.</span>
        <span class="mx-1.5 text-slate-700" aria-hidden="true">|</span>
        <a href="<?= esc_url(home_url('/about-us/')); ?>" class="font-bold text-slate-300 hover:text-secondary transition-colors"><?= esc_html(kmpr_field('footer_credit', null, 'option')); ?></a>
    </p>
    <nav aria-label="لینک‌های تکمیلی">
        <ul class="flex flex-wrap items-center justify-center gap-x-1 gap-y-0">
            <?php foreach ($kf_links as $l) : ?>
                <li>
                    <a href="<?= esc_url($l[0]); ?>" class="inline-flex items-center gap-1.5 min-h-10 px-2.5 rounded-full font-medium text-slate-400 hover:text-white hover:bg-white/5 transition-colors">
                        <?php if ($l[2]) : ?>
                            <svg width="13" height="13" class="shrink-0 text-secondary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3 9.5 9.5 3 12l6.5 2.5L12 21l2.5-6.5L21 12l-6.5-2.5Z"/></svg>
                        <?php endif; ?>
                        <?= esc_html($l[1]); ?>
                        <?php if ($l[2]) : ?><span class="text-slate-500" dir="ltr">(<?= esc_html($l[2]); ?>)</span><?php endif; ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>
    <?php if ($kf_badges) : ?>
        <div class="flex items-center gap-3 [&_img]:h-14 [&_img]:w-auto">
            <?php foreach ($kf_badges as $b) {
                echo wp_kses_post($b);
            } ?>
        </div>
    <?php endif; ?>
</div>
