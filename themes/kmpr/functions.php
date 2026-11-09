<?php
/**
 * KMPR Theme Functions & Definitions
 *
 * Highly modular, clean architecture. All core features are separated
 * into dedicated modules inside the /inc directory.
 *
 * @package KMPR
 * @version 2.0.0
 * @author Korosh Mpr
 * @link https://kmpr.ir
 */

if (!defined('ABSPATH')) {
    exit;
}

// =========================================================================
// 1. Theme Constants
// =========================================================================
define('KMPR_VERSION', '2.0.0');
define('KMPR_DIR', get_template_directory());
define('KMPR_URI', get_template_directory_uri());
define('KMPR_INC', KMPR_DIR . '/inc/');

// =========================================================================
// 2. Load Core Modules (inc/)
// =========================================================================

/**
 * Theme Setup: Supports, navigation menus, ACF options pages, Gravity Forms
 */
require_once KMPR_INC . 'setup.php';

/**
 * Custom post types & taxonomies: portfolio, services, rating (formerly mu-plugins/custom-post-types.php)
 */
require_once KMPR_INC . 'post-types.php';

/**
 * Assets & Fonts: Tailwind CSS, deferred Alpine/Swiper JS, font preloading
 */
require_once KMPR_INC . 'assets.php';

/**
 * Performance & Optimization: Emojis disable, cleanup head scripts, bloatware removal
 */
require_once KMPR_INC . 'optimize.php';

/**
 * Navigation: Dynamic active menu detection for single/archive pages
 */
require_once KMPR_INC . 'navigation.php';

/**
 * Table of Contents: Auto heading IDs, semantic structure, center-scroll tracking
 */
require_once KMPR_INC . 'toc.php';

/**
 * Native Shamsi / Jalali Date: Zero-plugin, high-performance Persian date formatting
 */
require_once KMPR_INC . 'shamsi-date.php';

/**
 * Post Utilities & Metrics: Reading time, view counter, AJAX rating, rewrite rules, breadcrumbs
 */
require_once KMPR_INC . 'post-helpers.php';

/**
 * REST API Endpoints: Live modal search engine & filter routes
 */
require_once KMPR_INC . 'search-route.php';
// require_once KMPR_INC . 'filter-route.php';

/**
 * Advanced SEO Schema Manager: Deep JSON-LD graph compatible with Yoast SEO
 */
require_once KMPR_INC . 'schema-manager.php';

/**
 * LLMs & AI: /llms.txt, /llms-full.txt, per-page Markdown (<url>.md), AI-crawler policy (heavy parts lazy-loaded from inc/llms/)
 */
require_once KMPR_INC . 'llms-manager.php';

/**
 * Online Resume Manager: ACF field groups, dynamic data, and PDF sync for /resume/
 */
require_once KMPR_INC . 'resume-manager.php';

/**
 * Project Requests: lead capture CPT, secured REST endpoint, Telegram Bot notifications & admin workflow
 */
require_once KMPR_INC . 'project-requests.php';

/**
 * Presentation helpers: experience years, portfolio covers, request URL
 */
require_once KMPR_INC . 'home-helpers.php';

/**
 * ACF schema in code + kmpr_field()/kmpr_contact() helpers (all front-end copy editable from the panel)
 */
require_once KMPR_INC . 'acf-fields.php';

/**
 * Feature modules: blog (course/series engine + fields), portfolio & services extra fields
 */
require_once KMPR_INC . 'acf-blog.php';
require_once KMPR_INC . 'blog-features.php';
require_once KMPR_INC . 'article-banners.php'; // [kmpr_banner] in-article banners + «بنر» editor button
require_once KMPR_INC . 'acf-portfolio-extra.php';
require_once KMPR_INC . 'acf-services-extra.php';
require_once KMPR_INC . 'games.php';
require_once KMPR_INC . 'arcade-monetize.php'; // Game Center demand test: «پاس آرکید», support, sponsors, advergame funnel

/**
 * Content sync bridge: applies queued kmpr_sync_* meta written by remote tools (MCP) via core APIs
 */
require_once KMPR_INC . 'content-sync.php';
require_once KMPR_INC . 'content-packs.php'; // content/packs/*.json → courses, lessons, covers (applied once per file hash)

/**
 * Performance: speculative prefetch on hover, speculative-request detection
 */
require_once KMPR_INC . 'performance.php';
require_once KMPR_INC . 'play-nudge.php';
require_once KMPR_INC . 'nav-icons.php';

/**
 * Technical SEO on top of Yoast: titles, descriptions, robots, OG image fallback, sitemaps, robots.txt, manifest
 */
require_once KMPR_INC . 'seo.php';

/**
 * Owner panel: mobile-first app-like control panel at /panel/ (REST kmpr/v1/panel/*, own PWA)
 */
require_once KMPR_INC . 'owner-panel.php';
