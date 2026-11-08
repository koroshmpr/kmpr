<?php
/**
 * Modern floating responsive header in Dark Obsidian Glassmorphism
 * Personal Brand: Korosh Mpr | Senior Software Architect
 * Fully responsive across Mobile (<640px), Tablet (640px-1023px), Laptop (1024px-1279px), and Desktop (>=1280px)
 */
?>

<!-- Header wrapper: Fixed top with subtle glassmorphic backdrop on all screens -->
<header id="header"
        :class="[scrolled ? 'shadow-2xl shadow-black/80 bg-slate-950/95 border-b border-white/10' : 'bg-slate-950/85 border-b border-white/5']"
        class="fixed top-0 inset-x-0 w-full pt-[env(safe-area-inset-top)] backdrop-blur-xl transition-[background-color,box-shadow] duration-300 z-50">
    <nav class="container mx-auto flex items-center h-14 sm:h-16 lg:h-20 px-4 sm:px-6 justify-between gap-2 lg:gap-4 flex-nowrap">
        
        <!-- Brand & Main Navigation (Desktop / Laptop) -->
        <div class="flex items-center self-stretch gap-3 lg:gap-5 xl:gap-8 shrink-0 min-w-0">
            <!-- Brand Logo -->
            <div class="shrink-0 flex items-center">
                <?php get_template_part('template-parts/logo-brand', null, array('sizeLogo' => 'h-9 lg:h-10 flex items-center')); ?>
            </div>
            
            <!-- Desktop Menu (>= 1280px) -->
            <?php if (has_nav_menu('headerMenuLocation')) : ?>
                <div class="hidden xl:block">
                    <?php
                    wp_nav_menu(array(
                        'theme_location' => 'headerMenuLocation',
                        'menu_class'     => 'flex items-center gap-1 text-sm font-bold text-slate-300 whitespace-nowrap [&_a]:inline-flex [&_a]:items-center [&_a]:gap-2 [&_.kmpr-nav-ico]:opacity-60 [&_a:hover_.kmpr-nav-ico]:opacity-100 [&_.current-menu-item_.kmpr-nav-ico]:opacity-100 [&_a]:px-3 [&_a]:py-2 [&_a]:rounded-xl [&_a]:transition-all [&_a]:duration-200 [&_a:hover]:text-white [&_a:hover]:bg-white/5 [&_.current-menu-item>a]:!text-secondary [&_.current-menu-item>a]:!bg-secondary/15 [&_.current-menu-item>a]:!font-black [&_.active>a]:!text-secondary [&_.active>a]:!bg-secondary/15 [&_.active>a]:!font-black',
                        'container'      => false,
                        'depth'          => 1,
                    ));
                    ?>
                </div>
                <!-- Compact Menu for Laptop (1024px to 1279px) -->
                <div class="hidden lg:block xl:hidden">
                    <?php
                    wp_nav_menu(array(
                        'theme_location' => 'headerMenuLocation',
                        'menu_class'     => 'flex items-center gap-0.5 text-xs font-bold text-slate-300 whitespace-nowrap [&_a]:inline-flex [&_a]:items-center [&_a]:gap-1.5 [&_.kmpr-nav-ico]:opacity-60 [&_a:hover_.kmpr-nav-ico]:opacity-100 [&_.current-menu-item_.kmpr-nav-ico]:opacity-100 [&_a]:px-2.5 [&_a]:py-1.5 [&_a]:rounded-lg [&_a]:transition-all [&_a:hover]:text-white [&_a:hover]:bg-white/5 [&_.current-menu-item>a]:!text-secondary [&_.current-menu-item>a]:!bg-secondary/15 [&_.current-menu-item>a]:!font-black [&_.active>a]:!text-secondary [&_.active>a]:!bg-secondary/15 [&_.active>a]:!font-black',
                        'container'      => false,
                        'depth'          => 1,
                    ));
                    ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Actions: one minimal icon toolbar + contact CTA -->
        <div class="flex items-center gap-2 lg:gap-3 shrink-0 flex-nowrap">
            <div class="flex items-center gap-0.5 p-1 rounded-full bg-white/[0.04] border border-white/10">
                <button type="button" @click="searchOpen = true" class="relative group size-9 lg:size-10 shrink-0 rounded-full flex items-center justify-center text-slate-300 hover:text-white hover:bg-white/[0.08] transition-colors cursor-pointer" aria-label="جستجو (Ctrl/⌘ + K)">
                    <?= kmpr_nav_svg('search', 18); ?>
                    <span class="pointer-events-none absolute top-full mt-2.5 left-1/2 -translate-x-1/2 px-2.5 py-1 rounded-lg bg-slate-800 border border-white/10 text-[11px] font-bold text-slate-100 whitespace-nowrap opacity-0 translate-y-1 group-hover:opacity-100 group-hover:translate-y-0 group-focus-visible:opacity-100 transition duration-200 max-lg:hidden" role="tooltip">جستجو <span class="text-slate-400 font-sans">⌘K</span></span>
                </button>
                <a href="<?= esc_url(home_url('/resume/')); ?>" class="hidden sm:flex relative group size-9 lg:size-10 shrink-0 rounded-full items-center justify-center text-slate-300 hover:text-white hover:bg-white/[0.08] transition-colors cursor-pointer" aria-label="رزومه آنلاین">
                    <?= kmpr_nav_svg('resume', 18); ?>
                    <span class="pointer-events-none absolute top-full mt-2.5 left-1/2 -translate-x-1/2 px-2.5 py-1 rounded-lg bg-slate-800 border border-white/10 text-[11px] font-bold text-slate-100 whitespace-nowrap opacity-0 translate-y-1 group-hover:opacity-100 group-hover:translate-y-0 group-focus-visible:opacity-100 transition duration-200 max-lg:hidden" role="tooltip">رزومه آنلاین</span>
                </a>
                <?php if (current_user_can('manage_options')) : ?>
                    <a href="<?= esc_url(home_url('/panel/')); ?>" class="admin-panel-link min-[783px]:hidden relative group size-9 shrink-0 rounded-full flex items-center justify-center text-amber-300 hover:text-white hover:bg-white/[0.08] transition-colors" aria-label="پنل مدیریت">
                        <svg width="18" height="18" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></svg>
                    </a>
                <?php endif; ?>
                <?php if (post_type_exists('game')) : ?>
                    <span class="w-px h-5 bg-white/10 mx-0.5" aria-hidden="true"></span>
                    <!-- Game center: animated gamepad (idle wiggle; hover = buttons blink, d-pad moves) -->
                    <a href="<?= esc_url(get_post_type_archive_link('game')); ?>" class="kmpr-pad group relative size-9 lg:size-10 shrink-0 rounded-full flex items-center justify-center" aria-label="گیم‌سنتر — بازی‌ها">
                        <span class="kmpr-pad-ring absolute inset-0 rounded-full" aria-hidden="true"></span>
                        <span class="absolute inset-[1.5px] rounded-full bg-slate-950" aria-hidden="true"></span>
                        <svg class="kmpr-pad-svg relative shrink-0" width="24" height="24" viewBox="0 0 32 32" fill="none" aria-hidden="true">
                            <path class="kmpr-pad-body" d="M9.5 9h13a6.5 6.5 0 0 1 6.3 5l1.4 6.4a3.6 3.6 0 0 1-6.2 3.2L21.6 21h-11.2L8 23.6a3.6 3.6 0 0 1-6.2-3.2L3.2 14a6.5 6.5 0 0 1 6.3-5Z" fill="#1e1b2e" stroke="url(#kmpr-pad-g)" stroke-width="1.6" stroke-linejoin="round"/>
                            <g class="kmpr-pad-dpad" fill="#cbd5e1"><rect x="8.6" y="12.2" width="2.2" height="6.6" rx=".6"/><rect x="6.4" y="14.4" width="6.6" height="2.2" rx=".6"/></g>
                            <circle class="kmpr-pad-a" cx="22.6" cy="13.6" r="1.55" fill="#f59e0b"/>
                            <circle class="kmpr-pad-b" cx="25.4" cy="16.4" r="1.55" fill="#e879f9"/>
                            <circle class="kmpr-pad-x" cx="19.8" cy="16.4" r="1.55" fill="#38bdf8"/>
                            <circle class="kmpr-pad-y" cx="22.6" cy="19.2" r="1.55" fill="#34d399"/>
                            <defs><linearGradient id="kmpr-pad-g" x1="3" y1="9" x2="30" y2="24"><stop stop-color="#e879f9"/><stop offset="1" stop-color="#f59e0b"/></linearGradient></defs>
                        </svg>
                        <span class="kmpr-pad-dot absolute top-0 end-0 size-2 rounded-full bg-emerald-400 ring-2 ring-slate-950" aria-hidden="true"></span>
                        <span class="pointer-events-none absolute top-full mt-2.5 left-1/2 -translate-x-1/2 px-2.5 py-1 rounded-lg bg-slate-800 border border-white/10 text-[11px] font-bold text-slate-100 whitespace-nowrap opacity-0 translate-y-1 group-hover:opacity-100 group-hover:translate-y-0 group-focus-visible:opacity-100 transition duration-200 max-lg:hidden" role="tooltip">گیم‌سنتر</span>
                    </a>
                <?php endif; ?>
            </div>

            <a href="<?= esc_url(function_exists('kmpr_request_url') ? kmpr_request_url() : home_url('/contact-us/')); ?>"
               class="hidden lg:inline-flex items-center gap-2 h-10 ps-3 pe-4 rounded-full bg-secondary hover:bg-amber-500 text-slate-950 text-xs font-black whitespace-nowrap shadow-[0_8px_24px_-8px] shadow-secondary/60 transition-colors active:scale-95">
                <span class="relative size-6 rounded-full bg-slate-950/15 flex items-center justify-center">
                    <?= kmpr_nav_svg('contact', 14); ?>
                    <span class="absolute -top-0.5 -end-0.5 size-2 rounded-full bg-emerald-400 ring-2 ring-secondary" aria-hidden="true"></span>
                </span>
                شروع همکاری
            </a>
        </div>
    </nav>
</header>

<?php get_template_part('template-parts/layout/header/mobile-nav'); ?>

<!-- Tabbed Multi-content Search Modal -->
<?php get_template_part('template-parts/layout/header/search-modal'); ?>
