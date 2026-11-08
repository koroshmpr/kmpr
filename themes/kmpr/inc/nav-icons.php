<?php
/**
 * Shared navigation icons (header menu, mobile tab bar / sheet, header toolbar).
 * Icons are picked from a link's URL path, so menu items stay editable in Appearance → Menus.
 *
 * @package KMPR
 */

if (!defined('ABSPATH')) {
    exit;
}

function kmpr_nav_icon_paths()
{
    return array(
        'home'      => '<path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/>',
        'blog'      => '<path d="M4 19.5V5a2 2 0 0 1 2-2h12v15H6.5A2.5 2.5 0 0 0 4 20.5 2.5 2.5 0 0 0 6.5 23H18"/><path d="M8 7h6M8 11h4"/>',
        'services'  => '<path d="M12 2 2 7l10 5 10-5-10-5Z"/><path d="m2 17 10 5 10-5"/><path d="m2 12 10 5 10-5"/>',
        'portfolio' => '<rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/>',
        'about'     => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'resume'    => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8"/>',
        'games'     => '<rect x="2" y="6" width="20" height="12" rx="6"/><path d="M6 12h4M8 10v4"/><circle cx="15.5" cy="11" r=".9" fill="currentColor"/><circle cx="17.5" cy="13" r=".9" fill="currentColor"/>',
        'faq'       => '<circle cx="12" cy="12" r="10"/><path d="M9.1 9a3 3 0 0 1 5.8 1c0 2-3 3-3 3M12 17h.01"/>',
        'contact'   => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
        'search'    => '<circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/>',
        'link'      => '<path d="M10 13a5 5 0 0 0 7.5.5l3-3a5 5 0 0 0-7-7l-1.7 1.7"/><path d="M14 11a5 5 0 0 0-7.5-.5l-3 3a5 5 0 0 0 7 7l1.7-1.7"/>',
    );
}

/**
 * Icon key for a URL (by path keywords).
 */
function kmpr_nav_icon_for($url)
{
    $p = trim((string) wp_parse_url((string) $url, PHP_URL_PATH), '/');
    if ($p === '') {
        return 'home';
    }
    foreach (array('blog' => 'blog', 'service' => 'services', 'portfolio' => 'portfolio', 'about' => 'about', 'resume' => 'resume', 'game' => 'games', 'faq' => 'faq', 'contact' => 'contact') as $needle => $icon) {
        if (strpos($p, $needle) !== false) {
            return $icon;
        }
    }
    return 'link';
}

/**
 * Inline SVG for an icon key.
 */
function kmpr_nav_svg($name, $size = 20, $class = 'shrink-0')
{
    $paths = kmpr_nav_icon_paths();
    return '<svg width="' . (int) $size . '" height="' . (int) $size . '" class="' . esc_attr($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($paths[$name] ?? $paths['link']) . '</svg>';
}

/**
 * Desktop header menu: prepend an icon to every top-level item title.
 */
add_filter('nav_menu_item_title', function ($title, $item, $args, $depth) {
    if (empty($args->theme_location) || $args->theme_location !== 'headerMenuLocation' || $depth > 0) {
        return $title;
    }
    return kmpr_nav_svg(kmpr_nav_icon_for($item->url), 16, 'kmpr-nav-ico shrink-0') . '<span>' . $title . '</span>';
}, 10, 4);
