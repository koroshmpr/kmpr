<?php $user_array_img = get_field('profile_image', 'user_' . $post->post_author);

if ($user_array_img) { ?>

    <img class="rounded-circle img-fluid" width="42" height="42" src="<?php echo $user_array_img['url'] ?>"

         alt="<?php echo $user_array_img['alt'] ?>">

<?php } else {

    ?>
    <svg xmlns="http://www.w3.org/2000/svg" version="1.0" width="<?= $args['sizeSvgX'] ?? '36';?>" height="<?= $args['sizeSvgY'] ?? '36';?>" viewBox="0 0 83.000000 70.000000" preserveAspectRatio="xMidYMid meet">
    <?= get_field('author-image-default', 'option'); ?>
    </svg>
    <?php

} ?>