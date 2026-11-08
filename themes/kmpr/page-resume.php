<?php
/**
 * Template Name: Resume / رزومه آنلاین
 *
 * Living, print-ready resume. Every visible text comes from the ACF group in inc/resume-manager.php
 * (page → Theme Settings › Online Resume → code defaults).
 *
 * @package KMPR
 */

get_header();

/* ---------- Data ---------- */
$photo_field = kmpr_resume('resume_photo', '') ?: (function_exists('get_field') ? get_field('hero-image', (int) get_option('page_on_front') ?: 44) : '');
$photo       = kmpr_image_src($photo_field, 'large');
$full_name   = kmpr_resume('resume_full_name');
$latin_name  = kmpr_resume('resume_latin_name');
$job_title   = kmpr_resume('resume_job_title');
$tagline     = kmpr_resume('resume_tagline');
$location    = kmpr_resume('resume_location');
$pdf_file    = kmpr_resume('resume_pdf_file', '');
$pdf_url     = is_array($pdf_file) ? ($pdf_file['url'] ?? '') : (is_numeric($pdf_file) ? wp_get_attachment_url((int) $pdf_file) : '');

$pid       = get_queried_object_id();
$available = (function_exists('get_field') && metadata_exists('post', $pid, 'resume_available')) ? get_field('resume_available', $pid) : (function_exists('get_field') ? get_field('resume_available', 'option') : true);
$available = ($available === null) ? true : (bool) $available;
$status    = $available ? kmpr_resume('resume_available_text') : kmpr_resume('resume_busy_text');

$summary     = kmpr_resume('resume_summary');
$focus       = kmpr_resume_rows('resume_focus', 'focus');
$skill_tabs  = array(
    array('file' => 'frontend.tsx', 'var' => 'frontend', 'label' => 'فرانت‌اند', 'items' => kmpr_resume_list(kmpr_resume('resume_skills_frontend'))),
    array('file' => 'backend.php', 'var' => 'backend', 'label' => 'بک‌اند و CMS', 'items' => kmpr_resume_list(kmpr_resume('resume_skills_backend'))),
    array('file' => 'toolkit.yml', 'var' => 'toolkit', 'label' => 'ابزارها و استانداردها', 'items' => kmpr_resume_list(kmpr_resume('resume_skills_tools'))),
);
$skill_tabs  = array_values(array_filter($skill_tabs, function ($t) { return $t['items']; }));
$marquee     = kmpr_lines(kmpr_resume('resume_tools_marquee'));
$experiences = kmpr_resume_rows('resume_experiences', 'experiences');
$education   = kmpr_resume_rows('resume_education', 'education');
$languages   = kmpr_resume_rows('resume_languages', 'languages');
$testimonials = array_values(array_filter(kmpr_resume_rows('resume_testimonials'), function ($t) { return !empty($t['quote']); }));
$domains     = array();
foreach (kmpr_lines(kmpr_resume('resume_project_domains')) as $line) {
    $parts = preg_split('/[:：]/u', $line, 2);
    if (count($parts) === 2) {
        $domains[] = array('label' => trim($parts[0]), 'items' => kmpr_resume_list($parts[1]));
    }
}

$years          = kmpr_years_experience();
$projects_total = (int) wp_count_posts('portfolio')->publish;
$skills_total   = array_sum(array_map(function ($t) { return count($t['items']); }, $skill_tabs));
$clients_total  = array_sum(array_map(function ($d) { return count($d['items']); }, $domains));

/* Selected projects: featured first, then latest. */
$proj_limit = max(2, min(12, (int) kmpr_resume('resume_projects_count', 6)));
$proj_ids   = get_posts(array('post_type' => 'portfolio', 'post_status' => 'publish', 'numberposts' => 30, 'fields' => 'ids', 'no_found_rows' => true));
usort($proj_ids, function ($a, $b) {
    return (int) (bool) get_post_meta($b, 'is_featured', true) - (int) (bool) get_post_meta($a, 'is_featured', true);
});
$proj_ids = array_slice($proj_ids, 0, $proj_limit);

$email     = kmpr_contact('email') ?: 'korosh.mpr@gmail.com';
$phone     = kmpr_contact('phone');
$phone_lbl = kmpr_field('phone_number_label', '', 'option') ?: $phone;
$github    = kmpr_contact('github');
$linkedin  = kmpr_contact('linkedin');
$telegram  = kmpr_contact_url('telegram');
$whatsapp  = kmpr_whatsapp_url('سلام کوروش، رزومه‌ات را در سایت دیدم.');

