<?php
/* Template Name: faq */

get_header(); ?>

<section class="h-100">
    <?php $title = get_the_title();
    $args = array(
        'title' => $title,
    );
    get_template_part('template-parts/loop/page-title', null, $args); ?>
    <div class="container py-lg-5 py-3">
        <div class="row justify-content-center align-items-center h-100">
            <div class="col-lg-8">

                <div class="accordion accordion-flush overflow-hidden box-shadow rounded-2" id="faq">
                    <?php
                    $i = 0;
                    if (have_rows('faq')):
                        while (have_rows('faq')): the_row();
                            $question = get_sub_field_object('question');
                            $rowIndex = get_row_index();
                            $answer = get_sub_field_object('answer');
                            if ($question) {
                                ?>
                                <div class="accordion-item" data-aos="zoom-in" data-aos-delay="<?= $i; ?>00">
                                    <h2 class="accordion-header" id="heading-<?= $rowIndex; ?>">
                                        <button class="accordion-button bg-primary text-white fs-5" type="button"
                                                data-bs-toggle="collapse" data-bs-target="#collapse-<?= $rowIndex; ?>"
                                                aria-expanded="<?= $i == 0 ? 'true' : 'false'; ?>"
                                                aria-controls="collapse-<?= $rowIndex; ?>">
                                            <?= $question['value']; ?>
                                        </button>
                                    </h2>
                                    <div id="collapse-<?= $rowIndex; ?>"
                                         class="accordion-collapse collapse <?= $i == 0 ? 'show' : ''; ?>"
                                         aria-labelledby="heading-<?= $rowIndex; ?>" data-bs-parent="#faq">
                                        <div class="accordion-body fs-5 bg-secondary bg-opacity-25 border border-primary">
                                            <?= $answer['value']; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php }
                            $i++;
                        endwhile;
                    endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>
<section class="bg-secondary overflow-hidden">
    <div class="container">
        <div class="row justify-content-center py-4">
            <div class="col-lg-6 col-11">
                <h3 data-aos="fade-down" class="text-center py-3 fw-bold fs-3 text-primary text-opacity-75">
                    <?= get_field('form-title'); ?>
                </h3>
                <div data-aos="fade-up" data-aos-delay="100">
                    <?= do_shortcode('[gravityform id="' . get_field('form-id') . '" title="false" description="false" ajax="true"]'); ?>
                </div>
            </div>
        </div>
    </div>
</section>
<?php get_footer(); ?>
