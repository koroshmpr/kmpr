<?php
/**
 * Services Card template part
 */
?>
<article class="group relative rounded-3xl overflow-hidden aspect-[4/3] shadow-md hover:shadow-2xl hover:-translate-y-1.5 transition-all duration-500 bg-slate-900 border border-slate-800">
    <!-- Background Image -->
    <img class="absolute inset-0 w-full h-full object-cover transition-transform duration-1000 group-hover:scale-105 opacity-60 pointer-events-none" 
         src="<?php echo get_the_post_thumbnail_url(get_the_ID(), 'large'); ?>" 
         alt="<?= esc_attr(get_the_title()); ?>">

    <!-- Gradient Overlay -->
    <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/60 to-slate-950/20 opacity-90 transition-opacity duration-300 group-hover:opacity-95"></div>

    <!-- Floating Service Icon (Top-Start) -->
    <?php $icon = get_field('service-icon'); ?>
    <?php if ($icon) : ?>
        <div class="absolute top-4 start-4 bg-slate-900/80 backdrop-blur-md border border-white/10 p-3 rounded-2xl text-secondary shadow-lg z-10 transition-transform duration-500 group-hover:scale-110 [&_svg]:size-6">
            <?= $icon; ?>
        </div>
    <?php endif; ?>

    <!-- Content Link -->
    <a class="absolute inset-0 flex flex-col justify-end p-6 z-10 text-start" href="<?php the_permalink(); ?>">
        <h3 class="text-white text-lg lg:text-xl font-black mb-2 tracking-wide group-hover:text-secondary transition-colors duration-300"><?= get_the_title(); ?></h3>
        
        <div class="flex items-center gap-2 mt-1">
            <?php if (get_field('services_badge')) : ?>
                <span class="text-[11px] font-black bg-secondary text-white px-2.5 py-1 rounded-lg shadow-xs">
                    <?= esc_html(get_field('services_badge')); ?>
                </span>
            <?php endif; ?>
            
            <?php if (get_field('services_description')) : ?>
                <span class="text-[11px] font-bold bg-white/15 backdrop-blur-md text-slate-200 px-2.5 py-1 rounded-lg border border-white/10 truncate max-w-[200px]">
                    <?= esc_html(get_field('services_description')); ?>
                </span>
            <?php endif; ?>
        </div>
    </a>
</article>
