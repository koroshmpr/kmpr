<?php
/** 
 * Template Name: Services Archive - Personal Brand: Korosh Mpr
 */
get_header(); ?>

<!-- Services Hero Header -->
<section class="relative min-h-[45vh] lg:min-h-[55vh] flex items-center justify-center overflow-hidden bg-slate-950 text-white select-none py-16 lg:py-24 border-b border-white/10">
    <!-- Subtle Grid Overlay -->
    <div class="absolute inset-0 bg-[radial-gradient(#334155_1px,transparent_1px)] [background-size:24px_24px] opacity-20 pointer-events-none"></div>

    <!-- Ambient Glow Spheres -->
    <div class="absolute top-1/4 start-1/4 w-96 h-96 bg-secondary/15 rounded-full blur-[140px] pointer-events-none"></div>
    <div class="absolute bottom-10 end-1/4 w-96 h-96 bg-blue-600/10 rounded-full blur-[140px] pointer-events-none"></div>

    <div class="container mx-auto px-4 sm:px-6 relative z-10 text-center">
        <!-- Live Availability Pill -->
        <div data-aos="fade-down" data-aos-duration="600"
             class="inline-flex items-center gap-2.5 px-4 py-2 rounded-full bg-white/[0.04] border border-white/10 backdrop-blur-xl mb-6">
            <span class="relative flex w-2.5 h-2.5 shrink-0">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full w-2.5 h-2.5 bg-emerald-500"></span>
            </span>
            <span class="text-xs font-black text-slate-200 tracking-wide">پذیرش پروژه‌های نرم‌افزاری و طراحی اختصاصی (۲۰۲۶)</span>
        </div>

        <h1 data-aos="fade-up" data-aos-duration="800"
            class="text-3xl sm:text-5xl lg:text-6xl font-black text-white leading-tight mb-4 tracking-tight">
            خدمات مهندسی نرم‌افزار، معماری وب و توسعه اختصاصی
        </h1>

        <p data-aos="fade-up" data-aos-duration="800" data-aos-delay="150"
           class="text-sm sm:text-base lg:text-lg text-slate-300 max-w-2xl mx-auto leading-relaxed">
            قالب‌های آماده یا افزونه‌های مخرب سرعت در کارهای من جایی ندارند؛ تخصص من پیاده‌سازی سیستم‌های فوق‌سریع، ایمن و متناسب با رشد واقعی کسب‌وکار شماست.
        </p>

        <div data-aos="fade-up" data-aos-duration="800" data-aos-delay="300"
             class="mt-8 flex flex-wrap items-center justify-center gap-4">
            <a href="#smart-configurator" 
               class="bg-secondary hover:bg-secondary-hover text-white font-black text-xs sm:text-sm px-7 py-3.5 rounded-full transition-all duration-300 shadow-xl shadow-secondary/30 hover:scale-105 active:scale-95">
                شروع پیکربندی هوشمند پروژه
            </a>
            <a href="#services-list" 
               class="bg-white/10 hover:bg-white/15 text-white font-bold text-xs sm:text-sm px-7 py-3.5 rounded-full transition-all duration-300 border border-white/20">
                مشاهده لیست کامل تخصص‌ها
            </a>
        </div>
    </div>
</section>

<!-- Smart Interactive Project Configurator -->
<?php get_template_part('template-parts/services/smart-configurator'); ?>

<!-- Services Catalog Grid -->
<section id="services-list" class="py-16 lg:py-24 bg-slate-950 border-b border-white/10 text-white">
    <div class="container mx-auto px-4 sm:px-6">
        <div class="text-center max-w-2xl mx-auto mb-14" data-aos="fade-up">
            <span class="text-xs font-black uppercase text-secondary tracking-widest block mb-2">لیست تخصص‌ها</span>
            <h2 class="text-2xl sm:text-3xl lg:text-4xl font-black text-white mb-3">حوزه‌های تخصصی فعالیت من</h2>
            <div class="h-1.5 w-16 bg-secondary mx-auto rounded-full mb-3"></div>
            <p class="text-slate-400 text-xs sm:text-sm leading-relaxed">
                هر پروژه با بالاترین استانداردهای مهندسی، معماری ماژولار و تضمین عملکرد ارائه می‌شود.
            </p>
        </div>

        <?php
        $args = array(
            'post_type'           => 'services',
            'post_status'         => 'publish',
            'order'               => 'DESC',
            'posts_per_page'      => -1,
            'ignore_sticky_posts' => true
        );
        $loop = new WP_Query($args);
        if ($loop->have_posts()) :
        ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php while ($loop->have_posts()) : $loop->the_post(); ?>
                    <div data-aos="fade-up" class="h-full">
                        <?php get_template_part('template-parts/services/services-card'); ?>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else : ?>
            <div class="py-16 text-center max-w-md mx-auto">
                <p class="font-bold text-lg text-slate-500">هیچ خدمتی در حال حاضر ثبت نشده است.</p>
            </div>
        <?php endif;
        wp_reset_postdata(); ?>
    </div>
</section>

<!-- Engineering Guarantees Banner -->
<section class="py-16 bg-slate-950 border-b border-white/10 text-start text-white">
    <div class="container mx-auto px-4 sm:px-6 max-w-5xl">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="p-6 rounded-3xl bg-white/[0.02] border border-white/10 flex flex-col gap-4">
                <span class="size-12 rounded-2xl bg-emerald-500/15 text-emerald-400 border border-emerald-500/25 flex items-center justify-center">
                    <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
                    </svg>
                </span>
                <div>
                    <h4 class="text-base font-black text-white mb-1">گارانتی عملکرد فوق‌سریع</h4>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        تضمین نمره بالای ۹۵ در تست‌های رسمی گوگل و لودینگ زیر یک ثانیه در تمامی صفحات.
                    </p>
                </div>
            </div>

            <div class="p-6 rounded-3xl bg-white/[0.02] border border-white/10 flex flex-col gap-4">
                <span class="size-12 rounded-2xl bg-secondary/15 text-secondary border border-secondary/25 flex items-center justify-center">
                    <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                        <path d="m9 12 2 2 4-4"/>
                    </svg>
                </span>
                <div>
                    <h4 class="text-base font-black text-white mb-1">مالکیت کامل سورس‌کد</h4>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        سورس‌کد تمیز و بدون وابستگی، بدون لایسنس‌های پولی اجباری و آماده برای توسعه تیم شما.
                    </p>
                </div>
            </div>

            <div class="p-6 rounded-3xl bg-white/[0.02] border border-white/10 flex flex-col gap-4">
                <span class="size-12 rounded-2xl bg-sky-500/15 text-sky-400 border border-sky-500/25 flex items-center justify-center">
                    <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                </span>
                <div>
                    <h4 class="text-base font-black text-white mb-1">همکاری مستقیم با دولوپر</h4>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        جلسات فنی و پیگیری مستقیم با خود من، بدون اتلاف وقت در لایه‌های بازاریابی و فروش.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php get_footer(); ?>
