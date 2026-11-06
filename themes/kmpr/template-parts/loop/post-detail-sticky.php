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
                        <span class="d-flex px-2 align-items-center">
                                زمان مطالعه :
                                <span class="fw-bold mx-1 my-0">
                                    <?= reading_time(); ?>
                                </span>
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
                    <a href="#rating" class="d-flex gap-1 align-items-center justify-content-center">
                        <div class="rating-result">
                            <?php
                            for ($i = 1; $i <= 5; $i++) {
                                if ($i <= $average_rating) { ?>
                                    <span class="star filled">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15"
                                                 fill="currentColor" class="bi bi-star-fill text-secondary"
                                                 viewBox="0 0 16 16">
                                                <path d="M3.612 15.443c-.386.198-.824-.149-.746-.592l.83-4.73L.173 6.765c-.329-.314-.158-.888.283-.95l4.898-.696L7.538.792c.197-.39.73-.39.927 0l2.184 4.327 4.898.696c.441.062.612.636.282.95l-3.522 3.356.83 4.73c.078.443-.36.79-.746.592L8 13.187l-4.389 2.256z"/>
                                            </svg>
                                        </span>
                                <?php } else { ?>
                                    <span class="star">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15"
                                                 fill="currentColor" class="bi bi-star text-secondary"
                                                 viewBox="0 0 16 16">
                                                <path d="M2.866 14.85c-.078.444.36.791.746.593l4.39-2.256 4.389 2.256c.386.198.824-.149.746-.592l-.83-4.73 3.522-3.356c.33-.314.16-.888-.282-.95l-4.898-.696L8.465.792a.513.513 0 0 0-.927 0L5.354 5.12l-4.898.696c-.441.062-.612.636-.283.95l3.523 3.356-.83 4.73zm4.905-2.767-3.686 1.894.694-3.957a.565.565 0 0 0-.163-.505L1.71 6.745l4.052-.576a.525.525 0 0 0 .393-.288L8 2.223l1.847 3.658a.525.525 0 0 0 .393.288l4.052.575-2.906 2.77a.565.565 0 0 0-.163.506l.694 3.957-3.686-1.894a.503.503 0 0 0-.461 0z"/>
                                            </svg>
                                        </span>
                                <?php }
                            }
                            ?>
                        </div>
                        <div class="ratings-summary text-center">
                            <span class="average-rating text-white">
                                <?= $average_rating; ?>
                            </span>
                        </div>
                    </a>
                </div>
                <div class="d-flex gap-lg-2 gap-1 align-items-stretch justify-content-center">
                    <a class="bg-white rounded bg-opacity-10 text-white px-2 py-1 border border-2 border-white border-bottom-0 border-opacity-10" href="#comment-section"
                       class="rounded d-flex align-items-center shadow-sm px-2 py-1 bg-white bg-opacity-10 text-white">
                        <?php if ($comment_count > 0) { ?>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                 class="bi bi-chat-square-text-fill me-2" viewBox="0 0 16 16">
                                <path d="M0 2a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2h-2.5a1 1 0 0 0-.8.4l-1.9 2.533a1 1 0 0 1-1.6 0L5.3 12.4a1 1 0 0 0-.8-.4H2a2 2 0 0 1-2-2V2zm3.5 1a.5.5 0 0 0 0 1h9a.5.5 0 0 0 0-1h-9zm0 2.5a.5.5 0 0 0 0 1h9a.5.5 0 0 0 0-1h-9zm0 2.5a.5.5 0 0 0 0 1h5a.5.5 0 0 0 0-1h-5z"/>
                            </svg>
                        <?php } else { ?>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                 class="bi bi-chat-square-text me-2 " viewBox="0 0 16 16">
                                <path d="M14 1a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1h-2.5a2 2 0 0 0-1.6.8L8 14.333 6.1 11.8a2 2 0 0 0-1.6-.8H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h12zM2 0a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2.5a1 1 0 0 1 .8.4l1.9 2.533a1 1 0 0 0 1.6 0l1.9-2.533a1 1 0 0 1 .8-.4H14a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2z"/>
                                <path d="M3 3.5a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5zM3 6a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9A.5.5 0 0 1 3 6zm0 2.5a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5z"/>
                            </svg>
                        <?php } ?>
                        <?= $comment_count; ?>
                    </a>
                    <a class="px-2 py-1 rounded shadow-sm bg-white bg-opacity-10 text-white border border-2 border-white border-bottom-0 border-opacity-10" href="#share-section">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                             class="bi bi-share" viewBox="0 0 16 16">
                            <path d="M13.5 1a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3zM11 2.5a2.5 2.5 0 1 1 .603 1.628l-6.718 3.12a2.499 2.499 0 0 1 0 1.504l6.718 3.12a2.5 2.5 0 1 1-.488.876l-6.718-3.12a2.5 2.5 0 1 1 0-3.256l6.718-3.12A2.5 2.5 0 0 1 11 2.5zm-8.5 4a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3zm11 5.5a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3z"/>
                        </svg>
                    </a>
                    <div class="dropdown">
                        <button class="border border-2 border-white border-bottom-0 border-opacity-10 h-100 rounded d-flex align-items-center shadow-sm px-2 py-1 bg-white bg-opacity-10 text-white" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-filter-square" viewBox="0 0 16 16">
                                <path d="M14 1a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h12zM2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2z"/>
                                <path d="M6 11.5a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 0 1h-3a.5.5 0 0 1-.5-.5zm-2-3a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5zm-2-3a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 0 1h-11a.5.5 0 0 1-.5-.5z"/>
                            </svg>
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="tableOfContent">
                            <li class="d-flex align-items-center w-100 p-3 border-0 table-c__sticky" style="white-space: nowrap">
                                <?= do_shortcode('[TOC]') ?>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="progress-container">
        <div class="progress-bar" id="myBar"></div>
    </div>
</div>
