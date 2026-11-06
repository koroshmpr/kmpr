<?php
/**
 * Smart Interactive Project & Architecture Configurator
 * Replaces boring static forms with an intelligent, highly engaging interactive scope builder.
 */
?>
<section id="smart-configurator" class="py-16 lg:py-24 bg-slate-950 text-white relative overflow-hidden select-none border-y border-slate-900"
         x-data="projectConfigurator()">
    <!-- Ambient subtle background glow -->
    <div class="absolute top-1/4 start-1/4 w-96 h-96 bg-secondary/15 rounded-full blur-[140px] pointer-events-none"></div>
    <div class="absolute bottom-10 end-1/4 w-96 h-96 bg-blue-600/15 rounded-full blur-[140px] pointer-events-none"></div>

    <div class="container mx-auto px-4 sm:px-6 relative z-10 max-w-5xl">
        <!-- Section Header -->
        <div class="text-center max-w-2xl mx-auto mb-12" data-aos="fade-up">
            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-secondary/15 border border-secondary/30 mb-4">
                <span class="w-2 h-2 rounded-full bg-secondary animate-pulse"></span>
                <span class="text-xs font-black uppercase text-secondary tracking-widest">پیکربندی هوشمند و بدون فرم خسته‌کننده</span>
            </div>
            <h2 class="text-3xl sm:text-4xl font-black text-white leading-tight">
                معماری و نیازهای پروژه خود را آنلاین انتخاب کنید
            </h2>
            <div class="h-1.5 w-16 bg-secondary mx-auto rounded-full mt-4 mb-3"></div>
            <p class="text-slate-400 text-sm sm:text-base leading-relaxed">
                بدون نیاز به نوشتن دستی فرم‌های طولانی؛ گزینه‌های مدنظرتان را علامت بزنید تا معماری پیشنهادی و خلاصه پروژه در یک ثانیه آماده شود.
            </p>
        </div>

        <!-- Main Configurator Card -->
        <div class="bg-slate-900/90 border border-slate-800 rounded-3xl p-6 sm:p-10 shadow-2xl backdrop-blur-xl relative overflow-hidden text-start" data-aos="zoom-in">
            
            <!-- Step Indicators -->
            <div class="flex items-center justify-between border-b border-slate-800 pb-6 mb-8 text-xs font-bold">
                <div class="flex items-center gap-2" :class="step >= 1 ? 'text-secondary' : 'text-slate-500'">
                    <span class="size-6 rounded-full flex items-center justify-center text-xs font-mono font-black"
                          :class="step >= 1 ? 'bg-secondary text-primary' : 'bg-slate-800 text-slate-400'">۱</span>
                    <span class="hidden sm:inline">نوع پروژه</span>
                </div>
                <div class="h-0.5 flex-1 mx-3" :class="step >= 2 ? 'bg-secondary/60' : 'bg-slate-800'"></div>
                <div class="flex items-center gap-2" :class="step >= 2 ? 'text-secondary' : 'text-slate-500'">
                    <span class="size-6 rounded-full flex items-center justify-center text-xs font-mono font-black"
                          :class="step >= 2 ? 'bg-secondary text-primary' : 'bg-slate-800 text-slate-400'">۲</span>
                    <span class="hidden sm:inline">قابلیت‌های فنی</span>
                </div>
                <div class="h-0.5 flex-1 mx-3" :class="step >= 3 ? 'bg-secondary/60' : 'bg-slate-800'"></div>
                <div class="flex items-center gap-2" :class="step >= 3 ? 'text-secondary' : 'text-slate-500'">
                    <span class="size-6 rounded-full flex items-center justify-center text-xs font-mono font-black"
                          :class="step >= 3 ? 'bg-secondary text-primary' : 'bg-slate-800 text-slate-400'">۳</span>
                    <span class="hidden sm:inline">پیشنهاد معماری و شروع</span>
                </div>
            </div>

            <!-- STEP 1: Project Type Selection -->
            <div x-show="step === 1" x-transition>
                <h3 class="text-lg sm:text-xl font-black text-white mb-2">۱. چه پلتفرمی مدنظر شماست؟</h3>
                <p class="text-xs sm:text-sm text-slate-400 mb-6">هدف اصلی خود را برای راه‌اندازی یا توسعه سیستم انتخاب کنید.</p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <template x-for="p in projectTypes" :key="p.id">
                        <div @click="selectedType = p.id; step = 2"
                             :class="selectedType === p.id ? 'border-secondary bg-secondary/10 ring-2 ring-secondary/30' : 'border-slate-800 bg-slate-950/60 hover:border-slate-700 hover:bg-slate-850'"
                             class="p-5 rounded-2xl border transition-all duration-200 cursor-pointer flex flex-col justify-between gap-3 group">
                            <div class="flex items-center justify-between">
                                <span class="text-2xl" x-text="p.icon"></span>
                                <span class="text-[10px] font-mono px-2 py-0.5 rounded-full bg-white/5 text-slate-400" x-text="p.badge"></span>
                            </div>
                            <div>
                                <h4 class="text-sm sm:text-base font-black text-white group-hover:text-secondary transition-colors" x-text="p.title"></h4>
                                <p class="text-xs text-slate-400 mt-1 leading-relaxed" x-text="p.desc"></p>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- STEP 2: Key Requirements & Features -->
            <div x-show="step === 2" x-transition>
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-lg sm:text-xl font-black text-white">۲. چه امکانات و اولویت‌هایی برای شما حیاتی است؟</h3>
                    <button @click="step = 1" class="text-xs text-slate-400 hover:text-white flex items-center gap-1 cursor-pointer">
                        <span>مرحله قبل</span>
                        <span>&rarr;</span>
                    </button>
                </div>
                <p class="text-xs sm:text-sm text-slate-400 mb-6">ویژگی‌های مورد نظر را انتخاب کنید (امکان انتخاب چندگانه):</p>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3.5 mb-8">
                    <template x-for="feat in featureList" :key="feat.id">
                        <div @click="toggleFeature(feat.id)"
                             :class="selectedFeatures.includes(feat.id) ? 'border-secondary bg-secondary/10' : 'border-slate-800 bg-slate-950/60 hover:border-slate-700'"
                             class="p-4 rounded-2xl border transition-all duration-200 cursor-pointer flex items-start gap-3">
                            <div class="w-5 h-5 rounded-md mt-0.5 flex items-center justify-center shrink-0 border"
                                 :class="selectedFeatures.includes(feat.id) ? 'bg-secondary border-secondary text-primary' : 'border-slate-700 bg-slate-900'">
                                <svg x-show="selectedFeatures.includes(feat.id)" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                            </div>
                            <div>
                                <span class="text-xs sm:text-sm font-black text-white block" x-text="feat.title"></span>
                                <span class="text-[11px] text-slate-400 leading-snug block mt-0.5" x-text="feat.desc"></span>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="flex justify-between items-center pt-4 border-t border-slate-800">
                    <button @click="step = 1" class="px-5 py-2.5 rounded-full text-xs font-bold bg-slate-800 text-slate-300 hover:bg-slate-700 transition-colors cursor-pointer">
                        مرحله قبل
                    </button>
                    <button @click="step = 3" class="px-7 py-3 rounded-full text-xs sm:text-sm font-black bg-secondary hover:bg-secondary-hover text-white transition-all duration-300 shadow-lg shadow-secondary/30 cursor-pointer flex items-center gap-2">
                        <span>مشاهده پیشنهاد معماری و خروجی هوشمند</span>
                        <span>&larr;</span>
                    </button>
                </div>
            </div>

            <!-- STEP 3: Smart Output & Direct 1-Click Action -->
            <div x-show="step === 3" x-transition>
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-emerald-400 animate-ping"></span>
                        <h3 class="text-lg sm:text-xl font-black text-white">۳. پیشنهاد استراتژیک و مشخصات فنی سیستم شما</h3>
                    </div>
                    <button @click="step = 2" class="text-xs text-slate-400 hover:text-white flex items-center gap-1 cursor-pointer">
                        <span>ویرایش مشخصات</span>
                        <span>&rarr;</span>
                    </button>
                </div>

                <!-- Analysis Summary Box -->
                <div class="bg-slate-950/80 rounded-2xl p-6 border border-slate-800 mb-8 grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="flex flex-col gap-1.5 border-b md:border-b-0 md:border-e border-slate-800/80 pb-4 md:pb-0 md:pe-4">
                        <span class="text-xs text-slate-400 font-bold">معماری نرم‌افزاری پیشنهادی:</span>
                        <span class="text-sm font-black text-secondary font-mono" x-text="getArchitectureRecommendation()"></span>
                        <span class="text-[11px] text-slate-500">طراحی اختصاصی ۱۰۰٪ بدون استفاده از قالب‌های سنگین</span>
                    </div>

                    <div class="flex flex-col gap-1.5 border-b md:border-b-0 md:border-e border-slate-800/80 pb-4 md:pb-0 md:pe-4">
                        <span class="text-xs text-slate-400 font-bold">پشته فناوری (Tech Stack):</span>
                        <div class="flex flex-wrap gap-1.5 mt-1">
                            <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-white/5 text-slate-300 border border-white/10">Tailwind CSS v4</span>
                            <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-white/5 text-slate-300 border border-white/10">Custom WP Core</span>
                            <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-white/5 text-slate-300 border border-white/10">Alpine.js</span>
                            <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-white/5 text-slate-300 border border-white/10">llms.txt AI</span>
                        </div>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <span class="text-xs text-slate-400 font-bold">تعهدات و تضمین مهندسی:</span>
                        <span class="text-xs font-bold text-emerald-400 flex items-center gap-1.5">
                            <span>✓</span>
                            <span>سرعت لود زیر یک ثانیه (100 Core Web Vitals)</span>
                        </span>
                        <span class="text-xs font-bold text-slate-300 flex items-center gap-1.5">
                            <span>✓</span>
                            <span>تحویل بدون باگ + پشتیبانی مستقیم دولوپر</span>
                        </span>
                    </div>
                </div>

                <!-- 1-Click Direct Action -->
                <div class="bg-gradient-to-r from-secondary/15 via-slate-800/60 to-slate-900 border border-secondary/30 rounded-2xl p-6 flex flex-col sm:flex-row items-center justify-between gap-6">
                    <div class="text-start">
                        <h4 class="text-base sm:text-lg font-black text-white mb-1">آماده‌اید پروژه خود را با بالاترین کیفیت شروع کنید؟</h4>
                        <p class="text-xs text-slate-300 leading-relaxed">
                            خلاصه نیازمندی‌های بالا در قالب یک پیام ساختاریافته آماده شده است. با یک کلیک مستقیماً با خود توسعه‌دهنده در ارتباط باشید:
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3 shrink-0 w-full sm:w-auto">
                        <!-- WhatsApp Action -->
                        <a :href="getWhatsAppLink()"
                           target="_blank"
                           class="w-full sm:w-auto text-center px-6 py-3.5 rounded-2xl bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xs sm:text-sm flex items-center justify-center gap-2 transition-all shadow-lg shadow-emerald-600/30">
                            <span>ارسال خلاصه در واتساپ</span>
                            <span>&larr;</span>
                        </a>

                        <!-- Telegram Action -->
                        <a :href="getTelegramLink()"
                           target="_blank"
                           class="w-full sm:w-auto text-center px-6 py-3.5 rounded-2xl bg-sky-600 hover:bg-sky-500 text-white font-black text-xs sm:text-sm flex items-center justify-center gap-2 transition-all shadow-lg shadow-sky-600/30">
                            <span>تلگرام</span>
                        </a>
                    </div>
                </div>

            </div>

        </div>
    </div>
