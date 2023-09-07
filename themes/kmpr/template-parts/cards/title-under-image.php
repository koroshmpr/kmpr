<article class=" overflow-hidden rounded-2 p-2">
    <div class="position-relative card__title-under-image bg-primary px-2 shadow-sm">
        <a class="row rounded-2 d-flex justify-content-center" href="<?php the_permalink(); ?>">
            <img class="post-cover object-fit px-1" src="<?php echo the_post_thumbnail_url(); ?>"
                 alt="<?= get_the_title(); ?>">
            <span class="category-badge bg-primary border border-2 border-white fw-bold text-shadow text-white shadow-sm  py-2 px-3 small">
                     <?php
                     $category_detail = get_the_category($post->ID);
                     $category_count = count($category_detail);
                     $i = 0;
                     foreach ($category_detail as $category) {
                         echo $category->name;
                         if (++$i !== $category_count) {
                             echo ' - ';
                         }
                     }
                     ?>
            </span>
            <div>
                <div class="px-3 text-white">
                    <h3 class="fs-5 fw-bold"> <?= get_the_title(); ?></h3>
                    <p class="fs-6"><?= wp_trim_words(get_the_content(), 18); ?></p>
                </div>
            </div>
        </a>
    </div>
</article>