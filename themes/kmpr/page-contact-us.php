<?php
/** Template Name: contact-us page */

get_header(); ?>
<section class="bg-slate-900 text-white py-16 lg:py-24 overflow-hidden border-b border-slate-800">
    <div class="container mx-auto px-6 max-w-6xl">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center text-start">
            
            <!-- Contact Channels -->
            <div class="lg:col-span-5 flex flex-col gap-6" data-aos="fade-left">
                <h1 class="text-3xl lg:text-5xl font-black text-white leading-tight">راه‌های ارتباطی</h1>
                <div class="h-1 bg-secondary w-16 rounded-full"></div>
                
                <div class="flex flex-col gap-5 mt-4">
                    <!-- Address -->
                    <?php if (get_field('address', 'option')) : ?>
                        <address class="not-italic flex items-start gap-4 p-4 rounded-2xl bg-white/5 border border-white/10 hover:bg-white/10 transition-all">
                            <span class="text-secondary mt-1">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-geo-alt-fill" viewBox="0 0 16 16">
                                    <path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/>
                                </svg>
                            </span>
                            <span class="text-sm font-bold leading-relaxed text-slate-200"><?= get_field('address', 'option'); ?></span>
                        </address>
                    <?php endif; ?>

                    <!-- Phone -->
                    <?php if (get_field('phone_number', 'option')) : ?>
                        <a href="tel:<?= esc_attr(get_field('phone', 'option')); ?>"
                           class="flex items-center gap-4 p-4 rounded-2xl bg-white/5 border border-white/10 hover:bg-white/10 transition-all text-slate-200">
                            <span class="text-secondary">
                                <?php
                                get_template_part('template-parts/svg/call-fill', null, array(
                                    'sizeSvgX' => '20',
                                    'sizeSvgY' => '20',
                                    'class' => 'text-secondary'
                                )); 
                                ?>
                            </span>
                            <span class="text-sm font-bold leading-none"><?= get_field('phone_number_label', 'option') ?: get_field('phone_number', 'option'); ?></span>
                        </a>
                    <?php endif; ?>

                    <!-- Email -->
                    <?php if (get_field('email', 'option')) : ?>
                        <a href="mailto:<?= esc_attr(get_field('email', 'option')); ?>"
                           class="flex items-center gap-4 p-4 rounded-2xl bg-white/5 border border-white/10 hover:bg-white/10 transition-all text-slate-200">
                            <span class="text-secondary">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M.05 3.555A2 2 0 0 1 2 2h12a2 2 0 0 1 1.95 1.555L8 8.414.05 3.555ZM0 4.697v7.104l5.803-3.558L0 4.697ZM6.761 8.83l-6.57 4.027A2 2 0 0 0 2 14h12a2 2 0 0 0 1.808-1.144l-6.57-4.027L8 9.586l-1.239-.757Zm3.436-.586L16 11.801V4.697l-5.803 3.546Z"/>
                                </svg>
                            </span>
                            <span class="text-sm font-bold leading-none"><?= get_field('email', 'option'); ?></span>
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Social links -->
                <div class="mt-4">
                    <?php
                    get_template_part('template-parts/social-media', null, array(
                        'class' => 'gap-3 justify-start',
                        'sizeSvgX' => '24',
                        'sizeSvgY' => '24',
                    ));
                    ?>
                </div>
            </div>

            <!-- Map box -->
            <div class="lg:col-span-7 h-[350px] lg:h-[420px] rounded-3xl overflow-hidden shadow-2xl border border-white/5 bg-slate-800 relative z-10" 
                 data-aos="flip-left" data-aos-duration="1000">
                <?= get_field('map', 'option'); ?>
            </div>

        </div>
    </div>
</section>

<!-- Gravity Form section -->
<section class="bg-slate-50 py-16 lg:py-24">
    <div class="container mx-auto px-6 max-w-xl text-start">
        <h5 class="text-2xl font-black text-slate-900 mb-6 text-center" data-aos="fade-left">با ما در ارتباط باشید</h5>
        <div data-aos="fade-up" data-aos-delay="100" class="bg-white border border-slate-200/80 rounded-3xl p-8 shadow-md">
            <?php echo do_shortcode('[gravityform id="' . get_field('form-id') . '" title="false" description="false" ajax="true"]') ?>
        </div>
    </div>
</section>
<?php get_footer(); ?>