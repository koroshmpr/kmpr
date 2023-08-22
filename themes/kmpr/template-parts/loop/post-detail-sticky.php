<?php $comment_count = get_comments_number(); // Get the number of comments for this post  ?>
<div class="sticky-post__detail w-100 bg-dark d-none">
    <div class="container py-2 py-lg-0">
        <p class="mb-0 d-lg-none text-white"><?= get_the_title(); ?></p>
        <div class="d-flex justify-content-between align-items-center">
            <!--                    post detail-->

            <div class="d-flex gap-2 align-items-center justify-content-start py-lg-3 text-white">
                <div class="img-fluid">
                    <img class="rounded-2" width="35" height="35"
                         src="<?= get_the_post_thumbnail_url(); ?>"
                         alt="<?= the_title(); ?>"/>
                </div>
                <div>
                    <p class="mb-1 d-none d-lg-inline"><?= get_the_title(); ?></p>
                    <div class="fw-normal fs-6 d-lg-flex">
                        <?php echo get_the_date('d  F , Y'); ?>
                        .
                        <span class="d-flex px-2 align-items-center">
                                زمان مطالعه :
                                <h6 class="fw-bold mx-1 my-0">
                                    <?= reading_time(); ?>
                                </h6>
                                دقیقه
                                 </span>
                    </div>
                </div>

            </div>
            <div class="d-lg-flex d-grid gap-lg-3">
                <div class="d-lg-flex d-grid gap-lg-2 gap-1 align-items-center">
                    <?php
                    $post_id = get_the_ID();
                    $rating_value = get_post_meta($post_id, 'rating_value', true);

                    // Get total ratings and average rating
                    $total_ratings = get_post_meta($post_id, 'total_ratings', true);
                    $total_rating_value = get_post_meta($post_id, 'total_rating_value', true);
                    $average_rating = 0;

                    if (is_numeric($total_ratings) && is_numeric($total_rating_value) && $total_ratings > 0) {
                        $average_rating = round($total_rating_value / $total_ratings, 1);
                    }
                    // Display the stars and average rating
                    ?>
                    <div class="d-flex gap-1 align-items-center justify-content-center">
                        <div class="rating">
                            <?php
                            for ($i = 1; $i <= 5; $i++) {
                                if ($i <= $average_rating) {
                                    echo '<span class="star filled"><i class="bi bi-star-fill  text-secondary"></i></span>';
                                } else {
                                    echo '<span class="star"><i class="bi bi-star text-secondary"></i></span>';
                                }
                            }
                            ?>
                        </div>
                        <div class="ratings-summary text-center">
                            <?php
                            //                            echo '<span class="total-ratings">' . $total_ratings . ' ratings</span>';
                            //                            echo '<br>';
                            echo '<span class="average-rating text-white">' . $average_rating . '</span>';
                            ?>
                        </div>
                    </div>
                </div>
                <div class="d-flex gap-lg-2 gap-1 align-items-stretch justify-content-center">
                    <a href="#comment-section"
                       class="rounded d-flex align-items-center shadow-sm px-2 pt-2 text-white bg-white <?php if ($comment_count == 0) { echo "bg-opacity-10";} else{ echo 'bg-opacity-25';};?>"><i
                                class="bi bi-chat-square-dots me-2"></i> <?= $comment_count; ?></a>
                    <div class="d-none d-lg-inline vr bg-white"></div>
                    <a class="px-2 py-1 bg-white bg-opacity-25 rounded" href="#share-section"><i
                                class="bi bi-share text-white"></i></a>
                </div>
            </div>
        </div>
    </div>
    <div class="progress-container">
        <div class="progress-bar" id="myBar"></div>
    </div>
</div>
