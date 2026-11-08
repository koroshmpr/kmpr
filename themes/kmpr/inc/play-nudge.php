<?php
/**
 * "Need a break? Play a game" nudge.
 *
 * A small, dismissible card that suggests a quick game after real engagement:
 *   - articles: read ≥ 55% and ≥ 75s on page, or idle 40s after a minute, or exit-intent (desktop)
 *   - blog / portfolio archives & single portfolio: ≥ 90s and ≥ 40% scrolled
 *   - search / 404: shortly after load
 * Never on conversion pages (contact, services, request wizard), the homepage or the game center.
 * Frequency: once per session; snoozed 3 days after "later", 7 days after clicking through.
 *
 * Copy + suggested games: «بازی‌ها → تنظیمات» (options page kmpr-games-settings).
 * Front-end: javascript/play-nudge.js.
 *
 * @package KMPR
 */

if (!defined('ABSPATH')) {
    exit;
}

function kmpr_nudge_defaults()
{
    return array(
        'nudge_enabled' => 1,
        'nudge_title'   => 'خسته نشدی؟ یه استراحت دو دقیقه‌ای!',
        'nudge_text'    => 'یه دست «%s» بزن، ذهنت تازه شه و برگرد سراغ ادامه.',
        'nudge_404'     => 'این صفحه گم شد، ولی بازی‌ها سر جاشونن!',
        'nudge_btn'     => 'بزن بریم',
        'nudge_later'   => 'بعداً',
    );
}

function kmpr_nudge_opt($name)
{
    $d = kmpr_nudge_defaults();
    $v = function_exists('get_field') ? get_field($name, 'option') : null;
    if ($name === 'nudge_enabled') {
        return $v === null ? (bool) $d[$name] : (bool) $v; // never saved → on
    }
    return ($v === null || $v === '' || $v === false || $v === array()) ? ($d[$name] ?? null) : $v;
}

add_action('acf/include_fields', function () {
    if (!function_exists('acf_add_local_field_group') || !function_exists('kmpr_acf')) {
        return;
    }
    $d = kmpr_nudge_defaults();
    acf_add_local_field_group(array(
        'key'      => 'group_kmpr_play_nudge',
        'title'    => 'بنر «وقت استراحت» در سایت',
        'fields'   => array(
            kmpr_acf('field_kmpr_nudge_on', 'nudge_enabled', 'نمایش بنر', 'true_false', array('ui' => 1, 'default_value' => 1, 'instructions' => 'بعد از مطالعه یا مکث کاربر در مقالات، آرشیوها، جستجو و ۴۰۴ یک بازی کوتاه پیشنهاد می‌شود (هر بار بازدید حداکثر یک‌بار).')),
            kmpr_acf('field_kmpr_nudge_title', 'nudge_title', 'تیتر', 'text', array('placeholder' => $d['nudge_title'])),
            kmpr_acf('field_kmpr_nudge_text', 'nudge_text', 'متن (%s = نام بازی)', 'text', array('placeholder' => $d['nudge_text'])),
            kmpr_acf('field_kmpr_nudge_404', 'nudge_404', 'تیتر در صفحه ۴۰۴', 'text', array('placeholder' => $d['nudge_404'])),
            kmpr_acf('field_kmpr_nudge_btn', 'nudge_btn', 'متن دکمه', 'text', array('placeholder' => $d['nudge_btn'])),
            kmpr_acf('field_kmpr_nudge_later', 'nudge_later', 'متن دکمه بعداً', 'text', array('placeholder' => $d['nudge_later'])),
            kmpr_acf('field_kmpr_nudge_games', 'nudge_games', 'بازی‌های پیشنهادی', 'relationship', array('post_type' => array('game'), 'filters' => array('search'), 'return_format' => 'id', 'max' => 8, 'instructions' => 'خالی = بازی‌های کوتاه پیش‌فرض.')),
        ),
        'location' => array(array(array('param' => 'options_page', 'operator' => '==', 'value' => 'kmpr-games-settings'))),
        'menu_order' => 20,
    ));
});

/**
 * Which context (if any) the nudge may appear in on this request.
 */
function kmpr_nudge_context()
{
    if (is_admin() || !post_type_exists('game') || is_front_page() || is_page(array('contact-us', 'contact'))) {
        return '';
    }
    if (is_post_type_archive('game') || is_singular('game') || is_tax('game_category') || is_singular('services') || is_post_type_archive('services')) {
        return '';
    }
    if (is_404()) {
        return '404';
    }
    if (is_search()) {
        return 'search';
    }
    if (is_singular('post')) {
        return 'article';
    }
    if (is_home() || is_category() || is_tag() || is_post_type_archive('portfolio') || is_singular('portfolio') || is_tax('portfolio_categories') || is_page(array('about-us', 'resume', 'faq'))) {
        return 'browse';
    }
    return '';
}

