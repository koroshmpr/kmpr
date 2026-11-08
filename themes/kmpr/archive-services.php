<?php
/**
 * Template Name: Services Archive - Personal Brand: Korosh Mpr
 *
 * Renders /services/ (post type archive). The legacy page "خدمات" (slug services) also points at this
 * template, so the layout is identical whichever one WordPress resolves. All copy: «تنظیمات سایت › صفحه خدمات».
 */
get_header();

$services = new WP_Query(array(
    'post_type'           => 'services',
    'post_status'         => 'publish',
    'posts_per_page'      => -1,
    'orderby'             => array('menu_order' => 'ASC', 'date' => 'ASC'),
    'ignore_sticky_posts' => true,
    'no_found_rows'       => true,
));
$service_ids = wp_list_pluck($services->posts, 'ID');

$trust = kmpr_lines(kmpr_srv_opt('srvp_trust'));

$steps = array_values(array_filter((array) get_field('srvp_process_steps', 'option'), function ($r) {
    return is_array($r) && !empty($r['t']);
}));
$steps = $steps ?: array(
    array('t' => 'گفت‌وگوی اولیه', 'd' => 'در یک تماس کوتاه و رایگان، هدف، مخاطب و اولویت‌های شما را می‌شنوم و می‌گویم کدام خدمت واقعاً به کارتان می‌آید.', 'm' => 'روز ۱'),
    array('t' => 'پیشنهاد مکتوب', 'd' => 'دامنه کار، خروجی‌ها، زمان‌بندی مرحله‌ای و هزینه را مکتوب دریافت می‌کنید؛ بدون هزینه پنهان.', 'm' => 'روز ۲ تا ۳'),
    array('t' => 'اجرا با گزارش هفتگی', 'd' => 'کار را مرحله‌به‌مرحله جلو می‌برم و هر هفته لینک پیش‌نمایش و گزارش پیشرفت برایتان می‌فرستم.', 'm' => 'طبق برنامه'),
    array('t' => 'تحویل و همراهی', 'd' => 'تست نهایی، آموزش پنل، تحویل کامل دسترسی‌ها و پشتیبانی بعد از تحویل تا خیالتان راحت باشد.', 'm' => 'بعد از تحویل'),
);

$faq = array_values(array_filter((array) get_field('srvp_faq', 'option'), function ($r) {
    return is_array($r) && !empty($r['question']);
}));
if (!$faq) {
    foreach ($service_ids as $sid) {
        $faq = array_merge($faq, array_slice(kmpr_srv_rows('service_faq', $sid, 'question'), 0, 2));
    }
}

$proof_ids = kmpr_srv_portfolio_ids($service_ids, 6);
?>

