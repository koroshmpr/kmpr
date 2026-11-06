<?php
/**
 * Single Portfolio Case Study Template - Dark Obsidian Edition
 * Personal Brand: Korosh Mpr | Senior Software Architect & Full-Stack Developer
 *
 * Designed for maximum readability, engineering depth, and modern aesthetics.
 */

get_header();

track_post_views(get_the_ID());

while (have_posts()) :
    the_post();
    $post_id         = get_the_ID();
    $brand_logo      = get_field('brand-logo');
    $logo_bg         = get_field('logo_background') ?: 'bg-white';
    $website         = get_field('portfolio-website');
    $theme_type      = get_field('theme_type');
    $page_builder    = get_field('page_builder');
    $services        = get_field('relation-service');
    $categories      = get_the_terms($post_id, 'portfolio_categories');
    $thumbnail_url   = get_the_post_thumbnail_url($post_id, 'full') ?: get_the_post_thumbnail_url($post_id, 'large');
    $prev_post       = get_previous_post();
    $next_post       = get_next_post();
?>

<div class="portfolio-single-wrapper min-h-screen bg-slate-950 text-slate-100 py-10 lg:py-16 selection:bg-secondary/30 selection:text-secondary relative overflow-hidden">
    
    <!-- Subtle Ambient Glow Spheres -->
    <div class="absolute top-20 start-1/4 w-96 h-96 bg-secondary/10 rounded-full blur-[140px] pointer-events-none -z-0"></div>
    <div class="absolute top-1/2 end-10 w-96 h-96 bg-blue-600/10 rounded-full blur-[140px] pointer-events-none -z-0"></div>

    <div class="container mx-auto px-4 sm:px-6 max-w-6xl relative z-10">

        <!-- Top Breadcrumbs & Back Navigation -->
        <div class="flex flex-wrap items-center justify-between gap-4 mb-8 pb-6 border-b border-white/10">
            <a href="<?= esc_url(get_post_type_archive_link('portfolio') ?: home_url('/portfolios/')); ?>" 
               class="inline-flex items-center gap-2 text-xs sm:text-sm font-bold text-slate-400 hover:text-white transition-colors group">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="rotate-180 text-secondary group-hover:-translate-x-1 transition-transform" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8z"/>
                </svg>
                <span>بازگشت به آرشیو نمونه‌کارها</span>
            </a>

            <!-- Categories Badges -->
            <?php if (!empty($categories) && !is_wp_error($categories)) : ?>
                <div class="flex flex-wrap items-center gap-2">
                    <?php foreach ($categories as $cat) : ?>
                        <span class="text-xs font-bold px-3 py-1 rounded-full bg-secondary/15 text-secondary border border-secondary/30">
                            <?= esc_html($cat->name); ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Hero Case Study Header -->
        <header class="mb-10 text-start space-y-4" data-aos="fade-up">
            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-white/[0.04] border border-white/10 text-xs font-mono text-slate-300">
                <span class="size-2 rounded-full bg-emerald-400 animate-pulse"></span>
                <span>مطالعه موردی مهندسی (Case Study)</span>
            </div>

            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                <div>
                    <h1 class="text-3xl sm:text-4xl lg:text-5xl font-black text-white tracking-tight leading-tight">
                        <?= get_the_title(); ?>
                    </h1>
                    <?php if (has_excerpt()) : ?>
                        <p class="text-sm sm:text-base text-slate-300 font-medium mt-3 max-w-3xl leading-relaxed">
                            <?= esc_html(get_the_excerpt()); ?>
                        </p>
                    <?php endif; ?>
                </div>

                <!-- Action Links -->
                <div class="flex items-center gap-3 shrink-0 flex-wrap">
                    <?php if (!empty($website['url'])) : ?>
                        <a href="<?= esc_url($website['url']); ?>" 
                           target="_blank" 
                           rel="noopener noreferrer"
                           class="inline-flex items-center gap-2 bg-secondary hover:bg-secondary-hover text-white text-xs sm:text-sm font-black px-5 py-3 rounded-2xl transition-all duration-300 shadow-xl shadow-secondary/25 hover:scale-105 active:scale-95">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                                <polyline points="15 3 21 3 21 9"></polyline>
                                <line x1="10" y1="14" x2="21" y2="3"></line>
                            </svg>
                            <span>مشاهده آنلاین وب‌سایت پروژه</span>
                        </a>
                    <?php endif; ?>

                    <a href="<?= esc_url(home_url('/contact-us') ?: home_url('/contact')); ?>" 
                       class="inline-flex items-center gap-2 bg-white/[0.05] hover:bg-white/[0.1] text-slate-200 hover:text-white border border-white/15 text-xs sm:text-sm font-bold px-4 py-3 rounded-2xl transition-all">
                        <span>مشاوره پروژه مشابه</span>
                    </a>
                </div>
            </div>
        </header>

        <!-- Bento Engineering Specs Grid -->
        <section class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-10 text-start" data-aos="fade-up" data-aos-delay="100">
            <!-- Box 1: Architecture -->
            <div class="p-4 sm:p-5 rounded-2xl bg-slate-900/80 border border-white/10 backdrop-blur-md">
                <span class="text-[11px] font-mono text-slate-400 block mb-1">نوع معماری</span>
                <span class="text-xs sm:text-sm font-black text-white block">
                    <?= esc_html($theme_type ?: 'توسعه اختصاصی'); ?>
                </span>
            </div>

            <!-- Box 2: Tech Stack -->
            <div class="p-4 sm:p-5 rounded-2xl bg-slate-900/80 border border-white/10 backdrop-blur-md">
                <span class="text-[11px] font-mono text-slate-400 block mb-1">روش توسعه</span>
                <span class="text-xs sm:text-sm font-black text-white block">
                    <?= esc_html($page_builder ?: 'کدنویسی بدون قالب آماده'); ?>
                </span>
            </div>

            <!-- Box 3: Performance Standard -->
            <div class="p-4 sm:p-5 rounded-2xl bg-slate-900/80 border border-white/10 backdrop-blur-md">
                <span class="text-[11px] font-mono text-emerald-400 block mb-1">ارزیابی عملکرد</span>
                <span class="text-xs sm:text-sm font-black text-white flex items-center gap-1.5">
                    <span class="size-2 rounded-full bg-emerald-400"></span>
                    <span>Lighthouse 99+ / لود زیر ۱ثانیه</span>
                </span>
            </div>

            <!-- Box 4: Standard & Stability -->
            <div class="p-4 sm:p-5 rounded-2xl bg-slate-900/80 border border-white/10 backdrop-blur-md">
                <span class="text-[11px] font-mono text-secondary block mb-1">استاندارد کیفی</span>
                <span class="text-xs sm:text-sm font-black text-white block">
                    Zero-Bloat & Clean Code
                </span>
            </div>
        </section>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-10 items-start">
            
            <!-- Col 8: Case Study Deep Dive Article -->
            <main class="lg:col-span-8 flex flex-col gap-6 text-start">
                <div class="bg-slate-900/70 border border-white/10 rounded-3xl p-6 sm:p-10 backdrop-blur-xl shadow-2xl space-y-6">
                    
                    <!-- Article Lead Summary Banner -->
                    <div class="p-4 sm:p-5 rounded-2xl bg-white/[0.02] border border-white/10 flex items-start gap-4">
                        <span class="size-8 rounded-xl bg-secondary/15 text-secondary flex items-center justify-center shrink-0 mt-0.5">
                            <svg class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="16" x2="12" y2="12"></line>
                                <line x1="12" y1="8" x2="12.01" y2="8"></line>
                            </svg>
                        </span>
                        <div class="text-xs sm:text-sm leading-relaxed text-slate-300 space-y-1">
                            <strong class="text-white block font-black">اهداف کلیدی پیاده‌سازی این پروژه:</strong>
                            <p>ارتقای حداکثری سرعت لود و عملکرد، پیاده‌سازی سئو تکنیکال بدون افزونه‌های سنگین، ساختار ریسپانسیو بی‌نقص و حذف کامل بدهی فنی (Technical Debt).</p>
                        </div>
                    </div>

                    <!-- Rich Text Content (Strictly High Contrast for Dark Theme) -->
                    <article class="prose prose-invert prose-slate max-w-none text-slate-200 leading-relaxed sm:leading-loose text-justify [&_h2]:text-white [&_h2]:font-black [&_h2]:text-xl sm:[&_h2]:text-2xl [&_h2]:mt-8 [&_h2]:mb-4 [&_h3]:text-secondary [&_h3]:font-black [&_h3]:text-lg [&_p]:text-slate-200 [&_p]:mb-5 [&_p]:leading-loose [&_strong]:text-white [&_strong]:font-black [&_ul]:list-disc [&_ul]:list-inside [&_ul]:space-y-2 [&_li]:text-slate-200 [&_img]:rounded-2xl [&_img]:border [&_img]:border-white/10 [&_img]:shadow-2xl [&_img]:my-6">
                        <?= the_content(); ?>
                    </article>

                    <!-- Client Direct Link Action at the end of article -->
                    <?php if (!empty($website['url'])) : ?>
                        <div class="pt-6 border-t border-white/10 flex flex-col sm:flex-row items-center justify-between gap-4">
                            <span class="text-xs sm:text-sm text-slate-300 font-medium">برای بررسی زنده تجربه کاربری و سرعت سایت، پروژه را آنلاین مشاهده کنید:</span>
                            <a href="<?= esc_url($website['url']); ?>" 
                               target="_blank" 
                               rel="noopener noreferrer"
                               class="inline-flex items-center gap-2 bg-secondary/20 hover:bg-secondary text-secondary hover:text-white border border-secondary/40 text-xs sm:text-sm font-black px-5 py-2.5 rounded-xl transition-all">
                                <span>باز کردن <?= esc_html(get_the_title()); ?> &larr;</span>
                            </a>
                        </div>
                    <?php endif; ?>

                </div>
            </main>

            <!-- Col 4: Sticky Case Study Sidebar -->
            <aside class="lg:col-span-4 flex flex-col gap-6 lg:sticky lg:top-24 text-start">
                
                <!-- Main Featured Visual Card -->
                <div class="bg-slate-900/80 border border-white/10 rounded-3xl p-4 shadow-xl backdrop-blur-xl relative group">
                    <div class="relative overflow-hidden aspect-[4/3] rounded-2xl bg-slate-950 border border-white/10">
                        <?php if ($thumbnail_url) : ?>
                            <img class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700 pointer-events-none"
                                 src="<?= esc_url($thumbnail_url); ?>"
                                 alt="<?= esc_attr(get_the_title()); ?>"
                                 loading="eager"/>
                        <?php endif; ?>

                        <!-- Brand Logo Badge -->
                        <?php if ($brand_logo) : ?>
                            <div class="absolute bottom-3 end-3 z-10 p-2 border border-white/15 bg-slate-950/90 rounded-2xl shadow-xl backdrop-blur-md size-14 flex items-center justify-center">
                                <img class="max-w-full max-h-full object-contain"
                                     src="<?= esc_url($brand_logo['url']); ?>"
                                     alt="<?= esc_attr($brand_logo['title'] ?? ''); ?>">
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Services Delivered -->
                <?php if ($services) : ?>
                    <div class="bg-slate-900/80 border border-white/10 rounded-3xl p-6 backdrop-blur-xl shadow-xl text-start">
                        <h4 class="text-xs font-black uppercase tracking-wider text-slate-400 mb-4 border-b border-white/10 pb-3 flex items-center gap-2">
                            <span class="size-2 rounded-full bg-secondary"></span>
                            <span>خدمات ارائه‌شده در این پروژه</span>
                        </h4>
                        
                        <div class="flex flex-col gap-2.5">
                            <?php foreach ($services as $service) :
                                $serviceID = $service->ID;
                                $permalink = get_permalink($serviceID);
                                $title     = get_the_title($serviceID);
                                $icon      = get_field('service-icon', $serviceID);
                            ?>
                                <a href="<?= esc_url($permalink); ?>" 
                                   class="flex items-center gap-3 p-3 rounded-2xl bg-white/[0.02] hover:bg-white/[0.08] border border-white/5 hover:border-secondary/30 transition-all text-slate-200 hover:text-white group">
                                    <?php if ($icon) : ?>
                                        <span class="text-secondary group-hover:scale-110 transition-transform shrink-0">
                                            <?= $icon; ?>
                                        </span>
                                    <?php else : ?>
                                        <span class="size-2 rounded-full bg-secondary shrink-0"></span>
                                    <?php endif; ?>
                                    <span class="text-xs font-bold"><?= esc_html($title); ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Client Channels & Social Media -->
                <div class="bg-slate-900/80 border border-white/10 rounded-3xl p-6 backdrop-blur-xl shadow-xl text-start space-y-4">
                    <h4 class="text-xs font-black uppercase tracking-wider text-slate-400 border-b border-white/10 pb-3">
                        ارتباط و اشتراک‌گذاری
                    </h4>

                    <?php if (!empty($website['url'])) : ?>
                        <a href="<?= esc_url($website['url']); ?>" 
                           target="_blank"
                           class="w-full text-center bg-secondary hover:bg-secondary-hover text-white text-xs font-black py-2.5 rounded-xl transition-all flex items-center justify-center gap-2 shadow-md">
                            <span>ورود به سایت کارفرما</span>
                            <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                                <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                                <polyline points="15 3 21 3 21 9"></polyline>
                                <line x1="10" y1="14" x2="21" y2="3"></line>
                            </svg>
                        </a>
                    <?php endif; ?>

                    <!-- Social media links -->
                    <div class="pt-2 flex justify-center">
                        <?php
                        get_template_part('template-parts/portfolio/social-media', null, array(
                            'class'    => 'gap-2 justify-center',
                            'sizeSvgX' => '16',
                            'sizeSvgY' => '16',
                        ));
                        ?>
                    </div>
                </div>

            </aside>

        </div>

    </div>

    <!-- Interactive Mockup Showcase Section -->
    <?php if (have_rows('screenshot')) : ?>
        <section class="mt-20 py-16 bg-slate-900/40 border-y border-white/10 relative">
            <div class="container mx-auto px-4 sm:px-6 max-w-6xl">
                <div class="text-center max-w-xl mx-auto mb-12">
                    <span class="text-xs font-mono text-secondary uppercase font-bold tracking-widest block mb-2">پیش‌نمایش تعاملی</span>
                    <h3 class="text-2xl sm:text-3xl font-black text-white">رابط کاربری و ساختار ریسپانسیو</h3>
                    <p class="text-xs sm:text-sm text-slate-400 mt-2">ماوس را روی هر تصویر ببرید تا صفحه را به صورت اسکرول کامل مشاهده کنید.</p>
                </div>

                <div class="flex flex-wrap lg:flex-nowrap justify-center gap-8 items-start">
                    <?php while (have_rows('screenshot')) : the_row();
                        $screen_size    = get_sub_field('screen-size');
                        $screenshot_img = get_sub_field('screenshot-image');
                        if ($screen_size == 'laptop') {
                            $col_width    = 'lg:w-2/3';
                            $aspect_ratio = 'aspect-[16/10]';
                            $label        = 'نسخه دسکتاپ و مانیتور';
                        } elseif ($screen_size == 'tablet') {
                            $col_width    = 'lg:w-1/3';
                            $aspect_ratio = 'aspect-[3/4]';
                            $label        = 'نسخه تبلت';
                        } elseif ($screen_size == 'mobile') {
                            $col_width    = 'lg:w-1/4';
                            $aspect_ratio = 'aspect-[9/19]';
                            $label        = 'نسخه موبایل';
                        } else {
                            $col_width    = 'lg:w-1/2';
                            $aspect_ratio = 'aspect-video';
                            $label        = 'پیش‌نمایش';
                        }
                    ?>
                        <div class="w-full <?= $col_width; ?> flex flex-col gap-3">
                            <div class="bg-slate-900 rounded-3xl shadow-2xl border border-white/10 overflow-hidden flex flex-col p-2.5">
                                <!-- macOS Header Toolbar -->
                                <div class="flex items-center justify-between px-4 py-2.5 border-b border-white/10 text-slate-400">
                                    <div class="flex items-center gap-1.5">
                                        <div class="size-2.5 rounded-full bg-rose-500/80"></div>
                                        <div class="size-2.5 rounded-full bg-amber-500/80"></div>
                                        <div class="size-2.5 rounded-full bg-emerald-500/80"></div>
                                    </div>
                                    <span class="text-[11px] font-mono text-slate-400"><?= esc_html($label); ?></span>
                                    <div class="scale-100 opacity-60">
                                        <?php
                                        get_template_part('template-parts/svg/portfolio/' . $screen_size, null, array(
                                            'sizeSvgX' => '20',
                                            'sizeSvgY' => '20',
                                            'color'    => '#94a3b8'
                                        ));
                                        ?>
                                    </div>
                                </div>

                                <!-- Screen view with auto smooth scroll on hover -->
                                <?php if ($screenshot_img) : ?>
                                    <div class="overflow-hidden w-full <?= $aspect_ratio; ?> bg-slate-950 rounded-2xl relative group/screen">
                                        <div class="screenshot absolute inset-0 pointer-events-auto transition-[background-position] ease-in-out bg-top bg-cover w-full h-full group-hover/screen:bg-bottom"
                                             style="background-image: url('<?= esc_url($screenshot_img['url']); ?>');
                                                     transition-duration: <?= esc_attr(max(1500, ($screenshot_img['height'] ?? 1000) * 1.5)); ?>ms">
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- Adjacent Case Studies Switcher (Next / Previous) -->
    <section class="container mx-auto px-4 sm:px-6 max-w-6xl my-16">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <?php if (!empty($prev_post)) : ?>
                <a href="<?= esc_url(get_permalink($prev_post->ID)); ?>" 
                   class="p-6 rounded-3xl bg-slate-900/60 hover:bg-slate-900 border border-white/10 hover:border-secondary/40 transition-all flex items-center gap-4 text-start group">
                    <span class="size-10 rounded-2xl bg-secondary/15 text-secondary flex items-center justify-center shrink-0 group-hover:-translate-x-1 transition-transform">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <polyline points="15 18 9 12 15 6"></polyline>
                        </svg>
                    </span>
                    <div>
                        <span class="text-[11px] font-mono text-slate-400 block">پروژه قبلی</span>
                        <h4 class="text-sm sm:text-base font-black text-white group-hover:text-secondary transition-colors"><?= esc_html(get_the_title($prev_post->ID)); ?></h4>
                    </div>
                </a>
            <?php else : ?>
                <div></div>
            <?php endif; ?>

            <?php if (!empty($next_post)) : ?>
                <a href="<?= esc_url(get_permalink($next_post->ID)); ?>" 
                   class="p-6 rounded-3xl bg-slate-900/60 hover:bg-slate-900 border border-white/10 hover:border-secondary/40 transition-all flex items-center justify-between gap-4 text-end group">
                    <div>
                        <span class="text-[11px] font-mono text-slate-400 block">پروژه بعدی</span>
                        <h4 class="text-sm sm:text-base font-black text-white group-hover:text-secondary transition-colors"><?= esc_html(get_the_title($next_post->ID)); ?></h4>
                    </div>
                    <span class="size-10 rounded-2xl bg-secondary/15 text-secondary flex items-center justify-center shrink-0 group-hover:translate-x-1 transition-transform">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </span>
                </a>
            <?php endif; ?>
        </div>
    </section>

    <!-- Other portfolios carousel links -->
    <section class="container mx-auto px-4 sm:px-6 max-w-6xl pb-16">
        <div class="flex items-center justify-between border-b border-white/10 pb-4 mb-8">
            <h3 class="text-xl sm:text-2xl font-black text-white">سایر پروژه‌ها و نمونه‌کارها</h3>
            <a href="<?= esc_url(get_post_type_archive_link('portfolio') ?: home_url('/portfolios/')); ?>" 
               class="text-xs font-bold text-secondary hover:text-white transition-colors">
                مشاهده همه نمونه‌کارها &larr;
            </a>
        </div>

        <?php
        $args = array(
            'post_type'           => 'portfolio',
            'post_status'         => 'publish',
            'order'               => 'DESC',
            'orderby'             => 'rand',
            'posts_per_page'      => 3,
            'ignore_sticky_posts' => true,
            'post__not_in'        => array($post_id)
        );
        $loop = new WP_Query($args);
        if ($loop->have_posts()) :
        ?>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 sm:gap-8">
                <?php
                while ($loop->have_posts()) : $loop->the_post();
                    get_template_part('template-parts/portfolio/portfolio-card', null, array(
                        'class' => 'h-full'
                    ));
                endwhile;
                ?>
            </div>
        <?php
        endif;
        wp_reset_postdata(); ?>
    </section>

</div>

<?php endwhile;
wp_reset_query();
get_footer(); ?>
