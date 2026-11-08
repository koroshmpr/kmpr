<?php
$links = paginate_links(array(
    'type'      => 'array',
    'prev_text' => '&rarr; قبلی',
    'next_text' => 'بعدی &larr;',
));
if ($links) : ?>
    <nav class="flex justify-center items-center gap-2 my-10" aria-label="Page Navigation">
        <ul class="flex flex-wrap justify-center items-center gap-2 list-none p-0 m-0">
            <?php foreach ($links as $link) : ?>
                <li class="inline-flex [&_a]:inline-flex [&_a]:items-center [&_a]:justify-center [&_a]:min-h-11 [&_a]:min-w-11 [&_a]:whitespace-nowrap [&_a]:px-4 [&_a]:py-2 [&_a]:rounded-xl [&_a]:bg-white/[0.03] [&_a]:border [&_a]:border-white/10 [&_a:hover]:border-secondary/40 [&_a:hover]:text-white [&_a]:text-slate-300 [&_a]:font-bold [&_a]:text-xs sm:[&_a]:text-sm [&_a]:transition-colors [&_span.current]:inline-flex [&_span.current]:items-center [&_span.current]:justify-center [&_span.current]:min-h-11 [&_span.current]:min-w-11 [&_span.current]:px-4 [&_span.current]:py-2 [&_span.current]:rounded-xl [&_span.current]:bg-secondary [&_span.current]:text-slate-950 [&_span.current]:font-black [&_span.current]:text-xs sm:[&_span.current]:text-sm [&_span.dots]:px-2 [&_span.dots]:text-slate-400">
                    <?= $link; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>
<?php endif; ?>
