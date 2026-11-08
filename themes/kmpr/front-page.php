<?php /* Template Name: Home */
/**
 * Front page — personal brand of Korosh Mpr.
 * Sections live in template-parts/home/ and share the motion layer (javascript/motion.js).
 *
 * @package KMPR
 */

get_header();

if (have_posts()) {
    the_post();
}

get_template_part('template-parts/home/hero');
get_template_part('template-parts/home/stack-marquee');
get_template_part('template-parts/home/services');
get_template_part('template-parts/home/selected-work');
get_template_part('template-parts/home/process');
get_template_part('template-parts/home/about');
get_template_part('template-parts/home/articles');
get_template_part('template-parts/home/games');
get_template_part('template-parts/home/cta');

get_footer();
