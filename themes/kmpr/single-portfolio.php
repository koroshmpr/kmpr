<?php get_header();

track_post_views(get_the_ID());


while (have_posts()) :

the_post();

$comment_count = get_comments_number(); // Get the number of comments for this post

?>
<section class="container pb-3 pt-lg-3">
    <div class="row align-items-start pb-4 justify-content-lg-between justify-content-center">
        <!--            sidebar-->
        <aside class="row justify-content-center col-lg-4 col-12 pt-lg-4 px-lg-4 position-relative order-first order-lg-last h-100">
            <div class="sticky__navbar">
                <div class="img-fluid mb-5 position-relative" data-aos="flip-right">
                    <img class="position-absolute top-100 start-50 translate-middle border border-3 shadow-sm border-white rounded-2 <?= get_field('logo_background') ?>"
                         width="60" height="60"
                         src="<?= get_field('brand-logo')['url']; ?>"
                         alt="<?= get_field('brand-logo')['title']; ?>">
                    <img class="w-100 object-fit rounded-2 img-thumbnail"
                         style="min-height: 230px"
                         src="<?= get_the_post_thumbnail_url(); ?>"
                         alt="<?= the_title(); ?>"/>
                </div>
                <!--                title-->
                <h1 class="fw-bold fs-3 text-dark text-center"><?= get_the_title(); ?></h1>
                <!--                share button-->
                <?php
                $sizeSvgX = '20';
                $sizeSvgY = '20';
                $containerCLass = 'share-button';
                $class = 'fill-primary border border-primary border-opacity-10';
                $mainClass = 'd-grid gap-1';
                $headingClass = 'd-none';
                $colorSvg = '#FE0000';
                $args = array(
                    'sizeSvgX' => $sizeSvgX,
                    'headingClass' => $headingClass,
                    'class' => $class,
                    'mainClass' => $mainClass,
                    'sizeSvgY' => $sizeSvgY,
                    'colorSvg' => $colorSvg,
                    'containerCLass' => $containerCLass
                );
                get_template_part('template-parts/share-button', null, $args); ?>
            </div>
        </aside>
        <!--            main content-->
        <div class='col-11 col-lg-8 order-first order-lg-last'>
            <?php if (get_queried_object()->post_type == 'portfolio'): ?>
                <!--                categories-->
                <div class="py-3">
                    <?php
                    $category_detail = get_the_category($post->ID, 'portfolio_categories');
                    $category_count = count($category_detail);
                    $i = 0;
                    foreach ($category_detail as $category) {
                        echo $category->name;
                        if (++$i !== $category_count) {
                            echo ' - ';
                        }
                    } ?>
                </div>
            <?php endif; ?>
            <div class="p-2 border border-primary">
                <div class="bg-primary row row-cols-1 row-cols-lg-2 justify-content-lg-between p-4 overflow-hidden row-gap-3 gx-5"
                     data-aos="zoom-in">
                    <!--                    social and website -->
                    <div class="row justify-content-center px-2 gap-3" data-aos="fade-left">
                        <h3 class="pt-3 text-secondary text-center">اطلاعات مشتری</h3>
                        <!--                        social media-->
                        <div data-aos="fade-left" data-aos-duration="700" data-aos-delay="200">
                            <?php
                            $sizeSvgX = '20';
                            $sizeSvgY = '20';
                            $class = 'fill-white justify-content-center';
                            $args = array(
                                'class' => $class,
                                'sizeSvgX' => $sizeSvgX,
                                'sizeSvgY' => $sizeSvgY,
                            );
                            get_template_part('template-parts/portfolio/social-media', null, $args); ?>
                        </div>
                        <!--                        website-->
                        <a class="col-4 bg-secondary py-2 text-primary rounded-2 fw-bold d-flex align-items-center gap-1 justify-content-center"
                           data-aos="fade-left"
                           data-aos-duration="700"
                           data-aos-delay="400"
                           target="_blank"
                           href="<?= get_field('portfolio-website')['url']; ?>">
                            <i class="bi bi-tv fs-4 fw-bold"></i>
                            وب‌سایت</a>
                    </div>
                    <!--                    website detail -->
                    <div class="row justify-content-center px-2 gap-3" data-aos="fade-right">
                        <h3 class="pt-3 text-secondary text-center">اطلاعات سایت</h3>
                        <div class="text-white text-center" data-aos="fade-right" data-aos-duration="500"
                             data-aos-delay="200">
                            <?= get_field_object('theme_type')['label']; ?> :
                            <span class="text-secondary fw-bold"><?= get_field_object('theme_type')['value']; ?></span>
                        </div>
                        <div class="text-white text-center" data-aos="fade-right" data-aos-duration="700"
                             data-aos-delay="400">
                            <?= get_field_object('page_builder')['label']; ?> :
                            <span class="text-secondary fw-bold"><?= get_field_object('page_builder')['value']; ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <!--                content-->
            <article class="pt-5 text-justify">
                <?= the_content(); ?>
            </article>
        </div>
    </div>
    <?php endwhile;
    wp_reset_query(); ?>
