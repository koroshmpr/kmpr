<?php
get_header();
?>
<section class="container mx-auto px-6 py-16 lg:py-28 flex items-center justify-center min-h-[70vh]">
    <article class="relative text-center p-8 lg:p-14 rounded-3xl shadow-2xl bg-slate-900 border border-slate-800 text-white max-w-xl w-full overflow-hidden" data-aos="zoom-in">
        <div class="font-black my-auto flex items-center justify-center gap-2 text-7xl lg:text-9xl text-secondary drop-shadow-md select-none" dir="ltr">
            <span data-aos="fade-down" data-aos-delay="200">4</span>
            <span data-aos="fade-down" data-aos-delay="400" class="text-white">0</span>
            <span data-aos="fade-down" data-aos-delay="600">4</span>
        </div>
        <h1 class="text-2xl lg:text-3xl font-black text-white mt-4 mb-3">برگه مورد نظر یافت نشد!</h1>
        <p class="text-slate-400 text-sm lg:text-base leading-relaxed max-w-md mx-auto">
            صفحه‌ای که به دنبال آن هستید حذف شده، تغییر نام یافته و یا موقتاً در دسترس نیست.
        </p>
        <div class="mt-8">
            <a href="<?= esc_url(home_url('/')); ?>" class="inline-flex items-center justify-center gap-2 bg-secondary text-primary font-extrabold text-sm px-8 py-3.5 rounded-2xl shadow-lg hover:bg-secondary/90 hover:scale-105 transition-all duration-300">
                <span>بازگشت به صفحه اصلی</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"/>
                </svg>
            </a>
        </div>
    </article>
</section>
<?php
get_footer();
