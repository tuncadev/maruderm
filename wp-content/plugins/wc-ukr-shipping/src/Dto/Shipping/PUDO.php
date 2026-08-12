<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Dto\Shipping;

class PUDO
{
    public const PUDO_TYPE_WAREHOUSE = 'warehouse';
    public const PUDO_TYPE_LOCKER = 'locker';

    public string $id;
    public string $cityId;
    public string $nameUa;
    public string $nameRu;
    public string $type;
    public array $meta;

    public function __construct(
        string $id,
        string $cityId,
        string $nameUa,
        string $nameRu,
        string $type,
        array $meta = []
    ) {
        $this->id = $id;
        $this->cityId = $cityId;
        $this->nameUa = $nameUa;
        $this->nameRu = $nameRu;
        $this->type = $type;
        $this->meta = $meta;
    }
}
