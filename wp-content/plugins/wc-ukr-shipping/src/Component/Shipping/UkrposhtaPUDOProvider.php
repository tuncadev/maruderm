<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Component\Shipping;

use kirillbdev\WCUkrShipping\Contracts\Shipping\PUDOProviderInterface;
use kirillbdev\WCUkrShipping\Dto\Shipping\City;
use kirillbdev\WCUkrShipping\Dto\Shipping\PUDO;
use kirillbdev\WCUkrShipping\Dto\Shipping\SearchPUDORequestDTO;
use kirillbdev\WCUkrShipping\Http\WpHttpClient;

/**
 * todo Remove by SmartyParcel Locator in future
 */
class UkrposhtaPUDOProvider implements PUDOProviderInterface
{
    private const API_URL = 'https://www.ukrposhta.ua';

    private WpHttpClient $httpClient;

    public function __construct(WpHttpClient $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function searchCitiesByQuery(string $query): array
    {
        $bearer = wc_ukr_shipping_get_option('wcus_ukrposhta_bearer_ecom');
        if (empty($bearer)) {
            return [];
        }

        $response = $this->httpClient->get(
            self::API_URL . sprintf('/address-classifier-ws/get_city_by_region_id_and_district_id_and_city_ua?city_ua=%s', rawurlencode(wp_unslash($query))),
            [
                'Authorization' => 'Bearer ' . $bearer,
                'Accept' => 'application/json',
            ]
        );

        if ($response === null) {
            return [];
        }

        $data = json_decode($response, true);
        if (json_last_error()) {
            return [];
        }

        $cities = $data['Entries']['Entry'] ?? [];

        return array_map(function (array $city) {
            $name = sprintf(
                '%s, %s, %s',
                $city['SHORTCITYTYPE_UA'] . ' ' . $city['CITY_UA'],
                $city['DISTRICT_UA'] . ' р-н',
                $city['REGION_UA'] . ' обл.'
            );

            return new City($city['CITY_ID'], $name, $name);
        }, $cities);
    }

    public function searchCityById(string $id): ?City
    {
        throw new \RuntimeException('Not implemented');
    }

    public function searchPUDOByQuery(SearchPUDORequestDTO $request): array
    {
        $bearer = wc_ukr_shipping_get_option('wcus_ukrposhta_bearer_ecom');
        if (empty($bearer)) {
            return [];
        }

        $response = $this->httpClient->get(
            self::API_URL . sprintf('/address-classifier-ws/get_postoffices_by_postcode_cityid_cityvpzid?city_id=%s', $request->cityId),
            [
                'Authorization' => 'Bearer ' . $bearer,
                'Accept' => 'application/json',
            ]
        );

        if ($response === null) {
            return [];
        }

        $data = json_decode($response, true);
        if (json_last_error()) {
            return [];
        }

        $data = array_map(function (array $warehouse) use ($request) {
             $name = sprintf(
                 '%s, %s',
                 $warehouse['POSTOFFICE_UA'],
                 $warehouse['STREET_UA_VPZ']
             );

            return new PUDO(
                $warehouse['POSTCODE'],
                $request->cityId,
                $name,
                $name,
                PUDO::PUDO_TYPE_WAREHOUSE
            );
        }, $data['Entries']['Entry'] ?? []);

        return [
            'data' => $data,
            'total' => count($data),
        ];
    }

    public function searchPUDOById(string $id): ?PUDO
    {
        throw new \RuntimeException('Not implemented');
    }
}
