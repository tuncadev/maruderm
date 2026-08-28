<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Component\Carriers\PostNord\Order;

use kirillbdev\WCUkrShipping\Component\WooCommerce\OrderDataStore;
use kirillbdev\WCUkrShipping\Contracts\Order\OrderHandlerInterface;

class CheckoutOrderHandler implements OrderHandlerInterface
{
    private string $fieldGroup;

    public function saveShippingData(\WC_Order $order, array $data): void
    {
        // Init checkout configuration
        $isShipToDifferentAddress = $this->isShipToDifferentAddress($data);
        $this->fieldGroup =  $isShipToDifferentAddress ? 'shipping' : 'billing';

        $unitOfWork['update'] = $this->saveWarehouseShipping($order, $data);
        $unitOfWork['update']['meta.wcus_data_version'] = '3';

        $store = new OrderDataStore($order);
        $store->save($unitOfWork);
    }

    private function saveWarehouseShipping(\WC_Order $order, array $data): array
    {
        return [
            'meta._wcus_pudo_point_name' => $data['wcus_post_nord_' . $this->fieldGroup . '_pudo_name'] ?? '',
            'shippingMeta.wcus_pudo_point_id' => $data['wcus_post_nord_' . $this->fieldGroup . '_pudo_id'] ?? '',
            'shippingMeta.wcus_pudo_point_name' => $data['wcus_post_nord_' . $this->fieldGroup . '_pudo_name'] ?? '',
        ];
    }

    private function isShipToDifferentAddress(array $data): bool
    {
        return isset($data['ship_to_different_address'])
            && (int)$data['ship_to_different_address'] === 1;
    }
}
