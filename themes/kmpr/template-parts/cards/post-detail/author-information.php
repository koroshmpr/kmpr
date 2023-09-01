    <div id="comment-section" class="mx-2 my-4 bg-secondary bg-opacity-25 text-center rounded-2 shadow-sm p-lg-5 p-md-3 p-4 row align-items-center justify-content-lg-start">
        <div class="col-12 col-lg-2 py-2 py-md-0">
            <?php
                $sizeSvgX = '90';
                $sizeSvgY = '90';
                $args = array(
                    'sizeSvgY' => $sizeSvgY,
                    'sizeSvgX' => $sizeSvgX
                );
                get_template_part('template-parts/cards/post-detail/author-image', null, $args);?>
        </div>
        <div class="col-12 col-lg-10">
            <h5 class="text-lg-start fs-3 fw-bold"><?= get_the_author_meta('nickname', get_queried_object()->post_author); ?></h5>
            <p class="text-lg-start">
                <?= get_the_author_meta('description', get_queried_object()->post_author); ?>
            </p>
        </div>
    </div>