<!-- Hero -->
<section class="relative isolate overflow-hidden pt-16 pb-20 lg:pt-24 lg:pb-28" aria-labelledby="services-hero-title">
    <div class="absolute inset-0 hero-grid -z-10" aria-hidden="true"></div>
    <div class="aurora w-[34rem] h-[34rem] -top-40 -start-40 bg-secondary/20 -z-10" aria-hidden="true"></div>
    <div class="aurora w-[28rem] h-[28rem] -bottom-40 end-0 bg-indigo-600/20 -z-10 [animation-delay:-6s]" aria-hidden="true"></div>

    <div class="container mx-auto px-4 sm:px-6">
        <nav aria-label="مسیر صفحه" class="mb-10" data-aos="fade-down">
            <ol class="flex flex-wrap items-center gap-2 text-xs font-bold text-slate-400">
                <li><a href="<?= esc_url(home_url('/')); ?>" class="hover:text-secondary transition-colors">خانه</a></li>
                <li aria-hidden="true">/</li>
                <li class="text-slate-200" aria-current="page">خدمات</li>
            </ol>
        </nav>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
            <div class="lg:col-span-7">
                <span class="inline-flex items-center gap-2.5 ps-2 pe-4 py-1.5 rounded-full bg-white/[0.04] border border-white/10 backdrop-blur-xl mb-7" data-aos="fade-up">
                    <span class="relative flex size-2.5 shrink-0">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full size-2.5 bg-emerald-500"></span>
                    </span>
                    <span class="text-xs font-bold text-slate-200"><?= esc_html(kmpr_srv_opt('srvp_badge')); ?></span>
                </span>
                <h1 id="services-hero-title" class="text-[2rem] leading-[1.4] sm:text-5xl sm:leading-[1.3] xl:text-6xl xl:leading-[1.25] font-black text-white tracking-tight" data-split>
                    <?= esc_html(kmpr_srv_opt('srvp_title')); ?>
                </h1>
                <p class="mt-6 max-w-[42rem] text-sm sm:text-base lg:text-lg text-slate-300 leading-loose" data-aos="fade-up" data-aos-delay="300">
                    <?= esc_html(kmpr_srv_opt('srvp_text')); ?>
                </p>
                <div class="mt-9 flex flex-col sm:flex-row items-stretch sm:items-center gap-3" data-aos="fade-up" data-aos-delay="450">
                    <a href="#smart-configurator" data-magnetic
                       class="group inline-flex items-center justify-center gap-2.5 min-h-11 px-8 py-3.5 rounded-full bg-secondary hover:bg-amber-500 text-slate-950 font-black text-sm sm:text-base whitespace-nowrap shadow-[0_10px_40px_-10px] shadow-secondary/60 transition-colors">
                        <?= esc_html(kmpr_srv_opt('srvp_btn_primary')); ?>
                        <svg width="18" height="18" class="shrink-0 group-hover:-translate-x-1 transition-transform" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.61l4.16 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.61 9.25h10.64A.75.75 0 0 1 17 10Z" clip-rule="evenodd"/></svg>
                    </a>
                    <a href="#services-list"
                       class="inline-flex items-center justify-center min-h-11 px-8 py-3.5 rounded-full bg-white/[0.04] hover:bg-white/[0.08] border border-white/15 hover:border-white/30 text-slate-100 font-bold text-sm sm:text-base whitespace-nowrap transition-colors">
                        <?= esc_html(kmpr_srv_opt('srvp_btn_secondary')); ?>
                    </a>
                </div>
                <?php if ($trust) : ?>
                    <ul class="mt-8 flex flex-wrap gap-x-6 gap-y-3" data-aos="fade-up" data-aos-delay="550">
                        <?php foreach ($trust as $line) : ?>
                            <li class="inline-flex items-center gap-2 text-xs sm:text-sm font-bold text-slate-300">
                                <svg width="16" height="16" class="shrink-0 text-emerald-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.7 4.15a.75.75 0 0 1 .15 1.05l-8 10.5a.75.75 0 0 1-1.13.08l-4.5-4.5a.75.75 0 1 1 1.06-1.06l3.9 3.9 7.47-9.82a.75.75 0 0 1 1.05-.15Z" clip-rule="evenodd"/></svg>
                                <?= esc_html($line); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <!-- Quick index of services -->
            <div class="lg:col-span-5" data-aos="fade-left" data-aos-delay="300">
                <div class="border-glow rounded-3xl">
                    <div class="rounded-3xl bg-slate-900/80 border border-white/10 backdrop-blur-xl p-3 sm:p-4">
                        <p class="px-3 pt-2 pb-3 text-xs font-black text-slate-400">دسترسی سریع به خدمات</p>
                        <ul class="grid grid-cols-3 gap-1.5 sm:gap-2">
                            <?php foreach ($services->posts as $i => $srv) : ?>
                                <li class="min-w-0">
                                    <a href="<?= esc_url(get_permalink($srv)); ?>" class="group h-full flex flex-col items-center justify-center gap-2 sm:gap-2.5 min-h-[5.75rem] sm:min-h-[6.5rem] px-1.5 sm:px-2 py-3 sm:py-3.5 rounded-2xl border border-white/[0.06] bg-white/[0.02] hover:bg-secondary/[0.08] hover:border-secondary/30 text-center transition-colors">
                                        <span class="size-10 shrink-0 rounded-xl bg-secondary/10 border border-secondary/25 text-secondary flex items-center justify-center group-hover:scale-105 transition-transform"><?= kmpr_service_icon($srv->ID, 20); ?></span>
                                        <span class="text-[11px] sm:text-[13px] font-black leading-[1.45] sm:leading-5 text-slate-100 group-hover:text-secondary transition-colors line-clamp-2"><?= esc_html(get_the_title($srv)); ?></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <div class="mt-3 grid grid-cols-3 gap-2 border-t border-white/10 pt-4 px-2 pb-1 text-center">
                            <div>
                                <span class="block text-2xl font-black text-white tabular-nums" data-count="<?= (int) kmpr_years_experience(); ?>" data-suffix="+"><?= esc_html(kmpr_fa_num(kmpr_years_experience())); ?>+</span>
                                <span class="block text-xs font-bold text-slate-400">سال تجربه</span>
                            </div>
                            <div>
                                <span class="block text-2xl font-black text-white tabular-nums" data-count="<?= (int) wp_count_posts('portfolio')->publish; ?>" data-suffix="+"><?= esc_html(kmpr_fa_num(wp_count_posts('portfolio')->publish)); ?>+</span>
                                <span class="block text-xs font-bold text-slate-400">پروژه تحویل‌شده</span>
                            </div>
                            <div>
                                <span class="block text-2xl font-black text-white">۱ نفر</span>
                                <span class="block text-xs font-bold text-slate-400">بدون واسطه</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Services -->
