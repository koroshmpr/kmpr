<?php
/** Template Name: about us page */

get_header(); ?>
    <section class="bg-primary py-5">
        <div class="container">
        <div class="row justify-content-around align-items-center">
            <div class="col-lg-6 col-11 text-white">
                <h1 class="fw-bold display-3" data-aos="fade-left"><?= get_field('aboutus-title');?></h1>
                <p class="mb-0 text-justify pt-5" data-aos="fade-left" data-aos-delay="100"><?= get_field('aboutus-content');?></p>
            </div>
            <div class="col-lg-6 col-11" data-aos="fade-right">
                <img class="w-100 object-fit img-thumbnail bg-secondary"
                     src="<?= get_field('aboutus_image')['url']; ?>"
                     alt="<?= get_field('aboutus_image')['title']; ?>">
            </div>
        </div>
        </div>
    </section>
    <section class="bg-secondary">
        <div class="row justify-content-center text-center py-5">
            <div class="col-12 col-lg-7">
                <h4 class="text-primary fs-2 fw-bold"><?= get_field('title_statistics'); ?></h4>
                <p class="text-justify text-primary px-3 fs-3"><?= get_field('content_statistics'); ?></p>
                <div class="row row-cols-2 row-cols-lg-4 justify-content-center text-center py-5">
                    <?php
                    while (have_rows('statistics_list')): the_row();;
                        ?>
                        <div data-aos="fade-up"
                             data-aos-anchor-placement="top-bottom">
                            <h5 class="fw-bolder fs-2 text-primary"><?= get_sub_field('list_statistics_value'); ?></h5>
                            <p class="text-dark text-opacity-75 fs-3"><?= get_sub_field('list_statistics_title'); ?></p>
                        </div>
                        <?php
                    endwhile; ?>
                </div>
            </div>
        </div>
    </section>

<?php get_footer(); ?>
