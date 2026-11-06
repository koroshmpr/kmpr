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

/// hero
get_template_part('template-parts/homePage/hero');
/// services list
get_template_part('template-parts/services/services-home');
/// cta
get_template_part('template-parts/homePage/callToAction');
// portfolios-slider
get_template_part('template-parts/homePage/portfolios-slider');
/// about
get_template_part('template-parts/homePage/about-us');
/// most visited post
get_template_part('template-parts/homePage/blog-slider');
/// portfolio last posts
get_template_part('template-parts/portfolio/recently-portfolios');
get_footer();