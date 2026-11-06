<?php
/**
 * Post Card: Title side image
 */
?>
<article class="group w-full bg-white border border-slate-200/80 hover:border-slate-300 rounded-2xl p-3.5 hover:shadow-md transition-all duration-300 text-start">
    <a class="flex items-center gap-4" href="<?php the_permalink(); ?>">
        <!-- Image thumbnail -->
        <img class="size-18 rounded-xl object-cover pointer-events-none transition-transform duration-500 group-hover:scale-105 shrink-0 bg-slate-100 border border-slate-100" 
             src="<?php echo get_the_post_thumbnail_url(get_the_ID(), 'thumbnail'); ?>" 
             alt="<?= esc_attr(get_the_title()); ?>">
        
        <!-- Meta Details -->
        <div class="flex flex-col gap-1.5 flex-1 overflow-hidden">
            <div class="flex items-center gap-2 text-xs">
                <!-- Categories -->
                <span class="font-black text-secondary">
                    <?php
                    $categories = get_the_category();
                    if (!empty($categories)) {
                        echo esc_html($categories[0]->name);
                    }
                    ?>
                </span>
                
                <span class="text-slate-300">•</span>
                
                <!-- Date -->
                <span class="text-slate-400 font-bold flex items-center gap-1">
                    <span><?= get_the_date('d F Y'); ?></span>
                </span>
            </div>
            
            <h4 class="text-sm font-black text-slate-800 group-hover:text-secondary transition-colors duration-300 truncate leading-snug">
                <?= get_the_title(); ?>
            </h4>
        </div>
    </a>
</article>
