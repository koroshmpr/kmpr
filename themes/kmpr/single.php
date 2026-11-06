<?php
/**
 * Single Post Template - Dark Obsidian Tech Blog Edition
 * Personal Brand: Korosh Mpr | Senior Software Architect & Full-Stack Developer
 */

get_header();

track_post_views(get_the_ID());

while (have_posts()) :
    the_post();
    $post_id            = get_the_ID();
    $comment_count      = get_comments_number(); 
    $categories         = get_the_category();
    $primary_cat        = !empty($categories) ? $categories[0] : null;
    $prev_post          = get_previous_post();
    $next_post          = get_next_post();
    
    // Ratings variables
    $total_ratings      = get_post_meta($post_id, 'total_ratings', true) ?: 0;
    $total_rating_value = get_post_meta($post_id, 'total_rating_value', true) ?: 0;
    $average_rating     = ($total_ratings > 0) ? round($total_rating_value / $total_ratings, 1) : 0;
?>

<!-- Alpine.js single page context wrapper -->
<div x-data="{ 
        active: null, 
        toc: false, 
        share: false, 
        stickyMenu: false,
        progress: 0,
        updateProgress() {
            const winScroll = document.documentElement.scrollTop || document.body.scrollTop;
            const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
            this.progress = height > 0 ? (winScroll / height) * 100 : 0;
        }
     }" 
     @scroll.window="updateProgress()"
     class="single-article-wrapper min-h-screen bg-slate-950 text-slate-100 py-8 lg:py-14 selection:bg-secondary/30 selection:text-secondary relative overflow-hidden">
    
    <!-- Top Reading Progress Indicator -->
    <div class="fixed top-0 start-0 h-1 bg-gradient-to-r from-secondary to-amber-400 z-50 transition-all duration-150 pointer-events-none"
         :style="'width: ' + progress + '%'"></div>

    <!-- Subtle Ambient Glow Spheres -->
    <div class="absolute top-20 start-1/4 w-96 h-96 bg-secondary/10 rounded-full blur-[140px] pointer-events-none -z-0"></div>
    <div class="absolute top-1/2 end-10 w-96 h-96 bg-blue-600/10 rounded-full blur-[140px] pointer-events-none -z-0"></div>

    <div class="container mx-auto px-4 sm:px-6 max-w-6xl relative z-10 text-start">
        
        <!-- Breadcrumbs Navigation -->
        <nav class="flex flex-wrap items-center text-xs font-bold text-slate-400 gap-2 mb-8 pb-4 border-b border-white/10" aria-label="breadcrumb">
            <a href="<?= esc_url(home_url('/')); ?>" class="hover:text-secondary transition-colors">صفحه اصلی</a>
            <span class="text-slate-600">&gt;</span>
            <a href="<?= esc_url(get_permalink(get_option('page_for_posts')) ?: home_url('/blog/')); ?>" class="hover:text-secondary transition-colors">مقالات و آموزش‌ها</a>
            <?php if ($primary_cat) : ?>
                <span class="text-slate-600">&gt;</span>
                <a href="<?= esc_url(get_category_link($primary_cat->term_id)); ?>" class="text-secondary hover:underline"><?= esc_html($primary_cat->name); ?></a>
            <?php endif; ?>
            <span class="text-slate-600">&gt;</span>
            <span class="text-slate-300 truncate max-w-xs sm:max-w-md"><?= esc_html(get_the_title()); ?></span>
        </nav>

        <!-- Main Layout Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-10 items-start">
            
            <!-- Col 8: Article Main Body -->
            <main class="lg:col-span-8 flex flex-col gap-6">
                
                <!-- Hero Featured Image & Title Box -->
                <div class="relative w-full">
                    <?php if (has_post_thumbnail()) : ?>
                        <div class="relative overflow-hidden aspect-[16/9] rounded-3xl bg-slate-900 border border-white/10 shadow-2xl">
                            <img src="<?php the_post_thumbnail_url('large'); ?>" 
                                 alt="<?php the_title_attribute(); ?>"
                                 class="w-full h-full object-cover select-none pointer-events-none">
                            <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-transparent to-transparent pointer-events-none"></div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Floating Title Card -->
                    <div class="relative -mt-10 sm:-mt-14 mx-auto bg-slate-900/90 border border-white/15 p-6 sm:p-8 shadow-2xl rounded-3xl backdrop-blur-2xl w-[94%] sm:w-11/12 text-start z-10 space-y-4">
                        
                        <!-- Badges Row -->
                        <div class="flex flex-wrap items-center gap-2">
                            <?php if ($primary_cat) : ?>
                                <a href="<?= esc_url(get_category_link($primary_cat->term_id)); ?>" 
                                   class="text-xs font-bold px-3 py-1 rounded-full bg-secondary/15 text-secondary border border-secondary/30 hover:bg-secondary/25 transition-colors">
                                    <?= esc_html($primary_cat->name); ?>
                                </a>
                            <?php endif; ?>
                            <span class="text-xs font-mono px-3 py-1 rounded-full bg-white/[0.04] text-slate-300 border border-white/10">
                                زمان مطالعه: <?= reading_time(); ?> دقیقه
                            </span>
                        </div>

                        <!-- Post Title -->
                        <h1 class="text-xl sm:text-2xl lg:text-3xl font-black text-white leading-tight sm:leading-snug tracking-tight">
                            <?php the_title(); ?>
                        </h1>

                        <!-- Author & Date Metadata Row -->
                        <div class="pt-3 border-t border-white/10 flex flex-wrap items-center justify-between gap-3 text-xs text-slate-400 font-medium">
                            <div class="flex items-center gap-2">
                                <span class="size-2 rounded-full bg-emerald-400"></span>
                                <span>نویسنده: <strong class="text-white"><?= esc_html(get_the_author_meta('display_name')); ?></strong></span>
                            </div>
                            <div class="flex items-center gap-4">
                                <span>انتشار: <?= shamsi_date('d F Y', get_the_time('U')); ?></span>
                                <span class="text-slate-600">•</span>
                                <span>بازدید: <?= get_post_meta($post_id, 'post_views', true) ?: '1'; ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mobile Collapsible Table of Contents -->
                <div class="lg:hidden">
                    <div class="bg-slate-900/80 border border-white/10 p-5 rounded-2xl shadow-md backdrop-blur-md">
                        <button @click="toc = !toc" class="text-sm font-black flex justify-between items-center cursor-pointer w-full text-white">
                            <span class="flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="text-secondary shrink-0" viewBox="0 0 16 16">
                                    <path fill-rule="evenodd" d="M2.5 12a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4.172a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5z"/>
                                </svg>
                                <span>فهرست سرفصل‌های این مقاله</span>
                            </span>
                            <svg :class="toc ? 'rotate-180' : ''" width="16" height="16" fill="currentColor" class="transition-transform duration-300 text-slate-400" viewBox="0 0 16 16">
                                <path fill-rule="evenodd" d="M7.646 4.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1-.708.708L8 5.707l-5.646 5.647a.5.5 0 0 1-.708-.708z"/>
                            </svg>
                        </button>
                        <div x-show="toc" x-collapse class="mt-4 pt-4 border-t border-white/10 text-slate-300 leading-relaxed max-h-60 overflow-y-auto pe-1.5 scroll-smooth">
                            <?php echo do_shortcode('[TOC levels="2,3"]'); ?>
                        </div>
                    </div>
                </div>

                <!-- Rich Content Article Body (Strictly High Contrast for Dark Theme) -->
                <article class="bg-slate-900/60 border border-white/10 rounded-3xl p-6 sm:p-10 backdrop-blur-xl shadow-2xl prose prose-invert prose-slate max-w-none text-slate-200 leading-relaxed sm:leading-loose text-justify [&_h2]:text-white [&_h2]:font-black [&_h2]:text-xl sm:[&_h2]:text-2xl [&_h2]:mt-8 [&_h2]:mb-4 [&_h3]:text-secondary [&_h3]:font-black [&_h3]:text-lg [&_p]:text-slate-200 [&_p]:mb-5 [&_p]:leading-loose [&_strong]:text-white [&_strong]:font-black [&_ul]:list-disc [&_ul]:list-inside [&_ul]:space-y-2 [&_li]:text-slate-200 [&_img]:rounded-2xl [&_img]:border [&_img]:border-white/10 [&_img]:shadow-2xl [&_img]:my-6 [&_a]:text-secondary [&_a:hover]:underline">
                    <?= the_content(); ?>
                </article>

                <!-- High-Converting Engineering CTA Box (Lead Capture Booster) -->
                <div class="p-6 sm:p-8 rounded-3xl bg-gradient-to-br from-slate-900 to-slate-950 border border-secondary/30 shadow-2xl relative overflow-hidden">
                    <div class="absolute -top-12 -end-12 w-48 h-48 bg-secondary/15 rounded-full blur-3xl pointer-events-none"></div>
                    <div class="relative z-10 flex flex-col sm:flex-row items-center sm:items-start justify-between gap-6 text-center sm:text-start">
                        <div class="space-y-2">
                            <span class="text-xs font-mono text-secondary uppercase font-bold tracking-wider block">همکاری اختصاصی با کوروش محمدپور</span>
                            <h3 class="text-lg sm:text-xl font-black text-white">نیاز به مشاوره معماری نرم‌افزار یا بازنویسی وب‌سایت دارید؟</h3>
                            <p class="text-xs sm:text-sm text-slate-300 max-w-xl leading-relaxed">
                                اگر به دنبال توسعه پلتفرمی فوق‌سریع، با کدنویسی تمیز، نمره ۱۰۰ در Core Web Vitals و بدون باگ‌های قالب‌های سنگین هستید، مستقیماً با من گفتگو کنید.
                            </p>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-3 shrink-0">
                            <a href="<?= esc_url(home_url('/contact-us') ?: home_url('/contact')); ?>" 
                               class="bg-secondary hover:bg-secondary-hover text-white text-xs sm:text-sm font-black px-6 py-3 rounded-2xl transition-all shadow-lg shadow-secondary/25 hover:scale-105 active:scale-95 whitespace-nowrap">
                                شروع مشاوره و همکاری
                            </a>
                            <a href="<?= esc_url(home_url('/resume/')); ?>" 
                               class="bg-white/[0.05] hover:bg-white/[0.1] text-slate-200 hover:text-white border border-white/15 text-xs sm:text-sm font-bold px-4 py-3 rounded-2xl transition-all whitespace-nowrap">
                                مشاهده رزومه آنلاین
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Tags List -->
                <?php
                $tags = get_the_tags();
                if ($tags) : ?>
                    <div class="flex flex-col gap-3 p-6 rounded-2xl bg-slate-900/60 border border-white/10">
                        <p class="font-black text-white text-xs uppercase tracking-wider">برچسب‌های تخصصی این مقاله:</p>
                        <ul class="flex flex-wrap gap-2 items-center list-none p-0 m-0">
                            <?php foreach ($tags as $tag) : ?>
                                <li>
                                    <a href="<?= esc_url(get_tag_link($tag->term_id)); ?>" 
                                       class="bg-white/[0.04] hover:bg-secondary/20 text-slate-300 hover:text-secondary border border-white/10 hover:border-secondary/40 text-xs font-bold px-3 py-1.5 rounded-xl transition-all">
                                        #<?= esc_html($tag->name); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- Author Bio Card -->
                <?php get_template_part('template-parts/cards/post-detail/author-information'); ?>

                <!-- Rating Section -->
                <div id="post-rating" class="p-6 bg-slate-900/80 border border-white/10 rounded-2xl flex flex-col sm:flex-row justify-between items-center gap-4">
                    <p class="font-bold text-slate-200 text-xs sm:text-sm mb-0">امتیاز شما به کیفیت علمی و محتوای این مقاله:</p>
                    <div class="rating flex items-center gap-1.5 relative" x-data="{ ratingLoading: false }">
                        <div x-show="ratingLoading" class="absolute inset-0 bg-slate-950/80 backdrop-blur-xs z-10 flex items-center justify-center rounded-lg" x-cloak>
                            <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-secondary"></div>
                        </div>
                        <?php for ($i = 1; $i <= 5; $i++) : ?>
                            <button class="star-btn p-1 text-secondary hover:scale-125 transition-transform cursor-pointer" 
                                    data-rating="<?= $i; ?>"
                                    aria-label="Rate <?= $i; ?> Stars">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="<?= ($i <= round($average_rating)) ? 'currentColor' : 'none'; ?>" class="stroke-secondary stroke-[1.25]" viewBox="0 0 16 16">
                                    <path d="M3.612 15.443c-.386.198-.824-.149-.746-.592l.83-4.73L.173 6.765c-.329-.314-.158-.888.283-.95l4.898-.696L7.538.792c.197-.39.73-.39.927 0l2.184 4.327 4.898.696c.441.062.612.636.282.95l-3.522 3.356.83 4.73c.078.443-.36.79-.746.592L8 13.187l-4.389 2.256z"/>
                                </svg>
                            </button>
                        <?php endfor; ?>
                        <span class="text-xs font-mono text-slate-400 ms-2 font-bold"><?= $average_rating; ?> / ۵</span>
                    </div>
                </div>

                <!-- Adjacent Articles Switcher (Previous / Next) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 my-2">
                    <?php if (!empty($prev_post)) : ?>
                        <a href="<?= esc_url(get_permalink($prev_post->ID)); ?>" 
                           class="p-5 rounded-2xl bg-slate-900/60 hover:bg-slate-900 border border-white/10 hover:border-secondary/40 transition-all flex items-center gap-3.5 text-start group">
                            <span class="size-9 rounded-xl bg-secondary/15 text-secondary flex items-center justify-center shrink-0 group-hover:-translate-x-1 transition-transform">
                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <polyline points="15 18 9 12 15 6"></polyline>
                                </svg>
                            </span>
                            <div class="overflow-hidden">
                                <span class="text-[10px] font-mono text-slate-400 block">مقاله قبلی</span>
                                <h4 class="text-xs sm:text-sm font-black text-white group-hover:text-secondary transition-colors truncate"><?= esc_html(get_the_title($prev_post->ID)); ?></h4>
                            </div>
                        </a>
                    <?php else : ?>
                        <div></div>
                    <?php endif; ?>

                    <?php if (!empty($next_post)) : ?>
                        <a href="<?= esc_url(get_permalink($next_post->ID)); ?>" 
                           class="p-5 rounded-2xl bg-slate-900/60 hover:bg-slate-900 border border-white/10 hover:border-secondary/40 transition-all flex items-center justify-between gap-3.5 text-end group">
                            <div class="overflow-hidden">
                                <span class="text-[10px] font-mono text-slate-400 block">مقاله بعدی</span>
                                <h4 class="text-xs sm:text-sm font-black text-white group-hover:text-secondary transition-colors truncate"><?= esc_html(get_the_title($next_post->ID)); ?></h4>
                            </div>
                            <span class="size-9 rounded-xl bg-secondary/15 text-secondary flex items-center justify-center shrink-0 group-hover:translate-x-1 transition-transform">
                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <polyline points="9 18 15 12 9 6"></polyline>
                                </svg>
                            </span>
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Comments Section -->
                <div id="comments" class="mt-4">
                    <?php
                    if (comments_open() || get_comments_number()) :
                        comments_template();
                    endif;
                    ?>
                </div>

            </main>

            <!-- Col 4: Sticky Sidebar (Desktop) -->
            <aside class="lg:col-span-4 flex flex-col gap-6 lg:sticky lg:top-24 max-lg:hidden text-start">
                
                <!-- Article Meta Details Card -->
                <div class="bg-slate-900/80 border border-white/10 rounded-3xl p-6 flex flex-col gap-4 text-xs text-slate-300 backdrop-blur-xl shadow-xl">
                    <h3 class="font-black text-white text-sm border-b border-white/10 pb-3 flex items-center gap-2">
                        <span class="size-2 rounded-full bg-secondary"></span>
                        <span>شناسنامه و آمار مقاله</span>
                    </h3>
                    
                    <!-- Modified Date -->
                    <?php
                    $published_date = get_the_time('Y-m-d');
                    $modified_date  = get_the_modified_time('Y-m-d');
                    if ($modified_date > $published_date) : ?>
                        <div class="flex items-center gap-3 pb-3 border-b border-white/5 text-emerald-400 font-bold">
                            <span class="size-2 rounded-full bg-emerald-400"></span>
                            <span>آخرین بازبینی فنی: <?= shamsi_date('d F Y', get_the_modified_time('U')); ?></span>
                        </div>
                    <?php endif; ?>

                    <!-- Author -->
                    <div class="flex items-center justify-between pb-2.5 border-b border-white/5">
                        <span class="text-slate-400">نویسنده:</span>
                        <span class="font-black text-white"><?= esc_html(get_the_author_meta('display_name')); ?></span>
                    </div>

                    <!-- Reading Time -->
                    <div class="flex items-center justify-between pb-2.5 border-b border-white/5">
                        <span class="text-slate-400">زمان تخمینی مطالعه:</span>
                        <span class="font-bold text-white font-mono"><?= reading_time(); ?> دقیقه</span>
                    </div>

                    <!-- Views -->
                    <div class="flex items-center justify-between pb-2.5 border-b border-white/5">
                        <span class="text-slate-400">تعداد بازدید:</span>
                        <span class="font-bold text-white font-mono"><?= get_post_meta($post_id, 'post_views', true) ?: '1'; ?></span>
                    </div>

                    <!-- Comments count -->
                    <div class="flex items-center justify-between">
                        <span class="text-slate-400">دیدگاه‌های ثبت‌شده:</span>
                        <span class="font-bold text-secondary font-mono"><?= $comment_count; ?> دیدگاه</span>
                    </div>
                </div>

                <!-- Sticky Table of Contents (Desktop) -->
                <div class="bg-slate-900/80 border border-white/10 p-6 rounded-3xl shadow-xl backdrop-blur-xl">
                    <div class="flex items-center justify-between border-b border-white/10 pb-3 mb-4">
                        <h4 class="font-black text-white text-xs uppercase tracking-wider flex items-center gap-2">
                            <svg class="size-4 text-secondary shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                                <line x1="8" y1="6" x2="21" y2="6"></line>
                                <line x1="8" y1="12" x2="21" y2="12"></line>
                                <line x1="8" y1="18" x2="21" y2="18"></line>
                                <line x1="3" y1="6" x2="3.01" y2="6"></line>
                                <line x1="3" y1="12" x2="3.01" y2="12"></line>
                                <line x1="3" y1="18" x2="3.01" y2="18"></line>
                            </svg>
                            <span>فهرست سرفصل‌های مقاله</span>
                        </h4>
                        <span class="text-[10px] font-mono font-bold text-secondary bg-secondary/15 px-2 py-0.5 rounded-full">TOC</span>
                    </div>
                    <div class="text-slate-300 text-xs leading-relaxed max-h-72 overflow-y-auto pe-1.5 scroll-smooth [&_a]:text-slate-300 [&_a:hover]:text-secondary [&_ul]:space-y-2 [&_li]:leading-normal">
                        <?php echo do_shortcode('[TOC levels="2,3"]'); ?>
                    </div>
                </div>

                <!-- Quick Consultation CTA in Sidebar -->
                <div class="bg-slate-900/80 border border-white/10 rounded-3xl p-6 text-center space-y-3 backdrop-blur-xl">
                    <span class="size-10 rounded-2xl bg-secondary/20 text-secondary mx-auto flex items-center justify-center font-black">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                        </svg>
                    </span>
                    <h5 class="text-sm font-black text-white">مشاوره اختصاصی با معمار نرم‌افزار</h5>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        برای ارزیابی و مشاوره فنی پروژه وب‌سایت خود، مستقیماً تماس بگیرید یا در واتساپ پیام دهید.
                    </p>
                    <a href="<?= esc_url(home_url('/contact-us') ?: home_url('/contact')); ?>" 
                       class="inline-block w-full bg-secondary hover:bg-secondary-hover text-white text-xs font-black py-2.5 rounded-xl transition-all shadow-md">
                        ارتباط مستقیم
                    </a>
                </div>

                <!-- Loop Sidebar (Recent & Recommended) -->
                <?php get_template_part('template-parts/loop/post-sidebar'); ?>

            </aside>

        </div>

    </div>

</div>

<?php endwhile;
wp_reset_query();
get_footer(); ?>
