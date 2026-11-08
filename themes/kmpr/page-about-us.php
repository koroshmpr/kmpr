<?php
/**
 * Template Name: About Us Page - Personal Brand
 * Content: ACF group «درباره من» (inc/resume-manager.php) + resume experiences.
 *
 * @package KMPR
 */
get_header();

$photo     = kmpr_image_src(kmpr_field('aboutus_image', ''), 'large');
$cutout_v  = function_exists('get_field') ? get_field('hero_cutout', (int) get_option('page_on_front')) : '';
$cutout    = is_array($cutout_v) ? ($cutout_v['url'] ?? '') : (is_numeric($cutout_v) ? wp_get_attachment_image_url((int) $cutout_v, 'large') : (string) $cutout_v);
$cutout    = $cutout ?: get_template_directory_uri() . '/public/images/hero/korosh-cutout.webp';
$title     = wp_strip_all_tags(kmpr_field('aboutus-title'));
$content   = kmpr_field('aboutus-content');
$values    = kmpr_field('about_values', array());
$values    = (is_array($values) && $values) ? $values : kmpr_resume_seed('about_values');
$journey   = array_slice(kmpr_resume_rows('resume_experiences', 'experiences'), 0, 4);
$years     = kmpr_years_experience();
$projects  = (int) wp_count_posts('portfolio')->publish;
$articles  = (int) wp_count_posts('post')->publish;
$resume    = get_page_by_path('resume');
$resume_url = $resume ? get_permalink($resume) : home_url('/resume/');
$icons = array(
    '<polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>',
    '<path d="m16 18 6-6-6-6"/><path d="m8 6-6 6 6 6"/>',
    '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
    '<path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/>',
);
?>

