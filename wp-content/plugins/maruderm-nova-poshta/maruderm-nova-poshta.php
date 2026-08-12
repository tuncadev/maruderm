<?php
/**
 * Plugin Name: Maruderm Nova Poshta
 * Description: Nova Poshta (Nova Post) integration for WooCommerce checkout, TTN creation, and tracking webhooks.
 * Version: 0.1.0
 * Author: Maruderm
 */

if (! defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/includes/Env.php';
require_once __DIR__ . '/includes/Config.php';
require_once __DIR__ . '/includes/Api/TokenProvider.php';
require_once __DIR__ . '/includes/Api/Client.php';
require_once __DIR__ . '/includes/Service/DivisionService.php';
require_once __DIR__ . '/includes/Service/ShipmentService.php';
require_once __DIR__ . '/includes/Service/WebhookService.php';
require_once __DIR__ . '/includes/Woo/CheckoutService.php';
require_once __DIR__ . '/includes/Plugin.php';

MarudermNovaPoshta\Plugin::boot(__FILE__);
