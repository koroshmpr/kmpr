<?php
/** Template Name: default pages */

get_header(); ?>
<section class="container mx-auto px-6 py-12 text-start">
    <article class="prose max-w-none text-slate-700 leading-loose text-justify">
        <?php the_content(); ?>
    </article>
</section>
<?php get_footer(); ?>
