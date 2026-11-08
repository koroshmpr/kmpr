<?php
/**
 * Template Name: Contact Us Page - Personal Brand: Korosh Mpr
 * Copy: ACF «محتوای صفحه ارتباط با من» + «ارتباط با من — بخش‌های تکمیلی»; contacts: kmpr_contact().
 *
 * @package KMPR
 */
get_header();

$email     = kmpr_contact('email') ?: 'korosh.mpr@gmail.com';
$phone     = kmpr_contact('phone');
$phone_lbl = kmpr_field('phone_number_label', '', 'option') ?: $phone;
$telegram  = kmpr_contact_url('telegram');
$whatsapp  = kmpr_whatsapp_url('سلام کوروش، درباره یک پروژه وب می‌خواستم صحبت کنم.');
$address   = kmpr_contact('address');

$channels = array_filter(array(
    $whatsapp ? array('url' => $whatsapp, 'ext' => true, 'title' => 'واتساپ', 'desc' => 'چت، ارسال فایل و ویس', 'value' => 'WhatsApp', 'color' => 'emerald', 'cta' => 'ارسال پیام',
        'icon' => '<path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91C21.95 6.45 17.5 2 12.04 2m.01 1.67a8.24 8.24 0 0 1 8.23 8.24c0 4.54-3.7 8.23-8.24 8.23-1.48 0-2.93-.39-4.19-1.15l-.3-.17-3.12.82.83-3.04-.2-.32a8.2 8.2 0 0 1-1.26-4.38c.01-4.54 3.7-8.23 8.25-8.23M8.53 7.33c-.16 0-.43.06-.66.31-.22.25-.87.86-.87 2.07 0 1.22.89 2.39 1 2.56.14.17 1.76 2.67 4.25 3.73.59.27 1.05.42 1.41.53.59.19 1.13.16 1.56.1.48-.07 1.46-.6 1.67-1.18s.21-1.07.15-1.18c-.07-.1-.23-.16-.48-.27-.25-.14-1.47-.74-1.69-.82-.23-.08-.37-.12-.56.12-.16.25-.64.81-.78.97-.15.17-.29.19-.53.07-.26-.13-1.06-.39-2-1.23-.74-.66-1.23-1.47-1.38-1.72-.12-.24-.01-.39.11-.5.11-.11.27-.29.37-.44.13-.14.17-.25.25-.41.08-.17.04-.31-.02-.43-.06-.11-.56-1.35-.77-1.84-.2-.48-.4-.42-.56-.43-.14 0-.3-.01-.47-.01"/>') : null,
    $telegram ? array('url' => $telegram, 'ext' => true, 'title' => 'تلگرام', 'desc' => 'سریع‌ترین راه برای هماهنگی', 'value' => 'Telegram', 'color' => 'sky', 'cta' => 'پیام در تلگرام',
        'icon' => '<path d="m20.665 3.717-17.73 6.837c-1.21.486-1.203 1.161-.222 1.462l4.552 1.42 10.532-6.645c.498-.303.953-.14.579.192l-8.533 7.701h-.002l.002.001-.314 4.692c.46 0 .663-.211.921-.46l2.211-2.15 4.599 3.397c.848.467 1.457.227 1.668-.785l3.019-14.228c.309-1.239-.473-1.8-1.282-1.434"/>') : null,
    $phone ? array('url' => 'tel:' . preg_replace('/[^\d+]/', '', $phone), 'ext' => false, 'title' => 'تماس تلفنی', 'desc' => kmpr_phone_display($phone), 'value' => 'Call', 'color' => 'amber', 'cta' => 'برقراری تماس', 'ltr' => true,
        'icon' => '<path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.79 19.79 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/>') : null,
    array('url' => 'mailto:' . antispambot($email), 'ext' => false, 'title' => 'ایمیل', 'desc' => antispambot($email), 'value' => 'Email', 'color' => 'violet', 'cta' => 'ارسال ایمیل', 'mono' => true,
        'icon' => '<g fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></g>'),
));
$tones = array(
    'emerald' => array('hover:border-emerald-400/50', 'bg-emerald-500/15 text-emerald-400', 'text-emerald-400', 'group-hover:text-emerald-400'),
    'sky'     => array('hover:border-sky-400/50', 'bg-sky-500/15 text-sky-400', 'text-sky-400', 'group-hover:text-sky-400'),
    'amber'   => array('hover:border-secondary/50', 'bg-secondary/15 text-secondary', 'text-secondary', 'group-hover:text-secondary'),
    'violet'  => array('hover:border-violet-400/50', 'bg-violet-500/15 text-violet-400', 'text-violet-400', 'group-hover:text-violet-400'),
);

