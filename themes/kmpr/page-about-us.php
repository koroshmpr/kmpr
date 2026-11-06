<?php
/** 
 * Template Name: About Us Page - Personal Brand: Korosh Mpr
 */
get_header(); 
$korosh_photo = get_template_directory_uri() . '/public/images/korosh-mpr.webp';
?>

<!-- About Hero Header -->
<section class="relative min-h-[60vh] lg:min-h-[70vh] flex items-center justify-center overflow-hidden bg-slate-950 text-white select-none py-20 border-b border-white/10">
    <!-- Subtle Grid Overlay -->
    <div class="absolute inset-0 bg-[radial-gradient(#334155_1px,transparent_1px)] [background-size:24px_24px] opacity-20 pointer-events-none"></div>

    <!-- Ambient Glow Spheres -->
    <div class="absolute top-1/4 start-1/4 w-96 h-96 bg-secondary/15 rounded-full blur-[140px] pointer-events-none"></div>
    <div class="absolute bottom-10 end-1/4 w-96 h-96 bg-blue-600/10 rounded-full blur-[140px] pointer-events-none"></div>

    <div class="container mx-auto px-4 sm:px-6 relative z-10 max-w-6xl">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
            
            <!-- Right Column: Personal Manifesto -->
            <div class="lg:col-span-7 flex flex-col items-center lg:items-start text-center lg:text-start gap-6">
                <!-- Live Badge -->
                <div data-aos="fade-down" data-aos-duration="600"
                     class="inline-flex items-center gap-2.5 px-4 py-2 rounded-full bg-white/[0.04] border border-white/10 backdrop-blur-xl">
                    <span class="w-2.5 h-2.5 rounded-full bg-secondary animate-pulse"></span>
                    <span class="text-xs font-black text-slate-200 tracking-wide">مسیر حرفه‌ای، فلسفه کدنویسی و استانداردهای من</span>
                </div>

                <h1 data-aos="fade-up" data-aos-duration="800"
                    class="text-3xl sm:text-5xl lg:text-6xl font-black text-white leading-tight tracking-tight">
                    مهندسی سیستم‌های وب بر پایه
                    <span class="text-transparent bg-clip-text bg-gradient-to-l from-amber-400 via-secondary to-amber-200">
                        کد تمیز و پرفورمنس
                    </span>
                </h1>

                <div data-aos="fade-up" data-aos-duration="800" data-aos-delay="150"
                     class="text-slate-300 text-sm sm:text-base lg:text-lg leading-relaxed font-medium space-y-3">
                    <p>
                        من <strong class="text-white font-extrabold">کوروش محمدپور</strong> هستم؛ معمار نرم‌افزار و برنامه‌نویس وب با بیش از ۷ سال تجربه در طراحی و توسعه پلتفرم‌های با مقیاس بالا.
                    </p>
                    <p>
                        باور عمیق من این است که یک وب‌سایت مدرن نباید زیر بار قالب‌های آماده، افزونه‌های حجیم و صفحه‌سازهای کند فلج شود. در هر پروژه، بالاترین اولویت را بر سرعت بارگذاری زیر یک ثانیه، امنیت پایدار و قابلیت فهم آسان توسط موتورهای هوش مصنوعی می‌گذارم.
                    </p>
                </div>
            </div>

            <!-- Left Column: Portrait -->
            <div class="lg:col-span-5 flex justify-center" data-aos="fade-left" data-aos-duration="800" data-aos-delay="200">
                <div class="relative w-full max-w-sm group">
                    <div class="absolute -inset-3 bg-gradient-to-tr from-secondary/25 via-blue-600/15 to-transparent rounded-3xl blur-2xl opacity-60 pointer-events-none"></div>
                    <div class="relative rounded-3xl overflow-hidden border border-white/15 bg-slate-900 shadow-2xl">
                        <img class="w-full h-auto aspect-square object-cover object-top filter grayscale contrast-110 transition-all duration-700 group-hover:grayscale-0 group-hover:contrast-105"
                             src="<?= esc_url($korosh_photo); ?>"
                             alt="کوروش محمدپور | توسعه‌دهنده وب">
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-transparent to-transparent pointer-events-none"></div>
                        <div class="absolute bottom-4 inset-x-4 p-3 rounded-2xl bg-slate-950/80 backdrop-blur-md border border-white/10 text-center">
                            <span class="block text-xs font-black text-white">کوروش محمدپور</span>
                            <span class="text-[10px] text-slate-400 font-mono">Senior Software Architect</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- Core Values & Philosophy Bento Section -->
