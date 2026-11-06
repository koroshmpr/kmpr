<?php
/**
 * Developer Tech Stack & Arsenal Showcase
 * Modern, High-Tech, Minimalist Tech Badges
 */
$tech_stacks = [
    [
        'name' => 'Next.js & React',
        'badge' => 'Frontend',
        'desc' => 'توسعه وب‌اپلیکیشن‌های فوق‌سریع و مدرن',
        'icon' => '<svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L2 19.5h20L12 2zm0 4.5l6.5 11.5h-13L12 6.5z"/></svg>'
    ],
    [
        'name' => 'TypeScript',
        'badge' => 'Type Safety',
        'desc' => 'کدنویسی بدون باگ و ساختاریافته',
        'icon' => '<svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="currentColor"><path d="M1.5 0h21A1.5 1.5 0 0 1 24 1.5v21A1.5 1.5 0 0 1 22.5 24h-21A1.5 1.5 0 0 1 0 22.5v-21A1.5 1.5 0 0 1 1.5 0zm10.7 13.9h-2.3V8.8h2.3c1.7 0 2.7.9 2.7 2.5s-1 2.6-2.7 2.6zm6.8 5.6c-1.3 0-2.3-.6-2.9-1.5l1.6-1.1c.4.6.9.9 1.4.9.7 0 1.1-.3 1.1-.8 0-.6-.5-.8-1.5-1.1-1.6-.5-2.6-1.2-2.6-2.6 0-1.6 1.3-2.6 3.1-2.6 1.2 0 2.1.4 2.7 1.2l-1.5 1.2c-.4-.5-.8-.7-1.3-.7-.6 0-.9.3-.9.7 0 .5.4.7 1.3 1 1.8.6 2.8 1.3 2.8 2.7 0 1.8-1.4 2.8-3.3 2.8z"/></svg>'
    ],
    [
        'name' => 'Custom WordPress',
        'badge' => 'Architecture',
        'desc' => 'قالب‌های اختصاصی، بدون صفحه‌سازهای کند',
        'icon' => '<svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2zm-8.2 10a8.2 8.2 0 0 1 2.2-5.6l3.8 10.4A8.3 8.3 0 0 1 3.8 12zm8.2 8.2a8.1 8.1 0 0 1-2.6-.4l2.8-8.2 2.9 8.2a8.1 8.1 0 0 1-3.1.4zm1.1-11.7l3.2 8.9a8.2 8.2 0 0 0 1.9-5.4 8.2 8.2 0 0 0-5.1-3.5z"/></svg>'
    ],
    [
        'name' => 'Tailwind CSS v4',
        'badge' => 'Modern UI',
        'desc' => 'طراحی واکنش‌گرا و فوق سبک بدون CSS اضافه',
        'icon' => '<svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="currentColor"><path d="M12 6c-3.3 0-5.4 1.7-6.2 5 1.2-1.7 2.7-2.3 4.4-1.8.9.3 1.6 1 2.3 1.7C13.8 12.1 15.3 13 18 13c3.3 0 5.4-1.7 6.2-5-1.2 1.7-2.7 2.3-4.4 1.8-.9-.3-1.6-1-2.3-1.7C16.2 6.9 14.7 6 12 6zm-6 7c-3.3 0-5.4 1.7-6.2 5 1.2-1.7 2.7-2.3 4.4-1.8.9.3 1.6 1 2.3 1.7 1.3 1.2 2.8 2.1 5.5 2.1 3.3 0 5.4-1.7 6.2-5-1.2 1.7-2.7 2.3-4.4 1.8-.9-.3-1.6-1-2.3-1.7C10.2 13.9 8.7 13 6 13z"/></svg>'
    ],
    [
        'name' => 'REST & GraphQL APIs',
        'badge' => 'Integration',
        'desc' => 'اتصال امن دیتابیس، پنل‌ها و سرویس‌های خارجی',
        'icon' => '<svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 9l3 3-3 3m5 0h3M4 4h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"/></svg>'
    ],
    [
        'name' => 'AI & llms.txt Ready',
        'badge' => 'Next-Gen SEO',
        'desc' => 'بهینه‌سازی برای هوش مصنوعی و مدل‌های زبانی',
        'icon' => '<svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>'
    ]
];
?>
<section class="py-12 bg-slate-900 border-y border-slate-800 relative overflow-hidden select-none">
    <!-- Ambient subtle background glow -->
    <div class="absolute -top-20 start-1/3 w-80 h-80 bg-secondary/10 rounded-full blur-[100px] pointer-events-none"></div>

    <div class="container mx-auto px-4 sm:px-6 relative z-10">
        <!-- Section Subheading -->
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4 mb-8">
            <div class="flex items-center gap-3">
                <span class="flex h-3 w-3 relative">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                </span>
                <span class="text-xs font-black tracking-widest text-slate-300 uppercase">تکنولوژی‌ها و ابزارهای مهندسی نرم‌افزار (Tech Arsenal)</span>
            </div>
            <div class="text-xs text-slate-400 font-bold flex items-center gap-2">
                <span>توسعه کامپوننت‌محور و بهینه</span>
                <span class="text-secondary">•</span>
                <span class="text-slate-200 font-mono">100% Custom Code</span>
            </div>
        </div>

        <!-- Tech Cards Grid -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3.5">
            <?php foreach ($tech_stacks as $tech) : ?>
                <div class="group relative rounded-2xl p-4 bg-slate-950/60 hover:bg-slate-800/80 border border-slate-800 hover:border-secondary/40 transition-all duration-300 flex flex-col justify-between gap-3 backdrop-blur-sm hover:-translate-y-1 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div class="w-9 h-9 rounded-xl bg-slate-800 group-hover:bg-secondary/15 text-slate-300 group-hover:text-secondary flex items-center justify-center transition-colors">
                            <?= $tech['icon']; ?>
                        </div>
                        <span class="text-[10px] font-mono px-2 py-0.5 rounded-full bg-white/5 text-slate-400 border border-white/10 group-hover:border-secondary/30 group-hover:text-secondary transition-colors">
                            <?= esc_html($tech['badge']); ?>
                        </span>
                    </div>

                    <div class="text-start">
                        <h4 class="text-xs sm:text-sm font-black text-white group-hover:text-secondary transition-colors truncate">
                            <?= esc_html($tech['name']); ?>
                        </h4>
                        <p class="text-[11px] text-slate-400 line-clamp-1 mt-1 font-medium">
                            <?= esc_html($tech['desc']); ?>
                        </p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

