<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Component\Carriers\NovaPost\Shipping;

use kirillbdev\WCUkrShipping\Contracts\Shipping\PUDOProviderInterface;
use kirillbdev\WCUkrShipping\Dto\Shipping\City;
use kirillbdev\WCUkrShipping\Dto\Shipping\PUDO;
use kirillbdev\WCUkrShipping\Dto\Shipping\SearchPUDORequestDTO;
use kirillbdev\WCUkrShipping\Http\WpHttpClient;

/**
 * todo Remove by SmartyParcel Locator in future
 */
class NovaPostPUDOProvider implements PUDOProviderInterface
{
    private const API_URL = 'https://api.novapost.com/v.1.0';

    private WpHttpClient $httpClient;

    public function __construct(WpHttpClient $httpClient) {
        $this->httpClient = $httpClient;
    }

    public function searchCitiesByQuery(string $query): array
    {
        throw new \RuntimeException('Not implemented');
    }

    public function searchCityById(string $id): ?City
    {
        throw new \RuntimeException('Not implemented');
    }

    public function searchPUDOByQuery(SearchPUDORequestDTO $request): array
    {
        $apiKey = wc_ukr_shipping_get_option('wcus_nova_post_api_key');
        if (!$apiKey) {
            return [];
        }

        // Step 1: JWT
        $response = $this->httpClient->get(
            sprintf('%s/clients/authorization?apiKey=%s', self::API_URL, rawurlencode($apiKey))
        );

        if ($response === null) {
            return [];
        }

        $data = json_decode($response, true);
        if (json_last_error() || !isset($data['jwt'])) {
            return [];
        }

        // Step 2: get warehouses
        $url = sprintf(
            '%s/divisions?countryCodes[]=%s&limit=100&page=1',
            self::API_URL,
            rawurlencode($request->cityId)
        );
        if (!empty($request->query)) {
            $url .= '&textSearch=' . rawurlencode($request->query);
        }
        $response = $this->httpClient->get($url, [
            'Authorization' => $data['jwt']
        ]);

        if ($response === null) {
            return [];
        }

        $data = json_decode($response, true);
        if (json_last_error()) {
            return [];
        }

        $result = array_map(function (array $warehouse) use ($request) {
            return new PUDO(
                (string)$warehouse['id'],
                $request->cityId,
                $warehouse['address'],
                $warehouse['address'],
                PUDO::PUDO_TYPE_WAREHOUSE,
                [
                    'settlementName' => $warehouse['settlement']['name'],
                    'settlementRegion' => $warehouse['settlement']['region']['name'],
                ]
            );
        }, $data['items'] ?? []);

        return [
            'data' => $result,
            'total' => count($result),
        ];
    }

    public function searchPUDOById(string $id): ?PUDO
    {
        throw new \RuntimeException('Not implemented');
    }
}
