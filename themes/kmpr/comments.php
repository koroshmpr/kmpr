<?php
/**
 * Comments — threaded dark list + composer card.
 * JS (kmprComments in javascript/blog.js): AJAX submit, inline reply, sort, char counter, inline errors.
 * Without JS the form posts to wp-comments-post.php as usual.
 *
 * @package KMPR
 */
if (post_password_required()) {
    return;
}
$post_id   = get_the_ID();
$count     = (int) get_comments_number();
$commenter = wp_get_current_commenter();
$user      = wp_get_current_user();
$logged_in = $user->exists();
$req       = (bool) get_option('require_name_email');
$max_len   = 3000;
$cur_name  = $logged_in ? $user->display_name : $commenter['comment_author'];
?>
<section id="comments" class="kmpr-comments scroll-mt-28" aria-labelledby="comments-title"
         x-data="kmprComments({ postId: <?= (int) $post_id; ?>, max: <?= (int) $max_len; ?>, name: <?= esc_attr(wp_json_encode($cur_name)); ?>, loggedIn: <?= $logged_in ? 'true' : 'false'; ?>, total: <?= (int) $count; ?> })"
         @kmpr-reply="reply($event.detail)">

    <header class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-8">
        <div>
            <span class="inline-flex items-center gap-2 text-xs font-black text-secondary mb-3"><span class="w-6 h-px bg-secondary"></span>گفتگو</span>
            <h2 id="comments-title" class="text-2xl sm:text-3xl font-black text-white">
                <?php if ($count) : ?>
                    <span x-text="$store.lessons.fa(total) + ' دیدگاه'"><?= esc_html(kmpr_fa_num($count)); ?> دیدگاه</span>
                <?php else : ?>
                    سؤال یا تجربه‌ات را بنویس
                <?php endif; ?>
            </h2>
        </div>
        <?php if ($count > 1) : ?>
            <div class="inline-flex self-start sm:self-auto items-center gap-1 p-1 rounded-full bg-white/[0.04] border border-white/10" role="group" aria-label="مرتب‌سازی دیدگاه‌ها">
                <?php foreach (array('newest' => 'جدیدترین', 'oldest' => 'قدیمی‌ترین', 'helpful' => 'مفیدترین') as $k => $label) : ?>
                    <button type="button" @click="sort('<?= esc_attr($k); ?>')" :aria-pressed="(order === '<?= esc_attr($k); ?>').toString()"
                            class="min-h-10 px-4 rounded-full text-[13px] font-bold whitespace-nowrap transition-colors cursor-pointer"
                            :class="order === '<?= esc_attr($k); ?>' ? 'bg-secondary text-slate-950' : 'text-slate-300 hover:text-white'"><?= esc_html($label); ?></button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </header>

    <ol x-ref="list" class="kmpr-comment-list list-none p-0 m-0 flex flex-col gap-4 <?= have_comments() ? 'mb-10' : ''; ?>">
        <?php if (have_comments()) {
            wp_list_comments(array(
                'style'      => 'ol',
                'callback'   => 'kmpr_comment_callback',
                'short_ping' => true,
                'max_depth'  => (int) get_option('thread_comments_depth', 2),
            ));
        } ?>
    </ol>

    <?php if (!have_comments() && comments_open()) : ?>
        <div x-show="total === 0" class="mb-8 flex flex-col items-center text-center gap-3 rounded-3xl border border-dashed border-white/15 px-6 py-10">
            <span class="inline-flex items-center justify-center size-14 rounded-2xl bg-secondary/15 text-secondary">
                <svg width="26" height="26" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12Z"/><path d="M8.5 12h.01M12 12h.01M15.5 12h.01"/></svg>
            </span>
            <p class="text-lg font-black text-white">اولین نفری باشید که دیدگاه می‌نویسد</p>
            <p class="text-sm text-slate-300 max-w-md leading-7">اگر سؤالی درباره این درس داری یا تجربه‌ای از پیاده‌سازی‌اش، همین‌جا بنویس؛ خودم جواب می‌دهم.</p>
        </div>
    <?php endif; ?>

    <?php if (have_comments() && get_comment_pages_count() > 1) : ?>
        <nav class="kmpr-pagination mb-10" aria-label="صفحه‌بندی دیدگاه‌ها"><?php paginate_comments_links(array('prev_text' => 'قبلی', 'next_text' => 'بعدی')); ?></nav>
    <?php endif; ?>

    <?php if (comments_open()) : ?>
        <div x-ref="home"></div>
        <div id="respond" x-ref="composer" class="comment-respond scroll-mt-28">
            <form action="<?= esc_url(site_url('/wp-comments-post.php')); ?>" method="post" id="commentform" novalidate
                  @submit.prevent="submit($el)"
                  class="kmpr-composer rounded-3xl bg-slate-900/80 border border-white/10 p-5 sm:p-7 flex flex-col gap-5 transition-colors"
                  :class="replyTo ? 'border-secondary/40' : ''">

                <div class="flex items-center gap-3">
                    <span class="size-11 shrink-0 inline-flex items-center justify-center rounded-2xl text-sm font-black text-white select-none"
                          :style="avatarStyle()" x-text="initials()" aria-hidden="true">؟</span>
                    <div class="min-w-0 flex-1">
                        <h3 id="reply-title" class="text-base font-black text-white" x-text="replyTo ? 'نوشتن پاسخ' : 'ارسال دیدگاه'">ارسال دیدگاه</h3>
                        <p class="text-[13px] text-slate-400 truncate">
                            <?php if ($logged_in) : ?>
                                با نام <strong class="text-slate-200"><?= esc_html($user->display_name); ?></strong>
                            <?php else : ?>
                                ایمیل شما منتشر نمی‌شود.
                            <?php endif; ?>
                        </p>
                    </div>
                </div>

                <div x-show="replyTo" x-cloak class="flex items-center gap-2 self-start ps-3 pe-1 py-1 rounded-full bg-secondary/15 border border-secondary/30 text-[13px] font-bold text-amber-200">
                    <span>در پاسخ به <span x-text="replyTo ? replyTo.name : ''"></span></span>
                    <button type="button" @click="cancelReply()" class="inline-flex items-center justify-center size-8 rounded-full hover:bg-white/10 cursor-pointer" aria-label="لغو پاسخ">
                        <svg width="14" height="14" class="shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M5.3 5.3a1 1 0 0 1 1.4 0L10 8.6l3.3-3.3a1 1 0 1 1 1.4 1.4L11.4 10l3.3 3.3a1 1 0 0 1-1.4 1.4L10 11.4l-3.3 3.3a1 1 0 0 1-1.4-1.4L8.6 10 5.3 6.7a1 1 0 0 1 0-1.4Z"/></svg>
                    </button>
                </div>

                <div>
                    <label for="comment" class="kmpr-label">متن دیدگاه <span class="text-secondary">*</span></label>
                    <textarea id="comment" name="comment" rows="5" maxlength="<?= (int) $max_len; ?>" required x-ref="text" x-model="text"
                              @input="errors.comment = ''" :aria-invalid="(!!errors.comment).toString()" aria-describedby="comment-help comment-error"
                              class="kmpr-field min-h-36 leading-8 resize-y" placeholder="سؤال، تجربه یا نکته‌ای که اضافه می‌کنی..."></textarea>
                    <div class="mt-2 flex items-start justify-between gap-3 text-xs">
                        <p id="comment-help" class="text-slate-400 leading-6">برای کد از <code class="px-1.5 py-0.5 rounded bg-white/10 text-amber-200" dir="ltr">`backtick`</code> استفاده کن؛ لینک‌ها خودکار ساخته می‌شوند.</p>
                        <span class="shrink-0 font-bold" :class="text.length > max * 0.9 ? 'text-amber-300' : 'text-slate-400'"
                              x-text="$store.lessons.fa(text.length) + ' / ' + $store.lessons.fa(max)"></span>
                    </div>
                    <p id="comment-error" class="kmpr-error" x-show="errors.comment" x-text="errors.comment" x-cloak role="alert"></p>
                </div>

                <?php if (!$logged_in) : ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="author" class="kmpr-label">نام<?= $req ? ' <span class="text-secondary">*</span>' : ''; ?></label>
                            <input id="author" name="author" type="text" autocomplete="name" maxlength="245" x-model="name" @input="errors.author = ''"
                                   value="<?= esc_attr($commenter['comment_author']); ?>" <?= $req ? 'required' : ''; ?> :aria-invalid="(!!errors.author).toString()"
                                   class="kmpr-field" placeholder="مثلاً سارا">
                            <p class="kmpr-error" x-show="errors.author" x-text="errors.author" x-cloak role="alert"></p>
                        </div>
                        <div>
                            <label for="email" class="kmpr-label">ایمیل<?= $req ? ' <span class="text-secondary">*</span>' : ''; ?></label>
                            <input id="email" name="email" type="email" dir="ltr" autocomplete="email" maxlength="100" @input="errors.email = ''"
                                   value="<?= esc_attr($commenter['comment_author_email']); ?>" <?= $req ? 'required' : ''; ?> :aria-invalid="(!!errors.email).toString()"
                                   class="kmpr-field text-start" placeholder="you@example.com">
                            <p class="kmpr-error" x-show="errors.email" x-text="errors.email" x-cloak role="alert"></p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Honeypot (bots fill it, people never see it) -->
                <div class="kmpr-hp" aria-hidden="true">
                    <label for="kmpr_website">وب‌سایت</label>
                    <input id="kmpr_website" name="kmpr_website" type="text" tabindex="-1" autocomplete="off">
                </div>
                <input type="hidden" name="kmpr_ts" value="<?= (int) time(); ?>">
                <input type="hidden" name="kmpr_nonce" value="<?= esc_attr(wp_create_nonce('kmpr_comment')); ?>">
                <input type="hidden" name="comment_post_ID" value="<?= (int) $post_id; ?>">
                <input type="hidden" name="comment_parent" id="comment_parent" :value="replyTo ? replyTo.id : 0" value="0">

                <div class="flex flex-col-reverse sm:flex-row sm:items-center justify-between gap-4 pt-2">
                    <?php if (!$logged_in) : ?>
                        <label for="wp-comment-cookies-consent" class="inline-flex items-center gap-3 cursor-pointer select-none text-[13px] text-slate-300">
                            <input id="wp-comment-cookies-consent" name="wp-comment-cookies-consent" type="checkbox" value="yes" class="peer sr-only" <?= empty($commenter['comment_author_email']) ? '' : 'checked'; ?>>
                            <span class="kmpr-switch" aria-hidden="true"></span>
                            نام و ایمیلم برای دفعه بعد ذخیره شود
                        </label>
                    <?php else : ?>
                        <span></span>
                    <?php endif; ?>
                    <button type="submit" :disabled="busy"
                            class="inline-flex items-center justify-center gap-2 self-stretch sm:self-auto min-h-11 px-8 py-3.5 rounded-full bg-secondary hover:bg-amber-500 disabled:opacity-60 text-slate-950 text-sm font-black whitespace-nowrap transition-colors cursor-pointer">
                        <svg x-show="!busy" width="16" height="16" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="m22 2-7 20-4-9-9-4 20-7Z"/><path d="M22 2 11 13"/></svg>
                        <span x-show="busy" x-cloak class="size-4 rounded-full border-2 border-slate-950/30 border-t-slate-950 animate-spin" aria-hidden="true"></span>
                        <span x-text="busy ? 'در حال ارسال...' : (replyTo ? 'ارسال پاسخ' : 'ثبت دیدگاه')">ثبت دیدگاه</span>
                    </button>
                </div>
                <p class="kmpr-error -mt-2" x-show="errors.general" x-text="errors.general" x-cloak role="alert"></p>
                <p x-show="notice" x-text="notice" x-cloak role="status" class="-mt-2 text-sm font-bold text-emerald-400"></p>
            </form>
        </div>
    <?php else : ?>
        <p class="text-sm text-slate-400">ارسال دیدگاه برای این درس بسته شده است.</p>
    <?php endif; ?>
</section>
