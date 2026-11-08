<?php
/**
 * Home — How we work together (scroll-drawn timeline)
 */
$steps = array_values(array_filter((array) get_field('process_steps'), function ($r) { return !empty($r['t']); }));
$steps = $steps ?: array(
    array('t' => 'گفت‌وگو و شناخت', 'd' => 'یک جلسه کوتاه و رایگان؛ هدف کسب‌وکار، مخاطب و اولویت‌ها را دقیق می‌کنیم.', 'm' => 'روز ۱'),
    array('t' => 'پیشنهاد و برنامه', 'd' => 'محدوده کار، زمان‌بندی مرحله‌ای و هزینه شفاف را مکتوب دریافت می‌کنید.', 'm' => 'روز ۲–۳'),
    array('t' => 'طراحی و توسعه', 'd' => 'طراحی اختصاصی، پیاده‌سازی تمیز و گزارش پیشرفت هفتگی با لینک پیش‌نمایش.', 'm' => 'هفته ۱–۶'),
    array('t' => 'تحویل و رشد', 'd' => 'تست کامل، آموزش پنل، انتشار و پشتیبانی بعد از تحویل برای خیال راحت.', 'm' => 'همیشه'),
);
?>
<section class="relative py-20 lg:py-28 border-t border-white/10 overflow-hidden" aria-labelledby="process-title">
    <div class="aurora w-[30rem] h-[30rem] top-1/3 -end-60 bg-secondary/15 -z-10" aria-hidden="true"></div>
    <div class="container mx-auto px-4 sm:px-6">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-16">
            <header class="lg:col-span-5 lg:sticky lg:top-32 self-start">
                <span class="inline-flex items-center gap-2 text-xs font-black text-secondary mb-4" data-aos="fade-up">
                    <span class="w-6 h-px bg-secondary"></span><?= esc_html(kmpr_field('process_kicker')); ?>
                </span>
                <h2 id="process-title" class="text-3xl sm:text-4xl lg:text-5xl font-black text-white leading-tight mb-6" data-split>
                    <?= esc_html(kmpr_field('process_title')); ?>
                </h2>
                <p class="text-slate-300 leading-loose mb-8" data-aos="fade-up" data-aos-delay="100">
                    <?= esc_html(kmpr_field('process_text')); ?>
                </p>
                <a href="<?= esc_url(kmpr_request_url()); ?>" data-aos="fade-up" data-aos-delay="200"
                   class="inline-flex items-center gap-2 px-7 py-3.5 rounded-full bg-secondary hover:bg-amber-500 text-slate-950 text-sm font-black whitespace-nowrap transition-colors">
                    همین حالا شروع کنیم
                </a>
            </header>

            <ol class="lg:col-span-7 relative ps-10 sm:ps-14" data-progress>
                <span class="absolute start-[0.95rem] sm:start-[1.45rem] top-2 bottom-2 w-px bg-white/10" aria-hidden="true"></span>
                <span class="absolute start-[0.95rem] sm:start-[1.45rem] top-2 bottom-2 w-px bg-gradient-to-b from-secondary to-amber-300 origin-top" style="transform: scaleY(var(--p, 0))" aria-hidden="true"></span>
                <?php foreach ($steps as $i => $step) : ?>
                    <li class="relative pb-12 last:pb-0" data-aos="fade-up">
                        <span class="absolute -start-10 sm:-start-14 top-0 size-8 sm:size-12 rounded-full bg-slate-950 border border-secondary/50 text-secondary font-black text-sm sm:text-base flex items-center justify-center shadow-[0_0_0_6px] shadow-slate-950">
                            <?= esc_html(kmpr_fa_num($i + 1)); ?>
                        </span>
                        <div class="spotlight p-6 sm:p-8 rounded-3xl bg-white/[0.025] border border-white/10 hover:border-white/20 transition-colors">
                            <div class="flex items-center justify-between gap-4 mb-3">
                                <h3 class="text-lg sm:text-xl font-black text-white"><?= esc_html($step['t']); ?></h3>
                                <span class="text-[11px] font-bold text-slate-400 px-2.5 py-1 rounded-full bg-white/[0.04] whitespace-nowrap"><?= esc_html($step['m']); ?></span>
                            </div>
                            <p class="text-sm sm:text-base text-slate-300 leading-loose"><?= esc_html($step['d']); ?></p>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ol>
        </div>
    </div>
</section>
