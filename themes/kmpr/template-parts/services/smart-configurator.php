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
            <div class="flex w-fit mx-auto items-center justify-between border-b border-slate-800 pb-6 mb-8 text-xs font-bold">
                <div @click="goToStep(1)" class="flex items-center gap-2 cursor-pointer transition-colors" :class="step >= 1 ? 'text-secondary' : 'text-slate-500'">
                    <span class="size-6 rounded-full flex items-center justify-center text-xs font-mono font-black"
                          :class="step >= 1 ? 'bg-secondary text-primary' : 'bg-slate-800 text-slate-400'">۱</span>
                    <span class="hidden sm:inline">نوع پروژه</span>
                </div>
                <div class="h-0.5 flex-1 w-10 mx-3" :class="step >= 2 ? 'bg-secondary/60' : 'bg-slate-800'"></div>
                <div @click="goToStep(2)" class="flex items-center gap-2 cursor-pointer transition-colors" :class="step >= 2 ? 'text-secondary' : 'text-slate-500'">
                    <span class="size-6 rounded-full flex items-center justify-center text-xs font-mono font-black"
                          :class="step >= 2 ? 'bg-secondary text-primary' : 'bg-slate-800 text-slate-400'">۲</span>
                    <span class="hidden sm:inline">قابلیت‌های فنی</span>
                </div>
                <div class="h-0.5 flex-1 mx-3" :class="step >= 3 ? 'bg-secondary/60' : 'bg-slate-800'"></div>
                <div @click="goToStep(3)" class="flex items-center gap-2 cursor-pointer transition-colors" :class="step >= 3 ? 'text-secondary' : 'text-slate-500'">
                    <span class="size-6 rounded-full flex items-center justify-center text-xs font-mono font-black"
                          :class="step >= 3 ? 'bg-secondary text-primary' : 'bg-slate-800 text-slate-400'">۳</span>
                    <span class="hidden sm:inline">پیشنهاد معماری و شروع</span>
                </div>
            </div>

            <!-- Slide Viewport with Smooth Animated Height & Directional Slide -->
            <div class="relative overflow-hidden w-full min-h-[460px] sm:min-h-[420px]">

                <!-- STEP 1: Project Type Selection -->
                <div x-show="step === 1"
                     x-transition:enter="transition-all duration-500 ease-[cubic-bezier(0.16,1,0.3,1)]"
                     x-transition:enter-start="opacity-0 translate-x-12"
                     x-transition:enter-end="opacity-100 translate-x-0"
                     x-transition:leave="transition-all duration-300 ease-in absolute inset-x-0 top-0 pointer-events-none"
                     x-transition:leave-start="opacity-100 translate-x-0"
                     x-transition:leave-end="opacity-0 -translate-x-12">
                    
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-lg sm:text-xl font-black text-white">۱. چه پلتفرمی مدنظر شماست؟</h3>
                        <span class="text-xs font-mono text-slate-400 hidden sm:inline">گام ۱ از ۳</span>
                    </div>
                    <p class="text-xs sm:text-sm text-slate-400 mb-6">هدف اصلی خود را برای راه‌اندازی یا توسعه سیستم انتخاب کنید.</p>

                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 lg:gap-5">
                        <template x-for="p in projectTypes" :key="p.id">
                            <div @click="selectedType = p.id; goToStep(2)"
                                 :class="selectedType === p.id 
                                    ? 'border-secondary bg-secondary/[0.08] ring-2 ring-secondary/40 shadow-xl shadow-secondary/10' 
                                    : 'border-slate-800/90 bg-slate-950/70 hover:border-slate-700 hover:bg-slate-850 hover:-translate-y-0.5'"
                                 class="p-6 sm:p-7 rounded-2xl border transition-all duration-300 cursor-pointer flex flex-col justify-between min-h-[170px] sm:min-h-[185px] group relative overflow-hidden">
                                
                                <div class="flex items-center justify-between w-full">
                                    <div class="size-12 rounded-2xl bg-secondary/15 text-secondary border border-secondary/25 flex items-center justify-center shrink-0 group-hover:scale-105 group-hover:bg-secondary/25 transition-all"
                                         x-html="p.svg">
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-[11px] font-mono px-3 py-1 rounded-full bg-white/5 border border-white/10 text-slate-300 font-semibold" x-text="p.badge"></span>
                                        <span x-show="selectedType === p.id" class="size-5 rounded-full bg-secondary text-primary flex items-center justify-center">
                                            <svg class="size-3" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd"/>
                                            </svg>
                                        </span>
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <h4 class="text-base sm:text-lg font-black text-white group-hover:text-secondary transition-colors" x-text="p.title"></h4>
                                    <p class="text-xs sm:text-sm text-slate-400 mt-1.5 leading-relaxed" x-text="p.desc"></p>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- STEP 2: Key Requirements & Features -->
                <div x-show="step === 2"
                     x-transition:enter="transition-all duration-500 ease-[cubic-bezier(0.16,1,0.3,1)]"
                     x-transition:enter-start="opacity-0 translate-x-12"
                     x-transition:enter-end="opacity-100 translate-x-0"
                     x-transition:leave="transition-all duration-300 ease-in absolute inset-x-0 top-0 pointer-events-none"
                     x-transition:leave-start="opacity-100 translate-x-0"
                     x-transition:leave-end="opacity-0 -translate-x-12">
                    
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-lg sm:text-xl font-black text-white">۲. چه امکانات و اولویت‌هایی برای شما حیاتی است؟</h3>
                        <button @click="goToStep(1)" class="text-xs text-slate-400 hover:text-white flex items-center gap-1.5 cursor-pointer transition-colors">
                            <span>مرحله قبل</span>
                            <svg class="size-3.5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h10.638L10.23 5.29a.75.75 0 1 1 1.04-1.08l5.5 5.25a.75.75 0 0 1 0 1.08l-5.5 5.25a.75.75 0 1 1-1.04-1.08l4.158-3.96H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                    <p class="text-xs sm:text-sm text-slate-400 mb-6">ویژگی‌های مورد نظر را انتخاب کنید (امکان انتخاب چندگانه):</p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3.5 sm:gap-4 mb-8">
                        <template x-for="feat in featureList" :key="feat.id">
                            <div @click="toggleFeature(feat.id)"
                                 :class="selectedFeatures.includes(feat.id) ? 'border-secondary bg-secondary/10 shadow-lg shadow-secondary/5' : 'border-slate-800 bg-slate-950/60 hover:border-slate-700'"
                                 class="p-4 sm:p-5 rounded-2xl border transition-all duration-200 cursor-pointer flex items-start gap-3.5 min-h-[96px]">
                                <div class="w-5 h-5 rounded-md mt-0.5 flex items-center justify-center shrink-0 border transition-all"
                                     :class="selectedFeatures.includes(feat.id) ? 'bg-secondary border-secondary text-primary' : 'border-slate-700 bg-slate-900'">
                                    <svg x-show="selectedFeatures.includes(feat.id)" class="size-3.5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div>
                                    <span class="text-xs sm:text-sm font-black text-white block" x-text="feat.title"></span>
                                    <span class="text-[11px] text-slate-400 leading-snug block mt-1" x-text="feat.desc"></span>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="flex justify-between items-center pt-4 border-t border-slate-800">
                        <button @click="goToStep(1)" class="px-5 py-2.5 rounded-full text-xs font-bold bg-slate-800 text-slate-300 hover:bg-slate-700 transition-colors cursor-pointer">
                            مرحله قبل
                        </button>
                        <button @click="goToStep(3)" class="px-7 py-3 rounded-full text-xs sm:text-sm font-black bg-secondary hover:bg-secondary-hover text-white transition-all duration-300 shadow-lg shadow-secondary/30 cursor-pointer flex items-center gap-2">
                            <span>مشاهده پیشنهاد معماری و خروجی هوشمند</span>
                            <svg class="size-4 rotate-180" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h10.638L10.23 5.29a.75.75 0 1 1 1.04-1.08l5.5 5.25a.75.75 0 0 1 0 1.08l-5.5 5.25a.75.75 0 1 1-1.04-1.08l4.158-3.96H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- STEP 3: Smart Output & Direct 1-Click Action -->
                <div x-show="step === 3"
                     x-transition:enter="transition-all duration-500 ease-[cubic-bezier(0.16,1,0.3,1)]"
                     x-transition:enter-start="opacity-0 translate-x-12"
                     x-transition:enter-end="opacity-100 translate-x-0"
                     x-transition:leave="transition-all duration-300 ease-in absolute inset-x-0 top-0 pointer-events-none"
                     x-transition:leave-start="opacity-100 translate-x-0"
                     x-transition:leave-end="opacity-0 -translate-x-12">
                    
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-emerald-400 animate-ping"></span>
                            <h3 class="text-lg sm:text-xl font-black text-white">۳. پیشنهاد استراتژیک و مشخصات فنی سیستم شما</h3>
                        </div>
                        <button @click="goToStep(2)" class="text-xs text-slate-400 hover:text-white flex items-center gap-1.5 cursor-pointer transition-colors">
                            <span>ویرایش مشخصات</span>
                            <svg class="size-3.5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h10.638L10.23 5.29a.75.75 0 1 1 1.04-1.08l5.5 5.25a.75.75 0 0 1 0 1.08l-5.5 5.25a.75.75 0 1 1-1.04-1.08l4.158-3.96H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>

                    <!-- Analysis Summary Box -->
                    <div class="bg-slate-950/80 rounded-2xl p-6 sm:p-7 border border-slate-800 mb-8 grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="flex flex-col gap-1.5 border-b md:border-b-0 md:border-e border-slate-800/80 pb-4 md:pb-0 md:pe-4">
                            <span class="text-xs text-slate-400 font-bold">معماری نرم‌افزاری پیشنهادی:</span>
                            <span class="text-sm font-black text-secondary font-mono" x-text="getArchitectureRecommendation()"></span>
                            <span class="text-[11px] text-slate-500">طراحی اختصاصی ۱۰۰٪ بدون استفاده از قالب‌های سنگین</span>
                        </div>

                        <div class="flex flex-col gap-1.5 border-b md:border-b-0 md:border-e border-slate-800/80 pb-4 md:pb-0 md:pe-4">
                            <span class="text-xs text-slate-400 font-bold">پشته فناوری و استانداردها:</span>
                            <div class="flex flex-wrap gap-1.5 mt-1">
                                <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-white/5 text-slate-300 border border-white/10">Zero Bloatware</span>
                                <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-white/5 text-slate-300 border border-white/10">Custom Architecture</span>
                                <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-white/5 text-slate-300 border border-white/10">High Concurrency</span>
                                <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-white/5 text-slate-300 border border-white/10">llms.txt Protocol</span>
                            </div>
                        </div>

                        <div class="flex flex-col gap-2">
                            <span class="text-xs text-slate-400 font-bold">تعهدات و تضمین مهندسی:</span>
                            <span class="text-xs font-bold text-emerald-400 flex items-center gap-2">
                                <svg class="size-4 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" />
                                </svg>
                                <span>سرعت لود زیر یک ثانیه (100 Core Web Vitals)</span>
                            </span>
                            <span class="text-xs font-bold text-slate-300 flex items-center gap-2">
                                <svg class="size-4 shrink-0 text-secondary" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" />
                                </svg>
                                <span>تحویل بدون باگ + پشتیبانی مستقیم دولوپر</span>
                            </span>
                        </div>
                    </div>

                    <!-- 1-Click Direct Action -->
                    <div class="bg-gradient-to-r from-secondary/15 via-slate-800/60 to-slate-900 border border-secondary/30 rounded-2xl p-6 sm:p-7 flex flex-col sm:flex-row items-center justify-between gap-6">
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
                               class="w-full sm:w-auto text-center px-6 py-3.5 rounded-2xl bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xs sm:text-sm flex items-center justify-center gap-2.5 transition-all shadow-lg shadow-emerald-600/30">
                                <svg class="size-4" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0 0 12.04 2m.01 1.67c2.2 0 4.26.86 5.82 2.42a8.225 8.225 0 0 1 2.41 5.83c0 4.54-3.7 8.24-8.24 8.24-1.48 0-2.93-.39-4.19-1.15l-.3-.18-3.12.82.83-3.04-.2-.31a8.196 8.196 0 0 1-1.26-4.38c0-4.54 3.7-8.24 8.24-8.24m4.52 11.66c-.25-.13-1.47-.72-1.7-.81-.23-.08-.39-.13-.56.13-.17.25-.64.81-.79.97-.14.17-.29.19-.54.06-.25-.13-1.06-.39-2.02-1.24-.75-.67-1.25-1.49-1.4-1.74-.14-.25-.02-.39.11-.51.11-.11.25-.29.37-.43.13-.15.17-.25.25-.42.08-.17.04-.31-.02-.44-.06-.13-.56-1.34-.76-1.84-.2-.48-.41-.42-.56-.43h-.48c-.17 0-.44.06-.67.31-.23.25-.88.86-.88 2.1s.9 2.43 1.02 2.6c.13.17 1.77 2.7 4.28 3.79.6.26 1.07.41 1.43.53.6.19 1.15.16 1.58.1.48-.07 1.47-.6 1.68-1.18.21-.58.21-1.07.15-1.18-.06-.12-.22-.19-.47-.32"/>
                                </svg>
                                <span>ارسال خلاصه در واتساپ</span>
                            </a>

                            <!-- Telegram Action -->
                            <a :href="getTelegramLink()"
                               target="_blank"
                               class="w-full sm:w-auto text-center px-6 py-3.5 rounded-2xl bg-sky-600 hover:bg-sky-500 text-white font-black text-xs sm:text-sm flex items-center justify-center gap-2.5 transition-all shadow-lg shadow-sky-600/30">
                                <svg class="size-4" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="m20.665 3.717-17.73 6.837c-1.21.486-1.203 1.161-.222 1.462l4.552 1.42 10.532-6.645c.498-.303.953-.14.579.192l-8.533 7.701h-.002l-.313 4.673c.46 0 .663-.211.921-.46l2.211-2.15 4.599 3.397c.848.467 1.457.227 1.668-.785l3.019-14.228c.309-1.239-.473-1.8-1.282-1.434z"/>
                                </svg>
                                <span>تلگرام</span>
                            </a>
                        </div>
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
                svg: '<svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>'
            },
            {
                id: 'web_app',
                title: 'وب‌اپلیکیشن و سامانه تحت وب (SaaS / Portal)',
                desc: 'فرانت‌اند مدرن با معماری کامپوننت‌محور، ارتباط بلادرنگ REST API، داشبورد اختصاصی مشتریان و احراز هویت.',
                badge: 'Custom Architecture',
                svg: '<svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="m9 8 3 3-3 3"/><path d="M15 14h.01"/></svg>'
            },
            {
                id: 'corporate',
                title: 'وب‌سایت شرکتی برندینگ و B2B',
                desc: 'طراحی بصری لوکس، پرستیژ بین‌المللی برای معرفی خدمات و سازمان با بالاترین استانداردهای سئو تکنیکال.',
                badge: 'Brand Authority',
                svg: '<svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/><path d="M6 12H4a2 2 0 0 0-2 2v8h4"/><path d="M18 9h2a2 2 0 0 1 2 2v11h-4"/><path d="M10 6h4"/><path d="M10 10h4"/><path d="M10 14h4"/><path d="M10 18h4"/></svg>'
            },
            {
                id: 'redesign',
                title: 'ریدیزاین اساسی، رفع باگ و ارتقای پرفورمنس',
                desc: 'مهاجرت از قالب‌های سنگین به کدنویسی تمیز اختصاصی با تضمین نمره ۱۰۰ لایت‌هاوس و صفر درصد کدهای زائد.',
                badge: 'Speed Guarantee',
                svg: '<svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"/><path d="m12 15-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"/><path d="M9 12H4s.55-3.03 2-4c1.62-1.08 5 0 5 0"/><path d="M12 15v5s3.03-.55 4-2c1.08-1.62 0-5 0-5"/></svg>'
            }
        ],
        featureList: [
            { id: 'speed', title: 'تضمین لود زیر ۱ ثانیه (Core Web Vitals)', desc: 'کدهای اختصاصی، تمیز و بدون پلاگین‌های زائد' },
            { id: 'ai', title: 'بهینه‌سازی برای هوش مصنوعی (llms.txt)', desc: 'ایندکسینگ خودکار توسط مدل‌های ChatGPT و Claude' },
            { id: 'seo', title: 'سئو تکنیکال و معماری عمیق اسکیما', desc: 'داده‌های ساختاریافته برای بیشترین رتبه در نتایج جستجو' },
            { id: 'pwa', title: 'طراحی واکنش‌گرا و تجربه شبیه اپ (PWA)', desc: 'نویگیشن روان و کارکرد بی‌نقص روی موبایل و تبلت' },
            { id: 'api', title: 'اتصال اختصاصی به سیستم‌های مالی / CRM', desc: 'همگام‌سازی اتوماتیک سفارشات، مشتریان و کالاها' },
            { id: 'security', title: 'امنیت پیشرفته و معماری ضد نفوذ', desc: 'کدهای استاندارد با رعایت سخت‌گیرانه‌ترین تدابیر امنیتی' }
        ],

        goToStep(s) {
            this.step = s;
        },

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
            if (this.selectedType === 'redesign') return 'Clean Architecture (0 Bloatware)';
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
