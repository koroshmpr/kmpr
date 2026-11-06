<?php
/**
 * The template for displaying comments - Dark Obsidian Theme
 */
if (post_password_required()) {
    return;
}

$kmpr_comment_count = get_comments_number();
?>

<section class="bg-slate-900/80 border border-white/10 rounded-3xl p-6 lg:p-8 flex flex-col gap-8 text-start backdrop-blur-xl shadow-xl">
    <div class="flex items-center justify-between border-b border-white/10 pb-4">
        <h3 class="text-lg lg:text-xl font-black text-white flex items-center gap-2">
            <span>دیدگاه‌های کاربران</span>
            <?php if ($kmpr_comment_count > 0) : ?>
                <span class="bg-secondary text-white text-xs font-black px-2.5 py-1 rounded-xl shadow-xs">
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
        <div class="bg-slate-950/60 border border-dashed border-white/10 rounded-2xl p-6 text-center text-slate-400 text-xs sm:text-sm font-medium">
            هنوز دیدگاهی برای این مطلب ثبت نشده است. اولین نفری باشید که دیدگاه یا تجربه خود را به اشتراک می‌گذارد!
        </div>
    <?php endif; ?>

    <?php if (comments_open()) : ?>
        <div class="bg-slate-950/70 border border-white/10 rounded-2xl p-6 shadow-md">
            <?php
            comment_form(array(
                'title_reply'          => 'ثبت دیدگاه یا پرسش فنی',
                'title_reply_before'   => '<h4 id="reply-title" class="comment-reply-title text-base font-black text-white mb-4">',
                'title_reply_after'    => '</h4>',
                'label_submit'         => 'ارسال دیدگاه',
                'class_submit'         => 'bg-secondary hover:bg-secondary-hover text-white text-xs sm:text-sm font-black px-6 py-2.5 rounded-xl transition-all cursor-pointer shadow-lg shadow-secondary/25 hover:scale-105 active:scale-95',
                'comment_notes_before' => '<p class="comment-notes text-xs text-slate-400 mb-4">نشانی ایمیل شما منتشر نخواهد شد. بخش‌های موردنیاز علامت‌گذاری شده‌اند.</p>',
            ));
            ?>
        </div>
    <?php endif; ?>
</section>
