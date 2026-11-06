<?php
/** 
 * Template Name: About Us Page 
 */
get_header(); ?>

<!-- About Hero Header -->
<section class="relative min-h-[50vh] lg:min-h-[60vh] flex items-center justify-center overflow-hidden bg-slate-950 text-white select-none py-20 border-b border-slate-900">
    <!-- Subtle Grid Overlay -->
    <div class="absolute inset-0 bg-[radial-gradient(#334155_1px,transparent_1px)] [background-size:24px_24px] opacity-25 pointer-events-none"></div>

    <!-- Ambient Glow Spheres -->
    <div class="absolute top-1/4 start-1/4 w-96 h-96 bg-secondary/15 rounded-full blur-[140px] pointer-events-none"></div>
    <div class="absolute bottom-10 end-1/4 w-96 h-96 bg-blue-600/15 rounded-full blur-[140px] pointer-events-none"></div>

    <div class="container mx-auto px-4 sm:px-6 relative z-10 max-w-4xl text-center">
        <!-- Live Badge -->
        <div data-aos="fade-down" data-aos-duration="600"
             class="inline-flex items-center gap-2.5 px-4 py-2 rounded-full bg-white/[0.06] border border-white/10 backdrop-blur-xl mb-6">
            <span class="w-2.5 h-2.5 rounded-full bg-secondary animate-pulse"></span>
            <span class="text-xs font-black text-slate-200 tracking-wide">داستان، ماموریت و استانداردهای مهندسی ما</span>
        </div>

        <h1 data-aos="fade-up" data-aos-duration="800"
            class="text-3xl sm:text-5xl lg:text-6xl font-black text-white leading-tight mb-6 tracking-tight">
            <?= esc_html(get_field('aboutus-title') ?: 'مهندسی وب بر پایه تخصص، نه قالب‌های آماده'); ?>
        </h1>

        <div data-aos="fade-up" data-aos-duration="800" data-aos-delay="150"
             class="text-slate-300 text-sm sm:text-base lg:text-lg leading-relaxed text-center font-medium max-w-3xl mx-auto">
            <?= wp_kses_post(get_field('aboutus-content') ?: 'ما تیمی از متخصصان توسعه نرم‌افزار هستیم که معتقدیم وب‌سایت‌های مدرن نباید زیر بار افزونه‌های سنگین و صفحه‌سازهای کند له شوند. ما سیستم‌هایی را طراحی و پیاده‌سازی می‌کنیم که علاوه بر ظاهر چشم‌نواز و مدرن، سرعتی باورنکردنی، کدی تمیز و بالاترین رتبه در موتورهای جستجو را برای بیزینس شما به ارمغان می‌آورند.'); ?>
        </div>
    </div>
</section>

