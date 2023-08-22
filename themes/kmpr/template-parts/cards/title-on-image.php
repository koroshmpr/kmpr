<article class="rounded card__title-on-image post-card object-fit position-relative overflow-hidden" style="background-image: url('<?php echo the_post_thumbnail_url(); ?>')">
    <a class="d-flex info_card h-100 flex-column justify-content-between p-3 align-items-start" href="<?php the_permalink(); ?>">
        <div class="col-11 d-flex gap-2 align-items-center justify-content-start">
            <?php get_template_part('template-parts/cards/post-detail/author-image');?>
            <div  class="info-part">
                <div class="fs-6 text-center">
                    ارسال توسط
                    <span class="fw-bold">
                <?php echo get_the_author_meta('display_name', $post->post_author); ?>
                </span>
                </div>
                <div class="fw-normal fs-6">
                    <?php echo get_the_date('d  F , Y'); ?>
                </div>
            </div>

        </div>
            <div>
                <div class="px-2">
                    <?php
                    $category_detail = get_the_category($post->ID); ?>
                    <h5 class="fs-6 fw-bold mb-0 text-white">
                        <?php
                        $category_count = count($category_detail);
                        $i = 0;
                        foreach ($category_detail as $category) {
                            echo $category->name;
                            if (++$i !== $category_count) {
                                echo ' - ';
                            }
                        }
                        ?>
                    </h5>
                    <p class="text-white fs-6"> <?= get_the_title(); ?></p>
                    <h6 class="text-white small"><?= wp_trim_words(get_the_content(), 8); ?></h6>
                </div>
            </div>
    </a>
</article>