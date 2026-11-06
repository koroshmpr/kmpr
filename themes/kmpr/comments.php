<?php
/**
 * The template for displaying comments
 */
if (post_password_required()) {
    return;
}

$kmpr_comment_count = get_comments_number();
?>

<section class="bg-slate-50 border border-slate-200/80 rounded-3xl p-6 lg:p-8 flex flex-col gap-8 text-start">
    <div class="flex items-center justify-between border-b border-slate-200 pb-4">
        <h3 class="text-lg lg:text-xl font-black text-slate-900 flex items-center gap-2">
            <span>دیدگاه‌های کاربران</span>
            <?php if ($kmpr_comment_count > 0) : ?>
                <span class="bg-secondary/20 text-primary text-xs font-extrabold px-2.5 py-1 rounded-lg">
                    <?= number_format_i18n($kmpr_comment_count); ?>
                </span>
            <?php endif; ?>
        </h3>
    </div>

    <?php if (have_comments()) : ?>
        <ol class="flex flex-col gap-4 list-none p-0 m-0">
            <?php
            wp_list_comments(array(
                'style'       => 'ol',
                'short_ping'  => true,
                'avatar_size' => 44,
                'format'      => 'html5',
            ));
            ?>
        </ol>

        <?php the_comments_navigation(); ?>
    <?php else : ?>
        <div class="bg-white border border-dashed border-slate-200 rounded-2xl p-6 text-center text-slate-500 text-sm font-medium">
            هنوز دیدگاهی برای این مطلب ثبت نشده است. اولین نفری باشید که دیدگاه خود را ثبت می‌کند!
        </div>
    <?php endif; ?>

    <?php if (comments_open()) : ?>
        <div class="bg-white border border-slate-200/80 rounded-2xl p-6 shadow-xs">
            <?php
            comment_form(array(
                'title_reply'          => 'ثبت دیدگاه جدید',
                'title_reply_before'   => '<h4 id="reply-title" class="comment-reply-title text-base font-extrabold text-slate-800 mb-4">',
                'title_reply_after'    => '</h4>',
                'label_submit'         => 'ارسال دیدگاه',
                'comment_notes_before' => '<p class="comment-notes text-xs text-slate-400 mb-2">نشانی ایمیل شما منتشر نخواهد شد.</p>',
            ));
            ?>
        </div>
    <?php endif; ?>
</section>
