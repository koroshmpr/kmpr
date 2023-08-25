<?php
$category_detail = get_the_category($post->ID); ?>
<h5 class="d-flex justify-content-center align-items-center position-absolute end-0 bottom-0 mb-0 bg-secondary p-2">
                <span class="fs-6 fw-bold">
                    <?php
                    $category_count = count($category_detail);
                    $i = 0;
                    foreach ($category_detail as $category) {
                        echo $category->name;
                        if (++$i !== $category_count) {
                            echo ' - ';
                        }
                    }
                    ?>
                </span>
</h5>