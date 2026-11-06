<?php
/**
 * Services list for homepage - Personal Brand
 */
?>
<section id="services" class="py-16 lg:py-24 bg-slate-950 border-b border-white/10 relative overflow-hidden">
    <!-- Ambient subtle glow -->
    <div class="absolute top-1/2 end-0 w-96 h-96 bg-secondary/10 rounded-full blur-[140px] pointer-events-none"></div>

    <div class="container mx-auto px-4 sm:px-6 relative z-10">
        <div class="text-center max-w-2xl mx-auto mb-14">
            <span class="text-xs font-black uppercase text-secondary tracking-widest block mb-2">تخصص و خدمات من</span>
            <h2 class="text-3xl lg:text-4xl font-black text-white mb-4">حوزه‌های مهندسی و توسعه اختصاصی</h2>
            <div class="h-1.5 w-16 bg-secondary mx-auto rounded-full mb-4"></div>
            <p class="text-slate-400 text-sm sm:text-base leading-relaxed">
                توسعه وب‌اپلیکیشن‌ها، قالب‌های اختصاصی بدون کدهای اضافه و بهینه‌سازی سیستم‌ها با بالاترین استانداردهای معماری نرم‌افزار
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php
            $args = array(
                'post_type'           => 'services',
                'post_status'         => 'publish',
                'order'               => 'DESC',
                'posts_per_page'      => '3',
                'ignore_sticky_posts' => true
            );
            $loop = new WP_Query($args);
            if ($loop->have_posts()) :
                while ($loop->have_posts()) : $loop->the_post(); ?>
                    <div data-aos="zoom-out" data-aos-duration="600" class="h-full">
                        <?php get_template_part('template-parts/services/services-card'); ?>
                    </div>
                <?php endwhile;
            endif;
            wp_reset_postdata(); ?>
        </div>

        <!-- View all services CTA -->
        <div class="text-center mt-12">
            <a href="<?= esc_url(get_post_type_archive_link('services') ?: home_url('/services')); ?>" 
               class="inline-flex items-center gap-2 bg-white/[0.04] hover:bg-white/[0.08] text-slate-200 hover:text-white border border-white/10 font-bold text-sm px-7 py-3.5 rounded-full transition-all duration-300 shadow-lg hover:border-secondary/40">
                <span>مشاهده کلیه حوزه‌ها و کانفیگور هوشمند پروژه</span>
                <span>&larr;</span>
            </a>
        </div>
    </div>
</section>
