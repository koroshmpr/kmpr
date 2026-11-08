<?php
/**
 * Home — About statement (big typographic manifesto, no duplicate portrait)
 */
$about_page = get_page_by_path('about-us');
$resume     = get_page_by_path('resume');
?>
<section class="relative py-20 lg:py-32 border-t border-white/10" aria-labelledby="about-title">
    <div class="container mx-auto px-4 sm:px-6 max-w-5xl">
        <span class="inline-flex items-center gap-2 text-xs font-black text-secondary mb-6" data-aos="fade-up">
            <span class="w-6 h-px bg-secondary"></span>درباره من
        </span>
        <h2 id="about-title" class="sr-only">درباره کوروش محمدپور</h2>
        <p class="text-2xl sm:text-3xl lg:text-[2.6rem] font-black leading-[1.7] lg:leading-[1.65] text-slate-500" data-split>
            <span class="text-white">من کوروش هستم؛</span> بیش از <span class="text-white"><?= esc_html(kmpr_fa_num(kmpr_years_experience())); ?> سال</span> است که وب می‌سازم. باور دارم یک سایت خوب باید <span class="text-secondary">سریع</span>، <span class="text-secondary">قابل اعتماد</span> و <span class="text-secondary">ساده برای مدیریت</span> باشد؛ نه فقط زیبا.
        </p>
        <div class="mt-12 flex flex-wrap items-center gap-3" data-aos="fade-up" data-aos-delay="200">
            <?php if ($about_page) : ?>
                <a href="<?= esc_url(get_permalink($about_page)); ?>" class="inline-flex items-center gap-2 px-7 py-3.5 rounded-full bg-white text-slate-950 hover:bg-secondary text-sm font-black whitespace-nowrap transition-colors">
                    داستان من را بخوانید
                </a>
            <?php endif; ?>
            <?php if ($resume) : ?>
                <a href="<?= esc_url(get_permalink($resume)); ?>" class="inline-flex items-center gap-2 px-7 py-3.5 rounded-full border border-white/15 hover:border-white/40 text-slate-100 text-sm font-bold whitespace-nowrap transition-colors">
                    <svg width="16" height="16" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h5"/></svg>
                    رزومه آنلاین
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>
