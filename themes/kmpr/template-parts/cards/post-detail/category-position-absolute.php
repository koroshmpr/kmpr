<?php
$category_detail = get_the_category(get_the_ID());
if (!empty($category_detail)) : ?>
<span class="inline-flex items-center justify-center bg-secondary text-white text-xs font-black px-3 py-1.5 rounded-xl shadow-md">
    <?php
    $names = wp_list_pluck($category_detail, 'name');
    echo esc_html(implode(' - ', $names));
    ?>
</span>
<?php endif; ?>
