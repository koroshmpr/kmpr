<?php if( have_rows('social-media') ): ?>
    <?php while( have_rows('social-media') ): the_row();?>
        <ul class="social-media flex items-center gap-2 m-0 <?= $args['class'] ?? 'justify-center lg:justify-start';?>">
            <?php if (get_sub_field('portfolio-instagram')) : ?>
                <li>
                    <a class="flex items-center justify-center size-8 rounded-full border border-white/10 bg-white/[0.04] text-slate-300 hover:bg-secondary hover:text-white hover:border-transparent transition-all duration-300" 
                       href="<?= esc_url(get_sub_field('portfolio-instagram')['url']); ?>"
                       aria-label="instagram" target="_blank" rel="noopener noreferrer">
                        <?php get_template_part('template-parts/svg/social/instagram', null, $args); ?>
                    </a>
                </li>
            <?php endif; ?>
            <?php if (get_sub_field('portfolio-youtube')) : ?>
                <li>
                    <a class="flex items-center justify-center size-8 rounded-full border border-white/10 bg-white/[0.04] text-slate-300 hover:bg-secondary hover:text-white hover:border-transparent transition-all duration-300" 
                       href="<?= esc_url(get_sub_field('portfolio-youtube')['url']); ?>"
                       aria-label="youtube" target="_blank" rel="noopener noreferrer">
                        <?php get_template_part('template-parts/svg/social/youtube', null, $args); ?>
                    </a>
                </li>
            <?php endif; ?>
            <?php if (get_sub_field('portfolio-twitter')) : ?>
                <li>
                    <a class="flex items-center justify-center size-8 rounded-full border border-white/10 bg-white/[0.04] text-slate-300 hover:bg-secondary hover:text-white hover:border-transparent transition-all duration-300" 
                       href="<?= esc_url(get_sub_field('portfolio-twitter')['url']); ?>"
                       aria-label="twitter" target="_blank" rel="noopener noreferrer">
                        <?php get_template_part('template-parts/svg/social/twitter', null, $args); ?>
                    </a>
                </li>
            <?php endif; ?>
            <?php if (get_sub_field('portfolio-aparat')) : ?>
                <li>
                    <a class="flex items-center justify-center size-8 rounded-full border border-white/10 bg-white/[0.04] text-slate-300 hover:bg-secondary hover:text-white hover:border-transparent transition-all duration-300" 
                       href="<?= esc_url(get_sub_field('portfolio-aparat')['url']); ?>"
                       aria-label="aparat" target="_blank" rel="noopener noreferrer">
                        <?php get_template_part('template-parts/svg/social/aparat', null, $args); ?>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    <?php endwhile; ?>
<?php endif; ?>
