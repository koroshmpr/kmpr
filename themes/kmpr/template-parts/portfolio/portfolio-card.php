<?php
/**
 * Portfolio Card Template Part
 */
$card_class = isset($args['class']) ? $args['class'] : '';
?>
<article class="group bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-md border border-slate-100/80 transition-all duration-300 flex flex-col h-full text-start <?= esc_attr($card_class); ?>">
    
    <!-- Image block -->
    <a href="<?php the_permalink(); ?>" class="block relative overflow-hidden aspect-[4/3] bg-slate-50 border-b border-slate-100">
        <img class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105 pointer-events-none" 
             src="<?php echo get_the_post_thumbnail_url(get_the_ID(), 'large'); ?>" 
             alt="<?= esc_attr(get_the_title()); ?>">
        
        <!-- Brand Logo Floating Badge -->
        <?php $brand_logo = get_field('brand-logo'); ?>
        <?php if ($brand_logo) : ?>
            <div class="absolute bottom-4 end-4 bg-white border border-slate-200/80 p-1.5 rounded-xl shadow-md z-10 <?= get_field('logo_background'); ?>">
                <img class="size-10 object-contain pointer-events-none"
                     src="<?= esc_url($brand_logo['url']); ?>" 
                     alt="<?= esc_attr($brand_logo['title']); ?>">
            </div>
        <?php endif; ?>
    </a>

    <!-- Details block -->
    <div class="p-6 flex flex-col flex-1 gap-2">
        <h3 class="text-lg font-bold text-slate-800 group-hover:text-secondary transition-colors duration-300 leading-snug">
            <a href="<?php the_permalink(); ?>">
                <?= get_the_title(); ?>
            </a>
        </h3>
        
        <p class="text-slate-500 text-sm leading-relaxed mt-1 flex-1">
            <?= esc_html(wp_trim_words(get_the_content(), 15)); ?>
        </p>

        <!-- Card Footer -->
        <div class="flex flex-row-reverse items-center justify-between border-t border-slate-100 pt-4 mt-4">
            <!-- Client Social Media Links -->
            <div class="flex gap-2">
                <?php
                get_template_part('template-parts/portfolio/social-media', null, array(
                    'class' => 'flex gap-2 justify-end',
                    'sizeSvgX' => '16',
                    'sizeSvgY' => '16',
                ));
                ?>
            </div>

            <!-- Read More Button -->
            <a class="flex items-center gap-1.5 text-xs font-bold text-secondary hover:text-secondary/80 transition-colors" href="<?php the_permalink(); ?>">
                <span>مشاهده پروژه</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
        </div>
    </div>
</article>