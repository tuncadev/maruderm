<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Api;

use kirillbdev\WCUkrShipping\Contracts\NovaPoshtaAddressProviderInterface;
use kirillbdev\WCUkrShipping\Exceptions\NovaPoshtaAddressProviderException;
use kirillbdev\WCUkrShipping\Model\NovaPoshta\City;
use kirillbdev\WCUkrShipping\Model\NovaPoshta\Warehouse;

final class SmartyParcelAddressBook implements NovaPoshtaAddressProviderInterface
{
    private string $cloudUrl = 'https://wp-api.smartyparcel.com/v1';

    public function getCities(int $page, int $limit): array
    {
        try {
            $response = $this->sendRequest('/locator/np/cities', [
                'page' => $page,
                'limit' => $limit,
            ]);

            return array_map(function (array $data) {
                return new City($data['ref'], $data['area'], $data['description_ru'], $data['description']);
            }, $response['data']);
        } catch (\Exception $e) {
            throw new NovaPoshtaAddressProviderException($e->getMessage());
        }
    }

    public function getWarehouses(int $page, int $limit): array
    {
        try {
            $response = $this->sendRequest('/locator/np/warehouses', [
                'page' => $page,
                'limit' => $limit,
            ]);

            return array_map(function (array $data) {
                return new Warehouse(
                    $data['ref'],
                    $data['city_ref'],
                    $data['description_ru'],
                    $data['description'],
                    (int)$data['warehouse_number'],
                    $this->mapWarehouseType($data['warehouse_type']),
                    (int)$data['max_weight_allowed'],
                    (int)$data['place_max_weight_allowed'],
                );
            }, $response['data']);
        } catch (\Exception $e) {
            throw new NovaPoshtaAddressProviderException($e->getMessage());
        }
    }

    private function mapWarehouseType(string $responseType): int
    {
        switch ($responseType) {
            case '9a68df70-0267-42a8-bb5c-37f427e36ee4':
                return Warehouse::TYPE_CARGO;
            case 'f9316480-5f2d-425d-bc2c-ac7cd29decf0':
                return Warehouse::TYPE_POSHTOMAT;
            default:
                return Warehouse::TYPE_REGULAR;
        }
    }

    /**
     * @throws \Exception
     */
    private function sendRequest(string $endpoint, array $payload = [])
    {
        $response = wp_remote_get($this->cloudUrl . $endpoint . '?' . http_build_query($payload), [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'timeout' => 10, // Hardcoded at now
        ]);

        if (is_wp_error($response)) {
            throw new \Exception($response->get_error_message());
        }

        $code = (int)wp_remote_retrieve_response_code($response);

        if ($code !== 200) {
            throw new \Exception("External API error: Bad request or communication error");
        }

        $result = json_decode($response['body'], true);
        if (json_last_error()) {
            throw new \Exception("External API error: malformed response");
        }

        return $result;
    }
}
