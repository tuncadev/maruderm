<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Modules;

use kirillbdev\WCUkrShipping\Component\Automation\Context;
use kirillbdev\WCUkrShipping\Helpers\SmartyParcelHelper;
use kirillbdev\WCUkrShipping\Http\Resources\OrderResource;
use kirillbdev\WCUkrShipping\Services\AutomationService;
use kirillbdev\WCUSCore\Contracts\ModuleInterface;
use kirillbdev\WCUSCore\Facades\DB;
use WP_REST_Request;
use WP_REST_Response;

class WcusLegacyCompatibility implements ModuleInterface
{
    /**
     * Allowed cursor request columns mapped to their wc_get_orders orderby key.
     */
    private const ORDERS_ALLOWED_FROM_COLUMNS = [
        'date_created',
        'date_modified',
    ];

    private const ORDERS_DEFAULT_LIMIT = 50;
    private const ORDERS_MAX_LIMIT = 200;

    /**
     * Allowed clock skew (seconds) between the platform and this store.
     */
    private const SIGNATURE_TOLERANCE = 300;

    private AutomationService $automationService;

    public function __construct(AutomationService $automationService)
    {
        $this->automationService = $automationService;
    }

    public function init(): void
    {
        add_action('rest_api_init', [$this, 'registerTrackingCallback']);
        add_action('rest_api_init', [$this, 'registerOrdersSyncRoute']);
    }

    public function registerTrackingCallback(): void
    {
        // todo: move to separated component
        register_rest_route('wc-ukr-shipping/v1', 'tracking', [
            'methods' => 'POST',
            'callback' => [$this, 'trackingHandler'],
            'permission_callback' => function (WP_REST_Request $request) {
                return true;
            }
        ]);
    }

    public function trackingHandler(WP_REST_Request $request): WP_REST_Response
    {
        $secret = wc_ukr_shipping_get_option('wcus_smartyparcel_api_key');
        if (empty($secret)) {
            return new WP_REST_Response(null, 404);
        }

        $requestSignature = $request->get_header('WCUS-Signature');
        if (empty($requestSignature)) {
            return new WP_REST_Response([
                'success' => false,
                'error' => 'Bad request format',
            ], 400);
        }

        $json = json_decode($request->get_body() ?? '', true);
        if (json_last_error()) {
            return new WP_REST_Response([
                'success' => false,
                'error' => 'Bad request format',
            ], 400);
        }

        // Compare signatures
        $signature = hash('sha256', $secret . base64_encode(json_encode($json, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)));
        if ($signature !== $requestSignature) {
            return new WP_REST_Response([
                'success' => false,
                'error' => 'Request not valid',
            ], 403);
        }

        $label = DB::table(DB::prefixedTable('wc_ukr_shipping_labels'))
            ->where('tracking_number', $json['tracking_number'])
            ->first();

        if ($label === null) {
            return new WP_REST_Response([
                'success' => false,
                'error' => 'Label not found',
            ]);
        }

        $label = (array)$label;
        try {
            if ($label['carrier_status_code'] !== $json['carrier_status']) {
                global $wpdb;
                $wpdb->update(
                    "{$wpdb->prefix}wc_ukr_shipping_labels",
                    [
                        'tracking_status' => $json['status'],
                        'tracking_sub_status' => $json['sub_status'],
                        'carrier_status' => $json['carrier_status_description'],
                        'carrier_status_code' => $json['carrier_status'],
                        'updated_at' => date('Y-m-d H:i:s'),
                    ],
                    [ 'tracking_number' => $json['tracking_number'] ],
                    [ '%s', '%s', '%s', '%s', '%s' ],
                    [ '%s' ]
                );

                $this->automationService->executeEvent(
                    AutomationService::EVENT_SP_CARRIER_STATUS_CHANGED,
                    new Context(
                        AutomationService::EVENT_SP_CARRIER_STATUS_CHANGED,
                        wc_get_order((int)$label['order_id']),
                        [
                            'carrier_slug' => $label['carrier_slug'] ?? '',
                            'tracking_number' => $label['tracking_number'],
                            'carrier_status_code' => $json['carrier_status'],
                            'carrier_status' => $json['carrier_status_description'],
                        ]
                    )
                );

                $this->automationService->executeEvent(
                    AutomationService::EVENT_SP_TRACKING_STATUS_CHANGED,
                    new Context(
                        AutomationService::EVENT_SP_TRACKING_STATUS_CHANGED,
                        wc_get_order((int)$label['order_id']),
                        [
                            'carrier_slug' => $label['carrier_slug'] ?? '',
                            'tracking_number' => $label['tracking_number'],
                            'cloud_status' => $json['status'],
                            'cloud_sub_status' => $json['sub_status'] ?? null,
                            'carrier_status_code' => $json['carrier_status'],
                            'carrier_status' => $json['carrier_status_description'],
                        ]
                    )
                );
            }
        } catch (\Throwable $e) {
            return new WP_REST_Response([
                'success' => false,
                'error' => 'Internal server error',
            ], 500);
        }

        return new WP_REST_Response([
            'success' => true,
        ]);
    }

