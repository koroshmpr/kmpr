<?php
/**
 * Services list for homepage
 */
?>
<section id="services" class="py-16 lg:py-24 bg-slate-50 border-b border-slate-200/60">
    <div class="container mx-auto px-4 sm:px-6">
        <div class="text-center max-w-xl mx-auto mb-14">
            <span class="text-xs font-black uppercase text-secondary tracking-widest block mb-2">تخصص و مهارت‌ها</span>
            <h2 class="text-3xl lg:text-4xl font-black text-slate-900 mb-4">خدمات تخصصی ما</h2>
            <div class="h-1.5 w-16 bg-secondary mx-auto rounded-full mb-3"></div>
            <p class="text-slate-500 text-sm sm:text-base leading-relaxed">
                ارائه برترین راهکارهای دیجیتال، طراحی وب و توسعه سیستم‌های مدرن متناسب با کسب‌وکار شما
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
               class="inline-flex items-center gap-2 bg-white hover:bg-slate-100 text-slate-800 hover:text-secondary border border-slate-200/80 font-bold text-sm px-6 py-3 rounded-full transition-all duration-300 shadow-2xs hover:shadow-sm">
                <span>مشاهده کلیه خدمات</span>
                <span>&larr;</span>
            </a>
        </div>
    </div>
</section>
