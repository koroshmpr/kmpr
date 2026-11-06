<?php
/** 
 * Template Name: Portfolio Archive 
 */
get_header(); 

// Fetch categories for interactive filtering
$portfolio_categories = get_terms([
    'taxonomy' => 'portfolio_categories',
    'hide_empty' => true,
]);
?>

<!-- Portfolio Hero Header -->
<section class="relative min-h-[45vh] lg:min-h-[55vh] flex items-center justify-center overflow-hidden bg-slate-950 text-white select-none py-16 lg:py-24 border-b border-slate-900">
    <!-- Subtle Grid Overlay -->
    <div class="absolute inset-0 bg-[radial-gradient(#334155_1px,transparent_1px)] [background-size:24px_24px] opacity-25 pointer-events-none"></div>

    <!-- Ambient Glow Spheres -->
    <div class="absolute top-1/4 start-1/4 w-96 h-96 bg-secondary/15 rounded-full blur-[140px] pointer-events-none"></div>
    <div class="absolute bottom-10 end-1/4 w-96 h-96 bg-blue-600/15 rounded-full blur-[140px] pointer-events-none"></div>

    <div class="container mx-auto px-4 sm:px-6 relative z-10 text-center">
        <!-- Live Quality Badge -->
        <div data-aos="fade-down" data-aos-duration="600"
             class="inline-flex items-center gap-2.5 px-4 py-2 rounded-full bg-white/[0.06] border border-white/10 backdrop-blur-xl mb-6">
            <span class="relative flex w-2.5 h-2.5 shrink-0">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full w-2.5 h-2.5 bg-emerald-500"></span>
            </span>
            <span class="text-xs font-black text-slate-200 tracking-wide">نمایش خروجی‌های واقعی؛ بدون قالب آماده و بدون باگ</span>
        </div>

        <h1 data-aos="fade-up" data-aos-duration="800"
            class="text-3xl sm:text-5xl lg:text-6xl font-black text-white leading-tight mb-4 tracking-tight">
            نمونه‌کارها و پروژه‌های اجرا شده
        </h1>

        <p data-aos="fade-up" data-aos-duration="800" data-aos-delay="150"
           class="text-sm sm:text-base lg:text-lg text-slate-300 max-w-2xl mx-auto leading-relaxed">
            مجموعه‌ای از پلتفرم‌های اختصاصی، وب‌سایت‌های شرکتی پرستیژ و فروشگاه‌های اینترنتی با بالاترین نرخ بازگشت سرمایه.
        </p>

        <!-- Proof of Craftsmanship Pills -->
        <div data-aos="fade-up" data-aos-duration="800" data-aos-delay="300"
             class="mt-8 pt-6 border-t border-white/10 flex flex-wrap items-center justify-center gap-4 sm:gap-8 text-xs text-slate-400 font-bold">
            <span class="flex items-center gap-2">
                <span class="text-emerald-400 font-mono text-base font-black">100/100</span>
                <span>نمره رسمی سرعت Core Web Vitals</span>
            </span>
            <span class="text-slate-700 hidden sm:inline">•</span>
            <span class="flex items-center gap-2">
                <span class="text-secondary font-mono text-base font-black">&lt; 1.0s</span>
                <span>زمان لود کامل در مرورگر</span>
            </span>
            <span class="text-slate-700 hidden sm:inline">•</span>
            <span class="flex items-center gap-2">
                <span class="text-sky-400 font-mono text-base font-black">0%</span>
                <span>استفاده از المنتور یا افزونه‌های مخرب</span>
            </span>
        </div>
    </div>
</section>

