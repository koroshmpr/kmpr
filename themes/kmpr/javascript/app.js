import Swiper from 'swiper/bundle';
import Alpine from 'alpinejs';
import intersect from '@alpinejs/intersect';
import axios from 'axios';

window.Alpine = Alpine;
window.axios = axios;
Alpine.plugin(intersect);

// Enhanced Live Search component with tabbed multi-content filtering
window.themeSearch = function () {
    return {
        query: '',
        activeTab: 'all', // 'all', 'post', 'portfolio', 'services'
        results: {
            all: [],
            post: [],
            portfolio: [],
            services: []
        },
        counts: {
            all: 0,
            post: 0,
            portfolio: 0,
            services: 0
        },
        isLoading: false,
        hasSearched: false,
        debounceTimeout: null,

        init() {
            this.$watch('query', value => {
                clearTimeout(this.debounceTimeout);
                if (!value || value.trim().length < 2) {
                    this.resetResults();
                    return;
                }
                this.isLoading = true;
                this.debounceTimeout = setTimeout(() => {
                    this.fetchResults();
                }, 280);
            });
        },

        setTab(tab) {
            this.activeTab = tab;
        },

        getCurrentResults() {
            return this.results[this.activeTab] || [];
        },

        resetResults() {
            this.results = { all: [], post: [], portfolio: [], services: [] };
            this.counts = { all: 0, post: 0, portfolio: 0, services: 0 };
            this.isLoading = false;
            this.hasSearched = false;
        },

        clearQuery() {
            this.query = '';
            this.resetResults();
        },

        fetchResults() {
            const trimmed = this.query.trim();
            if (trimmed.length < 2) {
                this.resetResults();
                return;
            }

            this.isLoading = true;
            const rootUrl = (typeof jsData !== 'undefined' && jsData.root_url) ? jsData.root_url : '';
            const searchUrl = `${rootUrl}/wp-json/search/v1/search?term=${encodeURIComponent(trimmed)}`;

            axios.get(searchUrl)
                .then(response => {
                    const data = response.data;
                    this.results = {
                        all: data.all || [],
                        post: data.post || [],
                        portfolio: data.portfolio || [],
                        services: data.services || []
                    };
                    this.counts = data.counts || {
                        all: (data.all || []).length,
                        post: (data.post || []).length,
                        portfolio: (data.portfolio || []).length,
                        services: (data.services || []).length
                    };
                    this.hasSearched = true;
                    this.isLoading = false;
                })
                .catch(error => {
                    console.error('Search error:', error);
                    this.isLoading = false;
                    this.hasSearched = true;
                });
        }
    };
};

document.addEventListener('DOMContentLoaded', function () {
    Alpine.start();

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

    // Back to top button handler
    const backToTop = document.getElementById("backToTop");
    if (backToTop) {
        backToTop.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }
});
