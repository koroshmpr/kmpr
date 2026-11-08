<?php
/**
 * Live "your site is being built" preview for the project request wizard.
 * Lives inside the projectRequest() Alpine scope (smart-configurator.php) and reacts to every choice.
 * Purely decorative wireframe — no real data.
 */
?>
<div class="bp-frame lg:h-full lg:flex lg:flex-col relative rounded-3xl border border-white/10 bg-slate-900/80 backdrop-blur-xl shadow-2xl shadow-black/40 overflow-hidden" aria-hidden="true">

    <!-- Browser chrome -->
    <div class="flex items-center gap-3 px-4 h-11 bg-slate-950/70 border-b border-white/10" dir="ltr">
        <div class="flex items-center gap-1.5 shrink-0">
            <span class="size-2.5 rounded-full bg-rose-400/80"></span><span class="size-2.5 rounded-full bg-amber-400/80"></span><span class="size-2.5 rounded-full bg-emerald-400/80"></span>
        </div>
        <div class="flex-1 min-w-0 flex items-center gap-2 h-7 px-3 rounded-full bg-white/[0.09] border border-white/10 text-xs text-slate-300">
            <svg width="12" height="12" class="shrink-0 text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
            <span class="truncate" x-text="previewUrl"></span><span class="bp-caret"></span>
        </div>
    </div>

    <!-- Viewport -->
    <div class="relative aspect-[4/3] lg:aspect-auto lg:flex-1 lg:min-h-[260px] overflow-hidden bg-[#0a1226]">

        <!-- CMS admin bar -->
        <div x-show="has('cms')" x-transition.opacity.duration.400ms class="absolute top-0 inset-x-0 h-5 bg-slate-800 flex items-center gap-2 px-3 z-20">
            <span class="size-2 rounded-sm bg-sky-400"></span><span class="h-1.5 w-14 rounded bg-slate-500"></span><span class="h-1.5 w-8 rounded bg-slate-600"></span>
            <span class="ms-auto text-[9px] font-bold text-sky-300">پنل مدیریت</span>
        </div>

        <!-- Empty state -->
        <div x-show="!form.type" x-transition.opacity.duration.300ms class="absolute inset-0 flex flex-col items-center justify-center gap-4 bp-dots">
            <div class="size-14 rounded-2xl border border-dashed border-white/20 flex items-center justify-center text-slate-400">
                <svg width="26" height="26" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
            </div>
            <p class="text-sm font-bold text-slate-300 text-center px-6">یک نوع پروژه انتخاب کنید<br><span class="text-xs font-medium text-slate-400">تا ساخت سایت شما شروع شود</span></p>
        </div>

        <!-- Built site -->
        <div x-show="form.type" x-transition:enter="transition duration-500 ease-out" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="absolute inset-0 flex flex-col" :class="has('cms') ? 'pt-5' : ''">

            <!-- Site header -->
            <div class="flex items-center gap-2 px-4 h-10 border-b border-white/5 shrink-0">
                <span class="size-5 rounded-md shrink-0 transition-colors duration-500" :class="has('design') ? 'bg-gradient-to-br from-amber-400 to-rose-500' : 'bg-secondary'"></span>
                <span class="text-[11px] font-black text-white truncate max-w-[7rem]" x-text="brandName"></span>
                <span class="hidden sm:flex items-center gap-1.5 ms-3">
                    <span class="h-1.5 w-7 rounded bg-white/40"></span><span class="h-1.5 w-7 rounded bg-white/40"></span><span class="h-1.5 w-7 rounded bg-white/40"></span>
                </span>
                <span class="ms-auto flex items-center gap-1.5">
                    <span x-show="has('multilang')" x-transition class="px-1.5 py-0.5 rounded bg-white/20 text-[8px] font-black text-slate-200" dir="ltr">FA | EN</span>
                    <span x-show="has('payment') || form.type === 'ecommerce'" x-transition class="relative size-5 rounded-md bg-white/20 flex items-center justify-center">
                        <svg width="11" height="11" class="shrink-0 text-slate-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M6 6h15l-1.5 9h-12z"/><circle cx="9" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/></svg>
                        <span class="absolute -top-1 -end-1 size-2.5 rounded-full bg-secondary bp-pop"></span>
                    </span>
                    <span x-show="has('auth')" x-transition class="size-5 rounded-full bg-gradient-to-br from-sky-400 to-indigo-500"></span>
                </span>
            </div>

            <!-- Body variants -->
            <div class="relative flex-1 p-4 pb-12 overflow-hidden">

                <!-- website -->
                <div x-cloak x-show="form.type === 'website'" class="bp-variant h-full flex flex-col gap-3">
                        <div class="bp-in rounded-xl p-4 flex-[1.3] flex flex-col justify-center gap-2 transition-colors duration-700" :class="has('design') ? 'bg-gradient-to-br from-amber-500/30 via-rose-500/15 to-indigo-500/25' : 'bg-white/[0.09]'">
                            <span class="h-2.5 w-2/3 rounded bg-white/70"></span><span class="h-2 w-1/2 rounded bg-white/30"></span>
                            <span class="mt-1 h-4 w-16 rounded-full bg-secondary"></span>
                        </div>
                        <div class="grid grid-cols-3 gap-2 flex-1">
                            <?php for ($i = 1; $i <= 3; $i++) : ?><div class="bp-in rounded-lg bg-white/[0.09] p-2 flex flex-col gap-1.5" style="animation-delay:<?= $i * 120; ?>ms"><span class="size-4 rounded bg-secondary/60"></span><span class="h-1.5 w-full rounded bg-white/40"></span><span class="h-1.5 w-2/3 rounded bg-white/40"></span></div><?php endfor; ?>
                        </div>
                    </div>

                <!-- ecommerce -->
                <div x-cloak x-show="form.type === 'ecommerce'" class="bp-variant h-full flex flex-col gap-3">
                        <div class="bp-in h-12 rounded-xl flex items-center px-3 gap-2 transition-colors duration-700" :class="has('design') ? 'bg-gradient-to-l from-rose-500/30 to-amber-500/30' : 'bg-white/[0.09]'"><span class="h-2 w-1/3 rounded bg-white/60"></span><span class="ms-auto h-4 w-12 rounded-full bg-secondary"></span></div>
                        <div class="grid grid-cols-3 gap-2 flex-1">
                            <?php for ($i = 1; $i <= 6; $i++) : ?><div class="bp-in rounded-lg bg-white/[0.09] p-1.5 flex flex-col gap-1" style="animation-delay:<?= $i * 90; ?>ms"><span class="flex-1 rounded bg-gradient-to-br from-white/25 to-white/10"></span><span class="h-1.5 w-3/4 rounded bg-white/40"></span><span class="flex items-center justify-between"><span class="h-1.5 w-6 rounded bg-emerald-400/60"></span><span class="size-3 rounded bg-secondary/80"></span></span></div><?php endfor; ?>
                        </div>
                    </div>

                <!-- web app -->
                <div x-cloak x-show="form.type === 'web_app'" class="bp-variant h-full flex gap-3">
                        <div class="bp-in w-1/5 rounded-xl bg-white/[0.09] p-2 flex flex-col gap-2"><?php for ($i = 1; $i <= 5; $i++) : ?><span class="h-1.5 rounded <?= $i === 1 ? 'bg-secondary' : 'bg-white/20'; ?>"></span><?php endfor; ?></div>
                        <div class="flex-1 flex flex-col gap-2">
                            <div class="grid grid-cols-3 gap-2"><?php for ($i = 1; $i <= 3; $i++) : ?><div class="bp-in rounded-lg bg-white/[0.09] p-2" style="animation-delay:<?= $i * 100; ?>ms"><span class="block h-1.5 w-1/2 rounded bg-white/25 mb-1.5"></span><span class="block h-3 w-2/3 rounded <?= array('bg-emerald-400/60', 'bg-sky-400/60', 'bg-secondary/70')[$i - 1]; ?>"></span></div><?php endfor; ?></div>
                            <div class="bp-in flex-1 rounded-xl bg-white/[0.09] p-3 flex items-end gap-1.5">
                                <?php foreach (array(40, 65, 50, 80, 60, 90, 72) as $i => $h) : ?><span class="bp-bar flex-1 rounded-t bg-gradient-to-t from-secondary/40 to-secondary" style="--h:<?= $h; ?>%; animation-delay:<?= $i * 80; ?>ms"></span><?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                <!-- redesign -->
                <div x-cloak x-show="form.type === 'redesign'" class="bp-variant h-full grid grid-cols-2 gap-3">
                        <div class="bp-in rounded-xl bg-rose-500/10 border border-rose-500/20 p-3 flex flex-col items-center justify-center gap-2 grayscale">
                            <span class="text-[10px] font-black text-rose-300">قبل</span>
                            <svg width="64" height="40" viewBox="0 0 64 40" class="shrink-0"><path d="M6 36a26 26 0 0 1 52 0" fill="none" stroke="rgb(255 255 255 / .12)" stroke-width="6" stroke-linecap="round"/><path d="M6 36a26 26 0 0 1 14-23" fill="none" stroke="#fb7185" stroke-width="6" stroke-linecap="round"/></svg>
                            <span class="h-1.5 w-2/3 rounded bg-white/40"></span><span class="h-1.5 w-1/2 rounded bg-white/10"></span>
                        </div>
                        <div class="bp-in rounded-xl bg-emerald-500/10 border border-emerald-500/25 p-3 flex flex-col items-center justify-center gap-2" style="animation-delay:200ms">
                            <span class="text-[10px] font-black text-emerald-300">بعد</span>
                            <svg width="64" height="40" viewBox="0 0 64 40" class="shrink-0"><path d="M6 36a26 26 0 0 1 52 0" fill="none" stroke="rgb(255 255 255 / .12)" stroke-width="6" stroke-linecap="round"/><path class="bp-gauge" d="M6 36a26 26 0 0 1 52 0" fill="none" stroke="#34d399" stroke-width="6" stroke-linecap="round"/></svg>
                            <span class="h-1.5 w-2/3 rounded bg-white/40"></span><span class="h-4 w-14 rounded-full bg-secondary"></span>
                        </div>
                    </div>

                <!-- seo -->
                <div x-cloak x-show="form.type === 'seo'" class="bp-variant h-full flex flex-col gap-2.5">
                        <div class="bp-in h-8 rounded-full bg-white/[0.07] border border-white/10 flex items-center px-3 gap-2"><svg width="12" height="12" class="shrink-0 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="7"/><path d="m21 21-4-4"/></svg><span class="h-1.5 w-1/3 rounded bg-white/30"></span></div>
                        <div class="bp-in rounded-xl border border-secondary/40 bg-secondary/10 p-3 flex flex-col gap-1.5" style="animation-delay:150ms">
                            <span class="flex items-center gap-1.5"><span class="px-1.5 rounded bg-secondary text-[8px] font-black text-slate-950">۱</span><span class="text-[9px] text-emerald-300 truncate" dir="ltr" x-text="previewUrl"></span></span>
                            <span class="text-[11px] font-black text-sky-300 truncate" x-text="brandName + ' | بهترین انتخاب'"></span>
                            <span class="h-1.5 w-full rounded bg-white/40"></span><span class="h-1.5 w-4/5 rounded bg-white/40"></span>
                            <span class="text-[9px] text-amber-300">★★★★★</span>
                        </div>
                        <?php for ($i = 1; $i <= 2; $i++) : ?><div class="bp-in rounded-xl bg-white/[0.08] p-3 flex flex-col gap-1.5 opacity-60" style="animation-delay:<?= 200 + $i * 120; ?>ms"><span class="h-1.5 w-1/4 rounded bg-white/30"></span><span class="h-2 w-1/2 rounded bg-white/40"></span><span class="h-1.5 w-full rounded bg-white/10"></span></div><?php endfor; ?>
                    </div>

                <!-- consult -->
                <div x-cloak x-show="form.type === 'consult'" class="bp-variant h-full flex flex-col justify-end gap-2.5">
                        <div class="bp-in self-start max-w-[70%] rounded-2xl rounded-ss-sm bg-white/[0.07] px-3 py-2 text-[10px] text-slate-200">سلام! پروژه‌تان را برایم توضیح دهید.</div>
                        <div class="bp-in self-end max-w-[70%] rounded-2xl rounded-se-sm bg-secondary/80 px-3 py-2 text-[10px] text-slate-950 font-bold" style="animation-delay:300ms" x-text="form.business ? ('برای ' + form.business + ' مشاوره می‌خواهم') : 'برای انتخاب معماری مشاوره می‌خواهم'"></div>
                        <div class="bp-in self-start rounded-2xl rounded-ss-sm bg-white/[0.07] px-3 py-2 flex gap-1" style="animation-delay:600ms"><span class="bp-typing"></span><span class="bp-typing [animation-delay:150ms]"></span><span class="bp-typing [animation-delay:300ms]"></span></div>
                    </div>

                <!-- Feature overlays: one tidy row along the bottom -->
                <div class="absolute bottom-2.5 inset-x-3 flex items-end justify-between gap-2 pointer-events-none" dir="ltr">
                    <div class="flex items-center gap-1 h-8">
                        <span x-show="has('api')" x-transition.opacity.duration.500ms class="flex items-center gap-1">
                            <span class="size-2.5 rounded-full bg-sky-400"></span><span class="bp-link w-5 h-px"></span><span class="size-2.5 rounded-full bg-secondary"></span><span class="bp-link w-5 h-px"></span><span class="size-2.5 rounded-full bg-emerald-400"></span>
                        </span>
                    </div>
                    <div class="flex flex-wrap items-center justify-center gap-1.5" dir="rtl">
                        <span x-show="has('speed')" x-transition:enter="transition duration-500" x-transition:enter-start="opacity-0 translate-y-2" class="flex items-center gap-1 px-2 py-1 rounded-full bg-emerald-500/20 border border-emerald-400/40 backdrop-blur text-[9px] font-black text-emerald-200">
                            <svg width="10" height="10" class="shrink-0" viewBox="0 0 24 24" fill="currentColor"><path d="M13 2 3 14h9l-1 8 10-12h-9z"/></svg>فوق‌سریع
                        </span>
                        <span x-show="has('seo') && form.type !== 'seo'" x-transition:enter="transition duration-500" x-transition:enter-start="opacity-0 translate-y-2" class="px-2 py-1 rounded-full bg-sky-500/20 border border-sky-400/40 backdrop-blur text-[9px] font-black text-sky-200">رتبه ۱ گوگل</span>
                        <span x-show="has('support')" x-transition:enter="transition duration-500" x-transition:enter-start="opacity-0 translate-y-2" class="flex items-center gap-1 px-2 py-1 rounded-full bg-white/15 border border-white/20 backdrop-blur text-[9px] font-black text-slate-100">
                            <svg width="10" height="10" class="shrink-0 text-emerald-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>پشتیبانی
                        </span>
                    </div>
                    <div class="h-8 w-8 shrink-0">
                        <span x-show="has('ai')" x-transition:enter="transition duration-500" x-transition:enter-start="opacity-0 scale-50" class="size-8 rounded-full bg-gradient-to-br from-violet-500 to-fuchsia-500 flex items-center justify-center shadow-lg shadow-violet-500/40 bp-pulse">
                            <svg width="14" height="14" class="shrink-0 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 3v3M12 18v3M3 12h3M18 12h3M6 6l2 2M16 16l2 2M6 18l2-2M16 8l2-2"/></svg>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Launch overlay -->
        <div x-show="launched" x-cloak x-transition.opacity.duration.300ms class="absolute inset-0 z-30 flex flex-col items-center justify-center gap-3 bg-slate-950/85 backdrop-blur-sm">
            <svg width="54" height="54" class="shrink-0 bp-rocket text-secondary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"/><path d="m12 15-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"/><path d="M9 12H4s.55-3.03 2-4c1.62-1.08 5 0 5 0M12 15v5s3.03-.55 4-2c1.08-1.62 0-5 0-5"/></svg>
            <span class="text-sm font-black text-white">بریف شما ارسال شد!</span>
            <span class="text-xs text-slate-300">ساخت سایت <span x-text="brandName"></span> از همین‌جا شروع می‌شود</span>
        </div>
    </div>

    <!-- Build meter -->
    <div class="px-5 py-4 border-t border-white/10">
        <div class="flex items-center justify-between gap-3 mb-2.5">
            <span class="text-sm font-bold text-slate-200" x-text="completion >= 100 ? 'آماده ارسال برای من!' : (completion >= 60 ? 'تقریباً آماده است…' : (completion > 0 ? 'در حال ساخت سایت شما…' : 'منتظر انتخاب شما'))"></span>
            <span class="text-base font-black text-secondary tabular-nums" x-text="completion.toLocaleString('fa-IR') + '٪'"></span>
        </div>
        <div class="h-2 rounded-full bg-white/20 overflow-hidden">
            <div class="rq-fill h-full rounded-full transition-[width] duration-700 ease-out" :style="`width:${completion}%`"></div>
        </div>
    </div>
</div>
