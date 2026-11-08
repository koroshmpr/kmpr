<?php
/**
 * Portfolio archive (/portfolios/) — hero, stats, crawlable category filter, paginated case-study grid, CTA.
 * Copy is editable in «نمونه‌کارها › تنظیمات آرشیو».
 */
get_header();
get_template_part('template-parts/portfolio/archive-layout', null, array('term' => null));
get_footer();
