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
        class="fixed top-0 inset-x-0 w-full backdrop-blur-xl transition-all duration-300 z-50">
    <nav class="container mx-auto flex items-center h-16 lg:h-20 px-3 sm:px-6 justify-between gap-2 lg:gap-4 flex-nowrap">
        
        <!-- Brand & Main Navigation (Desktop / Laptop) -->
        <div class="flex items-center gap-3 lg:gap-5 xl:gap-8 shrink-0 min-w-0">
            <!-- Brand Logo -->
            <div class="shrink-0">
                <?php get_template_part('template-parts/logo-brand'); ?>
            </div>
            
            <!-- Desktop Menu (>= 1280px) -->
            <?php if (has_nav_menu('headerMenuLocation')) : ?>
                <div class="hidden xl:block">
                    <?php
                    wp_nav_menu(array(
                        'theme_location' => 'headerMenuLocation',
                        'menu_class'     => 'flex items-center gap-1 text-sm font-bold text-slate-300 whitespace-nowrap [&_a]:px-3 [&_a]:py-2 [&_a]:rounded-xl [&_a]:transition-all [&_a]:duration-200 [&_a:hover]:text-white [&_a:hover]:bg-white/5 [&_.current-menu-item>a]:!text-secondary [&_.current-menu-item>a]:!bg-secondary/15 [&_.current-menu-item>a]:!font-black [&_.active>a]:!text-secondary [&_.active>a]:!bg-secondary/15 [&_.active>a]:!font-black',
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
                        'menu_class'     => 'flex items-center gap-0.5 text-xs font-bold text-slate-300 whitespace-nowrap [&_a]:px-2.5 [&_a]:py-1.5 [&_a]:rounded-lg [&_a]:transition-all [&_a:hover]:text-white [&_a:hover]:bg-white/5 [&_.current-menu-item>a]:!text-secondary [&_.current-menu-item>a]:!bg-secondary/15 [&_.current-menu-item>a]:!font-black [&_.active>a]:!text-secondary [&_.active>a]:!bg-secondary/15 [&_.active>a]:!font-black',
                        'container'      => false,
                        'depth'          => 1,
                    ));
                    ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Action Buttons (Responsive across all screens) -->
        <div class="flex items-center gap-1.5 sm:gap-2.5 lg:gap-3 shrink-0 flex-nowrap">
            <!-- Full Search Trigger Bar (>= 1536px / 2xl) -->
            <button type="button" 
                    @click="searchOpen = true"
                    class="hidden 2xl:flex items-center gap-3 bg-white/[0.04] hover:bg-white/[0.08] border border-white/10 rounded-full px-4 py-2 text-slate-400 hover:text-white transition-all cursor-pointer group shrink-0"
                    aria-label="Open Search">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" class="text-slate-400 group-hover:text-secondary transition-colors" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
                </svg>
                <span class="text-xs font-bold text-slate-400 group-hover:text-slate-200 transition-colors">جستجو در مقالات و پروژه‌ها...</span>
                <kbd class="text-[10px] bg-white/10 border border-white/15 px-1.5 py-0.5 rounded text-slate-400 font-mono">⌘K</kbd>
            </button>

            <!-- Compact Search Pill (1024px to 1535px / lg to xl) -->
            <button type="button" 
                    @click="searchOpen = true"
                    class="hidden lg:flex 2xl:hidden items-center gap-2 bg-white/[0.04] hover:bg-white/[0.08] border border-white/10 rounded-full px-3 py-1.5 text-slate-400 hover:text-white transition-all cursor-pointer group shrink-0"
                    aria-label="Open Search">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" class="text-slate-400 group-hover:text-secondary transition-colors" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
                </svg>
                <span class="text-xs font-bold text-slate-400 group-hover:text-slate-200">جستجو</span>
                <kbd class="text-[9px] bg-white/10 border border-white/15 px-1 py-0.5 rounded text-slate-400 font-mono">⌘K</kbd>
            </button>

            <!-- Icon Search Button (Mobile & Tablet: < 1024px) -->
            <button type="button" 
                    @click="searchOpen = true"
                    class="lg:hidden size-9 sm:size-10 flex items-center justify-center bg-white/[0.04] hover:bg-white/[0.08] border border-white/10 rounded-xl text-slate-400 hover:text-white transition-all cursor-pointer shrink-0"
                    aria-label="Open Search">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
                </svg>
            </button>

            <!-- Online Resume Pill (Desktop & Tablet >= 640px) -->
            <a href="<?= esc_url(home_url('/resume/')); ?>" 
               class="hidden sm:inline-flex items-center gap-1.5 bg-white/[0.04] hover:bg-white/[0.09] border border-white/10 hover:border-secondary/40 text-slate-300 hover:text-white rounded-full px-3 sm:px-3.5 py-1.5 lg:py-2 text-xs font-bold transition-all group shrink-0 whitespace-nowrap">
                <svg class="size-3.5 text-secondary group-hover:scale-110 transition-transform shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                </svg>
                <span>رزومه آنلاین</span>
            </a>

            <!-- Direct Contact Button (Adaptive text by viewport) -->
            <a href="<?= esc_url(home_url('/contact-us') ?: home_url('/contact')); ?>" 
               class="bg-secondary hover:bg-secondary-hover text-white text-xs font-black px-3 sm:px-4 py-2 lg:py-2.5 rounded-full transition-all duration-300 shadow-md shadow-secondary/20 hover:scale-105 active:scale-95 flex items-center gap-1.5 shrink-0 whitespace-nowrap">
                <span class="size-1.5 sm:size-2 rounded-full bg-emerald-400 animate-pulse shrink-0"></span>
                <span class="hidden md:inline">ارتباط مستقیم با من</span>
                <span class="md:hidden">تماس با من</span>
            </a>

            <!-- Hamburger Toggle for Mobile & Tablet (< 1024px) -->
            <button @click="menuOpen = !menuOpen" 
                    :class="menuOpen ? 'bg-secondary text-white border-secondary' : 'bg-white/[0.04] hover:bg-white/[0.08] text-slate-300 border-white/10'"
                    class="lg:hidden size-9 sm:size-10 rounded-xl border flex items-center justify-center transition-all duration-200 cursor-pointer shrink-0" 
                    aria-label="Toggle Navigation Menu">
                <div class="relative w-4 sm:w-5 h-3.5 sm:h-4 flex flex-col justify-between items-center pointer-events-none">
                    <span class="w-full h-0.5 bg-current rounded-full transition-all duration-300 transform origin-center"
                          :class="menuOpen ? 'rotate-45 translate-y-[6px] sm:translate-y-[7px]' : ''"></span>
                    <span class="w-full h-0.5 bg-current rounded-full transition-opacity duration-200"
                          :class="menuOpen ? 'opacity-0' : 'opacity-100'"></span>
                    <span class="w-full h-0.5 bg-current rounded-full transition-all duration-300 transform origin-center"
                          :class="menuOpen ? '-rotate-45 -translate-y-[6px] sm:-translate-y-[7px]' : ''"></span>
                </div>
            </button>
        </div>
    </nav>
