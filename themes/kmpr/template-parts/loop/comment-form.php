<?php
// Custom callback function to display comments in Bootstrap format
function bootstrap_comments($comment, $args, $depth) {
    $GLOBALS['comment'] = $comment; ?>
    <li <?php comment_class('media'); ?> id="comment-<?php comment_ID(); ?>">
        <div class="media-body">
            <h5 class="mt-0 mb-1"><?php printf(__('%s'), get_comment_author_link()); ?></h5>
            <div class="comment-meta mb-2">
                <a href="<?php echo htmlspecialchars(get_comment_link($comment->comment_ID)); ?>">
                    <time datetime="<?php comment_time('c'); ?>">
                        <?php printf(__('%1$s at %2$s'), get_comment_date(), get_comment_time()); ?>
                    </time>
                </a>
            </div>
            <div class="comment-text mb-3"><?php comment_text(); ?></div>
            <?php if ($comment->comment_approved == '0') : ?>
                <em class="comment-awaiting-moderation"><?php _e('Your comment is awaiting moderation.'); ?></em>
                <br />
            <?php endif; ?>
            <div class="reply">
                <?php
                comment_reply_link(array_merge($args, array(
                    'depth' => $depth,
                    'max_depth' => $args['max_depth'],
                    'reply_text' => __('Reply', 'textdomain'),
                    'before' => '<span class="mr-2"> &middot; </span>'
                )));
                ?>
            </div>
        </div>
    </li>
<?php } ?>

<!-- Display comments section -->
<?php if (comments_open()) : ?>
    <div id="comments" class="comments-section">
        <h3 class="comments-title"><?php comments_number(__('No Comments', 'textdomain'), __('1 Comment', 'textdomain'), __('% Comments', 'textdomain')); ?></h3>
        <ul class="comments-list">
            <?php
            wp_list_comments(array(
                'callback' => 'bootstrap_comments',
                'style' => 'ul'
            ));
            ?>
        </ul>
    </div>
<?php endif; ?>

<!-- Begin Comment Form -->
<div id="respond" class="comment-respond">
    <h3 id="reply-title"
        class="comment-reply-title fw-bold"><?php comment_form_title('دیدگاه بگذارید', 'به %s پاسخ دهید'); ?></h3>
    <form id="commentform" class="comment-form" action="<?php echo get_option('siteurl'); ?>/wp-comments-post.php"
           method="post">
        <?php if (is_user_logged_in()) : ?>
            <p class="logged-in-as">شمار وارد شدید</p>
        <?php else : ?>
            <p class="comment-notes"><?php __('آدرس ایمیل شما منتشر نخواهد شد.', 'twentytwenty'); ?></p>
            <div class="form-group row">
                <div class="col-md-4 mb-3">
                    <div class="form-floating">
                        <input id="author" name="author" type="text" class="form-control"
                               value="<?php echo esc_attr($comment_author); ?>"
                               placeholder="نام خود را وارد کنید"
                               <?php if ($req) : ?>required<?php endif; ?> />
                        <label class="d-flex gap-2 align-items-center" for="author"><i class="bi bi-person"></i> نام <?php if ($req) : ?> <span
                                    class="required">*</span><?php endif; ?></label>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="form-floating">
                        <input id="phone" name="phone" type="tel" class="form-control"
                               value="<?php echo esc_attr($comment_author_phone); ?>"
                               placeholder="شماره تماس خود را وارد کنید"
                               required />
                        <label class="d-flex gap-2 align-items-center" for="phone"><i class="bi bi-telephone"></i> شماره تماس <?php if ($req) : ?> <span
                                    class="required">*</span><?php endif; ?></label>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="form-floating">
                        <input id="email" name="email" type="email" class="form-control"
                               value="<?php echo esc_attr($comment_author_email); ?>"
                               placeholder="ایمیل خود را وارد کنید" />
                        <label class="d-flex gap-2 align-items-center" for="email"><i class="bi bi-envelope"></i> ایمیل</label>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        <div class="form-group">
            <div class="form-floating">
                <textarea id="comment" name="comment" class="form-control"
                          placeholder="متن نظر ... "
                          <?php if ($req) : ?>required<?php endif; ?> ></textarea>
                <?php if ($req) : ?>required<?php endif; ?></textarea>
                <label class="d-flex gap-2 align-items-center" for="comment"><i class="bi bi-chat-right-dots"></i>متن نظر ...
                    <?php if ($req) : ?> <span class="required">*</span><?php endif; ?>
                </label>
            </div>
            <p class="form-submit mt-3">
                <input name="submit" type="submit" id="submit" class="btn bg-primary px-5 py-3 text-white fw-bold d-none d-lg-inline"
                       value="ارسال تظر"/>
                <?php comment_id_fields(); ?>
            </p>
            <?php do_action('comment_form', $post->ID); ?>
    </form>
</div>
<!-- End Comment Form -->
