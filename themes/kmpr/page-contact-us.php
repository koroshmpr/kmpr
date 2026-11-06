<?php
/** 
 * Template Name: Contact Us Page 
 */
get_header(); ?>

<!-- Contact Hero Header -->
<section class="relative min-h-[45vh] lg:min-h-[55vh] flex items-center justify-center overflow-hidden bg-slate-950 text-white select-none py-16 lg:py-20 border-b border-slate-900">
    <!-- Subtle Grid Overlay -->
    <div class="absolute inset-0 bg-[radial-gradient(#334155_1px,transparent_1px)] [background-size:24px_24px] opacity-25 pointer-events-none"></div>

    <!-- Ambient Glow Spheres -->
    <div class="absolute top-1/4 start-1/4 w-96 h-96 bg-secondary/15 rounded-full blur-[140px] pointer-events-none"></div>
    <div class="absolute bottom-10 end-1/4 w-96 h-96 bg-blue-600/15 rounded-full blur-[140px] pointer-events-none"></div>

    <div class="container mx-auto px-4 sm:px-6 relative z-10 max-w-4xl text-center">
        <!-- Live Online Status Badge -->
        <div data-aos="fade-down" data-aos-duration="600"
             class="inline-flex items-center gap-2.5 px-4 py-2 rounded-full bg-white/[0.06] border border-white/10 backdrop-blur-xl mb-6 shadow-lg shadow-black/20">
            <span class="relative flex w-2.5 h-2.5 shrink-0">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full w-2.5 h-2.5 bg-emerald-500"></span>
            </span>
            <span class="text-xs font-black text-slate-200 tracking-wide">هم‌اکنون آنلاین و پاسخگوی پیام‌ها هستیم (پاسخگویی زیر ۳۰ دقیقه)</span>
        </div>

        <h1 data-aos="fade-up" data-aos-duration="800"
            class="text-3xl sm:text-5xl lg:text-6xl font-black text-white leading-tight mb-4 tracking-tight">
            راه‌های ارتباط مستقیم با تیم مهندسی
        </h1>

        <p data-aos="fade-up" data-aos-duration="800" data-aos-delay="150"
           class="text-sm sm:text-base lg:text-lg text-slate-300 max-w-2xl mx-auto leading-relaxed">
            بدون تشریفات پیچیده اداری؛ مستقیماً با متخصصان فنی پروژه در ارتباط باشید و ایده‌هایتان را به سیستم‌های کارآمد تبدیل کنید.
        </p>
    </div>
</section>

