<?php
$links = paginate_links(array(
    'type' => 'array',
    'prev_next' => false,
));
if ($links) : ?>
    <nav class="pt-8" aria-label="صفحه‌بندی">
        <ul class="flex flex-wrap justify-center items-center gap-2 list-none p-0 m-0">
            <?php if ($prev_posts_link = get_previous_posts_link(__('قبلی'))) : ?>
                <li class="page-item">
                    <?= $prev_posts_link; ?>
                </li>
            <?php endif; ?>
            <?php foreach ($links as $link) : ?>
                <li class="page-item">
                    <?= $link; ?>
                </li>
            <?php endforeach; ?>
            <?php if ($next_posts_link = get_next_posts_link(__('بعدی'))) : ?>
                <li class="page-item">
                    <?= $next_posts_link; ?>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
<?php endif;
wp_reset_postdata();
?>