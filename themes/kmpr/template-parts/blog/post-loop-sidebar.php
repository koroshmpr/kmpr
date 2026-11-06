<?php
/**
 * Blog Archive Post Loop & Sidebar container
 */
?>
<section class="container mx-auto px-6 py-12 text-start">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-start">
        <!-- Sidebar Column (lg:col-span-4) -->
        <div class="lg:col-span-4 order-2 lg:order-1 flex flex-col gap-6">
            <?php get_template_part('template-parts/loop/sidebar'); ?>
        </div>
        
        <!-- Main Posts List Column (lg:col-span-8) -->
        <div class="lg:col-span-8 order-1 lg:order-2">
            <?php get_template_part('template-parts/blog/recently-posts'); ?>
        </div>
    </div>
</section>