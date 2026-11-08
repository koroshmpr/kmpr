<?php
$kmpr_allowed_types = array('post', 'portfolio', 'services');
$kmpr_current_type  = isset($_GET['post_type']) ? sanitize_key(wp_unslash($_GET['post_type'])) : '';
if (!in_array($kmpr_current_type, $kmpr_allowed_types, true)) {
    $kmpr_current_type = '';
}
?>
<form class="flex items-center gap-2 w-full min-w-0 bg-slate-900/80 border border-white/10 rounded-2xl p-2 shadow-lg shadow-black/20 text-start focus-within:ring-2 focus-within:ring-secondary/30 focus-within:border-secondary/60 transition-all" 
      method="get" 
      role="search"
      action="<?php echo esc_url(home_url('/')); ?>">
    
    <div class="flex items-center gap-2 flex-1 min-w-0 px-2">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" class="text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <input type="search" 
               name="s" 
               aria-label="جستجو"
               class="w-full min-w-0 min-h-11 text-sm sm:text-base bg-transparent outline-none text-white placeholder:text-slate-400 font-bold" 
               placeholder="جستجو در مقالات، خدمات و نمونه‌کارها..." 
               value="<?= esc_attr(get_search_query()); ?>">
    </div>
    
    <select name="post_type" 
            aria-label="بخش جستجو"
            class="min-h-11 text-xs bg-slate-950/60 hover:bg-slate-950 border border-white/10 rounded-xl px-3 py-2.5 text-slate-200 outline-none focus:border-secondary/60 font-bold cursor-pointer transition-colors max-sm:hidden">
        <option value="" <?php selected($kmpr_current_type, ''); ?>>همه بخش‌ها</option>
        <option value="post" <?php selected($kmpr_current_type, 'post'); ?>>مقالات</option>
        <option value="portfolio" <?php selected($kmpr_current_type, 'portfolio'); ?>>نمونه‌کارها</option>
        <option value="services" <?php selected($kmpr_current_type, 'services'); ?>>خدمات</option>
    </select>

    <button type="submit" 
            class="min-h-11 min-w-11 justify-center whitespace-nowrap bg-secondary hover:bg-secondary-hover text-slate-950 font-black px-4 sm:px-5 py-2.5 rounded-xl cursor-pointer transition-all shadow-sm flex items-center gap-1.5 shrink-0" 
            aria-label="Submit Search">
        <span class="text-xs max-sm:hidden">جستجو</span>
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="shrink-0" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
            <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
        </svg>
    </button>
</form>