$arrow = '<svg width="18" height="18" class="shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.612l4.158 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.612 9.25H16.25A.75.75 0 0 1 17 10Z" clip-rule="evenodd"/></svg>';
$dl_icon = '<svg width="18" height="18" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v12m0 0-4-4m4 4 4-4M5 21h14"/></svg>';
$tg_icon = '<svg width="18" height="18" class="shrink-0" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M8.287 5.906q-1.168.486-4.666 2.01-.567.225-.595.442c-.03.243.275.339.69.47l.175.055c.408.133.958.288 1.243.294q.39.01.868-.32 3.269-2.206 3.374-2.23c.05-.012.12-.026.166.016s.042.12.037.141c-.03.129-1.227 1.241-1.846 1.817-.193.18-.33.307-.358.336a8 8 0 0 1-.188.186c-.38.366-.664.64.015 1.088.327.216.589.393.85.571.284.194.568.387.936.629q.14.092.27.187c.331.236.63.448.997.414.214-.02.435-.22.547-.82.265-1.417.786-4.486.906-5.751a1.4 1.4 0 0 0-.013-.315.34.34 0 0 0-.114-.217.53.53 0 0 0-.31-.093c-.3.005-.763.166-2.984 1.09"/></svg>';

/**
 * Section header (kicker + h2) — local to this template.
 */
$section_head = function ($kicker, $title, $id) {
    ?>
    <div class="mb-8 sm:mb-10 flex flex-col gap-3" data-aos="fade-up">
        <span class="inline-flex items-center gap-2 text-xs font-black text-secondary font-mono" dir="ltr"><span class="w-6 h-px bg-secondary"></span><?= esc_html($kicker); ?></span>
        <h2 id="<?= esc_attr($id); ?>" class="text-3xl sm:text-4xl font-black text-white leading-tight"><?= esc_html($title); ?></h2>
    </div>
    <?php
};
?>

