<?php

namespace MarudermNovaPoshta\Woo;

use MarudermNovaPoshta\Config;
use MarudermNovaPoshta\Service\DivisionService;
use MarudermNovaPoshta\Service\ShipmentService;
use MarudermNovaPoshta\Service\WebhookService;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

class CheckoutService
{
    public function __construct(
        private Config $config,
        private DivisionService $divisionService,
        private ShipmentService $shipmentService,
        private WebhookService $webhookService
    ) {
    }

    public function registerHooks(): void
    {
        add_filter('woocommerce_checkout_fields', [$this, 'registerCheckoutFields']);
        add_action('woocommerce_after_checkout_validation', [$this, 'validateCheckout'], 10, 2);
        add_action('woocommerce_checkout_create_order', [$this, 'saveCheckoutMeta'], 10, 2);

        add_action('woocommerce_checkout_order_processed', [$this, 'createShipmentOnOrderPlaced'], 20, 3);
        add_filter('woocommerce_order_actions', [$this, 'registerOrderActions']);
        add_action('woocommerce_order_action_maruderm_nova_poshta_create_ttn', [$this, 'orderActionCreateTtn']);
        add_action('woocommerce_order_action_maruderm_nova_poshta_recreate_ttn', [$this, 'orderActionRecreateTtn']);
        add_action('woocommerce_order_action_maruderm_nova_poshta_cancel_ttn', [$this, 'orderActionCancelTtn']);
        add_action('woocommerce_order_action_maruderm_nova_poshta_track_now', [$this, 'orderActionTrackNow']);
        add_action('woocommerce_order_action_maruderm_nova_poshta_print_label', [$this, 'orderActionPrintLabel']);

        add_action('rest_api_init', [$this, 'registerRestRoutes']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueCheckoutScript']);
    }

    /**
     * @param array<string, string> $actions
     * @return array<string, string>
     */
    public function registerOrderActions(array $actions): array
    {
        $actions['maruderm_nova_poshta_create_ttn'] = __('Nova Poshta: Create TTN', 'maruderm-nova-poshta');
        $actions['maruderm_nova_poshta_recreate_ttn'] = __('Nova Poshta: Recreate TTN', 'maruderm-nova-poshta');
        $actions['maruderm_nova_poshta_cancel_ttn'] = __('Nova Poshta: Cancel TTN', 'maruderm-nova-poshta');
        $actions['maruderm_nova_poshta_track_now'] = __('Nova Poshta: Track now', 'maruderm-nova-poshta');
        $actions['maruderm_nova_poshta_print_label'] = __('Nova Poshta: Print label', 'maruderm-nova-poshta');

        return $actions;
    }

    /**
     * @param array<string, mixed> $fields
     * @return array<string, mixed>
     */
    public function registerCheckoutFields(array $fields): array
    {
        if (! isset($fields['billing']) || ! is_array($fields['billing'])) {
            return $fields;
        }

        $fields['billing']['nova_poshta_settlement_id'] = [
            'type' => 'select',
            'label' => __('Nova Poshta City', 'maruderm-nova-poshta'),
            'required' => false,
            'class' => ['form-row-wide'],
            'priority' => 240,
            'options' => ['' => __('Select city', 'maruderm-nova-poshta')],
        ];

        $fields['billing']['nova_poshta_area'] = [
            'type' => 'select',
            'label' => __('Nova Poshta Area', 'maruderm-nova-poshta'),
            'required' => false,
            'class' => ['form-row-wide'],
            'priority' => 239,
            'options' => ['' => __('Select area', 'maruderm-nova-poshta')],
        ];

        $fields['billing']['nova_poshta_division_id'] = [
            'type' => 'select',
            'label' => __('Nova Poshta Office / Postomat', 'maruderm-nova-poshta'),
            'required' => false,
            'class' => ['form-row-wide'],
            'priority' => 241,
            'options' => ['' => __('Select office', 'maruderm-nova-poshta')],
        ];

        return $fields;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function validateCheckout(array $data, \WP_Error $errors): void
    {
        if (! $this->isNovaPoshtaChosenInPostedData($data)) {
            return;
        }

        $country = isset($data['billing_country']) ? strtoupper((string) $data['billing_country']) : '';
        if ($country !== $this->config->getCountryCode()) {
            return;
        }

        $area = isset($data['nova_poshta_area']) ? trim((string) $data['nova_poshta_area']) : '';
        $settlementId = isset($data['nova_poshta_settlement_id']) ? (int) $data['nova_poshta_settlement_id'] : 0;
        $divisionId = isset($data['nova_poshta_division_id']) ? (int) $data['nova_poshta_division_id'] : 0;

        if ($area === '') {
            $errors->add('nova_poshta_area_missing', __('Please select Nova Poshta area.', 'maruderm-nova-poshta'));
            return;
        }

        if ($settlementId <= 0) {
            $errors->add('nova_poshta_settlement_missing', __('Please select Nova Poshta city.', 'maruderm-nova-poshta'));
            return;
        }

        if ($divisionId <= 0) {
            $errors->add('nova_poshta_division_missing', __('Please select Nova Poshta office/postomat.', 'maruderm-nova-poshta'));
            return;
        }

        if (! $this->divisionService->isDivisionInSettlement($divisionId, $settlementId)) {
            $errors->add('nova_poshta_division_invalid', __('Selected Nova Poshta office does not match selected city.', 'maruderm-nova-poshta'));
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    public function saveCheckoutMeta(\WC_Order $order, array $data): void
    {
        if (! $this->isNovaPoshtaChosenInPostedData($data)) {
            return;
        }

        $area = isset($data['nova_poshta_area']) ? trim((string) $data['nova_poshta_area']) : '';
        $settlementId = isset($data['nova_poshta_settlement_id']) ? (int) $data['nova_poshta_settlement_id'] : 0;
        $divisionId = isset($data['nova_poshta_division_id']) ? (int) $data['nova_poshta_division_id'] : 0;

        if ($settlementId <= 0 || $divisionId <= 0) {
            return;
        }

        $order->update_meta_data('_nova_poshta_country_code', $this->config->getCountryCode());
        $order->update_meta_data('_nova_poshta_area', $area);
        $order->update_meta_data('_nova_poshta_settlement_id', (string) $settlementId);
        $order->update_meta_data('_nova_poshta_division_id', (string) $divisionId);

        $divisions = $this->divisionService->getDivisionsBySettlement($settlementId);
        foreach ($divisions as $division) {
            if ((int) ($division['id'] ?? 0) !== $divisionId) {
                continue;
            }

            $order->update_meta_data('_nova_poshta_division_number', (string) ($division['number'] ?? ''));
            $order->update_meta_data('_nova_poshta_division_category', (string) ($division['category'] ?? ''));
            break;
        }

        $settlements = $this->divisionService->getSettlements();
        foreach ($settlements as $settlement) {
            if ((int) ($settlement['id'] ?? 0) !== $settlementId) {
                continue;
            }

            $order->update_meta_data('_nova_poshta_settlement_name', (string) ($settlement['name'] ?? ''));
            break;
        }
    }

    /**
     * @param mixed $postedData
     */
    public function createShipmentOnOrderPlaced(int $orderId, $postedData, \WC_Order $order): void
    {
        if (! $order instanceof \WC_Order) {
            return;
        }

        if (! $this->orderHasNovaPoshtaMethod($order)) {
            return;
        }

        $countryCode = strtoupper((string) $order->get_meta('_nova_poshta_country_code'));
        if ($countryCode !== $this->config->getCountryCode()) {
            return;
        }

        $this->shipmentService->maybeCreateShipmentForOrder($orderId);
    }

    public function orderActionCreateTtn(\WC_Order $order): void
    {
        $this->shipmentService->maybeCreateShipmentForOrder($order->get_id());
    }

    public function orderActionRecreateTtn(\WC_Order $order): void
    {
        $this->shipmentService->recreateShipmentForOrder($order->get_id());
    }

    public function orderActionCancelTtn(\WC_Order $order): void
    {
        $this->shipmentService->cancelShipmentForOrder($order->get_id());
    }

    public function orderActionTrackNow(\WC_Order $order): void
    {
        $this->shipmentService->syncTrackingForOrder($order->get_id());
    }

    public function orderActionPrintLabel(\WC_Order $order): void
    {
        $this->shipmentService->requestPrintForOrder($order->get_id());
    }

    public function enqueueCheckoutScript(): void
    {
        if (! function_exists('is_checkout') || ! is_checkout()) {
            return;
        }

        wp_enqueue_script(
            'maruderm-nova-poshta-checkout',
            plugins_url('../../assets/checkout.js', __FILE__),
            ['jquery'],
            '0.1.0',
            true
        );

        wp_localize_script('maruderm-nova-poshta-checkout', 'MarudermNovaPoshta', [
            'countryCode' => $this->config->getCountryCode(),
            'shippingMethodId' => 'maruderm_nova_poshta',
            'areasEndpoint' => rest_url('maruderm-nova-poshta/v1/areas'),
            'settlementsEndpoint' => rest_url('maruderm-nova-poshta/v1/settlements'),
            'divisionsEndpoint' => rest_url('maruderm-nova-poshta/v1/divisions'),
            'strings' => [
                'selectArea' => __('Select area', 'maruderm-nova-poshta'),
                'selectCity' => __('Select city', 'maruderm-nova-poshta'),
                'selectOffice' => __('Select office', 'maruderm-nova-poshta'),
            ],
        ]);
    }

    public function registerRestRoutes(): void
    {
        register_rest_route('maruderm-nova-poshta/v1', '/areas', [
            'methods' => WP_REST_Server::READABLE,
            'permission_callback' => '__return_true',
            'callback' => [$this, 'getAreas'],
        ]);

        register_rest_route('maruderm-nova-poshta/v1', '/settlements', [
            'methods' => WP_REST_Server::READABLE,
            'permission_callback' => '__return_true',
            'callback' => [$this, 'getSettlements'],
            'args' => [
                'area' => [
                    'required' => false,
                    'type' => 'string',
                ],
            ],
        ]);

        register_rest_route('maruderm-nova-poshta/v1', '/divisions', [
            'methods' => WP_REST_Server::READABLE,
            'permission_callback' => '__return_true',
            'callback' => [$this, 'getDivisions'],
            'args' => [
                'settlement_id' => [
                    'required' => true,
                    'type' => 'integer',
                ],
            ],
        ]);

        register_rest_route('maruderm-nova-poshta/v1', '/webhook/tracking', [
            'methods' => WP_REST_Server::CREATABLE,
            'permission_callback' => '__return_true',
            'callback' => [$this, 'handleTrackingWebhook'],
        ]);
    }

    public function getSettlements(WP_REST_Request $request): WP_REST_Response
    {
        $area = trim((string) $request->get_param('area'));
        $settlements = $area === ''
            ? $this->divisionService->getSettlements()
            : $this->divisionService->getSettlementsByArea($area);
        if ($settlements === []) {
            return new WP_REST_Response([
                'items' => [],
                'message' => 'Nova Poshta settlements are not available. Check API credentials.',
            ], 503);
        }

        return new WP_REST_Response([
            'items' => $settlements,
        ]);
    }

    public function getAreas(WP_REST_Request $request): WP_REST_Response
    {
        $areas = $this->divisionService->getAreas();
        if ($areas === []) {
            return new WP_REST_Response([
                'items' => [],
                'message' => 'Nova Poshta areas are not available. Check API credentials.',
            ], 503);
        }

        return new WP_REST_Response([
            'items' => $areas,
        ]);
    }

    public function getDivisions(WP_REST_Request $request): WP_REST_Response
    {
        $settlementId = (int) $request->get_param('settlement_id');
        $divisions = $this->divisionService->getDivisionsBySettlement($settlementId);

        return new WP_REST_Response([
            'items' => $divisions,
        ]);
    }

    public function handleTrackingWebhook(WP_REST_Request $request): WP_REST_Response
    {
        $headerName = $this->config->getWebhookSecretHeaderName();
        $expectedToken = $this->config->getWebhookSecretToken();

        if ($expectedToken === '') {
            return new WP_REST_Response(['message' => 'Webhook secret is not configured.'], 503);
        }

        $headerValue = (string) $request->get_header($headerName);
        if ($headerValue === '' || ! hash_equals($expectedToken, $headerValue)) {
            return new WP_REST_Response(['message' => 'Forbidden'], 403);
        }

        $payload = $request->get_json_params();
        if (! is_array($payload)) {
            $payload = ['raw' => (string) $request->get_body()];
        }

        $this->webhookService->processPayload($payload);

        return new WP_REST_Response(['ok' => true]);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function isNovaPoshtaChosenInPostedData(array $data): bool
    {
        $shippingMethods = $data['shipping_method'] ?? [];
        if (is_string($shippingMethods)) {
            $shippingMethods = [$shippingMethods];
        }

        if (! is_array($shippingMethods)) {
            return false;
        }

        foreach ($shippingMethods as $method) {
            if (is_string($method) && str_contains($method, 'maruderm_nova_poshta')) {
                return true;
            }
        }

        return false;
    }

    private function orderHasNovaPoshtaMethod(\WC_Order $order): bool
    {
        foreach ($order->get_shipping_methods() as $shippingItem) {
            if (! $shippingItem instanceof \WC_Order_Item_Shipping) {
                continue;
            }

            $methodId = (string) $shippingItem->get_method_id();
            if (str_contains($methodId, 'maruderm_nova_poshta')) {
                return true;
            }
        }

        return false;
    }
}
