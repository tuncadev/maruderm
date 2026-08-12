<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Component\Carriers\RozetkaDelivery\Order;

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
        $cityId = $data['wcus_rozetka_' . $this->fieldGroup . '_city'] ?? '';
        $cityName = $data['wcus_rozetka_' . $this->fieldGroup . '_city_name'] ?? '';
        $warehouseId = $data['wcus_rozetka_' . $this->fieldGroup . '_warehouse'] ?? '';
        $warehouseName = $data['wcus_rozetka_' . $this->fieldGroup . '_warehouse_name'] ?? '';

        $this->updateMeta($item, 'wcus_rozetka_city_id', $cityId);
        $this->updateMeta($item, 'wcus_rozetka_city_name', $cityName);
        $this->updateMeta($item, 'wcus_rozetka_warehouse_id', $warehouseId);
        $this->updateMeta($item, 'wcus_rozetka_warehouse_name', $warehouseName);
    }
}
