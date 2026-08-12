<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Services\SmartyParcel;

use kirillbdev\WCUkrShipping\Api\SmartyParcelWPApi;

final class ManifestService
{
    private const CACHE_TTL = 10800; // 3 hours

    private SmartyParcelWPApi $api;

    public function __construct(SmartyParcelWPApi $api)
    {
        $this->api = $api;
    }

    public function getManifest(): array
    {
        $cached = get_transient('smartyparcel_manifest');
        if ($cached) {
            return json_decode($cached, true);
        }

        try {
            $manifest = $this->api->sendRequest('/v1/manifest');
            set_transient('smartyparcel_manifest', json_encode($manifest), self::CACHE_TTL);

            return $manifest;
        } catch (\Exception $e) {
            return [];
        }
    }
}
