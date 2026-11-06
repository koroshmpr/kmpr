<?php
/**
 * The template for displaying all default pages
 */

get_header();

while (have_posts()) :
    the_post();
    get_template_part('template-parts/loop/page-title', null, array(
        'title' => get_the_title(),
    ));
    ?>
    <section class="container mx-auto px-6 py-12 lg:py-16 text-start">
        <article id="post-<?php the_ID(); ?>" <?php post_class('prose max-w-4xl mx-auto text-slate-700 leading-loose text-justify bg-white border border-slate-200/80 rounded-3xl p-6 lg:p-12 shadow-sm'); ?>>
            <?php the_content(); ?>
        </article>

        <?php if (comments_open() || get_comments_number()) : ?>
            <div class="max-w-4xl mx-auto mt-10">
                <?php comments_template(); ?>
            </div>
        <?php endif; ?>
    </section>
    <?php
endwhile;

get_footer();
