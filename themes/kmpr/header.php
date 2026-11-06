<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta name="keywords" content="<?= get_bloginfo('name'); ?>">
    <meta name="author" content="کوروش محمدپور | Korosh Mpr">
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, user-scalable=yes, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
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
