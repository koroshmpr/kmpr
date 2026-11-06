<?php
/**
 * About Us section on Homepage
 */
?>
<section class="py-16 lg:py-24 bg-white overflow-hidden border-b border-slate-200/60 relative">
    <div class="container mx-auto px-4 sm:px-6">
        <!-- Section Header -->
        <div class="text-center max-w-2xl mx-auto mb-14">
            <span class="text-secondary text-xs sm:text-sm font-black tracking-widest uppercase block mb-2">
                <?= wp_kses_post(get_field('about-title')); ?>
            </span>
            <h2 class="text-3xl lg:text-4xl font-black text-slate-900 leading-tight">
                <?= wp_kses_post(get_field('about-description')); ?>
            </h2>
            <div class="h-1.5 w-16 bg-secondary mx-auto rounded-full mt-4"></div>
        </div>

        <div class="max-w-5xl mx-auto grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12 items-center">
            <!-- Image with offset border & floating badge -->
            <div class="relative group" data-aos="fade-left" data-aos-duration="800">
                <div class="absolute inset-0 bg-secondary/15 transform translate-x-3 translate-y-3 rounded-3xl transition-transform duration-500 group-hover:translate-x-1.5 group-hover:translate-y-1.5"></div>
                <div class="overflow-hidden rounded-3xl shadow-xl border border-slate-200/80 bg-white relative z-10">
                    <?php $about_img = get_field('about-box-img'); ?>
                    <?php if ($about_img) : ?>
                        <img class="w-full h-auto object-cover aspect-[4/3] lg:aspect-square transition-transform duration-1000 group-hover:scale-105 pointer-events-none"
                             src="<?= esc_url($about_img['url']); ?>" 
                             alt="<?= esc_attr($about_img['title'] ?? ''); ?>">
                    <?php endif; ?>
                </div>

                <!-- Floating Achievement Badge -->
                <div class="absolute -bottom-4 -end-4 bg-white/95 backdrop-blur-md border border-slate-200 p-3.5 rounded-2xl shadow-xl z-20 flex items-center gap-3">
                    <span class="size-10 rounded-xl bg-secondary/15 text-secondary flex items-center justify-center shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </span>
                    <div class="text-start">
                        <span class="text-xs font-black text-slate-900 block">کیفیت تضمین‌شده</span>
                        <span class="text-[11px] text-slate-500 font-bold">استاندارد روز بین‌المللی</span>
                    </div>
                </div>
            </div>

            <!-- Content Card (Premium Dark Card with High Contrast) -->
            <div class="bg-slate-900 text-white rounded-3xl p-8 sm:p-10 lg:p-12 h-full flex flex-col justify-center text-start shadow-2xl border border-slate-800 relative overflow-hidden" 
                 data-aos="fade-right" data-aos-duration="800" data-aos-delay="200">
                
                <!-- Ambient corner glow inside card -->
                <div class="absolute -top-12 -end-12 size-40 bg-secondary/20 rounded-full blur-2xl pointer-events-none"></div>

                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-secondary/15 border border-secondary/30 mb-3 w-fit">
                    <span class="size-2 rounded-full bg-secondary animate-pulse"></span>
                    <span class="text-xs font-black uppercase text-secondary tracking-wider">داستان ما</span>
                </div>

                <h3 class="text-2xl sm:text-3xl font-black text-white mb-6 leading-tight">
                    <?= esc_html(get_field('about-box-title')); ?>
                </h3>

                <div class="text-slate-300 text-sm sm:text-base leading-loose font-medium">
                    <?= wp_kses_post(get_field('about-box-content')); ?>
                </div>

                <div class="mt-8 pt-6 border-t border-slate-800">
                    <a href="<?= esc_url(home_url('/about-us') ?: home_url('/about')); ?>" 
                       class="group inline-flex items-center gap-2 text-secondary hover:text-white font-black text-sm transition-colors">
                        <span>اطلاعات بیشتر درباره ما</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-4 transition-transform duration-300 group-hover:-translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
