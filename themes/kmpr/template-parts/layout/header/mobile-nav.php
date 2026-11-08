<?php
/**
 * App-style mobile navigation (< lg): floating bottom tab bar + bottom-sheet menu.
 * Lives inside the <body> Alpine scope (menuOpen, searchOpen, scrolled).
 *
 * Sheet tiles come from the «منوی گوشی» menu location (editable in Appearance → Menus),
 * falling back to the main menu. Icons are picked from the link URL.
 */

$kmpr_path = '/' . trim((string) wp_parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/') . '/';
$kmpr_path = $kmpr_path === '//' ? '/' : $kmpr_path;
$kmpr_is   = function ($url) use ($kmpr_path) {
    $p = '/' . trim((string) wp_parse_url($url, PHP_URL_PATH), '/') . '/';
    $p = $p === '//' ? '/' : $p;
    return $p === '/' ? $kmpr_path === '/' : strpos($kmpr_path, $p) === 0;
};

$kmpr_locs  = get_nav_menu_locations();
$kmpr_menu  = !empty($kmpr_locs['topHeaderMenuLocation']) ? $kmpr_locs['topHeaderMenuLocation'] : ($kmpr_locs['headerMenuLocation'] ?? 0);
$kmpr_items = $kmpr_menu ? (array) wp_get_nav_menu_items($kmpr_menu) : array();
$kmpr_items = array_filter($kmpr_items, function ($i) {
    return empty($i->menu_item_parent);
});

$kmpr_tabs = array(
    array('label' => 'خانه', 'url' => home_url('/'), 'icon' => 'home'),
    array('label' => 'خدمات', 'url' => get_post_type_archive_link('services') ?: home_url('/services/'), 'icon' => 'services'),
    null, // center action
    array('label' => 'نمونه‌کار', 'url' => get_post_type_archive_link('portfolio') ?: home_url('/portfolios/'), 'icon' => 'portfolio'),
);
$kmpr_quick = array_filter(array(
    'telegram' => array(kmpr_contact('telegram'), 'تلگرام', 'bg-sky-500/15 text-sky-300 border-sky-400/25', '<path d="m21.5 4.5-19 7.3c-1.3.5-1.3 1.2-.2 1.6l4.9 1.5 11.3-7.1c.5-.3 1-.1.6.2l-9.1 8.2-.3 5c.5 0 .7-.2 1-.5l2.4-2.3 4.9 3.6c.9.5 1.6.2 1.8-.8l3.2-15.2c.3-1.3-.5-1.9-1.5-1.5z" fill="currentColor" stroke="none"/>'),
    'whatsapp' => array(kmpr_contact('whatsapp'), 'واتساپ', 'bg-emerald-500/15 text-emerald-300 border-emerald-400/25', '<path d="M3 21l1.7-5A9 9 0 1 1 8 19.3z"/><path d="M9 10c0 3 2 5 5 5l1.2-1.2-2-1-.8.8c-1-.4-2.2-1.6-2.6-2.6l.8-.8-1-2z" fill="currentColor" stroke="none"/>'),
    'phone'    => array('tel:' . preg_replace('/[^\d+]/', '', (string) kmpr_contact('phone')), 'تماس', 'bg-amber-500/15 text-amber-300 border-amber-400/25', '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2z"/>'),
), function ($q) {
    return !empty($q[0]) && $q[0] !== 'tel:';
});
?>

<!-- ===== Bottom tab bar (mobile / tablet) — hidden while playing a game ===== -->
<?php if (!is_singular(array('game', 'post'))) : // articles have their own lesson bar (with a menu button) ?>
<nav class="kmpr-tabbar lg:hidden fixed inset-x-3 z-50 mx-auto max-w-md" aria-label="ناوبری اصلی موبایل"
     :class="menuOpen || searchOpen ? 'translate-y-[140%] opacity-0' : ''">
    <div class="relative grid grid-cols-5 items-end h-16 px-1.5 rounded-[1.75rem] border border-white/10 bg-slate-900/85 backdrop-blur-xl shadow-[0_18px_50px_-12px_rgb(0_0_0/0.85)]">
        <?php foreach ($kmpr_tabs as $kmpr_tab) :
            if ($kmpr_tab === null) : ?>
                <a href="<?= esc_url(kmpr_request_url()); ?>" class="group relative flex flex-col items-center justify-end h-full pb-2 gap-1" aria-label="ثبت درخواست پروژه">
                    <span class="absolute -top-5 size-14 rounded-2xl bg-gradient-to-br from-amber-400 to-secondary text-slate-950 flex items-center justify-center shadow-[0_12px_30px_-6px] shadow-secondary/70 ring-4 ring-slate-950 transition-transform duration-200 group-active:scale-90">
                        <svg width="24" height="24" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                    </span>
                    <span class="text-[11px] font-black text-secondary leading-none">پروژه جدید</span>
                </a>
            <?php else :
                $kmpr_active = $kmpr_is($kmpr_tab['url']); ?>
                <a href="<?= esc_url($kmpr_tab['url']); ?>" <?= $kmpr_active ? 'aria-current="page"' : ''; ?>
                   class="kmpr-tab group relative flex flex-col items-center justify-center gap-1 h-full rounded-2xl transition-colors <?= $kmpr_active ? 'text-secondary' : 'text-slate-400 active:text-slate-100'; ?>">
                    <?php if ($kmpr_active) : ?><span class="absolute top-1.5 h-1 w-5 rounded-full bg-secondary shadow-[0_0_12px] shadow-secondary" aria-hidden="true"></span><?php endif; ?>
                    <span class="transition-transform duration-200 group-active:scale-90"><?= kmpr_nav_svg($kmpr_tab['icon']); // static markup ?></span>
                    <span class="text-[11px] font-bold leading-none"><?= esc_html($kmpr_tab['label']); ?></span>
                </a>
            <?php endif;
        endforeach; ?>
        <button type="button" @click="menuOpen = true" :aria-expanded="menuOpen.toString()" aria-controls="kmpr-sheet"
                class="group flex flex-col items-center justify-center gap-1 h-full rounded-2xl text-slate-400 active:text-slate-100 cursor-pointer">
            <span class="transition-transform duration-200 group-active:scale-90">
                <svg width="22" height="22" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" aria-hidden="true"><circle cx="5" cy="12" r="1.6" fill="currentColor"/><circle cx="12" cy="12" r="1.6" fill="currentColor"/><circle cx="19" cy="12" r="1.6" fill="currentColor"/></svg>
            </span>
            <span class="text-[11px] font-bold leading-none">منو</span>
        </button>
    </div>
</nav>
<?php endif; ?>

<!-- ===== Bottom sheet menu ===== -->
<div x-show="menuOpen" x-cloak class="lg:hidden fixed inset-0 z-[60]" @keydown.escape.window="menuOpen = false">
    <div class="absolute inset-0 bg-slate-950/70 backdrop-blur-[2px]" @click="menuOpen = false"
         x-show="menuOpen" x-transition:enter="transition duration-300" x-transition:enter-start="opacity-0" x-transition:leave="transition duration-200" x-transition:leave-end="opacity-0"></div>

    <div id="kmpr-sheet" role="dialog" aria-modal="true" aria-label="منوی سایت"
         x-show="menuOpen"
         x-transition:enter="kmpr-sheet-in" x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
         x-transition:leave="kmpr-sheet-out" x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full"
         x-data="{ dy: 0, y0: null }"
         @touchstart.passive="y0 = $event.touches[0].clientY; dy = 0"
         @touchmove.passive="if (y0 !== null && $refs.scroller.scrollTop <= 0) { dy = Math.max(0, $event.touches[0].clientY - y0) }"
         @touchend="if (dy > 90) { menuOpen = false } dy = 0; y0 = null"
         :style="dy ? `transform:translateY(${dy}px);transition:none` : ''"
         class="kmpr-sheet absolute inset-x-0 bottom-0 max-h-[88svh] flex flex-col rounded-t-[2rem] border-t border-white/10 bg-slate-900 shadow-[0_-30px_80px_-20px_rgb(0_0_0/0.9)]">
        <div class="shrink-0 pt-3 pb-2 flex flex-col items-center">
            <span class="h-1.5 w-11 rounded-full bg-white/20" aria-hidden="true"></span>
        </div>
        <div x-ref="scroller" class="flex-1 min-h-0 overflow-y-auto overscroll-contain px-5 pb-[calc(1.25rem+env(safe-area-inset-bottom))]">
            <div class="flex items-center justify-between gap-3 mb-4">
                <div class="min-w-0">
                    <p class="text-base font-black text-white"><?= esc_html(get_bloginfo('name')); ?></p>
                    <p class="text-xs text-slate-400 mt-0.5 truncate"><?= esc_html(kmpr_field('job_title', 'طراح و توسعه‌دهنده وب', 'option')); ?></p>
                </div>
                <button type="button" @click="menuOpen = false" class="size-10 shrink-0 rounded-full bg-white/[0.06] border border-white/10 text-slate-300 flex items-center justify-center cursor-pointer" aria-label="بستن منو">
                    <svg width="16" height="16" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </div>

            <button type="button" @click="menuOpen = false; searchOpen = true"
                    class="w-full flex items-center gap-3 h-12 px-4 rounded-2xl bg-white/[0.05] border border-white/10 text-sm text-slate-400 cursor-pointer mb-5">
                <svg width="18" height="18" class="shrink-0 text-secondary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                جستجو در مقالات، خدمات و پروژه‌ها…
            </button>

            <?php if ($kmpr_items) : ?>
                <ul class="grid grid-cols-3 gap-2.5" role="list">
                    <?php foreach ($kmpr_items as $kmpr_i => $kmpr_item) :
                        $kmpr_active = $kmpr_is($kmpr_item->url); ?>
                        <li class="kmpr-sheet-tile" style="--i:<?= (int) $kmpr_i; ?>">
                            <a href="<?= esc_url($kmpr_item->url); ?>" <?= $kmpr_active ? 'aria-current="page"' : ''; ?>
                               class="flex flex-col items-center justify-center gap-2 h-[5.5rem] px-2 rounded-2xl border text-center transition-colors active:scale-[0.97] <?= $kmpr_active ? 'border-secondary/50 bg-secondary/15 text-secondary' : 'border-white/10 bg-white/[0.03] text-slate-200 active:bg-white/[0.07]'; ?>">
                                <span class="<?= $kmpr_active ? 'text-secondary' : 'text-slate-300'; ?>"><?= kmpr_nav_svg(kmpr_nav_icon_for($kmpr_item->url), 22); ?></span>
                                <span class="text-xs font-bold leading-tight"><?= esc_html($kmpr_item->title); ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <a href="<?= esc_url(kmpr_request_url()); ?>"
               class="mt-5 flex items-center justify-between gap-3 p-4 rounded-2xl bg-gradient-to-l from-secondary/25 via-secondary/10 to-transparent border border-secondary/30">
                <span class="min-w-0">
                    <span class="block text-sm font-black text-white">پروژه‌ای در ذهن دارید؟</span>
                    <span class="block text-xs text-slate-300 mt-1">بریف را در کمتر از ۲ دقیقه بسازید</span>
                </span>
                <span class="shrink-0 inline-flex items-center gap-1.5 h-10 px-4 rounded-full bg-secondary text-slate-950 text-xs font-black whitespace-nowrap">
                    شروع
                    <svg width="14" height="14" class="shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.61l4.16 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.61 9.25h10.64A.75.75 0 0 1 17 10Z" clip-rule="evenodd"/></svg>
                </span>
            </a>

            <?php if ($kmpr_quick) : ?>
                <div class="mt-4 grid <?= array(1 => 'grid-cols-1', 2 => 'grid-cols-2', 3 => 'grid-cols-3')[count($kmpr_quick)]; ?> gap-2.5">
                    <?php foreach ($kmpr_quick as $kmpr_q) : ?>
                        <a href="<?= esc_url($kmpr_q[0]); ?>" <?= strpos($kmpr_q[0], 'tel:') === 0 ? '' : 'target="_blank" rel="noopener"'; ?>
                           class="flex items-center justify-center gap-2 h-12 rounded-2xl border text-xs font-bold whitespace-nowrap <?= esc_attr($kmpr_q[2]); ?>">
                            <svg width="18" height="18" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?= $kmpr_q[3]; // static markup ?></svg>
                            <?= esc_html($kmpr_q[1]); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
