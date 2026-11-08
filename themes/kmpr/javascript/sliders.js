/**
 * Swiper sliders — lazily imported by app.js only when a slider exists on the page.
 */
import Swiper from 'swiper/bundle';

function loadSwiperCss() {
    if (document.getElementById('swiper-css')) return;
    const link = document.createElement('link');
    link.id = 'swiper-css';
    link.rel = 'stylesheet';
    link.href = (window.jsData && window.jsData.theme_url ? window.jsData.theme_url : '/wp-content/themes/kmpr') + '/public/css/swiper.min.css';
    document.head.appendChild(link);
}

export default function initSliders() {
    loadSwiperCss();
    // Universal slider initialiser using data-attributes
const initPageSliders = () => {
    document.querySelectorAll('.post-slider').forEach((slider, i) => {
        if (slider.classList.contains('swiper-initialized')) return;
        let autoplaySpeed = slider.dataset.autoplay ? parseFloat(slider.dataset.autoplay) : 0;
        let perPage = slider.dataset.perpage ? parseFloat(slider.dataset.perpage) : 3;
        let perPageLapTop = slider.dataset.laptop ? parseFloat(slider.dataset.laptop) : 3;
        let perPageTablet = slider.dataset.tablet ? parseFloat(slider.dataset.tablet) : 2;
        let perPageMobile = slider.dataset.mobile ? parseFloat(slider.dataset.mobile) : 1.15;
        let perfix = slider.dataset.perfix ?? 'slider';
        let direction = slider.dataset.direction ?? 'horizontal';
        let index = slider.dataset.index ?? i;
        let space = slider.dataset.space ? parseFloat(slider.dataset.space) : 24;
        let speed = slider.dataset.speed ? parseFloat(slider.dataset.speed) : 600;
        let free = slider.dataset.free === '1';
        let loop = slider.dataset.loop === '1';
        let effect = slider.dataset.effect ?? 'slide';

        new Swiper(slider, {
            direction: direction,
            grabCursor: true,
            speed: speed,
            loop: loop,
            effect: effect,
            freeMode: free,
            spaceBetween: space,
            slidesPerView: perPageMobile,
            centeredSlides: false,
            breakpoints: {
                1440: {
                    slidesPerView: perPage,
                },
                1224: {
                    slidesPerView: perPageLapTop,
                },
                768: {
                    slidesPerView: perPageTablet,
                }
            },
            pagination: {
                el: slider.querySelector('.swiper-pagination') || `.${perfix}-pagination-${index}`,
                clickable: true,
                dynamicBullets: true,
            },
            navigation: {
                nextEl: slider.querySelector('.swiper-button-next') || `.${perfix}-next-${index}`,
                prevEl: slider.querySelector('.swiper-button-prev') || `.${perfix}-prev-${index}`,
            },
            autoplay: autoplaySpeed > 0 ? {
                delay: autoplaySpeed,
                disableOnInteraction: false,
                pauseOnMouseEnter: true,
            } : false,
        });
    });
};

initPageSliders();

// Specific Slider Handler: Hero Slider with Background Changing
const heroSwiperElement = document.querySelector('.hero_swiper');
if (heroSwiperElement) {
    const heroSection = document.querySelector('#hero-section');
    const firstSlide = heroSwiperElement.querySelector('.swiper-slide');
    if (heroSection && firstSlide) {
        const firstImg = firstSlide.dataset.image;
        if (firstImg) {
            heroSection.style.backgroundImage = `url(${firstImg})`;
        }
    }

    const heroSwiper = new Swiper(heroSwiperElement, {
        loop: false,
        effect: 'slide',
        speed: 600,
        slidesPerView: 1,
        spaceBetween: 30,
        direction: 'horizontal',
        pagination: {
            el: '.hero-pagination',
            clickable: true,
        },
        navigation: {
            nextEl: '.hero-next',
            prevEl: '.hero-prev',
        },
        autoplay: {
            delay: 10000,
            disableOnInteraction: false,
        },
        on: {
            slideChange: function () {
                const activeSlide = this.slides[this.activeIndex];
                if (activeSlide && heroSection) {
                    const imageUrl = activeSlide.getAttribute('data-image');
                    if (imageUrl) {
                        heroSection.style.backgroundImage = `url(${imageUrl})`;
                    }
                }
            }
        }
    });
}

}
