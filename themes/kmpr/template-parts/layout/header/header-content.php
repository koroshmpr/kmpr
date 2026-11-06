<?php
/**
 * Modern floating responsive header using Tailwind CSS and Alpine.js
 */
?>

<!-- Header wrapper. Fixed top on desktop, floating bottom bar on mobile -->
<header id="header"
        :class="[scrolled ? 'lg:shadow-md' : '']"
        class="fixed max-lg:bottom-4 max-lg:inset-x-4 max-lg:rounded-2xl max-lg:shadow-lg max-lg:border max-lg:border-slate-200/80 bg-white/90 backdrop-blur-md lg:bg-white lg:top-0 lg:start-0 lg:w-full transition-all duration-300 z-50">
    <nav class="container mx-auto flex items-center h-16 lg:h-20 px-6 justify-between">
        
        <!-- Brand & Main Navigation (Desktop) -->
        <div class="flex items-center gap-8 max-lg:hidden">
            <?php get_template_part('template-parts/logo-brand', null, ['sizeLogo' => 'h-8 lg:h-10 w-auto flex items-center']); ?>
            
            <?php
            if (has_nav_menu('headerMenuLocation')) :
                wp_nav_menu(array(
                    'theme_location' => 'headerMenuLocation',
                    'menu_class' => 'flex items-center gap-6 text-sm font-semibold text-slate-700 hover:[&_a]:text-secondary [&_a]:transition-colors',
                    'container' => false,
                    'depth' => 1,
                ));
            endif;
            ?>
        </div>

        <!-- Search Bar & Actions (Desktop) -->
        <div class="flex items-center gap-4 max-lg:hidden">
            <!-- Inline Search -->
            <form class="flex items-center gap-2 relative bg-slate-100 rounded-full px-4 py-1.5 border border-transparent focus-within:border-slate-300 transition-all"
                  method="get" action="<?php echo esc_url(home_url('/')); ?>">
                <input type="search" name="s" class="bg-transparent text-slate-800 text-sm outline-none w-48 placeholder:text-slate-400 font-medium" placeholder="جستجو...">
                <button type="submit" class="text-slate-500 hover:text-slate-800 transition-colors" aria-label="Search">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
                    </svg>
                </button>
            </form>
        </div>

        <!-- Mobile Navigation Row (Full width on mobile) -->
        <div class="lg:hidden flex items-center justify-between w-full">
            <!-- Menu button -->
            <button @click="menuOpen = !menuOpen" 
                    :class="menuOpen ? 'bg-secondary/10 text-secondary' : 'text-slate-600'"
                    class="p-2.5 rounded-xl transition-all duration-300 hover:bg-slate-100 cursor-pointer" aria-label="Toggle Mobile Menu">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M2.5 12a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4.172a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5z"/>
                </svg>
            </button>

            <!-- Logo (Centered) -->
            <?php get_template_part('template-parts/logo-brand', null, ['sizeLogo' => 'h-7 w-auto flex items-center justify-center']); ?>

            <!-- Search trigger -->
            <button @click="searchOpen = !searchOpen" 
                    :class="searchOpen ? 'bg-secondary/10 text-secondary' : 'text-slate-600'"
                    class="p-2.5 rounded-xl transition-all duration-300 hover:bg-slate-100 cursor-pointer" aria-label="Open Search">
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
     class="fixed bottom-24 inset-x-4 bg-white border border-slate-200 rounded-2xl shadow-xl z-50 p-6 lg:hidden"
     x-cloak>
    <div class="flex flex-col gap-5 text-start">
        <h5 class="text-xs font-bold uppercase tracking-wider text-slate-400 border-b pb-2 mb-1">منوی ناوبری</h5>
        <?php
        if (has_nav_menu('topHeaderMenuLocation') || has_nav_menu('headerMenuLocation')) :
            $location = has_nav_menu('topHeaderMenuLocation') ? 'topHeaderMenuLocation' : 'headerMenuLocation';
            wp_nav_menu(array(
                'theme_location' => $location,
                'menu_class' => 'flex flex-col gap-4 text-base font-bold text-slate-800 hover:[&_a]:text-secondary',
                'container' => false,
                'depth' => 1,
            ));
        endif;
        ?>
    </div>
</div>

<!-- Reactive Live Search Modal Overlay -->
<div x-show="searchOpen" 
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-start justify-center pt-24 px-4"
     @click.self="searchOpen = false"
     x-cloak>
    
    <div class="bg-white rounded-2xl w-full max-w-xl shadow-2xl overflow-hidden border border-slate-100"
         x-data="themeSearch()"
         @click.away="searchOpen = false">
        
        <!-- Search Input Row -->
        <div class="flex items-center gap-3 px-5 py-4 border-b border-slate-100">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" class="text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input type="search" 
                   x-model="query"
                   @keydown.escape="searchOpen = false"
                   class="w-full text-base outline-none bg-transparent placeholder:text-slate-400 text-slate-800 font-medium"
                   placeholder="جستجوی مقالات، خدمات و نمونه‌کارها...">
            <button @click="searchOpen = false; query = ''" class="text-slate-400 hover:text-slate-700 p-1 rounded-lg">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
                </svg>
            </button>
        </div>

        <!-- Search Results list -->
        <div class="max-h-96 overflow-y-auto p-4 bg-slate-50/50" x-show="query.length > 2">
            <!-- Spinner -->
            <div x-show="isLoading" class="flex justify-center py-8">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
            </div>

            <!-- No results -->
            <div x-show="!isLoading && results.length === 0" class="text-center py-8 text-slate-400 text-sm">
                هیچ نتیجه‌ای یافت نشد.
            </div>

            <!-- Results -->
            <div x-show="!isLoading && results.length > 0" class="flex flex-col gap-2">
                <template x-for="item in results" :key="item.id">
                    <a :href="item.link" class="flex items-center gap-4 bg-white p-3 rounded-xl hover:bg-slate-100 hover:shadow-sm border border-slate-100 transition-all">
                        <img x-show="item._embedded && item._embedded['wp:featuredmedia']"
                             :src="item._embedded && item._embedded['wp:featuredmedia'] ? item._embedded['wp:featuredmedia'][0].source_url : ''"
                             class="size-14 object-cover rounded-lg bg-slate-100 shrink-0"
                             alt="">
                        <div class="flex flex-col text-start">
                            <h6 class="font-bold text-slate-800 text-sm" x-html="item.title.rendered"></h6>
                            <p class="text-xs text-slate-400 mt-1 truncate" x-html="item.excerpt.rendered"></p>
                        </div>
                    </a>
                </template>
            </div>
        </div>
    </div>
</div>
