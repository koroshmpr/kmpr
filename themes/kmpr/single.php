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
                <h1 class="fw-bold text-dark"><?= get_the_title(); ?></h1>
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
                        <div class="d-flex gap-1 align-items-center justify-content-center">
                            <div class="rating">
                                <?php
                                for ($i = 1; $i <= 5; $i++) {
                                    if ($i <= $average_rating) {
                                        echo '<span class="star filled"><i class="bi bi-star-fill  text-primary"></i></span>';
                                    } else {
                                        echo '<span class="star"><i class="bi bi-star text-primary"></i></span>';
                                    }
                                }
                                ?>
                            </div>
                            <div class="ratings-summary text-center">
                                <?php
                                //                            echo '<span class="total-ratings">' . $total_ratings . ' ratings</span>';
                                //                            echo '<br>';
                                echo '<span class="average-rating">' . $average_rating . '</span>';
                                ?>
                            </div>
                        </div>
                        <div class="d-flex gap-2 align-items-stretch justify-content-center">
                            <a href="#comment-section"
                               class="rounded d-flex align-items-center shadow-sm px-2 bg-primary bg-opacity-10"><i
                                        class="bi bi-chat-square-dots me-2"></i> <?= $comment_count; ?></a>
                            <div class="d-none d-lg-inline vr bg-opacity-50 bg-dark"></div>
                            <a class="px-2 py-1 rounded shadow-sm bg-primary bg-opacity-10" href="#share-section"><i
                                        class="bi bi-share"></i></a>
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
                <article class="pt-5 text-justify">
                    <?= the_content(); ?>
                    <div class="pb-3" id="share-section"></div>
                </article>
                <!--                rating-->
                <div class="rating-section p-3 rounded bg-secondary d-flex justify-content-between align-items-center my-5">
                    <p class="mb-0 fw-bold text-white">چه میزان از این مقاله لذت بردید</p>
                    <div class="rating">
                        <?php
                        $post_id = get_the_ID();
                        $rating_value = get_post_meta($post_id, 'rating_value', true);
                        for ($i = 1; $i <= 5; $i++) {
                            if ($i <= $rating_value) {
                                echo '<span class="star filled"><i class="bi bi-star-fill text-primary"></i></span>';
                            } else {
                                echo '<span class="star"><i class="bi bi-star text-primary"></i></span>';
                            }
                        }
                        ?>
                    </div>
                    <script>
                        jQuery(document).ready(function ($) {
                            $('.rating .star').click(function () {
                                $(this).prevAll('.star').addBack().find('i').removeClass('bi-star').addClass('bi-star-fill');
                                $(this).nextAll('.star').find('i').removeClass('bi-star-fill').addClass('bi-star');
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
                $mainClass = 'd-flex';
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
                <?php get_template_part('template-parts/cards/post-detail/author-information');?>
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
    </section>
<?php endwhile;
wp_reset_query();
get_footer(); ?>

