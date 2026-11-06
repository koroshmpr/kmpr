<?php get_header(); ?>

<?php
$title = single_cat_title('', false);
$args = array(
    'title' => $title,
);
get_template_part('template-parts/loop/page-title', null, $args); ?>

<section class="container mx-auto px-4 sm:px-6 py-12 text-start">
    <?php if ( have_posts() ) : ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php while ( have_posts() ) : the_post(); ?>
                <div class="h-full">
                    <?php get_template_part('template-parts/cards/title-under-image'); ?>
                </div>
            <?php endwhile; ?>
        </div>

        <!-- Pagination -->
        <div class="flex justify-center items-center gap-2 mt-16 py-6 border-t border-slate-100">
            <?php
            $big = 999999999;
            echo paginate_links(array(
                'base'      => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
                'format'    => '?paged=%#%',
                'current'   => max(1, get_query_var('paged')),
                'total'     => $wp_query->max_num_pages,
                'prev_text' => '&rarr; قبلی',
                'next_text' => 'بعدی &larr;',
                'type'      => 'plain',
                'class'     => 'flex gap-2'
            ));
            ?>
        </div>
    <?php else : ?>
        <div class="py-16 text-center max-w-md mx-auto">
            <p class="font-bold text-lg text-slate-500">با عرض پوزش، هیچ مقاله‌ای در این دسته‌بندی یافت نشد.</p>
            <a href="<?= esc_url(home_url('/')); ?>" class="mt-4 inline-block bg-primary text-white text-xs font-bold px-6 py-2.5 rounded-full">
                بازگشت به صفحه اصلی
            </a>
        </div>
    <?php endif; ?>
</section>

<?php get_footer(); ?>
