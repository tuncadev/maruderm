<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Component\Carriers\RozetkaDelivery\Order;

use kirillbdev\WCUkrShipping\Contracts\Order\OrderHandlerInterface;

class CheckoutOrderHandler implements OrderHandlerInterface
{
    private string $fieldGroup;

    public function saveShippingData(\WC_Order $order, array $data): void
    {
        // Init checkout configuration
        $isShipToDifferentAddress = $this->isShipToDifferentAddress($data);
        $this->fieldGroup = $isShipToDifferentAddress ? 'shipping' : 'billing';

        if ($isShipToDifferentAddress) {
            $order->set_shipping_phone(sanitize_text_field($data['wcus_shipping_phone'] ?? ''));
            $order->update_meta_data('wcus_shipping_phone', sanitize_text_field($data['wcus_shipping_phone'] ?? ''));
            $order->update_meta_data('wcus_middlename', sanitize_text_field($data['wcus_shipping_middlename'] ?? ''));
            $order->update_meta_data('_wcus_ship_to_different_address', 1);
        } else {
            $order->set_shipping_first_name($order->get_billing_first_name());
            $order->set_shipping_last_name($order->get_billing_last_name());
            $order->update_meta_data('wcus_middlename', sanitize_text_field($data['wcus_billing_middlename'] ?? ''));
        }

        $this->saveWarehouseShipping($order, $data);

        $order->update_meta_data('wcus_data_version', '3');
        $order->save();
    }

    /**
     * @throws \WC_Data_Exception
     */
    public function setState(\WC_Order $order, string $state): void
    {
        $state = sanitize_text_field(wp_unslash($state));
        if ('billing_only' === get_option('woocommerce_ship_to_destination')) {
            $order->set_billing_state($state);
        } else {
            $order->set_shipping_state($state);
        }
    }

    /**
     * @throws \WC_Data_Exception
     */
    public function setCity(\WC_Order $order, string $city): void
    {
        $city = sanitize_text_field(wp_unslash($city));
        if ('billing_only' === get_option('woocommerce_ship_to_destination')) {
            $order->set_billing_city($city);
        } else {
            $order->set_shipping_city($city);
        }
    }

    /**
     * @throws \WC_Data_Exception
     */
    public function setAddress(\WC_Order $order, string $address): void
    {
        $address = sanitize_text_field(wp_unslash($address));
        if ('billing_only' === get_option('woocommerce_ship_to_destination')) {
            $order->set_billing_address_1($address);
        } else {
            $order->set_shipping_address_1($address);
        }
    }

    private function saveWarehouseShipping(\WC_Order $order, array $data): void
    {
        $this->saveCity($order, $data);
        $this->saveWarehouse($order, $data);
    }

    private function saveCity(\WC_Order $order, array $data): void
    {
        $cityName = $data['wcus_rozetka_' . $this->fieldGroup . '_city_name'] ?? '';
        if ($cityName) {
            $this->setCity($order, $cityName);
        }
    }

    private function saveWarehouse(\WC_Order $order, array $data): void
    {
        $warehouseName = $data['wcus_rozetka_' . $this->fieldGroup . '_warehouse_name'] ?? '';
        if ($warehouseName) {
            $this->setAddress($order, $warehouseName);
        }
    }

    private function isShipToDifferentAddress(array $data): bool
    {
        return isset($data['ship_to_different_address'])
            && (int)$data['ship_to_different_address'] === 1;
    }
}
