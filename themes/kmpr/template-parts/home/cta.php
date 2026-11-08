<?php
/**
 * Home — Final call to action (big type + magnetic button + marquee)
 */
?>
<section class="relative py-24 lg:py-36 border-t border-white/10 overflow-hidden isolate" aria-labelledby="cta-title">
    <div class="absolute inset-0 hero-grid -z-10" aria-hidden="true"></div>
    <div class="aurora w-[40rem] h-[40rem] top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 bg-secondary/20 -z-10" aria-hidden="true"></div>

    <div class="container mx-auto px-4 sm:px-6 text-center">
        <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white/[0.04] border border-white/10 text-xs font-bold text-slate-200 mb-8" data-aos="fade-up">
            <span class="size-2 rounded-full bg-emerald-400"></span><?= esc_html(kmpr_field('cta_badge')); ?>
        </span>
        <h2 id="cta-title" class="text-4xl sm:text-6xl lg:text-7xl font-black text-white leading-[1.25] max-w-4xl mx-auto" data-split>
            <?= esc_html(kmpr_field('cta_heading')); ?>
        </h2>
        <p class="mt-6 max-w-xl mx-auto text-slate-300 text-base sm:text-lg leading-loose" data-aos="fade-up" data-aos-delay="200">
            <?= esc_html(kmpr_field('cta_text')); ?>
        </p>
        <div class="mt-12 flex flex-col sm:flex-row items-center justify-center gap-4" data-aos="fade-up" data-aos-delay="300">
            <a href="<?= esc_url(kmpr_request_url()); ?>" data-magnetic
               class="group relative inline-flex items-center justify-center gap-3 size-40 sm:size-44 rounded-full bg-secondary text-slate-950 font-black text-base whitespace-nowrap shadow-[0_20px_80px_-20px] shadow-secondary transition-[background-color,transform] duration-300 hover:bg-amber-400">
                <span class="flex flex-col items-center gap-1">
                    <svg width="26" height="26" class="shrink-0 group-hover:-rotate-45 transition-transform duration-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.61l4.16 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.61 9.25h10.64A.75.75 0 0 1 17 10Z" clip-rule="evenodd"/></svg>
                    <?= esc_html(kmpr_field('cta_btn_text')); ?>
                </span>
            </a>
            <div class="flex sm:flex-col gap-3">
                <a href="<?= esc_url(kmpr_contact_url('telegram')); ?>" target="_blank" rel="noopener"
                   class="inline-flex items-center justify-center gap-2 px-6 py-3 min-h-11 rounded-full bg-white/[0.05] border border-white/10 hover:border-sky-400/50 text-sm font-bold text-slate-100 whitespace-nowrap transition-colors">
                    <svg width="16" height="16" class="shrink-0 text-sky-400" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="m20.67 3.72-17.73 6.83c-1.21.49-1.2 1.16-.22 1.47l4.55 1.42 10.53-6.65c.5-.3.95-.14.58.19l-8.53 7.7-.31 4.68c.46 0 .66-.21.92-.46l2.21-2.15 4.6 3.4c.85.47 1.46.23 1.67-.79l3.02-14.23c.31-1.24-.47-1.8-1.28-1.43z"/></svg>
                    تلگرام
                </a>
                <a href="<?= esc_url(kmpr_contact_url('whatsapp')); ?>" target="_blank" rel="noopener"
                   class="inline-flex items-center justify-center gap-2 px-6 py-3 min-h-11 rounded-full bg-white/[0.05] border border-white/10 hover:border-emerald-400/50 text-sm font-bold text-slate-100 whitespace-nowrap transition-colors">
                    <svg width="16" height="16" class="shrink-0 text-emerald-400" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38a9.9 9.9 0 0 0 4.74 1.21c5.46 0 9.91-4.45 9.91-9.91S17.5 2 12.04 2Zm4.52 13.33c-.25-.13-1.47-.72-1.7-.81-.23-.08-.39-.13-.56.13-.17.25-.64.81-.79.97-.14.17-.29.19-.54.06-.25-.13-1.06-.39-2.02-1.24-.75-.67-1.25-1.49-1.4-1.74-.14-.25-.02-.39.11-.51.11-.11.25-.29.37-.43.13-.15.17-.25.25-.42.08-.17.04-.31-.02-.44-.06-.13-.56-1.34-.76-1.84-.2-.48-.41-.42-.56-.43h-.48c-.17 0-.44.06-.67.31-.23.25-.88.86-.88 2.1s.9 2.43 1.02 2.6c.13.17 1.77 2.7 4.28 3.79 2.11.91 2.54.73 3 .69.48-.07 1.47-.6 1.68-1.18.21-.58.21-1.07.15-1.18-.06-.12-.22-.19-.47-.32Z"/></svg>
                    واتساپ
                </a>
            </div>
        </div>
    </div>
</section>
