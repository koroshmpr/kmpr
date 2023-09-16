<?php get_header();

while (have_posts()) :
    the_post();
    ?>
    <section class="container-fluid pb-2 p-0">
        <img class="vw-100 object-fit" data-aos="fade-down" height="500"
             src="<?= get_the_post_thumbnail_url(); ?>"
             alt="<?= the_title(); ?>">
        <div class="mt-n5 bg-white rounded-top-5 position-relative" data-aos="fade-up">
            <div class="container">
                <h1 class="display-1 m-0 pt-3 text-center animate__animated animate__fadeInLeft animate__delay-1"><?= the_title(); ?></h1>
                <!--                share button-->
                <?php
                $sizeSvgX = '23';
                $sizeSvgY = '23';
                $class = 'fill-primary border border-primary border-opacity-10 rounded-5';
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
                <article class="text-dark text-justify border border-1 p-lg-5 p-4 fs-5 rounded-5">
                    <?= the_content(); ?>
                </article>
            </div>

        </div>
    </section>
    <?php
    if (get_field('Proposal_switch') == 'yes') { ?>
        <section class="container py-5">
            <div class="row align-items-center justify-content-lg-center gap-lg-0 gap-5 flex-nowrap overflow-y-hidden overflow-x-scroll px-4 px-lg-0">
                <?php
                if (have_rows('proposal')) {
                    while (have_rows('proposal')):
                        the_row();
                        $rowIndex = get_row_index();
                        $offer = get_sub_field('proposal_offer'); ?>
                        <div <?php
                        if(($rowIndex) == 1) {
                            echo 'data-aos="fade-right" data-aos-delay="150"';
                        }
                        elseif (($rowIndex) == 2) {
                            echo 'data-aos="fade-up"';
                        } elseif (($rowIndex) == 3) {
                            echo 'data-aos="fade-left" data-aos-delay="150"';
                        }; ?>"
                             class="col-lg col-11 rounded-5 shadow row <?= $offer == 'yes' ? 'bg-primary text-white py-5 px-4 row-gap-3' : 'mx-lg-5 row-gap-2 p-4 border border-primary border-opacity-10'; ?>">
                            <div class="text-center">
                                <h3 class="display-6 fw-bold"><?= get_sub_field('proposal_title'); ?></h3>
                                <?php if (get_sub_field('proposal_description')) { ?>
                                    <p class="mb-0"><?= get_sub_field('proposal_description'); ?></p>
                                <?php }; ?>
                            </div>
                            <?php while (have_rows('proposal_row')): the_row(); ?>
                                <?php $propertyValue = get_sub_field('Property_value'); ?>
                                <div class="d-flex justify-content-center gap-3 align-items-center bg-opacity-10 p-3 rounded-5 <?= $offer == 'yes' ? 'bg-white' : 'bg-primary' ?>">
                                    <span>
                                        <?= get_sub_field('Property_label');
                                        echo $propertyValue ? " : " : ""; ?>
                                    </span>
                                    <?php if ($propertyValue) { ?>
                                        <p class="mb-0 fw-bold"><?= $propertyValue; ?></p>
                                    <?php } ?>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php endwhile;
                } ?>
            </div>
        </section>
    <?php } ?>
    <section class="container py-5">
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




