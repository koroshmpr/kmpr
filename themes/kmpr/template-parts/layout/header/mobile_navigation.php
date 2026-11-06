<?php
global $post;
$current_url = (is_ssl() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

$locations = get_nav_menu_locations();
$menu = wp_get_nav_menu_object($locations['topHeaderMenuLocation']);
$i = 0;
if ($menu) :
    $menu_items = wp_get_nav_menu_items($menu);

    echo '<ul class="d-flex justify-content-evenly px-3 mb-0 gap-3 list-unstyled">';

    foreach ($menu_items as $item) :
        $menu_item_title = $item->title;
        $menu_item_url = $item->url;
        $icon = get_field('icon', $item->ID);

        // Check if the menu item URL matches the current page URL
        $is_current_page = ($menu_item_url === $current_url);

        ?>
        <li  class="nav-item d-flex align-items-center lazy text-center col<?= $is_current_page ? ' bg-secondary shadow-sm rounded-3 text-primary' : (($i == 0 || $i == 3 || $i == 2) ? '' : ' border-start') ?>">
            <a href="<?= esc_url($menu_item_url) ?>" class="lazy text-decoration-none fs-3 fw-bold w-100"
               aria-label="<?= $menu_item_title; ?>"
                title="<?= $menu_item_title; ?>">
                <?php
                // Check if ACF field value is not empty and is a valid SVG
                if ($icon) {
                    echo $icon; // Output the ACF field value (SVG icon)
                } else {
                    echo esc_html($menu_item_title); // Output the default menu item text
                }
                ?>
            </a>
        </li>
        <?php
        $i++;
    endforeach;

    echo '</ul>';
endif;
?>
