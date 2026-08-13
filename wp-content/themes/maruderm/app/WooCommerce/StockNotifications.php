<?php

declare(strict_types=1);

namespace Maruderm\WooCommerce;

use Maruderm\Kernel\Loadable;
use Maruderm\Kernel\Registrable;

if (!defined('ABSPATH')) {
    exit();
}

/**
 * Connects Maruderm interfaces to WooCommerce customer stock notifications.
 */
final class StockNotifications implements Registrable
{
    use Loadable;

    private const AJAX_ACTION = 'maruderm_toggle_stock_notification';
    private const NONCE_ACTION = 'maruderm_stock_notifications';
    private const ACCOUNT_ENDPOINT = 'stock-notifications';

    private StockNotificationService $service;
    private StockNotificationRenderer $renderer;
    private StockNotificationMailer $mailer;

    public function __construct(
        ?StockNotificationService $service = null,
        ?StockNotificationRenderer $renderer = null,
        ?StockNotificationMailer $mailer = null
    ) {
        $this->service = $service ?? new StockNotificationService();
        $this->renderer = $renderer ?? new StockNotificationRenderer($this->service);
        $this->mailer = $mailer ?? new StockNotificationMailer($this->service);
    }

    public function register(): void
    {
        add_action('woocommerce_single_product_summary', [$this, 'renderSingleButton'], 31);
        add_action('wp_enqueue_scripts', [$this, 'exposeClientSettings'], 30);
        add_action('wp_ajax_' . self::AJAX_ACTION, [$this, 'toggle']);
        add_action('wp_ajax_nopriv_' . self::AJAX_ACTION, [$this, 'requireLogin']);
        add_action('woocommerce_account_' . self::ACCOUNT_ENDPOINT . '_endpoint', [$this, 'renderAccountEndpoint']);
        add_action('woocommerce_product_set_stock_status', [$this->mailer, 'schedule'], 100, 3);
        add_action('woocommerce_variation_set_stock_status', [$this->mailer, 'schedule'], 100, 3);
        add_action(StockNotificationMailer::PROCESS_ACTION, [$this->mailer, 'sendForProduct']);

        add_filter('woocommerce_get_query_vars', [$this, 'registerAccountQueryVar']);
        add_filter('woocommerce_account_menu_items', [$this, 'addAccountMenuItem']);
        add_filter('woocommerce_endpoint_' . self::ACCOUNT_ENDPOINT . '_title', [$this, 'accountEndpointTitle']);
    }

    public function renderSingleButton(): void
    {
        global $product;

        if (!$product instanceof \WC_Product) {
            return;
        }

        if ($product->is_in_stock() && !$product->is_type('variable')) {
            return;
        }

        echo $this->renderer->singleButton($product); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Renderer escapes dynamic values and owns trusted SVG markup.
    }

    /** @param array<string, string> $query_vars */
    public function registerAccountQueryVar(array $query_vars): array
    {
        $query_vars[self::ACCOUNT_ENDPOINT] = self::ACCOUNT_ENDPOINT;

        return $query_vars;
    }

    /** @param array<string, string> $items */
    public function addAccountMenuItem(array $items): array
    {
        $result = [];

        foreach ($items as $endpoint => $label) {
            if ($endpoint === 'customer-logout') {
                $result[self::ACCOUNT_ENDPOINT] = 'Сповіщення про наявність';
            }

            $result[$endpoint] = $label;
        }

        if (!isset($result[self::ACCOUNT_ENDPOINT])) {
            $result[self::ACCOUNT_ENDPOINT] = 'Сповіщення про наявність';
        }

        return $result;
    }

    public function accountEndpointTitle(): string
    {
        return 'Сповіщення про наявність';
    }

    public function renderAccountEndpoint(): void
    {
        $user = wp_get_current_user();
        $notifications = $this->service->subscriptionsForUser($user->ID);

        echo $this->renderer->accountPanel($notifications, $user->user_email); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Renderer escapes dynamic values and owns trusted SVG markup.
    }

    public function exposeClientSettings(): void
    {
        if (!wp_script_is('maruderm-globals', 'enqueued')) {
            return;
        }

        $active_product_ids = [];

        if (is_user_logged_in()) {
            foreach ($this->service->subscriptionsForUser(get_current_user_id()) as $product) {
                $active_product_ids[] = $product->get_id();
            }
        }

        $settings = [
            'action' => self::AJAX_ACTION,
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce(self::NONCE_ACTION),
            'authenticated' => is_user_logged_in(),
            'loginUrl' => add_query_arg('redirect_to', $this->currentUrl(), wc_get_page_permalink('myaccount')),
            'activeProductIds' => array_values(array_unique(array_map('absint', $active_product_ids))),
        ];

        wp_add_inline_script(
            'maruderm-globals',
            'window.marudermStockNotifications = ' . wp_json_encode($settings) . ';',
            'before'
        );
    }

    public function toggle(): void
    {
        check_ajax_referer(self::NONCE_ACTION, 'nonce');

        if (!is_user_logged_in()) {
            $this->requireLogin();
        }

        $product_id = isset($_POST['product_id']) ? absint(wp_unslash($_POST['product_id'])) : 0;
        $active = $this->service->toggle($product_id, get_current_user_id());

        if (is_wp_error($active)) {
            wp_send_json_error(['message' => $active->get_error_message()], 422);
        }

        wp_send_json_success([
            'productId' => $product_id,
            'active' => $active,
            'message' => $active
                ? 'Сповіщення про наявність увімкнено.'
                : 'Сповіщення про наявність вимкнено.',
        ]);
    }

    public function requireLogin(): void
    {
        wp_send_json_error([
            'message' => 'Увійди в акаунт, щоб увімкнути сповіщення.',
            'loginUrl' => add_query_arg('redirect_to', $this->currentUrl(), wc_get_page_permalink('myaccount')),
        ], 401);
    }

    private function currentUrl(): string
    {
        $request_uri = isset($_SERVER['REQUEST_URI'])
            ? esc_url_raw(wp_unslash((string) $_SERVER['REQUEST_URI']))
            : '/';

        return home_url($request_uri);
    }
}
