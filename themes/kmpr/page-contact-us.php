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
                    <span class="w-12 h-12 rounded-2xl bg-emerald-500/15 text-emerald-400 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <svg class="size-6" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0 0 12.04 2m.01 1.67c2.2 0 4.26.86 5.82 2.42a8.225 8.225 0 0 1 2.41 5.83c0 4.54-3.7 8.24-8.24 8.24-1.48 0-2.93-.39-4.19-1.15l-.3-.18-3.12.82.83-3.04-.2-.31a8.196 8.196 0 0 1-1.26-4.38c0-4.54 3.7-8.24 8.24-8.24m4.52 11.66c-.25-.13-1.47-.72-1.7-.81-.23-.08-.39-.13-.56.13-.17.25-.64.81-.79.97-.14.17-.29.19-.54.06-.25-.13-1.06-.39-2.02-1.24-.75-.67-1.25-1.49-1.4-1.74-.14-.25-.02-.39.11-.51.11-.11.25-.29.37-.43.13-.15.17-.25.25-.42.08-.17.04-.31-.02-.44-.06-.13-.56-1.34-.76-1.84-.2-.48-.41-.42-.56-.43h-.48c-.17 0-.44.06-.67.31-.23.25-.88.86-.88 2.1s.9 2.43 1.02 2.6c.13.17 1.77 2.7 4.28 3.79.6.26 1.07.41 1.43.53.6.19 1.15.16 1.58.1.48-.07 1.47-.6 1.68-1.18.21-.58.21-1.07.15-1.18-.06-.12-.22-.19-.47-.32"/>
                        </svg>
                    </span>
                    <span class="text-[10px] font-mono px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 font-bold">Fast Reply</span>
                </div>
                <div>
                    <h3 class="text-base font-black text-white group-hover:text-emerald-400 transition-colors">واتساپ (WhatsApp)</h3>
                    <p class="text-xs text-slate-400 mt-1 leading-relaxed">چت مستقیم، ارسال فایل و مشاوره آنلاین</p>
                </div>
                <div class="flex items-center justify-between border-t border-white/10 pt-3 text-xs font-bold text-emerald-400">
                    <span>ارسال پیام</span>
                    <svg class="size-4 rotate-180" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h10.638L10.23 5.29a.75.75 0 1 1 1.04-1.08l5.5 5.25a.75.75 0 0 1 0 1.08l-5.5 5.25a.75.75 0 1 1-1.04-1.08l4.158-3.96H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd" />
                    </svg>
                </div>
            </a>

            <!-- Channel 2: Telegram -->
            <a href="https://t.me/korosh_mpr"
               target="_blank"
               class="p-6 rounded-3xl bg-white/[0.02] hover:bg-white/[0.05] border border-white/10 hover:border-sky-500/50 hover:shadow-2xl hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between gap-4 group backdrop-blur-sm">
                <div class="flex items-center justify-between">
                    <span class="w-12 h-12 rounded-2xl bg-sky-500/15 text-sky-400 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <svg class="size-6" viewBox="0 0 24 24" fill="currentColor">
                            <path d="m20.665 3.717-17.73 6.837c-1.21.486-1.203 1.161-.222 1.462l4.552 1.42 10.532-6.645c.498-.303.953-.14.579.192l-8.533 7.701h-.002l-.313 4.673c.46 0 .663-.211.921-.46l2.211-2.15 4.599 3.397c.848.467 1.457.227 1.668-.785l3.019-14.228c.309-1.239-.473-1.8-1.282-1.434z"/>
                        </svg>
                    </span>
                    <span class="text-[10px] font-mono px-2 py-0.5 rounded-full bg-sky-500/10 text-sky-400 border border-sky-500/20 font-bold">Direct DM</span>
                </div>
                <div>
                    <h3 class="text-base font-black text-white group-hover:text-sky-400 transition-colors">تلگرام (Telegram)</h3>
                    <p class="text-xs text-slate-400 mt-1 leading-relaxed">ارتباط سریع، پیگیری تسک‌ها و هماهنگی</p>
                </div>
                <div class="flex items-center justify-between border-t border-white/10 pt-3 text-xs font-bold text-sky-400">
                    <span>پیام در تلگرام</span>
                    <svg class="size-4 rotate-180" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h10.638L10.23 5.29a.75.75 0 1 1 1.04-1.08l5.5 5.25a.75.75 0 0 1 0 1.08l-5.5 5.25a.75.75 0 1 1-1.04-1.08l4.158-3.96H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd" />
                    </svg>
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
                    <span class="w-12 h-12 rounded-2xl bg-secondary/15 text-secondary flex items-center justify-center group-hover:scale-110 transition-transform">
                        <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                        </svg>
                    </span>
                    <span class="text-[10px] font-mono px-2 py-0.5 rounded-full bg-secondary/10 text-secondary border border-secondary/20 font-bold">Call Now</span>
                </div>
                <div>
                    <h3 class="text-base font-black text-white group-hover:text-secondary transition-colors">تماس مستقیم</h3>
                    <p class="text-xs text-slate-400 mt-1 leading-relaxed font-mono"><?= esc_html($phone_label); ?></p>
                </div>
                <div class="flex items-center justify-between border-t border-white/10 pt-3 text-xs font-bold text-secondary">
                    <span>برقراری تماس</span>
                    <svg class="size-4 rotate-180" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h10.638L10.23 5.29a.75.75 0 1 1 1.04-1.08l5.5 5.25a.75.75 0 0 1 0 1.08l-5.5 5.25a.75.75 0 1 1-1.04-1.08l4.158-3.96H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd" />
                    </svg>
                </div>
            </a>

            <!-- Channel 4: Email -->
            <?php $email = get_field('email', 'option') ?: 'korosh.mpr@gmail.com'; ?>
            <a href="mailto:<?= esc_attr($email); ?>"
               class="p-6 rounded-3xl bg-white/[0.02] hover:bg-white/[0.05] border border-white/10 hover:border-purple-500/50 hover:shadow-2xl hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between gap-4 group backdrop-blur-sm">
                <div class="flex items-center justify-between">
                    <span class="w-12 h-12 rounded-2xl bg-purple-500/15 text-purple-400 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect width="20" height="16" x="2" y="4" rx="2"/>
                            <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
                        </svg>
                    </span>
                    <span class="text-[10px] font-mono px-2 py-0.5 rounded-full bg-purple-500/10 text-purple-400 border border-purple-500/20 font-bold">Email</span>
                </div>
                <div>
                    <h3 class="text-base font-black text-white group-hover:text-purple-400 transition-colors">ایمیل من</h3>
                    <p class="text-xs text-slate-400 mt-1 leading-relaxed truncate font-mono"><?= esc_html($email); ?></p>
                </div>
                <div class="flex items-center justify-between border-t border-white/10 pt-3 text-xs font-bold text-purple-400">
                    <span>ارسال ایمیل</span>
                    <svg class="size-4 rotate-180" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h10.638L10.23 5.29a.75.75 0 1 1 1.04-1.08l5.5 5.25a.75.75 0 0 1 0 1.08l-5.5 5.25a.75.75 0 1 1-1.04-1.08l4.158-3.96H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd" />
                    </svg>
                </div>
            </a>
        </div>

        <!-- Working Process / FAQ for new clients -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            
            <div class="lg:col-span-7 bg-white/[0.02] border border-white/10 rounded-3xl p-6 sm:p-8 shadow-xl backdrop-blur-sm">
                <h3 class="text-lg font-black text-white mb-6 flex items-center gap-2.5">
                    <span class="w-8 h-8 rounded-xl bg-secondary/15 text-secondary flex items-center justify-center shrink-0">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/>
                            <path d="M12 17h.01"/>
                        </svg>
                    </span>
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
                        <span class="w-8 h-8 rounded-xl bg-secondary/15 text-secondary flex items-center justify-center shrink-0">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                                <path d="m9 12 2 2 4-4"/>
                            </svg>
                        </span>
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
