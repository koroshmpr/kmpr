<?php get_header();

track_post_views(get_the_ID());

while (have_posts()) :
    the_post();
    $comment_count = get_comments_number(); 
    ?>
    <section class="container mx-auto px-6 py-8 lg:py-16">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-start">
            
            <!-- Sidebar: Order first on mobile, order last on desktop -->
            <aside class="lg:col-span-4 flex flex-col gap-6 lg:sticky lg:top-24 max-lg:order-first lg:order-last">
                <!-- Project Image & Logo Card -->
                <div class="bg-white border border-slate-200/80 rounded-2xl p-4 shadow-sm" data-aos="flip-right">
                    <div class="relative overflow-hidden aspect-[4/3] rounded-xl bg-slate-50">
                        <!-- Logo badge -->
                        <?php $brand_logo = get_field('brand-logo'); ?>
                        <?php if ($brand_logo) : ?>
                            <img class="absolute bottom-4 end-4 z-10 p-1.5 border border-slate-200 bg-white rounded-xl shadow-md size-14 object-contain <?= get_field('logo_background'); ?>"
                                 src="<?= esc_url($brand_logo['url']); ?>"
                                 alt="<?= esc_attr($brand_logo['title']); ?>">
                        <?php endif; ?>
                        
                        <!-- Thumbnail -->
                        <img class="w-full h-full object-cover pointer-events-none"
                             src="<?= esc_url(get_the_post_thumbnail_url()); ?>"
                             alt="<?= esc_attr(get_the_title()); ?>"/>
                    </div>
                </div>

                <!-- Info Card -->
                <div class="bg-slate-50 border border-slate-200/60 rounded-2xl p-6 text-start">
                    <h1 class="font-black text-2xl lg:text-3xl text-slate-800 mb-4"><?= get_the_title(); ?></h1>
                    
                    <!-- Share Button -->
                    <?php
                    get_template_part('template-parts/share-button', null, array(
                        'headingClass' => 'text-sm font-bold text-slate-500',
                        'containerCLass' => 'mt-4 border-t border-slate-200/60 pt-4',
                        'class' => 'gap-3 justify-end',
                    ));
                    ?>
                </div>

                <!-- Related Services -->
                <?php
                $services = get_field('relation-service');
                if ($services):
                    ?>
                    <div class="bg-white border border-slate-200/80 rounded-2xl p-6 text-start">
                        <h6 class="font-extrabold text-slate-800 border-b border-slate-200 pb-3 mb-4 text-base">خدمات ارائه شده</h6>
                        <div class="grid grid-cols-3 gap-3">
                            <?php
                            foreach ($services as $index => $service):
                                $serviceID = $service->ID;
                                $permalink = get_permalink($serviceID);
                                $title = get_the_title($serviceID);
                                $icon = get_field('service-icon', $serviceID); ?>
                                <a data-aos-delay="<?= $index;?>00" data-aos-duration="500" data-aos="zoom-out" 
                                   class="flex flex-col items-center justify-center p-3 bg-slate-50 hover:bg-primary hover:text-white rounded-xl transition-all duration-300 border border-slate-100"
                                   href="<?= esc_url($permalink) ?? ''; ?>">
                                    <span class="text-secondary group-hover:text-white"><?= $icon; ?></span>
                                    <span class="text-xs text-center font-bold mt-2 truncate w-full"><?= esc_html($title); ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </aside>

            <!-- Main Content: lg:col-span-8 -->
            <div class="lg:col-span-8 flex flex-col gap-6 text-start">
                
                <!-- Category Details badge -->
                <?php if (get_queried_object()->post_type == 'portfolio'): ?>
                    <div class="flex flex-wrap gap-2 justify-start items-center">
                        <?php
                        $category_detail = get_the_category($post->ID, 'portfolio_categories');
                        foreach ($category_detail as $category) {
                            echo '<span class="bg-secondary/15 text-secondary text-xs font-bold px-3 py-1 rounded-md">' . esc_html($category->name) . '</span>';
                        }
                        ?>
                    </div>
                <?php endif; ?>

                <!-- Customer Details Grid banner -->
                <div class="bg-slate-900 rounded-3xl p-6 lg:p-8 text-white" data-aos="zoom-in">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 divide-y md:divide-y-0 md:divide-x md:divide-x-reverse divide-white/10 text-start">
                        
                        <!-- Client Contact info -->
                        <div class="flex flex-col items-center md:items-start justify-center gap-4 max-md:pb-6">
                            <h3 class="text-secondary text-lg font-bold">اطلاعات مشتری</h3>
                            <!-- Client social links -->
                            <div data-aos="fade-left" data-aos-duration="700" data-aos-delay="200">
                                <?php
                                get_template_part('template-parts/portfolio/social-media', null, array(
                                    'class' => 'gap-2.5',
                                    'sizeSvgX' => '18',
                                    'sizeSvgY' => '18',
                                ));
                                ?>
                            </div>
                            
                            <!-- Client website -->
                            <?php $website = get_field('portfolio-website'); ?>
                            <?php if ($website) : ?>
                                <a class="inline-flex items-center gap-2 bg-secondary text-primary font-bold px-6 py-2.5 rounded-xl hover:scale-105 hover:bg-secondary/90 transition-all duration-300 shadow-lg text-sm"
                                   target="_blank"
                                   href="<?= esc_url($website['url']); ?>">
                                    <svg width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                                        <path d="M13.5 3a.5.5 0 0 1 .5.5V11H2V3.5a.5.5 0 0 1 .5-.5zm-11-1A1.5 1.5 0 0 0 1 3.5V12h14V3.5A1.5 1.5 0 0 0 13.5 2zM0 12.5h16a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 0 12.5"></path>
                                    </svg>
                                    وب‌سایت
                                </a>
                            <?php endif; ?>
                        </div>

                        <!-- Technical project specs -->
                        <div class="flex flex-col gap-4 items-center md:items-start justify-center max-md:pt-6 md:pe-8">
                            <h3 class="text-secondary text-lg font-bold">اطلاعات فنی</h3>
                            
                            <?php $theme_type = get_field_object('theme_type'); ?>
                            <?php if ($theme_type && $theme_type['value']) : ?>
                                <div class="text-slate-300 text-sm">
                                    <?= esc_html($theme_type['label']); ?>: 
                                    <span class="text-white font-bold ps-1"><?= esc_html($theme_type['value']); ?></span>
                                </div>
                            <?php endif; ?>

                            <?php $page_builder = get_field_object('page_builder'); ?>
                            <?php if ($page_builder && $page_builder['value']) : ?>
                                <div class="text-slate-300 text-sm">
                                    <?= esc_html($page_builder['label']); ?>: 
                                    <span class="text-white font-bold ps-1"><?= esc_html($page_builder['value']); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Rich content article -->
                <article class="prose max-w-none text-slate-700 leading-loose text-justify py-6 border-b border-slate-200/80">
                    <?= the_content(); ?>
                </article>
            </div>
        </div>
    </section>

    <!-- Project Showcase Screens mockup section -->
    <?php if (have_rows('screenshot')) { ?>
        <section class="bg-slate-50 border-y border-slate-200/60 py-16">
            <div class="container mx-auto px-6">
                <div class="flex flex-wrap lg:flex-nowrap justify-center gap-8">
                    <?php while (have_rows('screenshot')): the_row();
                        $screen_size = get_sub_field('screen-size');
                        if ($screen_size == 'laptop') {
                            $col_width = 'lg:w-1/2';
                            $aspect_ratio = 'aspect-[16/10]';
                        } elseif ($screen_size == 'tablet') {
                            $col_width = 'lg:w-1/3';
                            $aspect_ratio = 'aspect-[3/4]';
                        } elseif ($screen_size == 'mobile') {
                            $col_width = 'lg:w-1/5';
                            $aspect_ratio = 'aspect-[9/19]';
                        } else {
                            $col_width = 'lg:w-1/3';
                            $aspect_ratio = 'aspect-video';
                        }
                        ?>
                        <div class="w-full <?= $col_width; ?> flex flex-col gap-4">
                            <div class="bg-slate-900 rounded-2xl shadow-lg border border-slate-800 overflow-hidden flex flex-col p-2">
                                <!-- Top header toolbar -->
                                <div class="flex items-center justify-between px-4 py-2 border-b border-slate-800 text-slate-400">
                                    <div class="flex items-center gap-1.5">
                                        <div class="size-2.5 rounded-full bg-red-500/80"></div>
                                        <div class="size-2.5 rounded-full bg-yellow-500/80"></div>
                                        <div class="size-2.5 rounded-full bg-green-500/80"></div>
                                    </div>
                                    <div class="scale-110">
                                        <?php
                                        get_template_part('template-parts/svg/portfolio/' . $screen_size, null, array(
                                            'sizeSvgX' => '24',
                                            'sizeSvgY' => '24',
                                            'color' => '#64748b'
                                        ));
                                        ?>
                                    </div>
                                </div>

                                <!-- Screen view -->
                                <?php $screenshot_img = get_sub_field('screenshot-image'); ?>
                                <?php if ($screenshot_img) : ?>
                                    <div class="overflow-hidden w-full <?= $aspect_ratio; ?> bg-slate-950 rounded-xl relative group/screen">
                                        <div class="screenshot absolute inset-0 pointer-events-auto transition-[background-position] ease-in-out bg-top bg-cover w-full h-full group-hover/screen:bg-bottom"
                                             style="background-image: url('<?= esc_url($screenshot_img['url']); ?>');
                                                     transition-duration: <?= esc_attr($screenshot_img['height'] * 1.8); ?>ms">
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </section>
    <?php } ?>

    <!-- Other portfolios carousel links -->
    <section class="container mx-auto px-6 py-16">
        <h3 class="text-2xl font-black text-slate-800 border-b border-slate-200 pb-4 mb-8 text-start">نمونه کارهای دیگر</h3>
        <?php
        global $post;
        $current_post_id = $post->ID;
        $args = array(
            'post_type' => 'portfolio',
            'post_status' => 'publish',
            'order' => 'DESC',
            'orderby' => 'rand',
            'posts_per_page' => '3',
            'ignore_sticky_posts' => true,
            'post__not_in' => array($current_post_id)
        );
        $loop = new WP_Query($args);
        if ($loop->have_posts()) :
        ?>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
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
<?php endwhile;
wp_reset_query();
get_footer(); ?>
