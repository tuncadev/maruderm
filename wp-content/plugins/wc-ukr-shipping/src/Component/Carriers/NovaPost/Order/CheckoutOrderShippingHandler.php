<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Component\Carriers\NovaPost\Order;

use kirillbdev\WCUkrShipping\Contracts\Order\OrderShippingHandlerInterface;
use kirillbdev\WCUkrShipping\Traits\SafeOrderShippingTrait;

class CheckoutOrderShippingHandler implements OrderShippingHandlerInterface
{
    use SafeOrderShippingTrait;

    private string $fieldGroup;

    public function __construct(string $fieldGroup)
    {
        $this->fieldGroup = $fieldGroup;
    }

    public function saveShippingData(\WC_Order_Item_Shipping $item, array $data): void
    {
        $this->saveWarehouseShipping($item, $data);
    }

    private function saveWarehouseShipping(\WC_Order_Item_Shipping $item, array $data): void
    {
        $warehouseId = $data['wcus_nova_post_' . $this->fieldGroup . '_warehouse'] ?? '';
        $warehouseName = $data['wcus_nova_post_' . $this->fieldGroup . '_warehouse_name'] ?? '';

        $this->updateMeta($item, 'wcus_nova_post_warehouse_id', $warehouseId);
        $this->updateMeta($item, 'wcus_nova_post_warehouse_name', $warehouseName);
    }
}
