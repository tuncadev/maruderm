<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Component\Carriers\RozetkaDelivery\Shipping;

use kirillbdev\WCUkrShipping\Contracts\Shipping\PUDOProviderInterface;
use kirillbdev\WCUkrShipping\Dto\Shipping\City;
use kirillbdev\WCUkrShipping\Dto\Shipping\PUDO;
use kirillbdev\WCUkrShipping\Dto\Shipping\SearchPUDORequestDTO;
use kirillbdev\WCUkrShipping\Http\WpHttpClient;

/**
 * todo Remove by SmartyParcel Locator in future
 */
class RozetkaDeliveryPUDOProvider implements PUDOProviderInterface
{
    private const API_URL = 'https://rz-delivery.rozetka.ua/api';

    private WpHttpClient $httpClient;

    public function __construct(WpHttpClient $httpClient) {
        $this->httpClient = $httpClient;
    }

    public function searchCitiesByQuery(string $query): array
    {
        $response = $this->httpClient->get(
            sprintf(
                '%s/city?name=%s&page=1&limit=50&can_receive_tracks=true&sort_by_population=DESC',
                self::API_URL,
                rawurlencode($query)
            ),
            [
                'Accept' => 'application/json',
            ]
        );

        if ($response === null) {
            return [];
        }

        $data = json_decode($response, true);
        if (json_last_error() || (int)$data['statusCode'] !== 0) {
            return [];
        }

        return array_map(function (array $city) {
            $name = sprintf('%s, %s обл.', $city['name'], $city['region_name']);

            return new City($city['id'], $name, $name);
        },  $data['data'] ?? []);
    }

    public function searchCityById(string $id): ?City
    {
        throw new \RuntimeException('Not implemented');
    }

    public function searchPUDOByQuery(SearchPUDORequestDTO $request): array
    {
        $url = sprintf(
            '%s/department?city_id=%s&page=%d&limit=20&can_receive_tracks=true',
            self::API_URL,
            rawurlencode($request->cityId),
            $request->page
        );
        if (!empty($request->query)) {
            $url .= '&name=' . rawurlencode($request->query);
        }

        $response = $this->httpClient->get($url, [
            'Accept' => 'application/json',
        ]);

        if ($response === null) {
            return [];
        }

        $data = json_decode($response, true);
        if (json_last_error() || (int)$data['statusCode'] !== 0) {
            return [];
        }

        $result = array_map(function (array $warehouse) use ($request) {
            $name = ltrim(substr($warehouse['name'], strpos($warehouse['name'], ',') + 1));

            return new PUDO(
                (string)$warehouse['id'],
                $request->cityId,
                $name,
                $name,
                PUDO::PUDO_TYPE_WAREHOUSE
            );
        }, $data['data'] ?? []);

        return [
            'data' => $result,
            'total' => count($result) < 20 ? count($result) : 9999,
        ];
    }

    public function searchPUDOById(string $id): ?PUDO
    {
        throw new \RuntimeException('Not implemented');
    }
}
