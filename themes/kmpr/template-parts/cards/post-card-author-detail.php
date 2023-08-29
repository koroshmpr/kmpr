<div class="col-11 d-flex gap-2 align-items-center justify-content-start">
    <?php get_template_part('template-parts/cards/post-detail/author-image'); ?>
    <div class="info-part">
        <div class="fs-6 text-center">
            ارسال توسط
            <span class="fw-bold">
                <?php echo get_the_author_meta('display_name', $post->post_author); ?>
                </span>
        </div>
        <div class="fw-normal fs-6">
            <?php echo get_the_date('d  F , Y'); ?>
        </div>
    </div>
</div>