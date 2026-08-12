<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Component\SmartyParcel;

use kirillbdev\WCUkrShipping\Factories\ProductFactory;
use kirillbdev\WCUkrShipping\Helpers\SmartyParcelHelper;
use kirillbdev\WCUkrShipping\Helpers\WCUSHelper;
use kirillbdev\WCUkrShipping\Services\Calculation\ProductDimensionService;

class OrderLabelRequestBuilder implements LabelRequestBuilderInterface
{
    private \WC_Order $order;
    private ProductDimensionService $productDimensionService;

    public function __construct(\WC_Order $order)
    {
        $this->order = $order;
        $this->productDimensionService = wcus_container()->make(ProductDimensionService::class);
    }

    public function build(): array
    {
        $order = $this->order;

        $carrierSlug = SmartyParcelHelper::getOrderCarrierSlug($order);
        if ($carrierSlug === null) {
            throw new \LogicException('Unable to detect order carrier.');
        }

        $shipTo = [
            'name' => trim(sprintf(
                '%s %s',
                $order->get_billing_first_name(),
                $order->get_billing_last_name(),
            )),
            'phone' => WCUSHelper::preparePhone($order->get_billing_phone()),
            'email' => $order->get_billing_email(),
        ];
        $orderShipping = WCUSHelper::getOrderShippingMethod($order);
        if ($orderShipping === null) {
            throw new \LogicException('Internal Error. Order shipping method not set');
        } elseif (!empty($orderShipping->get_meta('wcus_address'))) {
            throw new \LogicException('Internal Error. Unable to parse shipping address');
        }

        if (empty($orderShipping->get_meta('wcus_settlement_ref'))) {
            // Service point shipping
            $shipTo['country_code'] = 'UA';
            $shipTo['carrier_city_id'] = $orderShipping->get_meta('wcus_city_ref');
            $shipTo['carrier_warehouse_id'] = $orderShipping->get_meta('wcus_warehouse_ref');
        } else {
            // Doors shipping
            $shipTo['country_code'] = 'UA';
            $shipTo['city'] = $orderShipping->get_meta('wcus_settlement_name');
            $shipTo['state'] = $orderShipping->get_meta('wcus_settlement_area');
            $shipTo['district'] = $orderShipping->get_meta('wcus_settlement_region');
            $shipTo['address_1'] = $orderShipping->get_meta('wcus_street_name');
            $shipTo['address_2'] = $orderShipping->get_meta('wcus_house');
            $shipTo['address_3'] = $orderShipping->get_meta('wcus_flat');
        }

        $payerType = apply_filters(
            'wcus_ttn_form_payer_type',
            wc_ukr_shipping_get_option('wc_ukr_shipping_np_ttn_payer_default'),
            $this->order
        );
        if (!in_array($payerType, ['Sender', 'Recipient'], true)) {
            throw new \InvalidArgumentException("Invalid param `payerType`");
        }

        $paymentMethod = apply_filters(
            'wcus_ttn_form_payment_method',
            wc_ukr_shipping_get_option('wcus_np_payment_method_default'),
            $this->order
        );
        if (!in_array($paymentMethod, ['Cash', 'NonCash'], true)) {
            throw new \InvalidArgumentException("Invalid param 'paymentMethod'");
        }

        $date = apply_filters('wcus_ttn_form_date', new \DateTime(), $this->order);
        if (!($date instanceof \DateTimeInterface)) {
            throw new \InvalidArgumentException("Parameter 'date' must be correct date");
        }

        $labelRequest = [
            'carrier_slug' => $carrierSlug,
            'billing' => [
                'paid_by' => strtolower($payerType),
                'payment_method' => $paymentMethod === 'NonCash'
                    ? 'card'
                    : 'cash',
            ],
            'shipment' => [
                'ship_date' => $date->format('Y-m-d'),
                'ship_to' => $shipTo,
            ]
        ];

        if ($order->get_meta('_smartyparcel_order_id')) {
            $labelRequest['order_id'] = $order->get_meta('_smartyparcel_order_id');
        }

        $factory = new ProductFactory();
        $orderProducts = [];
        foreach ($this->order->get_items() as $item) {
            /** @var \WC_Order_Item_Product $item */
            $product = $factory->makeOrderItemProduct($item);
            $orderProducts[] = $product;
        }

        $defaultWeight = wc_ukr_shipping_get_option('wcus_ttn_weight_default') ?: 0.1;
        $weight = 0;
        foreach ($orderProducts as $product) {
            $weight += $product->getWeight() * $product->getQuantity();
        }
        $weight = max($weight, (float)$defaultWeight);
        $description = wc_ukr_shipping_get_option('wcus_ttn_description') ?: 'Order #' . $order->get_id();

        // Parcels
        $labelRequest['shipment']['parcels'] = [
            [
                'insurance_cost' => apply_filters('wcus_ttn_form_cost', $this->getOrderCost($order), $order),
                'weight' => [
                    'value' => $weight,
                    'unit' => 'kg',
                ],
                'description' => apply_filters('wcus_ttn_form_description', $description, $order),
            ]
        ];

        $warehouseName = $orderShipping->get_meta('wcus_warehouse_name');
        if (is_string($warehouseName)) {
            if (preg_match('/(почтомат|поштомат)/', mb_strtolower($warehouseName))) {
                $dimensions = apply_filters(
                    'wcus_ttn_form_dimensions',
                    $this->productDimensionService->getTotalDimensions($orderProducts),
                    $this->order
                );

                $labelRequest['shipment']['parcels'][0]['dimensions'] = [
                    'width' => $dimensions['width'],
                    'height' => $dimensions['height'],
                    'length' => $dimensions['length'],
                    'unit' => 'cm',
                ];
            }
        }

        $labelRequest['shipment']['external_order_id'] = apply_filters('wcus_ttn_form_barcode', (string)$order->get_id(), $order);

        $additional = apply_filters('wcus_ttn_form_additional', '', $order);
        if (!empty($additional)) {
            $labelRequest['custom_fields']['additional_information'] = $additional;
        }

        $needPaymentControl = (int)wc_ukr_shipping_get_option('wcus_ttn_pay_control_default') === 1;
        $codPaymentId = wc_ukr_shipping_get_option('wcus_cod_payment_id');
        if ($codPaymentId && $codPaymentId === $this->order->get_payment_method()) {
            if ($needPaymentControl) {
                $labelRequest['service_options']['cod'] = [
                    'payment_method' => 'cash_equivalent',
                    'value' => [
                        'amount' => apply_filters('wcus_ttn_form_payment_control_cost', $this->getOrderCost($order), $order),
                        'currency' => 'UAH',
                    ],
                ];
            } else {
                $labelRequest['service_options']['cod'] = [
                    'payment_method' => 'cash',
                    'value' => [
                        'amount' => apply_filters('wcus_ttn_form_cod_cost', $this->getOrderCost($order), $order),
                        'currency' => 'UAH',
                    ],
                    'options' => [
                        'nova_poshta_cod_payer' => 'recipient',
                    ]
                ];
            }
        }

        return $labelRequest;
    }

    private function getOrderCost(\WC_Order $order): float
    {
        return $order->get_subtotal() + (float)$order->get_total_fees() + (float)$order->get_total_tax('') - $order->get_total_discount();
    }
}
