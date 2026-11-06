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
     * Enrich Yoast's Schema @graph seamlessly
     *
     * @param array $graph
     * @param object $context
     * @return array
     */
    public function enrich_yoast_schema_graph($graph, $context) {
        if (!is_array($graph)) {
            $graph = array();
        }

        $home_url = untrailingslashit(home_url('/'));
        $org_id   = $home_url . '/#organization';
        $site_id  = $home_url . '/#website';

        // 1. Single Service (Custom Post Type: 'services') -> Add @type: "Service"
        if (is_singular('services')) {
            $service_id   = get_the_ID();
            $permalink    = get_permalink($service_id);
            $service_node_id = $permalink . '#service';

            $service_schema = array(
                '@type'       => 'Service',
                '@id'         => $service_node_id,
                'name'        => get_the_title($service_id),
                'url'         => $permalink,
                'description' => wp_strip_all_tags(get_the_excerpt($service_id) ?: wp_trim_words(get_post_field('post_content', $service_id), 30)),
                'provider'    => array('@id' => $org_id),
                'serviceType' => 'طراحی سایت، سئو، توسعه نرم‌افزار و راهکارهای دیجیتال',
                'areaServed'  => array(
                    '@type' => 'Country',
                    'name'  => 'Iran'
                ),
                'inLanguage'  => 'fa-IR',
            );

            // Add thumbnail image if available
            if (has_post_thumbnail($service_id)) {
                $thumb_url = get_the_post_thumbnail_url($service_id, 'full');
                if ($thumb_url) {
                    $service_schema['image'] = esc_url($thumb_url);
                }
            }

            // Add Proposal / Pricing Tiers as OfferCatalog if ACF proposal rows exist
            if (have_rows('proposal', $service_id)) {
                $offers = array();
                while (have_rows('proposal', $service_id)) {
                    the_row();
                    $tier_title = get_sub_field('proposal_title');
                    $tier_desc  = get_sub_field('proposal_description') ?: '';
                    
                    if ($tier_title) {
                        $offers[] = array(
                            '@type'         => 'Offer',
                            'name'          => $tier_title,
                            'description'   => $tier_desc,
                            'url'           => $permalink,
                            'priceCurrency' => 'IRR',
                            'availability'  => 'https://schema.org/InStock',
                        );
                    }
                }

                if (!empty($offers)) {
                    $service_schema['hasOfferCatalog'] = array(
                        '@type'           => 'OfferCatalog',
                        'name'            => 'پلن‌ها و تعرفه‌های ' . get_the_title($service_id),
                        'itemListElement' => $offers
                    );
                }
            }

            $graph[] = $service_schema;

            // Link this Service node to Yoast's WebPage node
            foreach ($graph as &$piece) {
                if (isset($piece['@type']) && $piece['@type'] === 'WebPage') {
                    $piece['about'] = array('@id' => $service_node_id);
                }
            }
        }

        // 2. Single Portfolio (Custom Post Type: 'portfolio') -> Add @type: "CreativeWork"
        if (is_singular('portfolio')) {
            $portfolio_id   = get_the_ID();
            $permalink      = get_permalink($portfolio_id);
            $work_node_id   = $permalink . '#work';

            $work_schema = array(
                '@type'       => 'CreativeWork',
                '@id'         => $work_node_id,
                'name'        => get_the_title($portfolio_id),
                'url'         => $permalink,
                'headline'    => get_the_title($portfolio_id),
                'description' => wp_strip_all_tags(get_the_excerpt($portfolio_id) ?: wp_trim_words(get_post_field('post_content', $portfolio_id), 30)),
                'creator'     => array('@id' => $org_id),
                'inLanguage'  => 'fa-IR',
            );

            if (has_post_thumbnail($portfolio_id)) {
                $thumb_url = get_the_post_thumbnail_url($portfolio_id, 'full');
                if ($thumb_url) {
                    $work_schema['image'] = esc_url($thumb_url);
                }
            }

            $brand_logo = get_field('brand-logo', $portfolio_id);
            if (!empty($brand_logo['url'])) {
                $work_schema['associatedMedia'] = array(
                    '@type'      => 'ImageObject',
                    'contentUrl' => esc_url($brand_logo['url']),
                    'caption'    => esc_attr($brand_logo['title'] ?? get_the_title($portfolio_id))
                );
            }

            $graph[] = $work_schema;

            // Link CreativeWork node to WebPage
            foreach ($graph as &$piece) {
                if (isset($piece['@type']) && $piece['@type'] === 'WebPage') {
                    $piece['about'] = array('@id' => $work_node_id);
                }
            }
        }

        // 3. Services Archive or Portfolio Archive -> Enrich CollectionPage
        if (is_post_type_archive('services') || is_post_type_archive('portfolio')) {
            $cpt = is_post_type_archive('services') ? 'services' : 'portfolio';
            $archive_url = get_post_type_archive_link($cpt);

            $collection_node = array(
                '@type'       => 'CollectionPage',
                '@id'         => $archive_url . '#collection',
                'url'         => $archive_url,
                'name'        => ($cpt === 'services') ? 'خدمات تخصصی ما' : 'نمونه‌کارها و پروژه‌ها',
                'isPartOf'    => array('@id' => $site_id),
                'inLanguage'  => 'fa-IR',
            );

            $graph[] = $collection_node;
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
                '@type'     => 'Organization',
                '@id'       => $home_url . '/#organization',
                'name'      => $site_name,
                'url'       => $home_url,
                'logo'      => array(
                    '@type' => 'ImageObject',
                    'url'   => get_template_directory_uri() . '/assets/img/logo.png',
                ),
            ),
            array(
                '@type'       => 'WebSite',
                '@id'         => $home_url . '/#website',
                'url'         => $home_url,
                'name'        => $site_name,
                'description' => $site_desc,
                'publisher'   => array('@id' => $home_url . '/#organization'),
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
                'author'           => array(
                    '@type' => 'Person',
                    'name'  => get_the_author_meta('display_name', get_post_field('post_author', $post_id)),
                ),
                'publisher'        => array('@id' => $home_url . '/#organization'),
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

