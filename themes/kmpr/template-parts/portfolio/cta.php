<?php
/**
 * Portfolio CTA → project request wizard (copy editable in «نمونه‌کارها › تنظیمات آرشیو»).
 */
$service_title = $args['service'] ?? '';
?>
<section class="relative py-20 lg:py-28 border-t border-white/10 overflow-hidden isolate" aria-labelledby="pf-cta-title">
    <div class="absolute inset-0 hero-grid -z-10" aria-hidden="true"></div>
    <div class="aurora w-[28rem] h-[28rem] sm:w-[40rem] sm:h-[40rem] top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 bg-secondary/20 -z-10" aria-hidden="true"></div>

    <div class="container mx-auto px-4 sm:px-6 text-center max-w-3xl">
        <span class="inline-flex items-center gap-2 text-xs font-black text-secondary mb-5" data-aos="fade-up">
            <span class="w-6 h-px bg-secondary"></span><?= esc_html(kmpr_pf_opt('pfa_cta_kicker')); ?><span class="w-6 h-px bg-secondary"></span>
        </span>
        <h2 id="pf-cta-title" class="text-3xl sm:text-4xl lg:text-5xl font-black text-white leading-[1.4]" data-split>
            <?= esc_html(kmpr_pf_opt('pfa_cta_title')); ?>
        </h2>
        <p class="mt-5 text-slate-300 text-base sm:text-lg leading-loose" data-aos="fade-up" data-aos-delay="150">
            <?= esc_html(kmpr_pf_opt('pfa_cta_text')); ?>
        </p>
        <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-3" data-aos="fade-up" data-aos-delay="250">
            <a href="<?= esc_url(kmpr_request_url()); ?>" data-magnetic
               class="group inline-flex items-center justify-center gap-2 min-h-11 px-8 py-3.5 rounded-full bg-secondary hover:bg-amber-500 text-slate-950 text-base font-black whitespace-nowrap shadow-[0_20px_60px_-20px] shadow-secondary transition-colors">
                <?= esc_html(kmpr_pf_opt('pfa_cta_btn')); ?>
                <svg width="18" height="18" class="shrink-0 transition-transform group-hover:-translate-x-1" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.61l4.16 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.61 9.25h10.64A.75.75 0 0 1 17 10Z" clip-rule="evenodd"/></svg>
            </a>
            <a href="<?= esc_url(kmpr_contact_url('telegram')); ?>" target="_blank" rel="noopener"
               class="inline-flex items-center justify-center gap-2 min-h-11 px-6 py-3 rounded-full bg-white/[0.05] border border-white/10 hover:border-sky-400/50 text-sm font-bold text-slate-100 whitespace-nowrap transition-colors">
                <svg width="16" height="16" class="shrink-0 text-sky-400" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="m20.67 3.72-17.73 6.83c-1.21.49-1.2 1.16-.22 1.47l4.55 1.42 10.53-6.65c.5-.3.95-.14.58.19l-8.53 7.7-.31 4.68c.46 0 .66-.21.92-.46l2.21-2.15 4.6 3.4c.85.47 1.46.23 1.67-.79l3.02-14.23c.31-1.24-.47-1.8-1.28-1.43z"/></svg>
                <?= $service_title ? 'گفت‌وگو درباره پروژه مشابه' : 'پیام مستقیم در تلگرام'; ?>
            </a>
        </div>
    </div>
</section>
