<?php

namespace Maruderm\Analytics;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class AnalyticsRestController
{
    private const EVENT_TYPES = [
        'page_view',
        'engagement',
        'scroll_depth',
        'product_view',
        'category_view',
        'add_to_cart',
        'checkout_started',
        'checkout_step',
        'checkout_completed',
    ];

    public function __construct(private readonly AnalyticsRepository $repository)
    {
    }

    public function registerRoutes(): void
    {
        register_rest_route('maruderm/v1', '/analytics/events', [
            'methods' => 'POST',
            'permission_callback' => '__return_true',
            'callback' => [$this, 'record'],
        ]);

        register_rest_route('maruderm/v1', '/analytics/products/(?P<id>\d+)/views', [
            'methods' => 'GET',
            'permission_callback' => '__return_true',
            'callback' => [$this, 'productViews'],
        ]);
    }

    public function record(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $event = $request->get_json_params();
        if (! is_array($event)) {
            return new WP_Error('invalid_event', 'Invalid analytics event.', ['status' => 400]);
        }

        $sessionId = (string) ($event['sessionId'] ?? '');
        $eventType = sanitize_key((string) ($event['eventType'] ?? ''));
        if (! preg_match('/^[a-f0-9-]{20,64}$/i', $sessionId) || ! in_array($eventType, self::EVENT_TYPES, true)) {
            return new WP_Error('invalid_event', 'Invalid analytics event.', ['status' => 422]);
        }

        $event['eventType'] = $eventType;
        $viewCount = $this->repository->record($event, rest_sanitize_boolean($event['loggedIn'] ?? false));

        return new WP_REST_Response(['recorded' => true, 'viewCount' => $viewCount], 201);
    }

    public function productViews(WP_REST_Request $request): WP_REST_Response
    {
        $productId = absint($request['id']);
        $product = wc_get_product($productId);

        if (! $product) {
            return new WP_REST_Response(['error' => 'Product not found.'], 404);
        }

        return new WP_REST_Response(['viewCount' => $this->repository->productViews($productId)]);
    }
}