<!-- Core Values & Philosophy Bento Section -->
<section class="py-16 lg:py-24 bg-white border-b border-slate-200/80 text-start">
    <div class="container mx-auto px-4 sm:px-6 max-w-5xl">
        <div class="text-center max-w-2xl mx-auto mb-14" data-aos="fade-up">
            <span class="text-xs font-black uppercase text-secondary tracking-widest block mb-2">اصول کاری ما</span>
            <h2 class="text-2xl sm:text-3xl lg:text-4xl font-black text-slate-900 mb-3">چرا کار با ما حس متفاوتی دارد؟</h2>
            <div class="h-1.5 w-16 bg-secondary mx-auto rounded-full mb-3"></div>
            <p class="text-slate-500 text-xs sm:text-sm leading-relaxed">
                تعهداتی که در هر خط کد و در تک‌تک مراحل پروژه به آن وفادار می‌مانیم.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Pillar 1 -->
            <div class="p-8 rounded-3xl bg-slate-50 border border-slate-200/80 flex flex-col justify-between gap-4 hover:border-secondary/40 hover:shadow-xl transition-all duration-300 group" data-aos="fade-up">
                <div class="flex items-center justify-between">
                    <span class="w-12 h-12 rounded-2xl bg-secondary/15 text-secondary flex items-center justify-center text-xl font-bold">
                        ⚡
                    </span>
                    <span class="text-xs font-mono font-bold text-slate-400">01 / PERFORMANCE</span>
                </div>
                <div>
                    <h3 class="text-lg font-black text-slate-900 group-hover:text-secondary transition-colors mb-2">سرعت زیر یک ثانیه و وسواس در لود</h3>
                    <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                        هر میلی‌ثانیه برای ما و مشتریان شما اهمیت دارد. ما هیچ فریمورک اضافی یا اسکریپت سنگینی را بدون دلیل موجه وارد پروژه نمی‌کنیم تا بالاترین نرخ تبدیل حاصل شود.
                    </p>
                </div>
            </div>

            <!-- Pillar 2 -->
            <div class="p-8 rounded-3xl bg-slate-50 border border-slate-200/80 flex flex-col justify-between gap-4 hover:border-secondary/40 hover:shadow-xl transition-all duration-300 group" data-aos="fade-up" data-aos-delay="100">
                <div class="flex items-center justify-between">
                    <span class="w-12 h-12 rounded-2xl bg-sky-500/15 text-sky-500 flex items-center justify-center text-xl font-bold">
                        🛡️
                    </span>
                    <span class="text-xs font-mono font-bold text-slate-400">02 / ARCHITECTURE</span>
                </div>
                <div>
                    <h3 class="text-lg font-black text-slate-900 group-hover:text-secondary transition-colors mb-2">معماری تمیز، بدون باگ و قابل ارتقا</h3>
                    <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                        کدها ماژولار و طبق استانداردهای بین‌المللی نوشته می‌شوند؛ بنابراین بعد از تحویل، کسب‌وکار شما وابسته به یک شخص نبوده و به سادگی قابل توسعه و مقیاس‌پذیری است.
                    </p>
                </div>
            </div>

            <!-- Pillar 3 -->
            <div class="p-8 rounded-3xl bg-slate-50 border border-slate-200/80 flex flex-col justify-between gap-4 hover:border-secondary/40 hover:shadow-xl transition-all duration-300 group" data-aos="fade-up" data-aos-delay="150">
                <div class="flex items-center justify-between">
                    <span class="w-12 h-12 rounded-2xl bg-emerald-500/15 text-emerald-500 flex items-center justify-center text-xl font-bold">
                        🤖
                    </span>
                    <span class="text-xs font-mono font-bold text-slate-400">03 / AI & FUTURE</span>
                </div>
                <div>
                    <h3 class="text-lg font-black text-slate-900 group-hover:text-secondary transition-colors mb-2">پیشگام در استانداردهای هوش مصنوعی</h3>
                    <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                        ما از ساختارهای نوین مانند فایل رسمی llms.txt و اسکیماهای تو در تو استفاده می‌کنیم تا چت‌بات‌های هوش مصنوعی بتوانند خدمات و محصولات شما را به درستی ایندکس و معرفی کنند.
                    </p>
                </div>
            </div>

            <!-- Pillar 4 -->
            <div class="p-8 rounded-3xl bg-slate-50 border border-slate-200/80 flex flex-col justify-between gap-4 hover:border-secondary/40 hover:shadow-xl transition-all duration-300 group" data-aos="fade-up" data-aos-delay="200">
                <div class="flex items-center justify-between">
                    <span class="w-12 h-12 rounded-2xl bg-purple-500/15 text-purple-500 flex items-center justify-center text-xl font-bold">
                        🤝
                    </span>
                    <span class="text-xs font-mono font-bold text-slate-400">04 / TRANSPARENCY</span>
                </div>
                <div>
                    <h3 class="text-lg font-black text-slate-900 group-hover:text-secondary transition-colors mb-2">ارتباط مستقیم با مهندس نرم‌افزار</h3>
                    <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                        شما مستقیماً با شخص برنامه‌نویس و طراح سیستم صحبت می‌کنید؛ بدون اتلاف وقت، بدون واسطه و با ارائه دقیق‌ترین راهکارهای فنی در کوتاه‌ترین زمان.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Statistics Section -->