<section class="py-16 lg:py-24 bg-slate-950 border-b border-white/10 text-start">
    <div class="container mx-auto px-4 sm:px-6 max-w-5xl">
        <div class="text-center max-w-2xl mx-auto mb-14" data-aos="fade-up">
            <span class="text-xs font-black uppercase text-secondary tracking-widest block mb-2">اصول کاری من</span>
            <h2 class="text-2xl sm:text-3xl lg:text-4xl font-black text-white mb-3">چرا همکاری مستقیم با من متمایز است؟</h2>
            <div class="h-1.5 w-16 bg-secondary mx-auto rounded-full mb-3"></div>
            <p class="text-slate-400 text-xs sm:text-sm leading-relaxed">
                تعهداتی که در هر خط کد و در تک‌تک مراحل پروژه به آن وفادار می‌مانم.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Pillar 1 -->
            <div class="p-8 rounded-3xl bg-white/[0.02] hover:bg-white/[0.05] border border-white/10 flex flex-col justify-between gap-4 hover:border-secondary/40 hover:shadow-2xl transition-all duration-300 group backdrop-blur-sm" data-aos="fade-up">
                <div class="flex items-center justify-between">
                    <span class="w-12 h-12 rounded-2xl bg-secondary/15 text-secondary flex items-center justify-center text-xl font-bold">
                        ⚡
                    </span>
                    <span class="text-xs font-mono font-bold text-slate-400">01 / PERFORMANCE</span>
                </div>
                <div>
                    <h3 class="text-lg font-black text-white group-hover:text-secondary transition-colors mb-2">سرعت زیر یک ثانیه و وسواس در پرفورمنس</h3>
                    <p class="text-xs sm:text-sm text-slate-400 leading-relaxed">
                        هر میلی‌ثانیه برای من و مشتریان شما اهمیت دارد. هیچ فریمورک اضافی یا اسکریپت سنگینی را بدون دلیل موجه وارد پروژه نمی‌کنم تا بالاترین نرخ تبدیل و رتبه گوگل حاصل شود.
                    </p>
                </div>
            </div>

            <!-- Pillar 2 -->
            <div class="p-8 rounded-3xl bg-white/[0.02] hover:bg-white/[0.05] border border-white/10 flex flex-col justify-between gap-4 hover:border-secondary/40 hover:shadow-2xl transition-all duration-300 group backdrop-blur-sm" data-aos="fade-up" data-aos-delay="100">
                <div class="flex items-center justify-between">
                    <span class="w-12 h-12 rounded-2xl bg-secondary/15 text-secondary flex items-center justify-center text-xl font-bold">
                        💎
                    </span>
                    <span class="text-xs font-mono font-bold text-slate-400">02 / CLEAN ARCHITECTURE</span>
                </div>
                <div>
                    <h3 class="text-lg font-black text-white group-hover:text-secondary transition-colors mb-2">کدنویسی اختصاصی بدون Bloatware</h3>
                    <p class="text-xs sm:text-sm text-slate-400 leading-relaxed">
                        به جای قالب‌های چندمنظوره آماده، تمام بخش‌ها را با کامپوننت‌های سبک، Tailwind CSS v4 و ساختار استاندارد وردپرس می‌نویسم؛ بدون وابستگی به افزونه‌های غیرضروری.
                    </p>
                </div>
            </div>

            <!-- Pillar 3 -->
            <div class="p-8 rounded-3xl bg-white/[0.02] hover:bg-white/[0.05] border border-white/10 flex flex-col justify-between gap-4 hover:border-secondary/40 hover:shadow-2xl transition-all duration-300 group backdrop-blur-sm" data-aos="fade-up" data-aos-delay="200">
                <div class="flex items-center justify-between">
                    <span class="w-12 h-12 rounded-2xl bg-secondary/15 text-secondary flex items-center justify-center text-xl font-bold">
                        🤖
                    </span>
                    <span class="text-xs font-mono font-bold text-slate-400">03 / AI & FUTURE-READY</span>
                </div>
                <div>
                    <h3 class="text-lg font-black text-white group-hover:text-secondary transition-colors mb-2">معماری سازگار با هوش مصنوعی (AI-Native)</h3>
                    <p class="text-xs sm:text-sm text-slate-400 leading-relaxed">
                        سیستم‌های توسعه‌داده‌شده توسط من مجهز به فایل‌های استاندارد llms.txt و اسکیما گراف عمیق هستند تا مدل‌های هوش مصنوعی (ChatGPT, Claude, Perplexity) محتوای شما را به دقت ارجاع دهند.
                    </p>
                </div>
            </div>

            <!-- Pillar 4 -->
            <div class="p-8 rounded-3xl bg-white/[0.02] hover:bg-white/[0.05] border border-white/10 flex flex-col justify-between gap-4 hover:border-secondary/40 hover:shadow-2xl transition-all duration-300 group backdrop-blur-sm" data-aos="fade-up" data-aos-delay="300">
                <div class="flex items-center justify-between">
                    <span class="w-12 h-12 rounded-2xl bg-secondary/15 text-secondary flex items-center justify-center text-xl font-bold">
                        🎯
                    </span>
                    <span class="text-xs font-mono font-bold text-slate-400">04 / DIRECT TRANSPARENCY</span>
                </div>
                <div>
                    <h3 class="text-lg font-black text-white group-hover:text-secondary transition-colors mb-2">همکاری مستقیم، چابک و بدون واسطه</h3>
                    <p class="text-xs sm:text-sm text-slate-400 leading-relaxed">
                        شما مستقیماً با معمار فنی پروژه طرف هستید؛ نه منشی، بازاریاب یا مدیر میانی. هر نیاز، چالش یا تغییری با درک عمیق مهندسی و در سریع‌ترین زمان ممکن پاسخ داده می‌شود.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Stats & Numbers Section -->
