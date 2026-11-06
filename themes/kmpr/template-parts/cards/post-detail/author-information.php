<?php
$author_id = get_post_field('post_author', get_the_ID());
$author_name = get_the_author_meta('nickname', $author_id) ?: get_the_author_meta('display_name', $author_id);
$author_bio = get_the_author_meta('description', $author_id);
?>
<div id="author-section" class="my-6 bg-slate-50 border border-slate-200/80 rounded-3xl p-6 sm:p-7 flex flex-col sm:flex-row items-center sm:items-start gap-5 text-center sm:text-start shadow-xs">
    <div class="shrink-0">
        <?php
        get_template_part('template-parts/cards/post-detail/author-image', null, array(
            'sizeSvgY' => '64',
            'sizeSvgX' => '64'
        ));
        ?>
    </div>
    <div class="flex flex-col gap-2 flex-1 min-w-0">
        <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-2">
            <span class="text-xs font-bold text-slate-400">نویسنده مقاله:</span>
            <h4 class="text-base lg:text-lg font-black text-slate-900"><?= esc_html($author_name); ?></h4>
        </div>
        <?php if ($author_bio) : ?>
            <p class="text-xs sm:text-sm text-slate-600 leading-relaxed font-medium">
                <?= esc_html($author_bio); ?>
            </p>
        <?php endif; ?>
    </div>
</div>
