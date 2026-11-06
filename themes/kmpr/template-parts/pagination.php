<?php
$links = paginate_links(array(
    'type'      => 'array',
    'prev_text' => '&rarr; قبلی',
    'next_text' => 'بعدی &larr;',
));
if ($links) : ?>
    <nav class="flex justify-center items-center gap-2 my-10" aria-label="Page Navigation">
        <ul class="flex items-center gap-2 list-none p-0 m-0">
            <?php foreach ($links as $link) : ?>
                <li class="inline-flex [&_a]:px-4 [&_a]:py-2 [&_a]:rounded-xl [&_a]:bg-slate-100 [&_a:hover]:bg-slate-200 [&_a]:text-slate-800 [&_a]:font-bold [&_a]:text-xs sm:[&_a]:text-sm [&_a]:transition-colors [&_span.current]:px-4 [&_span.current]:py-2 [&_span.current]:rounded-xl [&_span.current]:bg-primary [&_span.current]:text-white [&_span.current]:font-black [&_span.current]:text-xs sm:[&_span.current]:text-sm [&_span.dots]:px-2">
                    <?= $link; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>
<?php endif; ?>
