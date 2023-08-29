<article class="rounded card__title-on-image post-card object-fit position-relative overflow-hidden border border-1 border-primary"
         style="background-image: url('<?php echo the_post_thumbnail_url(); ?>')">
    <a class="d-flex info_card h-100 flex-column justify-content-start align-items-start position-relative"
       href="<?php the_permalink(); ?>">
        <?php get_template_part('template-parts/cards/post-detail/category-position-absolute'); ?>
        <div class="p-3 bd-blur bg-primary bg-opacity-25 w-100">
            <p class="text-white fs-6"> <?= get_the_title(); ?></p>
            <h6 class="text-white small"><?= wp_trim_words(get_the_content(), 8); ?></h6>
        </div>
    </a>
</article>