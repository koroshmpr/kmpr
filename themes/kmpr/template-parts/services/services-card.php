<article class="rounded service-card card__title-on-image object-fit position-relative overflow-hidden"
         style="background-image: url('<?php echo the_post_thumbnail_url(); ?>')">
    <a class="d-flex info_card h-100 flex-column justify-content-end p-3 align-items-start"
       href="<?php the_permalink(); ?>">
        <div>
            <div class="px-2">
                <p class="card_title mb-0 text-white fs-4 fw-bold"> <?= get_the_title(); ?></p>
                <div class="d-flex text-white fs-5 fw-bold mt-3">
                    <span class="services_badge bg-secondary text-primary p-2 rounded-start shadow-sm"><?= get_field('services_badge'); ?></span>
                    <span class="services__description bg-primary p-2 rounded-end shadow-sm"><?= get_field('services_description'); ?></span>
                </div>
            </div>
        </div>
    </a>
</article>