<?php
/**
 * Case study device showcase: laptop / tablet / mobile frames with hover + auto-scroll of full-page screenshots.
 * Args: shots (array device => [url,width,height]), title (string), host (string)
 */
$shots = $args['shots'] ?? array();
$title = $args['title'] ?? get_the_title();
$host  = $args['host'] ?? '';
if (!$shots) {
    return;
}
$labels = array('laptop' => 'دسکتاپ', 'tablet' => 'تبلت', 'mobile' => 'موبایل');
$frame_ratio = array('laptop' => 0.625, 'tablet' => 1.333, 'mobile' => 2.1);
$dur = function ($shot, $device) use ($frame_ratio) {
    $r = $shot['width'] ? $shot['height'] / $shot['width'] : $frame_ratio[$device];
    return (int) max(1500, min(14000, ($r - $frame_ratio[$device]) * ($device === 'laptop' ? 1800 : 1100)));
};
$shot_img = function ($device) use ($shots, $title, $labels, $dur) {
    $s = $shots[$device];
    printf(
        '<img src="%s" alt="%s" width="%d" height="%d" loading="lazy" decoding="async" class="pf-device-shot absolute inset-0 w-full h-full object-cover object-top" style="--pf-dur:%dms">',
        esc_url($s['url']),
        esc_attr(sprintf('اسکرین‌شات کامل نسخه %s وب‌سایت %s', $labels[$device], $title)),
        (int) $s['width'],
        (int) $s['height'],
        $dur($s, $device)
    );
};
$multi = count($shots) > 1;
?>
<section class="relative py-16 lg:py-24 border-t border-white/10 overflow-hidden isolate" aria-labelledby="pf-devices-title"
         x-data="{ device: 'all', auto: false }">
    <div class="aurora w-[34rem] h-[34rem] top-1/3 left-1/2 -translate-x-1/2 bg-secondary/15 -z-10" aria-hidden="true"></div>

    <div class="container mx-auto px-4 sm:px-6">
        <header class="flex flex-col lg:flex-row lg:items-end justify-between gap-6 mb-10 lg:mb-14">
            <div class="max-w-xl">
                <span class="inline-flex items-center gap-2 text-xs font-black text-secondary mb-4" data-aos="fade-up">
                    <span class="w-6 h-px bg-secondary"></span>پیش‌نمایش زنده
                </span>
                <h2 id="pf-devices-title" class="text-3xl sm:text-4xl font-black text-white leading-tight" data-split>طراحی در همه دستگاه‌ها</h2>
                <p class="mt-3 text-sm sm:text-base text-slate-300 leading-loose" data-aos="fade-up">نشانگر را روی هر دستگاه ببرید یا «پیمایش خودکار» را بزنید تا کل صفحه را ببینید.</p>
            </div>

            <div class="flex flex-wrap items-center gap-3" data-aos="fade-up">
                <?php if ($multi) : ?>
                    <div class="flex items-center gap-1 p-1 rounded-full bg-slate-900/80 border border-white/10" role="group" aria-label="انتخاب دستگاه">
                        <button type="button" @click="device = 'all'" :aria-pressed="device === 'all'"
                                :class="device === 'all' ? 'bg-secondary text-slate-950' : 'text-slate-300 hover:text-white'"
                                class="min-h-11 px-4 rounded-full text-sm font-black whitespace-nowrap transition-colors cursor-pointer bg-secondary text-slate-950">همه</button>
                        <?php foreach ($labels as $key => $label) : if (!isset($shots[$key])) { continue; } ?>
                            <button type="button" @click="device = '<?= esc_attr($key); ?>'" :aria-pressed="device === '<?= esc_attr($key); ?>'"
                                    :class="device === '<?= esc_attr($key); ?>' ? 'bg-secondary text-slate-950' : 'text-slate-300 hover:text-white'"
                                    class="min-h-11 px-4 rounded-full text-sm font-black whitespace-nowrap transition-colors cursor-pointer text-slate-300"><?= esc_html($label); ?></button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <button type="button" @click="auto = !auto" :aria-pressed="auto"
                        :class="auto ? 'bg-secondary text-slate-950 border-secondary' : 'bg-white/[0.05] text-slate-100 border-white/10 hover:border-secondary/50'"
                        class="inline-flex items-center gap-2 min-h-11 px-6 py-3 rounded-full border text-sm font-black whitespace-nowrap transition-colors cursor-pointer bg-white/[0.05] text-slate-100 border-white/10">
                    <svg x-show="!auto" width="14" height="14" class="shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M6 4l14 8-14 8z"/></svg>
                    <svg x-show="auto" x-cloak width="14" height="14" class="shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M6 4h4v16H6zM14 4h4v16h-4z"/></svg>
                    <span x-text="auto ? 'توقف پیمایش' : 'پیمایش خودکار'">پیمایش خودکار</span>
                </button>
            </div>
        </header>

        <div class="pf-viewer pf-stage relative flex flex-wrap justify-center items-end gap-4 sm:gap-6 lg:block lg:pb-6"
             :data-auto="auto ? '1' : '0'" :data-device="device" data-device="all" data-auto="0">

            <?php if (isset($shots['laptop'])) : ?>
                <div class="pf-dev pf-dev--laptop pf-device w-full <?= $multi ? 'lg:w-[74%]' : 'lg:w-[86%]'; ?> lg:mx-auto" x-show="device === 'all' || device === 'laptop'" data-aos="fade-up">
                    <div class="rounded-t-[1.25rem] border border-white/15 bg-slate-900 p-2 sm:p-3 shadow-[0_50px_120px_-40px_rgba(0,0,0,0.9)]">
                        <div class="flex items-center gap-2 px-2 pb-2 sm:pb-3" dir="ltr">
                            <span class="size-2.5 rounded-full bg-rose-400/80"></span><span class="size-2.5 rounded-full bg-amber-400/80"></span><span class="size-2.5 rounded-full bg-emerald-400/80"></span>
                            <?php if ($host) : ?>
                                <span class="ms-3 min-w-0 truncate rounded-full bg-slate-950/80 border border-white/5 px-3 py-0.5 text-xs font-mono text-slate-400"><?= esc_html($host); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="relative aspect-[16/10] overflow-hidden rounded-lg bg-slate-950">
                            <?php $shot_img('laptop'); ?>
                        </div>
                    </div>
                    <div class="h-3 sm:h-4 mx-[-3%] rounded-b-2xl bg-gradient-to-b from-slate-700 to-slate-800 border border-white/10" aria-hidden="true"></div>
                </div>
            <?php endif; ?>

            <?php if (isset($shots['tablet'])) : ?>
                <div class="pf-dev pf-dev--tablet pf-device w-[54%] sm:w-[38%] lg:absolute lg:start-0 lg:bottom-0 lg:w-[22%] z-10" x-show="device === 'all' || device === 'tablet'" data-aos="fade-left" data-aos-delay="150">
                    <div class="rounded-[1.6rem] border border-white/20 bg-slate-900 p-2.5 shadow-[0_40px_90px_-30px_rgba(0,0,0,0.95)]">
                        <div class="relative aspect-[3/4] overflow-hidden rounded-[1rem] bg-slate-950">
                            <?php $shot_img('tablet'); ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (isset($shots['mobile'])) : ?>
                <div class="pf-dev pf-dev--mobile pf-device w-[36%] sm:w-[24%] lg:absolute lg:end-[3%] lg:bottom-0 lg:w-[14%] z-20" x-show="device === 'all' || device === 'mobile'" data-aos="fade-right" data-aos-delay="250">
                    <div class="rounded-[2rem] border-2 border-white/20 bg-slate-900 p-2 shadow-[0_40px_90px_-30px_rgba(0,0,0,0.95)]">
                        <div class="relative aspect-[9/19] overflow-hidden rounded-[1.5rem] bg-slate-950">
                            <span class="absolute top-2 left-1/2 -translate-x-1/2 z-10 w-1/3 h-4 rounded-full bg-black" aria-hidden="true"></span>
                            <?php $shot_img('mobile'); ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
