<?php
/**
 * Advanced Schema.org Manager with Seamless Yoast SEO Integration
 * 
 * Ensures all required schemas (Organization, WebSite, Service, CreativeWork, Article, Breadcrumbs)
 * are generated for every page without duplicating or conflicting with Yoast SEO.
 */

if (!defined('ABSPATH')) {
    exit;
}

class KMPR_Schema_Manager {

    public function __construct() {
        // If Yoast SEO is active, hook into Yoast's official schema graph filter
        add_filter('wpseo_schema_graph', array($this, 'enrich_yoast_schema_graph'), 20, 2);

        // Fallback: If Yoast is NOT active or fails to output, print clean standalone schemas
        add_action('wp_head', array($this, 'output_fallback_schema'), 2);
    }

    /**
     * Canonical @id of the site owner (Person). Templates reference the same id
     * (page-resume.php, single-services.php) so every node points at one entity.
     */
    public static function person_id() {
        return untrailingslashit(home_url('/')) . '/#person';
    }

    /**
     * Public profiles for Person.sameAs (from «تماس و شبکه‌های اجتماعی» options).
     */
    public static function same_as() {
        $urls = array();
        if (function_exists('kmpr_contact')) {
            foreach (array('linkedin', 'github', 'instagram', 'twitter', 'telegram', 'youtube') as $k) {
                $v = kmpr_contact($k);
                if ($v && preg_match('~^https?://~i', $v)) {
                    $urls[] = esc_url_raw($v);
                }
            }
        }
        return array_values(array_unique($urls));
    }

    /** Extra facts about the person, merged into Yoast's Person node. */
    public static function person_extra() {
        return array(
            'name'          => 'کوروش محمدپور',
            'alternateName' => array('Korosh Mpr', 'KMPR'),
            'jobTitle'      => 'طراح و توسعه‌دهنده وب',
            'description'   => 'کوروش محمدپور، طراح و توسعه‌دهنده وب؛ طراحی سایت اختصاصی، توسعه با وردپرس و Next.js، بهینه‌سازی سرعت و سئو تکنیکال.',
            'url'           => home_url('/'),
            'image'         => array(
                '@type'      => 'ImageObject',
                '@id'        => untrailingslashit(home_url('/')) . '/#personlogo',
                'url'        => get_template_directory_uri() . '/public/images/korosh-mpr.webp',
                'contentUrl' => get_template_directory_uri() . '/public/images/korosh-mpr.webp',
                'caption'    => 'کوروش محمدپور',
                'inLanguage' => 'fa-IR',
            ),
            'knowsAbout'    => array('طراحی سایت', 'توسعه وب', 'WordPress', 'Next.js', 'React', 'Tailwind CSS', 'سئو تکنیکال', 'Core Web Vitals', 'بهینه‌سازی سرعت سایت'),
            'knowsLanguage' => array('fa', 'en'),
            'sameAs'        => self::same_as(),
        );
    }

    /** Replace every reference to $from (as an @id value) with $to, recursively. */
    private function swap_ids($node, $from, $to) {
        if (is_array($node)) {
            foreach ($node as $k => $v) {
                if ($k === '@id' && $v === $from) {
                    $node[$k] = $to;
                } elseif (is_array($v)) {
                    $node[$k] = $this->swap_ids($v, $from, $to);
                }
            }
        }
        return $node;
    }

    /** Index of the first graph node whose @type contains $type. */
    private function find($graph, $type) {
        foreach ($graph as $i => $piece) {
            if (is_array($piece) && in_array($type, (array) ($piece['@type'] ?? array()), true)) {
                return $i;
            }
        }
        return null;
    }

    /** ItemList of the posts in the main query (archive pages). */
    private function archive_itemlist($url, $name) {
        global $wp_query;
        $items = array();
        foreach ((array) $wp_query->posts as $i => $p) {
            $items[] = array('@type' => 'ListItem', 'position' => $i + 1, 'url' => get_permalink($p), 'name' => wp_strip_all_tags(get_the_title($p)));
        }
        if (!$items) {
            return null;
        }
        return array(
            '@type'           => 'ItemList',
            '@id'             => $url . '#itemlist',
            'name'            => $name,
            'numberOfItems'   => count($items),
            'itemListElement' => $items,
        );
    }

