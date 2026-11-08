<?php
/* Template Name: faq */

get_header();

$faq_items = array();
if (function_exists('have_rows') && have_rows('faq')) {
    while (have_rows('faq')) {
        the_row();
        $question = trim((string) get_sub_field('question'));
        $answer   = (string) get_sub_field('answer');
        if ($question && $answer) {
            $faq_items[] = array('question' => $question, 'answer' => $answer);
        }
    }
}

$intro = trim(wp_strip_all_tags((string) get_post_field('post_content', get_queried_object_id())));
$intro = $intro ?: 'جواب سؤال‌هایی که معمولاً قبل از شروع همکاری از من می‌پرسند؛ از هزینه و زمان تا پشتیبانی و مالکیت کد.';
$telegram_url = kmpr_contact_url('telegram');
$count = count($faq_items);
?>

<div class="relative isolate overflow-x-clip bg-slate-950"
     x-data="{
        q: '',
        open: null,
        shown: <?= (int) $count; ?>,
        init() {
            const m = location.hash.match(/^#q-(\d+)$/);
            if (m) { this.open = +m[1]; this.$nextTick(() => document.getElementById('q-' + m[1])?.scrollIntoView({ block: 'center' })); }
        },
        match(el) {
            const t = this.q.trim().toLowerCase();
            return !t || el.dataset.text.includes(t);
        },
        toggle(i) {
            this.open = this.open === i ? null : i;
            history.replaceState(null, '', this.open === null ? location.pathname : '#q-' + i);
        },
        recount() { this.$nextTick(() => this.shown = this.$root.querySelectorAll('[data-faq]:not([hidden])').length); }
     }">

    <!-- Hero + search -->
    <section class="relative isolate pt-14 pb-10 lg:pt-20 lg:pb-14 text-center" aria-labelledby="faq-title">
        <div class="absolute inset-0 hero-grid -z-10" aria-hidden="true"></div>
        <div class="aurora w-[30rem] h-[30rem] -top-48 start-1/4 bg-secondary/20 -z-10" aria-hidden="true"></div>
        <div class="aurora w-[24rem] h-[24rem] top-10 -end-32 bg-indigo-600/20 -z-10 [animation-delay:-6s]" aria-hidden="true"></div>

        <div class="container mx-auto px-4 sm:px-6 max-w-3xl">
            <span class="inline-flex items-center gap-2 text-xs font-black text-secondary" data-aos="fade-down"><span class="w-6 h-px bg-secondary"></span>FAQ · <?= esc_html(kmpr_fa_num($count)); ?> سؤال<span class="w-6 h-px bg-secondary"></span></span>
            <h1 id="faq-title" class="mt-4 text-4xl sm:text-5xl lg:text-6xl font-black text-white leading-tight" data-split><?= esc_html(get_the_title()); ?></h1>
            <p class="mt-5 text-base sm:text-lg text-slate-300 leading-loose" data-aos="fade-up" data-aos-delay="200"><?= esc_html($intro); ?></p>

            <?php if ($count > 3) : ?>
                <div class="relative mt-8 max-w-xl mx-auto" data-aos="fade-up" data-aos-delay="300">
                    <label for="faq-search" class="sr-only">جست‌وجو در سؤالات</label>
                    <svg width="20" height="20" class="shrink-0 absolute top-1/2 -translate-y-1/2 start-5 text-slate-400 pointer-events-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                    <input id="faq-search" type="search" x-model="q" @input="recount()" autocomplete="off"
                           placeholder="مثلاً: هزینه، پشتیبانی، سئو…"
                           class="w-full min-h-14 ps-14 pe-5 rounded-full bg-slate-900/80 border border-white/10 focus:border-secondary/60 focus:ring-4 focus:ring-secondary/15 outline-none text-white placeholder:text-slate-400 text-base transition">
                    <p class="mt-3 text-sm text-slate-400" x-show="q" x-cloak aria-live="polite">
                        <span x-text="shown.toLocaleString('fa-IR')"></span> نتیجه
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Accordion -->
    <section class="pb-14 lg:pb-20" aria-label="فهرست سؤالات">
        <div class="container mx-auto px-4 sm:px-6 max-w-3xl">
            <ol class="flex flex-col gap-3 sm:gap-4" data-stagger="60">
                <?php foreach ($faq_items as $i => $item) :
                    $n    = $i + 1;
                    $text = function_exists('mb_strtolower') ? mb_strtolower(wp_strip_all_tags($item['question'] . ' ' . $item['answer'])) : strtolower(wp_strip_all_tags($item['question'] . ' ' . $item['answer']));
                    ?>
                    <li id="q-<?= (int) $n; ?>" data-faq data-text="<?= esc_attr($text); ?>" data-aos="fade-up"
                        :hidden="!match($el)"
                        :class="open === <?= (int) $n; ?> ? 'border-secondary/40 bg-white/[0.045]' : 'border-white/10 bg-white/[0.025] hover:border-white/20'"
                        class="spotlight scroll-mt-28 rounded-3xl border border-white/10 bg-white/[0.025] transition-colors duration-300">
                        <h2 class="m-0">
                            <button type="button" @click="toggle(<?= (int) $n; ?>)"
                                    :aria-expanded="(open === <?= (int) $n; ?>).toString()" aria-expanded="false" aria-controls="a-<?= (int) $n; ?>"
                                    class="group flex items-center gap-4 w-full min-h-11 p-5 sm:p-6 text-start cursor-pointer rounded-3xl outline-none focus-visible:ring-2 focus-visible:ring-secondary/60">
                                <span class="shrink-0 text-sm font-black text-slate-400" :class="open === <?= (int) $n; ?> && 'text-secondary'"><?= esc_html(kmpr_fa_num(str_pad((string) $n, 2, '0', STR_PAD_LEFT))); ?></span>
                                <span class="flex-1 text-base sm:text-lg font-bold text-white leading-relaxed"><?= esc_html($item['question']); ?></span>
                                <span class="shrink-0 flex items-center justify-center size-9 rounded-full border border-secondary/30 bg-secondary/10 text-secondary transition-transform duration-500"
                                      :class="open === <?= (int) $n; ?> && 'rotate-45 bg-secondary text-slate-950'">
                                    <svg width="16" height="16" class="shrink-0" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M8 3a.75.75 0 0 1 .75.75v3.5h3.5a.75.75 0 0 1 0 1.5h-3.5v3.5a.75.75 0 0 1-1.5 0v-3.5h-3.5a.75.75 0 0 1 0-1.5h3.5v-3.5A.75.75 0 0 1 8 3"/></svg>
                                </span>
                            </button>
                        </h2>
                        <div id="a-<?= (int) $n; ?>" role="region" class="grid grid-rows-[0fr] transition-[grid-template-rows] duration-500 ease-[cubic-bezier(.16,1,.3,1)]"
                             :class="open === <?= (int) $n; ?> && 'grid-rows-[1fr]'">
                            <div class="overflow-hidden">
                                <div class="mx-5 sm:mx-6 mb-6 pt-5 border-t border-dashed border-white/10 text-slate-300 leading-loose text-sm sm:text-base [&_p+p]:mt-3 [&_a]:text-secondary [&_a:hover]:underline [&_strong]:text-white">
                                    <?= wp_kses_post($item['answer']); ?>
                                    <a href="#q-<?= (int) $n; ?>" class="mt-3 inline-flex items-center gap-1 min-h-11 text-sm text-slate-400 hover:text-secondary" @click.prevent="navigator.clipboard?.writeText(location.origin + location.pathname + '#q-<?= (int) $n; ?>'); $el.querySelector('span').textContent = 'کپی شد ✓'">
                                        # <span>لینک این سؤال</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ol>

            <p class="mt-8 text-center text-slate-300" x-show="q && shown === 0" x-cloak>
                چیزی پیدا نشد؛ سؤالتان را مستقیم از خودم بپرسید.
            </p>
        </div>
    </section>

    <!-- CTA -->
    <section class="pb-20 lg:pb-28" aria-labelledby="faq-cta-title">
        <div class="container mx-auto px-4 sm:px-6 max-w-3xl">
            <div class="relative isolate overflow-hidden rounded-3xl border border-white/10 bg-slate-900/80 p-7 sm:p-10 text-center border-glow" data-aos="zoom-in">
                <div class="aurora w-72 h-72 -top-24 -end-16 bg-secondary/25 -z-10" aria-hidden="true"></div>
                <span class="inline-flex items-center px-3 py-1 rounded-full bg-secondary/15 text-secondary border border-secondary/30 text-xs font-black mb-4">جوابتان را پیدا نکردید؟</span>
                <h2 id="faq-cta-title" class="text-2xl sm:text-3xl font-black text-white leading-tight mb-3">مستقیم از خودم بپرسید</h2>
                <p class="text-slate-300 leading-loose mb-8 max-w-xl mx-auto">چند سؤال کوتاه درباره پروژه‌تان جواب بدهید تا خودم بررسی کنم و برآورد زمان و هزینه را برایتان بفرستم؛ یا همین حالا در تلگرام پیام بدهید.</p>
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-center gap-3">
                    <a href="<?= esc_url(kmpr_request_url()); ?>" data-magnetic class="inline-flex items-center justify-center gap-2 whitespace-nowrap px-8 py-3.5 min-h-11 rounded-full bg-secondary hover:bg-amber-500 text-slate-950 font-black text-sm transition-colors">ثبت درخواست پروژه</a>
                    <?php if ($telegram_url) : ?>
                        <a href="<?= esc_url($telegram_url); ?>" target="_blank" rel="noopener" class="inline-flex items-center justify-center gap-2 whitespace-nowrap px-8 py-3.5 min-h-11 rounded-full bg-white/[0.04] hover:bg-white/[0.08] border border-white/15 text-slate-100 font-bold text-sm transition-colors">
                            <svg width="16" height="16" class="shrink-0 text-sky-400" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M8.287 5.906q-1.168.486-4.666 2.01-.567.225-.595.442c-.03.243.275.339.69.47l.175.055c.408.133.958.288 1.243.294q.39.01.868-.32 3.269-2.206 3.374-2.23c.05-.012.12-.026.166.016s.042.12.037.141c-.03.129-1.227 1.241-1.846 1.817-.193.18-.33.307-.358.336a8 8 0 0 1-.188.186c-.38.366-.664.64.015 1.088.327.216.589.393.85.571.284.194.568.387.936.629q.14.092.27.187c.331.236.63.448.997.414.214-.02.435-.22.547-.82.265-1.417.786-4.486.906-5.751a1.4 1.4 0 0 0-.013-.315.34.34 0 0 0-.114-.217.53.53 0 0 0-.31-.093c-.3.005-.763.166-2.984 1.09"/></svg>
                            پیام در تلگرام
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</div>

<?php
if ($faq_items) {
    $faq_schema = array(
        '@context'   => 'https://schema.org',
        '@type'      => 'FAQPage',
        'url'        => get_permalink(),
        'mainEntity' => array_map(function ($item) {
            return array(
                '@type'          => 'Question',
                'name'           => wp_strip_all_tags($item['question']),
                'acceptedAnswer' => array('@type' => 'Answer', 'text' => wp_strip_all_tags($item['answer'])),
            );
        }, $faq_items),
    );
    echo '<script type="application/ld+json">' . wp_json_encode($faq_schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
}

get_footer();
