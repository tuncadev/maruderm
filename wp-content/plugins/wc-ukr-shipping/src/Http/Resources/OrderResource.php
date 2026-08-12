<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Http\Resources;

use kirillbdev\WCUkrShipping\Helpers\SmartyParcelHelper;
use kirillbdev\WCUkrShipping\Helpers\WCUSHelper;

class OrderResource
{
    private \WC_Order $order;

    public function __construct(\WC_Order $order)
    {
        $this->order = $order;
    }

    public function toArray(): array
    {
        $order = $this->order;
        $orderShipping = WCUSHelper::getOrderShippingMethod($order);
        $billingOnly = 'billing_only' === get_option('woocommerce_ship_to_destination');

        $orderPayload = [
            'order_number' => $order->get_order_number(),
            'external_id' => (string)$order->get_id(),
            'currency' => $order->get_currency(),
            'source_status' => $order->get_status(),
            'order_total' => $order->get_total(),
            'shipping_total' => (float)$order->get_shipping_total(''),
            'tax_total' => $order->get_total_tax(),
            'discount_total' => $order->get_total_discount(),
            'subtotal' => $order->get_subtotal(),
            'shipping_carrier' => SmartyParcelHelper::getOrderCarrierSlug($order),
            'shipping_method_id' => $orderShipping ? $orderShipping->get_method_id() : null,
            'shipping_method_name' => $orderShipping ? $orderShipping->get_method_title() : null,
            'payment_method_id' => $order->get_payment_method(),
            'payment_method_name' => $order->get_payment_method_title(),
            'source_created_at' => $order->get_date_created() !== null
                ? $order->get_date_created()->format('Y-m-d H:i:s')
                : date('Y-m-d H:i:s'),
            'source_updated_at' => $order->get_date_modified() !== null
                ? $order->get_date_modified()->format('Y-m-d H:i:s')
                : date('Y-m-d H:i:s'),
            'to_address' => [
                'name' => $billingOnly
                    ? $order->get_billing_first_name() . ' ' . $order->get_billing_last_name()
                    : $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name(),
                'email' => $order->get_billing_email(),
                'phone' => $order->get_billing_phone(),
                'country_code' => $billingOnly ? $order->get_billing_country() : $order->get_shipping_country(),
                'pudo_point_id' => $orderShipping ? $this->getPudoPointId($orderShipping) : null,
                'city' => $billingOnly ? $order->get_billing_city() : $order->get_shipping_city(),
                'state' => $billingOnly ? $order->get_billing_state() : $order->get_shipping_state(),
                'address_1' => $billingOnly ? $order->get_billing_address_1() : $order->get_shipping_address_1(),
                'address_2' => $billingOnly ? $order->get_billing_address_2() : $order->get_shipping_address_2(),
                'postal_code' => $billingOnly ? $order->get_billing_postcode() : $order->get_shipping_postcode(),
            ],
            'line_items' => []
        ];

        // Collect line items
        /** @var \WC_Order_Item_Product $item */
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            $orderPayload['line_items'][] = [
                'description' => $item->get_name(),
                'quantity' => $item->get_quantity(),
                'price' => (float)$item->get_total() / $item->get_quantity(),
                'currency' => $order->get_currency(),
                'weight' => $product ? (float)$product->get_weight() : 0.0,
                'weight_unit' => get_option('woocommerce_weight_unit', 'kg'),
                'sku' => $product ? $product->get_sku() : '',
            ];
        }

        return $orderPayload;
    }

    private function getPudoPointId(\WC_Order_Item_Shipping $orderShipping): ?string
    {
        switch ($orderShipping->get_method_id()) {
            case WC_UKR_SHIPPING_NP_SHIPPING_NAME:
                return $orderShipping->get_meta('wcus_warehouse_ref');
            case WCUS_SHIPPING_METHOD_UKRPOSHTA:
                return $orderShipping->get_meta('wcus_ukrposhta_warehouse_id');
            case WCUS_SHIPPING_METHOD_ROZETKA:
                return $orderShipping->get_meta('wcus_rozetka_warehouse_id');
            case WCUS_SHIPPING_METHOD_NOVA_POST:
                return $orderShipping->get_meta('wcus_nova_post_warehouse_id');
            case WCUS_SHIPPING_METHOD_MEEST:
                return $orderShipping->get_meta('wcus_pudo_point_id');
        }

        return null;
    }
}