<section class="bg-slate-950 text-white py-16 lg:py-24 border-b border-slate-900 relative overflow-hidden select-none">
    <div class="container mx-auto px-4 sm:px-6 relative z-10 max-w-5xl">
        <div class="text-center max-w-2xl mx-auto mb-14" data-aos="fade-up">
            <span class="text-xs font-black uppercase text-secondary tracking-widest block mb-2">دستاوردها</span>
            <h2 class="text-2xl sm:text-3xl lg:text-4xl font-black text-white mb-3">
                <?= esc_html(get_field('title_statistics') ?: 'آمار و شاخص‌های کیفی'); ?>
            </h2>
            <div class="h-1.5 w-16 bg-secondary mx-auto rounded-full mb-3"></div>
            <p class="text-slate-400 text-xs sm:text-sm leading-relaxed">
                <?= esc_html(get_field('content_statistics') ?: 'اعدادی که نشان‌دهنده کیفیت و بازدهی سیستم‌های توسعه داده شده توسط ما هستند.'); ?>
            </p>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
            <?php if (have_rows('statistics_list')): ?>
                <?php while (have_rows('statistics_list')): the_row(); ?>
                    <div class="flex flex-col items-center justify-center p-6 bg-slate-900/90 border border-slate-800 rounded-3xl shadow-lg hover:border-secondary/40 transition-all duration-300"
                         data-aos="zoom-in">
                        <span class="text-3xl sm:text-4xl lg:text-5xl font-black text-secondary font-mono leading-none mb-2">
                            <?= esc_html(get_sub_field('list_statistics_value')); ?>
                        </span>
                        <p class="text-slate-300 text-xs sm:text-sm font-bold text-center">
                            <?= esc_html(get_sub_field('list_statistics_title')); ?>
                        </p>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <!-- Default elite stats -->
                <div class="flex flex-col items-center p-6 bg-slate-900/90 border border-slate-800 rounded-3xl">
                    <span class="text-3xl sm:text-4xl font-black text-secondary font-mono mb-2">100/100</span>
                    <span class="text-slate-300 text-xs font-bold">امتیاز رسمی Core Web Vitals</span>
                </div>
                <div class="flex flex-col items-center p-6 bg-slate-900/90 border border-slate-800 rounded-3xl">
                    <span class="text-3xl sm:text-4xl font-black text-secondary font-mono mb-2">&lt; 0.8s</span>
                    <span class="text-slate-300 text-xs font-bold">میانگین زمان بارگذاری</span>
                </div>
                <div class="flex flex-col items-center p-6 bg-slate-900/90 border border-slate-800 rounded-3xl">
                    <span class="text-3xl sm:text-4xl font-black text-secondary font-mono mb-2">100%</span>
                    <span class="text-slate-300 text-xs font-bold">توسعه اختصاصی بدون المنتور</span>
                </div>
                <div class="flex flex-col items-center p-6 bg-slate-900/90 border border-slate-800 rounded-3xl">
                    <span class="text-3xl sm:text-4xl font-black text-secondary font-mono mb-2">24/7</span>
                    <span class="text-slate-300 text-xs font-bold">پشتیبانی و امنیت مداوم</span>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Call to action -->
<section class="py-16 lg:py-20 bg-slate-900 text-white text-center">
    <div class="container mx-auto px-4 sm:px-6 max-w-3xl">
        <h3 class="text-2xl sm:text-3xl font-black text-white mb-4">آیا آماده شروع همکاری با ما هستید؟</h3>
        <p class="text-slate-400 text-sm leading-relaxed mb-8">
            پروژه شما می‌تواند به یک نمونه‌کار برجسته و فوق‌سریع در صنعت شما تبدیل شود.
        </p>
        <div class="flex flex-wrap items-center justify-center gap-4">
            <a href="<?= esc_url(get_post_type_archive_link('services') ?: home_url('/services')); ?>#smart-configurator"
               class="bg-secondary hover:bg-secondary-hover text-white font-black text-sm px-8 py-3.5 rounded-full transition-all shadow-xl shadow-secondary/30 hover:scale-105 active:scale-95">
                پیکربندی آنلاین پروژه
            </a>
            <a href="<?= esc_url(home_url('/contact-us') ?: home_url('/contact')); ?>"
               class="bg-white/10 hover:bg-white/15 text-white font-bold text-sm px-8 py-3.5 rounded-full transition-all border border-white/20">
                تماس و راه‌های ارتباطی
            </a>
        </div>
    </div>
</section>

<?php get_footer(); ?>
