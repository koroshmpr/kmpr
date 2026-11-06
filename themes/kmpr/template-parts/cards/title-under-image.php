<?php
/**
 * Blog Post Card (Title under image)
 */
?>
<article class="group bg-white rounded-3xl overflow-hidden shadow-sm hover:shadow-xl hover:-translate-y-1 border border-slate-200/80 transition-all duration-300 flex flex-col h-full text-start">
    <!-- Image block -->
    <a href="<?php the_permalink(); ?>" class="block relative overflow-hidden aspect-[16/10] bg-slate-100">
        <img class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105 pointer-events-none" 
             src="<?php echo get_the_post_thumbnail_url(get_the_ID(), 'large'); ?>" 
             alt="<?= esc_attr(get_the_title()); ?>">
        
        <!-- Category Badge -->
        <span class="absolute bottom-3 end-3 bg-primary/90 backdrop-blur-xs text-white text-xs font-bold px-3 py-1.5 rounded-xl shadow-sm z-10">
            <?php
            $categories = get_the_category();
            if (!empty($categories)) {
                echo esc_html($categories[0]->name);
            } else {
                echo 'مقاله';
            }
            ?>
        </span>
    </a>

    <!-- Details block -->
    <div class="p-6 flex flex-col flex-1 gap-2.5">
        <h3 class="text-base sm:text-lg font-black text-slate-900 group-hover:text-secondary transition-colors duration-300 leading-snug">
            <a href="<?php the_permalink(); ?>">
                <?= get_the_title(); ?>
            </a>
        </h3>
        
        <p class="text-slate-500 text-xs sm:text-sm leading-relaxed flex-1">
            <?= esc_html(wp_strip_all_tags(wp_trim_words(get_the_excerpt() ?: get_the_content(), 18, '...'))); ?>
        </p>
        
        <!-- Footer info -->
        <div class="flex items-center justify-between border-t border-slate-100 pt-4 mt-2 text-xs text-slate-400 font-bold">
            <span><?php echo get_the_date(); ?></span>
            <div class="flex items-center gap-1.5 text-slate-500">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                <span><?= get_post_meta(get_the_ID(), 'post_views', true) ?: '0'; ?></span>
            </div>
        </div>
    </div>
</article>
