<nav class="sticky__nav <?= is_admin() ? 'is__admin' : ''; ?><?php !is_single() ? 'pb-1 ' : '' ?>start-0 end-0 z-3 shadow-sm text-primary bg-white pt-2 pb-1">
    <div class="container">
        <div class="d-flex align-items-center pb-lg-0 justify-content-center">
            <!--        brand and search bar -->
            <div class="col-lg-4 col-11 d-flex align-items-center gap-3 justify-content-between">
                <!--            logo -->
                <?php $sizeLogo = 'col-4 col-lg-3';
                get_template_part('template-parts/logo-brand', null, array('sizeLogo' => $sizeLogo)); ?>
                <!--        main menu-->
                <?php
                $place = 'header';
                $sizeSearch = 'col d-none d-lg-inline';
                $args = array(
                    'place' => $place,
                    'size' => $sizeSearch
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
<div class="offcanvas offcanvas-end bg-primary" tabindex="-1" id="offcanvasMainMenu"
     aria-labelledby="offcanvasNavbarLabel">
    <div class="offcanvas-header bg-white border-5 border-bottom border-secondary shadow-sm">
        <a class="offcanvas-title logo-brand" id="offcanvasNavbarLabel" href="/">
            <!--            logo -->
            <?php $sizeLogo = 'col';
            get_template_part('template-parts/logo-brand', null, array('sizeLogo' => $sizeLogo)); ?>
        </a>
        <button type="button" class="btn-close text-reset fs-4" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <?php
        $locations = get_nav_menu_locations();
        $menu = wp_get_nav_menu_object($locations['headerMenuLocation']);
        if ($menu) :
            wp_nav_menu(array(
                'theme_location' => 'headerMenuLocation',
                'menu_class' => 'navbar-nav gap-3 p-3',
                'container' => false,
                'menu_id' => 'navbarTogglerMenu',
                'item_class' => 'nav-item text-center',
                'link_class' => 'lazy text-decoration-none fs-3 text-white fw-bold',
                'depth' => 1,
            ));
        endif;
        ?>
    </div>
    <?php
    $place = 'mobile-header';
    $sizeSearch = 'position-absolute start-50 col-10 translate-middle-x bottom-0 pb-3';
    $args = array(
    'place' => $place,
    'size' => $sizeSearch
    );
    get_template_part('template-parts/search-bar', null, $args); ?>
</div>

