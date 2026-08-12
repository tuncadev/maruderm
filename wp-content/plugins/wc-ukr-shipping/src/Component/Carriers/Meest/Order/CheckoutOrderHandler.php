<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Component\Carriers\Meest\Order;

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

        if ($isShipToDifferentAddress) {
            $unitOfWork['update'] = [
                'shipping_phone' => sanitize_text_field($data['wcus_shipping_phone'] ?? ''),
                'meta._wcus_ship_to_different_address' => 1,
            ];
        } else {
            $unitOfWork['update'] = [
                'shipping_first_name' => $order->get_billing_first_name(),
                'shipping_last_name' => $order->get_billing_last_name(),
            ];
        }

        $unitOfWork['update'] = array_merge($unitOfWork['update'], $this->saveWarehouseShipping($order, $data));
        $unitOfWork['update']['meta.wcus_data_version'] = '3';

        $store = new OrderDataStore($order);
        $store->save($unitOfWork);
    }

    private function saveWarehouseShipping(\WC_Order $order, array $data): array
    {
        $group = 'billing_only' === get_option('woocommerce_ship_to_destination') ? 'billing' : 'shipping';
        return [
            $group . '_city' => $data['wcus_meest_' . $this->fieldGroup . '_city_name'] ?? '',
            $group . '_address_1' => $data['wcus_meest_' . $this->fieldGroup . '_warehouse_name'] ?? '',
            'shippingMeta.wcus_pudo_city_id' => $data['wcus_meest_' . $this->fieldGroup . '_city'] ?? '',
            'shippingMeta.wcus_pudo_city_name' => $data['wcus_meest_' . $this->fieldGroup . '_city_name'] ?? '',
            'shippingMeta.wcus_pudo_point_id' => $data['wcus_meest_' . $this->fieldGroup . '_warehouse'] ?? '',
            'shippingMeta.wcus_pudo_point_name' => $data['wcus_meest_' . $this->fieldGroup . '_warehouse_name'] ?? '',
        ];
    }

    private function isShipToDifferentAddress(array $data): bool
    {
        return isset($data['ship_to_different_address'])
            && (int)$data['ship_to_different_address'] === 1;
    }
}
