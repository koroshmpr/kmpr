<?php
/**
 * Single service — conversion-focused landing page.
 * Fields: «اطلاعات خدمت» (per service) + labels in «تنظیمات سایت › صفحه خدمات».
 */
get_header();

while (have_posts()) :
    the_post();
    $post_id      = get_the_ID();
    $title        = get_the_title();
    $badge        = get_field('services_badge');
    $desc         = get_field('services_description') ?: wp_trim_words(get_the_excerpt(), 30);
    $price_raw    = get_field('starting_price');
    $price        = $price_raw ?: kmpr_srv_opt('srvs_price_fallback');
    $duration     = get_field('duration');
    $type         = get_field('request_type');
    $deliverables = kmpr_srv_rows('deliverables');
    $audience     = kmpr_lines(get_field('audience'));
    $steps        = kmpr_srv_rows('service_steps', null, 't');
    $faq          = kmpr_srv_rows('service_faq', null, 'question');
    $plans        = kmpr_srv_on(get_field('Proposal_switch')) ? kmpr_srv_rows('proposal', null, 'proposal_title') : array();
    $proof_ids    = kmpr_srv_portfolio_ids($post_id, 6);
    $archive_url  = get_post_type_archive_link('services') ?: home_url('/services/');
    $pick         = 'data-srv-pick data-type="' . esc_attr($type) . '" data-service="' . esc_attr($title) . '"';
    $permalink    = get_permalink();

    /* Service JSON-LD (same @id as the Yoast graph node so they merge) */
    $service_schema = array(
        '@context'    => 'https://schema.org',
        '@type'       => 'Service',
        '@id'         => $permalink . '#service',
        'name'        => $title,
        'url'         => $permalink,
        'description' => wp_strip_all_tags($desc),
        'serviceType' => $title,
        'inLanguage'  => 'fa-IR',
        'areaServed'  => array('@type' => 'Country', 'name' => 'Iran'),
        'provider'    => array(
            '@type' => 'Person',
            '@id'   => untrailingslashit(home_url('/')) . '/#person',
            'name'  => 'کوروش محمدپور',
            'url'   => home_url('/'),
        ),
    );
    if (has_post_thumbnail()) {
        $service_schema['image'] = get_the_post_thumbnail_url($post_id, 'full');
    }
    if ($deliverables) {
        $service_schema['hasOfferCatalog'] = array(
            '@type'           => 'OfferCatalog',
            'name'            => 'خروجی‌های ' . $title,
            'itemListElement' => array_map(function ($d) {
                return array('@type' => 'Offer', 'itemOffered' => array('@type' => 'Service', 'name' => wp_strip_all_tags($d['title']), 'description' => wp_strip_all_tags($d['desc'] ?? '')));
            }, $deliverables),
        );
    }
    ?>

    <article id="service-<?= (int) $post_id; ?>" <?php post_class('relative'); ?>>
        <!-- Hero -->
        <header class="relative isolate overflow-hidden pt-14 pb-16 lg:pt-20 lg:pb-24">
            <div class="absolute inset-0 hero-grid -z-10" aria-hidden="true"></div>
            <div class="aurora w-[34rem] h-[34rem] -top-40 -start-40 bg-secondary/20 -z-10" aria-hidden="true"></div>
            <div class="aurora w-[26rem] h-[26rem] -bottom-40 end-0 bg-indigo-600/20 -z-10 [animation-delay:-6s]" aria-hidden="true"></div>

            <div class="container mx-auto px-4 sm:px-6">
                <nav aria-label="مسیر صفحه" class="mb-10" data-aos="fade-down">
                    <ol class="flex flex-wrap items-center gap-2 text-xs font-bold text-slate-400">
                        <li><a href="<?= esc_url(home_url('/')); ?>" class="hover:text-secondary transition-colors">خانه</a></li>
                        <li aria-hidden="true">/</li>
                        <li><a href="<?= esc_url($archive_url); ?>" class="hover:text-secondary transition-colors">خدمات</a></li>
                        <li aria-hidden="true">/</li>
                        <li class="text-slate-200" aria-current="page"><?= esc_html($title); ?></li>
                    </ol>
                </nav>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                    <div class="lg:col-span-7">
                        <div class="flex flex-wrap items-center gap-3 mb-6" data-aos="fade-up">
                            <span class="size-14 rounded-2xl bg-secondary/10 border border-secondary/25 text-secondary flex items-center justify-center shrink-0"><?= kmpr_service_icon($post_id, 28); ?></span>
                            <?php if ($badge) : ?>
                                <span class="px-3 py-1.5 rounded-full bg-secondary/15 text-secondary text-xs font-black"><?= esc_html($badge); ?></span>
                            <?php endif; ?>
                        </div>
                        <h1 class="text-[2.1rem] leading-[1.35] sm:text-5xl sm:leading-[1.3] xl:text-6xl xl:leading-[1.25] font-black text-white tracking-tight" data-split><?= esc_html($title); ?></h1>
                        <p class="mt-6 max-w-[42rem] text-base lg:text-lg text-slate-300 leading-loose" data-aos="fade-up" data-aos-delay="250"><?= esc_html($desc); ?></p>
                        <div class="mt-9 flex flex-col sm:flex-row items-stretch sm:items-center gap-3" data-aos="fade-up" data-aos-delay="400">
                            <a href="#smart-configurator" <?= $pick; ?> data-magnetic
                               class="group inline-flex items-center justify-center gap-2.5 min-h-11 px-8 py-3.5 rounded-full bg-secondary hover:bg-amber-500 text-slate-950 font-black text-sm sm:text-base whitespace-nowrap shadow-[0_10px_40px_-10px] shadow-secondary/60 transition-colors">
                                <?= esc_html(kmpr_srv_opt('srvs_cta')); ?>
                                <svg width="18" height="18" class="shrink-0 group-hover:-translate-x-1 transition-transform" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.61l4.16 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.61 9.25h10.64A.75.75 0 0 1 17 10Z" clip-rule="evenodd"/></svg>
                            </a>
                            <?php if ($tg = kmpr_contact_url('telegram')) : ?>
                                <a href="<?= esc_url($tg); ?>" target="_blank" rel="noopener"
                                   class="inline-flex items-center justify-center gap-2 min-h-11 px-8 py-3.5 rounded-full bg-white/[0.04] hover:bg-white/[0.08] border border-white/15 hover:border-white/30 text-slate-100 font-bold text-sm sm:text-base whitespace-nowrap transition-colors">
                                    <svg width="18" height="18" class="shrink-0 text-sky-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg>
                                    مشاوره سریع در تلگرام
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Fact card -->
                    <aside class="lg:col-span-5" data-aos="fade-left" data-aos-delay="300" aria-label="خلاصه خدمت">
                        <div class="border-glow rounded-3xl">
                            <div class="rounded-3xl bg-slate-900/80 border border-white/10 backdrop-blur-xl p-6 sm:p-8">
                                <dl class="grid grid-cols-2 gap-5">
                                    <div class="col-span-2 sm:col-span-1">
                                        <dt class="text-xs font-bold text-slate-400 mb-1.5">شروع هزینه</dt>
                                        <dd class="text-xl font-black text-white"><?= esc_html($price); ?></dd>
                                    </div>
                                    <div class="col-span-2 sm:col-span-1">
                                        <dt class="text-xs font-bold text-slate-400 mb-1.5">زمان تقریبی</dt>
                                        <dd class="text-xl font-black text-white"><?= esc_html($duration ?: 'بعد از جلسه اول'); ?></dd>
                                    </div>
                                </dl>
                                <?php if ($deliverables) : ?>
                                    <ul class="mt-6 pt-6 border-t border-white/10 space-y-3">
                                        <?php foreach (array_slice($deliverables, 0, 4) as $d) : ?>
                                            <li class="flex items-start gap-2.5 text-sm text-slate-200">
                                                <svg width="18" height="18" class="shrink-0 mt-0.5 text-secondary" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.7 4.15a.75.75 0 0 1 .15 1.05l-8 10.5a.75.75 0 0 1-1.13.08l-4.5-4.5a.75.75 0 1 1 1.06-1.06l3.9 3.9 7.47-9.82a.75.75 0 0 1 1.05-.15Z" clip-rule="evenodd"/></svg>
                                                <span><?= esc_html($d['title']); ?></span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                                <p class="mt-6 pt-5 border-t border-white/10 flex items-center gap-2 text-xs font-bold text-slate-300">
                                    <span class="size-2 rounded-full bg-emerald-400 shrink-0"></span>
                                    <?= esc_html(kmpr_field('response_time', null, 'option')); ?> · همکاری مستقیم با خودم
                                </p>
                            </div>
                        </div>
                    </aside>
                </div>
            </div>
        </header>

        <!-- Body content -->
        <?php if (trim(get_the_content())) : ?>
            <section class="relative py-16 lg:py-24 border-t border-white/10" aria-label="توضیحات خدمت">
                <div class="container mx-auto px-4 sm:px-6">
                    <div class="srv-prose prose prose-invert prose-slate prose-base lg:prose-lg max-w-[48rem] mx-auto" data-aos="fade-up">
                        <?php the_content(); ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <!-- Deliverables + audience -->
        <?php if ($deliverables || $audience) : ?>
            <section class="relative py-20 lg:py-28 border-t border-white/10" aria-labelledby="srv-deliver-title">
                <div class="container mx-auto px-4 sm:px-6">
                    <?php kmpr_srv_head('خروجی‌ها', kmpr_srv_opt('srvs_deliver_title'), '', 'srv-deliver-title'); ?>
                    <?php if ($deliverables) : ?>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 lg:gap-5" data-stagger="70">
                            <?php foreach ($deliverables as $i => $d) : ?>
                                <div class="spotlight p-6 sm:p-7 rounded-3xl bg-white/[0.025] border border-white/10 hover:border-secondary/40 transition-colors" data-aos="fade-up">
                                    <span class="text-sm font-black text-secondary"><?= esc_html(kmpr_fa_num(sprintf('%02d', $i + 1))); ?></span>
                                    <h3 class="mt-3 text-lg font-black text-white mb-2"><?= esc_html($d['title']); ?></h3>
                                    <?php if (!empty($d['desc'])) : ?>
                                        <p class="text-sm text-slate-300 leading-loose"><?= esc_html($d['desc']); ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($audience) : ?>
                        <div class="mt-12 lg:mt-16 grid grid-cols-1 lg:grid-cols-12 gap-8 p-6 sm:p-10 rounded-3xl bg-gradient-to-br from-secondary/10 via-white/[0.02] to-transparent border border-secondary/20" data-aos="fade-up">
                            <div class="lg:col-span-4">
                                <h3 class="text-2xl sm:text-3xl font-black text-white leading-snug"><?= esc_html(kmpr_srv_opt('srvs_audience_title')); ?></h3>
                                <a href="#smart-configurator" <?= $pick; ?> class="mt-6 inline-flex items-center justify-center min-h-11 px-6 py-3 rounded-full bg-secondary hover:bg-amber-500 text-slate-950 text-sm font-black whitespace-nowrap transition-colors">
                                    بله، همین را می‌خواهم
                                </a>
                            </div>
                            <ul class="lg:col-span-8 grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4">
                                <?php foreach ($audience as $line) : ?>
                                    <li class="flex items-start gap-3 text-sm sm:text-base text-slate-200 leading-relaxed">
                                        <span class="mt-0.5 size-6 shrink-0 rounded-full bg-secondary/20 text-secondary flex items-center justify-center">
                                            <svg width="14" height="14" class="shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.7 4.15a.75.75 0 0 1 .15 1.05l-8 10.5a.75.75 0 0 1-1.13.08l-4.5-4.5a.75.75 0 1 1 1.06-1.06l3.9 3.9 7.47-9.82a.75.75 0 0 1 1.05-.15Z" clip-rule="evenodd"/></svg>
                                        </span>
                                        <?= esc_html($line); ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php
        get_template_part('template-parts/services/steps', null, array(
            'steps'  => $steps,
            'kicker' => 'مراحل کار',
            'title'  => kmpr_srv_opt('srvs_steps_title'),
            'text'   => $duration ? 'زمان تقریبی کل پروژه: ' . $duration : '',
            'id'     => 'srv-steps-title',
        ));
        ?>

        <!-- Plans -->
        <?php if ($plans) : ?>
            <section class="relative py-20 lg:py-28 border-t border-white/10" aria-labelledby="srv-plans-title">
                <div class="container mx-auto px-4 sm:px-6">
                    <?php kmpr_srv_head('پلن‌ها', kmpr_srv_opt('srvs_plans_title'), '', 'srv-plans-title', true); ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 lg:gap-6 max-w-[72rem] mx-auto items-stretch" data-stagger="100">
                        <?php foreach ($plans as $plan) :
                            $hot  = kmpr_srv_on($plan['proposal_offer'] ?? '');
                            $rows = array_filter((array) ($plan['proposal_row'] ?? array()), function ($r) {
                                return !empty($r['Property_label']);
                            }); ?>
                            <div class="relative flex flex-col rounded-3xl p-7 sm:p-8 transition-colors <?= $hot ? 'bg-slate-900 border-2 border-secondary/60 shadow-[0_20px_60px_-20px] shadow-secondary/40 lg:-translate-y-3' : 'bg-white/[0.025] border border-white/10 hover:border-white/25'; ?>" data-aos="fade-up">
                                <?php if ($hot) : ?>
                                    <span class="absolute -top-3.5 start-7 px-3 py-1 rounded-full bg-secondary text-slate-950 text-xs font-black whitespace-nowrap">پیشنهاد من</span>
                                <?php endif; ?>
                                <h3 class="text-xl sm:text-2xl font-black <?= $hot ? 'text-secondary' : 'text-white'; ?>"><?= esc_html($plan['proposal_title']); ?></h3>
                                <?php if (!empty($plan['proposal_description'])) : ?>
                                    <p class="mt-2 text-sm text-slate-300 leading-relaxed"><?= esc_html($plan['proposal_description']); ?></p>
                                <?php endif; ?>
                                <ul class="mt-6 pt-6 border-t border-white/10 space-y-3 flex-1">
                                    <?php foreach ($rows as $row) : ?>
                                        <li class="flex items-start justify-between gap-3 text-sm">
                                            <span class="flex items-start gap-2.5 text-slate-200">
                                                <svg width="18" height="18" class="shrink-0 mt-0.5 text-secondary" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.7 4.15a.75.75 0 0 1 .15 1.05l-8 10.5a.75.75 0 0 1-1.13.08l-4.5-4.5a.75.75 0 1 1 1.06-1.06l3.9 3.9 7.47-9.82a.75.75 0 0 1 1.05-.15Z" clip-rule="evenodd"/></svg>
                                                <?= esc_html($row['Property_label']); ?>
                                            </span>
                                            <?php if (!empty($row['Property_value'])) : ?>
                                                <bdi class="font-black text-white whitespace-nowrap"><?= esc_html(kmpr_fa_num(trim($row['Property_value']))); ?></bdi>
                                            <?php endif; ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                                <a href="#smart-configurator" <?= $pick; ?> data-plan="<?= esc_attr($plan['proposal_title']); ?>"
                                   class="mt-8 inline-flex items-center justify-center w-full min-h-11 px-6 py-3 rounded-full text-sm font-black whitespace-nowrap transition-colors <?= $hot ? 'bg-secondary hover:bg-amber-500 text-slate-950' : 'bg-white/[0.05] hover:bg-secondary border border-white/10 hover:border-secondary text-white hover:text-slate-950'; ?>">
                                    انتخاب این پلن
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <p class="mt-10 text-center text-sm text-slate-400" data-aos="fade-up"><?= esc_html(kmpr_srv_opt('srvs_plans_note')); ?></p>
                </div>
            </section>
        <?php endif; ?>

        <?php
        get_template_part('template-parts/services/proof', null, array(
            'ids'    => $proof_ids,
            'kicker' => 'نمونه‌کارها',
            'title'  => kmpr_srv_opt('srvs_work_title'),
            'id'     => 'srv-proof-title',
        ));

        get_template_part('template-parts/services/faq', null, array(
            'items'  => $faq,
            'kicker' => 'سوالات متداول',
            'title'  => kmpr_srv_opt('srvs_faq_title'),
            'id'     => 'srv-faq-title',
            'schema' => true,
        ));
        ?>
        <script type="application/ld+json"><?= wp_json_encode($service_schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG); ?></script>
    </article>

    <!-- Other services -->
    <?php
    $others = new WP_Query(array(
        'post_type'      => 'services',
        'post_status'    => 'publish',
        'posts_per_page' => 3,
        'post__not_in'   => array($post_id),
        'orderby'        => array('menu_order' => 'ASC', 'date' => 'ASC'),
        'no_found_rows'  => true,
    ));
    if ($others->have_posts()) : ?>
        <section class="relative py-20 lg:py-28 border-t border-white/10" aria-labelledby="srv-other-title">
            <div class="container mx-auto px-4 sm:px-6">
                <?php kmpr_srv_head('خدمات دیگر', kmpr_srv_opt('srvs_other_title'), '', 'srv-other-title'); ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 lg:gap-6" data-stagger="90">
                    <?php while ($others->have_posts()) : $others->the_post(); ?>
                        <div data-aos="fade-up"><?php get_template_part('template-parts/services/services-card'); ?></div>
                    <?php endwhile; ?>
                </div>
            </div>
        </section>
    <?php endif;
    wp_reset_postdata();

    get_template_part('template-parts/services/smart-configurator', null, array('service' => $title, 'type' => $type));
    get_template_part('template-parts/services/pick-script');
endwhile;

get_footer();
