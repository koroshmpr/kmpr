<?php
/**
 * Blog "course" engine: a category is a course, its posts are lessons.
 *
 * Lessons are ordered by post meta `lesson_order` ASC, then date ASC (posts without an order go last).
 * A post's course = its Yoast primary category, else its first real category.
 *
 * Public API
 *   kmpr_course_of($post_id)            → WP_Term|null
 *   kmpr_course_lessons($term_id)       → int[] ordered lesson IDs (published)
 *   kmpr_lesson_position($post_id)      → array(term, lessons, index, number, total, prev, next) | null
 *   kmpr_course_pillar($term_id)        → int post ID (is_pillar, else first lesson) | 0
 *   kmpr_course_info($term)             → array(id, title, name, intro, color, url, lessons, count, minutes, pillar)
 *   kmpr_courses()                      → array of kmpr_course_info() for non-empty categories
 *   kmpr_course_palette($color)         → literal Tailwind class strings for a course colour
 *   kmpr_lesson_level($post_id)         → array(key, label) | null
 *   kmpr_post_views($post_id) / kmpr_post_rating($post_id)
 *
 * @package KMPR
 */

if (!defined('ABSPATH')) {
    exit;
}

/* -------------------------------------------------------------------------
 * Course resolution
 * ---------------------------------------------------------------------- */

function kmpr_course_of($post_id = null)
{
    $post_id = $post_id ?: get_the_ID();
    if (!$post_id) {
        return null;
    }
    $primary = (int) get_post_meta($post_id, '_yoast_wpseo_primary_category', true);
    if ($primary) {
        $term = get_term($primary, 'category');
        if ($term && !is_wp_error($term) && has_term($term->term_id, 'category', $post_id)) {
            return $term;
        }
    }
    $terms = get_the_category($post_id);
    if (!$terms) {
        return null;
    }
    $default = (int) get_option('default_category');
    foreach ($terms as $term) {
        if ((int) $term->term_id !== $default) {
            return $term;
        }
    }
    return $terms[0];
}

function kmpr_course_lessons($term_id)
{
    static $cache = array();
    $term_id = (int) $term_id;
    if (!$term_id) {
        return array();
    }
    if (isset($cache[$term_id])) {
        return $cache[$term_id];
    }
    $ids = get_posts(array(
        'post_type'              => 'post',
        'post_status'            => 'publish',
        'posts_per_page'         => 200,
        'fields'                 => 'ids',
        'no_found_rows'          => true,
        'suppress_filters'       => false,
        'update_post_term_cache' => false,
        'tax_query'              => array(array('taxonomy' => 'category', 'field' => 'term_id', 'terms' => $term_id, 'include_children' => false)),
        'orderby'                => 'date',
        'order'                  => 'ASC',
    ));
    if ($ids) {
        update_meta_cache('post', $ids);
    }
    $rows = array();
    foreach ($ids as $pos => $id) {
        $order  = get_post_meta($id, 'lesson_order', true);
        $rows[] = array($id, ($order === '' || $order === null) ? PHP_INT_MAX : (int) $order, $pos);
    }
    usort($rows, function ($a, $b) {
        return ($a[1] <=> $b[1]) ?: ($a[2] <=> $b[2]);
    });
    return $cache[$term_id] = array_map('intval', array_column($rows, 0));
}

function kmpr_lesson_position($post_id = null)
{
    $post_id = $post_id ?: get_the_ID();
    $term    = kmpr_course_of($post_id);
    if (!$term) {
        return null;
    }
    $lessons = kmpr_course_lessons($term->term_id);
    $index   = array_search((int) $post_id, $lessons, true);
    if ($index === false) {
        return null;
    }
    return array(
        'term'    => $term,
        'lessons' => $lessons,
        'index'   => $index,
        'number'  => $index + 1,
        'total'   => count($lessons),
        'prev'    => $lessons[$index - 1] ?? 0,
        'next'    => $lessons[$index + 1] ?? 0,
    );
}

