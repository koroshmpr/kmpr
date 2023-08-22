<?php if( have_rows('social-media') ): ?>
<?php while( have_rows('social-media') ): the_row();?>
<ul class="social-media d-flex list-unstyled  m-0 align-items-center
<?= $args['class'] ?? 'justify-content-center justify-content-lg-start';?>">
    <?php if (get_sub_field('portfolio-instagram')) {; ?>
    <li><a class="rounded-circle fs-3" href="<?= get_sub_field('portfolio-instagram')['url']; ?>"
           aria-label="instagram">
            <?php get_template_part('template-parts/svg/social/instagram', null, $args); ?></a></li>
            <?php }; if (get_sub_field('portfolio-youtube')) {; ?>
    <li><a class="rounded-circle fs-3" href="<?= get_sub_field('portfolio-youtube')['url']; ?>"
           aria-label="youtube">
            <?php get_template_part('template-parts/svg/social/youtube', null, $args); ?></a></li>
        <?php }; if (get_sub_field('portfolio-twitter')) {; ?>
            <li><a class="rounded-circle fs-3" href="<?= get_sub_field('portfolio-twitter')['url'] ?>"
           aria-label="twitter">
            <?php get_template_part('template-parts/svg/social/twitter', null, $args); ?></a></li>
        <?php }; if (get_sub_field('portfolio-aparat')) {; ?>
            <li><a class="rounded-circle fs-3" href="<?= get_sub_field('portfolio-aparat')['url'] ?>"
           aria-label="aparat">
            <?php get_template_part('template-parts/svg/social/aparat', null, $args); ?></a></li>
        <?php }; ?>
            </ul>
    <?php endwhile; ?>
<?php endif; ?>