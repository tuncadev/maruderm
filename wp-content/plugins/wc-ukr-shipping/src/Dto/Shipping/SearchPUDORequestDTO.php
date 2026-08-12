<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Dto\Shipping;

final class SearchPUDORequestDTO
{
    public string $cityId;
    public string $query;
    public array $types;
    public ?float $weight;
    public int $page;

    public function __construct(
        string $cityId,
        string $query,
        array $types,
        ?float $weight = null,
        int $page = 1
    ) {
        $this->cityId = $cityId;
        $this->query = $query;
        $this->types = $types;
        $this->weight = $weight;
        $this->page = $page;
    }
}
