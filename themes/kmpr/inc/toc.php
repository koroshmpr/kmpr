<?php
/**
 * Table of Contents (TOC) Generator with Real-time Active Heading Tracking
 *
 * @package KMPR
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Automatically add semantic IDs and Alpine.js intersection attributes to headings
 */
function auto_id_headings($content)
{
    $content = preg_replace_callback('/(<h[2-4](.*?))>(.*?)(<\/h[2-4]>)/i', function ($matches) {
        $heading_text = strip_tags($matches[3]);

        // Check if ID already exists
        if (preg_match('/id=[\'"]([^\'"]+)[\'"]/i', $matches[2], $id_match)) {
            $semantic_id = $id_match[1];
            if (stripos($matches[2], 'x-intersect') !== false) {
                return $matches[0];
            }
            return $matches[1] . ' x-intersect:enter.margin.-15%.0px.-70%.0px="active = \'' . esc_js($semantic_id) . '\'">' . $matches[3] . $matches[4];
        }

        // Generate clean semantic decoded ID
        $semantic_id = urldecode(sanitize_title_with_dashes($heading_text));
        if (empty($semantic_id)) {
            $semantic_id = 'heading-' . wp_generate_password(6, false);
        }

        return $matches[1] . $matches[2] . ' x-intersect:enter.margin.-15%.0px.-70%.0px="active = \'' . esc_js($semantic_id) . '\'" id="' . esc_attr($semantic_id) . '">' . $matches[3] . $matches[4];
    }, $content);

    return $content;
}
add_filter('the_content', 'auto_id_headings');

/**
 * Extract specific heading levels
 */
function get_headings($content, $levels = [2, 3])
{
    $headings = [];
    $tag_pattern = implode('|', array_map('intval', $levels));

    preg_match_all("/<h($tag_pattern)([^<]*)>(.*)<\/h[$tag_pattern]>/i", $content, $matches);

    for ($i = 0; $i < count($matches[1]); $i++) {
        $headings[$i]["tag"] = intval($matches[1][$i]);
        $att_string = $matches[2][$i];

        preg_match("/id=\"([^\"]*)\"/", $att_string, $id_matches);
        $headings[$i]["id"] = $id_matches[1] ?? '';
        $headings[$i]["name"] = strip_tags($matches[3][$i]);
    }

    return $headings;
}

/**
 * Generate Modern Reactive TOC with Active Heading Spy and Center-Scroll Tracking
 */
function get_toc($content, $levels = [2, 3])
{
    $headings = get_headings($content, $levels);
    if (empty($headings)) {
        return '<p class="text-xs text-slate-400 py-2">هیچ سرفصلی برای این مقاله ثبت نشده است.</p>';
    }

    ob_start();
    ?>
    <ul class="list-none space-y-1.5 border-s-2 border-white/10 ps-0 my-0 select-none text-start"
        x-effect="if (active) {
            $nextTick(() => {
                let activeLi = null;
                const items = $el.querySelectorAll('li[data-toc-target]');
                for (let i = 0; i < items.length; i++) {
                    if (items[i].getAttribute('data-toc-target') === active) {
                        activeLi = items[i];
                        break;
                    }
                }
                if (activeLi) {
                    const scrollContainer = activeLi.closest('.overflow-y-auto') || activeLi.closest('.overflow-y-scroll') || activeLi.closest('div');
                    if (scrollContainer) {
                        const containerRect = scrollContainer.getBoundingClientRect();
                        const liRect = activeLi.getBoundingClientRect();
                        const scrollAmount = (liRect.top - containerRect.top) - (scrollContainer.clientHeight / 2) + (liRect.height / 2);
                        scrollContainer.scrollBy({ top: scrollAmount, behavior: 'smooth' });
                    }
                }
            });
        }">

        <?php foreach ($headings as $heading) : 
            $is_subheading = ($heading['tag'] >= 3);
        ?>
            <li data-toc-target="<?= esc_attr($heading['id']); ?>"
                class="border-s-2 transition-all duration-300 <?= $is_subheading ? 'ps-5 text-xs' : 'ps-3 text-xs sm:text-sm'; ?> -ms-[2px]"
                :class="active === '<?= esc_attr($heading['id']); ?>' ? '!border-secondary' : 'border-transparent'">

                <button type="button"
                        aria-label="<?= esc_attr($heading['name']); ?>"
                        class="w-full text-start flex items-center py-1.5 px-2.5 rounded-xl cursor-pointer transition-all duration-300 leading-snug"
                        :class="active === '<?= esc_attr($heading['id']); ?>' ? '!text-secondary !font-black bg-secondary/15 shadow-2xs' : 'text-slate-300 hover:text-white hover:bg-white/[0.05] font-medium'"
                        @click.prevent="document.getElementById('<?= esc_attr($heading['id']); ?>').scrollIntoView({ behavior: 'smooth' }); toc = false">
                    <?= esc_html($heading['name']); ?>
                </button>
            </li>
        <?php endforeach; ?>
    </ul>
    <?php
    return ob_get_clean();
}

/**
 * TOC Shortcode with custom levels
 */
function toc_shortcode($atts = [])
{
    $atts = shortcode_atts(['levels' => '2,3'], $atts);
    $levels = array_map('intval', explode(',', $atts['levels']));

    return get_toc(auto_id_headings(get_the_content()), $levels);
}
add_shortcode('TOC', 'toc_shortcode');
