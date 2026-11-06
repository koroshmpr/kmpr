<?php
$author_id = get_post_field('post_author', get_the_ID());
$author_name = get_the_author_meta('nickname', $author_id) ?: get_the_author_meta('display_name', $author_id);
$author_bio = get_the_author_meta('description', $author_id);
?>
<div id="author-section" class="my-4 bg-secondary/15 border border-secondary/30 rounded-2xl p-6 flex flex-col sm:flex-row items-center sm:items-start gap-5 text-center sm:text-start">
    <?php
    get_template_part('template-parts/cards/post-detail/author-image', null, array(
        'sizeSvgY' => '64',
        'sizeSvgX' => '64'
    ));
    ?>
    <div class="flex flex-col gap-2 flex-1 min-w-0">
        <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-2">
            <span class="text-xs font-bold text-slate-500">نویسنده مقاله:</span>
            <h5 class="text-base lg:text-lg font-black text-slate-900"><?= esc_html($author_name); ?></h5>
        </div>
        <?php if ($author_bio) : ?>
            <p class="text-sm text-slate-600 leading-relaxed">
                <?= esc_html($author_bio); ?>
            </p>
        <?php endif; ?>
    </div>
</div>