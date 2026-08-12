<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Api;

use kirillbdev\WCUkrShipping\Exceptions\SmartyParcel\SmartyParcelErrorException;
use kirillbdev\WCUkrShipping\Exceptions\SmartyParcel\StoreNotConnectedException;

final class SmartyParcelWPApi
{
    private const API_URL = 'https://wp-api.smartyparcel.com';

    private const ROUTE_METHOD_MAP = [
        '/v1/account' => 'GET',
        '/v1/dashboard/overview' => 'GET',
        '/v1/carriers' => 'GET',
        '/v1/billing/plans' => 'GET',
        '/v1/locator/cities' => 'GET',
        '/v1/locator/pudo-points' => 'GET',
        '/v1/batches' => 'POST',
        '/v1/batches/:id' => 'GET',
        '/v1/downloads/l5/:uuid.pdf' => 'GET',
        '/v1/downloads/b6/:uuid.pdf' => 'GET',
        '/v1/manifest' => 'GET',
        '/v1/embedded/authx' => 'POST',
        '/v1/lookup/company' => 'GET',
        '/v1/rates/estimate' => 'POST',
        '/v1/addresses' => 'GET',
        '/v1/orders' => 'POST',
        '/v1/checkout/link' => 'POST',
    ];

    private const PUBLIC_ROUTES = [
        '/v1/manifest',
    ];

    public function connectApplication(string $accessToken): array
    {
        $response = wp_remote_post(self::API_URL . '/v1/app/connect', [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $accessToken,
            ],
            'timeout' => 5,
            'body' => json_encode([
                'store_url' => get_site_url(),
            ])
        ]);

        return $this->processResponse($response);
    }

    public function sendRequest(
        string $route,
        ?array $payload = null,
        array $query = [],
        array $pathParameters = []
    ): array {
        if ( ! isset(self::ROUTE_METHOD_MAP[$route])) {
            throw new \Exception('Route not found: ' . $route);
        }

        $this->mustCheckConnection($route);

        $method = self::ROUTE_METHOD_MAP[$route];
        $args = [
            'method' => $method,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'SP-API-Key' => get_option(WCUS_OPTION_SMARTY_PARCEL_API_KEY),
                'SP-Site-Url' => get_site_url(),
            ],
            'timeout' => 10,
        ];
        if ($payload !== null && $method === 'POST') {
            $args['body'] = json_encode($payload);
        }

        // Try to replace path placeholders
        foreach ($pathParameters as $name => $value) {
            $route = str_replace(":$name", $value, $route);
        }

        $url = self::API_URL . $route;
        if (count($query) > 0) {
            $url .= '?' . http_build_query($query);
        }

        return $this->processResponse(wp_remote_request($url, $args));
    }

    private function processResponse($response): array
    {
        if (is_wp_error($response)) {
            throw new \Exception($response->get_error_message());
        }

        $code = (int)wp_remote_retrieve_response_code($response);
        if (empty($response['body'])) {
            $payload = [];
        } else {
            $result = json_decode($response['body'], true);
            if (json_last_error()) {
                throw new \Exception("API error: malformed response");
            }
            $payload = $result;
        }

        if (in_array($code, [200, 201])) {
            return $payload;
        }

        throw new SmartyParcelErrorException(
            $payload['error']['code'] ?? 0,
            $payload['error']['message'] ?? 'Unknown error',
            $payload['error']['details'] ?? []
        );
    }

    private function mustCheckConnection(string $route): void
    {
        if ( !in_array($route, self::PUBLIC_ROUTES) && !get_option(WCUS_OPTION_SMARTY_PARCEL_API_KEY)) {
            throw new StoreNotConnectedException();
        }
    }
}
