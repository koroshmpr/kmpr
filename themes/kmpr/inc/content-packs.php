<?php
/**
 * Theme content packs — database content that ships with the theme.
 *
 * Only the theme folder is deployed (GitHub Actions → FTP); posts, categories and media live in the database.
 * A pack is a JSON file in content/packs/ that installs courses (categories + ACF + Yoast), lessons (posts) and
 * their cover images on whichever site runs the theme. It is applied once per file hash: change the file and
 * deploy, and it is applied again. Every write is an upsert by slug, so re-applying never duplicates anything.
 *
 * Pack format = the kmpr_sync_apply_site() payload (see content-sync.php: terms, posts, acf_options, menus…)
 * plus "media": [{"file":"cover.webp","alt":"…","title":"…"}] — files in content/media/, imported into the
 * Media Library once (matched on file name) and referenced from posts as "thumbnail":"media:cover.webp".
 * After import the theme copy is deleted on the server: article images live only in the Media Library.
 *
 * Status: option kmpr_content_packs = { "<pack file>": { hash, at, log[] } }.
 *
 * @package KMPR
 */

if (!defined('ABSPATH')) {
    exit;
}

define('KMPR_PACKS_DIR', get_template_directory() . '/content/packs/');
define('KMPR_PACKS_MEDIA', get_template_directory() . '/content/media/');

/**
 * Pack files whose content hash differs from the last applied one.
 * Cheap path: a stat-based signature of the folder is compared first, so a normal request costs one glob().
 */
function kmpr_packs_pending()
{
    $files = glob(KMPR_PACKS_DIR . '*.json') ?: array();
    sort($files);
    $sig = md5(wp_json_encode(array_map(function ($f) {
        return array(basename($f), filesize($f), filemtime($f));
    }, $files)));
    if (get_option('kmpr_content_packs_sig') === $sig) {
        return array();
    }
    $done    = (array) get_option('kmpr_content_packs', array());
    $pending = array();
    foreach ($files as $f) {
        $name = basename($f);
        if (($done[$name]['hash'] ?? '') !== md5_file($f)) {
            $pending[] = $f;
        }
    }
    if (!$pending) {
        update_option('kmpr_content_packs_sig', $sig, true);
    }
    return $pending;
}

function kmpr_packs_apply_file($file)
{
    $name = basename($file);
    $data = json_decode((string) file_get_contents($file), true);
    $log  = array();

    if (is_array($data)) {
        // FTP may upload the JSON before its images: wait (stay pending) until every cover is present.
        foreach ((array) ($data['media'] ?? array()) as $m) {
            $f = basename((string) ($m['file'] ?? ''));
            if ($f !== '' && !is_readable(KMPR_PACKS_MEDIA . $f) && !kmpr_sync_media_id($f)) {
                return array('waiting for media ' . $f);
            }
        }
    }

    if (!is_array($data)) {
        $log[] = 'ERR json: ' . json_last_error_msg();
    } else {
        foreach ((array) ($data['media'] ?? array()) as $m) {
            $id    = kmpr_pack_import_media((array) $m);
            $log[] = (is_wp_error($id) ? 'ERR media (' . $id->get_error_message() . ')' : 'media #' . $id) . ' ' . ($m['file'] ?? '');
        }
        $log = array_merge($log, kmpr_sync_apply_site($data));
    }

    $done        = (array) get_option('kmpr_content_packs', array());
    $done[$name] = array('hash' => md5_file($file), 'at' => gmdate('c'), 'log' => array_slice($log, 0, 120));
    update_option('kmpr_content_packs', $done, false);
    return $log;
}

/**
 * Import one pack image into the Media Library (once) and keep its alt text in sync. Returns the attachment ID or WP_Error.
 */
function kmpr_pack_import_media(array $m)
{
    $file = basename((string) ($m['file'] ?? ''));
    if ($file === '') {
        return new WP_Error('pack_media', 'no file name');
    }
    $id = kmpr_sync_media_id($file);
    if (!$id) {
        $src = KMPR_PACKS_MEDIA . $file;
        if (!is_readable($src)) {
            return new WP_Error('pack_media', 'missing in content/media');
        }
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        $tmp = wp_tempnam($file);
        if (!$tmp || !copy($src, $tmp)) {
            return new WP_Error('pack_media', 'temp copy failed');
        }
        $id = media_handle_sideload(array('name' => $file, 'tmp_name' => $tmp), 0, sanitize_text_field($m['title'] ?? ''));
        if (is_wp_error($id)) {
            @unlink($tmp); // phpcs:ignore
            return $id;
        }
        kmpr_sync_media_id($file, (int) $id); // later "media:<file>" lookups in this request must see the new ID
    }
    if (!empty($m['alt'])) {
        update_post_meta($id, '_wp_attachment_image_alt', sanitize_text_field($m['alt']));
    }
    // The image now lives in the Media Library (dashboard); the theme copy was only transport — remove it
    // on the server so article images are never served as static theme files.
    $src = KMPR_PACKS_MEDIA . $file;
    if ($id && wp_get_attachment_url((int) $id) && is_file($src) && !(defined('WP_ENVIRONMENT_TYPE') && WP_ENVIRONMENT_TYPE === 'local')) {
        @unlink($src); // phpcs:ignore
    }
    return (int) $id;
}

/**
 * Apply at most one pending pack per request (image resizing is the slow part), guarded by a lock.
 */
function kmpr_packs_run()
{
    $pending = kmpr_packs_pending();
    if (!$pending || get_transient('kmpr_packs_lock')) {
        return;
    }
    set_transient('kmpr_packs_lock', 1, 5 * MINUTE_IN_SECONDS);
    if (function_exists('set_time_limit')) {
        @set_time_limit(300); // phpcs:ignore
    }
    kmpr_packs_apply_file($pending[0]);
    delete_transient('kmpr_packs_lock');
    if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
    }
}
add_action('wp_loaded', 'kmpr_packs_run', 20);
