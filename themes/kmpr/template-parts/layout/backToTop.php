<button id="backToTop" type="button" aria-label="بازگشت به بالای صفحه" data-scroll-ring
        :class="scrolled && !menuOpen ? 'opacity-100 pointer-events-auto scale-100' : 'opacity-0 pointer-events-none scale-75'"
        class="kmpr-totop fixed end-4 lg:end-6 z-40 size-11 lg:size-12 rounded-full flex items-center justify-center bg-slate-900/90 backdrop-blur-md text-slate-100 border border-white/10 shadow-lg shadow-black/50 transition-[opacity,transform] duration-300 cursor-pointer active:scale-90 hover:text-secondary">
    <svg class="absolute inset-0 size-full -rotate-90" viewBox="0 0 48 48" aria-hidden="true">
        <circle cx="24" cy="24" r="21" fill="none" stroke="currentColor" stroke-opacity=".12" stroke-width="2.5"/>
        <circle class="kmpr-totop-ring" cx="24" cy="24" r="21" fill="none" stroke="#f59e0b" stroke-width="2.5" stroke-linecap="round" pathLength="100" stroke-dasharray="100"/>
    </svg>
    <svg width="18" height="18" class="shrink-0 relative" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 15 6-6 6 6"/></svg>
</button>