/* First 3 questions from the FAQ page */
$faq_page = get_page_by_path('faq');
$faq_rows = ($faq_page && function_exists('get_field')) ? array_slice((array) get_field('faq', $faq_page->ID), 0, 3) : array();
?>

<div class="relative isolate overflow-x-clip bg-slate-950">

    <!-- Hero -->
    <section class="relative isolate pt-16 pb-12 lg:pt-24 lg:pb-16 text-center" aria-labelledby="contact-title">
        <div class="absolute inset-0 hero-grid -z-10" aria-hidden="true"></div>
        <div class="aurora w-[32rem] h-[32rem] -top-48 start-1/4 bg-secondary/20 -z-10" aria-hidden="true"></div>
        <div class="aurora w-[26rem] h-[26rem] top-0 -end-32 bg-indigo-600/20 -z-10 [animation-delay:-6s]" aria-hidden="true"></div>
        <div class="container mx-auto px-4 sm:px-6 max-w-4xl">
            <span class="inline-flex items-center gap-2.5 px-4 py-2 rounded-full bg-white/[0.04] border border-white/10 backdrop-blur-xl text-xs font-bold text-slate-200" data-aos="fade-down">
                <span class="relative flex size-2.5 shrink-0">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full size-2.5 bg-emerald-500"></span>
                </span>
                <?= esc_html(kmpr_field('contact_badge')); ?>
            </span>
            <h1 id="contact-title" class="mt-6 text-4xl sm:text-5xl lg:text-6xl font-black text-white leading-tight tracking-tight" data-split><?= esc_html(kmpr_field('contact_hero_title')); ?></h1>
            <p class="mt-5 text-base sm:text-lg text-slate-300 max-w-2xl mx-auto leading-loose" data-aos="fade-up" data-aos-delay="250"><?= esc_html(kmpr_field('contact_hero_text')); ?></p>
            <ul class="mt-7 flex flex-wrap items-center justify-center gap-2 text-xs sm:text-sm text-slate-300" data-aos="fade-up" data-aos-delay="350">
                <li class="inline-flex items-center gap-2 px-3 py-2 rounded-full border border-white/10 bg-white/[0.03]">
                    <svg width="14" height="14" class="shrink-0 text-secondary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                    <?= esc_html(kmpr_field('contact_response_note')); ?>
                </li>
                <li class="inline-flex items-center gap-2 px-3 py-2 rounded-full border border-white/10 bg-white/[0.03]"><?= esc_html(kmpr_field('contact_hours')); ?></li>
                <?php if ($address) : ?><li class="inline-flex items-center gap-2 px-3 py-2 rounded-full border border-white/10 bg-white/[0.03]"><?= esc_html(wp_strip_all_tags($address)); ?></li><?php endif; ?>
            </ul>
        </div>
    </section>

    <!-- Channels -->
    <section class="pb-16 lg:pb-20" aria-labelledby="contact-channels">
        <div class="container mx-auto px-4 sm:px-6 max-w-6xl">
            <h2 id="contact-channels" class="sr-only"><?= esc_html(kmpr_field('contact_channels_title')); ?></h2>
            <ul class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5" data-stagger="90">
                <?php foreach ($channels as $c) : $t = $tones[$c['color']]; ?>
                    <li data-aos="fade-up">
                        <a href="<?= esc_url($c['url'], array('http', 'https', 'tel', 'mailto')); ?>"<?= $c['ext'] ? ' target="_blank" rel="noopener"' : ''; ?> data-tilt="4"
                           class="spotlight group flex flex-col gap-5 h-full p-6 rounded-3xl bg-white/[0.025] border border-white/10 <?= esc_attr($t[0]); ?> transition-colors">
                            <div class="flex items-center justify-between">
                                <span class="flex items-center justify-center size-12 rounded-2xl <?= esc_attr($t[1]); ?> group-hover:scale-110 transition-transform">
                                    <svg width="24" height="24" class="shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><?= $c['icon']; ?></svg>
                                </span>
                                <span class="text-xs font-mono text-slate-400" dir="ltr"><?= esc_html($c['value']); ?></span>
                            </div>
                            <div class="min-w-0">
                                <h3 class="text-lg font-black text-white <?= esc_attr($t[3]); ?> transition-colors"><?= esc_html($c['title']); ?></h3>
                                <p class="mt-1 text-sm text-slate-300 truncate<?= !empty($c['mono']) ? ' font-mono' : ''; ?>"<?= (!empty($c['mono']) || !empty($c['ltr'])) ? ' dir="ltr" style="text-align:right"' : ''; ?>><?= esc_html($c['desc']); ?></p>
                            </div>
                            <span class="mt-auto flex items-center justify-between border-t border-white/10 pt-4 text-sm font-bold <?= esc_attr($t[2]); ?>">
                                <?= esc_html($c['cta']); ?>
                                <svg width="16" height="16" class="shrink-0 group-hover:-translate-x-1 transition-transform" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.612l4.158 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.612 9.25H16.25A.75.75 0 0 1 17 10Z" clip-rule="evenodd"/></svg>
                            </span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <div class="mt-6 grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">
                <?php if ($faq_rows) : ?>
                    <div class="lg:col-span-7 rounded-3xl bg-white/[0.025] border border-white/10 p-6 sm:p-8" data-aos="fade-up" x-data="{ open: 0 }">
                        <div class="flex items-center justify-between gap-3 mb-5">
                            <h2 class="text-xl font-black text-white"><?= esc_html(kmpr_field('contact_faq_title')); ?></h2>
                            <a href="<?= esc_url(get_permalink($faq_page)); ?>" class="inline-flex items-center min-h-11 whitespace-nowrap text-sm font-bold text-secondary hover:underline">همه سؤالات ←</a>
                        </div>
                        <ul class="flex flex-col gap-3">
                            <?php foreach ($faq_rows as $i => $row) : if (empty($row['question'])) continue; ?>
                                <li class="rounded-2xl border border-white/10 bg-white/[0.02]" :class="open === <?= (int) $i; ?> && 'border-secondary/30'">
                                    <button type="button" @click="open = open === <?= (int) $i; ?> ? null : <?= (int) $i; ?>" :aria-expanded="(open === <?= (int) $i; ?>).toString()"
                                            class="flex items-center justify-between gap-3 w-full min-h-11 px-4 py-3.5 text-start cursor-pointer">
                                        <span class="text-sm sm:text-base font-bold text-white leading-relaxed"><?= esc_html($row['question']); ?></span>
                                        <svg width="16" height="16" class="shrink-0 text-secondary transition-transform duration-300" :class="open === <?= (int) $i; ?> && 'rotate-45'" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M8 3a.75.75 0 0 1 .75.75v3.5h3.5a.75.75 0 0 1 0 1.5h-3.5v3.5a.75.75 0 0 1-1.5 0v-3.5h-3.5a.75.75 0 0 1 0-1.5h3.5v-3.5A.75.75 0 0 1 8 3"/></svg>
                                    </button>
                                    <div class="grid transition-[grid-template-rows] duration-500" :class="open === <?= (int) $i; ?> ? 'grid-rows-[1fr]' : 'grid-rows-[0fr]'">
                                        <div class="overflow-hidden"><div class="px-4 pb-4 text-sm text-slate-300 leading-loose"><?= wp_kses_post($row['answer'] ?? ''); ?></div></div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="<?= $faq_rows ? 'lg:col-span-5' : 'lg:col-span-12'; ?> flex flex-col gap-5">
                    <div class="rounded-3xl bg-white/[0.025] border border-white/10 p-6" data-aos="fade-up" data-aos-delay="100">
                        <h2 class="text-sm font-bold text-slate-300 mb-4">شبکه‌ها و پروفایل‌های من</h2>
                        <?php get_template_part('template-parts/social-media', null, array('class' => 'flex-wrap gap-3 justify-start', 'sizeSvgX' => '20', 'sizeSvgY' => '20')); ?>
                    </div>
                    <div class="relative overflow-hidden rounded-3xl border border-secondary/30 bg-gradient-to-br from-secondary/15 via-white/[0.02] to-transparent p-6" data-aos="fade-up" data-aos-delay="200">
                        <div class="flex items-center gap-3 mb-2">
                            <span class="flex items-center justify-center size-10 rounded-xl bg-secondary/15 text-secondary shrink-0">
                                <svg width="18" height="18" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>
                            </span>
                            <h2 class="text-base font-black text-white"><?= esc_html(kmpr_field('contact_promise_title')); ?></h2>
                        </div>
                        <p class="text-sm text-slate-300 leading-loose"><?= esc_html(kmpr_field('contact_promise_text')); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Smart Project Configurator -->
    <?php get_template_part('template-parts/services/smart-configurator'); ?>
</div>

<?php get_footer(); ?>