function kmpr_course_pillar($term_id)
{
    $lessons = kmpr_course_lessons($term_id);
    foreach ($lessons as $id) {
        if (get_post_meta($id, 'is_pillar', true)) {
            return $id;
        }
    }
    return $lessons[0] ?? 0;
}

function kmpr_course_info($term)
{
    $term = is_object($term) ? $term : get_term((int) $term, 'category');
    if (!$term || is_wp_error($term)) {
        return null;
    }
    $acf_id  = 'category_' . $term->term_id;
    $get     = function ($name) use ($acf_id, $term) {
        if (function_exists('get_field')) {
            return get_field($name, $acf_id);
        }
        return get_term_meta($term->term_id, $name, true);
    };
    $lessons = kmpr_course_lessons($term->term_id);
    $minutes = 0;
    foreach ($lessons as $id) {
        $minutes += reading_time($id);
    }
    $color = $get('course_color');
    return array(
        'id'      => (int) $term->term_id,
        'name'    => $term->name,
        'title'   => $get('course_title') ?: $term->name,
        'intro'   => $get('course_intro') ?: wp_strip_all_tags(term_description($term)),
        'color'   => in_array($color, array('amber', 'sky', 'emerald', 'violet', 'rose'), true) ? $color : 'amber',
        'url'     => get_category_link($term),
        'lessons' => $lessons,
        'count'   => count($lessons),
        'minutes' => $minutes,
        'pillar'  => kmpr_course_pillar($term->term_id),
    );
}

function kmpr_courses()
{
    $terms = get_categories(array('hide_empty' => true, 'exclude' => array((int) get_option('default_category'))));
    $out   = array();
    foreach ($terms as $term) {
        $info = kmpr_course_info($term);
        if ($info && $info['count']) {
            $out[] = $info;
        }
    }
    usort($out, function ($a, $b) {
        return $b['count'] <=> $a['count'];
    });
    return $out;
}

/**
 * Literal class strings per colour (kept literal so Tailwind's scanner compiles them).
 */
function kmpr_course_palette($color)
{
    $map = array(
        'amber'   => array('text' => 'text-amber-400', 'bg' => 'bg-amber-400', 'soft' => 'bg-amber-400/10', 'border' => 'border-amber-400/30', 'hover' => 'hover:border-amber-400/50', 'glow' => 'from-amber-500/25', 'deco' => 'decoration-amber-400', 'ring' => 'ring-amber-400/40', 'hex' => '#f59e0b'),
        'sky'     => array('text' => 'text-sky-400', 'bg' => 'bg-sky-400', 'soft' => 'bg-sky-400/10', 'border' => 'border-sky-400/30', 'hover' => 'hover:border-sky-400/50', 'glow' => 'from-sky-500/25', 'deco' => 'decoration-sky-400', 'ring' => 'ring-sky-400/40', 'hex' => '#38bdf8'),
        'emerald' => array('text' => 'text-emerald-400', 'bg' => 'bg-emerald-400', 'soft' => 'bg-emerald-400/10', 'border' => 'border-emerald-400/30', 'hover' => 'hover:border-emerald-400/50', 'glow' => 'from-emerald-500/25', 'deco' => 'decoration-emerald-400', 'ring' => 'ring-emerald-400/40', 'hex' => '#34d399'),
        'violet'  => array('text' => 'text-violet-400', 'bg' => 'bg-violet-400', 'soft' => 'bg-violet-400/10', 'border' => 'border-violet-400/30', 'hover' => 'hover:border-violet-400/50', 'glow' => 'from-violet-500/25', 'deco' => 'decoration-violet-400', 'ring' => 'ring-violet-400/40', 'hex' => '#a78bfa'),
        'rose'    => array('text' => 'text-rose-400', 'bg' => 'bg-rose-400', 'soft' => 'bg-rose-400/10', 'border' => 'border-rose-400/30', 'hover' => 'hover:border-rose-400/50', 'glow' => 'from-rose-500/25', 'deco' => 'decoration-rose-400', 'ring' => 'ring-rose-400/40', 'hex' => '#fb7185'),
    );
    return $map[$color] ?? $map['amber'];
}