<section class="bg-slate-950 text-white py-16 lg:py-24 border-b border-white/10 relative overflow-hidden select-none">
    <div class="container mx-auto px-4 sm:px-6 relative z-10 max-w-5xl">
        <div class="text-center max-w-2xl mx-auto mb-14" data-aos="fade-up">
            <span class="text-xs font-black uppercase text-secondary tracking-widest block mb-2">دستاوردها</span>
            <h2 class="text-2xl sm:text-3xl lg:text-4xl font-black text-white mb-3">
                آمار و شاخص‌های کیفی مهندسی من
            </h2>
            <div class="h-1.5 w-16 bg-secondary mx-auto rounded-full mb-3"></div>
            <p class="text-slate-400 text-xs sm:text-sm leading-relaxed">
                شاخص‌هایی که نشان‌دهنده کیفیت و بازدهی سیستم‌های توسعه‌داده‌شده هستند.
            </p>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
            <div class="flex flex-col items-center p-6 bg-white/[0.02] border border-white/10 rounded-3xl hover:border-secondary/40 transition-all">
                <span class="text-3xl sm:text-4xl font-black text-secondary font-mono mb-2">100/100</span>
                <span class="text-slate-300 text-xs font-bold text-center">امتیاز Core Web Vitals</span>
            </div>
            <div class="flex flex-col items-center p-6 bg-white/[0.02] border border-white/10 rounded-3xl hover:border-secondary/40 transition-all">
                <span class="text-3xl sm:text-4xl font-black text-secondary font-mono mb-2">&lt; 0.8s</span>
                <span class="text-slate-300 text-xs font-bold text-center">میانگین زمان بارگذاری</span>
            </div>
            <div class="flex flex-col items-center p-6 bg-white/[0.02] border border-white/10 rounded-3xl hover:border-secondary/40 transition-all">
                <span class="text-3xl sm:text-4xl font-black text-secondary font-mono mb-2">+۷ سال</span>
                <span class="text-slate-300 text-xs font-bold text-center">سابقه توسعه وب اختصاصی</span>
            </div>
            <div class="flex flex-col items-center p-6 bg-white/[0.02] border border-white/10 rounded-3xl hover:border-secondary/40 transition-all">
                <span class="text-3xl sm:text-4xl font-black text-secondary font-mono mb-2">0%</span>
                <span class="text-slate-300 text-xs font-bold text-center">کدهای زائد و صفحه‌ساز</span>
            </div>
        </div>
    </div>
</section>

<!-- Call to action -->
<section class="py-16 lg:py-20 bg-slate-950 text-white text-center">
    <div class="container mx-auto px-4 sm:px-6 max-w-3xl">
        <h3 class="text-2xl sm:text-3xl font-black text-white mb-4">آماده‌اید پروژه خود را با استانداردهای مدرن آغاز کنیم؟</h3>
        <p class="text-slate-400 text-sm leading-relaxed mb-8">
            پروژه شما می‌تواند به یک نمونه‌کار شاخص، سریع و سودآور در وب تبدیل شود.
        </p>
        <div class="flex flex-wrap items-center justify-center gap-4">
            <a href="<?= esc_url(get_post_type_archive_link('services') ?: home_url('/services')); ?>#smart-configurator"
               class="bg-secondary hover:bg-secondary-hover text-white font-black text-sm px-8 py-3.5 rounded-full transition-all shadow-xl shadow-secondary/25 hover:scale-105 active:scale-95">
                پیکربندی هوشمند پروژه
            </a>
            <a href="<?= esc_url(home_url('/contact-us') ?: home_url('/contact')); ?>"
               class="bg-white/10 hover:bg-white/15 text-white font-bold text-sm px-8 py-3.5 rounded-full transition-all border border-white/20">
                ارتباط مستقیم با من
            </a>
        </div>
    </div>
</section>

<?php get_footer(); ?>
