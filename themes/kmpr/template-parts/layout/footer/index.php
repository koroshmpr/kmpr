<?php
/**
 * Modern Tailwind-styled footer
 */
?>
<section class="bg-slate-950 text-white pt-16 pb-24 lg:pb-8 border-t border-slate-900 overflow-hidden">
    <div class="container mx-auto px-4 sm:px-6">
        <!-- Main Footer Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-8 lg:gap-12 pb-12 border-b border-slate-900 text-start items-start">
            
            <!-- Description Panel (Logo, text, social) -->
            <div class="lg:col-span-4 bg-slate-900 border border-slate-800 p-6 sm:p-8 rounded-3xl flex flex-col items-center lg:items-start gap-4 shadow-xl" 
                 data-aos="fade-left" data-aos-duration="500">
                
                <!-- logo -->
                <?php get_template_part('template-parts/logo-brand', null, array('sizeLogo' => 'h-9 w-auto flex items-center justify-center')); ?>
                
                <!-- description (high-contrast white/light-slate) -->
                <div class="footer-panel text-sm text-slate-100 [&_p]:!text-slate-100 [&_span]:!text-slate-100 [&_*]:!text-slate-100 leading-relaxed text-center lg:text-start mt-2 font-medium">
                    <?= wp_kses_post(get_field('footer_description', 'option')); ?>
                </div>
                
                <!-- social media links -->
                <div class="mt-4 w-full flex justify-center lg:justify-start">
                    <?php
                    get_template_part('template-parts/social-media', null, array(
                        'class'    => 'gap-3 justify-center lg:justify-start',
                        'sizeSvgX' => '20',
                        'sizeSvgY' => '20',
                    ));
                    ?>
                </div>
            </div>

            <!-- Footer Menu 1 -->
            <div class="lg:col-span-2 flex flex-col gap-4">
                <span class="font-black text-center lg:text-start text-sm text-secondary uppercase tracking-wider block"><?= esc_html(get_field('first_menu', 'option')); ?></span>
                <?php
                if (has_nav_menu('footerLocationOne')) :
                    wp_nav_menu(array(
                        'theme_location' => 'footerLocationOne',
                        'menu_class'     => 'flex max-lg:flex-wrap justify-center lg:flex-col gap-2.5 text-sm [&_a]:!text-slate-300 [&_a:hover]:!text-secondary [&_a]:!bg-transparent font-medium transition-colors',
                        'container'      => false,
                        'depth'          => 1,
                    ));
                endif;
                ?>
            </div>

            <!-- Footer Menu 2 -->
            <div class="lg:col-span-2 flex flex-col gap-4">
                <span class="font-black text-center lg:text-start text-sm text-secondary uppercase tracking-wider block"><?= esc_html(get_field('second_menu', 'option')); ?></span>
                <?php
                if (has_nav_menu('footerLocationTwo')) :
                    wp_nav_menu(array(
                        'theme_location' => 'footerLocationTwo',
                        'menu_class'     => 'flex max-lg:flex-wrap justify-center lg:flex-col gap-2.5 text-sm [&_a]:!text-slate-300 [&_a:hover]:!text-secondary [&_a]:!bg-transparent font-medium transition-colors',
                        'container'      => false,
                        'depth'          => 1,
                    ));
                endif;
                ?>
            </div>

            <!-- Footer Menu 3 -->
            <div class="lg:col-span-2 flex flex-col gap-4">
                <span class="font-black text-center lg:text-start text-sm text-secondary uppercase tracking-wider block"><?= esc_html(get_field('third_menu', 'option')); ?></span>
                <?php
                if (has_nav_menu('footerLocationThree')) :
                    wp_nav_menu(array(
                        'theme_location' => 'footerLocationThree',
                        'menu_class'     => 'flex max-lg:flex-wrap justify-center lg:flex-col gap-2.5 text-sm [&_a]:!text-slate-300 [&_a:hover]:!text-secondary [&_a]:!bg-transparent font-medium transition-colors',
                        'container'      => false,
                        'depth'          => 1,
                    ));
                endif;
                ?>
            </div>

            <!-- Consultation & Contacts -->
            <div class="lg:col-span-2 flex flex-col gap-4">
                <span class="font-black text-center lg:text-start text-sm text-secondary uppercase tracking-wider block">مشاوره و ارتباط</span>
                
                <div class="flex lg:flex-col justify-center gap-3 mt-1">
                    <!-- Phone trigger -->
                    <?php if (get_field('phone_number', 'option')) : ?>
                        <a href="tel:<?= esc_attr(get_field('phone', 'option')); ?>"
                           class="flex items-center justify-start gap-3 p-3 bg-slate-900 hover:bg-slate-800 border border-slate-800 hover:border-slate-700 rounded-2xl transition-all text-slate-100 group">
                            <span class="text-secondary shrink-0 group-hover:scale-110 transition-transform">
                                <?php
                                get_template_part('template-parts/svg/call-fill', null, array(
                                    'sizeSvgX' => '16',
                                    'sizeSvgY' => '16',
                                )); 
                                ?>
                            </span>
                            <span class="text-xs font-bold leading-none text-slate-100"><?= get_field('phone_number_label', 'option') ?: get_field('phone_number', 'option'); ?></span>
                        </a>
                    <?php endif; ?>

                    <!-- Email trigger -->
                    <?php if (get_field('email', 'option')) : ?>
                        <a href="mailto:<?= esc_attr(get_field('email', 'option')); ?>"
                           class="flex items-center justify-start gap-3 p-3 bg-slate-900 hover:bg-slate-800 border border-slate-800 hover:border-slate-700 rounded-2xl transition-all text-slate-100 group">
                            <span class="text-secondary shrink-0 group-hover:scale-110 transition-transform">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M.05 3.555A2 2 0 0 1 2 2h12a2 2 0 0 1 1.95 1.555L8 8.414.05 3.555ZM0 4.697v7.104l5.803-3.558L0 4.697ZM6.761 8.83l-6.57 4.027A2 2 0 0 0 2 14h12a2 2 0 0 0 1.808-1.144l-6.57-4.027L8 9.586l-1.239-.757Zm3.436-.586L16 11.801V4.697l-5.803 3.546Z"/>
                                </svg>
                            </span>
                            <span class="text-xs font-bold leading-none truncate w-full text-slate-100"><?= get_field('email', 'option'); ?></span>
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Confirmations icons repeater -->
                <div class="flex gap-4 pt-4 justify-start items-center">
                    <?php while (have_rows('confirmation', 'option')): the_row(); ?>
                        <div class="text-slate-300 text-xs font-medium"><?= get_sub_field('conf_items', 'option'); ?></div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>

        <!-- Copyright block -->
        <?php get_template_part('template-parts/layout/footer/copyright'); ?>
    </div>
</section>
