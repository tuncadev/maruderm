<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Contracts\Order;

interface OrderHandlerInterface
{
    public function saveShippingData(\WC_Order $order, array $data): void;
}