<!-- Direct Communication Hub -->
<section class="py-16 lg:py-24 bg-slate-50 border-b border-slate-200/80 text-start">
    <div class="container mx-auto px-4 sm:px-6 max-w-5xl">
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-16">
            <!-- Channel 1: WhatsApp -->
            <a href="https://wa.me/989358088118?text=<?= urlencode('سلام، مایل به مشاوره فنی برای شروع پروژه هستم.'); ?>"
               target="_blank"
               class="p-6 rounded-3xl bg-white border border-slate-200/80 hover:border-emerald-500/50 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between gap-4 group">
                <div class="flex items-center justify-between">
                    <span class="w-12 h-12 rounded-2xl bg-emerald-500/15 text-emerald-600 flex items-center justify-center text-xl font-bold group-hover:scale-110 transition-transform">
                        💬
                    </span>
                    <span class="text-[10px] font-mono px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-600 font-bold">Fast Reply</span>
                </div>
                <div>
                    <h3 class="text-base font-black text-slate-900 group-hover:text-emerald-600 transition-colors">واتساپ (WhatsApp)</h3>
                    <p class="text-xs text-slate-500 mt-1 leading-relaxed">چت مستقیم، ارسال فایل و مشاوره آنلاین</p>
                </div>
                <div class="flex items-center justify-between border-t border-slate-100 pt-3 text-xs font-bold text-emerald-600">
                    <span>ارسال پیام</span>
                    <span>&larr;</span>
                </div>
            </a>

            <!-- Channel 2: Telegram -->
            <a href="https://t.me/korosh_mpr"
               target="_blank"
               class="p-6 rounded-3xl bg-white border border-slate-200/80 hover:border-sky-500/50 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between gap-4 group">
                <div class="flex items-center justify-between">
                    <span class="w-12 h-12 rounded-2xl bg-sky-500/15 text-sky-600 flex items-center justify-center text-xl font-bold group-hover:scale-110 transition-transform">
                        ✈️
                    </span>
                    <span class="text-[10px] font-mono px-2 py-0.5 rounded-full bg-sky-50 text-sky-600 font-bold">Direct DM</span>
                </div>
                <div>
                    <h3 class="text-base font-black text-slate-900 group-hover:text-sky-600 transition-colors">تلگرام (Telegram)</h3>
                    <p class="text-xs text-slate-500 mt-1 leading-relaxed">ارتباط امن، پیگیری تسک‌ها و هماهنگی</p>
                </div>
                <div class="flex items-center justify-between border-t border-slate-100 pt-3 text-xs font-bold text-sky-600">
                    <span>پیام در تلگرام</span>
                    <span>&larr;</span>
                </div>
            </a>

            <!-- Channel 3: Direct Phone Call -->
            <?php 
            $phone = get_field('phone', 'option') ?: '09358088118';
            $phone_label = get_field('phone_number_label', 'option') ?: $phone;
            ?>
            <a href="tel:<?= esc_attr($phone); ?>"
               class="p-6 rounded-3xl bg-white border border-slate-200/80 hover:border-secondary/50 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between gap-4 group">
                <div class="flex items-center justify-between">
                    <span class="w-12 h-12 rounded-2xl bg-secondary/15 text-secondary flex items-center justify-center text-xl font-bold group-hover:scale-110 transition-transform">
                        📞
                    </span>
                    <span class="text-[10px] font-mono px-2 py-0.5 rounded-full bg-secondary/10 text-secondary font-bold">Call Now</span>
                </div>
                <div>
                    <h3 class="text-base font-black text-slate-900 group-hover:text-secondary transition-colors">تماس تلفنی</h3>
                    <p class="text-xs text-slate-500 mt-1 leading-relaxed"><?= esc_html($phone_label); ?></p>
                </div>
                <div class="flex items-center justify-between border-t border-slate-100 pt-3 text-xs font-bold text-secondary">
                    <span>برقراری تماس</span>
                    <span>&larr;</span>
                </div>
            </a>

            <!-- Channel 4: Email -->
            <?php $email = get_field('email', 'option') ?: 'korosh.mpr@gmail.com'; ?>
            <a href="mailto:<?= esc_attr($email); ?>"
               class="p-6 rounded-3xl bg-white border border-slate-200/80 hover:border-purple-500/50 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between gap-4 group">
                <div class="flex items-center justify-between">
                    <span class="w-12 h-12 rounded-2xl bg-purple-500/15 text-purple-600 flex items-center justify-center text-xl font-bold group-hover:scale-110 transition-transform">
                        ✉️
                    </span>
                    <span class="text-[10px] font-mono px-2 py-0.5 rounded-full bg-purple-50 text-purple-600 font-bold">Official</span>
                </div>
                <div>
                    <h3 class="text-base font-black text-slate-900 group-hover:text-purple-600 transition-colors">ایمیل رسمی</h3>
                    <p class="text-xs text-slate-500 mt-1 leading-relaxed truncate"><?= esc_html($email); ?></p>
                </div>
                <div class="flex items-center justify-between border-t border-slate-100 pt-3 text-xs font-bold text-purple-600">
                    <span>ارسال ایمیل</span>
                    <span>&larr;</span>
                </div>
            </a>
        </div>

        <!-- Location & FAQ Row -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            
            <!-- Working Process / FAQ for new clients -->
            <div class="lg:col-span-7 bg-white border border-slate-200/80 rounded-3xl p-6 sm:p-8 shadow-sm">
                <h3 class="text-lg font-black text-slate-900 mb-6 flex items-center gap-2">
                    <span class="text-secondary">❓</span>
                    <span>سوالات پرتکرار قبل از شروع همکاری</span>
                </h3>

                <div class="flex flex-col gap-4 text-xs sm:text-sm">
                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100">
                        <strong class="text-slate-900 font-black block mb-1">۱. فرآیند شروع پروژه چگونه است؟</strong>
                        <p class="text-slate-600 leading-relaxed">
                            پس از بررسی اولیه نیازمندی‌های شما در واتساپ یا تماس تلفنی، یک جلسه آنلاین برای تعیین دقیق ساختار فنی و ددلاین برگزار می‌شود و بلافاصله فاز اول طراحی پیاده خواهد شد.
                        </p>
                    </div>

                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100">
                        <strong class="text-slate-900 font-black block mb-1">۲. آیا بعد از تحویل پروژه پشتیبانی دارید؟</strong>
                        <p class="text-slate-600 leading-relaxed">
                            بله؛ تمامی پروژه‌ها شامل گارانتی رفع باگ، مانیتورینگ عملکرد و پشتیبانی مستقیم مهندسی بدون واسطه هستند.
                        </p>
                    </div>

                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100">
                        <strong class="text-slate-900 font-black block mb-1">۳. آیا سورس‌کد کامل پروژه به کارفرما تحویل داده می‌شود؟</strong>
                        <p class="text-slate-600 leading-relaxed">
                            ۱۰۰٪؛ تمام کدهای نوشته‌شده تمیز، ماژولار و به همراه دسترسی کامل ریپازیتوری بدون هیچ‌گونه قفل‌گذاری به شما تحویل داده می‌شود.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Map & Office Info -->
            <div class="lg:col-span-5 flex flex-col gap-6">
                <!-- Address Card -->
                <?php if (get_field('address', 'option')) : ?>
                    <div class="bg-white border border-slate-200/80 rounded-3xl p-6 shadow-sm">
                        <span class="text-xs font-bold text-slate-400 block mb-1">نشانی دفتر:</span>
                        <p class="text-sm font-bold text-slate-800 leading-relaxed">
                            <?= esc_html(get_field('address', 'option')); ?>
                        </p>
                    </div>
                <?php endif; ?>

                <!-- Map Frame -->
                <?php $map = get_field('map', 'option'); ?>
                <?php if ($map) : ?>
                    <div class="h-64 sm:h-72 rounded-3xl overflow-hidden shadow-md border border-slate-200/80 bg-slate-100">
                        <?= $map; ?>
                    </div>
                <?php endif; ?>

                <!-- Social Links -->
                <div class="bg-slate-900 text-white rounded-3xl p-6 flex flex-col gap-3">
                    <span class="text-xs text-slate-400 font-bold">شبکه‌های اجتماعی ما:</span>
                    <?php
                    get_template_part('template-parts/social-media', null, array(
                        'class'    => 'gap-3 justify-start',
                        'sizeSvgX' => '22',
                        'sizeSvgY' => '22',
                    ));
                    ?>
                </div>
            </div>

        </div>

    </div>
</section>

<!-- Smart Project Configurator at Bottom of Contact Page -->
<?php get_template_part('template-parts/services/smart-configurator'); ?>

<?php get_footer(); ?>
