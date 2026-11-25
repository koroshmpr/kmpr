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
<nav class="kmpr-tabbar lg:hidden fixed inset-x-4 z-50 mx-auto max-w-sm flex items-center gap-2" aria-label="ناوبری اصلی موبایل"
     :class="menuOpen || searchOpen ? 'translate-y-[140%] opacity-0' : ''">
    <div class="kmpr-glass flex-1 grid grid-cols-4 h-14 px-1 rounded-full">
        <?php foreach (array_filter($kmpr_tabs) as $kmpr_tab) :
            $kmpr_active = $kmpr_is($kmpr_tab['url']); ?>
            <a href="<?= esc_url($kmpr_tab['url']); ?>" <?= $kmpr_active ? 'aria-current="page"' : ''; ?>
               class="kmpr-tab <?= $kmpr_active ? 'is-active' : ''; ?> flex flex-col items-center justify-center gap-[3px]">
                <span class="kmpr-tab-ico"><?= kmpr_nav_svg($kmpr_tab['icon'], 21); // static markup ?></span>
                <span class="kmpr-tab-txt"><?= esc_html($kmpr_tab['label']); ?></span>
            </a>
        <?php endforeach; ?>
        <button type="button" @click="menuOpen = true" :aria-expanded="menuOpen.toString()" aria-controls="kmpr-sheet"
                class="kmpr-tab flex flex-col items-center justify-center gap-[3px] cursor-pointer">
            <span class="kmpr-tab-ico">
                <svg width="21" height="21" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h10"/></svg>
            </span>
            <span class="kmpr-tab-txt">منو</span>
        </button>
    </div>
    <a href="<?= esc_url(kmpr_request_url()); ?>" class="kmpr-fab shrink-0 size-14 rounded-full flex items-center justify-center text-slate-950" aria-label="ثبت درخواست پروژه جدید">
        <svg width="22" height="22" class="kmpr-fab-ico shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
    </a>
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
         class="kmpr-sheet kmpr-sheet-glass absolute inset-x-0 bottom-0 max-h-[88svh] flex flex-col rounded-t-[1.75rem]">
        <div class="shrink-0 pt-3 pb-2 flex flex-col items-center">
            <span class="h-1 w-9 rounded-full bg-white/25" aria-hidden="true"></span>
        </div>
        <div x-ref="scroller" class="flex-1 min-h-0 overflow-y-auto overscroll-contain px-5 pb-[calc(1.25rem+env(safe-area-inset-bottom))]">
            <div class="flex items-center justify-between gap-3 mb-4">
                <div class="min-w-0">
                    <p class="text-base font-black text-white"><?= esc_html(get_bloginfo('name')); ?></p>
                    <p class="text-xs text-slate-400 mt-0.5 truncate"><?= esc_html(kmpr_field('job_title', 'طراح و توسعه‌دهنده وب', 'option')); ?></p>
                </div>
                <button type="button" @click="menuOpen = false" class="kmpr-sheet-x size-9 shrink-0 rounded-full bg-white/[0.06] text-slate-300 flex items-center justify-center cursor-pointer" aria-label="بستن منو">
                    <svg width="16" height="16" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </div>

            <button type="button" @click="menuOpen = false; searchOpen = true"
                    class="w-full flex items-center gap-2.5 h-11 px-4 rounded-full bg-white/[0.05] text-[13px] text-slate-400 cursor-pointer mb-5 transition-colors active:bg-white/[0.08]">
                <svg width="18" height="18" class="shrink-0 text-secondary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                جستجو در مقالات، خدمات و پروژه‌ها…
            </button>

            <?php if ($kmpr_items) : ?>
                <ul class="grid <?= count($kmpr_items) % 4 === 0 ? 'grid-cols-4' : 'grid-cols-3'; ?> gap-y-4 gap-x-1" role="list">
                    <?php foreach ($kmpr_items as $kmpr_i => $kmpr_item) :
                        $kmpr_active = $kmpr_is($kmpr_item->url); ?>
                        <li class="kmpr-sheet-tile" style="--i:<?= (int) $kmpr_i; ?>">
                            <a href="<?= esc_url($kmpr_item->url); ?>" <?= $kmpr_active ? 'aria-current="page"' : ''; ?>
                               class="kmpr-tile flex flex-col items-center gap-2 px-1 text-center <?= $kmpr_active ? 'is-active text-white' : 'text-slate-300'; ?>">
                                <span class="kmpr-tile-ico size-12 rounded-2xl flex items-center justify-center"><?= kmpr_nav_svg(kmpr_nav_icon_for($kmpr_item->url), 21); ?></span>
                                <span class="text-[11px] font-bold leading-tight"><?= esc_html($kmpr_item->title); ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <a href="<?= esc_url(kmpr_request_url()); ?>"
               class="kmpr-sheet-tile mt-6 flex items-center justify-between gap-3 p-3 ps-4 rounded-2xl bg-white/[0.04] border border-white/[0.07] transition-colors active:bg-white/[0.07]" style="--i:10">
                <span class="min-w-0">
                    <span class="block text-[13px] font-black text-white">پروژه‌ای در ذهن دارید؟</span>
                    <span class="block text-[11px] text-slate-400 mt-1">بریف را در کمتر از ۲ دقیقه بسازید</span>
                </span>
                <span class="shrink-0 inline-flex items-center gap-1.5 h-9 px-4 rounded-full bg-secondary text-slate-950 text-xs font-black whitespace-nowrap">
                    شروع
                    <svg width="14" height="14" class="shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.61l4.16 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.61 9.25h10.64A.75.75 0 0 1 17 10Z" clip-rule="evenodd"/></svg>
                </span>
            </a>

            <?php if ($kmpr_quick) : ?>
                <div class="kmpr-sheet-tile mt-3 grid <?= array(1 => 'grid-cols-1', 2 => 'grid-cols-2', 3 => 'grid-cols-3')[count($kmpr_quick)]; ?> gap-2" style="--i:11">
                    <?php foreach ($kmpr_quick as $kmpr_q) : ?>
                        <a href="<?= esc_url($kmpr_q[0]); ?>" <?= strpos($kmpr_q[0], 'tel:') === 0 ? '' : 'target="_blank" rel="noopener"'; ?>
                           class="flex items-center justify-center gap-2 h-11 rounded-full bg-white/[0.04] text-slate-200 text-xs font-bold whitespace-nowrap transition-colors active:bg-white/[0.08]">
                            <span class="<?= esc_attr(preg_replace('/\S*(bg|border)-\S+/', '', $kmpr_q[2])); ?>"><svg width="17" height="17" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?= $kmpr_q[3]; // static markup ?></svg></span>
                            <?= esc_html($kmpr_q[1]); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
