<?php get_header();

while (have_posts()) :
    the_post();
    ?>
    <section class="container-fluid pb-2 p-0">
        <img class="vw-100 object-fit" data-aos="fade-down" height="500"
             src="<?= get_the_post_thumbnail_url(); ?>"
             alt="<?= the_title(); ?>">
        <div class="container mt-lg-0 mt-n5 bg-white rounded-top-5 position-relative" data-aos="fade-up">
            <div class="row align-items-start py-lg-4 py-2">
                <div class="col-12">
                    <h1 class="display-1 m-0 pt-3 text-center animate__animated animate__fadeInLeft animate__delay-1"><?= the_title(); ?></h1>
                    <!--                share button-->
                    <?php
                    $sizeSvgX = '20';
                    $sizeSvgY = '20';
                    $class = 'fill-primary border border-primary border-opacity-10';
                    $mainClass = 'd-grid';
                    $headingCLass = "d-none";
                    $args = array(
                        'sizeSvgX' => $sizeSvgX,
                        'headingClass' => $headingCLass,
                        'class' => $class,
                        'mainClass' => $mainClass,
                        'sizeSvgY' => $sizeSvgY,
                    );
                    get_template_part('template-parts/share-button', null, $args); ?>
                </div>
                <article class="col-12 text-dark text-justify border border-1 p-lg-5 px-3 py-4 fs-5">
                    <?= the_content(); ?>
                </article>
                <div class="col-12 p-2 mt-3">
                    <div class="row justify-content-lg-between align-items-center g-3 mx-1 mx-lg-0">
                        <div class="col-lg-6 d-flex justify-content-center align-items-center">
                            <h4 class="fw-bold display-2 py-lg-5 text-primary m-0">خدمات دیگر</h4>
                        </div>
                        <div class="services-main swiper col-lg-6">
                            <div class="swiper-wrapper">
                                <?php
                                global $post;
                                // Get the ID of the current post
                                $current_post_id = $post->ID;
                                $args = array(
                                    'post_type' => 'services',
                                    'post_status' => 'publish',
                                    'order' => 'DESC',
                                    'posts_per_page' => '-1',
                                    'ignore_sticky_posts' => true,
                                    'post__not_in' => array($current_post_id)
                                );
                                $loop = new WP_Query($args);
                                if ($loop->have_posts()) :
                                    $i = 0;
                                    /* Start the Loop */
                                    ?>
                                    <?php while ($loop->have_posts()) :
                                    $loop->the_post(); ?>
                                    <div class="swiper-slide">
                                        <?php get_template_part('template-parts/services/services-card'); ?>
                                    </div>
                                <?php
                                endwhile;
                                endif;
                                wp_reset_postdata(); ?>
                            </div>
                            <div class="swiper-pagination hero-pagination position-static w-auto mt-4"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section class="bg-secondary">
        <div class="container">
            <div class="row justify-content-center py-4">
                <div class="col-lg-6 col-11">
                    <h6 class="text-center fw-bold fs-3 text-white">ارتباط با ما</h6>
                    <?php if (get_post_type() === 'services') {
                        echo do_shortcode('[gravityform id="' . get_field('form-id', 'option') . '" title="false" description="false" ajax="true"]');
                    }
                    ?>
                </div>
            </div>
        </div>
    </section>
<?php endwhile;
wp_reset_query();
get_footer(); ?>




