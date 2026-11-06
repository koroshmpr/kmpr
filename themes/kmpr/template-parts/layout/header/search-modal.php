<?php
/**
 * Tabbed Live Search Modal Component (Compact 3-Column Grid + Debounced Search)
 * Supports: All (همه), Articles (مقالات), Portfolios (نمونه کارها), Services (خدمات)
 */
?>
<div x-show="searchOpen" 
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 scale-95"
     x-transition:enter-end="opacity-100 scale-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 scale-100"
     x-transition:leave-end="opacity-0 scale-95"
     class="fixed inset-0 bg-slate-950/80 backdrop-blur-md z-[100] flex items-start justify-center pt-6 sm:pt-10 px-3 sm:px-4 overflow-y-auto"
     @click.self="searchOpen = false"
     @keydown.window.escape="searchOpen = false"
     x-cloak>
    
    <div class="bg-white rounded-3xl w-full max-w-5xl shadow-2xl overflow-hidden border border-slate-200 flex flex-col max-h-[88vh] my-auto sm:my-0 text-start"
         x-data="themeSearch()"
         @click.away="searchOpen = false">
        
        <!-- Search Input Bar (With 350ms debounce) -->
        <div class="flex items-center gap-3 px-5 py-3.5 border-b border-slate-200 bg-white sticky top-0 z-20">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" class="text-secondary shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input type="search" 
                   x-model.debounce.350ms="query"
                   x-ref="searchInput"
                   @keydown.escape="searchOpen = false"
                   class="w-full text-base sm:text-lg outline-none bg-transparent placeholder:text-slate-400 text-slate-900 font-bold"
                   placeholder="جستجوی مقالات، نمونه‌کارها و خدمات..."
                   autofocus>
            
            <!-- Clear Button -->
            <button x-show="query.length > 0" 
                    @click="clearQuery()" 
                    class="text-slate-400 hover:text-slate-700 p-1.5 rounded-lg transition-colors cursor-pointer"
                    aria-label="Clear Search">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
                </svg>
            </button>

            <!-- Close Modal Button -->
            <button @click="searchOpen = false" 
                    class="p-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 transition-colors cursor-pointer"
                    aria-label="Close modal">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Tab Filters (All, Blog, Portfolio, Services) -->
        <div class="px-5 py-2.5 border-b border-slate-100 bg-slate-50 flex flex-wrap items-center gap-2">
            <!-- All Tab -->
            <button @click="setTab('all')"
                    :class="activeTab === 'all' ? 'bg-primary text-white shadow-sm' : 'bg-white text-slate-700 hover:bg-slate-200/80 border border-slate-200/70'"
                    class="px-3.5 py-1.5 rounded-xl text-xs sm:text-sm font-black flex items-center gap-2 transition-all cursor-pointer">
                <span>همه</span>
                <span :class="activeTab === 'all' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600'" 
                      class="px-2 py-0.5 rounded-md text-[11px] font-bold" 
                      x-text="counts.all"></span>
            </button>

            <!-- Articles Tab -->
            <button @click="setTab('post')"
                    :class="activeTab === 'post' ? 'bg-primary text-white shadow-sm' : 'bg-white text-slate-700 hover:bg-slate-200/80 border border-slate-200/70'"
                    class="px-3.5 py-1.5 rounded-xl text-xs sm:text-sm font-black flex items-center gap-2 transition-all cursor-pointer">
                <span>مقالات</span>
                <span :class="activeTab === 'post' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600'" 
                      class="px-2 py-0.5 rounded-md text-[11px] font-bold" 
                      x-text="counts.post"></span>
            </button>

            <!-- Portfolio Tab -->
            <button @click="setTab('portfolio')"
                    :class="activeTab === 'portfolio' ? 'bg-primary text-white shadow-sm' : 'bg-white text-slate-700 hover:bg-slate-200/80 border border-slate-200/70'"
                    class="px-3.5 py-1.5 rounded-xl text-xs sm:text-sm font-black flex items-center gap-2 transition-all cursor-pointer">
                <span>نمونه‌کارها</span>
                <span :class="activeTab === 'portfolio' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600'" 
                      class="px-2 py-0.5 rounded-md text-[11px] font-bold" 
                      x-text="counts.portfolio"></span>
            </button>

            <!-- Services Tab -->
            <button @click="setTab('services')"
                    :class="activeTab === 'services' ? 'bg-primary text-white shadow-sm' : 'bg-white text-slate-700 hover:bg-slate-200/80 border border-slate-200/70'"
                    class="px-3.5 py-1.5 rounded-xl text-xs sm:text-sm font-black flex items-center gap-2 transition-all cursor-pointer">
                <span>خدمات</span>
                <span :class="activeTab === 'services' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600'" 
                      class="px-2 py-0.5 rounded-md text-[11px] font-bold" 
                      x-text="counts.services"></span>
            </button>
        </div>

        <!-- Modal Body / 3-Column Results Container -->
        <div class="overflow-y-auto p-4 sm:p-5 flex-1 bg-slate-50/70 min-h-[260px]">
            
            <!-- Loading Indicator -->
            <div x-show="isLoading" class="flex flex-col items-center justify-center py-12 gap-3">
                <div class="animate-spin rounded-full h-8 w-8 border-3 border-secondary border-t-transparent"></div>
                <span class="text-xs font-bold text-slate-500">در حال جستجو...</span>
            </div>

            <!-- Empty Search Prompt -->
            <div x-show="!isLoading && query.length < 2" class="text-center py-12 text-slate-400 text-sm flex flex-col items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="text-slate-300">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <span class="font-bold">برای جستجو عبارت مورد نظر خود را بنویسید (حداقل ۲ حرف)...</span>
            </div>

            <!-- No Results Found -->
            <div x-show="!isLoading && query.length >= 2 && hasSearched && getCurrentResults().length === 0" 
                 class="text-center py-12 text-slate-500 text-sm flex flex-col items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="text-amber-500">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <p class="font-bold">هیچ موردی برای «<span x-text="query" class="text-slate-800"></span>» در این بخش پیدا نشد.</p>
                <p class="text-xs text-slate-400">کلمات دیگری را امتحان کرده یا تب‌های دیگر را بررسی کنید.</p>
            </div>

            <!-- Compact 3-Column Grid for optimal card visibility -->
            <div x-show="!isLoading && getCurrentResults().length > 0" 
                 class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3.5">
                
                <template x-for="item in getCurrentResults()" :key="item.type + '-' + item.id">
                    <div class="h-full">
                        <!-- 1. Post (Article) Card Template -->
                        <template x-if="item.type === 'post'">
                            <a :href="item.url" 
                               class="group bg-white rounded-2xl overflow-hidden shadow-2xs hover:shadow-md border border-slate-200/80 transition-all duration-300 flex flex-col h-full text-start hover:-translate-y-0.5">
                                <div class="relative aspect-[16/9] bg-slate-100 overflow-hidden shrink-0">
                                    <img x-show="item.img" 
                                         :src="item.img" 
                                         class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105" 
                                         :alt="item.title">
                                    <div x-show="!item.img" class="w-full h-full bg-slate-200 flex items-center justify-center text-slate-400 text-xs">بدون تصویر</div>
                                    <span class="absolute bottom-2.5 end-2.5 bg-primary/90 backdrop-blur-xs text-white text-[10px] font-bold px-2 py-0.5 rounded-md shadow-2xs" 
                                          x-text="item.category"></span>
                                </div>
                                <div class="p-3.5 flex flex-col flex-1 gap-1.5">
                                    <h4 class="text-xs sm:text-sm font-black text-slate-900 group-hover:text-secondary transition-colors line-clamp-1 leading-snug" 
                                        x-text="item.title"></h4>
                                    <p class="text-[11px] text-slate-500 leading-relaxed line-clamp-2" 
                                       x-text="item.excerpt"></p>
                                    <div class="flex items-center justify-between border-t border-slate-100 pt-2.5 mt-auto text-[10px] text-slate-400 font-bold">
                                        <span x-text="item.date"></span>
                                        <span class="text-secondary font-black">مطالعه مقاله &larr;</span>
                                    </div>
                                </div>
                            </a>
                        </template>

                        <!-- 2. Portfolio Card Template -->
                        <template x-if="item.type === 'portfolio'">
                            <a :href="item.url" 
                               class="group bg-white rounded-2xl overflow-hidden shadow-2xs hover:shadow-md border border-slate-200/80 transition-all duration-300 flex flex-col h-full text-start hover:-translate-y-0.5">
                                <div class="relative aspect-[16/9] bg-slate-100 overflow-hidden shrink-0">
                                    <img x-show="item.img" 
                                         :src="item.img" 
                                         class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105" 
                                         :alt="item.title">
                                    <div x-show="!item.img" class="w-full h-full bg-slate-200 flex items-center justify-center text-slate-400 text-xs">بدون تصویر</div>
                                    <div x-show="item.brand_logo" 
                                         class="absolute bottom-2 end-2 bg-white/95 p-1 rounded-lg shadow-sm border border-slate-200" 
                                         :class="item.logo_bg">
                                        <img :src="item.brand_logo" class="size-6 object-contain" :alt="item.brand_title">
                                    </div>
                                </div>
                                <div class="p-3.5 flex flex-col flex-1 gap-1.5">
                                    <div class="flex items-center justify-between">
                                        <h4 class="text-xs sm:text-sm font-black text-slate-900 group-hover:text-secondary transition-colors line-clamp-1 leading-snug" 
                                            x-text="item.title"></h4>
                                        <span class="text-[9px] font-black bg-secondary/15 text-secondary px-1.5 py-0.5 rounded shrink-0">نمونه‌کار</span>
                                    </div>
                                    <p class="text-[11px] text-slate-500 leading-relaxed line-clamp-2" 
                                       x-text="item.excerpt"></p>
                                    <div class="flex items-center justify-between border-t border-slate-100 pt-2.5 mt-auto text-[10px]">
                                        <span class="text-slate-400 font-bold">مشاهده پروژه</span>
                                        <span class="text-secondary font-black">&larr;</span>
                                    </div>
                                </div>
                            </a>
                        </template>

                        <!-- 3. Services Card Template -->
                        <template x-if="item.type === 'services'">
                            <a :href="item.url" 
                               class="group relative rounded-2xl overflow-hidden aspect-[16/9] shadow-2xs hover:shadow-lg transition-all duration-300 bg-slate-900 border border-slate-800 flex flex-col justify-end p-4 text-start hover:-translate-y-0.5">
                                <img x-show="item.img" 
                                     :src="item.img" 
                                     class="absolute inset-0 w-full h-full object-cover opacity-45 group-hover:scale-105 transition-transform duration-700 pointer-events-none" 
                                     :alt="item.title">
                                <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/60 to-transparent"></div>
                                
                                <div class="relative z-10 flex flex-col gap-1">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-[9px] font-black bg-secondary text-white px-2 py-0.5 rounded shadow-xs" 
                                              x-show="item.badge" 
                                              x-text="item.badge"></span>
                                        <span class="text-[9px] font-bold bg-white/20 text-white px-1.5 py-0.5 rounded backdrop-blur-xs">خدمت</span>
                                    </div>
                                    <h4 class="text-white text-xs sm:text-sm font-black group-hover:text-secondary transition-colors line-clamp-1" 
                                        x-text="item.title"></h4>
                                    <p class="text-[10px] text-slate-300 line-clamp-1 leading-relaxed" 
                                       x-text="item.description"></p>
                                </div>
                            </a>
                        </template>
                    </div>
                </template>

            </div>
        </div>

        <!-- Footer Action -->
        <div class="p-3.5 border-t border-slate-200 bg-white flex flex-wrap items-center justify-between gap-3 text-xs">
            <span class="text-slate-400 font-medium text-[11px]">
                راهنما: بستن با کلید <kbd class="px-1.5 py-0.5 bg-slate-100 border border-slate-300 rounded text-[10px] font-mono">Esc</kbd>
            </span>
            <a :href="'<?php echo esc_url(home_url('/')); ?>?s=' + encodeURIComponent(query) + (activeTab !== 'all' ? '&post_type=' + activeTab : '')"
               x-show="query.length >= 2"
               class="text-secondary hover:text-secondary-hover font-black flex items-center gap-1 transition-colors text-xs">
                <span>مشاهده همه نتایج در صفحه جستجو</span>
                <span>&larr;</span>
            </a>
        </div>
    </div>
</div>
