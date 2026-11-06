<?php
/* Template Name: faq */

get_header(); ?>

<section class="h-full bg-white pb-16">
    <?php $title = get_the_title();
    $args = array(
        'title' => $title,
    );
    get_template_part('template-parts/loop/page-title', null, $args); ?>

    <div class="container mx-auto px-6 py-12 lg:py-16">
        <div class="max-w-3xl mx-auto flex flex-col gap-4 text-start">
            <?php
            $i = 0;
            if (have_rows('faq')):
                while (have_rows('faq')): the_row();
                    $question = get_sub_field('question');
                    $answer = get_sub_field('answer');
                    if ($question && $answer) {
                        ?>
                        <div x-data="{ expanded: false }"
                             :class="expanded ? 'border-s-2 bg-slate-50 border-s-secondary' : 'bg-slate-50/50 border-s-slate-300'"
                             class="border border-slate-200/80 rounded-xl transition-all duration-300"
                             data-aos="zoom-in" data-aos-delay="<?= $i; ?>00">
                            
                            <!-- Accordion Header Button -->
                            <button type="button"
                                    class="flex items-center justify-between w-full p-5 cursor-pointer text-start outline-none"
                                    @click="expanded = !expanded"
                                    :aria-expanded="expanded">
                                <span class="text-slate-800 text-base font-bold leading-tight">
                                    <?= esc_html($question); ?>
                                </span>
                                <span class="shrink-0 ms-3 text-secondary transition-transform duration-300">
                                    <!-- Plus Icon -->
                                    <svg x-show="!expanded" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                                        <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4"/>
                                    </svg>
                                    <!-- Minus Icon -->
                                    <svg x-show="expanded" width="20" height="20" fill="currentColor" viewBox="0 0 16 16" x-cloak>
                                        <path d="M4 8a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7A.5.5 0 0 1 4 8"/>
                                    </svg>
                                </span>
                            </button>

                            <!-- Accordion Body (CSS Grid Slide Animation) -->
                            <div class="grid text-slate-600 overflow-hidden px-5 transition-all duration-300 ease-in-out"
                                 :class="expanded ? 'grid-rows-[1fr] opacity-100 py-5 border-t border-dashed border-slate-200' : 'grid-rows-[0fr] opacity-0'">
                                <div class="overflow-hidden leading-relaxed text-sm font-medium">
                                    <?= wp_kses_post($answer); ?>
                                </div>
                            </div>
                        </div>
                    <?php }
                    $i++;
                endwhile;
            endif; ?>
        </div>
    </div>
</section>

<!-- Contact Form at bottom -->
<section class="bg-secondary/15 py-16 border-t border-slate-200/60 overflow-hidden">
    <div class="container mx-auto px-6 max-w-xl text-start">
        <h3 data-aos="fade-down" class="text-2xl font-black text-slate-900 mb-6 text-center">
            <?= esc_html(get_field('form-title')); ?>
        </h3>
        <div data-aos="fade-up" data-aos-delay="100" class="bg-white border border-slate-200/80 rounded-3xl p-8 shadow-md">
            <?= do_shortcode('[gravityform id="' . get_field('form-id') . '" title="false" description="false" ajax="true"]'); ?>
        </div>
    </div>
</section>

<?php get_footer(); ?>
