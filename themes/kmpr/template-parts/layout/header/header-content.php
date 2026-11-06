<?php
/**
 * Modern floating responsive header in Dark Obsidian Glassmorphism
 * Personal Brand: Korosh Mpr
 */
?>

<!-- Header wrapper: Fixed top on desktop, floating bottom bar on mobile -->
<header id="header"
        :class="[scrolled ? 'lg:shadow-2xl lg:shadow-black/60 lg:bg-slate-950/95' : 'lg:bg-slate-950/80']"
        class="fixed max-lg:bottom-4 max-lg:inset-x-4 max-lg:rounded-2xl max-lg:shadow-2xl max-lg:border max-lg:border-white/10 bg-slate-950/90 backdrop-blur-xl lg:border-b lg:border-white/10 lg:top-0 lg:start-0 lg:w-full transition-all duration-300 z-50">
    <nav class="container mx-auto flex items-center h-16 lg:h-20 px-4 sm:px-6 justify-between">
        
        <!-- Brand & Main Navigation (Desktop) -->
        <div class="flex items-center gap-8 max-lg:hidden">
            <?php get_template_part('template-parts/logo-brand'); ?>
            
            <?php
            if (has_nav_menu('headerMenuLocation')) :
                wp_nav_menu(array(
                    'theme_location' => 'headerMenuLocation',
                    'menu_class'     => 'flex items-center gap-1 text-sm font-bold text-slate-300 [&_a]:px-3.5 [&_a]:py-2 [&_a]:rounded-xl [&_a]:transition-all [&_a]:duration-200 [&_a:hover]:text-white [&_a:hover]:bg-white/5 [&_.current-menu-item>a]:!text-secondary [&_.current-menu-item>a]:!bg-secondary/15 [&_.current-menu-item>a]:!font-black [&_.active>a]:!text-secondary [&_.active>a]:!bg-secondary/15 [&_.active>a]:!font-black',
                    'container'      => false,
                    'depth'          => 1,
                ));
            endif;
            ?>
        </div>

        <!-- Search Bar & Direct Contact Trigger (Desktop) -->
        <div class="flex items-center gap-3 max-lg:hidden">
            <!-- Modal Search Trigger Button -->
            <button type="button" 
                    @click="searchOpen = true"
                    class="flex items-center gap-3 bg-white/[0.04] hover:bg-white/[0.08] border border-white/10 rounded-full px-4 py-2 text-slate-400 hover:text-white transition-all cursor-pointer shadow-2xs group"
                    aria-label="Open Search">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" class="text-slate-400 group-hover:text-secondary transition-colors" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
                </svg>
                <span class="text-xs font-bold text-slate-400 group-hover:text-slate-200 transition-colors">جستجو در مقالات و پروژه‌ها...</span>
                <kbd class="text-[10px] bg-white/10 border border-white/15 px-1.5 py-0.5 rounded text-slate-400 font-mono shadow-2xs">⌘K</kbd>
            </button>

            <!-- Online Resume Pill (Desktop) -->
            <a href="<?= esc_url(home_url('/resume/')); ?>" 
               class="flex items-center gap-2 bg-white/[0.04] hover:bg-white/[0.09] border border-white/10 hover:border-secondary/40 text-slate-300 hover:text-white rounded-full px-4 py-2 text-xs font-bold transition-all group">
                <svg class="size-3.5 text-secondary group-hover:scale-110 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                </svg>
                <span>رزومه آنلاین</span>
            </a>

            <!-- Direct Contact Button -->
            <a href="<?= esc_url(home_url('/contact-us') ?: home_url('/contact')); ?>" 
               class="bg-secondary hover:bg-secondary-hover text-white text-xs font-black px-4 py-2.5 rounded-full transition-all duration-300 shadow-lg shadow-secondary/25 hover:scale-105 active:scale-95 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                <span>ارتباط مستقیم با من</span>
            </a>
        </div>

        <!-- Mobile Navigation Row (Full width on mobile) -->
        <div class="lg:hidden flex items-center justify-between w-full">
            <!-- Menu button -->
            <button @click="menuOpen = !menuOpen" 
                    :class="menuOpen ? 'bg-secondary text-white font-black' : 'text-slate-300 hover:bg-white/10'"
                    class="p-2.5 rounded-xl transition-all duration-200 cursor-pointer" 
                    aria-label="Toggle Mobile Menu">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M2.5 12a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4.172a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5z"/>
                </svg>
            </button>

            <!-- Brand Monogram (Centered) -->
            <a href="<?= esc_url(home_url('/')); ?>" class="flex items-center gap-2.5">
                <span class="size-8 rounded-lg bg-slate-900 border border-secondary/40 flex items-center justify-center shadow-sm">
                    <svg class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M7 8L3 12L7 16" stroke="#38bdf8" />
                        <path d="M12 4V20" stroke="#f59e0b" />
                        <path d="M12 12L17 7" stroke="#f59e0b" />
                        <path d="M14 10.5L18 17" stroke="#f59e0b" />
                        <path d="M19 9L22 12L19 15" stroke="#38bdf8" stroke-opacity="0.7" />
                    </svg>
                </span>
                <span class="font-black text-sm text-white">کوروش محمدپور</span>
            </a>

            <!-- Search trigger (Opens the modal!) -->
            <button @click="searchOpen = true" 
                    :class="searchOpen ? 'bg-secondary text-white' : 'text-slate-300 hover:bg-white/10'"
                    class="p-2.5 rounded-xl transition-all duration-200 cursor-pointer" 
                    aria-label="Open Search">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
                </svg>
            </button>
        </div>
    </nav>