</header>

<!-- Mobile Navigation Backdrop Overlay -->
<div x-show="menuOpen" 
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 bg-slate-950/75 backdrop-blur-sm z-40 lg:hidden"
     @click="menuOpen = false"
     x-cloak>
</div>

<!-- Mobile Navigation Drawer Overlay (Slides down smoothly below top header) -->
<div x-show="menuOpen" 
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 -translate-y-4"
     x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 translate-y-0"
     x-transition:leave-end="opacity-0 -translate-y-4"
     class="fixed top-16 sm:top-20 inset-x-3 sm:inset-x-6 max-w-xl mx-auto bg-slate-950/95 backdrop-blur-2xl border border-white/10 rounded-3xl shadow-2xl z-50 p-5 sm:p-6 lg:hidden max-h-[calc(100vh-5.5rem)] overflow-y-auto text-start"
     @click.away="menuOpen = false"
     x-cloak>
    <div class="flex flex-col gap-4">
        <!-- Header inside drawer -->
        <div class="flex items-center justify-between border-b border-white/10 pb-3">
            <div class="flex items-center gap-2">
                <span class="size-2 rounded-full bg-secondary"></span>
                <h5 class="text-xs font-black uppercase tracking-wider text-slate-300">منوی ناوبری سایت</h5>
            </div>
            <button @click="menuOpen = false" 
                    class="text-slate-400 hover:text-white p-1.5 rounded-lg hover:bg-white/10 transition-colors"
                    aria-label="Close menu">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
                </svg>
            </button>
        </div>
        
        <!-- Navigation Links -->
        <?php
        if (has_nav_menu('headerMenuLocation') || has_nav_menu('topHeaderMenuLocation')) :
            $location = has_nav_menu('headerMenuLocation') ? 'headerMenuLocation' : 'topHeaderMenuLocation';
            wp_nav_menu(array(
                'theme_location' => $location,
                'menu_class'     => 'flex flex-col gap-1.5 text-sm sm:text-base font-bold text-slate-200 [&_a]:px-3.5 [&_a]:py-2.5 [&_a]:rounded-xl [&_a]:flex [&_a]:items-center [&_a]:justify-between [&_a]:transition-colors [&_a:hover]:text-secondary [&_a:hover]:bg-white/5 [&_.current-menu-item>a]:!text-secondary [&_.current-menu-item>a]:!bg-secondary/15 [&_.current-menu-item>a]:!font-black [&_.active>a]:!text-secondary [&_.active>a]:!bg-secondary/15 [&_.active>a]:!font-black',
                'container'      => false,
                'depth'          => 1,
            ));
        endif;
        ?>

        <!-- Quick Search Trigger in Drawer -->
        <button @click="menuOpen = false; searchOpen = true" 
                class="bg-white/[0.04] hover:bg-white/[0.08] border border-white/10 text-slate-300 w-full py-2.5 px-4 rounded-xl text-xs font-bold flex items-center justify-between transition-colors">
            <div class="flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" class="text-secondary" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
                </svg>
                <span>جستجوی مقالات و پروژه‌ها...</span>
            </div>
            <kbd class="text-[10px] bg-white/10 px-1.5 py-0.5 rounded text-slate-400 font-mono">⌘K</kbd>
        </button>

        <!-- Mobile Online Resume Button -->
        <a href="<?= esc_url(home_url('/resume/')); ?>" 
           class="bg-white/[0.04] hover:bg-white/[0.08] border border-white/10 hover:border-secondary/40 text-slate-200 text-center py-2.5 rounded-xl text-xs font-bold flex items-center justify-center gap-2 transition-colors">
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
           class="bg-secondary hover:bg-secondary-hover text-white text-center py-3 rounded-xl text-xs sm:text-sm font-black flex items-center justify-center gap-2 shadow-lg shadow-secondary/20 transition-all">
            <span class="size-2 rounded-full bg-emerald-400 animate-pulse"></span>
            <span>ارتباط مستقیم و شروع همکاری</span>
        </a>
    </div>
</div>

<!-- Tabbed Multi-content Search Modal -->
<?php get_template_part('template-parts/layout/header/search-modal'); ?>
