<?php
/**
 * Home — Services bento (services CPT + request card)
 */
$picked   = array_filter((array) get_field('services_items'));
$services = new WP_Query($picked ? array(
    'post_type'      => 'services',
    'post__in'       => $picked,
    'orderby'        => 'post__in',
    'posts_per_page' => 5,
    'no_found_rows'  => true,
) : array(
    'post_type'      => 'services',
    'posts_per_page' => 5,
    'orderby'        => 'menu_order date',
    'order'          => 'ASC',
    'no_found_rows'  => true,
));
$fallback_icons = array(
    '<path d="M3 5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M3 9h18M7 6h.01M10 6h.01"/>',
    '<circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3M8 13l2-2 2 2 3-3"/>',
    '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/>',
    '<path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"/>',
    '<rect width="18" height="18" x="3" y="3" rx="2"/><path d="m9 8 3 3-3 3M14 14h2"/>',
);
?>
<section id="services" class="relative py-20 lg:py-28 scroll-mt-20" aria-labelledby="services-title">
    <div class="container mx-auto px-4 sm:px-6">
        <header class="flex flex-col lg:flex-row lg:items-end justify-between gap-6 mb-12 lg:mb-16">
            <div class="max-w-2xl">
                <span class="inline-flex items-center gap-2 text-xs font-black text-secondary mb-4" data-aos="fade-up">
                    <span class="w-6 h-px bg-secondary"></span><?= esc_html(kmpr_field('services_kicker')); ?>
                </span>
                <h2 id="services-title" class="text-3xl sm:text-4xl lg:text-5xl font-black text-white leading-tight" data-split>
                    <?= esc_html(kmpr_field('services_title')); ?>
                </h2>
            </div>
            <p class="max-w-md text-slate-300 leading-relaxed" data-aos="fade-up" data-aos-delay="150">
                <?= esc_html(kmpr_field('services_text')); ?>
            </p>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 lg:gap-5" data-stagger="90">
            <?php $i = 0;
            while ($services->have_posts()) : $services->the_post();
                $icon  = get_field('service-icon');
                $desc  = get_field('services_description') ?: wp_trim_words(get_the_excerpt(), 22);
                $badge = get_field('services_badge');
                $wide  = ($i === 0);
                ?>
                <a href="<?php the_permalink(); ?>" data-aos="fade-up"
                   class="spotlight group relative flex flex-col justify-between gap-10 p-7 lg:p-8 rounded-3xl bg-white/[0.025] border border-white/10 hover:border-secondary/40 overflow-hidden transition-colors duration-500 min-h-[17rem] <?= $wide ? 'lg:col-span-2' : ''; ?>">
                    <?php if ($wide && has_post_thumbnail()) : ?>
                        <?php the_post_thumbnail('large', array('class' => 'absolute inset-0 w-full h-full object-cover opacity-[0.12] group-hover:opacity-20 group-hover:scale-105 transition duration-700 -z-10', 'alt' => '', 'loading' => 'lazy')); ?>
                    <?php endif; ?>
                    <div class="flex items-start justify-between gap-4">
                        <span class="size-14 rounded-2xl bg-secondary/10 border border-secondary/25 text-secondary flex items-center justify-center shrink-0 group-hover:scale-110 group-hover:rotate-[-6deg] transition-transform duration-500 [&_svg]:w-7 [&_svg]:h-7">
                            <?php if ($icon) : echo wp_kses($icon, array('svg' => array('xmlns' => 1, 'viewbox' => 1, 'fill' => 1, 'stroke' => 1, 'stroke-width' => 1, 'stroke-linecap' => 1, 'stroke-linejoin' => 1, 'class' => 1, 'width' => 1, 'height' => 1), 'path' => array('d' => 1, 'fill' => 1, 'stroke' => 1, 'fill-rule' => 1, 'clip-rule' => 1), 'circle' => array('cx' => 1, 'cy' => 1, 'r' => 1), 'rect' => array('x' => 1, 'y' => 1, 'width' => 1, 'height' => 1, 'rx' => 1), 'g' => array())); else : ?>
                                <svg width="28" height="28" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?= $fallback_icons[$i % count($fallback_icons)]; // static ?></svg>
                            <?php endif; ?>
                        </span>
                        <span class="font-mono text-sm font-bold text-slate-500 group-hover:text-secondary transition-colors" dir="ltr">0<?= (int) $i + 1; ?></span>
                    </div>
                    <div>
                        <?php if ($badge) : ?>
                            <span class="inline-block mb-3 px-2.5 py-1 rounded-full bg-secondary/15 text-secondary text-[11px] font-black"><?= esc_html($badge); ?></span>
                        <?php endif; ?>
                        <h3 class="text-xl lg:text-2xl font-black text-white mb-3"><?php the_title(); ?></h3>
                        <p class="text-sm text-slate-300 leading-relaxed max-w-lg"><?= esc_html($desc); ?></p>
                        <span class="mt-6 inline-flex items-center gap-2 text-sm font-black text-secondary">
                            جزئیات و تعرفه
                            <svg width="16" height="16" class="shrink-0 group-hover:-translate-x-1.5 transition-transform" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.61l4.16 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.61 9.25h10.64A.75.75 0 0 1 17 10Z" clip-rule="evenodd"/></svg>
                        </span>
                    </div>
                </a>
            <?php $i++;
            endwhile;
            wp_reset_postdata(); ?>

            <!-- Request card -->
            <a href="<?= esc_url(kmpr_request_url()); ?>" data-aos="fade-up"
               class="group relative flex flex-col justify-between gap-8 p-7 lg:p-8 rounded-3xl bg-gradient-to-br from-secondary to-amber-700 text-slate-950 overflow-hidden min-h-[17rem] <?= ($i > 0 && ($i + 1) % 3 === 1) ? 'lg:col-span-2' : ''; ?>">
                <span class="absolute -bottom-16 -end-16 size-56 rounded-full border-[28px] border-white/15 group-hover:scale-125 transition-transform duration-700" aria-hidden="true"></span>
                <span class="text-sm font-black opacity-80"><?= esc_html(kmpr_field('request_card_kicker')); ?></span>
                <div class="relative">
                    <h3 class="text-2xl lg:text-3xl font-black leading-snug mb-3"><?= esc_html(kmpr_field('request_card_title')); ?></h3>
                    <span class="inline-flex items-center gap-2 mt-2 px-5 py-2.5 rounded-full bg-slate-950 text-white text-sm font-black whitespace-nowrap group-hover:gap-3 transition-all">
                        <?= esc_html(kmpr_field('request_card_btn')); ?>
                        <svg width="16" height="16" class="shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.61l4.16 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.61 9.25h10.64A.75.75 0 0 1 17 10Z" clip-rule="evenodd"/></svg>
                    </span>
                </div>
            </a>
        </div>
    </div>
</section>
