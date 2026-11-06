<?php
$author_id   = get_post_field('post_author', get_the_ID());
$author_name = get_the_author_meta('display_name', $author_id) ?: 'کوروش محمدپور';
$author_bio  = get_the_author_meta('description', $author_id) ?: 'توسعه‌دهنده ارشد وب و معمار نرم‌افزار با بیش از ۷ سال تجربه در طراحی و توسعه پلتفرم‌های پرسرعت، بدون بدهی فنی و مبتنی بر کدهای اختصاصی PHP، وردپرس پیشرفته و فریم‌ورک‌های React و Next.js.';
?>
<div id="author-section" class="my-8 bg-slate-900/80 border border-white/10 rounded-3xl p-6 sm:p-8 flex flex-col sm:flex-row items-center sm:items-start gap-6 text-center sm:text-start shadow-xl backdrop-blur-md relative overflow-hidden">
    <!-- Ambient subtle glow -->
    <div class="absolute -top-10 -end-10 w-40 h-40 bg-secondary/10 rounded-full blur-2xl pointer-events-none"></div>

    <!-- Avatar -->
    <div class="shrink-0 relative">
        <div class="size-20 sm:size-24 rounded-2xl overflow-hidden border-2 border-secondary/40 shadow-xl bg-slate-950">
            <img class="w-full h-full object-cover object-top filter grayscale contrast-110" 
                 src="<?= esc_url(get_template_directory_uri() . '/public/images/korosh-mpr.webp'); ?>" 
                 alt="کوروش محمدپور">
        </div>
        <span class="absolute -bottom-1 -end-1 size-4 rounded-full bg-emerald-400 border-2 border-slate-900 shadow" title="آنلاین و در دسترس"></span>
    </div>

    <!-- Bio & Details -->
    <div class="flex flex-col gap-2.5 flex-1 min-w-0">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
                <div class="flex items-center justify-center sm:justify-start gap-2">
                    <h4 class="text-base sm:text-lg font-black text-white"><?= esc_html($author_name); ?></h4>
                    <span class="text-[10px] font-mono px-2 py-0.5 rounded-full bg-secondary/15 text-secondary border border-secondary/30 font-bold">Senior Architect</span>
                </div>
                <span class="text-xs text-slate-400 font-medium">معمار سیستم‌های مدرن وب و متخصص پرفورمنس</span>
            </div>

            <!-- Profile / Resume Link -->
            <a href="<?= esc_url(home_url('/resume/')); ?>" 
               class="inline-flex items-center justify-center gap-1.5 text-xs font-black text-secondary hover:text-white px-3.5 py-1.5 rounded-xl bg-white/[0.04] hover:bg-secondary/20 border border-white/10 hover:border-secondary/40 transition-all self-center sm:self-auto">
                <span>مشاهده رزومه آنلاین</span>
                <svg class="size-3.5 rotate-180" viewBox="0 0 16 16" fill="currentColor">
                    <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8z"/>
                </svg>
            </a>
        </div>

        <p class="text-xs sm:text-sm text-slate-300 leading-relaxed font-medium">
            <?= esc_html($author_bio); ?>
        </p>
    </div>
</div>
