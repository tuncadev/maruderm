<?php
/**
 * Privacy-first first-party analytics for the headless Maruderm storefront.
 *
 * @package Maruderm
 */

if (! defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/maruderm-private-analytics/class-analytics-repository.php';
require_once __DIR__ . '/maruderm-private-analytics/class-analytics-exclusion-policy.php';
require_once __DIR__ . '/maruderm-private-analytics/class-analytics-rest-controller.php';
require_once __DIR__ . '/maruderm-private-analytics/class-analytics-admin-page.php';
require_once __DIR__ . '/maruderm-private-analytics/class-analytics-plugin.php';

(new \Maruderm\Analytics\AnalyticsPlugin())->boot();
