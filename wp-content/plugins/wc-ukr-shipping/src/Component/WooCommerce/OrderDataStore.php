<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Component\WooCommerce;

use kirillbdev\WCUkrShipping\Helpers\WCUSHelper;

final class OrderDataStore
{
    private const GROUP_META = 'meta';
    private const GROUP_SHIPPING_META = 'shippingMeta';

    private array $keyMap = [
        'billing_state' => 'set_billing_state',
        'billing_city' => 'set_billing_city',
        'billing_address_1' => 'set_billing_address_1',
        'shipping_state' => 'set_shipping_state',
        'shipping_city' => 'set_shipping_city',
        'shipping_address_1' => 'set_shipping_address_1',
    ];

    private \WC_Order $order;
    private ?\WC_Order_Item_Shipping $orderShipping;

    public function __construct(\WC_Order $order)
    {
        $this->order = $order;
        $this->orderShipping = WCUSHelper::getOrderShippingMethod($order);
    }

    public function save(array $unitOfWork): void
    {
        $this->doDeleteWork($unitOfWork['delete'] ?? []);
        $this->doUpdateWork($unitOfWork['update'] ?? []);
        $this->commitChanges();
    }

    private function doDeleteWork(array $data): void
    {
        foreach ($data as $key) {
            $keyParts = explode('.', $key);
            if (count($keyParts) === 2) {
                $realKey = $keyParts[1];
                // Delegate saving data to sub function
                if ($keyParts[0] === self::GROUP_META) {
                    $this->deleteOrderMeta($realKey);
                } elseif ($keyParts[0] === self::GROUP_SHIPPING_META) {
                    $this->deleteOrderShippingMeta($realKey);
                }
            }
        }
    }

    private function doUpdateWork(array $data): void
    {
        foreach ($data as $key => $value) {
            $sanitizedValue = sanitize_text_field(wp_unslash($value));

            $keyParts = explode('.', $key);
            if (count($keyParts) === 2) {
                $realKey = $keyParts[1];
                // Delegate saving data to sub function
                if ($keyParts[0] === self::GROUP_META) {
                    $this->saveOrderMeta($realKey, $sanitizedValue);
                } elseif ($keyParts[0] === self::GROUP_SHIPPING_META) {
                    $this->saveOrderShippingMeta($realKey, $sanitizedValue);
                }

                // Skip all unknown keys
                continue;
            }

            if (!isset($this->keyMap[$key]) || !method_exists($this->order, $this->keyMap[$key])) {
                continue;
            }

            $fn = $this->keyMap[$key];
            $this->order->$fn($sanitizedValue);
        }
    }

    private function deleteOrderMeta(string $key): void
    {
        $this->order->delete_meta_data($key);
    }

    private function deleteOrderShippingMeta(string $key): void
    {
        if ($this->orderShipping === null) {
            return;
        }

        $this->orderShipping->delete_meta_data($key);
    }

    private function saveOrderMeta(string $key, $value): void
    {
        $this->order->update_meta_data($key, $value);
    }

    private function saveOrderShippingMeta(string $key, $value): void
    {
        if ($this->orderShipping === null) {
            return;
        }

        $this->orderShipping->update_meta_data($key, $value);
    }

    private function commitChanges(): void
    {
        if ($this->orderShipping !== null) {
            $this->orderShipping->save();
        }
        $this->order->save();
    }
}
