<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Api;

use kirillbdev\WCUkrShipping\Component\SmartyParcel\LabelRequestBuilderInterface;
use kirillbdev\WCUkrShipping\Exceptions\SmartyParcel\SmartyParcelErrorException;

final class SmartyParcelApi
{
    private const API_URL = 'https://wp-api.smartyparcel.com';

    public function getAccount(string $apiKey): array
    {
        $response = wp_remote_get(self::API_URL . '/v1/account', [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'SP-API-Key' => $apiKey,
                'SP-Site-Url' => get_site_url(),
            ],
            'timeout' => 10,
        ]);

        return $this->processResponse($response);
    }

    public function disconnectApplication(string $apiKey): array
    {
        $response = wp_remote_post(self::API_URL . '/v1/app/disconnect', [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'SP-API-Key' => $apiKey,
                'SP-Site-Url' => get_site_url(),
            ],
            'timeout' => 10,
        ]);

        return $this->processResponse($response);
    }

    public function getCarrierAccounts(string $apiKey): array
    {
        $response = wp_remote_get(self::API_URL . '/v1/carriers', [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'SP-API-Key' => $apiKey,
                'SP-Site-Url' => get_site_url(),
            ],
            'timeout' => 10,
        ]);

        return $this->processResponse($response);
    }

    public function createLabel(LabelRequestBuilderInterface $builder): array
    {
        $response = wp_remote_post(self::API_URL . '/v1/labels', [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'SP-API-Key' =>  get_option(WCUS_OPTION_SMARTY_PARCEL_API_KEY),
                'SP-Site-Url' => get_site_url(),
            ],
            'timeout' => 15,
            'body' => json_encode($builder->build())
        ]);

        return $this->processResponse($response);
    }

    public function voidLabel(string $labelId): array
    {
        $response = wp_remote_request(self::API_URL . "/v1/labels/$labelId/void", [
            'method' => 'PUT',
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'SP-API-Key' =>  get_option(WCUS_OPTION_SMARTY_PARCEL_API_KEY),
                'SP-Site-Url' => get_site_url(),
            ],
            'timeout' => 10,
        ]);

        return $this->processResponse($response);
    }

    public function estimateRates(
        string $carrierAccountId,
        string $shipFrom,
        string $shipTo,
        string $deliveryType,
        float $declaredValue,
        float $weight,
        ?string $serviceType = null,
        bool $covertCurrency = false
    ): array {
        $payload = [
            'carrier_account_id' => $carrierAccountId,
            'delivery_type' => $deliveryType,
            'ship_from' => [
                'country_code' => 'UA',
                'carrier_city_id' => $shipFrom,
            ],
            'ship_to' => [
                'country_code' => 'UA',
                'carrier_city_id' => $shipTo,
            ],
            'declared_value' => [
                'amount' => $declaredValue,
                'currency' => 'UAH',
            ],
            'weight' => [
                'value' => $weight,
                'unit' => 'kg',
            ]
        ];
        if ($serviceType !== null) {
            $payload['service_type'] = $serviceType;
        }
        if ($covertCurrency) {
            $payload['convert_to_currency'] = get_woocommerce_currency();
        }

        $response = wp_remote_post(self::API_URL . "/v1/rates/estimate", [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'SP-API-Key' => get_option(WCUS_OPTION_SMARTY_PARCEL_API_KEY),
                'SP-Site-Url' => get_site_url(),
            ],
            'timeout' => 10,
            'body' => json_encode($payload),
        ]);

        return $this->processResponse($response);
    }

    public function addTracking(string $trackingNumber, string $carrierSlug): array
    {
        $response = wp_remote_post(self::API_URL . "/v1/trackings", [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'SP-API-Key' =>  get_option(WCUS_OPTION_SMARTY_PARCEL_API_KEY),
                'SP-Site-Url' => get_site_url(),
            ],
            'timeout' => 10,
            'body' => json_encode([
                'tracking_number' => $trackingNumber,
                'carrier_slug' => $carrierSlug,
            ]),
        ]);

        return $this->processResponse($response);
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

        if ($code === 200) {
            return $payload;
        }

        throw new SmartyParcelErrorException(
            $payload['error']['code'] ?? 0,
            $payload['error']['message'] ?? 'Unknown error',
            $payload['error']['details'] ?? []
        );
    }
}