function kmpr_lesson_level($post_id = null)
{
    $post_id = $post_id ?: get_the_ID();
    $labels  = array('beginner' => 'مقدماتی', 'intermediate' => 'متوسط', 'advanced' => 'پیشرفته');
    $key     = get_post_meta($post_id, 'difficulty', true);
    return isset($labels[$key]) ? array('key' => $key, 'label' => $labels[$key]) : null;
}

function kmpr_post_views($post_id = null)
{
    return (int) get_post_meta($post_id ?: get_the_ID(), 'post_views', true);
}

function kmpr_post_rating($post_id = null)
{
    $post_id = $post_id ?: get_the_ID();
    $count   = (int) get_post_meta($post_id, 'total_ratings', true);
    $sum     = (float) get_post_meta($post_id, 'total_rating_value', true);
    return array('count' => $count, 'average' => $count ? round($sum / $count, 1) : 0);
}

/**
 * FAQ rows of a post (from the ACF repeater, raw-meta safe).
 */
function kmpr_post_faq($post_id = null)
{
    $post_id = $post_id ?: get_the_ID();
    $rows    = function_exists('get_field') ? get_field('post_faq', $post_id) : array();
    if (!is_array($rows)) {
        $rows = array();
    }
    return array_values(array_filter($rows, function ($r) {
        return !empty($r['question']) && !empty($r['answer']);
    }));
}

/**
 * `data-split` only for headings without Latin words: the word-split motion turns each word into an
 * inline-block, which breaks bidi ordering of multi-word Latin phrases inside RTL text.
 */
function kmpr_split_attr($text)
{
    return preg_match('/[A-Za-z]/', (string) $text) ? '' : 'data-split';
}

function kmpr_author_name($post_id = null)
{
    $name = get_the_author_meta('display_name', (int) get_post_field('post_author', $post_id ?: get_the_ID()));
    return $name ?: 'کوروش محمدپور';
}

/**
 * Persian minutes label ("۱ ساعت و ۲۰ دقیقه").
 */
function kmpr_minutes_label($minutes)
{
    $minutes = (int) $minutes;
    if ($minutes < 60) {
        return kmpr_fa_num($minutes) . ' دقیقه';
    }
    $h = intdiv($minutes, 60);
    $m = $minutes % 60;
    return kmpr_fa_num($h) . ' ساعت' . ($m ? ' و ' . kmpr_fa_num($m) . ' دقیقه' : '');
}

/* -------------------------------------------------------------------------
 * Hooks
 * ---------------------------------------------------------------------- */

/**
 * Count views before output starts (setcookie needs headers).
 */
add_action('template_redirect', function () {
    if (is_singular('post') && !is_preview() && !kmpr_is_speculative_request() && function_exists('track_post_views')) {
        track_post_views(get_queried_object_id());
    }
});

/**
 * Blog home: 9 lessons per page on the main query (crawlable /page/N).
 */
add_action('pre_get_posts', function ($q) {
    if (is_admin() || !$q->is_main_query()) {
        return;
    }
    if ($q->is_home()) {
        $q->set('posts_per_page', 9);
        $q->set('ignore_sticky_posts', true);
    }
    if ($q->is_category()) {
        $q->set('posts_per_page', 60);
    }
});

/* -------------------------------------------------------------------------
 * Structured data
 * Yoast active: add FAQPage + course ItemList into its graph (it already emits Article + BreadcrumbList).
 * Yoast inactive: schema-manager.php emits BlogPosting; we add BreadcrumbList + FAQPage + ItemList.
 * ---------------------------------------------------------------------- */

