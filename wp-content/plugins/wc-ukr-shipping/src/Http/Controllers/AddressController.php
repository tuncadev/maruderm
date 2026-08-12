<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Http\Controllers;

use kirillbdev\WCUkrShipping\Api\SmartyParcelWPApi;
use kirillbdev\WCUkrShipping\Component\Carriers\Meest\Shipping\MeestPUDOProvider;
use kirillbdev\WCUkrShipping\Component\Carriers\NovaPost\Shipping\NovaPostPUDOProvider;
use kirillbdev\WCUkrShipping\Component\Carriers\RozetkaDelivery\Shipping\RozetkaDeliveryPUDOProvider;
use kirillbdev\WCUkrShipping\Component\Shipping\NovaPoshtaPUDOProvider;
use kirillbdev\WCUkrShipping\Component\Shipping\SmartyParcelPUDOProvider;
use kirillbdev\WCUkrShipping\Component\Shipping\UkrposhtaPUDOProvider;
use kirillbdev\WCUkrShipping\Contracts\Shipping\PUDOProviderInterface;
use kirillbdev\WCUkrShipping\Dto\Shipping\City;
use kirillbdev\WCUkrShipping\Dto\Shipping\PUDO;
use kirillbdev\WCUkrShipping\Dto\Shipping\SearchPUDORequestDTO;
use kirillbdev\WCUkrShipping\Enums\CarrierSlug;
use kirillbdev\WCUkrShipping\Helpers\SmartyParcelHelper;
use kirillbdev\WCUSCore\Http\Controller;
use kirillbdev\WCUSCore\Http\Request;

if ( ! defined('ABSPATH')) {
    exit;
}

class AddressController extends Controller
{
    public function searchCities(Request $request)
    {
        // todo: move query check logic to concrete provider
        if (  ! $request->get('query') && $request->get('carrier') !== CarrierSlug::MEEST) {
            return $this->jsonResponse([
                'success' => true,
                'data' => []
            ]);
        }
        $provider = $this->getPUDOProvider(
            $request->get('carrier'),
            $request->get('lang', '')
        );

        /**
         * Enable third-party code to override PUDO cities query
         * @since 1.17.5
         */
        $query = apply_filters('wcus_pudo_cities_query', $request->get('query'), $request->get('carrier'));

        return $this->jsonResponse([
            'success' => true,
            'data' => $this->mapCities(
                $provider->searchCitiesByQuery($query),
                $request->get('lang', '')
            )
        ]);
    }

    public function searchWarehouses(Request $request)
    {
        if ( ! $request->get('city_ref') || ! (int)$request->get('page')) {
            return $this->jsonResponse([
                'success' => true,
                'data' => [
                    'items' => [],
                    'more' => false
                ]
            ]);
        }

        $provider = $this->getPUDOProvider(
            $request->get('carrier'),
            $request->get('lang', '')
        );

        /**
         * Enable third-party code to override pickup points query
         * @since 1.17.5
         */
        $query = apply_filters('wcus_pudo_points_query', $request->get('query', ''), $request->get('carrier'));

        /**
         * Enable third-party code to override request data
         * @since 1.21.7
         */
        $requestData = apply_filters(
            'wcus_pudo_points_request',
            [
                'cityId' => $request->get('city_ref'),
                'query' => $query,
                'page' => (int)$request->get('page'),
                'types' => $request->get('types', [
                    PUDO::PUDO_TYPE_WAREHOUSE,
                    PUDO::PUDO_TYPE_LOCKER,
                ]),
            ],
            $request->get('carrier')
        );

        try {
            $result = $provider->searchPUDOByQuery(
                new SearchPUDORequestDTO(
                    $requestData['cityId'],
                    $requestData['query'],
                    $requestData['types'],
                    isset($requestData['weight']) ? (float)$requestData['weight'] : null,
                    $requestData['page']
                )
            );
        } catch (\Throwable $e) {
            return $this->jsonResponse([
                'success' => true,
                'data' => [
                    'items' => [],
                    'more' => false
                ]
            ]);
        }

        if (!isset($result['data']) || count($result['data']) === 0) {
            return $this->jsonResponse([
                'success' => true,
                'data' => [
                    'items' => [],
                    'more' => false
                ]
            ]);
        }

        $items = $this->mapWarehouses($result['data'], $request->get('lang', ''));

        return $this->jsonResponse([
            'success' => true,
            'data' => [
                'items' => $items,
                'more' => $request->get('carrier') === 'nova_poshta'
                    ? count($items) >= 20
                    : false,
            ]
        ]);
    }

    /**
     * @param City[] $cities
     * @param string $locale
     * @return array
     */
    private function mapCities(array $cities, string $locale): array
    {
        return array_map(function (City $item) use ($locale) {
            return [
                'value' => $item->id,
                'name' => $locale === 'ru' ? $item->nameRu : $item->nameUa,
            ];
        }, $cities);
    }

    /**
     * @param PUDO[] $warehouses
     * @param string $locale
     * @return array
     */
    private function mapWarehouses(array $warehouses, string $locale): array
    {
        return array_map(function (PUDO $item) use ($locale) {
            return [
                'value' => $item->id,
                'name' => $locale === 'ru' ? $item->nameRu : $item->nameUa,
                'meta' => $item->meta,
            ];
        }, $warehouses);
    }

    private function getPUDOProvider(string $carrier, string $lang): PUDOProviderInterface
    {
        $useLocator = (int)wc_ukr_shipping_get_option('wcus_use_smartyparcel_locator') === 1;
        $locatorSupportedCarriers = [
          CarrierSlug::NOVA_POSHTA,
          CarrierSlug::UKRPOSHTA,
          CarrierSlug::ROZETKA_DELIVERY,
          CarrierSlug::NOVA_POST,
        ];
        if ($useLocator && SmartyParcelHelper::isConnected() && in_array($carrier, $locatorSupportedCarriers, true)) {
            return new SmartyParcelPUDOProvider(
                $carrier,
                $lang,
                wcus_container()->make(SmartyParcelWPApi::class)
            );
        }

        switch ($carrier) {
            case CarrierSlug::NOVA_POSHTA:
                return wcus_container()->make(NovaPoshtaPUDOProvider::class);
            case CarrierSlug::UKRPOSHTA:
                return wcus_container()->make(UkrposhtaPUDOProvider::class);
            case CarrierSlug::NOVA_POST:
                return wcus_container()->make(NovaPostPUDOProvider::class);
            case CarrierSlug::ROZETKA_DELIVERY:
                return wcus_container()->make(RozetkaDeliveryPUDOProvider::class);
            case CarrierSlug::MEEST:
                return wcus_container()->make(MeestPUDOProvider::class);
        }

        throw new \Exception('Wrong carrier ' . $carrier);
    }
}
