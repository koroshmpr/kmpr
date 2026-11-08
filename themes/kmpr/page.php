<?php
/**
 * Default page template.
 *
 * @package KMPR
 */

get_header();

while (have_posts()) :
    the_post();
    ?>
    <div class="relative isolate overflow-x-clip bg-slate-950">
        <header class="relative isolate pt-14 pb-10 lg:pt-20 lg:pb-14 text-center">
            <div class="absolute inset-0 hero-grid -z-10" aria-hidden="true"></div>
            <div class="aurora w-[28rem] h-[28rem] -top-48 start-1/3 bg-secondary/20 -z-10" aria-hidden="true"></div>
            <div class="container mx-auto px-4 sm:px-6 max-w-4xl">
                <nav aria-label="مسیر صفحه" class="mb-5" data-aos="fade-down">
                    <ol class="inline-flex flex-wrap items-center justify-center gap-2 text-xs sm:text-sm text-slate-400">
                        <li><a href="<?= esc_url(home_url('/')); ?>" class="inline-flex items-center min-h-11 hover:text-secondary transition-colors">صفحه اصلی</a></li>
                        <li aria-hidden="true">/</li>
                        <li class="text-slate-200 font-bold" aria-current="page"><?= esc_html(get_the_title()); ?></li>
                    </ol>
                </nav>
                <h1 class="text-3xl sm:text-5xl font-black text-white leading-tight" data-split><?= esc_html(get_the_title()); ?></h1>
                <p class="mt-4 text-sm text-slate-400">آخرین به‌روزرسانی: <time datetime="<?= esc_attr(get_the_modified_date('c')); ?>"><?= esc_html(function_exists('shamsi_date') ? shamsi_date('j F Y', get_the_modified_time('U')) : get_the_modified_date()); ?></time></p>
            </div>
        </header>

        <section class="pb-16 lg:pb-24">
            <div class="container mx-auto px-4 sm:px-6">
                <article id="post-<?php the_ID(); ?>" <?php post_class('max-w-3xl mx-auto rounded-3xl bg-white/[0.025] border border-white/10 p-6 sm:p-10 lg:p-12 text-start text-slate-300 leading-loose text-base [&_h2]:text-2xl [&_h2]:font-black [&_h2]:text-white [&_h2]:mt-10 [&_h2]:mb-4 [&_h3]:text-xl [&_h3]:font-black [&_h3]:text-white [&_h3]:mt-8 [&_h3]:mb-3 [&_p]:mb-5 [&_ul]:list-disc [&_ul]:ps-6 [&_ul]:mb-5 [&_ol]:list-decimal [&_ol]:ps-6 [&_ol]:mb-5 [&_li]:mb-2 [&_strong]:text-white [&_a]:text-secondary [&_a:hover]:underline [&_img]:rounded-2xl [&_img]:border [&_img]:border-white/10 [&_img]:h-auto [&_blockquote]:border-s-4 [&_blockquote]:border-secondary [&_blockquote]:ps-5 [&_blockquote]:text-slate-200'); ?> data-aos="fade-up">
                    <?php the_content(); ?>
                </article>

                <?php if (comments_open() || get_comments_number()) : ?>
                    <div class="max-w-3xl mx-auto mt-10">
                        <?php comments_template(); ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>
    <?php
endwhile;

get_footer();
