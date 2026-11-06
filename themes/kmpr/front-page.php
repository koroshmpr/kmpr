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

/// Hero Section (Modern High-Tech Developer Intro)
get_template_part('template-parts/homePage/hero');

/// Developer Tech Arsenal & Tooling
get_template_part('template-parts/homePage/tech-stack');

/// Services List
get_template_part('template-parts/services/services-home');

/// Developer Bento Grid (Why Choose Custom Engineering)
get_template_part('template-parts/homePage/developer-bento');

/// Portfolios Slider
get_template_part('template-parts/homePage/portfolios-slider');

/// Call To Action
get_template_part('template-parts/homePage/callToAction');

/// About Us
get_template_part('template-parts/homePage/about-us');

/// Most Visited Posts / Blog Slider
get_template_part('template-parts/homePage/blog-slider');

/// Portfolio Last Posts
get_template_part('template-parts/portfolio/recently-portfolios');

get_footer();
