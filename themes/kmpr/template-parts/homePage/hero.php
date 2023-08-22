<section class="bg-primary position-relative overflow-hidden py-5">
    <div class="container">
        <div id="hero-section" class="row rounded-2 mb-3 border border-5 border-secondary mt-lg-0 justify-content-center align-content-lg-center align-items-end justify-content-lg-start object-fit-cover">
            <div class="swiper hero_swiper col-11 col-lg-4 ms-lg-4 mb-3 p-lg-5 p-4 bg-white rounded-2">
                <div class="swiper-wrapper">
                    <?php while (have_rows('hero-slider')):
                        the_row(); ?>
                        <div class="swiper-slide text-center text-lg-start" data-image="<?= get_sub_field('image')['url'];?>">
                            <span class="badge bg-secondary fs-6 mb-3"><?= get_sub_field('badge');?></span>
                            <p class="fs-2 fw-bold text-dark"><?= get_sub_field('title');?></p>
                            <p class="text-justify"><?= wp_trim_words(get_sub_field('content') , 25);?></p>
                            <div class="d-flex gap-lg-2 mt-4 align-items-center justify-content-center justify-content-lg-start">
                                <a href="<?= get_sub_field('button_link')['url'];?>" class="btn bg-primary text-white">مشاهده و ادامه</a>
                                <a class="d-none d-lg-inline text-dark fw-bold p-3" href="<?= get_sub_field('button_link')['url'];?>">بیشتر بدانید</a>
                            </div>
                        </div>
                    <?php
                    endwhile; ?>
                </div>
                <!--            navigation and pagination-->
                <div class="d-flex justify-content-lg-start justify-content-center pt-lg-5 pt-3 ps-lg-5 align-items-center">
                    <div class="swiper-button-next position-static swiper__nav">
                        <i class="bi bi-arrow-right-short fs-4 text-dark"></i>
                    </div>
                    <div class="hero-pagination position-static w-auto mb-3"></div>
                    <div class="swiper-button-prev position-static swiper__nav">
                        <i class="bi bi-arrow-left-short fs-4 text-dark"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>