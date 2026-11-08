<?php
/**
 * Home — Hero
 * Animated personal intro: split headline, rotating value words, tilt portrait with orbiting stack, live counters.
 */
$hero_image = get_field('hero-image');
if (is_array($hero_image) && !empty($hero_image['url'])) {
    $photo_url = $hero_image['url'];
} elseif (is_numeric($hero_image)) {
    $photo_url = wp_get_attachment_image_url($hero_image, 'large');
} else {
    $photo_url = '';
}
$photo_url  = $photo_url ?: get_template_directory_uri() . '/public/images/korosh-mpr.webp';
$img_url    = function ($field, $fallback) {
    $v = get_field($field);
    $u = is_array($v) ? ($v['url'] ?? '') : (is_numeric($v) ? wp_get_attachment_image_url($v, 'large') : (string) $v);
    return $u ?: get_template_directory_uri() . $fallback;
};
$cutout_url = $img_url('hero_cutout', '/public/images/hero/korosh-cutout.webp');
$plate_v    = get_field('hero_plate'); // optional custom backdrop (default: pure code-rain scene)
$plate_url  = $plate_v ? $img_url('hero_plate', '') : '';

$projects = (int) wp_count_posts('portfolio')->publish;
$articles = (int) wp_count_posts('post')->publish;
$years    = kmpr_years_experience();
$rotating = kmpr_lines(kmpr_field('hero_rotating'));
$stack    = array_slice(kmpr_lines(kmpr_field('hero_stack')), 0, 8);
$btn1     = kmpr_link(get_field('hero_primary_btn'), kmpr_request_url(), 'شروع پروژه با من');
$btn2     = kmpr_link(get_field('hero_secondary_btn'), get_post_type_archive_link('portfolio') ?: home_url('/portfolios/'), 'مشاهده نمونه‌کارها');
?>
<section class="relative isolate lg:min-h-[calc(100svh-5rem)] flex items-center overflow-hidden pt-6 pb-14 sm:py-16 lg:py-20" aria-labelledby="hero-title">
    <!-- Ambience -->
    <div class="absolute inset-0 hero-grid -z-10" aria-hidden="true"></div>
    <div class="aurora w-[38rem] h-[38rem] -top-40 -start-40 bg-secondary/25 -z-10" aria-hidden="true"></div>
    <div class="aurora w-[32rem] h-[32rem] -bottom-40 end-0 bg-indigo-600/25 -z-10 [animation-delay:-6s]" aria-hidden="true"></div>
    <div class="cursor-glow -z-10" data-cursor-glow aria-hidden="true"></div>

    <div class="container mx-auto px-4 sm:px-6">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 sm:gap-14 lg:gap-10 items-center">

            <!-- Pitch -->
            <div class="lg:col-span-7 flex flex-col items-center lg:items-start text-center lg:text-start">
                <a href="<?= esc_url(kmpr_request_url()); ?>" data-hero-rise
                   class="group hidden sm:inline-flex items-center gap-2.5 ps-2 pe-4 py-1.5 rounded-full bg-white/[0.04] border border-white/10 hover:border-emerald-400/40 backdrop-blur-xl transition-colors mb-7">
                    <span class="relative flex size-2.5 shrink-0">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full size-2.5 bg-emerald-500"></span>
                    </span>
                    <span class="text-xs font-bold text-slate-200"><?= esc_html(kmpr_field('hero_badge')); ?></span>
                    <svg width="14" height="14" class="shrink-0 text-slate-400 group-hover:text-emerald-400 group-hover:-translate-x-0.5 transition" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.61l4.16 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.61 9.25h10.64A.75.75 0 0 1 17 10Z" clip-rule="evenodd"/></svg>
                </a>

                <h1 id="hero-title" class="font-black text-white tracking-tight">
                    <span class="block text-base sm:text-lg font-bold text-slate-300 mb-4" data-hero-rise><?= esc_html(wp_strip_all_tags(kmpr_field('hero-slogan'))); ?></span>
                    <span class="block text-[2.1rem] leading-[1.35] sm:text-5xl sm:leading-[1.3] xl:text-6xl xl:leading-[1.25]" data-hero-rise><?= esc_html(kmpr_field('hero_headline')); ?></span>
                    <span class="word-rotator text-[2.1rem] leading-[1.45] sm:text-5xl sm:leading-[1.35] xl:text-6xl xl:leading-[1.3] mt-1" style="--n:<?= count($rotating); ?>">
                        <?php foreach ($rotating as $i => $word) : ?>
                            <span class="whitespace-nowrap" style="--i:<?= (int) $i; ?>"><span class="text-shimmer"><?= esc_html($word); ?></span></span>
                        <?php endforeach; ?>
                    </span>
                </h1>

                <p class="mt-6 max-w-xl text-sm sm:text-base lg:text-lg text-slate-300 leading-loose" data-hero-rise>
                    <?= esc_html(wp_strip_all_tags(kmpr_field('hero-subtitle'))); ?>
                </p>

                <div class="mt-9 flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full sm:w-auto" data-hero-rise>
                    <a href="<?= esc_url($btn1['url']); ?>"<?= $btn1['target'] ? ' target="_blank" rel="noopener"' : ''; ?> data-magnetic
                       class="group inline-flex items-center justify-center gap-2.5 px-8 py-4 rounded-full bg-secondary hover:bg-amber-500 text-slate-950 font-black text-sm sm:text-base whitespace-nowrap shadow-[0_10px_40px_-10px] shadow-secondary/60 transition-[background-color,transform] duration-300">
                        <?= esc_html($btn1['title']); ?>
                        <svg width="18" height="18" class="shrink-0 group-hover:-translate-x-1 transition-transform" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.61l4.16 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.61 9.25h10.64A.75.75 0 0 1 17 10Z" clip-rule="evenodd"/></svg>
                    </a>
                    <a href="<?= esc_url($btn2['url']); ?>"<?= $btn2['target'] ? ' target="_blank" rel="noopener"' : ''; ?>
                       class="inline-flex items-center justify-center gap-2 px-8 py-4 rounded-full bg-white/[0.04] hover:bg-white/[0.08] border border-white/15 hover:border-white/30 text-slate-100 font-bold text-sm sm:text-base whitespace-nowrap transition-colors">
                        <?= esc_html($btn2['title']); ?>
                    </a>
                </div>

                <dl class="mt-12 grid grid-cols-3 gap-4 sm:gap-10 w-full sm:w-auto border-t border-white/10 pt-8">
                    <div class="flex flex-col-reverse" data-hero-rise>
                        <dt class="text-xs sm:text-sm text-slate-400 font-bold">سال تجربه</dt>
                        <dd class="text-3xl sm:text-4xl font-black text-white tabular-nums mb-1" data-count="<?= (int) $years; ?>" data-suffix="+"><?= esc_html(kmpr_fa_num($years)); ?>+</dd>
                    </div>
                    <div class="flex flex-col-reverse" data-hero-rise>
                        <dt class="text-xs sm:text-sm text-slate-400 font-bold">پروژه تحویل‌شده</dt>
                        <dd class="text-3xl sm:text-4xl font-black text-white tabular-nums mb-1" data-count="<?= (int) $projects; ?>" data-suffix="+"><?= esc_html(kmpr_fa_num($projects)); ?>+</dd>
                    </div>
                    <div class="flex flex-col-reverse" data-hero-rise>
                        <dt class="text-xs sm:text-sm text-slate-400 font-bold">مقاله تخصصی</dt>
                        <dd class="text-3xl sm:text-4xl font-black text-white tabular-nums mb-1" data-count="<?= (int) $articles; ?>"><?= esc_html(kmpr_fa_num($articles)); ?></dd>
                    </div>
                </dl>
            </div>

            <!-- Portrait: layered depth (code rain → cut-out portrait breaking out of the frame) -->
            <div class="order-first lg:order-none lg:col-span-5 flex justify-center pt-12 sm:pt-14 lg:pt-0" data-hero-rise>
                <div class="hero-portrait relative w-[13.5rem] min-[400px]:w-[15rem] sm:w-[21rem] xl:w-[24rem] aspect-[4/5]" data-hero-portrait>
                    <!-- Orbit ring -->
                    <div class="absolute -inset-10 sm:-inset-14 rounded-full border border-dashed border-white/10 orbit hidden sm:block" style="--orbit-speed:40s" aria-hidden="true">
                        <?php foreach ($stack as $i => $tech) :
                            $angle = (360 / max(1, count($stack))) * $i; ?>
                            <span class="absolute top-1/2 left-1/2 -mt-4 -ml-4 size-8" style="transform: rotate(<?= (float) $angle; ?>deg) translateY(calc(-50% - 13rem)) rotate(-<?= (float) $angle; ?>deg)">
                                <span class="block px-2.5 py-1 -translate-x-1/3 rounded-full bg-slate-900/90 border border-white/10 text-xs font-bold text-slate-200 whitespace-nowrap shadow-lg" dir="ltr"><?= esc_html($tech); ?></span>
                            </span>
                        <?php endforeach; ?>
                    </div>

                    <!-- Back layers (clipped by the frame) -->
                    <div class="hp-scene absolute inset-0 rounded-[2rem] overflow-hidden border border-emerald-400/15 shadow-2xl shadow-black/70" aria-hidden="true">
                        <?php if ($plate_url) : ?>
                            <img src="<?= esc_url($plate_url); ?>" alt="" width="640" height="640" decoding="async" class="hp-layer hp-plate absolute inset-0 w-full h-full object-cover opacity-40">
                        <?php endif; ?>
                        <canvas class="hp-layer hp-rain absolute inset-0 w-full h-full"></canvas>
                        <!-- volumetric light from above + floor glow + vignette -->
                        <div class="absolute inset-0 bg-[radial-gradient(ellipse_60%_45%_at_50%_0%,rgb(134_239_172/0.16),transparent_70%)]"></div>
                        <div class="absolute inset-x-0 bottom-0 h-1/3 bg-[radial-gradient(ellipse_70%_100%_at_50%_100%,rgb(0_255_65/0.14),transparent_70%)]"></div>
                        <div class="absolute inset-0 shadow-[inset_0_0_80px_30px_rgb(0_0_0/0.75)] rounded-[2rem]"></div>
                    </div>

                    <!-- Subject: not clipped, breaks out of the top of the frame -->
                    <img src="<?= esc_url($cutout_url); ?>" alt="کوروش محمدپور، طراح و توسعه‌دهنده وب" width="520" height="952"
                         fetchpriority="high" decoding="async"
                         class="hp-layer hp-person absolute bottom-0 left-1/2 w-[86%] h-auto max-w-none pointer-events-none select-none">

                    <!-- Foreground glyphs for depth -->
                    <div class="hp-layer hp-front absolute inset-0 pointer-events-none overflow-hidden rounded-[2rem]" aria-hidden="true">
                        <span class="hp-glyph" style="--x:12%;--d:7s;--delay:-1s">۰۱</span>
                        <span class="hp-glyph" style="--x:82%;--d:9s;--delay:-4s">&lt;/&gt;</span>
                        <span class="hp-glyph" style="--x:64%;--d:11s;--delay:-7s">{ }</span>
                    </div>

                    <div class="absolute -bottom-5 start-1/2 -translate-x-1/2 rtl:translate-x-1/2 w-[94%] sm:w-[85%] flex items-center gap-3 px-3.5 sm:px-4 py-3 rounded-2xl bg-slate-900/85 backdrop-blur-xl border border-white/10 shadow-xl z-20">
                        <span class="size-10 shrink-0 rounded-xl bg-secondary/15 border border-secondary/30 text-secondary flex items-center justify-center font-black text-sm" dir="ltr">&lt;/&gt;</span>
                        <span class="min-w-0">
                            <span class="block text-sm font-black text-white truncate">Korosh Mpr</span>
                            <span class="sm:hidden flex items-center gap-1.5 mt-0.5 text-[11px] font-bold text-emerald-300 whitespace-nowrap"><span class="size-1.5 rounded-full bg-emerald-400 shrink-0"></span><?= esc_html(kmpr_field('response_time', null, 'option')); ?></span>
                            <span class="hidden sm:block text-xs text-slate-400 truncate" dir="ltr"><?= esc_html(kmpr_field('hero_card_subtitle')); ?></span>
                        </span>
                    </div>

                    <div class="hidden sm:flex absolute -top-4 -end-8 float-y px-3.5 py-2 rounded-2xl bg-slate-900/85 backdrop-blur-xl border border-white/10 shadow-xl items-center gap-2 z-20">
                        <span class="size-2 rounded-full bg-emerald-400 shrink-0"></span>
                        <span class="text-xs font-bold text-slate-200 whitespace-nowrap"><?= esc_html(kmpr_field('response_time', null, 'option')); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <a href="#services" class="hidden lg:flex absolute bottom-6 left-1/2 -translate-x-1/2 flex-col items-center gap-2 text-slate-400 hover:text-white transition-colors" aria-label="اسکرول به بخش خدمات">
        <span class="w-6 h-10 rounded-full border-2 border-current flex justify-center pt-2">
            <span class="w-1 h-2 rounded-full bg-current scroll-cue"></span>
        </span>
    </a>
</section>
