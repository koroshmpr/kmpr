<form class="search-form d-flex gap-1 align-items-center <?= $args['size']; ?>" method="get" action="<?php echo esc_url(home_url('/')); ?>">
    <div class="input-group">
        <input id="search-form-<?= $args['place'] ?>" type="search" name="s" class="<?= $args['inputClass'] ?? ''; ?> form-control text-primary bg-primary"
               placeholder="جستجو..." aria-label="Search">
        <button type="button" class="<?= $args['dropdownClass'] ?? ''; ?> bg-primary text-white d-flex align-items-center" data-bs-toggle="dropdown" aria-expanded="false">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-caret-down-fill" viewBox="0 0 16 16">
                <path d="M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z"/>
            </svg>
        </button>
        <ul class="dropdown-menu">
            <?php
            $post_types = array('post', 'portfolio', 'services');
            foreach ($post_types as $post_type) {
                $post_type_info = get_post_type_object($post_type);
                $post_type_label = $post_type_info->labels->name;
                echo '<li><a class="dropdown-item" href="' . esc_url(home_url('/')) . '?post_type=' . $post_type . '&s=' . get_search_query() . '">' . $post_type_label . '</a></li>';
            }
            ?>
        </ul>
        <button type="submit" class="bg-primary text-white <?= $args['buttonClass'] ?? 'px-2'; ?> d-flex align-items-center rounded-end" aria-label="Search-<?= $args['place'] ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-search" viewBox="0 0 16 16">
                <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
            </svg>
        </button>
    </div>
</form>
