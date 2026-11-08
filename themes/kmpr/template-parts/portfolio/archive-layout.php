<?php
/**
 * Shared layout for the portfolio archive and portfolio_categories term archives.
 * Uses the main query (paginated in inc/acf-portfolio-extra.php).
 *
 * Args: term (WP_Term|null)
 */
global $wp_query;

$term        = $args['term'] ?? null;
$archive_url = get_post_type_archive_link('portfolio') ?: home_url('/portfolios/');
$categories  = get_terms(array('taxonomy' => 'portfolio_categories', 'hide_empty' => true));
$categories  = is_wp_error($categories) ? array() : $categories;
$total_all   = (int) (wp_count_posts('portfolio')->publish ?? 0);
$found       = (int) $wp_query->found_posts;
$paged       = max(1, (int) get_query_var('paged'));
$live_sites  = 0;
foreach (get_posts(array('post_type' => 'portfolio', 'posts_per_page' => -1, 'fields' => 'ids', 'no_found_rows' => true, 'tax_query' => $term ? array(array('taxonomy' => 'portfolio_categories', 'terms' => $term->term_id)) : array())) as $pid) {
    $l = function_exists('get_field') ? get_field('portfolio-website', $pid) : '';
    $u = is_array($l) ? ($l['url'] ?? '') : (string) $l;
    if ($u && strpos($u, 'localhost') === false) {
        $live_sites++;
    }
}

$title = $term ? 'نمونه‌کارهای ' . $term->name : kmpr_pf_opt('pfa_title');
$intro = $term && trim(wp_strip_all_tags(term_description($term))) ? wp_strip_all_tags(term_description($term)) : kmpr_pf_opt('pfa_text');

