<section class="overflow-hidden shadow">
    <div class="row">
        <div class="row justify-content-center align-content-start swiper hero_swiper col-lg-5 bg-white position-relative">
            <div class="swiper-wrapper h-auto">
                <?php while (have_rows('hero-slider')):
                    the_row(); ?>
                    <div class="swiper-slide text-center px-5 py-3 py-lg-5 overflow-hidden" data-aos="fade-left" data-aos-delay="100"
                         data-image="<?= get_sub_field('image')['url']; ?>">
                        <span class="d-none d-lg-inline badge bg-secondary fs-4 mb-3 text-primary"><?= get_sub_field('badge'); ?></span>
                        <h1 class="display-1 fw-bold text-primary py-3 py-lg-5"><?= get_sub_field('title'); ?></h1>
                        <p class="text-justify fs-4"><?= wp_trim_words(get_sub_field('content'), 20); ?></p>
                        <a data-aos="fade-up" data-aos-delay="500" href="<?= get_sub_field('button_link')['url']; ?>"
                           class="btn bg-primary text-white fs-4 mt-2 mt-lg-4 fw-bold rounded-pill shadow">مشاهده و ادامه</a>
                    </div>
                <?php
                endwhile; ?>
            </div>
            <!--            navigation and pagination-->
            <div class="d-flex justify-content-around align-items-center">
                <div class="swiper-button-next position-static swiper__nav">
                    <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="currentColor" class="bi bi-arrow-right-short text-dark" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M4 8a.5.5 0 0 1 .5-.5h5.793L8.146 5.354a.5.5 0 1 1 .708-.708l3 3a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.708-.708L10.293 8.5H4.5A.5.5 0 0 1 4 8z"/>
                    </svg>
                </div>
                <div class="hero-pagination position-static w-auto mb-3 ps-3"></div>
                <div class="swiper-button-prev position-static swiper__nav">
                    <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="currentColor" class="bi bi-arrow-left-short text-dark" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M12 8a.5.5 0 0 1-.5.5H5.707l2.147 2.146a.5.5 0 0 1-.708.708l-3-3a.5.5 0 0 1 0-.708l3-3a.5.5 0 1 1 .708.708L5.707 7.5H11.5a.5.5 0 0 1 .5.5z"/>
                    </svg>
                </div>
            </div>
        </div>
        <div id="hero-section" data-aos="fade-right" class="col-lg-7 img-fluid"></div>
    </div>
</section>