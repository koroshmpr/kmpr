<?php
/** Template Name: contact-us page */

get_header(); ?>
    <section class="bg-primary pt-5 pb-lg-5 my-auto">
        <div class="container">
            <div class="row justify-content-around align-items-center ">
                <div class="col-lg-4 col-11 pb-5 text-white" data-aos="fade-left">
                    <h1 class="fw-bold text-center text-lg-start">راه های ارتباطی</h1>
                    <address class="py-2 m-0 d-flex gap-3 align-items-center fw-bold justify-content-center justify-content-lg-start">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-geo-alt-fill text-secondary" viewBox="0 0 16 16">
                            <path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/>
                        </svg>
                        <?= get_field('address', 'option'); ?>
                    </address>
                    <a href="tel:<?= get_field('phone', 'option'); ?>"
                       class="py-2 fw-bold d-flex gap-3 text-white align-items-center justify-content-center justify-content-lg-start">
                        <?php
                        $sizeSvgX = '20';
                        $sizeSvgY = '20';
                        $class = 'text-secondary';
                        $args = array(
                        'sizeSvgX' => $sizeSvgX,
                        'sizeSvgY' => $sizeSvgY,
                        'class' => $class
                        );
                        get_template_part('template-parts/svg/call-fill', null, $args); ?>
                        <?= get_field('phone_number', 'option'); ?>
                    </a>
                    <a href="mailto:<?= get_field('email', 'option'); ?>"
                       class="py-2 fw-bold d-flex gap-3 text-white align-items-center justify-content-center justify-content-lg-start">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-envelope-fill text-secondary" viewBox="0 0 16 16">
                            <path d="M.05 3.555A2 2 0 0 1 2 2h12a2 2 0 0 1 1.95 1.555L8 8.414.05 3.555ZM0 4.697v7.104l5.803-3.558L0 4.697ZM6.761 8.83l-6.57 4.027A2 2 0 0 0 2 14h12a2 2 0 0 0 1.808-1.144l-6.57-4.027L8 9.586l-1.239-.757Zm3.436-.586L16 11.801V4.697l-5.803 3.546Z"/>
                        </svg>
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
                <div class="col-lg-8 col-12 px-0" data-aos="flip-left" data-aos-duration="1000" style="height: 400px;">
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