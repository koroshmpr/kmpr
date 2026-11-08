<?php
/**
 * Author box (lesson page). Bio from the WP user profile, falls back to theme copy.
 */
$author_id   = (int) get_post_field('post_author', get_the_ID());
$author_name = kmpr_author_name();
$author_bio  = get_the_author_meta('description', $author_id) ?: sprintf('من کوروش محمدپور هستم؛ %s با بیش از %s سال تجربه. این درس‌ها را از دل پروژه‌های واقعی می‌نویسم تا مستقیم در کارت به درد بخورند.', kmpr_field('job_title', 'طراح و توسعه‌دهنده وب', 'option'), kmpr_fa_num(kmpr_years_experience()));
$avatar      = get_template_directory_uri() . '/public/images/korosh-mpr.webp';
?>
<section id="author-section" aria-label="درباره نویسنده" data-aos="fade-up"
         class="relative overflow-hidden rounded-3xl bg-white/[0.025] border border-white/10 p-6 sm:p-8 flex flex-col sm:flex-row items-center sm:items-start gap-6 text-center sm:text-start">
    <div class="pointer-events-none absolute -top-16 -end-16 size-48 rounded-full bg-secondary/10 blur-3xl" aria-hidden="true"></div>
    <img src="<?= esc_url($avatar); ?>" alt="<?= esc_attr($author_name); ?>" width="96" height="96" loading="lazy"
         class="relative shrink-0 size-20 sm:size-24 rounded-2xl object-cover object-top border border-white/15">
    <div class="relative flex-1 min-w-0">
        <p class="text-xs font-bold text-slate-400 mb-1">نویسنده این درس</p>
        <p class="text-lg font-black text-white"><?= esc_html($author_name); ?></p>
        <p class="mt-3 text-sm text-slate-300 leading-loose"><?= esc_html($author_bio); ?></p>
        <div class="mt-5 flex flex-wrap justify-center sm:justify-start gap-3">
            <a href="<?= esc_url(home_url('/resume/')); ?>" class="inline-flex items-center min-h-11 px-6 py-3 rounded-full border border-white/15 hover:border-secondary/50 text-sm font-bold text-slate-200 hover:text-white whitespace-nowrap transition-colors">رزومه من</a>
            <a href="<?= esc_url(kmpr_request_url()); ?>" class="inline-flex items-center min-h-11 px-6 py-3 rounded-full bg-secondary hover:bg-amber-500 text-slate-950 text-sm font-black whitespace-nowrap transition-colors">شروع همکاری</a>
        </div>
    </div>
</section>
