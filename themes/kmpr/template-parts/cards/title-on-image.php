<?php
/**
 * Post Card: Title on image
 */
?>
<article class="group relative rounded-3xl overflow-hidden aspect-[4/3] shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 bg-slate-900 border border-slate-800">
    <!-- Image -->
    <img class="absolute inset-0 w-full h-full object-cover transition-transform duration-700 group-hover:scale-105 pointer-events-none opacity-80" 
         src="<?php echo get_the_post_thumbnail_url(get_the_ID(), 'large'); ?>" 
         alt="<?= esc_attr(get_the_title()); ?>">
    
    <!-- Gradient Overlay -->
    <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/40 to-transparent"></div>

    <!-- Category Floating Badge -->
    <div class="absolute top-4 end-4 z-10">
        <?php get_template_part('template-parts/cards/post-detail/category-position-absolute'); ?>
    </div>

    <!-- Details overlay bottom -->
    <a class="absolute inset-x-0 bottom-0 p-5 bg-slate-950/70 backdrop-blur-sm border-t border-white/10 flex flex-col justify-end text-start z-10"
       href="<?php the_permalink(); ?>">
        <h4 class="text-white text-base font-black truncate group-hover:text-secondary transition-colors duration-200">
            <?= get_the_title(); ?>
        </h4>
        <p class="text-slate-300 text-xs truncate mt-1 font-medium">
            <?= esc_html(wp_strip_all_tags(wp_trim_words(get_the_excerpt() ?: get_the_content(), 10, '...'))); ?>
        </p>
    </a>
</article>
