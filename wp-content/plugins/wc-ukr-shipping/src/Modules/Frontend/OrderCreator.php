<?php

namespace kirillbdev\WCUkrShipping\Modules\Frontend;

use kirillbdev\WCUkrShipping\Contracts\Order\OrderHandlerInterface;
use kirillbdev\WCUkrShipping\Contracts\Order\OrderShippingHandlerInterface;
use kirillbdev\WCUSCore\Contracts\ModuleInterface;
use kirillbdev\WCUkrShipping\Component\Carriers\NovaPoshta\Order\CheckoutOrderHandler as NovaPoshtaCheckoutOrderHandler;
use kirillbdev\WCUkrShipping\Component\Carriers\NovaPoshta\Order\CheckoutOrderShippingHandler as NovaPoshtaCheckoutOrderShippingHandler;
use kirillbdev\WCUkrShipping\Component\Carriers\Ukrposhta\Order\CheckoutOrderHandler as UkrposhtaCheckoutOrderHandler;
use kirillbdev\WCUkrShipping\Component\Carriers\Ukrposhta\Order\CheckoutOrderShippingHandler as UkrposhtaCheckoutOrderShippingHandler;
use kirillbdev\WCUkrShipping\Component\Carriers\RozetkaDelivery\Order\CheckoutOrderHandler as RozetkaCheckoutOrderHandler;
use kirillbdev\WCUkrShipping\Component\Carriers\RozetkaDelivery\Order\CheckoutOrderShippingHandler as RozetkaCheckoutOrderShippingHandler;
use kirillbdev\WCUkrShipping\Component\Carriers\NovaPost\Order\CheckoutOrderHandler as NovaPostCheckoutOrderHandler;
use kirillbdev\WCUkrShipping\Component\Carriers\NovaPost\Order\CheckoutOrderShippingHandler as NovaPostCheckoutOrderShippingHandler;
use kirillbdev\WCUkrShipping\Component\Carriers\Meest\Order\CheckoutOrderHandler as MeestCheckoutOrderHandler;

if ( ! defined('ABSPATH')) {
    exit;
}

class OrderCreator implements ModuleInterface
{
    public function init(): void
    {
        add_action('woocommerce_thankyou', [ $this, 'handleSyncOrder' ]);
        add_action('woocommerce_checkout_create_order', [ $this, 'createOrder' ]);
        add_action('woocommerce_checkout_create_order_shipping_item', [ $this, 'saveOrderShipping' ]);

        if (is_admin()) {
            add_action('woocommerce_new_order', [ $this, 'handleSyncOrder' ]);
        }
    }

    public function createOrder(\WC_Order $order): void
    {
        $handler = $this->createOrderHandler($order);
        if ($handler !== null) {
            $handler->saveShippingData($order, $_POST);
        }
    }

    public function handleSyncOrder(int $orderId): void
    {
        wp_schedule_single_event(time(), 'wcus_smartyparcel_sync_order', [$orderId]);
    }

    /**
     * @param \WC_Order_Item_Shipping $item
     */
    public function saveOrderShipping($item)
    {
        $handler = $this->createOrderShippingHandler($item->get_method_id());
        if ($handler !== null) {
            $handler->saveShippingData($item, $_POST);
        }
    }

    private function createOrderHandler(\WC_Order $order): ?OrderHandlerInterface
    {
        switch (true) {
            case $order->has_shipping_method(WC_UKR_SHIPPING_NP_SHIPPING_NAME):
                return new NovaPoshtaCheckoutOrderHandler();
            case $order->has_shipping_method(WCUS_SHIPPING_METHOD_UKRPOSHTA):
                return new UkrposhtaCheckoutOrderHandler();
            case $order->has_shipping_method(WCUS_SHIPPING_METHOD_ROZETKA):
                return new RozetkaCheckoutOrderHandler();
            case $order->has_shipping_method(WCUS_SHIPPING_METHOD_NOVA_POST):
                return new NovaPostCheckoutOrderHandler();
            case $order->has_shipping_method(WCUS_SHIPPING_METHOD_MEEST):
                return new MeestCheckoutOrderHandler();
            default:
                return null;
        }
    }

    private function createOrderShippingHandler(string $shippingMethod): ?OrderShippingHandlerInterface
    {
        $fieldGroup = $this->isShipToDifferentAddress() ? 'shipping' : 'billing';
        switch ($shippingMethod) {
            case WC_UKR_SHIPPING_NP_SHIPPING_NAME:
                return new NovaPoshtaCheckoutOrderShippingHandler($fieldGroup);
            case WCUS_SHIPPING_METHOD_UKRPOSHTA:
                return new UkrposhtaCheckoutOrderShippingHandler($fieldGroup);
            case WCUS_SHIPPING_METHOD_ROZETKA:
                return new RozetkaCheckoutOrderShippingHandler($fieldGroup);
            case WCUS_SHIPPING_METHOD_NOVA_POST:
                return new NovaPostCheckoutOrderShippingHandler($fieldGroup);
        }

        return null;
    }

    private function isShipToDifferentAddress(): bool
    {
        return isset($_POST['ship_to_different_address'])
            && (int)$_POST['ship_to_different_address'] === 1;
    }
}