    /**
     * Enrich Yoast's Schema @graph: one coherent graph per page.
     *
     * @param array  $graph
     * @param object $context
     * @return array
     */
    public function enrich_yoast_schema_graph($graph, $context) {
        if (!is_array($graph)) {
            $graph = array();
        }

        // 1. One Person entity for the whole site (Yoast "person" mode), with a stable @id.
        $person_id = self::person_id();
        $pi        = $this->find($graph, 'Person');
        if ($pi !== null) {
            $yoast_id = $graph[$pi]['@id'] ?? '';
            if ($yoast_id && $yoast_id !== $person_id && strpos($yoast_id, '/#/schema/person/') !== false) {
                $graph = $this->swap_ids($graph, $yoast_id, $person_id);
            }
            if (($graph[$pi]['@id'] ?? '') === $person_id) {
                $graph[$pi] = array_merge($graph[$pi], self::person_extra());
                $graph[$pi]['logo'] = array('@id' => untrailingslashit(home_url('/')) . '/#personlogo');
            }
            // Duplicate author Person nodes (same user) collapse into the site Person.
            foreach ($graph as $i => $piece) {
                if ($i !== $pi && is_array($piece) && in_array('Person', (array) ($piece['@type'] ?? array()), true) && ($piece['@id'] ?? '') === $person_id) {
                    unset($graph[$i]);
                }
            }
            $graph = array_values($graph);
        }

        // Single-author site: every Article/BlogPosting is written by the site Person
        // (also repairs posts whose post_author points at a deleted user -> empty Person node).
        foreach ($graph as $i => $piece) {
            if (!is_array($piece)) {
                continue;
            }
            $types = (array) ($piece['@type'] ?? array());
            if (array_intersect($types, array('Article', 'BlogPosting'))) {
                $graph[$i]['author'] = array('@id' => $person_id);
            }
            if (in_array('Person', $types, true) && empty($piece['@id'])) {
                unset($graph[$i]);
            }
        }
        $graph = array_values($graph);

        // WebSite: Persian name + alternate brand.
        $wi = $this->find($graph, 'WebSite');
        if ($wi !== null) {
            $graph[$wi]['name']          = 'کوروش محمدپور';
            $graph[$wi]['alternateName'] = 'KMPR';
            $graph[$wi]['inLanguage']    = 'fa-IR';
        }

        $page_i = $this->find($graph, 'WebPage');
        if ($page_i === null) {
            $page_i = $this->find($graph, 'CollectionPage');
        }

        // 2. Single service: the template (single-services.php) prints the full Service node
        //    with the same @id; here we only tie the WebPage to it.
        if (is_singular('services') && $page_i !== null) {
            $graph[$page_i]['about']      = array('@id' => get_permalink(get_queried_object_id()) . '#service');
            $graph[$page_i]['mainEntity'] = array('@id' => get_permalink(get_queried_object_id()) . '#service');
        }

        // 3. Single portfolio item -> CreativeWork (enriched further in acf-portfolio-extra.php).
        if (is_singular('portfolio')) {
            $portfolio_id = get_queried_object_id();
            $permalink    = get_permalink($portfolio_id);
            $work_node_id = $permalink . '#work';

            $work_schema = array(
                '@type'       => 'CreativeWork',
                '@id'         => $work_node_id,
                'name'        => wp_strip_all_tags(get_the_title($portfolio_id)),
                'url'         => $permalink,
                'headline'    => wp_strip_all_tags(get_the_title($portfolio_id)),
                'description' => wp_strip_all_tags(get_the_excerpt($portfolio_id) ?: wp_trim_words(get_post_field('post_content', $portfolio_id), 30)),
                'creator'     => array('@id' => $person_id),
                'author'      => array('@id' => $person_id),
                'inLanguage'  => 'fa-IR',
                'mainEntityOfPage' => array('@id' => $permalink),
            );

            if (has_post_thumbnail($portfolio_id)) {
                $thumb_url = get_the_post_thumbnail_url($portfolio_id, 'full');
                if ($thumb_url) {
                    $work_schema['image'] = esc_url($thumb_url);
                }
            }

            $brand_logo = function_exists('get_field') ? get_field('brand-logo', $portfolio_id) : null;
            if (!empty($brand_logo['url'])) {
                $work_schema['associatedMedia'] = array(
                    '@type'      => 'ImageObject',
                    'contentUrl' => esc_url($brand_logo['url']),
                    'caption'    => wp_strip_all_tags($brand_logo['title'] ?? get_the_title($portfolio_id)),
                );
            }

            $graph[] = $work_schema;
            if ($page_i !== null) {
                $graph[$page_i]['about']      = array('@id' => $work_node_id);
                $graph[$page_i]['mainEntity'] = array('@id' => $work_node_id);
            }
        }

        // 4. Archives (services / portfolio / portfolio category / games): enrich Yoast's CollectionPage, no duplicates.
        if (is_post_type_archive(array('services', 'portfolio', 'game')) || is_tax('portfolio_categories')) {
            $ci = $this->find($graph, 'CollectionPage');
            if ($ci !== null) {
                $url  = $graph[$ci]['url'] ?? '';
                $name = $graph[$ci]['name'] ?? '';
                $list = $url ? $this->archive_itemlist($url, $name) : null;
                if ($list) {
                    $graph[]                     = $list;
                    $graph[$ci]['mainEntity']    = array('@id' => $list['@id']);
                }
                $graph[$ci]['about'] = array('@id' => $person_id);
            }
        }

        // 5. Résumé: the page *is* the person's profile.
        if (is_page('resume') && $page_i !== null) {
            $graph[$page_i]['@type']      = array('WebPage', 'ProfilePage');
            $graph[$page_i]['mainEntity'] = array('@id' => $person_id);
            $graph[$page_i]['about']      = array('@id' => $person_id);
        }

        // 6. About & contact pages are about the person too.
        if ((is_page('about-us') || is_page('contact-us')) && $page_i !== null) {
            if (is_page('about-us')) {
                $graph[$page_i]['@type'] = array('WebPage', 'AboutPage');
            } else {
                $graph[$page_i]['@type'] = array('WebPage', 'ContactPage');
            }
            $graph[$page_i]['about'] = array('@id' => $person_id);
        }

        return $graph;
    }

