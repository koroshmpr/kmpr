<?php
/**
 * Call to Action - Direct Collaboration with Korosh Mpr
 */
$posters = get_field('cta');
if ($posters):
    foreach ($posters as $poster):
        ?>
        <section class="relative my-6 bg-slate-950 overflow-hidden select-none">
            <!-- Ambient Glow -->
            <div class="absolute -top-24 start-1/4 w-96 h-96 bg-secondary/15 rounded-full blur-[120px] pointer-events-none"></div>
            <div class="absolute -bottom-24 end-1/4 w-96 h-96 bg-blue-600/15 rounded-full blur-[120px] pointer-events-none"></div>

            <!-- Background Image -->
            <?php if (!empty($poster['image']['url'])) : ?>
                <img class="absolute inset-0 w-full h-full object-cover opacity-15 pointer-events-none select-none scale-105" 
                     src="<?= esc_url($poster['image']['url']); ?>" 
                     alt="<?= esc_attr($poster['image']['title'] ?? ''); ?>">
            <?php endif; ?>
            
            <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/70 to-slate-950"></div>

            <div class="container mx-auto px-4 sm:px-6 relative z-10">
                <div class="max-w-4xl mx-auto p-[1px] rounded-3xl bg-gradient-to-r from-secondary/50 via-sky-400/30 to-secondary/20 shadow-2xl shadow-secondary/10"
                     data-aos="zoom-in" data-aos-duration="800">
                    
                    <div class="bg-slate-900/95 backdrop-blur-2xl rounded-3xl p-8 sm:p-12 lg:p-14 flex flex-col lg:flex-row items-center justify-between gap-8 text-start">
                        <div class="flex-1">
                            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-secondary/15 border border-secondary/30 mb-4">
                                <span class="w-2 h-2 rounded-full bg-secondary animate-pulse"></span>
                                <span class="text-xs font-black uppercase text-secondary tracking-widest">شروع همکاری اختصاصی با من</span>
                            </div>
                            
                            <h3 class="text-2xl sm:text-3xl lg:text-4xl font-black text-white mb-4 leading-tight">
                                <?= esc_html($poster['heading']); ?>
                            </h3>
                            
                            <div class="text-sm lg:text-base text-slate-300 leading-loose font-medium">
                                <?= wp_kses_post($poster['content']); ?>
                            </div>
                        </div>
                        
                        <div class="shrink-0 max-lg:w-full">
                            <a target="_blank" 
                               class="inline-block w-full lg:w-auto text-center whitespace-nowrap bg-secondary hover:bg-secondary-hover text-white font-black px-8 py-4 rounded-2xl transition-all duration-300 shadow-xl shadow-secondary/30 hover:scale-105 active:scale-95 cursor-pointer text-sm sm:text-base" 
                               href="<?= esc_url($poster['btn_link']['url'] ?? home_url('/contact-us')); ?>">
                                <?= esc_html($poster['btn_text'] ?? 'شروع گفتگو و مشاوره فنی'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    <?php
    endforeach;
else: ?>
    <!-- Fallback Direct CTA for Personal Brand -->
    <section class="relative py-20 lg:py-28 my-6 bg-slate-950 overflow-hidden select-none border-y border-white/10">
        <div class="absolute -top-24 start-1/4 w-96 h-96 bg-secondary/15 rounded-full blur-[120px] pointer-events-none"></div>
        <div class="absolute -bottom-24 end-1/4 w-96 h-96 bg-blue-600/15 rounded-full blur-[120px] pointer-events-none"></div>

        <div class="container mx-auto px-4 sm:px-6 relative z-10">
            <div class="max-w-4xl mx-auto p-[1px] rounded-3xl bg-gradient-to-r from-secondary/50 via-sky-400/30 to-secondary/20 shadow-2xl shadow-secondary/10"
                 data-aos="zoom-in" data-aos-duration="800">
                
                <div class="bg-slate-900/95 backdrop-blur-2xl rounded-3xl p-8 sm:p-12 lg:p-14 flex flex-col lg:flex-row items-center justify-between gap-8 text-start">
                    <div class="flex-1">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-secondary/15 border border-secondary/30 mb-4">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                            <span class="text-xs font-black uppercase text-secondary tracking-widest">شروع همکاری مستقیم با من</span>
                        </div>
                        
                        <h3 class="text-2xl sm:text-3xl lg:text-4xl font-black text-white mb-4 leading-tight">
                            ایده یا پروژه‌ای در ذهن دارید؟ بیایید با بالاترین استانداردهای مهندسی بسازیمش.
                        </h3>
                        
                        <p class="text-sm lg:text-base text-slate-300 leading-loose font-medium">
                            بدون واسطه و به طور مستقیم با من در ارتباط باشید تا نیازمندی‌ها، معماری فنی و بهینه‌سازی پروژه شما را با هم بررسی کنیم.
                        </p>
                    </div>
                    
                    <div class="shrink-0 max-lg:w-full">
                        <a class="inline-block w-full lg:w-auto text-center whitespace-nowrap bg-secondary hover:bg-secondary-hover text-white font-black px-8 py-4 rounded-2xl transition-all duration-300 shadow-xl shadow-secondary/30 hover:scale-105 active:scale-95 cursor-pointer text-sm sm:text-base" 
                           href="<?= esc_url(home_url('/contact-us') ?: home_url('/contact')); ?>">
                            ارتباط مستقیم و ثبت پروژه
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
<?php endif; ?>
