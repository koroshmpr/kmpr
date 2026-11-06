<?php
/**
 * Portfolio Card Template Part
 */
$card_class = isset($args['class']) ? $args['class'] : '';
?>
<article class="group bg-white rounded-3xl overflow-hidden shadow-sm hover:shadow-2xl hover:-translate-y-1.5 border border-slate-200/80 hover:border-secondary/30 transition-all duration-300 flex flex-col h-full text-start <?= esc_attr($card_class); ?>">
    
    <!-- Image block -->
    <a href="<?php the_permalink(); ?>" class="block relative overflow-hidden aspect-[4/3] bg-slate-100 border-b border-slate-100">
        <img class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105 pointer-events-none" 
             src="<?php echo get_the_post_thumbnail_url(get_the_ID(), 'large'); ?>" 
             alt="<?= esc_attr(get_the_title()); ?>">
        
        <!-- Brand Logo Floating Badge -->
        <?php $brand_logo = get_field('brand-logo'); ?>
        <?php if ($brand_logo) : ?>
            <div class="absolute bottom-3 end-3 bg-white/95 backdrop-blur-md border border-slate-200/80 p-2.5 rounded-2xl shadow-lg z-10 <?= get_field('logo_background'); ?>">
                <img class="size-8 object-contain pointer-events-none"
                     src="<?= esc_url($brand_logo['url']); ?>" 
                     alt="<?= esc_attr($brand_logo['title'] ?? ''); ?>">
            </div>
        <?php endif; ?>

        <!-- Action Pill on Hover (Top-Start) -->
        <div class="absolute top-3 start-3 opacity-0 group-hover:opacity-100 transition-opacity duration-300 bg-slate-900/80 backdrop-blur-md text-white text-xs font-bold px-3 py-1.5 rounded-xl border border-white/10 shadow-md">
            مشاهده پروژه
        </div>
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
            <a class="group/link inline-flex items-center gap-1.5 text-xs font-black text-secondary hover:text-secondary-hover transition-colors" href="<?php the_permalink(); ?>">
                <span>مشاهده پروژه</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5 transition-transform duration-300 group-hover/link:-translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
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