    /**
     * Fallback Schema generator
     * Only runs if Yoast SEO is NOT installed/active, ensuring schemas are ALWAYS present.
     */
    public function output_fallback_schema() {
        if (defined('WPSEO_VERSION')) {
            // Yoast is already handling the main schema graph
            return;
        }

        $home_url  = untrailingslashit(home_url('/'));
        $site_name = get_bloginfo('name');
        $site_desc = get_bloginfo('description');

        $graph = array(
            array(
                '@type'     => 'Person',
                '@id'       => self::person_id(),
                'name'      => 'کوروش محمدپور',
                'alternateName' => 'Korosh Mpr',
                'jobTitle'  => 'توسعه‌دهنده و طراح وب',
                'url'       => $home_url,
                'image'     => array(
                    '@type' => 'ImageObject',
                    'url'   => get_template_directory_uri() . '/public/images/kmpr-logo.png',
                ),
                'sameAs'    => self::same_as(),
            ),
            array(
                '@type'       => 'WebSite',
                '@id'         => $home_url . '/#website',
                'url'         => $home_url,
                'name'        => $site_name,
                'description' => $site_desc,
                'publisher'   => array('@id' => self::person_id()),
                'inLanguage'  => 'fa-IR',
            )
        );

        if (is_singular('post')) {
            $post_id = get_the_ID();
            $graph[] = array(
                '@type'            => 'BlogPosting',
                '@id'              => get_permalink($post_id) . '#article',
                'isPartOf'         => array('@id' => $home_url . '/#website'),
                'headline'         => get_the_title($post_id),
                'description'      => wp_strip_all_tags(get_the_excerpt($post_id)),
                'datePublished'    => get_the_date('c', $post_id),
                'dateModified'     => get_the_modified_date('c', $post_id),
                'inLanguage'       => 'fa-IR',
                'mainEntityOfPage' => get_permalink($post_id),
                'author'           => array('@id' => self::person_id()),
                'publisher'        => array('@id' => self::person_id()),
            );
        }

        $schema = array(
            '@context' => 'https://schema.org',
            '@graph'   => $graph,
        );

        echo "\n" . '<script type="application/ld+json">' . "\n";
        echo json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        echo "\n" . '</script>' . "\n";
    }
}

new KMPR_Schema_Manager();

