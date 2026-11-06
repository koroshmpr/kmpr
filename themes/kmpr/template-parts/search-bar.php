<form class="flex items-center gap-2 w-full bg-white border border-slate-200/90 rounded-2xl p-2 shadow-sm text-start focus-within:ring-2 focus-within:ring-secondary/40 focus-within:border-secondary transition-all" 
      method="get" 
      action="<?php echo esc_url(home_url('/')); ?>">
    
    <div class="flex items-center gap-2 flex-1 px-2">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" class="text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <input type="search" 
               name="s" 
               class="w-full text-sm sm:text-base bg-transparent outline-none text-slate-900 placeholder:text-slate-400 font-bold" 
               placeholder="جستجو در مقالات، خدمات و نمونه‌کارها..." 
               value="<?= esc_attr(get_search_query()); ?>">
    </div>
    
    <select name="post_type" 
            class="text-xs bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-xl px-3 py-2.5 text-slate-700 outline-none font-bold cursor-pointer transition-colors max-sm:hidden">
        <option value="" <?= !isset($_GET['post_type']) || $_GET['post_type'] == '' ? 'selected' : ''; ?>>همه بخش‌ها</option>
        <option value="post" <?= isset($_GET['post_type']) && $_GET['post_type'] == 'post' ? 'selected' : ''; ?>>مقالات</option>
        <option value="portfolio" <?= isset($_GET['post_type']) && $_GET['post_type'] == 'portfolio' ? 'selected' : ''; ?>>نمونه‌کارها</option>
        <option value="services" <?= isset($_GET['post_type']) && $_GET['post_type'] == 'services' ? 'selected' : ''; ?>>خدمات</option>
    </select>

    <button type="submit" 
            class="bg-primary hover:bg-slate-800 text-white font-bold px-4 py-2.5 rounded-xl cursor-pointer transition-all shadow-sm flex items-center gap-1.5 shrink-0" 
            aria-label="Submit Search">
        <span class="text-xs max-sm:hidden">جستجو</span>
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
            <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
        </svg>
    </button>
</form>
