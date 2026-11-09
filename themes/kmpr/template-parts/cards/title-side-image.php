<?php
/**
 * Post Card: Title side image - Dark Obsidian Theme
 */
?>
<article class="group w-full bg-slate-900/70 border border-white/10 hover:border-secondary/40 rounded-2xl p-3 hover:shadow-lg transition-all duration-300 text-start backdrop-blur-md">
    <a class="flex items-center gap-3.5" href="<?php the_permalink(); ?>">
        <!-- Image thumbnail -->
        <div class="size-16 rounded-xl overflow-hidden shrink-0 bg-slate-950 border border-white/10 relative">
            <img class="w-full h-full object-cover pointer-events-none transition-transform duration-500 group-hover:scale-105" 
                 src="<?php echo get_the_post_thumbnail_url(get_the_ID(), 'thumbnail') ?: kmpr_default_cover_url('thumbnail'); ?>" 
                 alt="<?= esc_attr(get_the_title()); ?>"
                 loading="lazy">
        </div>
        
        <!-- Meta Details -->
        <div class="flex flex-col gap-1 flex-1 overflow-hidden">
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
                
                <span class="text-slate-600">•</span>
                
                <!-- Date -->
                <span class="text-slate-400 font-medium">
                    <?= shamsi_date('d F Y', get_the_time('U')); ?>
                </span>
            </div>
            
            <h4 class="text-xs sm:text-sm font-black text-white group-hover:text-secondary transition-colors duration-300 truncate leading-snug">
                <?= get_the_title(); ?>
            </h4>
        </div>
    </a>
</article>