add_action('wp_footer', function () {
    $ctx = kmpr_nudge_context();
    if (!$ctx || !kmpr_nudge_opt('nudge_enabled')) {
        return;
    }
    $ids = kmpr_nudge_opt('nudge_games');
    $ids = is_array($ids) ? array_filter(array_map('intval', $ids)) : array();
    if (!$ids) {
        $ids = get_posts(array(
            'post_type'      => 'game',
            'post_name__in'  => array('bug-hunter', 'neon-snake', 'xo', 'rps-15', 'hex-hunter', 'pixel-runner', 'sky-shooter'),
            'fields'         => 'ids',
            'posts_per_page' => 8,
            'no_found_rows'  => true,
        ));
    }
    $games = array();
    foreach ($ids as $id) {
        if (get_post_status($id) !== 'publish') {
            continue;
        }
        $games[] = array(
            'title' => html_entity_decode(get_the_title($id), ENT_QUOTES, 'UTF-8'),
            'url'   => get_permalink($id),
            'img'   => get_the_post_thumbnail_url($id, 'medium') ?: '',
        );
    }
    if (!$games) {
        return;
    }
    $config = array(
        'ctx'   => $ctx,
        'games' => $games,
        'title' => $ctx === '404' ? kmpr_nudge_opt('nudge_404') : kmpr_nudge_opt('nudge_title'),
        'text'  => kmpr_nudge_opt('nudge_text'),
    );
    ?>
    <aside id="kmpr-nudge" class="kmpr-nudge" role="dialog" aria-modal="false" aria-labelledby="kmpr-nudge-title" hidden
           data-config="<?= esc_attr(wp_json_encode($config, JSON_UNESCAPED_UNICODE)); ?>">
        <div class="relative overflow-hidden rounded-3xl border border-white/10 bg-slate-900/95 backdrop-blur-xl shadow-[0_30px_80px_-20px_rgb(0_0_0/0.9)] p-4">
            <div class="absolute -top-16 -end-10 size-40 rounded-full bg-[radial-gradient(circle,rgb(168_85_247/0.35),transparent_70%)] pointer-events-none" aria-hidden="true"></div>
            <button type="button" data-nudge-close class="absolute top-2.5 end-2.5 size-9 rounded-full flex items-center justify-center text-slate-400 hover:text-white hover:bg-white/10 cursor-pointer z-10" aria-label="بستن">
                <svg width="14" height="14" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
            <div class="relative flex items-start gap-3.5 pe-8">
                <a data-nudge-link href="#" class="kmpr-nudge-cover relative size-[4.5rem] shrink-0 rounded-2xl overflow-hidden border border-white/10 bg-slate-800" tabindex="-1" aria-hidden="true">
                    <img data-nudge-img src="data:image/gif;base64,R0lGODlhAQABAAAAACw=" alt="" width="72" height="72" class="size-full object-cover">
                </a>
                <div class="min-w-0">
                    <p class="inline-flex items-center gap-1.5 text-[11px] font-black text-fuchsia-300 mb-1">
                        <svg width="14" height="14" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><rect x="2" y="6" width="20" height="12" rx="6"/><path d="M6 12h4M8 10v4"/><circle cx="15.5" cy="11" r=".9" fill="currentColor"/><circle cx="17.5" cy="13" r=".9" fill="currentColor"/></svg>
                        وقت استراحت
                    </p>
                    <p id="kmpr-nudge-title" data-nudge-title class="text-sm font-black text-white leading-6"></p>
                    <p data-nudge-text class="text-xs text-slate-300 leading-6 mt-0.5"></p>
                </div>
            </div>
            <div class="relative mt-3.5 flex items-center gap-2">
                <a data-nudge-link data-nudge-go href="#" class="flex-1 inline-flex items-center justify-center gap-2 h-11 px-5 rounded-full bg-gradient-to-l from-fuchsia-500 to-secondary text-slate-950 text-sm font-black whitespace-nowrap">
                    <svg width="14" height="14" class="shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 4v16l13-8z"/></svg>
                    <?= esc_html(kmpr_nudge_opt('nudge_btn')); ?>
                </a>
                <button type="button" data-nudge-shuffle class="size-11 shrink-0 rounded-full border border-white/10 bg-white/[0.04] text-slate-300 hover:text-white flex items-center justify-center cursor-pointer" aria-label="یه بازی دیگه">
                    <svg width="16" height="16" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 3h5v5M4 20 21 3M21 16v5h-5M15 15l6 6M4 4l5 5"/></svg>
                </button>
                <button type="button" data-nudge-close data-nudge-later class="h-11 px-4 shrink-0 rounded-full text-xs font-bold text-slate-400 hover:text-white cursor-pointer whitespace-nowrap"><?= esc_html(kmpr_nudge_opt('nudge_later')); ?></button>
            </div>
        </div>
    </aside>
    <?php
}, 5);
