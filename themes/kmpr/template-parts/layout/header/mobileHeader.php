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
        $menu = wp_get_nav_menu_object($locations['topHeaderMenuLocation']);
        if ($menu) :
            $menu_items = wp_get_nav_menu_items($menu);

            echo '<ul class="navbar-nav gap-3 p-3">';

            foreach ($menu_items as $item) :
                $menu_item_title = $item->title;
                $menu_item_url = $item->url;
                $icon= get_field('icon', $item->ID);
                echo '<li class="nav-item text-center">';
                echo '<a href="' . esc_url($menu_item_url) . '" class="lazy text-decoration-none fs-3 text-white fw-bold">';

                // Check if ACF field value is not empty and is a valid SVG
                if ($icon) {
                    echo $icon; // Output the ACF field value (SVG icon)
                } else {
                    echo esc_html($menu_item_title); // Output the default menu item text
                }

                echo '</a>';
                echo '</li>';
            endforeach;

            echo '</ul>';
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