<?php
/**
 * Live search — command-palette on desktop, full-screen sheet on mobile.
 * Data: GET /wp-json/search/v1/search (inc/search-route.php). Component: window.themeSearch (javascript/app.js).
 * Empty state (before typing): recent searches (per browser), suggested topics and quick links — all from live content.
 */
$kmpr_types = function_exists('kmpr_search_types') ? kmpr_search_types() : array('post' => 'مقاله', 'services' => 'خدمت', 'portfolio' => 'نمونه‌کار');
$kmpr_tab_labels = array('post' => 'مقالات', 'services' => 'خدمات', 'portfolio' => 'نمونه‌کارها', 'game' => 'بازی‌ها', 'page' => 'صفحات');
$kmpr_type_icons = array(
    'post'      => '<path d="M4 19.5V5a2 2 0 0 1 2-2h12v15H6.5A2.5 2.5 0 0 0 4 20.5 2.5 2.5 0 0 0 6.5 23H18"/><path d="M8 7h6M8 11h4"/>',
    'services'  => '<path d="M12 2 2 7l10 5 10-5-10-5Z"/><path d="m2 17 10 5 10-5"/><path d="m2 12 10 5 10-5"/>',
    'portfolio' => '<rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/>',
    'game'      => '<rect x="2" y="6" width="20" height="12" rx="6"/><path d="M6 12h4M8 10v4"/><circle cx="15.5" cy="11" r=".9" fill="currentColor"/><circle cx="17.5" cy="13" r=".9" fill="currentColor"/>',
    'page'      => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/>',
);

// Suggested topics: most-used tags, then categories, then sensible defaults.
$kmpr_suggest = wp_list_pluck(get_tags(array('orderby' => 'count', 'order' => 'DESC', 'number' => 6, 'hide_empty' => true)) ?: array(), 'name');
if (count($kmpr_suggest) < 4) {
    $kmpr_suggest = array_merge($kmpr_suggest, array('سئو', 'وردپرس', 'Next.js', 'Core Web Vitals', 'فروشگاه اینترنتی', 'سرعت سایت'));
}
$kmpr_suggest = array_slice(array_values(array_unique($kmpr_suggest)), 0, 6);

