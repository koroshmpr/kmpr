<article class="card__title-side-image">
    <a class="row gap-2 align-items-center" href="<?php the_permalink(); ?>">
        <img class="col-2 rounded p-0 object-fit" src="<?php echo the_post_thumbnail_url(); ?>"
             alt="<?= get_the_title(); ?>">
        <div class="row gap-2 col">
            <div class="d-flex gap-2 align-items-center px-0">
                <?php
                $category_detail = get_the_category($post->ID); ?>
                <h5 class="fs-6 fw-bold mb-0">
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
                <div class="vr"></div>
                <span class="fw-normal fs-6 d-flex gap-1 align-items-center">
                  <?php get_template_part('template-parts/svg/clock');
                  echo get_the_date('d  F , Y'); ?>
                </span>
            </div>
            <p class="fs-6 mb-0 text-dark px-0"><?= get_the_title(); ?></p>
        </div>
    </a>
</article>