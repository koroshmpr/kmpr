<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta name="keywords" content="<?= get_bloginfo('name'); ?>">
    <meta name="author" content="کوروش محمدپور | Korosh Mpr">
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, user-scalable=yes, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <?php
    $fav_id = get_option('site_icon') ?: get_field('favicon', 'option');
    $fav_url = $fav_id ? (is_numeric($fav_id) ? wp_get_attachment_image_url($fav_id, 'full') : (is_array($fav_id) ? ($fav_id['url'] ?? '') : $fav_id)) : (get_template_directory_uri() . '/public/images/kmpr-favicon.png');
    $fav_svg = get_template_directory_uri() . '/public/images/kmpr-favicon.svg';
    ?>
    <link rel="icon" type="image/svg+xml" href="<?= esc_url($fav_svg); ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= esc_url($fav_url); ?>">
    <link rel="apple-touch-icon" href="<?= esc_url($fav_url); ?>">
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
    <?php get_template_part('template-parts/global/preloader'); ?>
    <?php get_template_part('template-parts/layout/header/header-content'); ?>
    <main class="min-h-screen lg:pt-20 overflow-x-clip bg-slate-950 text-slate-100">
