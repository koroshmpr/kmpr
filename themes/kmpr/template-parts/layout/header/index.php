<nav class="d-none d-lg-inline sticky__nav <?= is_admin() ? 'is__admin' : ''; ?><?php !is_single() ? 'pb-3 ' : '' ?> pb-2 start-0 end-0 z-3 text-primary bg-white pt-2">
    <div class="container <?= is_singular('post') ? 'pb-2' : ''; ?>">
        <div class="d-flex align-items-center pb-lg-0 justify-content-center">
            <!--        brand and search bar -->
            <div class="col-lg-4 col-11 d-flex align-items-center gap-3 justify-content-between">
                <!--            logo -->
                <?php $sizeLogo = 'col-4 col-lg-3';
                get_template_part('template-parts/logo-brand', null, array('sizeLogo' => $sizeLogo)); ?>
                <!--        main menu-->
                <?php
                $place = 'header';
                $inputClass= 'bg-opacity-10';
                $dropdownClass = "d-none";
                $sizeSearch = 'col d-none d-lg-inline';
                $args = array(
                    'place' => $place,
                    'size' => $sizeSearch,
                    'dropdownClass' => $dropdownClass,
                    'inputClass' => $inputClass
                );
                get_template_part('template-parts/search-bar', null, $args); ?>
            </div>
            <button class="navbar-toggler d-lg-none text-primary" type="button" data-bs-toggle="offcanvas"
                    data-bs-target="#offcanvasMainMenu" aria-controls="offcanvasMainMenu"
                    aria-labelledby="offcanvasMainMenu" aria-label="menu-icon">
                <i class="bi bi-list fs-1 text-primary"></i>
            </button>
            <!--            menu-->
            <div class="col-lg-8 navbar navbar-expand-lg d-none d-lg-grid justify-content-end pe-3">
                <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                    <?php
                    $locations = get_nav_menu_locations();
                    $menu = wp_get_nav_menu_object($locations['headerMenuLocation']);
                    if ($menu) :
                        wp_nav_menu(array(
                            'theme_location' => 'headerMenuLocation',
                            'menu_class' => 'navbar-nav gap-2 align-items-center desktop-menu flex-wrap',
                            'container' => false,
                            'menu_id' => 'navbarTogglerMenu',
                            'item_class' => 'nav-item', // Add 'dropdown' class to top-level menu items
                            'link_class' => 'nav-link text-primary fw-bold', // Add 'nav-link' and 'dropdown-toggle' classes to menu item links
                            'depth' => 1,
                        ));
                    endif;
                    ?>
                </div>
            </div>
        </div>

    </div>
    <?php if (is_singular('post')) { get_template_part('template-parts/loop/post-detail-sticky');} ?>
</nav>
<nav class="d-lg-none bg-secondary-subtle rounded-top-3 fixed-bottom py-2 shadow-sm border-top border-2 border-primary">
    <?php get_template_part('template-parts/layout/header/mobile_navigation'); ?>
</nav>

