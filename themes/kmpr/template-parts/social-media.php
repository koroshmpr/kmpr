<ul class="social-media d-flex list-unstyled gap-1 m-0 align-items-center
 <?= $args['class'] ?? 'justify-content-center justify-content-lg-start';?> ">
    <?php if( get_field('instagram', 'option') )  {; ?>
    <li><a class="rounded-circle fs-3" href="<?= get_field('instagram', 'option'); ?>"
           aria-label="instagram">
            <?php get_template_part('template-parts/svg/social/instagram', null, $args); ?></a></li>
    <?php }; if( get_field('telegram', 'option') )  {; ?>
    <li><a class="rounded-circle fs-3" href="<?= get_field('telegram', 'option'); ?>"
           aria-label="telegram">
            <?php get_template_part('template-parts/svg/social/telegram', null, $args); ?></a></li>
    <?php }; if( get_field('whatsapp', 'option') )  {; ?>
    <li><a class="rounded-circle fs-3" href="<?= get_field('whatsapp', 'option'); ?>"
           aria-label="telegram">
            <?php get_template_part('template-parts/svg/social/whatsapp', null, $args); ?></a></li>
    <?php } if( get_field('aparat', 'option') )  {; ?>
    <li><a class="rounded-circle fs-3" href="<?= get_field('aparat', 'option'); ?>"
           aria-label="aparat">
            <?php get_template_part('template-parts/svg/social/aparat', null, $args); ?></a></li>
    <?php } if( get_field('youtube', 'option') )  {; ?>
        <li><a class="rounded-circle fs-3" href="<?= get_field('youtube','option'); ?>"
            aria-label="youtube">
                <?php get_template_part('template-parts/svg/social/youtube');?></a></li>
    <?php } if( get_field('twitter', 'option') )  {; ?>
    <li><a class="rounded-circle fs-3" href="<?= get_field('twitter','option'); ?>"
            aria-label="twitter">
                <?php get_template_part('template-parts/svg/social/twitter');?></a></li>
    <?php } if( get_field('linkedin', 'option') )  {; ?>
    <li><a class="rounded-circle fs-3" href="<?= get_field('linkedin','option'); ?>"
            aria-label="linkedin">
                <?php get_template_part('template-parts/svg/social/linkedin');?></a></li>
    <?php }; ?>
</ul>
