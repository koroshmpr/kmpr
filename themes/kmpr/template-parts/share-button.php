<?php
/**
 * Share buttons (Telegram, WhatsApp, LinkedIn, X) + copy link.
 * $args['label'] optional heading text, $args['class'] extra wrapper classes.
 */
$share_url   = wp_get_canonical_url() ?: get_permalink();
$share_title = wp_strip_all_tags(get_the_title());
$u           = rawurlencode($share_url);
$t           = rawurlencode($share_title);
$btn         = 'inline-flex items-center justify-center size-11 rounded-full bg-white/[0.04] border border-white/10 text-slate-300 hover:text-slate-950 hover:bg-secondary hover:border-secondary transition-colors';
$networks    = array(
    array('label' => 'اشتراک در تلگرام', 'url' => 'https://t.me/share/url?url=' . $u . '&text=' . $t,
          'icon' => '<path d="M21.9 4.6 18.7 19.7c-.2 1-.9 1.3-1.7.8l-4.8-3.5-2.3 2.2c-.3.3-.5.5-1 .5l.3-4.9 9-8.1c.4-.3-.1-.5-.6-.2L6.5 13.5 1.8 12c-1-.3-1-1 .2-1.5L20.5 3.4c.9-.3 1.6.2 1.4 1.2Z" fill="currentColor"/>'),
    array('label' => 'اشتراک در واتس‌اپ', 'url' => 'https://api.whatsapp.com/send?text=' . $t . '%20' . $u,
          'icon' => '<path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm5.3 14.1c-.2.6-1.3 1.2-1.8 1.2-.5.1-1 .2-3.3-.7-2.8-1.1-4.5-3.9-4.7-4.1-.1-.2-1.1-1.5-1.1-2.9s.7-2 1-2.3c.2-.3.5-.3.7-.3h.5c.2 0 .4 0 .6.5l.8 2c.1.2.1.4 0 .5l-.3.5-.4.4c-.1.1-.3.3-.1.6.2.3.7 1.2 1.6 1.9 1.1 1 2 1.3 2.3 1.4.3.1.5.1.6-.1l.9-1c.2-.3.4-.2.6-.1l1.9.9c.3.1.5.2.5.3.1.2.1.7-.1 1.3Z" fill="currentColor"/>'),
    array('label' => 'اشتراک در لینکدین', 'url' => 'https://www.linkedin.com/sharing/share-offsite/?url=' . $u,
          'icon' => '<path d="M4.98 3.5a2.5 2.5 0 1 1 0 5 2.5 2.5 0 0 1 0-5ZM3 9.75h4V21H3V9.75Zm6.5 0h3.8v1.6h.1c.5-1 1.8-2 3.8-2 4 0 4.8 2.6 4.8 6V21h-4v-5c0-1.2 0-2.8-1.7-2.8s-2 1.3-2 2.7V21h-4V9.75Z" fill="currentColor"/>'),
    array('label' => 'اشتراک در ایکس', 'url' => 'https://twitter.com/intent/tweet?text=' . $t . '&url=' . $u,
          'icon' => '<path d="M17.8 3h3.1l-6.8 7.8L22 21h-6.2l-4.9-6.4L5.3 21H2.2l7.3-8.3L2 3h6.4l4.4 5.8L17.8 3Zm-1.1 16.2h1.7L7.4 4.7H5.6l11.1 14.5Z" fill="currentColor"/>'),
);
?>
<div class="flex flex-wrap items-center gap-3 <?= esc_attr($args['class'] ?? ''); ?>"
     x-data="{ copied: false, copy() { navigator.clipboard && navigator.clipboard.writeText(<?= esc_attr(wp_json_encode($share_url)); ?>).then(() => { this.copied = true; setTimeout(() => this.copied = false, 1800); }); } }">
    <span class="text-sm font-bold text-slate-300 me-1"><?= esc_html($args['label'] ?? 'اشتراک‌گذاری'); ?></span>
    <ul class="flex flex-wrap items-center gap-2 list-none p-0 m-0">
        <?php foreach ($networks as $n) : ?>
            <li>
                <a href="<?= esc_url($n['url']); ?>" target="_blank" rel="noopener noreferrer" class="<?= esc_attr($btn); ?>" aria-label="<?= esc_attr($n['label']); ?>" title="<?= esc_attr($n['label']); ?>">
                    <svg width="18" height="18" class="shrink-0" viewBox="0 0 24 24" aria-hidden="true"><?= $n['icon']; // static markup ?></svg>
                </a>
            </li>
        <?php endforeach; ?>
        <li>
            <button type="button" @click="copy()" class="<?= esc_attr($btn); ?> cursor-pointer" aria-label="کپی لینک" title="کپی لینک">
                <svg x-show="!copied" width="18" height="18" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M10 13a5 5 0 0 0 7.07 0l3-3a5 5 0 0 0-7.07-7.07l-1 1"/><path d="M14 11a5 5 0 0 0-7.07 0l-3 3a5 5 0 0 0 7.07 7.07l1-1"/></svg>
                <svg x-show="copied" x-cloak width="18" height="18" class="shrink-0 text-emerald-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-8 8a1 1 0 0 1-1.4 0l-4-4a1 1 0 1 1 1.4-1.4L8 12.58l7.3-7.3a1 1 0 0 1 1.4 0Z" clip-rule="evenodd"/></svg>
            </button>
        </li>
    </ul>
    <span x-show="copied" x-cloak class="text-xs font-bold text-emerald-400" role="status">لینک کپی شد</span>
</div>