function kmpr_blog_schema_pieces($with_breadcrumb)
{
    $pieces = array();
    $home   = untrailingslashit(home_url('/'));
    $blog   = get_permalink(get_option('page_for_posts')) ?: home_url('/blog/');

    if (is_singular('post')) {
        $post_id = get_queried_object_id();
        $url     = get_permalink($post_id);
        $pos     = kmpr_lesson_position($post_id);

        if ($with_breadcrumb) {
            $items = array(
                array('name' => get_bloginfo('name'), 'item' => $home . '/'),
                array('name' => 'وبلاگ', 'item' => $blog),
            );
            if ($pos) {
                $items[] = array('name' => kmpr_course_info($pos['term'])['title'], 'item' => get_category_link($pos['term']));
            }
            $items[] = array('name' => get_the_title($post_id), 'item' => $url);
            $list    = array();
            foreach ($items as $i => $it) {
                $list[] = array('@type' => 'ListItem', 'position' => $i + 1, 'name' => $it['name'], 'item' => $it['item']);
            }
            $pieces[] = array('@type' => 'BreadcrumbList', '@id' => $url . '#kmpr-breadcrumb', 'itemListElement' => $list);
        }

        $faq = kmpr_post_faq($post_id);
        if ($faq) {
            $pieces[] = array(
                '@type'      => 'FAQPage',
                '@id'        => $url . '#kmpr-faq',
                'url'        => $url,
                'inLanguage' => 'fa-IR',
                'mainEntity' => array_map(function ($r) {
                    return array('@type' => 'Question', 'name' => wp_strip_all_tags($r['question']), 'acceptedAnswer' => array('@type' => 'Answer', 'text' => wp_strip_all_tags($r['answer'])));
                }, $faq),
            );
        }

        if ($pos && $pos['total'] > 1) {
            $info     = kmpr_course_info($pos['term']);
            $pieces[] = kmpr_course_itemlist($info);
        }
    } elseif (is_category()) {
        $info = kmpr_course_info(get_queried_object());
        if ($info && $info['count']) {
            $pieces[] = kmpr_course_itemlist($info);
            if ($with_breadcrumb) {
                $pieces[] = array('@type' => 'BreadcrumbList', '@id' => $info['url'] . '#kmpr-breadcrumb', 'itemListElement' => array(
                    array('@type' => 'ListItem', 'position' => 1, 'name' => get_bloginfo('name'), 'item' => $home . '/'),
                    array('@type' => 'ListItem', 'position' => 2, 'name' => 'وبلاگ', 'item' => $blog),
                    array('@type' => 'ListItem', 'position' => 3, 'name' => $info['title'], 'item' => $info['url']),
                ));
            }
        }
    }
    return $pieces;
}

function kmpr_course_itemlist($info)
{
    $items = array();
    foreach ($info['lessons'] as $i => $id) {
        $items[] = array('@type' => 'ListItem', 'position' => $i + 1, 'url' => get_permalink($id), 'name' => get_the_title($id));
    }
    return array(
        '@type'           => 'ItemList',
        '@id'             => $info['url'] . '#kmpr-course',
        'name'            => $info['title'],
        'description'     => $info['intro'],
        'url'             => $info['url'],
        'itemListOrder'   => 'https://schema.org/ItemListOrderAscending',
        'numberOfItems'   => $info['count'],
        'itemListElement' => $items,
    );
}

add_filter('wpseo_schema_graph', function ($graph) {
    if (is_singular('post') || is_category()) {
        foreach (kmpr_blog_schema_pieces(false) as $piece) {
            $graph[] = $piece;
        }
    }
    return $graph;
}, 30);

