<?php
$author_id = get_post_field('post_author', get_the_ID());
$user_array_img = get_field('profile_image', 'user_' . $author_id);

if ($user_array_img && !empty($user_array_img['url'])) { ?>
    <img class="rounded-full size-16 lg:size-20 object-cover border-2 border-secondary shadow-sm shrink-0"
         width="80" height="80"
         src="<?php echo esc_url($user_array_img['url']); ?>"
         alt="<?php echo esc_attr($user_array_img['alt'] ?: get_the_author_meta('display_name', $author_id)); ?>">
<?php } else { ?>
    <div class="rounded-full size-16 lg:size-20 bg-white border-2 border-secondary/40 flex items-center justify-center text-primary shrink-0 shadow-sm overflow-hidden p-2">
        <?php if (get_field('author-image-default', 'option')) : ?>
            <svg xmlns="http://www.w3.org/2000/svg" version="1.0" width="<?= esc_attr($args['sizeSvgX'] ?? '48'); ?>"
                 height="<?= esc_attr($args['sizeSvgY'] ?? '48'); ?>" viewBox="0 0 83.000000 70.000000"
                 preserveAspectRatio="xMidYMid meet">
                <?= get_field('author-image-default', 'option'); ?>
            </svg>
        <?php else : ?>
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="text-slate-400" viewBox="0 0 16 16">
                <path d="M3 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1H3zm5-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/>
            </svg>
        <?php endif; ?>
    </div>
<?php } ?>