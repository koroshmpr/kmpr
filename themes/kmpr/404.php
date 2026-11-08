<?php
/**
 * 404 — playful terminal error with search and quick links.
 *
 * @package KMPR
 */
get_header();

$path  = isset($_SERVER['REQUEST_URI']) ? rawurldecode(wp_parse_url(wp_unslash($_SERVER['REQUEST_URI']), PHP_URL_PATH) ?: '/') : '/';
$links = array(
    array(home_url('/'), 'صفحه اصلی', 'home'),
    array(get_post_type_archive_link('services') ?: home_url('/services/'), 'خدمات', 'services'),
    array(get_post_type_archive_link('portfolio') ?: home_url('/portfolios/'), 'نمونه‌کارها', 'portfolio'),
    array(get_permalink(get_option('page_for_posts')) ?: home_url('/blog/'), 'مقالات', 'blog'),
    array(kmpr_request_url(), 'شروع پروژه', 'contact'),
);
?>
<section class="relative isolate overflow-x-clip bg-slate-950 py-16 lg:py-24 min-h-[75vh] flex items-center" aria-labelledby="nf-title">
    <div class="absolute inset-0 hero-grid -z-10" aria-hidden="true"></div>
    <div class="aurora w-[30rem] h-[30rem] -top-40 start-1/4 bg-secondary/20 -z-10" aria-hidden="true"></div>
    <div class="aurora w-[24rem] h-[24rem] bottom-0 -end-24 bg-indigo-600/20 -z-10 [animation-delay:-6s]" aria-hidden="true"></div>

    <div class="container mx-auto px-4 sm:px-6 max-w-3xl text-center">
        <p class="nf-digits select-none font-black text-[7rem] leading-none sm:text-[11rem] text-white" dir="ltr" aria-hidden="true" data-aos="zoom-in">
            <span class="nf-digit inline-block">4</span><span class="nf-digit nf-zero inline-block text-secondary">0</span><span class="nf-digit inline-block">4</span>
        </p>

        <div class="mt-6 mx-auto max-w-xl rounded-2xl border border-white/10 bg-slate-900/80 text-start overflow-hidden" data-aos="fade-up" data-aos-delay="150">
            <div class="flex items-center gap-2 px-4 py-2.5 border-b border-white/10" dir="ltr" aria-hidden="true">
                <span class="size-2.5 rounded-full bg-rose-500/80"></span><span class="size-2.5 rounded-full bg-amber-400/80"></span><span class="size-2.5 rounded-full bg-emerald-400/80"></span>
            </div>
            <div class="p-4 font-mono text-xs sm:text-sm leading-7 break-all" dir="ltr">
                <p class="text-slate-300"><span class="text-emerald-400">$</span> cd <?= esc_html($path); ?></p>
                <p class="text-rose-300">bash: cd: no such file or directory</p>
                <p class="text-slate-400"><span class="text-emerald-400">$</span> <span class="rz-caret" aria-hidden="true"></span></p>
            </div>
        </div>

        <h1 id="nf-title" class="mt-8 text-2xl sm:text-4xl font-black text-white leading-tight">این صفحه پیدا نشد!</h1>
        <p class="mt-3 text-slate-300 leading-loose">شاید آدرس جابه‌جا شده یا حذف شده باشد. جست‌وجو کنید یا از یکی از مسیرهای زیر ادامه بدهید.</p>

        <form role="search" method="get" action="<?= esc_url(home_url('/')); ?>" class="relative mt-7 max-w-xl mx-auto" data-aos="fade-up" data-aos-delay="250">
            <label for="nf-search" class="sr-only">جست‌وجو در سایت</label>
            <input id="nf-search" type="search" name="s" placeholder="دنبال چه می‌گشتید؟" class="w-full min-h-14 ps-5 pe-32 rounded-full bg-slate-900/80 border border-white/10 focus:border-secondary/60 focus:ring-4 focus:ring-secondary/15 outline-none text-white placeholder:text-slate-400">
            <button type="submit" class="absolute top-1.5 bottom-1.5 end-1.5 inline-flex items-center gap-2 whitespace-nowrap px-6 rounded-full bg-secondary hover:bg-amber-500 text-slate-950 font-black text-sm cursor-pointer transition-colors">
                <svg width="16" height="16" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                جست‌وجو
            </button>
        </form>

        <nav class="mt-8" aria-label="لینک‌های پیشنهادی">
            <ul class="flex flex-wrap items-center justify-center gap-2.5" data-stagger="70">
                <?php foreach ($links as $l) : ?>
                    <li data-aos="fade-up">
                        <a href="<?= esc_url($l[0]); ?>" class="inline-flex items-center gap-2 whitespace-nowrap min-h-11 px-5 py-2.5 rounded-full border border-white/10 bg-white/[0.03] hover:border-secondary/40 hover:text-white text-sm font-bold text-slate-200 transition-colors">
                            <span class="font-mono text-xs text-secondary" dir="ltr">/<?= esc_html($l[2]); ?></span><?= esc_html($l[1]); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>
    </div>
</section>
<?php
get_footer();