add_action('wp_head', function () {
    if (defined('WPSEO_VERSION') || !(is_singular('post') || is_category())) {
        return;
    }
    $pieces = kmpr_blog_schema_pieces(true);
    if (!$pieces) {
        return;
    }
    echo "\n<script type=\"application/ld+json\">" . wp_json_encode(array('@context' => 'https://schema.org', '@graph' => $pieces), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "</script>\n";
}, 5);

/* -------------------------------------------------------------------------
 * Comments: generated avatars, threaded dark markup, AJAX submit, "helpful" votes
 * ---------------------------------------------------------------------- */

/**
 * Initials avatar (deterministic gradient) — or the site author's photo.
 */
function kmpr_comment_avatar($name, $seed = '', $is_author = false, $size = 'size-10 sm:size-11')
{
    if ($is_author) {
        return '<img src="' . esc_url(get_template_directory_uri() . '/public/images/korosh-mpr.webp') . '" alt="" width="44" height="44" loading="lazy" class="' . esc_attr($size) . ' shrink-0 rounded-2xl object-cover object-top border border-secondary/40">';
    }
    $name  = trim(wp_strip_all_tags((string) $name)) ?: 'مهمان';
    $parts = preg_split('/\s+/u', $name);
    $init  = mb_substr($parts[0], 0, 1) . (isset($parts[1]) ? mb_substr($parts[1], 0, 1) : '');
    $hue   = abs(crc32(strtolower($seed ?: $name))) % 360;
    $style = sprintf('background:linear-gradient(135deg,hsl(%1$d 70%% 55%%),hsl(%2$d 75%% 40%%))', $hue, ($hue + 50) % 360);
    return '<span class="' . esc_attr($size) . ' shrink-0 inline-flex items-center justify-center rounded-2xl text-sm font-black text-white shadow-inner select-none" style="' . esc_attr($style) . '" aria-hidden="true">' . esc_html(mb_strtoupper($init)) . '</span>';
}

/**
 * "۳ روز پیش" for recent dates, Shamsi date after 30 days.
 */
function kmpr_relative_date($timestamp)
{
    $diff = time() - (int) $timestamp;
    if ($diff < MINUTE_IN_SECONDS) {
        return 'همین حالا';
    }
    if ($diff < 30 * DAY_IN_SECONDS) {
        return kmpr_fa_num(human_time_diff((int) $timestamp, time())) . ' پیش';
    }
    return shamsi_date('j F Y', (int) $timestamp);
}

function kmpr_comment_is_author($comment)
{
    $author = (int) get_post_field('post_author', $comment->comment_post_ID);
    if ((int) $comment->user_id && ((int) $comment->user_id === $author || user_can((int) $comment->user_id, 'manage_options'))) {
        return true;
    }
    return false;
}

function kmpr_comment_callback($comment, $args, $depth)
{
    $is_author = kmpr_comment_is_author($comment);
    $votes     = (int) get_comment_meta($comment->comment_ID, 'helpful', true);
    $pending   = ('0' === (string) $comment->comment_approved);
    $time      = strtotime($comment->comment_date_gmt . ' UTC');
    $name      = get_comment_author($comment);
    $can_reply = comments_open($comment->comment_post_ID) && $depth < (int) ($args['max_depth'] ?? 2);
    ?>
    <li id="comment-<?php comment_ID(); ?>" <?php comment_class('kmpr-comment list-none' . ($depth > 1 ? ' is-reply' : ''), $comment); ?>
        data-time="<?= (int) $time; ?>" data-votes="<?= (int) $votes; ?>">
        <article class="kmpr-comment__card flex gap-3 sm:gap-4 p-4 sm:p-5 rounded-2xl <?= $pending ? 'bg-amber-500/[0.04] border border-dashed border-amber-400/40' : ($is_author ? 'bg-secondary/[0.06] border border-secondary/25' : 'bg-white/[0.025] border border-white/10'); ?>">
            <?= kmpr_comment_avatar($name, $comment->comment_author_email, $is_author); // phpcs:ignore -- escaped inside ?>
            <div class="min-w-0 flex-1">
                <header class="flex flex-wrap items-center gap-x-3 gap-y-1 mb-2">
                    <strong class="text-[15px] font-black text-white"><?= esc_html($name); ?></strong>
                    <?php if ($is_author) : ?>
                        <span class="text-xs font-black px-2.5 py-0.5 rounded-full bg-secondary text-slate-950">نویسنده</span>
                    <?php endif; ?>
                    <a href="<?= esc_url(get_comment_link($comment)); ?>" class="text-[13px] text-slate-400 hover:text-slate-200 transition-colors">
                        <time datetime="<?= esc_attr(gmdate('c', $time)); ?>" title="<?= esc_attr(shamsi_date('j F Y، H:i', $time)); ?>"><?= esc_html(kmpr_relative_date($time)); ?></time>
                    </a>
                </header>
                <?php if ($pending) : ?>
                    <p class="inline-flex items-center gap-2 mb-3 px-3 py-1 rounded-full bg-amber-400/10 border border-amber-400/30 text-xs font-bold text-amber-300">
                        <svg width="14" height="14" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                        در انتظار تأیید؛ پس از بررسی برای همه نمایش داده می‌شود.
                    </p>
                <?php endif; ?>
                <div class="kmpr-comment__text text-[15px] text-slate-300 leading-8 break-words"><?php comment_text($comment); ?></div>
                <?php if (!$pending) : ?>
                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <button type="button" x-data="kmprVote(<?= (int) $comment->comment_ID; ?>, <?= (int) $votes; ?>)" @click="vote()" :disabled="voted || busy"
                                :aria-pressed="voted.toString()" aria-label="این دیدگاه مفید بود"
                                class="inline-flex items-center gap-1.5 min-h-10 px-4 rounded-full border text-[13px] font-bold transition-colors cursor-pointer disabled:cursor-default"
                                :class="voted ? 'border-emerald-400/40 bg-emerald-400/10 text-emerald-300' : 'border-white/10 text-slate-300 hover:text-white hover:border-white/25'">
                            <svg width="15" height="15" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M7 10v11H3V10h4Zm0 0 4-7a2.5 2.5 0 0 1 2.5 2.5V9h5.6a2 2 0 0 1 2 2.4l-1.6 8A2 2 0 0 1 17.5 21H7"/></svg>
                            مفید بود
                            <span x-show="count > 0" x-text="'(' + $store.lessons.fa(count) + ')'"<?= $votes ? '' : ' x-cloak'; ?>><?= $votes ? '(' . esc_html(kmpr_fa_num($votes)) . ')' : ''; ?></span>
                        </button>
                        <?php if ($can_reply) : ?>
                            <a href="<?= esc_url(add_query_arg('replytocom', $comment->comment_ID, get_permalink($comment->comment_post_ID)) . '#respond'); ?>" rel="nofollow"
                               @click.prevent="$dispatch('kmpr-reply', { id: <?= (int) $comment->comment_ID; ?>, name: <?= esc_attr(wp_json_encode($name)); ?> })"
                               class="inline-flex items-center gap-1.5 min-h-10 px-4 rounded-full border border-white/10 text-[13px] font-bold text-slate-300 hover:text-white hover:border-secondary/50 transition-colors">
                                <svg width="15" height="15" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M9 14 4 9l5-5"/><path d="M4 9h10.5a5.5 5.5 0 0 1 0 11H11"/></svg>
                                پاسخ
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </article>
    <?php
    // </li> is closed by Walker_Comment::end_el.
}

/**
 * `inline code` in comments (content is already sanitised by kses).
 */
add_filter('comment_text', function ($text) {
    return preg_replace('/`([^`\n<]{1,200})`/u', '<code>$1</code>', $text);
}, 30);

/**
 * AJAX comment submission (falls back to wp-comments-post.php without JS).
 */
function kmpr_ajax_comment()
{
    if (!check_ajax_referer('kmpr_comment', 'kmpr_nonce', false)) {
        wp_send_json_error(array('message' => 'نشست شما منقضی شده است؛ صفحه را تازه کنید.'), 403);
    }
    // Honeypot + minimum fill time against bots.
    if (!empty($_POST['kmpr_website']) || (time() - (int) ($_POST['kmpr_ts'] ?? 0)) < 3) {
        wp_send_json_error(array('message' => 'ارسال نامعتبر بود؛ چند ثانیه بعد دوباره تلاش کنید.'), 400);
    }
    $ip  = preg_replace('/[^0-9a-f:.]/i', '', $_SERVER['REMOTE_ADDR'] ?? '');
    $key = 'kmpr_cmt_' . md5($ip);
    if ((int) get_transient($key) >= 6) {
        wp_send_json_error(array('message' => 'تعداد ارسال‌ها زیاد بود؛ کمی بعد دوباره تلاش کنید.'), 429);
    }

    $comment = wp_handle_comment_submission(wp_unslash($_POST));
    if (is_wp_error($comment)) {
        $map = array(
            'require_name_email'      => array('author', 'نام و ایمیل را وارد کنید.'),
            'require_valid_email'     => array('email', 'ایمیل معتبر نیست.'),
            'require_valid_comment'   => array('comment', 'متن دیدگاه خالی است.'),
            'comment_duplicate'       => array('comment', 'این دیدگاه را قبلاً فرستاده‌اید.'),
            'comment_flood'           => array('comment', 'کمی صبر کنید و دوباره بفرستید.'),
            'comment_content_column_length' => array('comment', 'متن دیدگاه بیش از حد طولانی است.'),
        );
        $code = $comment->get_error_code();
        wp_send_json_error(array(
            'field'   => $map[$code][0] ?? '',
            'message' => $map[$code][1] ?? wp_strip_all_tags($comment->get_error_message()),
        ), 400);
    }
    set_transient($key, (int) get_transient($key) + 1, HOUR_IN_SECONDS);

    $user = wp_get_current_user();
    do_action('set_comment_cookies', $comment, $user, !empty($_POST['wp-comment-cookies-consent']));

    $depth = 1;
    for ($p = (int) $comment->comment_parent; $p; $p = (int) get_comment($p)->comment_parent) {
        $depth++;
    }
    $GLOBALS['comment'] = $comment;
    ob_start();
    kmpr_comment_callback($comment, array('max_depth' => (int) get_option('thread_comments_depth', 2)), $depth);
    echo '</li>';
    wp_send_json_success(array(
        'html'     => ob_get_clean(),
        'id'       => (int) $comment->comment_ID,
        'parent'   => (int) $comment->comment_parent,
        'approved' => ('1' === (string) $comment->comment_approved),
    ));
}
add_action('wp_ajax_kmpr_comment', 'kmpr_ajax_comment');
add_action('wp_ajax_nopriv_kmpr_comment', 'kmpr_ajax_comment');

/**
 * "Helpful" vote: one per browser (localStorage, client side) + a per-IP hourly burst cap.
 * No per-vote records are stored on the server (keeps wp_options small on limited hosting).
 */
function kmpr_ajax_comment_vote()
{
    if (!check_ajax_referer('kmpr_comment', 'kmpr_nonce', false)) {
        wp_send_json_error(null, 403);
    }
    $id      = absint($_POST['comment_id'] ?? 0);
    $comment = $id ? get_comment($id) : null;
    if (!$comment || '1' !== (string) $comment->comment_approved) {
        wp_send_json_error(null, 404);
    }
    $ip      = preg_replace('/[^0-9a-f:.]/i', '', $_SERVER['REMOTE_ADDR'] ?? '');
    $burst   = 'kmpr_votes_' . md5($ip);
    $count   = (int) get_comment_meta($id, 'helpful', true);
    if ((int) get_transient($burst) >= 30) {
        wp_send_json_success(array('count' => $count, 'duplicate' => true));
    }
    $count++;
    update_comment_meta($id, 'helpful', $count);
    set_transient($burst, (int) get_transient($burst) + 1, HOUR_IN_SECONDS);
    wp_send_json_success(array('count' => $count));
}
add_action('wp_ajax_kmpr_comment_vote', 'kmpr_ajax_comment_vote');
add_action('wp_ajax_nopriv_kmpr_comment_vote', 'kmpr_ajax_comment_vote');
