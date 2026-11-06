<button id="backToTop" 
        aria-label="Back to Top"
        :class="scrolled ? 'opacity-100 pointer-events-auto translate-y-0' : 'opacity-0 pointer-events-none translate-y-4'"
        class="fixed bottom-24 lg:bottom-8 end-6 z-40 size-11 lg:size-12 bg-secondary text-primary border border-primary/10 rounded-full flex items-center justify-center shadow-lg transition-all duration-300 hover:bg-secondary/90 hover:scale-105 active:scale-95 cursor-pointer">
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-chevron-up" viewBox="0 0 16 16">
        <path fill-rule="evenodd" d="M7.646 4.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1-.708.708L8 5.707l-5.646 5.647a.5.5 0 0 1-.708-.708l6-6z"/>
    </svg>
</button>