<!-- Portfolio Grid Section with Category Filters -->
<section class="py-16 lg:py-24 bg-slate-50 border-b border-slate-200/80" 
         x-data="{ activeCat: 'all' }">
    <div class="container mx-auto px-4 sm:px-6">
        
        <!-- Category Filter Tabs -->
        <?php if (!empty($portfolio_categories) && !is_wp_error($portfolio_categories)) : ?>
            <div class="flex flex-wrap items-center justify-center gap-2.5 mb-14" data-aos="fade-up">
                <button @click="activeCat = 'all'"
                        :class="activeCat === 'all' ? 'bg-primary text-white shadow-md' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200'"
                        class="px-5 py-2.5 rounded-full text-xs sm:text-sm font-black transition-all cursor-pointer">
                    همه پروژه‌ها
                </button>
                <?php foreach ($portfolio_categories as $cat) : ?>
                    <button @click="activeCat = '<?= esc_attr($cat->slug); ?>'"
                            :class="activeCat === '<?= esc_attr($cat->slug); ?>' ? 'bg-primary text-white shadow-md' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200'"
                            class="px-5 py-2.5 rounded-full text-xs sm:text-sm font-black transition-all cursor-pointer">
                        <?= esc_html($cat->name); ?>
                    </button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Portfolio Cards Grid -->
        <?php
        $args = array(
            'post_type'           => 'portfolio',
            'post_status'         => 'publish',
            'order'               => 'DESC',
            'posts_per_page'      => -1,
            'ignore_sticky_posts' => true
        );
        $loop = new WP_Query($args);
        if ($loop->have_posts()) :
        ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php while ($loop->have_posts()) : $loop->the_post(); 
                    $post_terms = wp_get_post_terms(get_the_ID(), 'portfolio_categories', ['fields' => 'slugs']);
                    $terms_json = json_encode($post_terms ?: []);
                ?>
                    <div x-show="activeCat === 'all' || <?= esc_attr($terms_json); ?>.includes(activeCat)"
                         x-transition
                         class="h-full">
                        <?php
                        get_template_part('template-parts/portfolio/portfolio-card', null, array(
                            'class' => 'h-full'
                        )); 
                        ?>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else : ?>
            <div class="py-16 text-center max-w-md mx-auto">
                <p class="font-bold text-lg text-slate-500">هیچ نمونه‌کاری در حال حاضر ثبت نشده است.</p>
            </div>
        <?php
        endif;
        wp_reset_postdata(); ?>
    </div>
</section>

<!-- Call to action at bottom of portfolios -->
<section class="py-16 lg:py-20 bg-slate-950 text-white relative overflow-hidden select-none">
    <div class="container mx-auto px-4 sm:px-6 relative z-10 text-center max-w-3xl">
        <span class="text-xs font-black uppercase text-secondary tracking-widest block mb-2">شروع همکاری</span>
        <h3 class="text-2xl sm:text-4xl font-black text-white mb-4 leading-tight">
            می‌خواهید وب‌سایت شما هم با این استاندارد و ظرافت پیاده‌سازی شود؟
        </h3>
        <p class="text-slate-400 text-sm sm:text-base leading-relaxed mb-8">
            از پیکربندی هوشمند پروژه استفاده کنید یا مستقیماً برای مشاوره فنی با ما پیام دهید.
        </p>
        <div class="flex flex-wrap items-center justify-center gap-4">
            <a href="<?= esc_url(get_post_type_archive_link('services') ?: home_url('/services')); ?>#smart-configurator"
               class="bg-secondary hover:bg-secondary-hover text-white font-black text-sm px-8 py-4 rounded-full transition-all shadow-xl shadow-secondary/30 hover:scale-105 active:scale-95">
                محاسبه آنلاین نیازمندی‌های پروژه
            </a>
            <a href="https://wa.me/989358088118?text=<?= urlencode('سلام، نمونه‌کارهای سایت را مشاهده کردم و مایل به مشاوره برای اجرای پروژه‌ام هستم.'); ?>"
               target="_blank"
               class="bg-white/10 hover:bg-white/15 text-white font-bold text-sm px-8 py-4 rounded-full transition-all border border-white/20">
                ارتباط مستقیم در واتساپ
            </a>
        </div>
    </div>
</section>

<?php get_footer(); ?>
