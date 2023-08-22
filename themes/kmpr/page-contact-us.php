<?php
/** Template Name: contact-us page */

get_header(); ?>
    <section class="bg-primary py-5 my-auto">
        <div class="container">
            <div class="row justify-content-around align-items-center ">
                <div class="col-lg-4 col-11 pb-5 text-white" data-aos="fade-left">
                    <h1 class="fw-bold text-center text-lg-start">راه های ارتباطی</h1>
                    <address class="py-2 m-0 d-flex gap-3 align-items-center fw-bold justify-content-center justify-content-lg-start">
                        <i class="bi bi-geo-alt-fill me-2 text-secondary fs-4"></i>
                        <?= get_field('address', 'option'); ?>
                    </address>
                    <a href="tel:<?= get_field('phone', 'option'); ?>"
                       class="py-2 fw-bold d-flex gap-3 text-white align-items-center justify-content-center justify-content-lg-start">
                        <i class="bi bi-telephone-fill me-2  text-secondary fs-4"></i>
                        <?= get_field('phone_number', 'option'); ?>
                    </a>
                    <a href="mailto:<?= get_field('email', 'option'); ?>"
                       class="py-2 fw-bold d-flex gap-3 text-white align-items-center justify-content-center justify-content-lg-start">
                        <i class="bi bi-envelope-fill me-2 text-secondary fs-4"></i>
                        <?= get_field('email', 'option'); ?>
                    </a>
                    <!--                social media-->
                    <?php
                    $sizeSvgX = '30';
                    $sizeSvgY = '30';
                    $class = "fill-secondary mx-n2 justify-content-center justify-content-lg-start";
                    $args = array(
                        'sizeSvgX' => $sizeSvgX,
                        'sizeSvgY' => $sizeSvgY,
                        'class' => $class
                    );
                    get_template_part('template-parts/social-media', null, $args);
                    ?>
                </div>
                <div class="col-lg-8 col-11" data-aos="flip-left" data-aos-duration="1000" style="height: 400px;">
                    <?= get_field('map', 'option'); ?>
                </div>
            </div>
        </div>
    </section>
    <section class="bg-secondary">
        <div class="container">
            <div class="row justify-content-center align-items-center py-5 overflow-hidden">
                <div class="col-lg-6 col-11">
                    <h5 class="text-white fw-bold fs-2 py-3 text-center" data-aos="fade-left">با ما در ارتباط
                        باشید</h5>
                    <?php echo do_shortcode('[gravityform id="' . get_field('form-id') . '" title="false" description="false" ajax="true"]') ?>
                </div>
            </div>

        </div>
        </div>
    </section>
<?php get_footer(); ?>