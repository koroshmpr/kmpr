<form class="search-form d-flex gap-1 align-items-center <?= $args['size']; ?>" method="get" action="<?php echo esc_url(home_url('/')); ?>">
    <div class="input-group">
        <input id="search-form-<?= $args['place'] ?>" type="search" name="s" class="<?= $args['inputClass'] ?? ''; ?> form-control text-primary bg-primary bg-opacity-10"
               placeholder="جستجو..." aria-label="Search">
        <button type="submit" class="bg-primary text-white <?= $args['buttonClass'] ?? 'px-2'; ?> d-flex align-items-center rounded-end" aria-label="Search-<?= $args['place'] ?>">
            <i class="bi bi-search"></i>
        </button>
    </div>
</form>
