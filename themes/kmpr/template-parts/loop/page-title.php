<?php
/**
 * Page Title template part
 */
?>
<div class="overflow-hidden bg-slate-950 border-b border-slate-800 relative py-12 lg:py-16">
    <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/70 to-slate-950"></div>
    <div class="container mx-auto px-4 sm:px-6 text-center relative z-10">
        <h1 data-aos="fade-down" data-aos-duration="800" 
            class="text-3xl sm:text-4xl lg:text-5xl font-black text-white leading-tight drop-shadow-md">
            <?= esc_html($args['title']); ?>
        </h1>
        <div class="h-1.5 w-16 bg-secondary mx-auto rounded-full mt-4"></div>
    </div>
</div>
