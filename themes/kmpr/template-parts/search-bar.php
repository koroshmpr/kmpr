<form class="flex items-center gap-2 w-full bg-white border border-slate-200/80 rounded-2xl p-1.5 shadow-sm text-start" method="get" action="<?php echo esc_url(home_url('/')); ?>">
    <input type="search" name="s" class="flex-1 text-sm bg-transparent outline-none px-3 py-2 text-slate-800 placeholder:text-slate-400 font-bold" placeholder="جستجو..." value="<?= get_search_query(); ?>">
    
    <select name="post_type" class="text-xs bg-slate-50 border border-slate-200/60 rounded-xl px-3 py-2.5 text-slate-600 outline-none font-bold">
        <option value="" <?= !isset($_GET['post_type']) || $_GET['post_type'] == '' ? 'selected' : ''; ?>>همه بخش‌ها</option>
        <option value="post" <?= isset($_GET['post_type']) && $_GET['post_type'] == 'post' ? 'selected' : ''; ?>>مقاله‌ها</option>
        <option value="portfolio" <?= isset($_GET['post_type']) && $_GET['post_type'] == 'portfolio' ? 'selected' : ''; ?>>نمونه‌کارها</option>
        <option value="services" <?= isset($_GET['post_type']) && $_GET['post_type'] == 'services' ? 'selected' : ''; ?>>خدمات</option>
    </select>

    <button type="submit" class="bg-primary hover:bg-primary/95 text-white p-2.5 rounded-xl cursor-pointer transition-colors shadow-sm" aria-label="Submit Search">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
            <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
        </svg>
    </button>
</form>
