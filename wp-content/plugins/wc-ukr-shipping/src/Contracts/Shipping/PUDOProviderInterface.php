<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Contracts\Shipping;

use kirillbdev\WCUkrShipping\Dto\Shipping\City;
use kirillbdev\WCUkrShipping\Dto\Shipping\PUDO;
use kirillbdev\WCUkrShipping\Dto\Shipping\SearchPUDORequestDTO;

interface PUDOProviderInterface
{
    /**
     * @param string $query
     * @return City[]
     */
    public function searchCitiesByQuery(string $query): array;

    public function searchCityById(string $id): ?City;

    public function searchPUDOByQuery(SearchPUDORequestDTO $request): array;

    public function searchPUDOById(string $id): ?PUDO;
}
