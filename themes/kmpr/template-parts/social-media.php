<ul class="social-media flex items-center gap-2 m-0 <?= $args['class'] ?? 'justify-center lg:justify-start';?>">
    <?php if( get_field('instagram', 'option') ) : ?>
        <li>
            <a class="flex items-center justify-center size-9 rounded-full border border-white/10 bg-white/5 text-slate-300 hover:bg-secondary hover:text-white hover:border-transparent transition-all duration-300" 
               href="<?= esc_url(get_field('instagram', 'option')); ?>"
               aria-label="instagram" target="_blank">
                <?php get_template_part('template-parts/svg/social/instagram', null, $args); ?>
            </a>
        </li>
    <?php endif; ?>
    <?php if( get_field('telegram', 'option') ) : ?>
        <li>
            <a class="flex items-center justify-center size-9 rounded-full border border-white/10 bg-white/5 text-slate-300 hover:bg-secondary hover:text-white hover:border-transparent transition-all duration-300" 
               href="<?= esc_url(get_field('telegram', 'option')); ?>"
               aria-label="telegram" target="_blank">
                <?php get_template_part('template-parts/svg/social/telegram', null, $args); ?>
            </a>
        </li>
    <?php endif; ?>
    <?php if( get_field('whatsapp', 'option') ) : ?>
        <li>
            <a class="flex items-center justify-center size-9 rounded-full border border-white/10 bg-white/5 text-slate-300 hover:bg-secondary hover:text-white hover:border-transparent transition-all duration-300" 
               href="<?= esc_url(get_field('whatsapp', 'option')); ?>"
               aria-label="whatsapp" target="_blank">
                <?php get_template_part('template-parts/svg/social/whatsapp', null, $args); ?>
            </a>
        </li>
    <?php endif; ?>
    <?php if( get_field('aparat', 'option') ) : ?>
        <li>
            <a class="flex items-center justify-center size-9 rounded-full border border-white/10 bg-white/5 text-slate-300 hover:bg-secondary hover:text-white hover:border-transparent transition-all duration-300" 
               href="<?= esc_url(get_field('aparat', 'option')); ?>"
               aria-label="aparat" target="_blank">
                <?php get_template_part('template-parts/svg/social/aparat', null, $args); ?>
            </a>
        </li>
    <?php endif; ?>
    <?php if( get_field('youtube', 'option') ) : ?>
        <li>
            <a class="flex items-center justify-center size-9 rounded-full border border-white/10 bg-white/5 text-slate-300 hover:bg-secondary hover:text-white hover:border-transparent transition-all duration-300" 
               href="<?= esc_url(get_field('youtube', 'option')); ?>"
               aria-label="youtube" target="_blank">
                <?php get_template_part('template-parts/svg/social/youtube', null, $args); ?>
            </a>
        </li>
    <?php endif; ?>
    <?php if( get_field('twitter', 'option') ) : ?>
        <li>
            <a class="flex items-center justify-center size-9 rounded-full border border-white/10 bg-white/5 text-slate-300 hover:bg-secondary hover:text-white hover:border-transparent transition-all duration-300" 
               href="<?= esc_url(get_field('twitter', 'option')); ?>"
               aria-label="twitter" target="_blank">
                <?php get_template_part('template-parts/svg/social/twitter', null, $args); ?>
            </a>
        </li>
    <?php endif; ?>
    <?php if( get_field('linkedin', 'option') ) : ?>
        <li>
            <a class="flex items-center justify-center size-9 rounded-full border border-white/10 bg-white/5 text-slate-300 hover:bg-secondary hover:text-white hover:border-transparent transition-all duration-300" 
               href="<?= esc_url(get_field('linkedin', 'option')); ?>"
               aria-label="linkedin" target="_blank">
                <?php get_template_part('template-parts/svg/social/linkedin', null, $args); ?>
            </a>
        </li>
    <?php endif; ?>
    <?php if( get_field('github', 'option') ) : ?>
        <li>
            <a class="flex items-center justify-center size-9 rounded-full border border-white/10 bg-white/5 text-slate-300 hover:bg-secondary hover:text-white hover:border-transparent transition-all duration-300" 
               href="<?= esc_url(get_field('github', 'option')); ?>"
               aria-label="github" target="_blank">
                <?php get_template_part('template-parts/svg/social/github', null, $args); ?>
            </a>
        </li>
    <?php endif; ?>
</ul>
