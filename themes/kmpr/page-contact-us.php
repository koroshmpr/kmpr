<?php
/** 
 * Template Name: Contact Us Page - Personal Brand: Korosh Mpr
 */
get_header(); ?>

<!-- Contact Hero Header -->
<section class="relative min-h-[45vh] lg:min-h-[55vh] flex items-center justify-center overflow-hidden bg-slate-950 text-white select-none py-16 lg:py-20 border-b border-white/10">
    <!-- Subtle Grid Overlay -->
    <div class="absolute inset-0 bg-[radial-gradient(#334155_1px,transparent_1px)] [background-size:24px_24px] opacity-20 pointer-events-none"></div>

    <!-- Ambient Glow Spheres -->
    <div class="absolute top-1/4 start-1/4 w-96 h-96 bg-secondary/15 rounded-full blur-[140px] pointer-events-none"></div>
    <div class="absolute bottom-10 end-1/4 w-96 h-96 bg-blue-600/10 rounded-full blur-[140px] pointer-events-none"></div>

    <div class="container mx-auto px-4 sm:px-6 relative z-10 max-w-4xl text-center">
        <!-- Live Online Status Badge -->
        <div data-aos="fade-down" data-aos-duration="600"
             class="inline-flex items-center gap-2.5 px-4 py-2 rounded-full bg-white/[0.04] border border-white/10 backdrop-blur-xl mb-6 shadow-lg shadow-black/20">
            <span class="relative flex w-2.5 h-2.5 shrink-0">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full w-2.5 h-2.5 bg-emerald-500"></span>
            </span>
            <span class="text-xs font-black text-slate-200 tracking-wide">هم‌اکنون آنلاین و آماده گفتگو درباره پروژه شما (پاسخگویی سریع)</span>
        </div>

        <h1 data-aos="fade-up" data-aos-duration="800"
            class="text-3xl sm:text-5xl lg:text-6xl font-black text-white leading-tight mb-4 tracking-tight">
            راه‌های ارتباط مستقیم با من
        </h1>

        <p data-aos="fade-up" data-aos-duration="800" data-aos-delay="150"
           class="text-sm sm:text-base lg:text-lg text-slate-300 max-w-2xl mx-auto leading-relaxed">
            بدون تشریفات پیچیده و واسطه‌ها؛ مستقیماً با من در ارتباط باشید تا ایده‌ها و اهداف تجاری‌تان را به یک سیستم وب قدرتمند و پرسرعت تبدیل کنیم.
        </p>
    </div>
</section>