</header>

<!-- Mobile Navigation Drawer Overlay -->
<div x-show="menuOpen" 
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 translate-y-10"
     x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 translate-y-0"
     x-transition:leave-end="opacity-0 translate-y-10"
     class="fixed bottom-24 inset-x-4 bg-slate-950/95 backdrop-blur-2xl border border-white/10 rounded-3xl shadow-2xl z-50 p-6 lg:hidden"
     @click.away="menuOpen = false"
     x-cloak>
    <div class="flex flex-col gap-4 text-start">
        <div class="flex items-center justify-between border-b border-white/10 pb-3">
            <h5 class="text-xs font-black uppercase tracking-wider text-slate-400">بخش‌های سایت</h5>
            <button @click="menuOpen = false" class="text-slate-400 hover:text-white p-1">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
                </svg>
            </button>
        </div>
        
        <?php
        if (has_nav_menu('topHeaderMenuLocation') || has_nav_menu('headerMenuLocation')) :
            $location = has_nav_menu('topHeaderMenuLocation') ? 'topHeaderMenuLocation' : 'headerMenuLocation';
            wp_nav_menu(array(
                'theme_location' => $location,
                'menu_class'     => 'flex flex-col gap-2 text-base font-bold text-slate-200 [&_a]:px-3.5 [&_a]:py-2.5 [&_a]:rounded-xl [&_a]:block [&_a]:transition-colors [&_a:hover]:text-secondary [&_a:hover]:bg-white/5 [&_.current-menu-item>a]:!text-secondary [&_.current-menu-item>a]:!bg-secondary/15 [&_.current-menu-item>a]:!font-black [&_.active>a]:!text-secondary [&_.active>a]:!bg-secondary/15 [&_.active>a]:!font-black',
                'container'      => false,
                'depth'          => 1,
            ));
        endif;
        ?>

        <!-- Mobile Online Resume Button -->
        <a href="<?= esc_url(home_url('/resume/')); ?>" 
           class="bg-white/[0.05] hover:bg-white/[0.1] border border-white/10 text-slate-200 text-center py-2.5 rounded-2xl text-xs font-bold flex items-center justify-center gap-2 transition-colors">
            <svg class="size-3.5 text-secondary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <line x1="16" y1="13" x2="8" y2="13"></line>
                <line x1="16" y1="17" x2="8" y2="17"></line>
            </svg>
            <span>مشاهده رزومه آنلاین (CV)</span>
        </a>

        <!-- Mobile Direct Contact Button -->
        <a href="<?= esc_url(home_url('/contact-us') ?: home_url('/contact')); ?>" 
           class="mt-2 bg-secondary text-white text-center py-3 rounded-2xl text-sm font-black flex items-center justify-center gap-2 shadow-lg shadow-secondary/20">
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
            <span>ارتباط مستقیم و شروع همکاری</span>
        </a>
    </div>
</div>

<!-- Tabbed Multi-content Search Modal -->
<?php get_template_part('template-parts/layout/header/search-modal'); ?>
