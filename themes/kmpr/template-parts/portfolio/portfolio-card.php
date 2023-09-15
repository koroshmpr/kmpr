<article class="h-100 <?= $args['class'] ?? ''; ?>">
    <div class="bg-primary rounded-3 overflow-hidden h-100">
        <a class="row rounded mx-0" href="<?php the_permalink(); ?>">
            <img width="350" height="300" class="post-cover object-fit border-5 border-bottom border-secondary px-0" src="<?php echo the_post_thumbnail_url(); ?>"
                 alt="<?= get_the_title(); ?>">
            <div class="mt-3 px-4 text-white">
                <div class="d-flex align-items-center justify-content-start gap-4">
                    <img class="object-fit  border border-1 shadow-sm border-white border-opacity-25 p-1 rounded-1 <?= get_field('logo_background')?>"
                         width="50" height="50"
                         src="<?= get_field('brand-logo')['url']; ?>"
                         alt="<?= get_field('brand-logo')['title']; ?>">
                    <p class="fs-5 fw-bold m-0">
                        <?= get_the_title(); ?></p>
                </div>

                <p class="fs-6 mb-0 mt-3 portrolio-card__content"><?= wp_trim_words(get_the_content(), 16); ?></p>
            </div>
        </a>
        <div class="d-flex justify-content-between <?= $args['footerContainer'] ?? ''; ?> ps-4 pt-3">
            <div class="my-2">
                <?php
                $sizeSvgX = '20';
                $sizeSvgY = '20';
                $class= "fill-secondary";
                $colorSvg = '#FE0000';
                $args = array(
                    'sizeSvgX' => $sizeSvgX,
                    'class' => $class,
                    'sizeSvgY' => $sizeSvgY,
                    'colorSvg' => $colorSvg
                );
                get_template_part('template-parts/portfolio/social-media', null, $args); ?>
            </div>
            <a class="btn bg-secondary <?= $args['buttonCard'] ?? ''; ?> rounded-end-0 rounded-start-pill text-primary fw-bold border-0 border-5 border-start border-bottom border-primary border-opacity-10" href="<?php the_permalink(); ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-eye me-2" viewBox="0 0 16 16">
                    <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z"/>
                    <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z"/>
                </svg>
                مشاهده و ادامه
                </a>
        </div>
    </div>
</article>