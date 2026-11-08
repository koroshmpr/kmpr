<?php
/**
 * Crawlable pagination for the main query (paginate_links → real <a href> links).
 * $args['query'] optional WP_Query (defaults to the main query).
 */
$q     = $args['query'] ?? $GLOBALS['wp_query'];
$links = paginate_links(array(
    'type'      => 'array',
    'total'     => (int) $q->max_num_pages,
    'current'   => max(1, (int) get_query_var('paged')),
    'mid_size'  => 1,
    'prev_text' => '<span aria-hidden="true">&rarr;</span> قبلی',
    'next_text' => 'بعدی <span aria-hidden="true">&larr;</span>',
));
if ($links) : ?>
    <nav class="kmpr-pagination pt-12" aria-label="صفحه‌بندی">
        <ul class="flex flex-wrap justify-center items-center gap-2 list-none p-0 m-0">
            <?php foreach ($links as $link) : ?>
                <li><?= wp_kses_post($link); ?></li>
            <?php endforeach; ?>
        </ul>
    </nav>
<?php endif;
