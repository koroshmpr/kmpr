<section class="bg-primary pt-3">
<div class="container">
    <!--    main footer -->
    <div class="row justify-content-center pt-lg-4 align-items-start">
        <!--            column-01-->
        <div class="col-lg-4 p-lg-5 pt-lg-0 pb-lg-4 mb-4 mb-lg-0" data-aos="fade-left" data-aos-duration="500">
            <div class="p-4 pb-0 pb-lg-4 text-center bg-secondary rounded-2">
            <!--            logo -->
            <?php $sizeLogo = 'col-3';
            get_template_part('template-parts/logo-brand', null, array('sizeLogo' => $sizeLogo)); ?>
            <!--                footer descriptions-->
            <p class="py-3 py-lg-4  mb-0 text-primary fw-bold text-justify"><?= get_field('footer_description', 'option'); ?></p>
            <div class=" d-none d-lg-inline">
                <!--                social media-->
                <?php
                $sizeSvgX = '30';
                $sizeSvgY = '30';
                $class = 'justify-content-center text-primary fill-primary';
                $args = array(
                    'sizeSvgX' => $sizeSvgX,
                    'sizeSvgY' => $sizeSvgY,
                    'class' => $class
                );
                get_template_part('template-parts/social-media', null, $args);
                ?>
            </div>
            </div>
        </div>
        <!--            column-02-->
        <div class="col-lg col-12 my-2 my-lg-0">
            <h6 class="fw-bold fs-5 mb-4 text-center text-secondary text-lg-start"><?= get_field('first_menu', 'option'); ?></h6>
            <?php
            $locations = get_nav_menu_locations();
            $menu = wp_get_nav_menu_object($locations['footerLocationOne']);
            ?>
            <?php
            wp_nav_menu(array(
                'theme_location' => 'footerLocationOne',
                'menu_class' => 'list-unstyled pe-0 d-lg-grid d-flex  flex-wrap gap-3 justify-content-lg-start justify-content-center align-items-center align-items-lg-start',
                'container' => false,
                'menu_id' => 'navbarTogglerMenu',
                'item_class' => 'nav-item',
                'link_class' => 'lazy text-decoration-none text-white text-opacity-75',
                'depth' => 1,
            ));
            ?>

        </div>
        <!--            column-03-->
        <div class="col-lg col-4 my-2 my-lg-0">
            <h6 class="fw-bold fs-5 mb-4 text-center text-secondary  text-lg-start"><?= get_field('second_menu', 'option'); ?></h6>
            <?php
            $locations = get_nav_menu_locations();
            $menu = wp_get_nav_menu_object($locations['footerLocationTwo']);
            ?>
            <?php
            wp_nav_menu(array(
                'theme_location' => 'footerLocationTwo',
                'menu_class' => 'list-unstyled pe-0 d-lg-grid d-flex flex-wrap gap-3 justify-content-lg-start justify-content-center align-items-center align-items-lg-start',
                'container' => false,
                'menu_id' => 'navbarTogglerMenu',
                'item_class' => 'nav-item',
                'link_class' => 'lazy text-decoration-none text-white text-opacity-75',
                'depth' => 1,
            ));
            ?>

        </div>
        <!--            column-04-->
        <div class="col-lg col-12 my-2 my-lg-0 ">
            <h6 class="fw-bold fs-5 mb-4 text-center text-secondary  text-lg-start"><?= get_field('third_menu', 'option'); ?></h6>
            <?php
            $locations = get_nav_menu_locations();
            $menu = wp_get_nav_menu_object($locations['footerLocationThree']);
            ?>
            <?php
            wp_nav_menu(array(
                'theme_location' => 'footerLocationThree',
                'menu_class' => 'list-unstyled pe-0 d-lg-grid d-flex flex-wrap gap-3 justify-content-lg-start justify-content-center align-items-center align-items-lg-start',
                'container' => false,
                'menu_id' => 'navbarTogglerMenu',
                'item_class' => 'nav-item',
                'link_class' => 'lazy text-decoration-none text-white text-opacity-75',
                'depth' => 1,
            ));
            ?>

        </div>
        <!--            column-05-->
        <div class="col-lg-3 col-12">
            <p class="fw-bold pb-3 mb-0 fs-5 text-lg-start text-center text-secondary">به مشاوره احتیاج دارید</p>
            <div class="d-flex justify-content-lg-start justify-content-center">
                <a href="tel:<?= get_field('phone_number', 'option'); ?>"
                   class="d-flex align-items-center gap-3 justify-content-center justify-content-lg-start">
                    <?php
                    $sizeSvgX = '18';
                    $sizeSvgY = '18';
                    $colorSvg = '#e9c77b';
                    $args = array(
                        'sizeSvgX' => $sizeSvgX,
                        'sizeSvgY' => $sizeSvgY,
                        'colorSvg' => $colorSvg
                    );
                    get_template_part('template-parts/svg/call-fill', null, $args); ?>
                    <div>
                        <h6 class="fw-bold fs-6 text-white-50"><?= get_field('phone_number', 'option'); ?></h6>
                    </div>
                </a>
            </div>
            <!--                conformations-->
            <div class="d-flex gap-4 pt-4 justify-content-lg-start justify-content-center align-items-center">
                <?php
                while (have_rows('confirmation', 'option')): the_row();
                    ?>
                    <p class="text-dark"><?= get_sub_field('conf_items', 'option'); ?></p>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
</div>
<?php get_template_part('template-parts/layout/footer/copyright'); ?>
</section>