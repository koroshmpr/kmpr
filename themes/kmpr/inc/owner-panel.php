<?php
/**
 * Owner panel — a mobile-first, app-like control panel at /panel/ (front-end route, not wp-admin).
 *
 *  - inc/owner-panel/helpers.php  shared formatting + tiny daily counters (online matches)
 *  - inc/owner-panel/router.php   init-level router: /panel/* shell, manifest, service worker, nonce refresh
 *  - inc/owner-panel/rest.php     REST API kmpr/v1/panel/* (cookie auth + wp_rest nonce, manage_options)
 *  - inc/owner-panel/admin.php    admin-bar link, dashboard widget, speculation-rules exclusion
 *
 * Front-end: javascript/owner-panel/* (vanilla JS SPA) + tailwind/custom/owner-panel.css (self-contained),
 * built by `npm run production:panel` into public/js/owner-panel*.js and public/css/owner-panel.css.
 *
 * Everything reads/writes through the existing modules (games, arcade-monetize, project-requests,
 * play-nudge, llms-manager); no logic is duplicated here.
 *
 * @package KMPR
 */

if (!defined('ABSPATH')) {
    exit;
}

define('KMPR_PANEL_CAP', 'manage_options');
define('KMPR_PANEL_SLUG', 'panel');

require_once __DIR__ . '/owner-panel/helpers.php';
require_once __DIR__ . '/owner-panel/router.php';
require_once __DIR__ . '/owner-panel/rest.php';
require_once __DIR__ . '/owner-panel/admin.php';
