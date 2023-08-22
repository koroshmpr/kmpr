<?php /* Template Name: Home */
/**
 * Front page
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package pandplus
 */

get_header();


if (have_posts())
    the_post();
// hero
get_template_part('template-parts/homePage/hero');
/// portfolio last posts
get_template_part('template-parts/portfolio/recently-portfolios');
/// most visited post
get_template_part('template-parts/homePage/blog-slider');
/// services list
get_template_part('template-parts/services/services-home');
get_footer();