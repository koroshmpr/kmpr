<?php
/**
 * Template Part: Custom Page Transition Preloader
 * Requires Alpine.js and Tailwind CSS
 */
?>
<style>
    #alpine-preloader {
        position: fixed;
        inset: 0;
        z-index: 9999;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        background: #FFFFFF;
    }
    .dots {
        width: 14px;
        height: 14px;
        position: relative;
    }

    .dots::before,
    .dots::after {
        content: "";
        position: absolute;
        inset: 0;
        border-radius: 50%;
        background: var(--color-primary, #1E293B);
    }

    .dots::before {
        box-shadow: -27px 0 var(--color-primary, #1E293B);
        animation: dots-dm 0.6s infinite linear;
    }

    .dots::after {
        transform: rotate(0deg) translateX(27px);
        animation: dots-dh 0.6s infinite linear;
    }

    @keyframes dots-dm {
        100% {
            transform: translateX(27px);
        }
    }

    @keyframes dots-dh {
        100% {
            transform: rotate(-180deg) translateX(27px);
        }
    }
</style>

<div id="alpine-preloader"
     x-data="pageTransition()"
     x-show="isLoading"
     x-transition.opacity.duration.400ms>
    <div class="dots"></div>
</div>
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('pageTransition', () => ({
            isLoading: true,
            init() {
                const hideLoader = () => {
                    setTimeout(() => {
                        this.isLoading = false;
                    }, 150);
                };

                if (document.readyState === 'complete' || document.readyState === 'interactive') {
                    hideLoader();
                } else {
                    window.addEventListener('DOMContentLoaded', hideLoader, { once: true });
                    window.addEventListener('load', hideLoader, { once: true });
                }

                // Safety fallback so preloader never blocks the UI
                setTimeout(() => {
                    this.isLoading = false;
                }, 1500);

                window.addEventListener('pageshow', () => {
                    this.isLoading = false;
                });
            }
        }));
    });
</script>