// Quick links: latest lessons + key sections.
$kmpr_latest = get_posts(array('post_type' => 'post', 'posts_per_page' => 3, 'no_found_rows' => true));
$kmpr_links  = array_filter(array(
    array('خدمات', get_post_type_archive_link('services'), 'services'),
    array('نمونه‌کارها', get_post_type_archive_link('portfolio'), 'portfolio'),
    post_type_exists('game') ? array('بازی‌ها', get_post_type_archive_link('game'), 'game') : null,
    array('رزومه', home_url('/resume/'), 'page'),
));
$kmpr_svg = function ($type, $size = 18) use ($kmpr_type_icons) {
    return '<svg width="' . (int) $size . '" height="' . (int) $size . '" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($kmpr_type_icons[$type] ?? $kmpr_type_icons['page']) . '</svg>';
};
?>
<div x-show="searchOpen" x-cloak class="fixed inset-0 z-[100]" role="dialog" aria-modal="true" aria-label="جستجو در سایت"
     @keydown.window.escape="searchOpen = false">
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-slate-950/80 backdrop-blur-sm" @click="searchOpen = false"
         x-show="searchOpen" x-transition:enter="transition duration-300" x-transition:enter-start="opacity-0" x-transition:leave="transition duration-200" x-transition:leave-end="opacity-0"></div>

    <!-- Panel -->
    <div x-data='themeSearch(<?= wp_json_encode($kmpr_types, JSON_HEX_APOS | JSON_UNESCAPED_UNICODE); ?>)'
         x-show="searchOpen"
         x-transition:enter="transition duration-[420ms] ease-[cubic-bezier(0.16,1,0.3,1)]"
         x-transition:enter-start="opacity-0 translate-y-6 sm:translate-y-0 sm:-translate-y-3 sm:scale-[0.97]"
         x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
         x-transition:leave="transition duration-200 ease-in"
         x-transition:leave-start="opacity-100 sm:scale-100"
         x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-[0.98]"
         class="absolute inset-0 sm:inset-auto sm:top-[8vh] sm:inset-x-4 sm:mx-auto sm:max-w-2xl flex flex-col sm:max-h-[min(44rem,84vh)] bg-slate-900 sm:rounded-3xl sm:border sm:border-white/10 shadow-[0_40px_120px_-20px_rgb(0_0_0/0.9)] overflow-hidden text-start pt-[env(safe-area-inset-top)] sm:pt-0">

        <!-- Input -->
        <div class="shrink-0 flex items-center gap-2 sm:gap-3 px-3 sm:px-5 h-16 sm:h-[4.5rem] border-b border-white/10">
            <span class="size-10 shrink-0 flex items-center justify-center text-secondary" aria-hidden="true">
                <svg x-show="!isLoading" width="22" height="22" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                <svg x-show="isLoading" x-cloak width="22" height="22" class="shrink-0 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity=".25" stroke-width="3"/><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
            </span>
            <input type="search" x-model="query" x-ref="searchInput" enterkeyhint="search" autocomplete="off"
                   @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)" @keydown.enter.prevent="open()"
                   aria-label="عبارت جستجو"
                   class="flex-1 min-w-0 h-12 bg-transparent outline-none text-base sm:text-lg font-bold text-white placeholder:text-slate-500 placeholder:font-normal [&::-webkit-search-cancel-button]:hidden"
                   placeholder="دنبال چه می‌گردید؟">
            <button type="button" x-show="query.length" x-cloak @click="clearQuery()" class="size-9 shrink-0 rounded-full flex items-center justify-center text-slate-400 hover:text-white hover:bg-white/10 transition-colors cursor-pointer" aria-label="پاک کردن">
                <svg width="16" height="16" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
            <kbd class="hidden sm:inline-flex shrink-0 h-7 items-center px-2 rounded-lg bg-white/[0.06] border border-white/10 text-[11px] font-bold text-slate-400 font-sans">Esc</kbd>
            <button type="button" @click="searchOpen = false" class="sm:hidden shrink-0 h-10 px-3 rounded-full text-sm font-bold text-slate-300 active:bg-white/10 cursor-pointer whitespace-nowrap">انصراف</button>
        </div>

        <!-- Tabs (only once there are results) -->
        <div x-show="hasSearched && counts.all" x-cloak class="shrink-0 border-b border-white/10">
            <div class="flex gap-1.5 px-3 sm:px-5 py-2.5 overflow-x-auto [scrollbar-width:none] [&::-webkit-scrollbar]:hidden" role="tablist">
                <?php foreach (array_merge(array('all' => 'همه'), array_intersect_key($kmpr_tab_labels, $kmpr_types)) as $key => $label) : ?>
                    <button type="button" role="tab" @click="setTab('<?= esc_js($key); ?>')" :aria-selected="activeTab === '<?= esc_js($key); ?>'"
                            x-show="'<?= esc_js($key); ?>' === 'all' || counts['<?= esc_js($key); ?>'] > 0"
                            :class="activeTab === '<?= esc_js($key); ?>' ? 'bg-secondary text-slate-950 border-secondary' : 'bg-white/[0.03] text-slate-300 border-white/10 hover:border-white/25'"
                            class="shrink-0 inline-flex items-center gap-1.5 h-9 px-3.5 rounded-full border text-xs font-black whitespace-nowrap transition-colors cursor-pointer">
                        <?= esc_html($label); ?>
                        <span class="tabular-nums opacity-70" x-text="(counts['<?= esc_js($key); ?>'] || 0).toLocaleString('fa-IR')"></span>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Body -->
        <div class="flex-1 min-h-0 overflow-y-auto overscroll-contain">

            <!-- Before typing -->
            <div x-show="query.trim().length < 2" class="p-4 sm:p-5 space-y-6">
                <section x-show="recent.length" x-cloak>
                    <div class="flex items-center justify-between mb-2.5">
                        <h3 class="text-xs font-black text-slate-400">جستجوهای اخیر</h3>
                        <button type="button" @click="forget()" class="text-xs font-bold text-slate-500 hover:text-slate-300 cursor-pointer min-h-8 px-1">پاک کردن</button>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="r in recent" :key="r">
                            <button type="button" @click="use(r)" class="inline-flex items-center gap-1.5 h-9 px-3.5 rounded-full bg-white/[0.04] border border-white/10 text-xs font-bold text-slate-200 hover:border-secondary/40 cursor-pointer">
                                <svg width="13" height="13" class="shrink-0 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                                <span x-text="r"></span>
                            </button>
                        </template>
                    </div>
                </section>

                <section>
                    <h3 class="text-xs font-black text-slate-400 mb-2.5">موضوعات پیشنهادی</h3>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($kmpr_suggest as $kmpr_s) : ?>
                            <button type="button" @click="use(<?= esc_attr(wp_json_encode($kmpr_s, JSON_UNESCAPED_UNICODE)); ?>)"
                                    class="h-9 px-3.5 rounded-full bg-secondary/10 border border-secondary/25 text-xs font-bold text-amber-200 hover:bg-secondary/20 cursor-pointer whitespace-nowrap"><?= esc_html($kmpr_s); ?></button>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section>
                    <h3 class="text-xs font-black text-slate-400 mb-2.5">دسترسی سریع</h3>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                        <?php foreach ($kmpr_links as $kmpr_l) : ?>
                            <a href="<?= esc_url($kmpr_l[1]); ?>" class="flex items-center gap-2.5 h-12 px-3.5 rounded-2xl bg-white/[0.03] border border-white/10 text-sm font-bold text-slate-200 hover:border-secondary/40 hover:text-white transition-colors">
                                <span class="text-secondary"><?= $kmpr_svg($kmpr_l[2]); // static markup ?></span><?= esc_html($kmpr_l[0]); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>

                <?php if ($kmpr_latest) : ?>
                    <section>
                        <h3 class="text-xs font-black text-slate-400 mb-1.5">تازه‌ترین درس‌ها</h3>
                        <ul class="divide-y divide-white/[0.06]">
                            <?php foreach ($kmpr_latest as $kmpr_p) : ?>
                                <li>
                                    <a href="<?= esc_url(get_permalink($kmpr_p)); ?>" class="group flex items-center gap-3 py-2.5 min-h-12">
                                        <?php if (has_post_thumbnail($kmpr_p)) : ?>
                                            <?= get_the_post_thumbnail($kmpr_p, 'thumbnail', array('class' => 'size-11 shrink-0 rounded-xl object-cover bg-slate-800', 'loading' => 'lazy', 'alt' => esc_attr(get_the_title($kmpr_p)))); ?>
                                        <?php endif; ?>
                                        <span class="min-w-0 flex-1 text-sm font-bold text-slate-200 group-hover:text-secondary transition-colors line-clamp-2 leading-6"><?= esc_html(get_the_title($kmpr_p)); ?></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </section>
                <?php endif; ?>
            </div>

            <!-- No results -->
            <div x-show="query.trim().length >= 2 && hasSearched && !isLoading && !list.length" x-cloak class="px-6 py-14 text-center">
                <div class="mx-auto size-14 rounded-2xl bg-white/[0.04] border border-white/10 flex items-center justify-center text-slate-500 mb-4">
                    <svg width="26" height="26" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3M8.5 8.5l5 5M13.5 8.5l-5 5"/></svg>
                </div>
                <p class="text-sm font-black text-white">نتیجه‌ای برای «<span x-text="query"></span>» پیدا نشد</p>
                <p class="text-xs text-slate-400 mt-2 leading-6">کلمه کوتاه‌تر یا مترادف آن را امتحان کنید، یا مستقیم بپرسید:</p>
                <a href="<?= esc_url(function_exists('kmpr_request_url') ? kmpr_request_url() : home_url('/contact-us/')); ?>" class="mt-5 inline-flex items-center justify-center h-11 px-6 rounded-full bg-secondary text-slate-950 text-sm font-black whitespace-nowrap">سوالم را می‌پرسم</a>
            </div>

            <!-- Results -->
            <ul x-show="list.length" x-cloak class="p-2 sm:p-3" role="listbox" :key="activeTab + query">
                <template x-for="(item, i) in list" :key="item.type + '-' + item.id">
                    <li role="option" :aria-selected="cursor === i" :data-sr="i" class="kmpr-sr" :style="`--i:${Math.min(i, 12)}`">
                        <a :href="item.url" @click="remember()" @mouseenter="cursor = i"
                           :class="cursor === i ? 'bg-white/[0.06] border-white/10' : 'border-transparent'"
                           class="group flex items-center gap-3 p-2.5 sm:p-3 rounded-2xl border transition-colors">
                            <span class="relative size-12 sm:size-14 shrink-0 rounded-xl overflow-hidden bg-slate-800 border border-white/10 flex items-center justify-center text-secondary">
                                <template x-if="item.img"><img :src="item.img" alt="" width="56" height="56" loading="lazy" class="size-full object-cover"></template>
                                <?php foreach ($kmpr_type_icons as $kmpr_t => $kmpr_unused) : ?>
                                    <span x-show="!item.img && item.type === '<?= esc_js($kmpr_t); ?>'"><?= $kmpr_svg($kmpr_t, 22); // static markup ?></span>
                                <?php endforeach; ?>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="flex items-center gap-2 mb-1">
                                    <span class="shrink-0 text-[10px] font-black px-2 py-0.5 rounded-md bg-secondary/15 text-secondary border border-secondary/25" x-text="item.typeName"></span>
                                    <span class="text-[11px] text-slate-500 font-bold truncate" x-text="item.meta"></span>
                                </span>
                                <span class="text-sm sm:text-[15px] font-black text-white leading-6 line-clamp-1 group-hover:text-secondary transition-colors" x-html="highlight(item.title)"></span>
                                <span class="text-xs text-slate-400 leading-5 line-clamp-1 mt-0.5" x-show="item.excerpt" x-html="highlight(item.excerpt)"></span>
                            </span>
                            <svg width="16" height="16" class="shrink-0 text-slate-600 group-hover:text-secondary transition-colors" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.61l4.16 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.61 9.25h10.64A.75.75 0 0 1 17 10Z" clip-rule="evenodd"/></svg>
                        </a>
                    </li>
                </template>
            </ul>
        </div>

        <!-- Footer -->
        <div class="shrink-0 flex items-center justify-between gap-3 px-4 sm:px-5 min-h-12 border-t border-white/10 bg-slate-950/40 pb-[env(safe-area-inset-bottom)]">
            <span class="hidden sm:flex items-center gap-3 text-[11px] font-bold text-slate-500">
                <span class="inline-flex items-center gap-1"><kbd class="px-1.5 py-0.5 rounded bg-white/[0.06] border border-white/10 text-slate-400 font-sans">↑</kbd><kbd class="px-1.5 py-0.5 rounded bg-white/[0.06] border border-white/10 text-slate-400 font-sans">↓</kbd> جابه‌جایی</span>
                <span class="inline-flex items-center gap-1"><kbd class="px-1.5 py-0.5 rounded bg-white/[0.06] border border-white/10 text-slate-400 font-sans">Enter</kbd> باز کردن</span>
            </span>
            <a :href="fullUrl()" x-show="query.trim().length >= 2" x-cloak @click="remember()"
               class="ms-auto inline-flex items-center gap-1.5 min-h-11 text-xs font-black text-secondary hover:text-amber-300 whitespace-nowrap">
                همه نتایج در صفحه جستجو
                <svg width="14" height="14" class="shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.61l4.16 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.61 9.25h10.64A.75.75 0 0 1 17 10Z" clip-rule="evenodd"/></svg>
            </a>
        </div>
    </div>
</div>
