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
 * LLMs & AI Engine: Automated llms.txt & llms-full.txt generator with WP admin dashboard
 */
require_once KMPR_INC . 'llms-manager.php';
