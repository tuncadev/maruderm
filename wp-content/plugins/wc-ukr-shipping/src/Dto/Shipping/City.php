<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Dto\Shipping;

class City
{
    public string $id;
    public string $nameUa;
    public string $nameRu;

    public function __construct(string $id, string $nameUa, string $nameRu)
    {
        $this->id = $id;
        $this->nameUa = $nameUa;
        $this->nameRu = $nameRu;
    }
}
