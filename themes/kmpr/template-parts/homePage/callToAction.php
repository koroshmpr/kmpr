<?php
$posters = get_field('cta');
if ($posters):
    foreach ($posters as $poster):
        ?>
        <section class="relative py-20 lg:py-28 my-12 bg-slate-950 overflow-hidden">
            <!-- Background Image -->
            <img class="absolute inset-0 w-full h-full object-cover opacity-20 pointer-events-none select-none" 
                 src="<?= esc_url($poster['image']['url']); ?>" 
                 alt="<?= esc_attr($poster['image']['title']); ?>">
            
            <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/40 to-slate-950"></div>

            <div class="container mx-auto px-6 relative z-10">
                <!-- Main Glass Card Container -->
                <div class="bg-slate-900/60 backdrop-blur-lg border border-white/10 rounded-3xl p-8 lg:p-12 max-w-4xl mx-auto flex flex-col lg:flex-row items-center justify-between gap-8 text-start shadow-2xl"
                     data-aos="zoom-in" data-aos-duration="800">
                    
                    <div class="flex-1">
                        <h4 class="text-2xl lg:text-3xl font-black text-white mb-4 leading-tight">
                            <?= esc_html($poster['heading']); ?>
                        </h4>
                        <div class="text-sm lg:text-base text-slate-300 leading-loose font-medium prose prose-invert prose-sm">
                            <?= wp_kses_post($poster['content']); ?>
                        </div>
                    </div>
                    
                    <div class="shrink-0 max-lg:w-full">
                        <a target="_blank" 
                           class="inline-block w-full lg:w-auto text-center bg-secondary text-primary font-black px-8 py-3.5 rounded-2xl hover:bg-secondary/95 transition-all duration-300 shadow-lg hover:shadow-secondary/20 hover:scale-105" 
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