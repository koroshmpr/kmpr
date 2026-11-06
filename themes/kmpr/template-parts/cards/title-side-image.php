<?php
/**
 * Post Card: Title side image
 */
?>
<article class="group w-full bg-white border border-slate-100 hover:border-slate-200 rounded-2xl p-3 hover:shadow-sm transition-all duration-300 text-right">
    <a class="flex items-center gap-4" href="<?php the_permalink(); ?>">
        <!-- Image thumbnail -->
        <img class="size-16 rounded-xl object-cover pointer-events-none transition-transform duration-500 group-hover:scale-105 shrink-0 bg-slate-50" 
             src="<?php echo get_the_post_thumbnail_url(get_the_ID(), 'thumbnail'); ?>" 
             alt="<?= esc_attr(get_the_title()); ?>">
        
        <!-- Meta Details -->
        <div class="flex flex-col gap-1 flex-1 overflow-hidden">
            <div class="flex flex-row-reverse justify-end items-center gap-2 text-xs">
                <!-- Categories -->
                <span class="font-extrabold text-secondary">
                    <?php
                    $categories = get_the_category();
                    if (!empty($categories)) {
                        echo esc_html($categories[0]->name);
                    }
                    ?>
                </span>
                
                <span class="text-slate-300">|</span>
                
                <!-- Date -->
                <span class="text-slate-400 flex items-center gap-1">
                    <?php get_template_part('template-parts/svg/clock'); ?>
                    <span><?= get_the_date('d F Y'); ?></span>
                </span>
            </div>
            
            <h4 class="text-sm font-bold text-slate-800 group-hover:text-secondary transition-colors duration-300 truncate leading-snug mt-1">
                <?= get_the_title(); ?>
            </h4>
        </div>
    </a>
</article>