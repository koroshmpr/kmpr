<?php
/**
 * Portfolio Card Template Part
 */
$card_class = isset($args['class']) ? $args['class'] : '';
?>
<article class="group bg-white rounded-3xl overflow-hidden shadow-sm hover:shadow-xl hover:-translate-y-1 border border-slate-200/80 transition-all duration-300 flex flex-col h-full text-start <?= esc_attr($card_class); ?>">
    
    <!-- Image block -->
    <a href="<?php the_permalink(); ?>" class="block relative overflow-hidden aspect-[4/3] bg-slate-100 border-b border-slate-100">
        <img class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105 pointer-events-none" 
             src="<?php echo get_the_post_thumbnail_url(get_the_ID(), 'large'); ?>" 
             alt="<?= esc_attr(get_the_title()); ?>">
        
        <!-- Brand Logo Floating Badge -->
        <?php $brand_logo = get_field('brand-logo'); ?>
        <?php if ($brand_logo) : ?>
            <div class="absolute bottom-3 end-3 bg-white/95 backdrop-blur-xs border border-slate-200 p-2 rounded-2xl shadow-md z-10 <?= get_field('logo_background'); ?>">
                <img class="size-9 object-contain pointer-events-none"
                     src="<?= esc_url($brand_logo['url']); ?>" 
                     alt="<?= esc_attr($brand_logo['title'] ?? ''); ?>">
            </div>
        <?php endif; ?>
    </a>

    <!-- Details block -->
    <div class="p-6 flex flex-col flex-1 gap-2.5">
        <h3 class="text-base sm:text-lg font-black text-slate-900 group-hover:text-secondary transition-colors duration-300 leading-snug">
            <a href="<?php the_permalink(); ?>">
                <?= get_the_title(); ?>
            </a>
        </h3>
        
        <p class="text-slate-500 text-xs sm:text-sm leading-relaxed flex-1">
            <?= esc_html(wp_strip_all_tags(wp_trim_words(get_the_excerpt() ?: get_the_content(), 15, '...'))); ?>
        </p>

        <!-- Card Footer -->
        <div class="flex items-center justify-between border-t border-slate-100 pt-4 mt-2">
            <!-- Read More Link -->
            <a class="flex items-center gap-1.5 text-xs font-black text-secondary hover:text-secondary-hover transition-colors" href="<?php the_permalink(); ?>">
                <span>مشاهده پروژه</span>
                <span>&larr;</span>
            </a>

            <!-- Client Social Media Links -->
            <div class="flex gap-2">
                <?php
                get_template_part('template-parts/portfolio/social-media', null, array(
                    'class'    => 'flex gap-2 justify-end',
                    'sizeSvgX' => '16',
                    'sizeSvgY' => '16',
                ));
                ?>
            </div>
        </div>
    </div>
</article>
