<?php
/**
 * Single portfolio — full case study.
 * Hero → device showcase → overview + project facts → challenge/solution → results → services → prev/next → more → CTA.
 * Every block renders only from real field data (inc/acf-fields.php group «اطلاعات نمونه‌کار»).
 */
get_header();

while (have_posts()) :
    the_post();
    $post_id = get_the_ID();
    if (function_exists('track_post_views')) {
        track_post_views($post_id);
    }
    $pf          = kmpr_pf_data($post_id, 'full');
    $archive_url = get_post_type_archive_link('portfolio') ?: home_url('/portfolios/');
    $services    = array_filter((array) get_field('relation-service'));
    $shots       = $pf['shots'];
    if (!$shots && $pf['cover']) {
        $shots = array('laptop' => array('url' => $pf['cover']['url'], 'width' => (int) $pf['cover']['width'], 'height' => (int) $pf['cover']['height']));
    }
    $facts = array_filter(array(
        'کارفرما'   => $pf['client'],
        'سال اجرا'  => $pf['year'] ? kmpr_fa_num($pf['year']) : '',
        'نقش من'    => $pf['role'],
        'مدت اجرا'  => $pf['duration'],
        'نوع توسعه' => $pf['theme'],
        'صفحه‌ساز'  => $pf['builder'],
        'حوزه'      => $pf['term'] ? $pf['term']->name : '',
        'وب‌سایت'   => $pf['host'],
        'انتشار در سایت' => function_exists('shamsi_date') ? shamsi_date('F Y', (int) get_post_time('U', true)) : get_the_date(),
    ));
    $prev_post = get_previous_post();
    $next_post = get_next_post();
    $svg_kses  = array(
        'svg'      => array('xmlns' => true, 'viewbox' => true, 'width' => true, 'height' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true, 'class' => true, 'aria-hidden' => true),
        'path'     => array('d' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true, 'fill-rule' => true, 'clip-rule' => true, 'opacity' => true),
        'g'        => array('fill' => true, 'stroke' => true, 'clip-path' => true),
        'circle'   => array('cx' => true, 'cy' => true, 'r' => true, 'fill' => true, 'stroke' => true),
        'rect'     => array('x' => true, 'y' => true, 'width' => true, 'height' => true, 'rx' => true, 'fill' => true, 'stroke' => true),
        'line'     => array('x1' => true, 'y1' => true, 'x2' => true, 'y2' => true, 'stroke' => true),
        'polyline' => array('points' => true, 'fill' => true, 'stroke' => true),
        'polygon'  => array('points' => true, 'fill' => true),
    );
    $content = trim(get_the_content());
