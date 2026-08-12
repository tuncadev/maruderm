<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Component\Carriers\Meest\Shipping;

use kirillbdev\WCUkrShipping\Contracts\Shipping\PUDOProviderInterface;
use kirillbdev\WCUkrShipping\Dto\Shipping\City;
use kirillbdev\WCUkrShipping\Dto\Shipping\PUDO;
use kirillbdev\WCUkrShipping\Dto\Shipping\SearchPUDORequestDTO;
use kirillbdev\WCUkrShipping\Http\WpHttpClient;

class MeestPUDOProvider implements PUDOProviderInterface
{
    private const API_URL = 'https://api.meest.com/v3.0/openAPI';
    private const COUNTRY_ID_UA = 'c35b6195-4ea3-11de-8591-001d600938f8';

    private WpHttpClient $httpClient;

    public function __construct(WpHttpClient $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function searchCitiesByQuery(string $query): array
    {
        $apiToken = wc_ukr_shipping_get_option('wcus_meest_api_token');
        if (!$apiToken) {
            return [];
        }

        $response = $this->httpClient->post(
            self::API_URL . '/citySearch',
            json_encode([
                'filters' => [
                    'cityDescr' => empty($query) ? '' : $query . '%',
                    'countryID' => self::COUNTRY_ID_UA,
                ]
            ]),
            [
                'token' => $apiToken,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ]
        );

        if ($response === null) {
            return [];
        }

        $data = json_decode($response, true);
        if (json_last_error() || strtolower($data['status'] ?? '') !== 'ok') {
            return [];
        }

        return array_map(function (array $city) {
            $name = sprintf(
                '%s (%s%s)',
                $city['cityDescr']['descrUA'],
                $city['regionDescr']['descrUA'] . ' обл.',
                !empty($city['districtDescr']['descrUA'])
                    ? ', ' . $city['districtDescr']['descrUA']  . ' р-н'
                    : ''
            );

            return new City($city['cityID'], $name, $name);
        }, $data['result'] ?? []);
    }

    public function searchCityById(string $id): ?City
    {
        throw new \RuntimeException('Not implemented');
    }

    public function searchPUDOByQuery(SearchPUDORequestDTO $request): array
    {
        $apiToken = wc_ukr_shipping_get_option('wcus_meest_api_token');
        if (!$apiToken) {
            return [];
        }

        $response = $this->httpClient->post(
            self::API_URL . '/branchSearch',
            json_encode([
                'filters' => [
                    'branchDescr' => '%' . $request->query . '%',
                    'cityID' => $request->cityId,
                ]
            ]),
            [
                'token' => $apiToken,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ]
        );

        if ($response === null) {
            return [];
        }

        $data = json_decode($response, true);
        if (json_last_error() || strtolower($data['status'] ?? '') !== 'ok') {
            return [];
        }

        $result = array_map(function (array $branch) use ($request) {
            return new PUDO(
                (string)$branch['branchID'],
                $request->cityId,
                $branch['ShortName'],
                $branch['ShortName'],
                PUDO::PUDO_TYPE_WAREHOUSE
            );
        }, $data['result'] ?? []);

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
