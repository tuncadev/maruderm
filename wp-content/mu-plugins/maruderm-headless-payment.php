<?php
/**
 * Headless payment support: exposes the BACS bank account publicly (the same
 * data WooCommerce's own thank-you page/emails already show every customer)
 * and redirects HUTKO's hosted-page return flow to the Next.js frontend
 * instead of WooCommerce's classic checkout/order-received page.
 *
 * @package Maruderm
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('rest_api_init', 'maruderm_register_payment_routes');
add_filter('woocommerce_get_return_url', 'maruderm_hutko_return_url', 10, 2);

function maruderm_register_payment_routes(): void
{
    register_rest_route('maruderm/v1', '/payment/bacs-account', [
        'methods' => 'GET',
        'permission_callback' => '__return_true',
        'callback' => 'maruderm_handle_get_bacs_account',
    ]);
}

function maruderm_handle_get_bacs_account(): WP_REST_Response
{
    $accounts = get_option('woocommerce_bacs_accounts');
    $account = is_array($accounts) ? reset($accounts) : false;

    if (! is_array($account)) {
        return new WP_REST_Response(['error' => 'Банківський переказ тимчасово недоступний.'], 404);
    }

    return new WP_REST_Response([
        'accountName' => $account['account_name'] ?? '',
        'bankName' => $account['bank_name'] ?? '',
        'iban' => $account['iban'] ?? '',
        'accountNumber' => $account['account_number'] ?? '',
    ]);
}

function maruderm_hutko_return_url(string $return_url, $order): string
{
    if (! $order instanceof WC_Order || $order->get_payment_method() !== 'hutko') {
        return $return_url;
    }

    $frontend_url = maruderm_headless_frontend_url();

    if ($frontend_url === '') {
        return $return_url;
    }

    $total = number_format((float) $order->get_total(), 2, ',', '') . ' ₴';

    return $frontend_url . '/checkout/success?' . http_build_query([
        'orderNumber' => $order->get_order_number(),
        'total' => $total,
    ]);
}

function maruderm_headless_frontend_url(): string
{
    $value = getenv('HEADLESS_FRONTEND_URL');

    if ($value === false) {
        foreach ([$_ENV, $_SERVER] as $source) {
            if (array_key_exists('HEADLESS_FRONTEND_URL', $source) && is_scalar($source['HEADLESS_FRONTEND_URL'])) {
                $value = (string) $source['HEADLESS_FRONTEND_URL'];
                break;
            }
        }
    }

    if ($value === false || $value === '') {
        $env_path = dirname(__DIR__, 2) . '/.env';
        $lines = is_readable($env_path) ? file($env_path, FILE_IGNORE_NEW_LINES) : false;

        if (is_array($lines)) {
            foreach ($lines as $line) {
                if (preg_match('/^\s*HEADLESS_FRONTEND_URL\s*=\s*(.*)$/', $line, $matches)) {
                    $value = trim($matches[1], " \t\"'");
                    break;
                }
            }
        }
    }

    return is_string($value) ? rtrim($value, '/') : '';
}
