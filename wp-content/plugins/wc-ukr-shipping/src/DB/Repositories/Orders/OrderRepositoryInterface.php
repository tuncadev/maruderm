<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\DB\Repositories\Orders;

interface OrderRepositoryInterface
{
    public function getOrdersWithTTN(int $offset, int $limit, array $filters = []): array;
    public function getOrderInfo(int $orderId): array;
    public function getOrderShippingMethod(int $orderId): ?\stdClass;
    public function getCountOrderPages(int $limit, array $filters = []): int;
}