    /**
     * Orders sync endpoint consumed by the SmartyParcel platform.
     *
     * The platform polls this endpoint periodically and walks the store with a
     * keyset cursor (from_column / from_value). Matches the platform-side route
     * `/wp-json/smartyparcel/v1/orders`.
     *
     * todo: move to a dedicated controller/component during refactor.
     */
    public function registerOrdersSyncRoute(): void
    {
        register_rest_route('smartyparcel/v1', 'orders', [
            'methods' => 'GET',
            'callback' => [$this, 'ordersHandler'],
            'permission_callback' => function (WP_REST_Request $request) {
                return true;
            },
        ]);
    }

    public function ordersHandler(WP_REST_Request $request): WP_REST_Response
    {
        $response = $this->validateSmartyParcelRequest($request);
        if ($response !== null) {
            return $response;
        }

        // Raw values are used for signing; clamping/normalization happens after verification.
        $fromColumn = (string)$request->get_param('from_column');
        $fromValue = (string)$request->get_param('from_value');
        $rawLimit = (string)$request->get_param('limit');

        $signatureValid = $this->validateSignature(
            $request,
            'GET',
            '/v1/orders',
            [
                'from_column' => $fromColumn,
                'from_value' => $fromValue,
                'limit' => $rawLimit,
            ]
        );

        if (!$signatureValid) {
            return new WP_REST_Response(['error' => 'Forbidden'], 403);
        }

        // --- Resolve the cursor and fetch a page of orders. ---
        if (!in_array($fromColumn, self::ORDERS_ALLOWED_FROM_COLUMNS, true)) {
            return new WP_REST_Response([
                'error' => 'Bad request',
            ], 400);
        }

        $limit = (int)$rawLimit;
        if ($limit <= 0) {
            $limit = self::ORDERS_DEFAULT_LIMIT;
        }
        $limit = min($limit, self::ORDERS_MAX_LIMIT);

        try {
            $orders = $this->fetchOrdersPage($fromColumn, $fromValue, $limit);
        } catch (\Throwable $e) {
            return new WP_REST_Response(['error' => 'Internal server error'], 500);
        }

        return new WP_REST_Response(array_map(function (\WC_Order $order) {
            return (new OrderResource($order))->toArray();
        }, $orders));
    }

    /**
     * Fetch a page of WooCommerce orders for the given keyset cursor.
     *
     * Works on both legacy (post-based) and HPOS storage by delegating to
     * wc_get_orders(). The strict-greater-than cursor on `id` is applied through
     * the relevant query-clause filter for the active storage, since
     * wc_get_orders() has no native id-range argument.
     *
     * @param string $cursor One of self::ORDERS_CURSOR_COLUMNS values ('id'|'modified').
     * @return \WC_Order[]
     */
    private function fetchOrdersPage(string $cursor, string $fromValue, int $limit): array
    {
        $args = [
            'type' => 'shop_order',
            'status' => array_keys(wc_get_order_statuses()),
            'limit' => $limit,
            'order' => 'ASC',
            'orderby' => 'date',
        ];

        // wc_get_orders understands the `>` operator on date fields (GMT timestamp).
        $fromTs = is_numeric($fromValue) ? (int)$fromValue : (int)strtotime($fromValue);
        if ($fromTs > 0) {
            $args[$cursor] = '>' . $fromTs;
        }

        return wc_get_orders($args);
    }

    private function validateSmartyParcelRequest(WP_REST_Request $request): ?WP_REST_Response
    {
        $secret = get_option(WCUS_OPTION_SMARTY_PARCEL_API_KEY);
        if (!SmartyParcelHelper::isConnected() || empty($secret)) {
            return new WP_REST_Response(null, 404);
        }

        $requestSignature = (string)$request->get_header('X-SmartyParcel-Signature');
        $timestamp = (int)$request->get_header('X-SmartyParcel-Timestamp');

        if ($requestSignature === '' || $timestamp <= 0) {
            return new WP_REST_Response(['error' => 'Bad request'], 400);
        }

        // Replay protection: reject requests with a stale/forward-dated timestamp.
        if (abs(time() - $timestamp) > self::SIGNATURE_TOLERANCE) {
            return new WP_REST_Response(['error' => 'Forbidden'], 403);
        }

        return null;
    }

    private function validateSignature(
        WP_REST_Request $request,
        string $method,
        string $path,
        array $params
    ): bool {
        $secret = get_option(WCUS_OPTION_SMARTY_PARCEL_API_KEY);
        $requestSignature = (string)$request->get_header('X-SmartyParcel-Signature');
        $timestamp = (int)$request->get_header('X-SmartyParcel-Timestamp');

        $canonical = implode('|', [
            strtoupper($method),
            $path,
            http_build_query($params),
            (string)$timestamp,
        ]);

        $expectedSignature = hash_hmac('sha256', $canonical, $secret);

        return hash_equals($expectedSignature, $requestSignature);
    }
}
