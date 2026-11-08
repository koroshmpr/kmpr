<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="author" content="کوروش محمدپور | Korosh Mpr">
    <meta name="viewport" content="width=device-width, user-scalable=yes, initial-scale=1.0, viewport-fit=cover">
    <?php
    $fav_id = get_option('site_icon') ?: get_field('favicon', 'option');
    $fav_url = $fav_id ? (is_numeric($fav_id) ? wp_get_attachment_image_url($fav_id, 'full') : (is_array($fav_id) ? ($fav_id['url'] ?? '') : $fav_id)) : (get_template_directory_uri() . '/public/images/kmpr-favicon.png');
    $fav_svg = get_template_directory_uri() . '/public/images/kmpr-favicon.svg';
    ?>
    <link rel="icon" type="image/svg+xml" href="<?= esc_url($fav_svg); ?>">
    <?php if (!has_site_icon()) : /* WordPress prints the PNG / apple-touch icons itself when a Site Icon is set */ ?>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= esc_url($fav_url); ?>">
    <link rel="apple-touch-icon" href="<?= esc_url($fav_url); ?>">
    <?php endif; ?>
    <script>
        /* KMPR motion bootstrap: hide reveal targets only when JS + motion are available; never leave content hidden. */
        (function (d) {
            if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
            d.classList.add('motion-ready');
            setTimeout(function () { if (!window.__kmprMotion) d.classList.add('motion-fallback'); }, 3500);
        })(document.documentElement);
    </script>
    <?php wp_head(); ?>
</head>

<body <?php body_class('font-yekan bg-slate-950 text-slate-100 antialiased selection:bg-secondary/30 selection:text-secondary'); ?>
      :class="searchOpen || menuOpen ? 'overflow-hidden' : ''"
      x-data="{ scrolled: false, lastScroll: 0, scrollingDown: false, menuOpen: false, searchOpen: false, ticking: false }"
      @keydown.window.prevent.cmd.k="searchOpen = true"
      @keydown.window.prevent.ctrl.k="searchOpen = true"
      x-init="
        window.addEventListener('scroll', () => {
            if (!ticking) {
                window.requestAnimationFrame(() => {
                    let currentScroll = window.pageYOffset || window.scrollY;
                    scrollingDown = currentScroll > lastScroll && currentScroll > 20;
                    lastScroll = currentScroll;
                    scrolled = currentScroll > 80;
                    ticking = false;
                });
                ticking = true;
            }
        }, { passive: true });">
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:start-3 focus:z-[100] focus:px-4 focus:py-2 focus:rounded-full focus:bg-secondary focus:text-slate-950 focus:font-black">پرش به محتوای اصلی</a>
    <?php get_template_part(function_exists('kmpr_is_games_page') && kmpr_is_games_page() ? 'template-parts/games/header-bar' : 'template-parts/layout/header/header-content'); ?>
    <main id="main" class="min-h-screen pt-14 sm:pt-16 lg:pt-20 overflow-x-clip bg-slate-950 text-slate-100">
