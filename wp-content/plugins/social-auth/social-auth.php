<?php
/**
 * Plugin Name: Social Auth
 * Description: OAuth login with Google, Facebook, and Apple.
 * Version: 1.0.0
 * Author: Maruderm
 */

if (! defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/includes/Contracts/ProviderInterface.php';
require_once __DIR__ . '/includes/Providers/AbstractProvider.php';
require_once __DIR__ . '/includes/Providers/GoogleProvider.php';
require_once __DIR__ . '/includes/Providers/FacebookProvider.php';
require_once __DIR__ . '/includes/Providers/AppleProvider.php';
require_once __DIR__ . '/includes/Config.php';
require_once __DIR__ . '/includes/AuthCore.php';
require_once __DIR__ . '/includes/ProviderFactory.php';
require_once __DIR__ . '/includes/Admin/SettingsPage.php';
require_once __DIR__ . '/includes/Plugin.php';

SocialAuth\Plugin::boot(__FILE__);
