<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Services\Address;

use kirillbdev\WCUkrShipping\Contracts\NovaPoshtaAddressProviderInterface;
use kirillbdev\WCUkrShipping\DB\Repositories\CityRepository;
use kirillbdev\WCUkrShipping\DB\Repositories\WarehouseRepository;
use kirillbdev\WCUkrShipping\DB\Repositories\WarehouseSyncRepository;

if ( ! defined('ABSPATH')) {
    exit;
}

class AddressBookService
{
    private NovaPoshtaAddressProviderInterface $addressProvider;
    private WarehouseSyncRepository $syncRepository;
    private CityRepository $cityRepository;
    private WarehouseRepository $warehouseRepository;

    public function __construct(
        WarehouseSyncRepository $syncRepository,
        CityRepository $cityRepository,
        WarehouseRepository $warehouseRepository
    ) {
        $this->addressProvider = wcus_container()->make(NovaPoshtaAddressProviderInterface::class);
        $this->syncRepository = $syncRepository;
        $this->cityRepository = $cityRepository;
        $this->warehouseRepository = $warehouseRepository;
    }

    public function loadCities(int $page): int
    {
        $cities = $this->addressProvider->getCities(
            $page,
            apply_filters('wcus_api_city_limit', 2000)
        );
        $this->syncRepository->updateStage(WarehouseSyncRepository::STAGE_CITY, $page);

        if ($page === 1) {
            $this->cityRepository->clearCities();
        }

        $chunkSize = apply_filters('wcus_update_cities_chunk_size', 200);
        foreach (array_chunk($cities, $chunkSize) as $chunk) {
            $this->cityRepository->bulkUpsertCities($chunk);
        }

        return count($cities);
    }

    public function loadWarehouses(int $page): int
    {
        $warehouses = $this->addressProvider->getWarehouses(
            $page,
            apply_filters('wcus_api_warehouse_limit', 2000)
        );
        $this->syncRepository->updateStage(WarehouseSyncRepository::STAGE_WAREHOUSE, $page);

        if ($page === 1) {
            $this->warehouseRepository->clearWarehouses();
        }

        $chunkSize = apply_filters('wcus_update_warehouses_chunk_size', 200);
        foreach (array_chunk($warehouses, $chunkSize) as $chunk) {
            $this->warehouseRepository->bulkUpsertWarehouses($chunk);
        }

        if (count($warehouses) === 0) {
            $this->syncRepository->setCompleteSync();
        }

        return count($warehouses);
    }
}