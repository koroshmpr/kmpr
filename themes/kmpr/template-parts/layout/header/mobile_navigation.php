<?php
$locations = get_nav_menu_locations();
$menu = wp_get_nav_menu_object($locations['topHeaderMenuLocation']);
$i = 0;
if ($menu) :
    $menu_items = wp_get_nav_menu_items($menu);

    echo '<ul class="d-flex justify-content-evenly px-3 mb-0 gap-3 list-unstyled">';

    foreach ($menu_items as $item) :
        $menu_item_title = $item->title;
        $menu_item_url = $item->url;
        $icon= get_field('icon', $item->ID); ?>
        <li class="nav-item d-flex align-items-center text-center col <?=  $i == 2 ? 'bg-secondary shadow-sm rounded-3 text-primary' : '' ; echo $i == 0 || $i == 3 ? '' : ' border-start' ?>">
<?php
        echo '<a href="' . esc_url($menu_item_url) . '" class="lazy text-decoration-none fs-3 fw-bold w-100">';

        // Check if ACF field value is not empty and is a valid SVG
        if ($icon) {
            echo $icon; // Output the ACF field value (SVG icon)
        } else {
            echo esc_html($menu_item_title); // Output the default menu item text
        }

        echo '</a>';
        echo '</li>';
        $i++;
    endforeach;

    echo '</ul>';
endif;
?>
