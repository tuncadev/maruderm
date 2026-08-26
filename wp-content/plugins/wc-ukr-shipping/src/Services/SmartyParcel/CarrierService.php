<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Services\SmartyParcel;

use kirillbdev\WCUkrShipping\Api\SmartyParcelWPApi;
use kirillbdev\WCUkrShipping\Contracts\Cache\CacheInterface;
use kirillbdev\WCUkrShipping\Helpers\SmartyParcelHelper;

if ( ! defined('ABSPATH')) {
    exit;
}

/**
 * Provides the list of carriers supported by the SmartyParcel platform.
 */
class CarrierService
{
    private const CACHE_KEY = 'sp_carriers_list';
    private const CACHE_TTL = 86400; // 24 hours

    private SmartyParcelWPApi $api;
    private CacheInterface $cache;

    public function __construct(SmartyParcelWPApi $api)
    {
        $this->api = $api;
        $this->cache = wcus_container()->make(CacheInterface::class);
    }

    /**
     * @return array<int, array{slug: string, name: string}>
     */
    public function getAvailableCarriers(): array
    {
        if ( ! SmartyParcelHelper::isConnected()) {
            return [];
        }

        $cached = $this->cache->get(self::CACHE_KEY);
        if (is_array($cached) && count($cached) > 0) {
            return $cached;
        }

        $carriers = $this->api->sendRequest('/v1/carriers/list');
        $carriersList = [];
        foreach ($carriers as $item) {
            $carriersList[] = [
                'slug' => $item['carrier_slug'],
                'name' => $item['name_en'],
            ];
        }


        if (count($carriersList) > 0) {
            $this->cache->set(self::CACHE_KEY, $carriersList, self::CACHE_TTL);
        }

        return $carriersList;
    }

    public function isCarrierSupported(string $carrierSlug): bool
    {
        foreach ($this->getAvailableCarriers() as $carrier) {
            if ($carrier['slug'] === $carrierSlug) {
                return true;
            }
        }

        return false;
    }

    public function flushCache(): void
    {
        $this->cache->delete(self::CACHE_KEY);
    }
}
