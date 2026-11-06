<?php if( have_rows('social-media') ): ?>
    <?php while( have_rows('social-media') ): the_row();?>
        <ul class="social-media flex items-center gap-3 m-0 <?= $args['class'] ?? 'justify-center lg:justify-start';?>">
            <?php if (get_sub_field('portfolio-instagram')) : ?>
                <li>
                    <a class="flex items-center justify-center p-2 rounded-full border border-slate-200 text-slate-600 hover:bg-primary hover:text-white hover:border-transparent transition-all duration-300" 
                       href="<?= esc_url(get_sub_field('portfolio-instagram')['url']); ?>"
                       aria-label="instagram">
                        <?php get_template_part('template-parts/svg/social/instagram', null, $args); ?>
                    </a>
                </li>
            <?php endif; ?>
            <?php if (get_sub_field('portfolio-youtube')) : ?>
                <li>
                    <a class="flex items-center justify-center p-2 rounded-full border border-slate-200 text-slate-600 hover:bg-primary hover:text-white hover:border-transparent transition-all duration-300" 
                       href="<?= esc_url(get_sub_field('portfolio-youtube')['url']); ?>"
                       aria-label="youtube">
                        <?php get_template_part('template-parts/svg/social/youtube', null, $args); ?>
                    </a>
                </li>
            <?php endif; ?>
            <?php if (get_sub_field('portfolio-twitter')) : ?>
                <li>
                    <a class="flex items-center justify-center p-2 rounded-full border border-slate-200 text-slate-600 hover:bg-primary hover:text-white hover:border-transparent transition-all duration-300" 
                       href="<?= esc_url(get_sub_field('portfolio-twitter')['url']); ?>"
                       aria-label="twitter">
                        <?php get_template_part('template-parts/svg/social/twitter', null, $args); ?>
                    </a>
                </li>
            <?php endif; ?>
            <?php if (get_sub_field('portfolio-aparat')) : ?>
                <li>
                    <a class="flex items-center justify-center p-2 rounded-full border border-slate-200 text-slate-600 hover:bg-primary hover:text-white hover:border-transparent transition-all duration-300" 
                       href="<?= esc_url(get_sub_field('portfolio-aparat')['url']); ?>"
                       aria-label="aparat">
                        <?php get_template_part('template-parts/svg/social/aparat', null, $args); ?>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    <?php endwhile; ?>
<?php endif; ?>