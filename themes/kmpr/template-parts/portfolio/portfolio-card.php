<article class="position-relative <?= $args['class'] ?? ''; ?>">
    <div class="pt-0 p-3">
        <img class="position-absolute top-0 start-0 object-fit ms-4 translate-middle-y border border-3 shadow-sm border-white rounded-2 <?= get_field('logo_background')?>"
             width="60" height="60"
             src="<?= get_field('brand-logo')['url']; ?>"
             alt="<?= get_field('brand-logo')['title']; ?>">
        <a class="row p-4 rounded" href="<?php the_permalink(); ?>">
            <img height="300" class="post-cover object-fit rounded p-0 shadow-sm" src="<?php echo the_post_thumbnail_url(); ?>"
                 alt="<?= get_the_title(); ?>">
            <div class="mt-3 px-lg-0">
                <p class="fs-5 fw-bold"> <?= get_the_title(); ?></p>
                <p class="fs-6 mb-0"><?= wp_trim_words(get_the_content(), 18); ?></p>
            </div>
        </a>
        <div class="d-flex justify-content-between pt-3">
            <div class="my-2">
                <?php
                $sizeSvgX = '20';
                $sizeSvgY = '20';
                $class= "fill-primary";
                $colorSvg = '#FE0000';
                $args = array(
                    'sizeSvgX' => $sizeSvgX,
                    'class' => $class,
                    'sizeSvgY' => $sizeSvgY,
                    'colorSvg' => $colorSvg
                );
                get_template_part('template-parts/portfolio/social-media', null, $args); ?>
            </div>
            <a class="btn bg-primary text-white" href="<?php the_permalink(); ?>">مشاهده و ادامه</a>
        </div>
    </div>
</article>