<?php
/**
 * Modern floating responsive header using Tailwind CSS and Alpine.js
 */
?>

<!-- Header wrapper: Fixed top on desktop, floating bottom bar on mobile -->
<header id="header"
        :class="[scrolled ? 'lg:shadow-md lg:border-b lg:border-slate-200/80' : '']"
        class="fixed max-lg:bottom-4 max-lg:inset-x-4 max-lg:rounded-2xl max-lg:shadow-xl max-lg:border max-lg:border-slate-200/80 bg-white/95 backdrop-blur-md lg:bg-white lg:top-0 lg:start-0 lg:w-full transition-all duration-300 z-50">
    <nav class="container mx-auto flex items-center h-16 lg:h-20 px-4 sm:px-6 justify-between">
        
        <!-- Brand & Main Navigation (Desktop) -->
        <div class="flex items-center gap-8 max-lg:hidden">
            <?php get_template_part('template-parts/logo-brand', null, ['sizeLogo' => 'h-9 lg:h-10 w-auto flex items-center']); ?>
            
            <?php
            if (has_nav_menu('headerMenuLocation')) :
                wp_nav_menu(array(
                    'theme_location' => 'headerMenuLocation',
                    'menu_class'     => 'flex items-center gap-1.5 text-sm font-bold text-slate-700 [&_a]:px-3.5 [&_a]:py-2 [&_a]:rounded-xl [&_a]:transition-all [&_a]:duration-200 [&_a:hover]:text-secondary [&_a:hover]:bg-slate-100/80 [&_.current-menu-item_a]:text-secondary [&_.current-menu-item_a]:bg-secondary/10 [&_.current-menu-item_a]:font-black [&_.current_page_item_a]:text-secondary',
                    'container'      => false,
                    'depth'          => 1,
                ));
            endif;
            ?>
        </div>

        <!-- Search Bar & Call Trigger (Desktop) -->
        <div class="flex items-center gap-3 max-lg:hidden">
            <!-- Modal Search Trigger Button -->
            <button type="button" 
                    @click="searchOpen = true"
                    class="flex items-center gap-3 bg-slate-100 hover:bg-slate-200/70 border border-slate-200/70 rounded-full px-4 py-2 text-slate-500 hover:text-slate-900 transition-all cursor-pointer shadow-2xs group"
                    aria-label="Open Search">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="text-slate-500 group-hover:text-secondary transition-colors" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
                </svg>
                <span class="text-xs font-bold text-slate-500 group-hover:text-slate-800 transition-colors">جستجو در سایت...</span>
                <kbd class="text-[10px] bg-white border border-slate-200/90 px-1.5 py-0.5 rounded text-slate-400 font-mono shadow-2xs">ESC</kbd>
            </button>

            <!-- Phone quick link if configured -->
            <?php if (get_field('phone', 'option')) : ?>
                <a href="tel:<?= esc_attr(get_field('phone', 'option')); ?>" 
                   class="bg-primary hover:bg-slate-800 text-white text-xs font-bold px-4 py-2.5 rounded-full transition-all duration-300 shadow-sm flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="text-secondary" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M1.885.511a1.745 1.745 0 0 1 2.61.163L6.29 2.98c.329.423.445.974.315 1.494l-.547 2.19a.678.678 0 0 0 .178.643l2.457 2.457a.678.678 0 0 0 .644.178l2.189-.547a1.745 1.745 0 0 1 1.494.315l2.306 1.794c.829.645.905 1.87.163 2.611l-1.034 1.034c-.74.74-1.846 1.065-2.877.702a18.634 18.634 0 0 1-7.01-4.42 18.634 18.634 0 0 1-4.42-7.009c-.362-1.03-.037-2.137.703-2.877L1.885.511z"/>
                    </svg>
                    <span>تماس با ما</span>
                </a>
            <?php endif; ?>
        </div>

        <!-- Mobile Navigation Row (Full width on mobile) -->
        <div class="lg:hidden flex items-center justify-between w-full">
            <!-- Menu button -->
            <button @click="menuOpen = !menuOpen" 
                    :class="menuOpen ? 'bg-secondary text-primary font-black' : 'text-slate-700 hover:bg-slate-100'"
                    class="p-2.5 rounded-xl transition-all duration-200 cursor-pointer" 
                    aria-label="Toggle Mobile Menu">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M2.5 12a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4.172a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5z"/>
                </svg>
            </button>

            <!-- Logo (Centered) -->
            <?php get_template_part('template-parts/logo-brand', null, ['sizeLogo' => 'h-7 w-auto flex items-center justify-center']); ?>

            <!-- Search trigger (Opens the modal!) -->
            <button @click="searchOpen = true" 
                    :class="searchOpen ? 'bg-secondary text-primary' : 'text-slate-700 hover:bg-slate-100'"
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
     class="fixed bottom-24 inset-x-4 bg-white/95 backdrop-blur-md border border-slate-200/90 rounded-3xl shadow-2xl z-50 p-6 lg:hidden"
     @click.away="menuOpen = false"
     x-cloak>
    <div class="flex flex-col gap-4 text-start">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h5 class="text-xs font-black uppercase tracking-wider text-slate-400">منوی ناوبری</h5>
            <button @click="menuOpen = false" class="text-slate-400 hover:text-slate-700 p-1">
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
                'menu_class'     => 'flex flex-col gap-2 text-base font-bold text-slate-800 [&_a]:px-3 [&_a]:py-2 [&_a]:rounded-xl [&_a]:block [&_a]:transition-colors [&_a:hover]:text-secondary [&_a:hover]:bg-slate-100/70 [&_.current-menu-item_a]:text-secondary [&_.current-menu-item_a]:bg-secondary/10',
                'container'      => false,
                'depth'          => 1,
            ));
        endif;
        ?>

        <!-- Mobile Phone Call Button -->
        <?php if (get_field('phone', 'option')) : ?>
            <a href="tel:<?= esc_attr(get_field('phone', 'option')); ?>" 
               class="mt-2 bg-primary text-white text-center py-3 rounded-2xl text-sm font-bold flex items-center justify-center gap-2 shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="text-secondary" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M1.885.511a1.745 1.745 0 0 1 2.61.163L6.29 2.98c.329.423.445.974.315 1.494l-.547 2.19a.678.678 0 0 0 .178.643l2.457 2.457a.678.678 0 0 0 .644.178l2.189-.547a1.745 1.745 0 0 1 1.494.315l2.306 1.794c.829.645.905 1.87.163 2.611l-1.034 1.034c-.74.74-1.846 1.065-2.877.702a18.634 18.634 0 0 1-7.01-4.42 18.634 18.634 0 0 1-4.42-7.009c-.362-1.03-.037-2.137.703-2.877L1.885.511z"/>
                </svg>
                <span>تماس با مجموعه</span>
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Tabbed Multi-content Search Modal -->
<?php get_template_part('template-parts/layout/header/search-modal'); ?>
