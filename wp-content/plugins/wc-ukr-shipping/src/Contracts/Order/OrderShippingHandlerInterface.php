<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Contracts\Order;

interface OrderShippingHandlerInterface
{
    public function saveShippingData(\WC_Order_Item_Shipping $item, array $data): void;
}