?>
<article class="bg-slate-950 text-white overflow-x-clip">

    <!-- Hero -->
    <header class="relative isolate pt-10 pb-14 lg:pt-14 lg:pb-20">
        <div class="absolute inset-0 hero-grid -z-10" aria-hidden="true"></div>
        <div class="aurora w-[26rem] h-[26rem] -top-24 end-0 bg-secondary/25 -z-10" aria-hidden="true"></div>
        <div class="aurora w-[22rem] h-[22rem] top-1/2 start-0 bg-indigo-600/20 -z-10" aria-hidden="true"></div>

        <div class="container mx-auto px-4 sm:px-6">
            <nav aria-label="مسیر صفحه" class="mb-8 lg:mb-12">
                <ol class="flex flex-wrap items-center gap-2 text-xs font-bold text-slate-400">
                    <li><a href="<?= esc_url(home_url('/')); ?>" class="hover:text-secondary transition-colors">خانه</a></li>
                    <li aria-hidden="true" class="text-slate-600">/</li>
                    <li><a href="<?= esc_url($archive_url); ?>" class="hover:text-secondary transition-colors">نمونه‌کارها</a></li>
                    <?php if ($pf['term']) : ?>
                        <li aria-hidden="true" class="text-slate-600">/</li>
                        <li><a href="<?= esc_url(get_term_link($pf['term'])); ?>" class="hover:text-secondary transition-colors"><?= esc_html($pf['term']->name); ?></a></li>
                    <?php endif; ?>
                    <li aria-hidden="true" class="text-slate-600">/</li>
                    <li aria-current="page" class="text-slate-200 truncate max-w-[14rem]"><?= esc_html($pf['title']); ?></li>
                </ol>
            </nav>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-14 items-end">
                <div class="lg:col-span-7">
                    <div class="flex items-center gap-4 mb-6" data-aos="fade-up">
                        <?php if ($pf['logo']) : ?>
                            <span class="flex items-center justify-center size-16 shrink-0 rounded-2xl border border-white/15 shadow-xl <?= esc_attr($pf['logo_bg']); ?>">
                                <img src="<?= esc_url($pf['logo']['url']); ?>" alt="<?= esc_attr('لوگوی ' . ($pf['client'] ?: $pf['title'])); ?>" width="44" height="44" class="size-11 object-contain">
                            </span>
                        <?php endif; ?>
                        <span class="inline-flex items-center gap-2 text-xs font-black text-secondary">
                            <span class="w-6 h-px bg-secondary"></span><?= esc_html(kmpr_pf_opt('pfs_badge')); ?><?= $pf['term'] ? ' · ' . esc_html($pf['term']->name) : ''; ?>
                        </span>
                    </div>
                    <h1 class="text-3xl sm:text-5xl xl:text-6xl font-black text-white leading-[1.35]" <?= preg_match('/[A-Za-z]/', $pf['title']) ? 'data-aos="fade-up"' : 'data-split'; ?>><?= esc_html($pf['title']); ?></h1>
                    <?php if (has_excerpt()) : ?>
                        <p class="mt-6 max-w-2xl text-base sm:text-lg text-slate-300 leading-loose" data-aos="fade-up" data-aos-delay="150"><?= esc_html(get_the_excerpt()); ?></p>
                    <?php endif; ?>

                    <div class="mt-9 flex flex-col sm:flex-row sm:flex-wrap gap-3" data-aos="fade-up" data-aos-delay="250">
                        <?php if ($pf['site']) : ?>
                            <a href="<?= esc_url($pf['site']); ?>" target="_blank" rel="noopener" data-magnetic
                               class="group inline-flex items-center justify-center gap-2 min-h-11 px-8 py-3.5 rounded-full bg-secondary hover:bg-amber-500 text-slate-950 text-base font-black whitespace-nowrap shadow-[0_20px_60px_-20px] shadow-secondary transition-colors">
                                مشاهده سایت زنده
                                <svg width="16" height="16" class="shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M4.25 5.5a.75.75 0 0 0-.75.75v8.5c0 .414.336.75.75.75h8.5a.75.75 0 0 0 .75-.75v-4a.75.75 0 0 1 1.5 0v4A2.25 2.25 0 0 1 12.75 17h-8.5A2.25 2.25 0 0 1 2 14.75v-8.5A2.25 2.25 0 0 1 4.25 4h5a.75.75 0 0 1 0 1.5h-5Zm7.25-.75a.75.75 0 0 1 .75-.75h3.5a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0V6.56l-5.22 5.22a.75.75 0 1 1-1.06-1.06l5.22-5.22h-1.69a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd"/></svg>
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($pf['archived'])) : ?>
                            <span class="inline-flex items-center justify-center gap-2 min-h-11 px-6 py-3 rounded-full border border-white/10 bg-white/[0.03] text-sm font-bold text-slate-300 whitespace-nowrap">
                                <svg width="16" height="16" class="shrink-0 text-slate-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M2 3a1 1 0 0 0-1 1v1a1 1 0 0 0 1 1h16a1 1 0 0 0 1-1V4a1 1 0 0 0-1-1H2Z"/><path fill-rule="evenodd" d="M2 7.5h16l-.811 7.71a2 2 0 0 1-1.99 1.79H4.802a2 2 0 0 1-1.99-1.79L2 7.5ZM7 11a1 1 0 0 1 1-1h4a1 1 0 1 1 0 2H8a1 1 0 0 1-1-1Z" clip-rule="evenodd"/></svg>
                                پروژه آرشیوی · سایت دیگر آنلاین نیست
                            </span>
                        <?php endif; ?>
                        <a href="<?= esc_url(kmpr_request_url()); ?>"
                           class="inline-flex items-center justify-center gap-2 min-h-11 px-8 py-3.5 rounded-full border border-white/15 bg-white/[0.04] hover:border-secondary/50 text-base font-bold text-slate-100 whitespace-nowrap transition-colors">
                            پروژه مشابه می‌خواهم
                        </a>
                    </div>
                </div>

                <?php if ($facts) : ?>
                    <div class="lg:col-span-5" data-aos="fade-up" data-aos-delay="200">
                        <dl class="grid grid-cols-2 gap-px overflow-hidden rounded-3xl border border-white/10 bg-white/10">
                            <?php foreach ($facts as $label => $value) : ?>
                                <div class="bg-slate-950/90 p-5 last:odd:col-span-2">
                                    <dt class="text-xs font-bold text-slate-400"><?= esc_html($label); ?></dt>
                                    <dd class="mt-1.5 text-sm sm:text-base font-black text-white leading-relaxed break-all"<?= $label === 'وب‌سایت' ? ' dir="ltr"' : ''; ?>><?= esc_html($value); ?></dd>
                                </div>
                            <?php endforeach; ?>
                        </dl>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <?php get_template_part('template-parts/portfolio/device-showcase', null, array('shots' => $shots, 'title' => $pf['title'], 'host' => $pf['host'])); ?>

    <!-- Overview + facts -->
    <section class="relative py-16 lg:py-24 border-t border-white/10" aria-labelledby="pf-overview-title">
        <div class="container mx-auto px-4 sm:px-6">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-14 items-start">
                <div class="lg:col-span-8">
                    <span class="inline-flex items-center gap-2 text-xs font-black text-secondary mb-4" data-aos="fade-up">
                        <span class="w-6 h-px bg-secondary"></span>معرفی پروژه
                    </span>
                    <h2 id="pf-overview-title" class="text-3xl sm:text-4xl font-black text-white leading-tight mb-8" data-aos="fade-up">درباره <?= esc_html($pf['client'] ?: $pf['title']); ?></h2>
                    <?php if ($content !== '') : ?>
                        <div class="pf-prose" data-aos="fade-up">
                            <?php the_content(); ?>
                        </div>
                    <?php elseif ($pf['excerpt']) : ?>
                        <p class="pf-prose" data-aos="fade-up"><?= esc_html(wp_strip_all_tags($pf['excerpt'])); ?></p>
                    <?php endif; ?>
                </div>

                <aside class="lg:col-span-4 lg:sticky lg:top-28 flex flex-col gap-5" aria-label="مشخصات پروژه">
                    <?php if ($pf['stack']) : ?>
                        <div class="rounded-3xl bg-white/[0.025] border border-white/10 p-6" data-aos="fade-up">
                            <h3 class="text-sm font-black text-white mb-4">تکنولوژی‌ها و ابزارها</h3>
                            <ul class="flex flex-wrap gap-2">
                                <?php foreach ($pf['stack'] as $tech) : ?>
                                    <li class="px-3 py-1.5 rounded-xl bg-white/[0.05] border border-white/10 text-xs font-bold text-slate-200" dir="auto"><?= esc_html($tech); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if ($pf['site'] || get_field('social-media')) : ?>
                        <div class="rounded-3xl bg-white/[0.025] border border-white/10 p-6" data-aos="fade-up">
                            <h3 class="text-sm font-black text-white mb-4">لینک‌های <?= esc_html($pf['client'] ?: 'پروژه'); ?></h3>
                            <?php if ($pf['site']) : ?>
                                <a href="<?= esc_url($pf['site']); ?>" target="_blank" rel="noopener"
                                   class="flex items-center justify-between gap-3 min-h-11 px-4 py-3 rounded-2xl bg-slate-950/60 border border-white/10 hover:border-secondary/50 text-sm font-bold text-slate-100 transition-colors">
                                    <span class="font-mono truncate" dir="ltr"><?= esc_html($pf['host']); ?></span>
                                    <svg width="16" height="16" class="shrink-0 text-secondary" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M4.25 5.5a.75.75 0 0 0-.75.75v8.5c0 .414.336.75.75.75h8.5a.75.75 0 0 0 .75-.75v-4a.75.75 0 0 1 1.5 0v4A2.25 2.25 0 0 1 12.75 17h-8.5A2.25 2.25 0 0 1 2 14.75v-8.5A2.25 2.25 0 0 1 4.25 4h5a.75.75 0 0 1 0 1.5h-5Zm7.25-.75a.75.75 0 0 1 .75-.75h3.5a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0V6.56l-5.22 5.22a.75.75 0 1 1-1.06-1.06l5.22-5.22h-1.69a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd"/></svg>
                                </a>
                            <?php endif; ?>
                            <?php get_template_part('template-parts/portfolio/social-media', null, array('post_id' => $post_id, 'class' => 'mt-4')); ?>
                        </div>
                    <?php endif; ?>

                    <div class="rounded-3xl border border-secondary/30 bg-gradient-to-br from-secondary/15 to-transparent p-6" data-aos="fade-up">
                        <p class="text-sm font-black text-white leading-relaxed">پروژه‌ای شبیه این در ذهن دارید؟</p>
                        <p class="mt-2 text-sm text-slate-300 leading-relaxed">چند سوال کوتاه را جواب دهید تا برآورد زمان و هزینه را برایتان بفرستم.</p>
                        <a href="<?= esc_url(kmpr_request_url()); ?>" class="mt-4 inline-flex items-center justify-center gap-2 min-h-11 px-6 py-3 rounded-full bg-secondary hover:bg-amber-500 text-slate-950 text-sm font-black whitespace-nowrap transition-colors">
                            شروع درخواست پروژه
                        </a>
                    </div>
                </aside>
            </div>
        </div>
    </section>

    <?php if (trim(wp_strip_all_tags($pf['challenge'])) || trim(wp_strip_all_tags($pf['solution']))) : ?>
        <!-- Challenge → Solution -->
        <section class="relative py-16 lg:py-24 border-t border-white/10" aria-labelledby="pf-story-title">
            <div class="container mx-auto px-4 sm:px-6">
                <span class="inline-flex items-center gap-2 text-xs font-black text-secondary mb-4" data-aos="fade-up">
                    <span class="w-6 h-px bg-secondary"></span>مسئله و راه‌حل
                </span>
                <h2 id="pf-story-title" class="text-3xl sm:text-4xl font-black text-white leading-tight mb-10 lg:mb-14" data-split>از چالش تا نتیجه</h2>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 lg:gap-8 relative" data-stagger="120">
                    <?php if (trim(wp_strip_all_tags($pf['challenge']))) : ?>
                        <div class="spotlight rounded-3xl bg-white/[0.025] border border-white/10 p-7 sm:p-10" data-aos="fade-up">
                            <div class="flex items-center gap-3 mb-6">
                                <span class="flex items-center justify-center size-11 rounded-2xl bg-rose-500/15 text-rose-300 font-black">۰۱</span>
                                <h3 class="text-xl sm:text-2xl font-black text-white">چالش</h3>
                            </div>
                            <div class="pf-prose"><?= wp_kses_post($pf['challenge']); ?></div>
                        </div>
                    <?php endif; ?>
                    <?php if (trim(wp_strip_all_tags($pf['solution']))) : ?>
                        <div class="spotlight rounded-3xl bg-gradient-to-br from-secondary/10 to-white/[0.02] border border-secondary/25 p-7 sm:p-10" data-aos="fade-up">
                            <div class="flex items-center gap-3 mb-6">
                                <span class="flex items-center justify-center size-11 rounded-2xl bg-secondary/20 text-secondary font-black">۰۲</span>
                                <h3 class="text-xl sm:text-2xl font-black text-white">راه‌حل من</h3>
                            </div>
                            <div class="pf-prose"><?= wp_kses_post($pf['solution']); ?></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($pf['results']) : ?>
        <!-- Results -->
        <section class="relative py-16 lg:py-24 border-t border-white/10 overflow-hidden isolate" aria-labelledby="pf-results-title">
            <div class="aurora w-[30rem] h-[30rem] top-0 left-1/2 -translate-x-1/2 bg-emerald-500/10 -z-10" aria-hidden="true"></div>
            <div class="container mx-auto px-4 sm:px-6">
                <span class="inline-flex items-center gap-2 text-xs font-black text-secondary mb-4" data-aos="fade-up">
                    <span class="w-6 h-px bg-secondary"></span>نتایج
                </span>
                <h2 id="pf-results-title" class="text-3xl sm:text-4xl font-black text-white leading-tight mb-10" data-split>نتیجه‌های قابل اندازه‌گیری</h2>
                <dl class="grid grid-cols-2 <?= array(1 => 'lg:grid-cols-2', 2 => 'lg:grid-cols-2', 3 => 'lg:grid-cols-3')[count($pf['results'])] ?? 'lg:grid-cols-4'; ?> gap-4 lg:gap-6" data-stagger="100">
                    <?php foreach ($pf['results'] as $res) :
                        $c = kmpr_pf_counter($res['value']); ?>
                        <div class="rounded-3xl bg-white/[0.025] border border-white/10 p-6 sm:p-8 flex flex-col-reverse gap-2" data-aos="fade-up">
                            <dt class="text-sm text-slate-300 leading-relaxed"><?= esc_html($res['label']); ?></dt>
                            <dd class="text-4xl sm:text-5xl font-black text-white" dir="ltr">
                                <?php if ($c) : ?>
                                    <?= esc_html(str_replace('%', '٪', kmpr_fa_num($c['prefix']))); ?><span data-count="<?= (int) $c['number']; ?>" data-suffix="<?= esc_attr(str_replace('%', '٪', kmpr_fa_num($c['suffix']))); ?>"><?= esc_html(str_replace('%', '٪', kmpr_fa_num($c['number'] . $c['suffix']))); ?></span>
                                <?php else : ?>
                                    <?= esc_html(str_replace('%', '٪', kmpr_fa_num($res['value']))); ?>
                                <?php endif; ?>
                            </dd>
                        </div>
                    <?php endforeach; ?>
                </dl>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($services) : ?>
        <!-- Related services -->
        <section class="relative py-16 lg:py-24 border-t border-white/10" aria-labelledby="pf-services-title">
            <div class="container mx-auto px-4 sm:px-6">
                <span class="inline-flex items-center gap-2 text-xs font-black text-secondary mb-4" data-aos="fade-up">
                    <span class="w-6 h-px bg-secondary"></span>خدمات این پروژه
                </span>
                <h2 id="pf-services-title" class="text-3xl sm:text-4xl font-black text-white leading-tight mb-10" data-split>کاری که برای این پروژه انجام دادم</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 lg:gap-6" data-stagger="90">
                    <?php foreach ($services as $service) :
                        $sid  = is_object($service) ? $service->ID : (int) $service;
                        $icon = get_field('service-icon', $sid);
                        $desc = get_field('services_description', $sid) ?: get_the_excerpt($sid); ?>
                        <a href="<?= esc_url(get_permalink($sid)); ?>" data-aos="fade-up"
                           class="spotlight group flex flex-col gap-4 rounded-3xl bg-white/[0.025] border border-white/10 hover:border-secondary/40 p-6 transition-colors">
                            <span class="flex items-center justify-center size-12 rounded-2xl bg-secondary/15 text-secondary [&_svg]:size-6 [&_svg]:shrink-0">
                                <?php if ($icon) : ?>
                                    <?= wp_kses($icon, $svg_kses); ?>
                                <?php else : ?>
                                    <svg width="22" height="22" class="shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.7 4.15a.75.75 0 0 1 .15 1.05l-8 10.5a.75.75 0 0 1-1.13.08l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.9 3.9 7.47-9.82a.75.75 0 0 1 1.05-.15Z" clip-rule="evenodd"/></svg>
                                <?php endif; ?>
                            </span>
                            <h3 class="text-lg font-black text-white group-hover:text-secondary transition-colors"><?= esc_html(get_the_title($sid)); ?></h3>
                            <?php if ($desc) : ?>
                                <p class="text-sm text-slate-300 leading-relaxed line-clamp-3"><?= esc_html(wp_strip_all_tags($desc)); ?></p>
                            <?php endif; ?>
                            <span class="mt-auto inline-flex items-center gap-1.5 text-sm font-black text-secondary whitespace-nowrap">
                                جزئیات خدمت
                                <svg width="16" height="16" class="shrink-0 group-hover:-translate-x-1 transition-transform" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.61l4.16 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.61 9.25h10.64A.75.75 0 0 1 17 10Z" clip-rule="evenodd"/></svg>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($prev_post || $next_post) : ?>
        <!-- Prev / next -->
        <nav class="relative py-12 lg:py-16 border-t border-white/10" aria-label="پروژه قبلی و بعدی">
            <div class="container mx-auto px-4 sm:px-6 grid grid-cols-1 md:grid-cols-2 gap-4 lg:gap-6">
                <?php foreach (array('prev' => $prev_post, 'next' => $next_post) as $dir => $adj) :
                    if (!$adj) {
                        echo '<div class="hidden md:block"></div>';
                        continue;
                    }
                    $thumb = kmpr_portfolio_cover($adj->ID, 'medium_large', 'laptop'); ?>
                    <a href="<?= esc_url(get_permalink($adj)); ?>" rel="<?= $dir === 'prev' ? 'prev' : 'next'; ?>"
                       class="spotlight group flex items-center gap-4 sm:gap-5 p-3 sm:p-4 rounded-3xl bg-white/[0.025] border border-white/10 hover:border-secondary/40 transition-colors <?= $dir === 'next' ? 'md:flex-row-reverse md:text-end' : ''; ?>">
                        <span class="relative w-28 sm:w-36 aspect-[16/10] shrink-0 overflow-hidden rounded-2xl border border-white/10 bg-slate-900">
                            <?php if ($thumb) : ?>
                                <img src="<?= esc_url($thumb['url']); ?>" alt="<?= esc_attr('نمای سایت ' . get_the_title($adj)); ?>" width="<?= (int) $thumb['width']; ?>" height="<?= (int) $thumb['height']; ?>" loading="lazy" class="absolute inset-0 w-full h-full object-cover object-top transition-transform duration-700 group-hover:scale-105">
                            <?php else : ?>
                                <span class="pf-fallback absolute inset-0 flex items-center justify-center text-2xl font-black text-white/90" aria-hidden="true"><?= esc_html(mb_substr(get_the_title($adj), 0, 1)); ?></span>
                            <?php endif; ?>
                        </span>
                        <span class="min-w-0 flex flex-col gap-1">
                            <span class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-400 <?= $dir === 'next' ? 'md:justify-end' : ''; ?>">
                                <?php if ($dir === 'prev') : ?>
                                    <svg width="14" height="14" class="shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h10.64l-4.16-3.96a.75.75 0 1 1 1.04-1.08l5.5 5.25a.75.75 0 0 1 0 1.08l-5.5 5.25a.75.75 0 1 1-1.04-1.08l4.16-3.96H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd"/></svg>
                                    پروژه قبلی
                                <?php else : ?>
                                    پروژه بعدی
                                    <svg width="14" height="14" class="shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.61l4.16 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.61 9.25h10.64A.75.75 0 0 1 17 10Z" clip-rule="evenodd"/></svg>
                                <?php endif; ?>
                            </span>
                            <span class="text-base sm:text-lg font-black text-white group-hover:text-secondary transition-colors line-clamp-2"><?= esc_html(get_the_title($adj)); ?></span>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        </nav>
    <?php endif; ?>

    <?php get_template_part('template-parts/portfolio/recently-portfolios', null, array(
        'postCount' => 3,
        'exclude'   => array($post_id),
        'term'      => $pf['term'],
        'kicker'    => 'ادامه بدهید',
        'title'     => kmpr_pf_opt('pfs_more_title'),
        'id'        => 'pf-more',
    )); ?>

    <?php get_template_part('template-parts/portfolio/cta', null, array('service' => $pf['title'])); ?>
</article>
<?php endwhile;
get_footer();