</section>

<script>
function projectConfigurator() {
    return {
        step: 1,
        selectedType: 'ecommerce',
        selectedFeatures: ['speed', 'ai', 'seo'],
        projectTypes: [
            {
                id: 'ecommerce',
                title: 'فروشگاه اینترنتی فوق‌سریع و اختصاصی',
                desc: 'سیستم خرید تک‌مرحله‌ای، بدون کرش، سرعت لود زیر ۱ ثانیه و هماهنگ با درگاه‌های بانکی و انبارداری.',
                badge: 'High Conversion',
                icon: '🛒'
            },
            {
                id: 'web_app',
                title: 'وب‌اپلیکیشن و سامانه تحت وب (SaaS / Portal)',
                desc: 'فرانت‌اند مدرن با Next.js و React، ارتباط بلادرنگ REST API، داشبورد اختصاصی مشتریان و احراز هویت.',
                badge: 'Custom Architecture',
                icon: '⚡'
            },
            {
                id: 'corporate',
                title: 'وب‌سایت شرکتی برندینگ و B2B',
                desc: 'طراحی بصری لوکس، پرستیژ بین‌المللی برای معرفی خدمات و سازمان با بالاترین استانداردهای سئو تکنیکال.',
                badge: 'Brand Authority',
                icon: '🏢'
            },
            {
                id: 'redesign',
                title: 'ریدیزاین اساسی، رفع باگ و ارتقای پرفورمنس',
                desc: 'مهاجرت از قالب‌های سنگین المنتور به کدنویسی تمیز اختصاصی با تضمین نمره ۱۰۰ لایت‌هاوس.',
                badge: 'Speed Guarantee',
                icon: '🚀'
            }
        ],
        featureList: [
            { id: 'speed', title: 'تضمین لود زیر ۱ ثانیه (Core Web Vitals)', desc: 'کدهای بهینه Tailwind v4 بدون پلاگین‌های زائد' },
            { id: 'ai', title: 'بهینه‌سازی برای هوش مصنوعی (llms.txt)', desc: 'ایندکسینگ خودکار توسط مدل‌های ChatGPT و Claude' },
            { id: 'seo', title: 'سئو تکنیکال و معماری عمیق اسکیما', desc: 'داده‌های ساختاریافته برای بیشترین رتبه در نتایج جستجو' },
            { id: 'pwa', title: 'طراحی واکنش‌گرا و تجربه شبیه اپ (PWA)', desc: 'نویگیشن روان و کارکرد بی‌نقص روی موبایل و تبلت' },
            { id: 'api', title: 'اتصال اختصاصی به سیستم‌های مالی / CRM', desc: 'همگام‌سازی اتوماتیک سفارشات، مشتریان و کالاها' },
            { id: 'security', title: 'امنیت پیشرفته و معماری ضد نفوذ', desc: 'کدهای استاندارد با رعایت سخت‌گیرانه‌ترین تدابیر امنیتی' }
        ],

        toggleFeature(id) {
            if (this.selectedFeatures.includes(id)) {
                this.selectedFeatures = this.selectedFeatures.filter(f => f !== id);
            } else {
                this.selectedFeatures.push(id);
            }
        },

        getArchitectureRecommendation() {
            if (this.selectedType === 'web_app') return 'Next.js 15 Full-Stack + Headless API Engine';
            if (this.selectedType === 'ecommerce') return 'Custom High-Velocity E-Commerce Engine';
            if (this.selectedType === 'redesign') return 'Clean Vanilla Architecture (0 Bloatware)';
            return 'Bespoke Modern Enterprise Architecture';
        },

        getFormattedSummary() {
            const typeObj = this.projectTypes.find(p => p.id === this.selectedType);
            const typeName = typeObj ? typeObj.title : 'پروژه اختصاصی';
            const features = this.selectedFeatures.join(', ');
            return `سلام، من از بخش پیکربندی هوشمند سایت، پروژه با مشخصات زیر را انتخاب کردم:\n• نوع پروژه: ${typeName}\n• معماری پیشنهادی: ${this.getArchitectureRecommendation()}\n• اولویت‌های کلیدی: ${features}\nلطفاً در خصوص برآورد هزینه و مراحل شروع راهنمایی بفرمایید.`;
        },

        getWhatsAppLink() {
            const text = encodeURIComponent(this.getFormattedSummary());
            const phone = '09358088118';
            return `https://wa.me/98${phone.substring(1)}?text=${text}`;
        },

        getTelegramLink() {
            const text = encodeURIComponent(this.getFormattedSummary());
            return `https://t.me/korosh_mpr?text=${text}`;
        }
    };
}
</script>
