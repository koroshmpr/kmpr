<div class="flex justify-center my-4 <?= $args['containerCLass'] ?? '';?>">
    <div class="w-full max-w-lg <?= $args['mainClass'] ?? 'flex flex-col lg:flex-row-reverse';?> gap-3 justify-center items-center">
        <p class="text-lg font-bold text-slate-800 mb-0 <?= $args['headingClass'] ?? '';?>">
            اشتراک گذاری
        </p>
        <?php
        $postUrl = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $postTitle = urlencode(get_the_title());
        $twitterHandle = get_the_author_meta('twitter');
        $websiteName = get_bloginfo('name');
        ?>
        <ul class="flex items-center gap-2 m-0 justify-center p-2 rounded-xl <?= $args['class'] ?? '';?>">
            <li data-aos="fade-up" data-aos-duration="500">
                <a aria-label="share-in-aparat" 
                   class="flex items-center justify-center size-10 bg-white hover:bg-primary hover:text-white border border-slate-200/80 rounded-full transition-all duration-300 shadow-sm"
                   href="https://www.aparat.com/share?url=<?php echo urlencode($postUrl); ?>">
                    <?php get_template_part('template-parts/svg/social/aparat', null, $args); ?>
                </a>
            </li>
            <li data-aos="fade-up" data-aos-delay="100" data-aos-duration="500">
                <a aria-label="share-in-telegram" 
                   class="flex items-center justify-center size-10 bg-white hover:bg-primary hover:text-white border border-slate-200/80 rounded-full transition-all duration-300 shadow-sm"
                   href="https://telegram.me/share/url?url=<?php echo urlencode($postUrl); ?>&text=<?php echo $postTitle; ?>">
                    <?php get_template_part('template-parts/svg/social/telegram', null, $args); ?>
                </a>
            </li>
            <li data-aos="fade-up" data-aos-delay="200" data-aos-duration="500">
                <a aria-label="share-in-youtube" 
                   class="flex items-center justify-center size-10 bg-white hover:bg-primary hover:text-white border border-slate-200/80 rounded-full transition-all duration-300 shadow-sm"
                   href="https://www.youtube.com/share?url=<?php echo urlencode($postUrl); ?>">
                    <?php get_template_part('template-parts/svg/social/youtube', null, $args); ?>
                </a>
            </li>
            <li data-aos="fade-up" data-aos-delay="300" data-aos-duration="500">
                <a aria-label="share-in-instagram" 
                   class="flex items-center justify-center size-10 bg-white hover:bg-primary hover:text-white border border-slate-200/80 rounded-full transition-all duration-300 shadow-sm"
                   href="https://www.instagram.com/share?url=<?php echo urlencode($postUrl); ?>">
                    <?php get_template_part('template-parts/svg/social/instagram', null, $args); ?>
                </a>
            </li>
            <li data-aos="fade-up" data-aos-delay="400" data-aos-duration="500">
                <a aria-label="share-in-twitter" 
                   class="flex items-center justify-center size-10 bg-white hover:bg-primary hover:text-white border border-slate-200/80 rounded-full transition-all duration-300 shadow-sm"
                   href="https://twitter.com/intent/tweet?text=<?php echo urlencode('Check out this awesome post from ' . $websiteName . ': ') . $postTitle; ?>&url=<?php echo urlencode($postUrl); ?>&via=<?php echo urlencode($twitterHandle); ?>">
                    <?php get_template_part('template-parts/svg/social/twitter', null, $args); ?>
                </a>
            </li>
            <li data-aos="fade-up" data-aos-delay="500" data-aos-duration="500">
                <a aria-label="share-in-linkedin" 
                   class="flex items-center justify-center size-10 bg-white hover:bg-primary hover:text-white border border-slate-200/80 rounded-full transition-all duration-300 shadow-sm"
                   href="https://www.linkedin.com/shareArticle?mini=true&url=<?php echo urlencode($postUrl); ?>&title=<?php echo $postTitle; ?>">
                    <?php get_template_part('template-parts/svg/social/linkedin', null, $args); ?>
                </a>
            </li>
        </ul>
    </div>
</div>