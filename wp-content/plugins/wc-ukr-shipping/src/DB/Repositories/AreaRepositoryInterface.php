<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\DB\Repositories;

use kirillbdev\WCUkrShipping\Model\Address\Area;

interface AreaRepositoryInterface
{
    public function findByRef(string $ref): ?Area;
}
