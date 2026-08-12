<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Component\Shipping;

use kirillbdev\WCUkrShipping\Api\SmartyParcelWPApi;
use kirillbdev\WCUkrShipping\Contracts\Shipping\PUDOProviderInterface;
use kirillbdev\WCUkrShipping\Dto\Shipping\City;
use kirillbdev\WCUkrShipping\Dto\Shipping\PUDO;
use kirillbdev\WCUkrShipping\Dto\Shipping\SearchPUDORequestDTO;

class SmartyParcelPUDOProvider implements PUDOProviderInterface
{
    private string $carrierSlug;
    private string $lang;
    private SmartyParcelWPApi $api;

    public function __construct(string $carrierSlug, string $lang, SmartyParcelWPApi $api)
    {
        $this->carrierSlug = $carrierSlug;
        $this->lang = $lang;
        $this->api = $api;
    }

    public function searchCitiesByQuery(string $query): array
    {
        $response = $this->api->sendRequest('/v1/locator/cities', null, [
            'carrier_slug' => $this->carrierSlug,
            'query' => $query,
            'language' => $this->lang,
        ]);

        return array_map(function (array $city) {
            return new City($city['carrier_city_id'], $city['name'], $city['name']);
        }, $response['cities']);
    }

    public function searchCityById(string $id): ?City
    {
        throw new \RuntimeException('Not implemented');
    }

    public function searchPUDOByQuery(SearchPUDORequestDTO $request): array
    {
        $mappedTypes = [];
        if (in_array(PUDO::PUDO_TYPE_WAREHOUSE, $request->types, true)) {
            $mappedTypes[] = 'warehouse';
            $mappedTypes[] = 'pudo';
        }
        if (in_array(PUDO::PUDO_TYPE_LOCKER, $request->types, true)) {
            $mappedTypes[] = 'parcel_locker';
        }

        $params = [
            'carrier_slug' => $this->carrierSlug,
            'language' => $this->lang,
            'page' => $request->page,
            'limit' => 20,
            'types' => $mappedTypes,
        ];
        if (!empty($request->query)) {
            $params['query'] = $request->query;
        }
        if (isset($request->weight) && $request->weight > 0) {
            $params['weight'] = [
                'value' => (float)$request->weight,
                'unit' => 'kg',
            ];
        }
        if ($this->carrierSlug === 'nova_post') {
            $params['country_code'] = $request->cityId;
        } else {
            $params['carrier_city_id'] = $request->cityId;
        }
        $response = $this->api->sendRequest('/v1/locator/pudo-points', null, $params);

        $data = array_map(function (array $item) use ($request) {
            return new PUDO(
                $item['carrier_pudo_id'],
                $request->cityId,
                $item['name'],
                $item['name'],
                $item['type'] === 'parcel_locker'
                    ? PUDO::PUDO_TYPE_LOCKER
                    : PUDO::PUDO_TYPE_WAREHOUSE
            );
        }, $response['pudo_points']);

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