<!-- Direct Communication Hub -->
<section class="py-16 lg:py-24 bg-slate-950 border-b border-white/10 text-start">
    <div class="container mx-auto px-4 sm:px-6 max-w-5xl">
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-16">
            <!-- Channel 1: WhatsApp -->
            <a href="https://wa.me/989358088118?text=<?= urlencode('سلام کوروش عزیز، مایل به گفتگو درباره شروع یک پروژه وب اختصاصی هستم.'); ?>"
               target="_blank"
               class="p-6 rounded-3xl bg-white/[0.02] hover:bg-white/[0.05] border border-white/10 hover:border-emerald-500/50 hover:shadow-2xl hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between gap-4 group backdrop-blur-sm">
                <div class="flex items-center justify-between">
                    <span class="w-12 h-12 rounded-2xl bg-emerald-500/15 text-emerald-400 flex items-center justify-center text-xl font-bold group-hover:scale-110 transition-transform">
                        💬
                    </span>
                    <span class="text-[10px] font-mono px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 font-bold">Fast Reply</span>
                </div>
                <div>
                    <h3 class="text-base font-black text-white group-hover:text-emerald-400 transition-colors">واتساپ (WhatsApp)</h3>
                    <p class="text-xs text-slate-400 mt-1 leading-relaxed">چت مستقیم، ارسال فایل و مشاوره آنلاین</p>
                </div>
                <div class="flex items-center justify-between border-t border-white/10 pt-3 text-xs font-bold text-emerald-400">
                    <span>ارسال پیام</span>
                    <span>&larr;</span>
                </div>
            </a>

            <!-- Channel 2: Telegram -->
            <a href="https://t.me/korosh_mpr"
               target="_blank"
               class="p-6 rounded-3xl bg-white/[0.02] hover:bg-white/[0.05] border border-white/10 hover:border-sky-500/50 hover:shadow-2xl hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between gap-4 group backdrop-blur-sm">
                <div class="flex items-center justify-between">
                    <span class="w-12 h-12 rounded-2xl bg-sky-500/15 text-sky-400 flex items-center justify-center text-xl font-bold group-hover:scale-110 transition-transform">
                        ✈️
                    </span>
                    <span class="text-[10px] font-mono px-2 py-0.5 rounded-full bg-sky-500/10 text-sky-400 border border-sky-500/20 font-bold">Direct DM</span>
                </div>
                <div>
                    <h3 class="text-base font-black text-white group-hover:text-sky-400 transition-colors">تلگرام (Telegram)</h3>
                    <p class="text-xs text-slate-400 mt-1 leading-relaxed">ارتباط سریع، پیگیری تسک‌ها و هماهنگی</p>
                </div>
                <div class="flex items-center justify-between border-t border-white/10 pt-3 text-xs font-bold text-sky-400">
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
               class="p-6 rounded-3xl bg-white/[0.02] hover:bg-white/[0.05] border border-white/10 hover:border-secondary/50 hover:shadow-2xl hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between gap-4 group backdrop-blur-sm">
                <div class="flex items-center justify-between">
                    <span class="w-12 h-12 rounded-2xl bg-secondary/15 text-secondary flex items-center justify-center text-xl font-bold group-hover:scale-110 transition-transform">
                        📞
                    </span>
                    <span class="text-[10px] font-mono px-2 py-0.5 rounded-full bg-secondary/10 text-secondary border border-secondary/20 font-bold">Call Now</span>
                </div>
                <div>
                    <h3 class="text-base font-black text-white group-hover:text-secondary transition-colors">تماس مستقیم</h3>
                    <p class="text-xs text-slate-400 mt-1 leading-relaxed"><?= esc_html($phone_label); ?></p>
                </div>
                <div class="flex items-center justify-between border-t border-white/10 pt-3 text-xs font-bold text-secondary">
                    <span>برقراری تماس</span>
                    <span>&larr;</span>
                </div>
            </a>

            <!-- Channel 4: Email -->
            <?php $email = get_field('email', 'option') ?: 'korosh.mpr@gmail.com'; ?>
            <a href="mailto:<?= esc_attr($email); ?>"
               class="p-6 rounded-3xl bg-white/[0.02] hover:bg-white/[0.05] border border-white/10 hover:border-purple-500/50 hover:shadow-2xl hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between gap-4 group backdrop-blur-sm">
                <div class="flex items-center justify-between">
                    <span class="w-12 h-12 rounded-2xl bg-purple-500/15 text-purple-400 flex items-center justify-center text-xl font-bold group-hover:scale-110 transition-transform">
                        ✉️
                    </span>
                    <span class="text-[10px] font-mono px-2 py-0.5 rounded-full bg-purple-500/10 text-purple-400 border border-purple-500/20 font-bold">Email</span>
                </div>
                <div>
                    <h3 class="text-base font-black text-white group-hover:text-purple-400 transition-colors">ایمیل من</h3>
                    <p class="text-xs text-slate-400 mt-1 leading-relaxed truncate"><?= esc_html($email); ?></p>
                </div>
                <div class="flex items-center justify-between border-t border-white/10 pt-3 text-xs font-bold text-purple-400">
                    <span>ارسال ایمیل</span>
                    <span>&larr;</span>
                </div>
            </a>
        </div>

        <!-- Working Process / FAQ for new clients -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            
            <div class="lg:col-span-7 bg-white/[0.02] border border-white/10 rounded-3xl p-6 sm:p-8 shadow-xl backdrop-blur-sm">
                <h3 class="text-lg font-black text-white mb-6 flex items-center gap-2">
                    <span class="text-secondary">❓</span>
                    <span>پاسخ به سوالات متداول قبل از شروع همکاری</span>
                </h3>

                <div class="flex flex-col gap-4 text-xs sm:text-sm">
                    <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/10">
                        <strong class="text-white font-black block mb-1">۱. فرآیند هماهنگی و شروع پروژه چگونه است؟</strong>
                        <p class="text-slate-400 leading-relaxed">
                            پس از ارسال پیام در واتساپ یا تلگرام، نیازهای پروژه را بررسی می‌کنیم و در صورت لزوم یک جلسه آنلاین برای تعیین معماری دقیق سیستم و ددلاین برگزار می‌کنیم.
                        </p>
                    </div>

                    <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/10">
                        <strong class="text-white font-black block mb-1">۲. وضعیت تحویل سورس‌کد و مالکیت نرم‌افزار چگونه است؟</strong>
                        <p class="text-slate-400 leading-relaxed">
                            تمام سورس‌کد پروژه با رعایت اصول ماژولار و به همراه گیت ریپازیتوری کامل و بدون هیچ‌گونه لایسنس محدودکننده به نام خود شما تحویل داده می‌شود.
                        </p>
                    </div>

                    <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/10">
                        <strong class="text-white font-black block mb-1">۳. آیا پروژه‌ها شامل پشتیبانی و گارانتی کارکرد هستند؟</strong>
                        <p class="text-slate-400 leading-relaxed">
                            بله؛ تمامی پروژه‌ها دارای گارانتی رفع ایراد احتمالی و تعهد مانیتورینگ عملکرد فنی به طور مستقیم هستند.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Social Links & Network -->
            <div class="lg:col-span-5 flex flex-col gap-6">
                <div class="bg-white/[0.02] border border-white/10 rounded-3xl p-6 shadow-xl backdrop-blur-sm flex flex-col gap-4">
                    <span class="text-xs text-slate-400 font-bold uppercase tracking-wider">شبکه‌ها و پروفایل‌های من:</span>
                    <?php
                    get_template_part('template-parts/social-media', null, array(
                        'class'    => 'gap-3 justify-start',
                        'sizeSvgX' => '22',
                        'sizeSvgY' => '22',
                    ));
                    ?>
                </div>

                <!-- Engineering Guarantee Box -->
                <div class="bg-gradient-to-br from-secondary/15 via-white/[0.02] to-transparent border border-secondary/30 rounded-3xl p-6 shadow-xl">
                    <div class="flex items-center gap-3 mb-2">
                        <span class="text-secondary text-lg">🛡️</span>
                        <h4 class="text-sm font-black text-white">تضمین کیفیت مهندسی</h4>
                    </div>
                    <p class="text-xs text-slate-300 leading-relaxed">
                        کدنویسی تمیز، پرفورمنس ۱۰۰/۱۰۰ و سازگاری با استانداردهای مدرن جهانی، تعهد اخلاقی و حرفه‌ای من در تمام پروژه‌هاست.
                    </p>
                </div>
            </div>

        </div>

    </div>
</section>

<!-- Smart Project Configurator at Bottom of Contact Page -->
<?php get_template_part('template-parts/services/smart-configurator'); ?>

<?php get_footer(); ?>