// Collage of the three latest projects that have a real cover.
$collage = array();
if ($paged === 1) {
    $recent = get_posts(array(
        'post_type'      => 'portfolio',
        'posts_per_page' => 8,
        'fields'         => 'ids',
        'tax_query'      => $term ? array(array('taxonomy' => 'portfolio_categories', 'terms' => $term->term_id)) : array(),
    ));
    foreach ($recent as $rid) {
        $c = kmpr_portfolio_cover($rid, 'medium_large', 'laptop');
        if ($c) {
            $collage[] = array('cover' => $c, 'title' => get_the_title($rid));
        }
        if (count($collage) === 3) {
            break;
        }
    }
}
?>
<div class="bg-slate-950 text-white overflow-x-clip">

    <!-- Hero -->
    <section class="relative isolate border-b border-white/10 pt-10 pb-16 lg:pt-14 lg:pb-24" aria-labelledby="pf-archive-title">
        <div class="absolute inset-0 hero-grid -z-10" aria-hidden="true"></div>
        <div class="aurora w-[26rem] h-[26rem] -top-20 start-0 bg-secondary/25 -z-10" aria-hidden="true"></div>
        <div class="aurora w-[24rem] h-[24rem] top-1/3 end-0 bg-indigo-600/20 -z-10" aria-hidden="true"></div>

        <div class="container mx-auto px-4 sm:px-6">
            <nav aria-label="مسیر صفحه" class="mb-8 lg:mb-12">
                <ol class="flex flex-wrap items-center gap-2 text-xs font-bold text-slate-400">
                    <li><a href="<?= esc_url(home_url('/')); ?>" class="hover:text-secondary transition-colors">خانه</a></li>
                    <li aria-hidden="true" class="text-slate-600">/</li>
                    <?php if ($term) : ?>
                        <li><a href="<?= esc_url($archive_url); ?>" class="hover:text-secondary transition-colors">نمونه‌کارها</a></li>
                        <li aria-hidden="true" class="text-slate-600">/</li>
                        <li aria-current="page" class="text-slate-200"><?= esc_html($term->name); ?></li>
                    <?php else : ?>
                        <li aria-current="page" class="text-slate-200">نمونه‌کارها</li>
                    <?php endif; ?>
                </ol>
            </nav>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                <div class="lg:col-span-6 xl:col-span-6">
                    <span class="inline-flex items-center gap-2 text-xs font-black text-secondary mb-5" data-aos="fade-up">
                        <span class="w-6 h-px bg-secondary"></span><?= esc_html(kmpr_pf_opt('pfa_kicker')); ?>
                    </span>
                    <h1 id="pf-archive-title" class="text-3xl sm:text-5xl xl:text-6xl font-black text-white leading-[1.35]" <?= preg_match('/[A-Za-z]/', $title) ? 'data-aos="fade-up"' : 'data-split'; ?>>
                        <?= esc_html($title); ?>
                    </h1>
                    <p class="mt-6 max-w-xl text-base sm:text-lg text-slate-300 leading-loose" data-aos="fade-up" data-aos-delay="150">
                        <?= esc_html($intro); ?>
                    </p>

                    <dl class="mt-10 grid grid-cols-3 gap-3 max-w-lg" data-stagger="90">
                        <div class="rounded-2xl bg-white/[0.03] border border-white/10 p-4" data-aos="fade-up">
                            <dt class="text-xs font-bold text-slate-400">پروژه منتشرشده</dt>
                            <dd class="mt-1 text-2xl sm:text-3xl font-black text-white"><span data-count="<?= (int) ($term ? $found : $total_all); ?>"><?= esc_html(kmpr_fa_num($term ? $found : $total_all)); ?></span></dd>
                        </div>
                        <div class="rounded-2xl bg-white/[0.03] border border-white/10 p-4" data-aos="fade-up">
                            <dt class="text-xs font-bold text-slate-400">سال تجربه</dt>
                            <dd class="mt-1 text-2xl sm:text-3xl font-black text-white"><span data-count="<?= (int) kmpr_years_experience(); ?>" data-suffix="+"><?= esc_html(kmpr_fa_num(kmpr_years_experience())); ?>+</span></dd>
                        </div>
                        <div class="rounded-2xl bg-white/[0.03] border border-white/10 p-4" data-aos="fade-up">
                            <dt class="text-xs font-bold text-slate-400">سایت آنلاین</dt>
                            <dd class="mt-1 text-2xl sm:text-3xl font-black text-white"><span data-count="<?= (int) $live_sites; ?>"><?= esc_html(kmpr_fa_num($live_sites)); ?></span></dd>
                        </div>
                    </dl>
                </div>

                <?php if (count($collage) >= 2) : ?>
                    <div class="hidden lg:block lg:col-span-6 relative h-[26rem] xl:h-[28rem]" aria-hidden="true">
                        <?php
                        $pos = array(
                            'top-0 end-0 w-[78%] rotate-[-3deg] z-10',
                            'bottom-0 start-0 w-[62%] rotate-[4deg] z-20',
                            'top-[30%] end-[28%] w-[44%] rotate-[-1deg] z-30',
                        );
                        foreach ($collage as $i => $c) : ?>
                            <div class="absolute <?= esc_attr($pos[$i]); ?>" data-aos="zoom-in" data-aos-delay="<?= (int) (200 + $i * 150); ?>">
                                <div class="float-y rounded-2xl overflow-hidden border border-white/15 bg-slate-900 shadow-[0_40px_80px_-30px_rgba(0,0,0,0.9)]" style="animation-delay:-<?= (int) $i * 2; ?>s">
                                    <div class="flex items-center gap-1.5 px-3 h-6 bg-slate-800/90" dir="ltr">
                                        <span class="size-2 rounded-full bg-rose-400/70"></span><span class="size-2 rounded-full bg-amber-400/70"></span><span class="size-2 rounded-full bg-emerald-400/70"></span>
                                    </div>
                                    <img src="<?= esc_url($c['cover']['url']); ?>" alt="" width="<?= (int) $c['cover']['width']; ?>" height="<?= (int) $c['cover']['height']; ?>" class="w-full aspect-[16/10] object-cover object-top" decoding="async">
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Filter + grid -->
    <section class="relative py-14 lg:py-20" aria-labelledby="pf-grid-title">
        <div class="container mx-auto px-4 sm:px-6">

            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 mb-10 lg:mb-12">
                <div>
                    <h2 id="pf-grid-title" class="text-2xl sm:text-3xl font-black text-white"><?= esc_html($term ? $term->name : 'همه پروژه‌ها'); ?></h2>
                    <p class="mt-1 text-sm text-slate-400">
                        <?= esc_html(kmpr_fa_num($found)); ?> پروژه<?php if ($wp_query->max_num_pages > 1) : ?> — صفحه <?= esc_html(kmpr_fa_num($paged)); ?> از <?= esc_html(kmpr_fa_num($wp_query->max_num_pages)); ?><?php endif; ?>
                    </p>
                </div>

                <?php if ($categories) : ?>
                    <nav aria-label="دسته‌بندی نمونه‌کارها">
                        <ul class="flex flex-wrap items-center gap-2">
                            <li>
                                <a href="<?= esc_url($archive_url); ?>"<?= $term ? '' : ' aria-current="page"'; ?>
                                   class="inline-flex items-center gap-2 min-h-11 px-5 py-2.5 rounded-full text-sm font-black whitespace-nowrap border transition-colors <?= $term ? 'bg-white/[0.03] border-white/10 text-slate-200 hover:border-secondary/50 hover:text-white' : 'bg-secondary border-secondary text-slate-950'; ?>">
                                    همه
                                    <span class="text-xs font-bold <?= $term ? 'text-slate-400' : 'text-slate-900/70'; ?>"><?= esc_html(kmpr_fa_num($total_all)); ?></span>
                                </a>
                            </li>
                            <?php foreach ($categories as $cat) :
                                $active = $term && (int) $term->term_id === (int) $cat->term_id; ?>
                                <li>
                                    <a href="<?= esc_url(get_term_link($cat)); ?>"<?= $active ? ' aria-current="page"' : ''; ?>
                                       class="inline-flex items-center gap-2 min-h-11 px-5 py-2.5 rounded-full text-sm font-black whitespace-nowrap border transition-colors <?= $active ? 'bg-secondary border-secondary text-slate-950' : 'bg-white/[0.03] border-white/10 text-slate-200 hover:border-secondary/50 hover:text-white'; ?>">
                                        <?= esc_html($cat->name); ?>
                                        <span class="text-xs font-bold <?= $active ? 'text-slate-900/70' : 'text-slate-400'; ?>"><?= esc_html(kmpr_fa_num($cat->count)); ?></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>

            <?php if (have_posts()) : ?>
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 lg:gap-8" data-stagger="70">
                    <?php $i = 0;
                    while (have_posts()) : the_post(); ?>
                        <div data-aos="fade-up">
                            <?php get_template_part('template-parts/portfolio/portfolio-card', null, array('class' => 'h-full', 'eager' => $i < 3)); ?>
                        </div>
                    <?php $i++;
                    endwhile; ?>
                </div>

                <?php
                $links = paginate_links(array(
                    'total'     => $wp_query->max_num_pages,
                    'current'   => $paged,
                    'mid_size'  => 1,
                    'prev_text' => 'قبلی',
                    'next_text' => 'بعدی',
                    'type'      => 'array',
                ));
                if ($links) : ?>
                    <nav class="mt-14 flex justify-center" aria-label="صفحه‌بندی نمونه‌کارها">
                        <ul class="flex flex-wrap items-center justify-center gap-2">
                            <?php foreach ($links as $link) : ?>
                                <li><?= wp_kses_post(preg_replace_callback('/>(\d+)</', function ($m) {
                                    return '>' . kmpr_fa_num($m[1]) . '<';
                                }, $link)); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php else : ?>
                <div class="py-20 text-center max-w-md mx-auto rounded-3xl border border-dashed border-white/15">
                    <p class="font-bold text-lg text-slate-300">هنوز پروژه‌ای در این بخش منتشر نشده است.</p>
                    <a href="<?= esc_url($archive_url); ?>" class="mt-6 inline-flex items-center min-h-11 px-6 py-3 rounded-full border border-white/15 text-sm font-bold text-slate-200 hover:border-secondary/50 whitespace-nowrap">مشاهده همه نمونه‌کارها</a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php get_template_part('template-parts/portfolio/cta'); ?>
</div>
