<?php get_header();

track_post_views(get_the_ID());

while (have_posts()) :
    the_post();
    $post_id = get_the_ID();
    $comment_count = get_comments_number(); 
    $categories = get_the_category();
    $primary_cat = !empty($categories) ? $categories[0] : null;
    
    // Ratings variables
    $total_ratings = get_post_meta($post_id, 'total_ratings', true) ?: 0;
    $total_rating_value = get_post_meta($post_id, 'total_rating_value', true) ?: 0;
    $average_rating = ($total_ratings > 0) ? round($total_rating_value / $total_ratings, 1) : 0;
    ?>

    <!-- Alpine.js single page context wrapper -->
    <section x-data="{ active: null, toc: false, share: false, stickyMenu: false }" 
             class="container mx-auto px-6 py-8 lg:py-16 text-start relative">
        
        <!-- Grid layout container -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-start">
            
            <!-- Breadcrumbs (Column span 12) -->
            <nav class="lg:col-span-12 flex text-xs font-bold text-slate-500 gap-2 items-center mb-2" aria-label="breadcrumb">
                <a href="<?= esc_url(home_url('/')); ?>" class="hover:text-secondary transition-colors">صفحه اصلی</a>
                <span class="text-slate-300">&gt;</span>
                <a href="<?= esc_url(get_permalink(get_option('page_for_posts')) ?: home_url('/blog/')); ?>" class="hover:text-secondary transition-colors">مقالات</a>
                <?php if ($primary_cat) : ?>
                    <span class="text-slate-300">&gt;</span>
                    <a href="<?= esc_url(get_category_link($primary_cat->term_id)); ?>" class="hover:text-secondary transition-colors"><?= esc_html($primary_cat->name); ?></a>
                <?php endif; ?>
                <span class="text-slate-300">&gt;</span>
                <span class="text-slate-400 truncate max-w-xs sm:max-w-md"><?= esc_html(get_the_title()); ?></span>
            </nav>

            <!-- Main post wrapper: lg:col-span-8 -->
            <div class="lg:col-span-8 flex flex-col gap-6">
                <!-- Premium Featured image with title overlay -->
                <div class="relative w-full">
                    <?php if (has_post_thumbnail()) : ?>
                        <picture class="block w-full">
                            <source media="(min-width: 961px)" srcset="<?php the_post_thumbnail_url('large'); ?>">
                            <source media="(max-width: 960px)" srcset="<?php the_post_thumbnail_url('medium-large'); ?>">
                            <img src="<?php the_post_thumbnail_url(); ?>" alt="<?php the_title_attribute(); ?>"
                                 class="object-cover w-full lg:rounded-3xl shadow-sm aspect-square lg:aspect-video select-none">
                        </picture>
                    <?php endif; ?>
                    
                    <!-- Floating title overlay -->
                    <h1 class="text-xl sm:text-2xl lg:text-3xl leading-[1.6] -mt-10 sm:-mt-14 mx-auto bg-white/95 p-5 sm:p-7 shadow-xl rounded-3xl text-center backdrop-blur-md w-[92%] sm:w-11/12 text-slate-900 font-black border border-slate-200/80 z-10 relative">
                        <?php the_title(); ?>
                    </h1>
                </div>

                <!-- Mobile Post Metadata Bar -->
                <div class="lg:hidden -mt-6 bg-slate-50 border border-slate-200/80 rounded-2xl p-4 flex flex-wrap items-center justify-between gap-3 text-xs text-slate-600 font-bold">
                    <span>نویسنده: <strong class="text-slate-900"><?= esc_html(get_the_author_meta('display_name')); ?></strong></span>
                    <span><?= shamsi_date('d F Y', get_the_time('U')); ?></span>
                    <span>زمان مطالعه: <?= reading_time(); ?> دقیقه</span>
                </div>

                <!-- Live TOC component on mobile/main body top -->
                <div class="lg:hidden mb-2">
                    <div class="bg-slate-50 border border-slate-200/80 p-5 rounded-2xl shadow-sm">
                        <button @click="toc = !toc" class="text-sm font-bold flex justify-between items-center cursor-pointer w-full text-slate-800">
                            <span>فهرست مطالب این مقاله</span>
                            <svg :class="toc ? 'rotate-180' : ''" width="16" height="16" fill="currentColor" class="bi bi-chevron-up transition-transform duration-300" viewBox="0 0 16 16">
                                <path fill-rule="evenodd" d="M7.646 4.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1-.708.708L8 5.707l-5.646 5.647a.5.5 0 0 1-.708-.708z"/>
                            </svg>
                        </button>
                        <div x-show="toc" x-collapse class="mt-4 pt-4 border-t border-dashed border-slate-200 prose prose-sm text-slate-600 leading-relaxed max-h-48 overflow-y-auto">
                            <?php echo do_shortcode('[TOC levels="2,3"]'); ?>
                        </div>
                    </div>
                </div>

                <!-- Rich content article body -->
                <article class="-mt-4 prose max-w-none text-slate-700 leading-loose text-justify py-4 entry-content prose-img:mx-auto prose-strong:text-slate-900 prose-headings:text-slate-900 prose-a:text-secondary">
                    <?= the_content(); ?>
                </article>

                <!-- Tags -->
                <?php
                $tags = get_the_tags();
                if ($tags) { ?>
                    <div class="flex flex-col gap-3 border-t border-slate-100 pt-6">
                        <p class="font-extrabold text-slate-800 text-sm">برچسب‌های این مقاله:</p>
                        <ul class="flex flex-wrap gap-2 items-center list-unstyled">
                            <li class="text-secondary">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M2 1a1 1 0 0 0-1 1v4.586a1 1 0 0 0 .293.707l7 7a1 1 0 0 0 1.414 0l4.586-4.586a1 1 0 0 0 0-1.414l-7-7A1 1 0 0 0 6.586 1H2zm4 3.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                                </svg>
                            </li>
                            <?php foreach ($tags as $tag) {
                                echo '<li><a href="' . esc_url(get_tag_link($tag->term_id)) . '" class="bg-secondary/15 text-secondary text-xs font-bold px-3 py-1.5 rounded-xl hover:bg-secondary/25 transition-all">'. $tag->name . '</a></li>';
                            } ?>
                        </ul>
                    </div>
                <?php } ?>

                <!-- Author info card -->
                <?php get_template_part('template-parts/cards/post-detail/author-information'); ?>

                <!-- Ratings section -->
                <div id="post-rating" class="p-6 bg-slate-50 border border-slate-200/80 rounded-2xl flex justify-between items-center my-4">
                    <p class="font-bold text-slate-800 text-sm mb-0">امتیاز خود به این مقاله را ثبت کنید:</p>
                    <div class="rating flex items-center gap-1.5 relative" x-data="{ ratingLoading: false }">
                        <div x-show="ratingLoading" class="absolute inset-0 bg-white/80 backdrop-blur-xs z-10 flex items-center justify-center rounded-lg" x-cloak>
                            <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-primary"></div>
                        </div>
                        <?php for ($i = 1; $i <= 5; $i++) : ?>
                            <button class="star-btn p-0.5 text-secondary hover:scale-110 transition-transform cursor-pointer" 
                                    data-rating="<?= $i; ?>"
                                    aria-label="Rate <?= $i; ?> Stars">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="<?= ($i <= round($average_rating)) ? 'currentColor' : 'none'; ?>" class="bi bi-star-fill stroke-secondary stroke-[1.25]" viewBox="0 0 16 16">
                                    <path d="M3.612 15.443c-.386.198-.824-.149-.746-.592l.83-4.73L.173 6.765c-.329-.314-.158-.888.283-.95l4.898-.696L7.538.792c.197-.39.73-.39.927 0l2.184 4.327 4.898.696c.441.062.612.636.282.95l-3.522 3.356.83 4.73c.078.443-.36.79-.746.592L8 13.187l-4.389 2.256z"/>
                                </svg>
                            </button>
                        <?php endfor; ?>
                    </div>
                </div>

                <!-- Comments list -->
                <div id="comments" class="mt-4">
                    <?php
                    if (comments_open() || get_comments_number()) :
                        comments_template();
                    endif;
                    ?>
                </div>
            </div>

            <!-- Sticky Sidebar: lg:col-span-4 -->
            <aside class="lg:col-span-4 flex flex-col gap-6 lg:sticky lg:top-24 max-lg:hidden">
                <!-- Post Information panel modeled after Gilllda -->
                <div class="bg-slate-50 border border-slate-200/80 rounded-2xl p-6 flex flex-col gap-4 text-sm text-slate-700">
                    <h3 class="font-extrabold text-slate-800 text-base border-b border-slate-200 pb-3 mb-2">مشخصات مقاله</h3>
                    
                    <!-- Last update date -->
                    <?php
                    $published_date = get_the_time('Y-m-d');
                    $modified_date = get_the_modified_time('Y-m-d');
                    if ($modified_date > $published_date) :
                        ?>
                        <div class="flex items-center gap-3 pb-3 border-b border-slate-200/60 text-emerald-600 font-bold">
                            <span class="rounded-lg p-1.5 bg-emerald-50 border border-emerald-200 shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-check2" viewBox="0 0 16 16">
                                    <path d="M13.854 3.646a.5.5 0 0 1 0 .708l-7 7a.5.5 0 0 1-.708 0l-3.5-3.5a.5.5 0 1 1 .708-.708L6.5 10.293l6.646-6.647a.5.5 0 0 1 .708 0z"/>
                                </svg>
                            </span>
                            <span class="text-xs">آخرین بروز رسانی: <?= shamsi_date('d F Y', get_the_modified_time('U')); ?></span>
                        </div>
                    <?php endif; ?>

                    <!-- Author name -->
                    <div class="flex items-center gap-3">
                        <span class="rounded-lg p-1.5 bg-slate-100 border border-slate-200 text-slate-500 shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M3 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1H3zm5-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/>
                            </svg>
                        </span>
                        <span class="text-xs">نویسنده: 
                            <a href="<?= esc_url(get_author_posts_url(get_the_author_meta('ID'))); ?>" class="font-extrabold text-slate-800 hover:text-secondary transition-colors">
                                <?= esc_html(get_the_author_meta('display_name')); ?>
                            </a>
                        </span>
                    </div>

                    <!-- Published date -->
                    <div class="flex items-center gap-3">
                        <span class="rounded-lg p-1.5 bg-slate-100 border border-slate-200 text-slate-500 shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M11 6.5a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1-.5-.5v-1z"/>
                                <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z"/>
                            </svg>
                        </span>
                        <span class="text-xs">تاریخ انتشار: <?= shamsi_date('d F Y', get_the_time('U')); ?></span>
                    </div>

                    <!-- Reading time -->
                    <div class="flex items-center gap-3">
                        <span class="rounded-lg p-1.5 bg-slate-100 border border-slate-200 text-slate-500 shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                <path d="M7.5 3a.5.5 0 0 1 .5.5v5.21l3.248 1.856a.5.5 0 0 1-.496.868l-3.5-2A.5.5 0 0 1 7 9V3.5a.5.5 0 0 1 .5-.5z"/>
                            </svg>
                        </span>
                        <span class="text-xs">زمان مطالعه: <?= reading_time(); ?> دقیقه</span>
                    </div>

                    <!-- Category tags -->
                    <div class="flex items-center gap-3">
                        <span class="rounded-lg p-1.5 bg-slate-100 border border-slate-200 text-slate-500 shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M2 2a1 1 0 0 1 1-1h4.586a1 1 0 0 1 .707.293l7 7a1 1 0 0 1 0 1.414l-4.586 4.586a1 1 0 0 1-1.414 0l-7-7A1 1 0 0 1 2 6.586V2zm3.5 3.5a1.5 1.5 0 1 0-3 0 1.5 1.5 0 0 0 3 0z"/>
                            </svg>
                        </span>
                        <div class="text-xs flex flex-wrap gap-2">
                            <span>دسته‌بندی:</span>
                            <?php foreach ($categories as $cat) : ?>
                                <a href="<?= get_term_link($cat); ?>" class="font-extrabold text-slate-800 hover:underline"><?= $cat->name; ?></a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Divider -->
                    <div class="border-t border-slate-200/60 my-2"></div>

                    <!-- Interactive Stats block (message, eye, rating click scroll) -->
                    <div class="grid grid-cols-3 divide-x divide-x-reverse divide-slate-200 bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
                        <button @click.prevent="document.getElementById('comments').scrollIntoView({ behavior: 'smooth' })"
                                class="flex items-center justify-center gap-2 py-3 hover:bg-slate-50 cursor-pointer text-slate-400 text-xs">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                            <span><?= $comment_count; ?></span>
                        </button>
                        
                        <div class="flex items-center justify-center gap-2 py-3 text-slate-400 text-xs">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <span><?= get_post_meta($post_id, 'post_views', true) ?: '0'; ?></span>
                        </div>

                        <button @click.prevent="document.getElementById('post-rating').scrollIntoView({ behavior: 'smooth' })"
                                class="flex items-center justify-center gap-1.5 py-3 hover:bg-slate-50 cursor-pointer text-slate-400 text-xs">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="currentColor" class="text-amber-400" viewBox="0 0 16 16">
                                <path d="M3.612 15.443c-.386.198-.824-.149-.746-.592l.83-4.73L.173 6.765c-.329-.314-.158-.888.283-.95l4.898-.696L7.538.792c.197-.39.73-.39.927 0l2.184 4.327 4.898.696c.441.062.612.636.282.95l-3.522 3.356.83 4.73c.078.443-.36.79-.746.592L8 13.187l-4.389 2.256z"/>
                            </svg>
                            <span><?= $average_rating; ?>/۵</span>
                        </button>
                    </div>
                </div>

                <!-- Desktop TOC Widget -->
                <div class="bg-white border border-slate-200/80 p-5 rounded-2xl shadow-sm">
                    <h4 class="font-extrabold text-slate-800 text-sm border-b border-slate-200 pb-3 mb-4">فهرست مطالب این مقاله</h4>
                    <div class="prose prose-sm text-slate-600 leading-relaxed max-h-48 overflow-y-auto">
                        <?php echo do_shortcode('[TOC levels="2,3"]'); ?>
                    </div>
                </div>

                <!-- Related posts links -->
                <?php get_template_part('template-parts/loop/post-sidebar'); ?>
            </aside>
        </div>

        <!-- Alpine.js sticky bottom action bar (mobile only) modeled after Gilllda -->
        <div :class="[stickyMenu ? 'bottom-24' : '-bottom-20 opacity-0 pointer-events-none', scrollingDown ? 'max-lg:scale-95 translate-y-2' : '']"
             class="lg:hidden duration-500 transition-all fixed rounded-2xl divide-x divide-x-reverse divide-slate-200 inset-x-4 bg-white/95 border border-slate-300/80 backdrop-blur-md z-40 py-1.5 flex items-center shadow-xl text-slate-700">
            <button @click="share = !share" class="flex-1 flex items-center justify-center py-2 gap-2 text-xs font-bold cursor-pointer hover:bg-slate-50">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.684 10.742l4.636-2.318M8.684 13.258l4.636 2.318M21 12a3 3 0 11-6 0 3 3 0 016 0zm-11 5a3 3 0 11-6 0 3 3 0 016 0zM10 7a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span>اشتراک‌گذاری</span>
            </button>
            <button @click="toc = !toc" class="flex-1 flex items-center justify-center py-2 gap-2 text-xs font-bold cursor-pointer hover:bg-slate-50">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h7" />
                </svg>
                <span>فهرست مطالب</span>
            </button>
            <button @click.prevent="document.getElementById('comments').scrollIntoView({ behavior: 'smooth' })"
                    class="flex-1 flex items-center justify-center py-2 gap-2 text-xs font-bold cursor-pointer hover:bg-slate-50">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
                <span class="flex items-center gap-1">
                    <span><?= $comment_count; ?></span>
                    <span>دیدگاه</span>
                </span>
            </button>
            
            <!-- Share floating overlays (mobile) -->
            <div x-show="share" x-collapse
                 class="absolute bottom-16 inset-x-0 bg-white/95 border border-slate-200 p-4 rounded-xl flex flex-col gap-2 z-50">
                <div class="flex justify-between items-center border-b border-slate-100 pb-2 mb-2">
                    <span class="text-xs font-bold text-slate-800">اشتراک‌گذاری مقاله</span>
                    <button @click="share = false" class="text-slate-400 p-1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
                        </svg>
                    </button>
                </div>
                <?php
                get_template_part('template-parts/share-button', null, array(
                    'headingClass' => 'hidden',
                    'containerCLass' => '!my-0',
                    'class' => 'gap-2 justify-center',
                ));
                ?>
            </div>
        </div>

        <!-- Sticky Toggle dots button (mobile only) -->
        <button @click="stickyMenu = !stickyMenu"
                aria-label="Toggle Details Menu"
                :class="[stickyMenu ? 'bottom-40' : 'bottom-24', scrollingDown ? 'scale-90 translate-y-2' : '']"
                class="size-11 lg:hidden fixed start-6 bg-white border border-slate-200 transition-all duration-300 flex items-center justify-center text-primary rounded-full z-40 shadow-lg cursor-pointer hover:bg-slate-100">
            <svg x-show="!stickyMenu" xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                <path d="M3 9.5a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3z"/>
            </svg>
            <svg x-show="stickyMenu" x-cloak xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
            </svg>
        </button>

        <!-- Services Section bottom -->
        <div class="border-t border-slate-200 mt-16 pt-16">
            <div class="text-center max-w-2xl mx-auto mb-12">
                <h4 class="text-2xl lg:text-3xl font-black text-slate-900 mb-4" data-aos="fade-down">خدمات ما</h4>
                <div class="h-1.5 w-12 bg-secondary mx-auto rounded-full"></div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php
                $args = array(
                    'post_type' => 'services',
                    'post_status' => 'publish',
                    'order' => 'DESC',
                    'posts_per_page' => '3',
                    'ignore_sticky_posts' => true
                );
                $loop = new WP_Query($args);
                if ($loop->have_posts()) :
                    while ($loop->have_posts()) : $loop->the_post(); ?>
                        <div data-aos="zoom-out" class="h-full">
                            <?php get_template_part('template-parts/services/services-card'); ?>
                        </div>
                    <?php endwhile;
                endif;
                wp_reset_postdata(); ?>
            </div>
        </div>
    </section>
<?php endwhile;
wp_reset_query();
get_footer(); ?>
