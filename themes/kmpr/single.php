<?php get_header();

track_post_views(get_the_ID());


while (have_posts()) :

    the_post();

    $comment_count = get_comments_number(); // Get the number of comments for this post

    ?>
    <section class="container py-2">
        <div class="row align-items-start pb-4 justify-content-lg-between justify-content-center">
            <!--            sidebar-->
            <aside class="row justify-content-center col-lg-4 col-12 pt-lg-4 px-lg-4">
                <div class="img-fluid mb-2">
                    <img class="w-100 object-fit rounded-2 d-none d-lg-inline shadow-sm img-thumbnail"
                         src="<?= get_the_post_thumbnail_url(); ?>"
                         alt="<?= the_title(); ?>"/>
                </div>
                <?php get_template_part('template-parts/loop/post-sidebar'); ?>
            </aside>
            <div class="col-12 col-lg-8 order-first order-lg-last">
                <!--                breadcrumb-->
                <div class="p-3">
                    <nav class="row" style="--bs-breadcrumb-divider: '>';" aria-label="breadcrumb">
                        <ul class="breadcrumb d-flex fw-bold gap-2 list-unstyled">
                            <li class="breadcrumb-item">صفحه اصلی</li>
                            <?php if (get_queried_object()->post_type == 'post'): ?>
                            <li class="breadcrumb-item">بلاگ</li>
                            <li class="breadcrumb-item">
                                <?php endif;
                                $category_detail = get_the_category($post->ID);
                                $category_count = count($category_detail);
                                $i = 0;
                                foreach ($category_detail as $category) {
                                    echo $category->name;
                                    if (++$i !== $category_count) {
                                        echo ' - ';
                                    }
                                } ?>
                            </li>
                        </ul>
                    </nav>
                </div>
                <!--                title-->
                <h1 class="fw-bold text-primary"><?= get_the_title(); ?></h1>
                <div class="d-flex justify-content-between align-items-center">
                    <!--                    post detail-->
                    <div class="d-flex gap-2 align-items-center justify-content-start py-3">
                        <?php get_template_part('template-parts/cards/post-detail/author-image'); ?>
                        <div>
                            <div class="fs-6">
                                نوشته
                                <span class="fw-bold">
                                 <?= get_the_author_meta('display_name', $post->post_author); ?>
                                </span>
                            </div>
                            <div class="fw-normal fs-6 d-lg-flex">
                                <?php echo get_the_date('d  F , Y'); ?>
                                <span class="d-flex px-lg-2 align-items-center">
                                زمان مطالعه :
                                <h6 class="fw-bold mx-1 my-0">
                                    <?= reading_time(); ?>
                                </h6>
                                دقیقه
                                 </span>
                            </div>
                        </div>
                    </div>
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
                            <div class="rating">
                                <?php
                                for ($i = 1; $i <= 5; $i++) {
                                    if ($i <= $average_rating) { ?>
                                        <span class="star filled">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15"
                                                 fill="currentColor" class="bi bi-star-fill" viewBox="0 0 16 16">
                                                <path d="M3.612 15.443c-.386.198-.824-.149-.746-.592l.83-4.73L.173 6.765c-.329-.314-.158-.888.283-.95l4.898-.696L7.538.792c.197-.39.73-.39.927 0l2.184 4.327 4.898.696c.441.062.612.636.282.95l-3.522 3.356.83 4.73c.078.443-.36.79-.746.592L8 13.187l-4.389 2.256z"/>
                                            </svg>
                                        </span>
                                    <?php } else { ?>
                                        <span class="star">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15"
                                                 fill="currentColor" class="bi bi-star"
                                                 viewBox="0 0 16 16">
                                                <path d="M2.866 14.85c-.078.444.36.791.746.593l4.39-2.256 4.389 2.256c.386.198.824-.149.746-.592l-.83-4.73 3.522-3.356c.33-.314.16-.888-.282-.95l-4.898-.696L8.465.792a.513.513 0 0 0-.927 0L5.354 5.12l-4.898.696c-.441.062-.612.636-.283.95l3.523 3.356-.83 4.73zm4.905-2.767-3.686 1.894.694-3.957a.565.565 0 0 0-.163-.505L1.71 6.745l4.052-.576a.525.525 0 0 0 .393-.288L8 2.223l1.847 3.658a.525.525 0 0 0 .393.288l4.052.575-2.906 2.77a.565.565 0 0 0-.163.506l.694 3.957-3.686-1.894a.503.503 0 0 0-.461 0z"/>
                                            </svg>
                                        </span>
                                    <?php }
                                }
                                ?>
                            </div>
                            <div class="ratings-summary text-center">
                                <?php
                                echo '<span class="average-rating">' . $average_rating . '</span>';
                                ?>
                            </div>
                        </a>
                        <div class="d-flex gap-2 align-items-stretch justify-content-center">
                            <a href="#comment-section"
                               class="rounded d-flex align-items-center shadow-sm px-2 py-1 bg-primary bg-opacity-10">
                                <?php if ($comment_count > 0) { ?>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                         class="bi bi-chat-square-text-fill me-2" viewBox="0 0 16 16">
                                        <path d="M0 2a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2h-2.5a1 1 0 0 0-.8.4l-1.9 2.533a1 1 0 0 1-1.6 0L5.3 12.4a1 1 0 0 0-.8-.4H2a2 2 0 0 1-2-2V2zm3.5 1a.5.5 0 0 0 0 1h9a.5.5 0 0 0 0-1h-9zm0 2.5a.5.5 0 0 0 0 1h9a.5.5 0 0 0 0-1h-9zm0 2.5a.5.5 0 0 0 0 1h5a.5.5 0 0 0 0-1h-5z"/>
                                    </svg>
                                <?php } else { ?>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                         class="bi bi-chat-square-text me-2" viewBox="0 0 16 16">
                                        <path d="M14 1a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1h-2.5a2 2 0 0 0-1.6.8L8 14.333 6.1 11.8a2 2 0 0 0-1.6-.8H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h12zM2 0a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2.5a1 1 0 0 1 .8.4l1.9 2.533a1 1 0 0 0 1.6 0l1.9-2.533a1 1 0 0 1 .8-.4H14a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2z"/>
                                        <path d="M3 3.5a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5zM3 6a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9A.5.5 0 0 1 3 6zm0 2.5a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5z"/>
                                    </svg>
                                <?php } ?>
                                <?= $comment_count; ?></a>
                            <div class="d-none d-lg-inline vr bg-opacity-50 bg-dark"></div>
                            <a class="px-2 py-1 rounded shadow-sm bg-primary bg-opacity-10" href="#share-section">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                     class="bi bi-share" viewBox="0 0 16 16">
                                    <path d="M13.5 1a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3zM11 2.5a2.5 2.5 0 1 1 .603 1.628l-6.718 3.12a2.499 2.499 0 0 1 0 1.504l6.718 3.12a2.5 2.5 0 1 1-.488.876l-6.718-3.12a2.5 2.5 0 1 1 0-3.256l6.718-3.12A2.5 2.5 0 0 1 11 2.5zm-8.5 4a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3zm11 5.5a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3z"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
                <!--                thumbnail-->
                <div class="img-fluid d-lg-none">
                    <img class="w-100 object-fit rounded-2"
                         src="<?= get_the_post_thumbnail_url(); ?>"
                         alt="<?= the_title(); ?>"/>
                </div>
                <!--                content-->
                <article class="pt-5 text-justify post-content">
                    <?= the_content(); ?>
                    <div class="pb-3" id="share-section"></div>
                </article>
                <!--                rating-->
                <div id="rating"
                     class="rating-section p-3 rounded bg-secondary bg-opacity-50 d-flex justify-content-between align-items-center my-5">
                    <p class="mb-0 fw-bold text-primary">چه میزان از این مقاله لذت بردید</p>
                    <div class="rating">
                        <?php
                        $post_id = get_the_ID();
                        $rating_value = get_post_meta($post_id, 'rating_value', true);
                        for ($i = 1; $i <= 5; $i++) {
                            if ($i <= $rating_value) { ?>
                                <span class="star filled">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15"
                                                 fill="currentColor" class="bi bi-star-fill" viewBox="0 0 16 16">
                                                <path d="M3.612 15.443c-.386.198-.824-.149-.746-.592l.83-4.73L.173 6.765c-.329-.314-.158-.888.283-.95l4.898-.696L7.538.792c.197-.39.73-.39.927 0l2.184 4.327 4.898.696c.441.062.612.636.282.95l-3.522 3.356.83 4.73c.078.443-.36.79-.746.592L8 13.187l-4.389 2.256z"/>
                                            </svg>
                                        </span>
                            <?php } else { ?>
                                <span class="star">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15"
                                                 fill="currentColor" class="bi bi-star"
                                                 viewBox="0 0 16 16">
                                                <path d="M2.866 14.85c-.078.444.36.791.746.593l4.39-2.256 4.389 2.256c.386.198.824-.149.746-.592l-.83-4.73 3.522-3.356c.33-.314.16-.888-.282-.95l-4.898-.696L8.465.792a.513.513 0 0 0-.927 0L5.354 5.12l-4.898.696c-.441.062-.612.636-.283.95l3.523 3.356-.83 4.73zm4.905-2.767-3.686 1.894.694-3.957a.565.565 0 0 0-.163-.505L1.71 6.745l4.052-.576a.525.525 0 0 0 .393-.288L8 2.223l1.847 3.658a.525.525 0 0 0 .393.288l4.052.575-2.906 2.77a.565.565 0 0 0-.163.506l.694 3.957-3.686-1.894a.503.503 0 0 0-.461 0z"/>
                                            </svg>
                                        </span>
                            <?php }
                        } ?>
                    </div>
                    <script>
                        jQuery(document).ready(function ($) {
                            $('.rating .star').click(function () {
                                $(this).prevAll('.star').addBack().find('path').attr('d' , 'M3.612 15.443c-.386.198-.824-.149-.746-.592l.83-4.73L.173 6.765c-.329-.314-.158-.888.283-.95l4.898-.696L7.538.792c.197-.39.73-.39.927 0l2.184 4.327 4.898.696c.441.062.612.636.282.95l-3.522 3.356.83 4.73c.078.443-.36.79-.746.592L8 13.187l-4.389 2.256z');
                                $(this).nextAll('.star').find('path').attr('d', 'M2.866 14.85c-.078.444.36.791.746.593l4.39-2.256 4.389 2.256c.386.198.824-.149.746-.592l-.83-4.73 3.522-3.356c.33-.314.16-.888-.282-.95l-4.898-.696L8.465.792a.513.513 0 0 0-.927 0L5.354 5.12l-4.898.696c-.441.062-.612.636-.283.95l3.523 3.356-.83 4.73zm4.905-2.767-3.686 1.894.694-3.957a.565.565 0 0 0-.163-.505L1.71 6.745l4.052-.576a.525.525 0 0 0 .393-.288L8 2.223l1.847 3.658a.525.525 0 0 0 .393.288l4.052.575-2.906 2.77a.565.565 0 0 0-.163.506l.694 3.957-3.686-1.894a.503.503 0 0 0-.461 0z');
                                var ratingValue = $(this).index() + 1;
                                $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                                    action: 'save_rating',
                                    post_id: <?php echo $post_id; ?>,
                                    rating_value: ratingValue
                                });
                            });
                        });
                    </script>
                </div>
                <!--                share button-->
                <?php
                $sizeSvgX = '20';
                $sizeSvgY = '20';
                $class = 'fill-primary';
                $mainClass = 'd-grid d-lg-flex';
                $colorSvg = '#FE0000';
                $args = array(
                    'sizeSvgX' => $sizeSvgX,
                    'class' => $class,
                    'mainClass' => $mainClass,
                    'sizeSvgY' => $sizeSvgY,
                    'colorSvg' => $colorSvg
                );
                get_template_part('template-parts/share-button', null, $args); ?>
                <!--                tags-->
                <?php
                $tags = get_the_tags();
                if ($tags) { ?>
                    <div>
                        <p class="fw-bold fs-3 py-3">برچسب های این مقاله</p>
                        <?php echo '<ul class="d-flex gap-2 align-items-center list-unstyled flex-wrap">';
                        foreach ($tags as $tag) {
                            echo '<li class="bg-primary bg-opacity-10 px-2 text-primary py-2 rounded-2"><i class="bi bi-tag-fill me-1"></i><a class="text-primary" href="' . get_tag_link($tag->term_id) . '">' . $tag->name . '</a></li>';
                        }
                        echo '</ul>'; ?>
                    </div>
                <?php } ?>
                <!--                author information-->
                <?php get_template_part('template-parts/cards/post-detail/author-information'); ?>
                <!--                comments-->
                <div class="my-2">
                    <?php
                    if (comments_open() || get_comments_number()) :
                        comments_template();
                    endif;
                    ?>
                </div>
            </div>
        </div>
        <hr class="m-0"/>
        <div class="col-12 pb-4 overflow-hidden">
            <div class="row justify-content-center g-3 g-lg-2 mx-1 mx-lg-0">
                <h4 class="fw-bold text-center w-auto bg-primary text-white p-2 rounded-bottom" data-aos="fade-down"
                    data-aos-duration="800">خدمات</h4>
                <div class="row row-cols-1 row-cols-lg-3 g-2 hero-section">
                    <?php
                    global $post;
                    // Get the ID of the current post
                    $current_post_id = $post->ID;
                    $args = array(
                        'post_type' => 'services',
                        'post_status' => 'publish',
                        'order' => 'DESC',
                        'posts_per_page' => '-1',
                        'ignore_sticky_posts' => true,
                        'post__not_in' => array($current_post_id)
                    );
                    $loop = new WP_Query($args);
                    if ($loop->have_posts()) :
                        $i = 0;
                        /* Start the Loop */
                        ?>
                        <?php while ($loop->have_posts()) :
                        $loop->the_post(); ?>
                        <div data-aos="zoom-out">
                            <?php get_template_part('template-parts/services/services-card'); ?>
                        </div>
                    <?php
                    endwhile;
                    endif;
                    wp_reset_postdata(); ?>
                </div>
            </div>
        </div>
    </section>
<?php endwhile;
wp_reset_query();
get_footer(); ?>