</section>
<?php if (have_rows('screenshot')) { ?>
    <section class="bg-secondary py-4">
        <div class="container">
            <div class="d-flex flex-wrap flex-lg-nowrap justify-content-center align-content-center gap-3 justify-content-lg-between ">
                <?php while (have_rows('screenshot')): the_row(); ?>
                    <div class="col-11 col-lg-4 px-lg-4">
                        <div class="row bg-primary justify-content-center my-3 gx-5">
                            <div class="py-1 px-2 fs-1 text-center border-bottom border-5 border-secondary">
                                <?php
                                $sizeSvgX = '40';
                                $sizeSvgY = '40';
                                $svgColor = "#fff";
                                $args = array(
                                    'sizeSvgX' => $sizeSvgX,
                                    'sizeSvgY' => $sizeSvgY,
                                    'color' => $svgColor
                                );
                                get_template_part('template-parts/svg/portfolio/' . get_sub_field('screen-size', null, $args)); ?>
                            </div>
                            <?php if (get_sub_field('screenshot-image')) { ?>
                                <div class="overflow-hidden w-100 p-1">
                                    <div class="screenshot w-100"
                                        <?php echo get_sub_field('screenshot-image')['height']; ?>
                                         style="background-image: url('<?= get_sub_field('screenshot-image')['url']; ?>');
                                                 transition-duration: <?= get_sub_field('screenshot-image')['height'] * 1.8; ?>ms">
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </section>
<?php } ?>
<section class="container py-5">
    <h3 class="bg-primary text-white text-center p-4 shadow-sm mx-lg-3">نمونه کارهای دیگر</h3>
    <?php
    global $post;
    // Get the ID of the current post
    $current_post_id = $post->ID;
    $args = array(
        'post_type' => 'portfolio',
        'post_status' => 'publish',
        'order' => 'DESC',
        'orderby' => 'rand',
        'posts_per_page' => '3',
        'ignore_sticky_posts' => true,
        'post__not_in' => array($current_post_id)
    );
    $loop = new WP_Query($args);
    if ($loop->have_posts()) :
    $i = 0;
    /* Start the Loop */
    ?>
    <div class="d-flex flex-nowrap gap-3 mt-n4 overflow-x-scroll p-lg-5 py-5 p-3 align-items-center justify-content-start border border-1 border-primary border-opacity-25 justify-content-lg-center bg-opacity-10 bg-info">
        <?php
        $footerContainer = "pb-3";
        $customClass = "col-11 col-lg-4";
        $buttonCard = "py-1 py-lg-3";
        $args = array(
            'class' => $customClass,
            'footerContainer' => $footerContainer,
            'buttonCard' => $buttonCard
        );
        while ($loop->have_posts()) :

            $loop->the_post(); ?>
            <?php get_template_part('template-parts/portfolio/portfolio-card', null, $args);
            $i++;
        endwhile;
        endif;
        wp_reset_postdata(); ?>
    </div>
</section>
<?php get_footer(); ?>