<div class="rz relative isolate overflow-x-clip bg-slate-950 text-slate-300">

    <!-- ================= HERO ================= -->
    <section class="relative isolate pt-10 pb-16 lg:pt-16 lg:pb-24" aria-labelledby="rz-name">
        <div class="no-print absolute inset-0 hero-grid -z-10" aria-hidden="true"></div>
        <div class="no-print aurora w-[34rem] h-[34rem] -top-48 -start-40 bg-secondary/20 -z-10" aria-hidden="true"></div>
        <div class="no-print aurora w-[28rem] h-[28rem] top-40 -end-40 bg-indigo-600/20 -z-10 [animation-delay:-7s]" aria-hidden="true"></div>

        <div class="container mx-auto px-4 sm:px-6 max-w-6xl">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8 items-stretch">

                <!-- Terminal card -->
                <div class="rz-card lg:col-span-8 rounded-3xl bg-slate-900/80 border border-white/10 overflow-hidden backdrop-blur-xl shadow-2xl shadow-black/40" data-aos="fade-up">
                    <div class="no-print flex items-center gap-2 px-5 py-3 border-b border-white/10 bg-white/[0.02]" dir="ltr">
                        <span class="size-3 rounded-full bg-rose-500/80"></span>
                        <span class="size-3 rounded-full bg-amber-400/80"></span>
                        <span class="size-3 rounded-full bg-emerald-400/80"></span>
                        <span class="ms-3 text-xs font-mono text-slate-400 truncate">~/korosh/resume.md</span>
                    </div>

                    <div class="p-6 sm:p-10 flex flex-col gap-6">
                        <p class="no-print font-mono text-sm text-slate-400" dir="ltr"><span class="text-emerald-400">$</span> whoami<?php if ($latin_name) : ?> <span class="text-slate-500">→</span> <span class="text-secondary"><?= esc_html($latin_name); ?></span><?php endif; ?><span class="rz-caret" aria-hidden="true"></span></p>

                        <div class="flex flex-col gap-3">
                            <h1 id="rz-name" class="text-4xl sm:text-5xl lg:text-6xl font-black text-white tracking-tight leading-tight" data-split><?= esc_html($full_name); ?></h1>
                            <p class="text-lg sm:text-xl font-bold text-slate-200 leading-relaxed" data-aos="fade-up" data-aos-delay="250">
                                <?= kmpr_bidi($job_title); ?>
                            </p>
                            <p class="max-w-2xl text-sm sm:text-base text-slate-300 leading-loose" data-aos="fade-up" data-aos-delay="350"><?= esc_html($tagline); ?></p>
                        </div>

                        <ul class="flex flex-wrap gap-2 text-xs sm:text-sm" data-aos="fade-up" data-aos-delay="450">
                            <li class="inline-flex items-center gap-2 px-3 py-2 rounded-full border <?= $available ? 'border-emerald-400/30 bg-emerald-400/10 text-emerald-300' : 'border-amber-400/30 bg-amber-400/10 text-amber-300'; ?> font-bold">
                                <span class="relative flex size-2.5 shrink-0">
                                    <?php if ($available) : ?><span class="no-print animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span><?php endif; ?>
                                    <span class="relative inline-flex rounded-full size-2.5 <?= $available ? 'bg-emerald-400' : 'bg-amber-400'; ?>"></span>
                                </span>
                                <?= esc_html($status); ?>
                            </li>
                            <?php if ($location) : ?>
                                <li class="inline-flex items-center gap-2 px-3 py-2 rounded-full border border-white/10 bg-white/[0.03] text-slate-200">
                                    <svg width="14" height="14" class="shrink-0 text-secondary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                                    <?= esc_html($location); ?>
                                </li>
                            <?php endif; ?>
                            <li>
                                <a href="mailto:<?= esc_attr(antispambot($email)); ?>" class="inline-flex items-center gap-2 px-3 py-2 rounded-full border border-white/10 bg-white/[0.03] text-slate-200 hover:border-secondary/40 hover:text-white transition-colors font-mono" dir="ltr">
                                    <svg width="14" height="14" class="shrink-0 text-secondary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                                    <?= esc_html(antispambot($email)); ?>
                                </a>
                            </li>
                            <?php if ($phone) : ?>
                                <li>
                                    <a href="tel:<?= esc_attr(preg_replace('/[^\d+]/', '', $phone)); ?>" class="inline-flex items-center gap-2 px-3 py-2 rounded-full border border-white/10 bg-white/[0.03] text-slate-200 hover:border-secondary/40 hover:text-white transition-colors" dir="ltr">
                                        <svg width="14" height="14" class="shrink-0 text-secondary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.79 19.79 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                        <?= esc_html(kmpr_phone_display($phone)); ?>
                                    </a>
                                </li>
                            <?php endif; ?>
                            <?php foreach (array('GitHub' => $github, 'LinkedIn' => $linkedin) as $label => $url) : if (!$url) continue; ?>
                                <li>
                                    <a href="<?= esc_url($url); ?>" target="_blank" rel="noopener me" class="inline-flex items-center gap-2 px-3 py-2 rounded-full border border-white/10 bg-white/[0.03] text-slate-200 hover:border-secondary/40 hover:text-white transition-colors font-mono" dir="ltr"><?= esc_html($label); ?> ↗</a>
                                </li>
                            <?php endforeach; ?>
                        </ul>

                        <div class="no-print flex flex-col sm:flex-row flex-wrap gap-3 pt-2" data-aos="fade-up" data-aos-delay="550">
                            <?php if ($pdf_url) : ?>
                                <a href="<?= esc_url($pdf_url); ?>" download data-magnetic class="inline-flex items-center justify-center gap-2 whitespace-nowrap px-6 py-3 min-h-11 rounded-full bg-secondary hover:bg-amber-500 text-slate-950 font-black text-sm shadow-[0_10px_40px_-10px_rgb(217_119_6/0.7)] transition-colors"><?= $dl_icon; ?>دانلود PDF رزومه</a>
                            <?php else : ?>
                                <button type="button" onclick="window.print()" data-magnetic class="inline-flex items-center justify-center gap-2 whitespace-nowrap px-6 py-3 min-h-11 rounded-full bg-secondary hover:bg-amber-500 text-slate-950 font-black text-sm cursor-pointer transition-colors"><?= $dl_icon; ?>دانلود PDF رزومه</button>
                            <?php endif; ?>
                            <button type="button" onclick="window.print()" class="inline-flex items-center justify-center gap-2 whitespace-nowrap px-6 py-3 min-h-11 rounded-full bg-white/[0.04] hover:bg-white/[0.08] border border-white/15 text-slate-100 font-bold text-sm cursor-pointer transition-colors">
                                <svg width="18" height="18" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6z"/></svg>
                                نسخه چاپی
                            </button>
                            <?php if ($telegram) : ?>
                                <a href="<?= esc_url($telegram); ?>" target="_blank" rel="noopener" class="inline-flex items-center justify-center gap-2 whitespace-nowrap px-6 py-3 min-h-11 rounded-full bg-sky-500/10 hover:bg-sky-500/20 border border-sky-400/30 text-sky-300 font-bold text-sm transition-colors"><?= $tg_icon; ?>پیام در تلگرام</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Portrait -->
                <div class="lg:col-span-4 flex" data-aos="zoom-in" data-aos-delay="200">
                    <figure class="rz-photo relative w-full max-w-sm mx-auto lg:max-w-none rounded-3xl border-glow" data-tilt="6">
                        <div class="relative h-full rounded-3xl overflow-hidden border border-white/10 bg-slate-900">
                            <img src="<?= esc_url($photo['url']); ?>" width="<?= (int) $photo['width']; ?>" height="<?= (int) $photo['height']; ?>"
                                 alt="<?= esc_attr($full_name . ' — ' . $job_title); ?>" fetchpriority="high"
                                 class="w-full h-full aspect-[4/5] lg:aspect-auto object-cover object-top grayscale-[35%] hover:grayscale-0 transition duration-700">
                            <div class="no-print absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/10 to-transparent" aria-hidden="true"></div>
                            <figcaption class="no-print absolute bottom-4 inset-x-4 flex items-center justify-between gap-3 px-4 py-3 rounded-2xl bg-slate-950/80 border border-white/10 backdrop-blur-md">
                                <span class="text-sm font-black text-white truncate"><?= esc_html($full_name); ?></span>
                                <span class="text-xs font-mono text-secondary shrink-0" dir="ltr">&lt;dev /&gt;</span>
                            </figcaption>
                        </div>
                    </figure>
                </div>

                <!-- Live counters -->
                <dl class="lg:col-span-12 grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4" data-stagger="90">
                    <?php
                    $stats = array(
                        array($years, '+', 'سال تجربه حرفه‌ای', 'years'),
                        array($projects_total, '+', 'پروژه در نمونه‌کارها', 'projects'),
                        array(count($experiences), '', 'سابقه همکاری سازمانی', 'roles'),
                        array($skills_total, '', 'ابزار و تکنولوژی', 'stack'),
                    );
                    foreach ($stats as $s) : ?>
                        <div class="rz-card spotlight flex flex-col-reverse gap-1 p-5 sm:p-6 rounded-3xl bg-white/[0.025] border border-white/10 hover:border-secondary/40 transition-colors" data-aos="fade-up">
                            <dt class="text-sm text-slate-400 font-bold"><?= esc_html($s[2]); ?></dt>
                            <dd class="flex items-baseline justify-between gap-2">
                                <span class="text-3xl sm:text-4xl font-black text-white tabular-nums" data-count="<?= (int) $s[0]; ?>" data-suffix="<?= esc_attr($s[1]); ?>"><?= esc_html(kmpr_fa_num($s[0]) . $s[1]); ?></span>
                                <span class="no-print text-xs font-mono text-slate-400" dir="ltr">.<?= esc_html($s[3]); ?></span>
                            </dd>
                        </div>
                    <?php endforeach; ?>
                </dl>
            </div>
        </div>
    </section>

    <!-- ================= SUMMARY + FOCUS ================= -->
    <section class="py-14 lg:py-20" aria-labelledby="rz-summary">
        <div class="container mx-auto px-4 sm:px-6 max-w-6xl">
            <?php $section_head('01 — about.md', kmpr_resume('resume_title_summary'), 'rz-summary'); ?>
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <div class="rz-card lg:col-span-7 rounded-3xl bg-white/[0.025] border border-white/10 p-6 sm:p-8 relative overflow-hidden" data-aos="fade-up">
                    <span class="no-print absolute top-5 end-6 font-mono text-5xl text-white/[0.04] select-none" aria-hidden="true" dir="ltr">/**</span>
                    <p class="relative text-base sm:text-lg text-slate-200 leading-[2.1] sm:text-justify"><?= esc_html(wp_strip_all_tags($summary)); ?></p>
                </div>
                <?php if ($focus) : ?>
                    <div class="rz-card lg:col-span-5 rounded-3xl bg-slate-900/80 border border-white/10 p-6 sm:p-8" data-aos="fade-up" data-aos-delay="120">
                        <h3 class="text-base font-black text-white mb-5 flex items-center gap-2">
                            <svg width="18" height="18" class="shrink-0 text-secondary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                            تمرکز اصلی
                        </h3>
                        <ul class="flex flex-col gap-5" data-stagger="110">
                            <?php foreach ($focus as $f) : if (empty($f['label'])) continue; $lvl = max(0, min(100, (int) ($f['level'] ?? 0))); ?>
                                <li class="rz-meter group" data-aos="fade-up" style="--lvl:<?= esc_attr($lvl / 100); ?>">
                                    <div class="flex items-baseline justify-between gap-3 mb-2">
                                        <span class="text-sm font-bold text-slate-100"><?= esc_html($f['label']); ?></span>
                                        <span class="text-sm font-black text-secondary"><?= esc_html(kmpr_fa_num($lvl)); ?>٪</span>
                                    </div>
                                    <div class="h-2 rounded-full bg-white/[0.06] overflow-hidden" role="presentation">
                                        <div class="rz-meter__fill h-full rounded-full bg-gradient-to-l from-amber-300 to-secondary"></div>
                                    </div>
                                    <?php if (!empty($f['note'])) : ?>
                                        <p class="mt-1.5 text-xs text-slate-400 leading-relaxed"><?= esc_html($f['note']); ?></p>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- ================= SKILLS (IDE) ================= -->
    <?php if ($skill_tabs) : ?>
    <section class="py-14 lg:py-20" aria-labelledby="rz-skills">
        <div class="container mx-auto px-4 sm:px-6 max-w-6xl">
            <?php $section_head('02 — stack/', kmpr_resume('resume_title_skills'), 'rz-skills'); ?>
            <div class="rz-card rz-ide rounded-3xl bg-slate-900/80 border border-white/10 overflow-hidden shadow-2xl shadow-black/30" x-data="{ tab: 0 }" data-aos="fade-up">
                <div class="no-print flex items-stretch overflow-x-auto border-b border-white/10 bg-slate-950/60" role="tablist" aria-label="<?= esc_attr(kmpr_resume('resume_title_skills')); ?>" dir="ltr">
                    <?php foreach ($skill_tabs as $i => $t) : ?>
                        <button type="button" role="tab" id="rz-tab-<?= (int) $i; ?>" aria-controls="rz-pane-<?= (int) $i; ?>"
                                :aria-selected="(tab === <?= (int) $i; ?>).toString()" @click="tab = <?= (int) $i; ?>"
                                :class="tab === <?= (int) $i; ?> ? 'text-white bg-slate-900/80 border-secondary' : 'text-slate-400 border-transparent hover:text-slate-200'"
                                class="shrink-0 min-h-11 px-5 py-3 border-t-2 font-mono text-xs sm:text-sm whitespace-nowrap cursor-pointer transition-colors">
                            <?= esc_html($t['file']); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
                <?php foreach ($skill_tabs as $i => $t) : ?>
                    <div class="rz-pane grid grid-cols-[auto_1fr]" dir="ltr" id="rz-pane-<?= (int) $i; ?>" role="tabpanel" aria-labelledby="rz-tab-<?= (int) $i; ?>"
                         x-show="tab === <?= (int) $i; ?>" <?= $i ? 'x-cloak' : ''; ?>
                         x-transition:enter="transition duration-500" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
                        <ol class="no-print select-none py-6 ps-4 pe-3 text-end font-mono text-xs leading-[2.6rem] text-slate-600 border-e border-white/5" aria-hidden="true" dir="ltr">
                            <?php for ($n = 1; $n <= 3; $n++) : ?><li><?= $n; ?></li><?php endfor; ?>
                        </ol>
                        <div class="p-5 sm:p-6 min-w-0" dir="ltr">
                            <p class="font-mono text-xs sm:text-sm leading-[2.6rem] text-slate-400"><span class="text-violet-400">const</span> <span class="text-sky-300"><?= esc_html($t['var']); ?></span> = [ <span class="text-slate-400" dir="rtl">// <?= esc_html($t['label']); ?></span></p>
                            <ul class="flex flex-wrap gap-2 sm:gap-2.5 py-2 ps-4 sm:ps-6" data-stagger="40">
                                <?php foreach ($t['items'] as $skill) : ?>
                                    <li data-aos="zoom-in" class="rz-token inline-flex items-center min-h-10 px-3.5 py-2 rounded-xl bg-white/[0.04] border border-white/10 font-mono text-xs sm:text-sm text-amber-200 hover:text-slate-950 hover:bg-secondary hover:border-secondary hover:-translate-y-0.5 transition cursor-default">"<?= esc_html($skill); ?>"</li>
                                <?php endforeach; ?>
                            </ul>
                            <p class="font-mono text-xs sm:text-sm leading-[2.6rem] text-slate-400">];<span class="rz-caret" aria-hidden="true"></span></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <?php if ($marquee) : ?>
        <div class="no-print py-6 border-y border-white/5 bg-white/[0.015]" aria-label="ابزارها">
            <div class="marquee" style="--speed:45s;--gap:2.5rem" dir="ltr">
                <?php for ($loop = 0; $loop < 2; $loop++) : ?>
                    <ul class="marquee__track items-center"<?= $loop ? ' aria-hidden="true"' : ''; ?>>
                        <?php foreach ($marquee as $tool) : ?>
                            <li class="flex items-center gap-2.5 whitespace-nowrap font-mono text-sm sm:text-base font-bold text-slate-300"><span class="size-1.5 rounded-full bg-secondary"></span><?= esc_html($tool); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endfor; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- ================= TIMELINE ================= -->
    <?php if ($experiences) : ?>
    <section class="py-14 lg:py-20" aria-labelledby="rz-timeline">
        <div class="container mx-auto px-4 sm:px-6 max-w-6xl">
            <?php $section_head('03 — git log --career', kmpr_resume('resume_title_timeline'), 'rz-timeline'); ?>
            <ol class="rz-timeline relative flex flex-col gap-6 sm:gap-8 ps-8 sm:ps-12" data-progress>
                <span class="absolute top-2 bottom-2 start-[11px] sm:start-[19px] w-px bg-white/10" aria-hidden="true"></span>
                <span class="rz-timeline__line no-print absolute top-2 bottom-2 start-[11px] sm:start-[19px] w-px bg-gradient-to-b from-secondary via-amber-300 to-secondary" aria-hidden="true"></span>
                <?php foreach ($experiences as $i => $exp) :
                    $tags = kmpr_resume_list($exp['tags'] ?? '');
                    $hash = substr(md5(($exp['company'] ?? '') . $i), 0, 7);
                    ?>
                    <li class="rz-step relative" data-aos="fade-up">
                        <span class="rz-step__dot absolute -start-8 sm:-start-12 top-6 flex items-center justify-center size-6 sm:size-10 rounded-full border <?= $i === 0 ? 'border-secondary bg-secondary/20' : 'border-white/15 bg-slate-900'; ?>" aria-hidden="true">
                            <span class="size-2 sm:size-2.5 rounded-full <?= $i === 0 ? 'bg-secondary' : 'bg-slate-500'; ?>"></span>
                        </span>
                        <article class="rz-card spotlight rounded-3xl bg-white/[0.025] border <?= $i === 0 ? 'border-secondary/30' : 'border-white/10'; ?> hover:border-secondary/40 transition-colors p-5 sm:p-7">
                            <header class="flex flex-col sm:flex-row sm:items-start justify-between gap-2 sm:gap-6 mb-3">
                                <div class="min-w-0">
                                    <h3 class="text-lg sm:text-xl font-black text-white leading-snug"><?= kmpr_bidi($exp['job_title'] ?? ''); ?></h3>
                                    <?php if (!empty($exp['company'])) : ?>
                                        <p class="mt-1 text-sm font-bold text-secondary"><?= esc_html($exp['company']); ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <?php if ($i === 0) : ?><span class="no-print px-2.5 py-1 rounded-full bg-emerald-400/10 border border-emerald-400/30 text-emerald-300 text-xs font-black">الان</span><?php endif; ?>
                                    <span class="text-sm font-bold text-slate-300 whitespace-nowrap"><?= esc_html($exp['period'] ?? ''); ?></span>
                                </div>
                            </header>
                            <div class="rz-prose text-sm text-slate-300 leading-loose"><?= wp_kses_post($exp['description'] ?? ''); ?></div>
                            <?php if ($tags) : ?>
                                <div class="mt-4 pt-4 border-t border-dashed border-white/10 flex flex-wrap items-center gap-2" dir="ltr">
                                    <span class="no-print font-mono text-xs text-slate-400"><?= esc_html($hash); ?></span>
                                    <?php foreach ($tags as $tag) : ?>
                                        <span class="px-2.5 py-1 rounded-lg bg-white/[0.04] border border-white/10 font-mono text-xs text-slate-300"><?= esc_html($tag); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </article>
                    </li>
                <?php endforeach; ?>
            </ol>
        </div>
    </section>
    <?php endif; ?>

    <!-- ================= PROJECTS ================= -->
    <?php if ($proj_ids || $domains) : ?>
    <section class="py-14 lg:py-20" aria-labelledby="rz-projects">
        <div class="container mx-auto px-4 sm:px-6 max-w-6xl">
            <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
                <?php $section_head('04 — ls ./projects', kmpr_resume('resume_title_projects'), 'rz-projects'); ?>
                <a href="<?= esc_url(get_post_type_archive_link('portfolio') ?: home_url('/portfolios/')); ?>" class="no-print mb-10 inline-flex items-center gap-2 whitespace-nowrap self-start sm:self-auto px-6 py-3 min-h-11 rounded-full border border-white/15 hover:border-secondary/40 text-sm font-bold text-slate-100 transition-colors">
                    همه نمونه‌کارها <?= $arrow; ?>
                </a>
            </div>
            <?php if ($proj_ids) : ?>
                <ul class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6" data-stagger="90">
                    <?php foreach ($proj_ids as $p) :
                        $cover = kmpr_portfolio_cover($p, 'medium_large');
                        $term  = kmpr_primary_term($p, 'portfolio_categories');
                        $year  = function_exists('get_field') ? get_field('project_year', $p) : '';
                        ?>
                        <li data-aos="fade-up">
                            <a href="<?= esc_url(get_permalink($p)); ?>" class="rz-card rz-proj group flex flex-col h-full rounded-3xl bg-white/[0.025] border border-white/10 hover:border-secondary/40 overflow-hidden transition-colors">
                                <div class="no-print relative aspect-[16/10] overflow-hidden bg-slate-900">
                                    <?php if ($cover) : ?>
                                        <img src="<?= esc_url($cover['url']); ?>" width="<?= (int) $cover['width']; ?>" height="<?= (int) $cover['height']; ?>" alt="<?= esc_attr($cover['alt']); ?>" loading="lazy" decoding="async" class="w-full h-full object-cover object-top transition duration-700 group-hover:scale-105">
                                    <?php else : ?>
                                        <div class="w-full h-full hero-grid" aria-hidden="true"></div>
                                    <?php endif; ?>
                                    <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 to-transparent" aria-hidden="true"></div>
                                </div>
                                <div class="flex items-center justify-between gap-3 p-5">
                                    <div class="min-w-0">
                                        <h3 class="text-base font-black text-white group-hover:text-secondary transition-colors truncate"><?= esc_html(get_the_title($p)); ?></h3>
                                        <p class="mt-1 text-sm text-slate-400 truncate">
                                            <?= esc_html($term ? $term->name : 'نمونه‌کار'); ?><?php if ($year) : ?> · <?= esc_html(kmpr_fa_num($year)); ?><?php endif; ?>
                                        </p>
                                    </div>
                                    <span class="no-print shrink-0 flex items-center justify-center size-10 rounded-full border border-white/10 text-slate-300 group-hover:bg-secondary group-hover:text-slate-950 group-hover:border-secondary transition-colors"><?= $arrow; ?></span>
                                </div>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <?php if ($domains) : ?>
                <div class="rz-card mt-6 rounded-3xl bg-slate-900/80 border border-white/10 p-6 sm:p-8" data-aos="fade-up">
                    <h3 class="text-base font-black text-white mb-5 flex flex-wrap items-center gap-2">
                        برندها و حوزه‌هایی که برایشان کار کرده‌ام
                        <span class="text-sm font-black text-secondary">(<?= esc_html(kmpr_fa_num($clients_total)); ?>)</span>
                    </h3>
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-5">
                        <?php foreach ($domains as $d) : ?>
                            <div>
                                <dt class="text-sm font-black text-secondary mb-2"><?= esc_html($d['label']); ?></dt>
                                <dd class="flex flex-wrap gap-1.5">
                                    <?php foreach ($d['items'] as $item) : ?>
                                        <span class="px-2.5 py-1 rounded-lg bg-white/[0.03] border border-white/10 text-sm text-slate-200"><?= esc_html($item); ?></span>
                                    <?php endforeach; ?>
                                </dd>
                            </div>
                        <?php endforeach; ?>
                    </dl>
                </div>
            <?php endif; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- ================= EDUCATION + LANGUAGES ================= -->
    <section class="py-14 lg:py-20" aria-labelledby="rz-edu">
        <div class="container mx-auto px-4 sm:px-6 max-w-6xl">
            <?php $section_head('05 — education', kmpr_resume('resume_title_education'), 'rz-edu'); ?>
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <ul class="lg:col-span-8 grid grid-cols-1 sm:grid-cols-2 gap-4" data-stagger="90">
                    <?php foreach ($education as $edu) : ?>
                        <li class="rz-card spotlight flex gap-4 p-5 sm:p-6 rounded-3xl bg-white/[0.025] border border-white/10 hover:border-secondary/40 transition-colors" data-aos="fade-up">
                            <span class="no-print shrink-0 flex items-center justify-center size-11 rounded-2xl bg-secondary/15 text-secondary">
                                <svg width="20" height="20" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
                            </span>
                            <div class="min-w-0">
                                <span class="text-sm font-bold text-secondary"><?= esc_html($edu['period'] ?? ''); ?></span>
                                <h3 class="mt-1 text-base font-black text-white leading-snug"><?= esc_html($edu['degree'] ?? ''); ?></h3>
                                <p class="mt-1 text-sm text-slate-400"><?= esc_html($edu['institution'] ?? ''); ?></p>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <?php if ($languages) : ?>
                    <div class="rz-card lg:col-span-4 rounded-3xl bg-slate-900/80 border border-white/10 p-6 sm:p-7" data-aos="fade-up" data-aos-delay="150">
                        <h3 class="text-base font-black text-white mb-5"><?= esc_html(kmpr_resume('resume_title_languages')); ?></h3>
                        <ul class="flex flex-col gap-5">
                            <?php foreach ($languages as $lang) : if (empty($lang['name'])) continue; $lvl = max(0, min(100, (int) ($lang['level'] ?? 0))); ?>
                                <li class="rz-meter" data-aos="fade-up" style="--lvl:<?= esc_attr($lvl / 100); ?>">
                                    <div class="flex items-baseline justify-between gap-3 mb-2">
                                        <span class="text-sm font-bold text-slate-100"><?= esc_html($lang['name']); ?></span>
                                        <span class="text-xs text-slate-400"><?= esc_html($lang['level_label'] ?? ''); ?></span>
                                    </div>
                                    <div class="h-2 rounded-full bg-white/[0.06] overflow-hidden" role="presentation"><div class="rz-meter__fill h-full rounded-full bg-gradient-to-l from-sky-300 to-indigo-500"></div></div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- ================= TESTIMONIALS (only real data) ================= -->
    <?php if ($testimonials) : ?>
    <section class="py-14 lg:py-20" aria-labelledby="rz-testi">
        <div class="container mx-auto px-4 sm:px-6 max-w-6xl">
            <?php $section_head('06 — reviews', kmpr_resume('resume_title_testimonials'), 'rz-testi'); ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6" data-stagger="100">
                <?php foreach ($testimonials as $t) : ?>
                    <figure class="rz-card rounded-3xl bg-white/[0.025] border border-white/10 p-6 sm:p-8" data-aos="fade-up">
                        <blockquote class="text-base text-slate-200 leading-loose">«<?= esc_html($t['quote']); ?>»</blockquote>
                        <figcaption class="mt-5 text-sm"><strong class="text-white font-black"><?= esc_html($t['name'] ?? ''); ?></strong><?php if (!empty($t['role'])) : ?> <span class="text-slate-400">— <?= esc_html($t['role']); ?></span><?php endif; ?></figcaption>
                    </figure>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- ================= CTA ================= -->
    <section class="no-print pb-20 lg:pb-28 pt-6" aria-labelledby="rz-cta">
        <div class="container mx-auto px-4 sm:px-6 max-w-6xl">
            <div class="relative isolate overflow-hidden rounded-3xl border border-white/10 bg-slate-900/80 p-8 sm:p-12 lg:p-16 border-glow" data-aos="zoom-in">
                <div class="aurora w-96 h-96 -top-32 -end-20 bg-secondary/25 -z-10" aria-hidden="true"></div>
                <div class="absolute inset-0 hero-grid -z-10 opacity-60" aria-hidden="true"></div>
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                    <div class="lg:col-span-7">
                        <p class="font-mono text-sm text-slate-400 mb-4" dir="ltr"><span class="text-emerald-400">$</span> hire korosh --freelance<span class="rz-caret" aria-hidden="true"></span></p>
                        <h2 id="rz-cta" class="text-3xl sm:text-4xl lg:text-5xl font-black text-white leading-tight" data-split><?= esc_html(kmpr_resume('resume_cta_title')); ?></h2>
                        <p class="mt-5 text-slate-300 leading-loose max-w-xl"><?= esc_html(kmpr_resume('resume_cta_text')); ?></p>
                    </div>
                    <div class="lg:col-span-5 flex flex-col gap-3">
                        <a href="<?= esc_url(kmpr_request_url()); ?>" data-magnetic class="inline-flex items-center justify-center gap-2.5 whitespace-nowrap px-8 py-3.5 min-h-11 rounded-full bg-secondary hover:bg-amber-500 text-slate-950 font-black text-sm sm:text-base shadow-[0_10px_40px_-10px_rgb(217_119_6/0.7)] transition-colors">شروع پروژه با من <?= $arrow; ?></a>
                        <?php if ($telegram) : ?>
                            <a href="<?= esc_url($telegram); ?>" target="_blank" rel="noopener" class="inline-flex items-center justify-center gap-2 whitespace-nowrap px-8 py-3.5 min-h-11 rounded-full bg-white/[0.04] hover:bg-white/[0.08] border border-white/15 text-slate-100 font-bold text-sm transition-colors"><?= $tg_icon; ?>پیام در تلگرام</a>
                        <?php endif; ?>
                        <?php if ($whatsapp) : ?>
                            <a href="<?= esc_url($whatsapp); ?>" target="_blank" rel="noopener" class="inline-flex items-center justify-center gap-2 whitespace-nowrap px-8 py-3.5 min-h-11 rounded-full bg-emerald-500/10 hover:bg-emerald-500/20 border border-emerald-400/30 text-emerald-300 font-bold text-sm transition-colors">گفت‌وگو در واتساپ</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php
/* ---------- Person JSON-LD ---------- */
$same_as = array_values(array_filter(array($linkedin, $github, kmpr_contact('instagram'), $telegram)));
$person  = array(
    '@context'   => 'https://schema.org',
    '@type'      => 'ProfilePage',
    'url'        => get_permalink(),
    'inLanguage' => 'fa-IR',
    'mainEntity' => array_filter(array(
        '@type'         => 'Person',
        '@id'           => untrailingslashit(home_url('/')) . '/#person',
        'name'          => $full_name,
        'alternateName' => $latin_name,
        'jobTitle'      => wp_strip_all_tags($job_title),
        'description'   => wp_strip_all_tags($tagline),
        'image'         => $photo['url'],
        'url'           => home_url('/'),
        'email'         => 'mailto:' . $email,
        'telephone'     => $phone,
        'address'       => $location ? array('@type' => 'PostalAddress', 'addressLocality' => $location, 'addressCountry' => 'IR') : null,
        'worksFor'      => !empty($experiences[0]['company']) ? array('@type' => 'Organization', 'name' => $experiences[0]['company']) : null,
        'alumniOf'      => array_values(array_filter(array_map(function ($e) {
            return !empty($e['institution']) && preg_match('/دانشگاه|university/iu', $e['institution']) ? array('@type' => 'CollegeOrUniversity', 'name' => $e['institution']) : null;
        }, $education))),
        'knowsAbout'    => array_values(array_unique(array_merge(...array_map(function ($t) { return $t['items']; }, $skill_tabs ?: array(array('items' => array())))))),
        'knowsLanguage' => array_values(array_filter(array_map(function ($l) { return $l['name'] ?? ''; }, $languages))),
        'sameAs'        => $same_as,
    )),
);
// Yoast's WebPage is typed ProfilePage in inc/schema-manager.php; here only the Person facts (same @id) are added.
echo '<script type="application/ld+json">' . wp_json_encode(array('@context' => 'https://schema.org') + $person['mainEntity'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';

get_footer();
