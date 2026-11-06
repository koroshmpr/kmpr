<?php
$posters = get_field('cta');
if ($posters):
    foreach ($posters as $poster):
        ?>
        <section class="relative py-20 lg:py-28 my-8 bg-slate-950 overflow-hidden">
            <!-- Background Image -->
            <?php if (!empty($poster['image']['url'])) : ?>
                <img class="absolute inset-0 w-full h-full object-cover opacity-20 pointer-events-none select-none" 
                     src="<?= esc_url($poster['image']['url']); ?>" 
                     alt="<?= esc_attr($poster['image']['title'] ?? ''); ?>">
            <?php endif; ?>
            
            <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/60 to-slate-950"></div>

            <div class="container mx-auto px-4 sm:px-6 relative z-10">
                <!-- Main Glass Card Container -->
                <div class="bg-slate-900/80 backdrop-blur-xl border border-white/10 rounded-3xl p-8 sm:p-10 lg:p-14 max-w-4xl mx-auto flex flex-col lg:flex-row items-center justify-between gap-8 text-start shadow-2xl"
                     data-aos="zoom-in" data-aos-duration="800">
                    
                    <div class="flex-1">
                        <span class="text-xs font-black uppercase text-secondary tracking-widest block mb-2">همکاری با ما</span>
                        <h3 class="text-2xl sm:text-3xl lg:text-4xl font-black text-white mb-4 leading-tight">
                            <?= esc_html($poster['heading']); ?>
                        </h3>
                        <div class="text-sm lg:text-base text-slate-300 leading-loose font-medium">
                            <?= wp_kses_post($poster['content']); ?>
                        </div>
                    </div>
                    
                    <div class="shrink-0 max-lg:w-full">
                        <a target="_blank" 
                           class="inline-block w-full lg:w-auto text-center bg-secondary hover:bg-secondary-hover text-white font-black px-8 py-4 rounded-2xl transition-all duration-300 shadow-xl shadow-secondary/30 hover:scale-105 cursor-pointer text-sm sm:text-base" 
                           href="<?= esc_url($poster['btn_link']['url'] ?? ''); ?>">
                            <?= esc_html($poster['btn_text']); ?>
                        </a>
                    </div>
                </div>
            </div>
        </section>
    <?php
    endforeach;
endif; ?>
