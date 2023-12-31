<section class="bg-primary pt-3">
    <div class="container overflow-hidden">
        <!--    main footer -->
        <div class="row justify-content-center pt-lg-4 align-items-start">
            <!--            column-01-->
            <div class="col-lg-4 p-lg-5 pt-lg-0 pb-lg-4 mb-4 mb-lg-0" data-aos="fade-left" data-aos-duration="500">
                <div class="p-4 pb-0 pb-lg-4 text-center bg-secondary rounded-2">
                    <!--            logo -->
                    <?php $sizeLogo = 'col-3';
                    get_template_part('template-parts/logo-brand', null, array('sizeLogo' => $sizeLogo)); ?>
                    <!--                footer descriptions-->
                    <div class="py-3 text-center">
                        <?= get_field('footer_description', 'option'); ?>
                    </div>
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
                <p class="fw-bold fs-5 mb-4 text-center text-secondary text-lg-start"><?= get_field('first_menu', 'option'); ?></p>
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
                <p class="fw-bold fs-5 mb-4 text-center text-secondary  text-lg-start"><?= get_field('second_menu', 'option'); ?></p>
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
                <p class="fw-bold fs-5 mb-4 text-center text-secondary  text-lg-start"><?= get_field('third_menu', 'option'); ?></p>
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
                <div class="row justify-content-lg-start justify-content-center row-gap-2">
                    <a href="tel:<?= get_field('phone_number', 'option'); ?>"
                       class="call-btn d-flex align-items-center overflow-hidden rounded-pill justify-content-center justify-content-lg-start px-0">
                        <div class="tel-icon d-flex align-items-center roll-btn m-n1 text-primary bg-secondary border overflow-hidden border-4 border-primary rounded-circle justify-content-center">
                            <?php
                            $sizeSvgX = '18';
                            $sizeSvgY = '18';
                            $colorSvg = '#333';
                            $args = array(
                                'sizeSvgX' => $sizeSvgX,
                                'sizeSvgY' => $sizeSvgY,
                                'colorSvg' => $colorSvg
                            );
                            get_template_part('template-parts/svg/call-fill', null, $args); ?>
                        </div>
                        <div class="tel-label bg-secondary fw-bold text-primary rounded-end-pill ps-4 pe-3 py-1 my-1 ms-n3 d-flex justify-content-center align-items-center">
                            <?= get_field('phone_number_label', 'option'); ?>
                        </div>
                    </a>
                    <a href="mailto:<?= get_field('email', 'option'); ?>"
                       class="call-btn d-flex align-items-center overflow-hidden rounded-pill justify-content-center justify-content-lg-start px-0">
                        <div class="tel-icon d-flex align-items-center roll-btn m-n1 text-primary bg-secondary border overflow-hidden border-4 border-primary rounded-circle justify-content-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="23" height="23" fill="currentColor" class="bi bi-envelope-fill text-primary" viewBox="0 0 16 16">
                                <path d="M.05 3.555A2 2 0 0 1 2 2h12a2 2 0 0 1 1.95 1.555L8 8.414.05 3.555ZM0 4.697v7.104l5.803-3.558L0 4.697ZM6.761 8.83l-6.57 4.027A2 2 0 0 0 2 14h12a2 2 0 0 0 1.808-1.144l-6.57-4.027L8 9.586l-1.239-.757Zm3.436-.586L16 11.801V4.697l-5.803 3.546Z"/>
                            </svg>
                        </div>
                        <div class="tel-label bg-secondary fw-bold text-primary rounded-end-pill ps-4 pe-3 py-1 my-1 ms-n3 d-flex justify-content-center align-items-center">
                            <?= get_field('email', 'option'); ?>
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
