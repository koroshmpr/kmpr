<?php
/**
 * High-Performance Developer Terminal Preloader
 * Dark Obsidian & Software Engineering Theme (No White Flash)
 */
?>
<style>
    #alpine-preloader {
        position: fixed;
        inset: 0;
        z-index: 99999;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        background: #020617;
        overflow: hidden;
    }

    .dev-loader-ring {
        position: relative;
        width: 76px;
        height: 76px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .dev-loader-spinner {
        position: absolute;
        inset: 0;
        border-radius: 24px;
        padding: 2.5px;
        background: linear-gradient(135deg, #f59e0b, #0284c7, #10b981, #d97706);
        -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
        -webkit-mask-composite: xor;
        mask-composite: exclude;
        animation: dev-spin 2s linear infinite;
    }

    @keyframes dev-spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    .dev-bracket-pulse {
        animation: dev-pulse 1.4s ease-in-out infinite alternate;
    }

    @keyframes dev-pulse {
        0% { transform: scale(0.96); opacity: 0.8; }
        100% { transform: scale(1.04); opacity: 1; }
    }
</style>

<div id="alpine-preloader"
     x-data="pageTransition()"
     x-show="isLoading"
     x-transition:leave="transition ease-out duration-300"
     x-transition:leave-start="opacity-100 scale-100"
     x-transition:leave-end="opacity-0 scale-95 pointer-events-none">
    
    <!-- Ambient subtle background glow -->
    <div class="absolute w-80 h-80 bg-secondary/15 rounded-full blur-[120px] pointer-events-none"></div>
    <div class="absolute w-80 h-80 bg-blue-600/10 rounded-full blur-[120px] pointer-events-none"></div>

    <div class="relative z-10 flex flex-col items-center gap-5 dev-bracket-pulse select-none">
        
        <!-- Animated Developer Emblem Ring -->
        <div class="dev-loader-ring">
            <div class="dev-loader-spinner"></div>
            
            <div class="w-16 h-16 rounded-[22px] bg-slate-900/90 border border-slate-800 flex items-center justify-center shadow-2xl backdrop-blur-md">
                <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="16 18 22 12 16 6" stroke="#38bdf8" />
                    <polyline points="8 6 2 12 8 18" stroke="#38bdf8" />
                    <line x1="14" y1="4" x2="10" y2="20" stroke="#f59e0b" />
                </svg>
            </div>
        </div>

        <!-- Terminal Status Pill -->
        <div class="flex items-center gap-2.5 font-mono text-[11px] text-slate-400 bg-slate-900/80 px-4 py-1.5 rounded-full border border-slate-800 shadow-2xl backdrop-blur-md">
            <span class="size-2 rounded-full bg-emerald-400 animate-pulse"></span>
            <span class="font-bold text-slate-200">KMPR</span>
            <span class="text-secondary font-bold">//</span>
            <span class="text-slate-400 tracking-wider">DEV_INITIALIZE</span>
        </div>

    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('pageTransition', () => ({
            isLoading: true,
            init() {
                const hideLoader = () => {
                    setTimeout(() => {
                        this.isLoading = false;
                    }, 120);
                };

                if (document.readyState === 'complete' || document.readyState === 'interactive') {
                    hideLoader();
                } else {
                    window.addEventListener('DOMContentLoaded', hideLoader, { once: true });
                    window.addEventListener('load', hideLoader, { once: true });
                }

                // Safety guard: dismiss preloader after 1.2s max so it never blocks
                setTimeout(() => {
                    this.isLoading = false;
                }, 1200);

                window.addEventListener('pageshow', () => {
                    this.isLoading = false;
                });
            }
        }));
    });
</script>
