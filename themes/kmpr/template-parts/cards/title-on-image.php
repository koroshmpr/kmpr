<?php
/**
 * Post Card: Title on image
 */
?>
<article class="group relative rounded-2xl overflow-hidden aspect-[4/3] shadow-sm hover:shadow-md transition-all duration-300 bg-slate-900 border border-slate-200/40">
    <!-- Image -->
    <img class="absolute inset-0 w-full h-full object-cover transition-transform duration-700 group-hover:scale-105 pointer-events-none opacity-90" 
         src="<?php echo get_the_post_thumbnail_url(get_the_ID(), 'large'); ?>" 
         alt="<?= esc_attr(get_the_title()); ?>">
    
    <!-- Gradient Overlay -->
    <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-900/30 to-transparent"></div>

    <!-- Category Floating Badge -->
    <div class="absolute top-4 right-4 z-10">
        <?php get_template_part('template-parts/cards/post-detail/category-position-absolute'); ?>
    </div>

    <!-- Details overlay bottom -->
    <a class="absolute inset-x-0 bottom-0 p-4 bg-slate-900/60 backdrop-blur-sm border-t border-white/10 flex flex-col justify-end text-right z-10"
       href="<?php the_permalink(); ?>">
        <p class="text-white text-sm font-bold truncate"> <?= get_the_title(); ?></p>
        <h6 class="text-slate-300 text-xs truncate mt-1"><?= wp_trim_words(get_the_content(), 8); ?></h6>
    </a>
</article>