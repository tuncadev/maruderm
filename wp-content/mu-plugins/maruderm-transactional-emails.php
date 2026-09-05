<?php
/**
 * Authenticated transactional email delivery and internal order notifications.
 *
 * @package Maruderm
 */

if (! defined('ABSPATH')) {
    exit;
}

$maruderm_email_root = __DIR__ . '/maruderm-transactional-emails';

require_once $maruderm_email_root . '/class-smtp-configurator.php';
require_once $maruderm_email_root . '/class-order-email-renderer.php';
require_once $maruderm_email_root . '/class-customer-order-email-renderer.php';
require_once $maruderm_email_root . '/class-woocommerce-order-notifier.php';
require_once $maruderm_email_root . '/class-keycrm-order-notification-webhook.php';
require_once $maruderm_email_root . '/class-keycrm-ttn-status-synchronizer.php';

$maruderm_email_renderer = new Maruderm_Order_Email_Renderer();
$maruderm_customer_email_renderer = new Maruderm_Customer_Order_Email_Renderer();

(new Maruderm_SMTP_Configurator())->register();
(new Maruderm_WooCommerce_Order_Notifier($maruderm_email_renderer, $maruderm_customer_email_renderer))->register();
(new Maruderm_KeyCRM_Order_Notification_Webhook($maruderm_email_renderer))->register();
(new Maruderm_KeyCRM_TTN_Status_Synchronizer())->register();