<section id="services-list" class="relative py-20 lg:py-28 border-t border-white/10 scroll-mt-24" aria-labelledby="services-list-title">
    <div class="container mx-auto px-4 sm:px-6">
        <?php kmpr_srv_head(kmpr_srv_opt('srvp_list_kicker'), kmpr_srv_opt('srvp_list_title'), kmpr_srv_opt('srvp_list_text'), 'services-list-title'); ?>
        <?php if ($services->have_posts()) : ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 lg:gap-6" data-stagger="90">
                <?php $i = 0;
                while ($services->have_posts()) : $services->the_post(); ?>
                    <div data-aos="fade-up">
                        <?php get_template_part('template-parts/services/services-card', null, array('index' => $i, 'request' => true)); ?>
                    </div>
                <?php $i++;
                endwhile; ?>
            </div>
        <?php else : ?>
            <p class="py-16 text-center font-bold text-slate-300">هنوز خدمتی ثبت نشده است.</p>
        <?php endif;
        wp_reset_postdata(); ?>
    </div>
</section>

<?php
get_template_part('template-parts/services/steps', null, array(
    'steps'  => $steps,
    'kicker' => kmpr_srv_opt('srvp_process_kicker'),
    'title'  => kmpr_srv_opt('srvp_process_title'),
    'text'   => kmpr_srv_opt('srvp_process_text'),
    'id'     => 'services-process-title',
));

get_template_part('template-parts/services/proof', null, array(
    'ids'    => $proof_ids,
    'kicker' => kmpr_srv_opt('srvp_proof_kicker'),
    'title'  => kmpr_srv_opt('srvp_proof_title'),
    'id'     => 'services-proof-title',
));

get_template_part('template-parts/services/faq', null, array(
    'items'  => $faq,
    'kicker' => kmpr_srv_opt('srvp_faq_kicker'),
    'title'  => kmpr_srv_opt('srvp_faq_title'),
    'id'     => 'services-faq-title',
    'schema' => true,
));

get_template_part('template-parts/services/smart-configurator');
get_template_part('template-parts/services/pick-script');

get_footer();
