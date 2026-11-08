<?php
/**
 * Services — FAQ accordion (native <details>) + optional FAQPage JSON-LD.
 * $args: 'items' (array of [question, answer]), 'kicker', 'title', 'id', 'schema' (bool)
 */
$items = array_values(array_filter((array) ($args['items'] ?? array()), function ($r) {
    return is_array($r) && !empty($r['question']) && !empty($r['answer']);
}));
if (!$items) {
    return;
}
$sid = $args['id'] ?? 'srv-faq-title';
?>
<section class="relative py-20 lg:py-28 border-t border-white/10" aria-labelledby="<?= esc_attr($sid); ?>">
    <div class="container mx-auto px-4 sm:px-6">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-16">
            <div class="lg:col-span-4">
                <?php kmpr_srv_head($args['kicker'] ?? '', $args['title'] ?? '', '', $sid); ?>
                <p class="text-sm text-slate-300 leading-loose -mt-4 mb-6" data-aos="fade-up">
                    جواب سوالتان اینجا نیست؟ مستقیم بپرسید؛ معمولاً همان روز پاسخ می‌دهم.
                </p>
                <?php if ($tg = kmpr_contact_url('telegram')) : ?>
                    <a href="<?= esc_url($tg); ?>" target="_blank" rel="noopener" data-aos="fade-up"
                       class="inline-flex items-center gap-2 min-h-11 px-6 py-3 rounded-full bg-white/[0.04] hover:bg-white/[0.08] border border-white/15 text-slate-100 text-sm font-bold whitespace-nowrap transition-colors">
                        <svg width="18" height="18" class="shrink-0 text-sky-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg>
                        پرسیدن در تلگرام
                    </a>
                <?php endif; ?>
            </div>
            <div class="lg:col-span-8 space-y-3" data-stagger="70">
                <?php foreach ($items as $i => $item) : ?>
                    <details class="srv-faq group rounded-2xl bg-white/[0.025] border border-white/10 open:border-secondary/40 open:bg-white/[0.04] transition-colors" data-aos="fade-up"<?= $i === 0 ? ' open' : ''; ?>>
                        <summary class="flex items-center justify-between gap-4 cursor-pointer list-none min-h-11 px-5 sm:px-6 py-4 sm:py-5 text-start">
                            <h3 class="text-sm sm:text-base font-black text-white leading-relaxed"><?= esc_html($item['question']); ?></h3>
                            <span class="size-8 shrink-0 rounded-full bg-white/[0.05] border border-white/10 flex items-center justify-center text-secondary transition-transform duration-300 group-open:rotate-45">
                                <svg width="14" height="14" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                            </span>
                        </summary>
                        <div class="px-5 sm:px-6 pb-5 sm:pb-6 -mt-1 text-sm sm:text-base text-slate-300 leading-loose">
                            <?= wp_kses_post(wpautop($item['answer'])); ?>
                        </div>
                    </details>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php if (!empty($args['schema'])) :
        $faq_schema = array(
            '@context'   => 'https://schema.org',
            '@type'      => 'FAQPage',
            'mainEntity' => array_map(function ($item) {
                return array(
                    '@type'          => 'Question',
                    'name'           => wp_strip_all_tags($item['question']),
                    'acceptedAnswer' => array('@type' => 'Answer', 'text' => wp_strip_all_tags($item['answer'])),
                );
            }, $items),
        ); ?>
        <script type="application/ld+json"><?= wp_json_encode($faq_schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG); ?></script>
    <?php endif; ?>
</section>