<div class="relative isolate overflow-x-clip bg-slate-950">

    <!-- Hero -->
    <section class="relative isolate py-16 lg:py-24" aria-labelledby="about-title">
        <div class="absolute inset-0 hero-grid -z-10" aria-hidden="true"></div>
        <div class="aurora w-[34rem] h-[34rem] -top-40 -start-40 bg-secondary/20 -z-10" aria-hidden="true"></div>
        <div class="aurora w-[28rem] h-[28rem] bottom-0 -end-32 bg-indigo-600/20 -z-10 [animation-delay:-6s]" aria-hidden="true"></div>

        <div class="container mx-auto px-4 sm:px-6 max-w-6xl">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-10 items-center">
                <div class="lg:col-span-7 flex flex-col items-center lg:items-start text-center lg:text-start">
                    <span class="inline-flex items-center gap-2.5 px-4 py-2 rounded-full bg-white/[0.04] border border-white/10 backdrop-blur-xl text-xs font-bold text-slate-200" data-aos="fade-down">
                        <span class="size-2 rounded-full bg-secondary animate-pulse"></span><?= esc_html(kmpr_field('about_badge')); ?>
                    </span>
                    <h1 id="about-title" class="mt-6 text-3xl sm:text-5xl lg:text-[3.4rem] font-black text-white leading-[1.35] tracking-tight" data-split><?= esc_html($title); ?></h1>
                    <div class="mt-6 text-base sm:text-lg text-slate-300 leading-loose space-y-4 max-w-2xl [&_strong]:text-white" data-aos="fade-up" data-aos-delay="300">
                        <?= wp_kses_post(preg_replace('/\s(class|data-aos[\w-]*)="[^"]*"/', '', $content)); ?>
                    </div>

                    <dl class="mt-10 grid grid-cols-3 gap-4 sm:gap-10 w-full sm:w-auto border-t border-white/10 pt-8" data-stagger="100">
                        <?php foreach (array(array($years, 'سال تجربه'), array($projects, 'نمونه‌کار'), array($articles, 'مقاله آموزشی')) as $s) : ?>
                            <div class="flex flex-col-reverse" data-aos="fade-up">
                                <dt class="text-xs sm:text-sm text-slate-400 font-bold"><?= esc_html($s[1]); ?></dt>
                                <dd class="text-3xl sm:text-4xl font-black text-white tabular-nums mb-1" data-count="<?= (int) $s[0]; ?>" data-suffix="+"><?= esc_html(kmpr_fa_num($s[0])); ?>+</dd>
                            </div>
                        <?php endforeach; ?>
                    </dl>

                    <div class="mt-9 flex flex-col sm:flex-row gap-3 w-full sm:w-auto" data-aos="fade-up" data-aos-delay="400">
                        <a href="<?= esc_url($resume_url); ?>" data-magnetic class="inline-flex items-center justify-center gap-2 whitespace-nowrap px-8 py-3.5 min-h-11 rounded-full bg-secondary hover:bg-amber-500 text-slate-950 font-black text-sm transition-colors">مشاهده رزومه کامل</a>
                        <a href="<?= esc_url(get_post_type_archive_link('portfolio') ?: home_url('/portfolios/')); ?>" class="inline-flex items-center justify-center gap-2 whitespace-nowrap px-8 py-3.5 min-h-11 rounded-full bg-white/[0.04] hover:bg-white/[0.08] border border-white/15 text-slate-100 font-bold text-sm transition-colors">نمونه‌کارهای من</a>
                    </div>
                </div>

                <!-- Portrait: same cut-out as the homepage, warm "editorial" treatment (arch + amber backlight + outlined name) -->
                <div class="lg:col-span-5 flex justify-center pt-24 sm:pt-16 lg:pt-10" data-aos="zoom-in" data-aos-delay="200">
                    <figure class="about-portrait relative w-[16.5rem] sm:w-[20rem] xl:w-[22rem] aspect-[4/5]" data-tilt="5">
                        <div class="absolute inset-0 rounded-t-full rounded-b-[2rem] overflow-hidden border border-amber-300/30 bg-[#120c16] shadow-[0_0_60px_-10px_rgb(245_158_11/0.35)]" aria-hidden="true">
                            <div class="absolute inset-0 bg-[radial-gradient(ellipse_80%_60%_at_50%_100%,rgb(245_158_11/0.55),transparent_68%),radial-gradient(ellipse_70%_50%_at_50%_0%,rgb(99_102_241/0.35),transparent_70%)]"></div>
                            <div class="absolute inset-0 opacity-40 bg-[repeating-linear-gradient(90deg,rgb(255_255_255/0.04)_0_1px,transparent_1px_22px)]"></div>
                            <span class="about-name absolute top-[9%] left-1/2 -translate-x-1/2 whitespace-nowrap text-[4.6rem] sm:text-[5.6rem] xl:text-[6.2rem] font-black leading-none tracking-tight text-transparent [-webkit-text-stroke:1.5px_rgb(251_191_36/0.7)] [text-shadow:0_0_30px_rgb(245_158_11/0.25)] select-none" dir="ltr">KOROSH</span>
                            <div class="absolute inset-x-6 bottom-0 h-1/4 bg-[radial-gradient(ellipse_at_50%_100%,rgb(251_191_36/0.35),transparent_70%)]"></div>
                        </div>
                        <img src="<?= esc_url($cutout); ?>" width="520" height="952" alt="<?= esc_attr('کوروش محمدپور — ' . kmpr_field('about_image_caption')); ?>" fetchpriority="high" decoding="async"
                             class="about-person absolute bottom-0 left-1/2 w-[80%] h-auto max-w-none pointer-events-none select-none">
                        <figcaption class="absolute -bottom-5 left-1/2 -translate-x-1/2 w-[96%] sm:w-[92%] flex items-center justify-between gap-3 px-3.5 sm:px-4 py-3 rounded-2xl bg-slate-950/85 backdrop-blur-md border border-white/10 shadow-xl z-10">
                            <span class="text-sm font-black text-white whitespace-nowrap">کوروش محمدپور</span>
                            <span class="text-[11px] sm:text-xs font-bold text-amber-300 truncate" dir="ltr"><?= esc_html(kmpr_field('about_image_caption')); ?></span>
                        </figcaption>
                        <span class="hidden sm:inline-flex absolute top-[38%] -end-10 float-y items-center gap-2 px-3.5 py-2 rounded-2xl bg-slate-900/85 backdrop-blur-xl border border-white/10 shadow-xl text-xs font-bold text-slate-200 whitespace-nowrap z-10">
                            <span class="text-secondary font-black tabular-nums"><?= esc_html(kmpr_fa_num($years)); ?>+</span> سال کدنویسی
                        </span>
                    </figure>
                </div>
            </div>
        </div>
    </section>

    <!-- Values -->
    <section class="py-16 lg:py-24" aria-labelledby="about-values">
        <div class="container mx-auto px-4 sm:px-6 max-w-6xl">
            <div class="mb-10 flex flex-col gap-3 max-w-2xl" data-aos="fade-up">
                <span class="inline-flex items-center gap-2 text-xs font-black text-secondary"><span class="w-6 h-px bg-secondary"></span>اصول کاری من</span>
                <h2 id="about-values" class="text-3xl sm:text-4xl lg:text-5xl font-black text-white leading-tight"><?= esc_html(kmpr_field('about_values_title')); ?></h2>
                <p class="text-slate-300 leading-loose"><?= esc_html(kmpr_field('about_values_text')); ?></p>
            </div>
            <ul class="grid grid-cols-1 md:grid-cols-2 gap-5" data-stagger="100">
                <?php foreach ($values as $i => $v) : if (empty($v['title'])) continue; ?>
                    <li class="spotlight group flex flex-col gap-5 p-7 sm:p-8 rounded-3xl bg-white/[0.025] border border-white/10 hover:border-secondary/40 transition-colors" data-aos="fade-up" data-tilt="3">
                        <div class="flex items-center justify-between gap-3">
                            <span class="flex items-center justify-center size-12 rounded-2xl bg-secondary/15 text-secondary group-hover:scale-110 transition-transform">
                                <svg width="22" height="22" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?= $icons[$i % 4]; ?></svg>
                            </span>
                            <?php if (!empty($v['tag'])) : ?><span class="text-xs font-mono font-bold text-slate-400" dir="ltr"><?= esc_html($v['tag']); ?></span><?php endif; ?>
                        </div>
                        <div>
                            <h3 class="text-lg sm:text-xl font-black text-white group-hover:text-secondary transition-colors mb-2"><?= esc_html($v['title']); ?></h3>
                            <p class="text-sm sm:text-base text-slate-300 leading-loose"><?= esc_html($v['text'] ?? ''); ?></p>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </section>

    <!-- Journey -->
    <?php if ($journey) : ?>
    <section class="py-16 lg:py-24" aria-labelledby="about-journey">
        <div class="container mx-auto px-4 sm:px-6 max-w-6xl">
            <div class="mb-10 flex flex-col sm:flex-row sm:items-end justify-between gap-4" data-aos="fade-up">
                <div class="flex flex-col gap-3">
                    <span class="inline-flex items-center gap-2 text-xs font-black text-secondary"><span class="w-6 h-px bg-secondary"></span>مسیر حرفه‌ای</span>
                    <h2 id="about-journey" class="text-3xl sm:text-4xl lg:text-5xl font-black text-white leading-tight"><?= esc_html(kmpr_field('about_journey_title')); ?></h2>
                </div>
                <a href="<?= esc_url($resume_url); ?>" class="self-start sm:self-auto inline-flex items-center gap-2 whitespace-nowrap px-6 py-3 min-h-11 rounded-full border border-white/15 hover:border-secondary/40 text-sm font-bold text-slate-100 transition-colors">رزومه کامل ←</a>
            </div>
            <ol class="relative grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5" data-stagger="110">
                <span class="hidden lg:block absolute top-6 inset-x-6 h-px bg-gradient-to-l from-secondary via-white/15 to-transparent" aria-hidden="true"></span>
                <?php foreach ($journey as $i => $j) : ?>
                    <li class="relative flex flex-col gap-3" data-aos="fade-up">
                        <span class="relative z-10 flex items-center justify-center size-12 rounded-full border <?= $i === 0 ? 'border-secondary bg-secondary text-slate-950' : 'border-white/15 bg-slate-900 text-slate-300'; ?> text-base font-black"><?= esc_html(kmpr_fa_num(str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT))); ?></span>
                        <span class="text-sm font-bold text-secondary"><?= esc_html($j['period'] ?? ''); ?></span>
                        <h3 class="text-base font-black text-white leading-snug"><?= esc_html($j['job_title'] ?? ''); ?></h3>
                        <p class="text-sm text-slate-400"><?= esc_html($j['company'] ?? ''); ?></p>
                    </li>
                <?php endforeach; ?>
            </ol>
        </div>
    </section>
    <?php endif; ?>

    <!-- CTA -->
    <section class="pb-20 lg:pb-28 pt-6" aria-labelledby="about-cta">
        <div class="container mx-auto px-4 sm:px-6 max-w-5xl">
            <div class="relative isolate overflow-hidden rounded-3xl border border-white/10 bg-slate-900/80 p-8 sm:p-12 text-center border-glow" data-aos="zoom-in">
                <div class="aurora w-80 h-80 -top-28 -end-16 bg-secondary/25 -z-10" aria-hidden="true"></div>
                <h2 id="about-cta" class="text-2xl sm:text-4xl font-black text-white leading-tight" data-split><?= esc_html(kmpr_field('about_cta_title')); ?></h2>
                <p class="mt-4 text-slate-300 leading-loose max-w-2xl mx-auto"><?= esc_html(kmpr_field('about_cta_text')); ?></p>
                <div class="mt-8 flex flex-col sm:flex-row items-stretch sm:items-center justify-center gap-3">
                    <a href="<?= esc_url(kmpr_request_url()); ?>" data-magnetic class="inline-flex items-center justify-center whitespace-nowrap px-8 py-3.5 min-h-11 rounded-full bg-secondary hover:bg-amber-500 text-slate-950 font-black text-sm transition-colors">شروع پروژه با من</a>
                    <a href="<?= esc_url(kmpr_contact_url('telegram')); ?>" target="_blank" rel="noopener" class="inline-flex items-center justify-center whitespace-nowrap px-8 py-3.5 min-h-11 rounded-full bg-white/[0.04] hover:bg-white/[0.08] border border-white/15 text-slate-100 font-bold text-sm transition-colors">پیام در تلگرام</a>
                    <a href="mailto:<?= esc_attr(antispambot(kmpr_contact('email') ?: 'korosh.mpr@gmail.com')); ?>" class="inline-flex items-center justify-center whitespace-nowrap px-8 py-3.5 min-h-11 rounded-full bg-white/[0.04] hover:bg-white/[0.08] border border-white/15 text-slate-100 font-bold text-sm transition-colors font-mono" dir="ltr"><?= esc_html(antispambot(kmpr_contact('email') ?: 'korosh.mpr@gmail.com')); ?></a>
                </div>
            </div>
        </div>
    </section>
</div>

<?php get_footer(); ?>
