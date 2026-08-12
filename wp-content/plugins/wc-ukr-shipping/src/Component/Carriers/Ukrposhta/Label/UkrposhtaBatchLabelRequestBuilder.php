<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Component\Carriers\Ukrposhta\Label;

use kirillbdev\WCUkrShipping\Component\SmartyParcel\LabelRequestBuilderInterface;
use kirillbdev\WCUkrShipping\Factories\ProductFactory;
use kirillbdev\WCUkrShipping\Foundation\UkrPoshtaShipping;
use kirillbdev\WCUkrShipping\Helpers\SmartyParcelHelper;
use kirillbdev\WCUkrShipping\Helpers\WCUSHelper;
use kirillbdev\WCUkrShipping\Services\Calculation\ProductDimensionService;

class UkrposhtaBatchLabelRequestBuilder implements LabelRequestBuilderInterface
{
    private \WC_Order $order;

    public function __construct(\WC_Order $order)
    {
        $this->order = $order;
    }

    public function build(): array
    {
        $carrierSlug = SmartyParcelHelper::getOrderCarrierSlug($this->order);
        if ($carrierSlug === null) {
            throw new \LogicException('Unable to detect order carrier.');
        }

        $orderShipping = WCUSHelper::getOrderShippingMethod($this->order);
        $shippingMethod = new UkrPoshtaShipping((int)$orderShipping->get_instance_id());

        $labelRequest = [
            'carrier_slug' => $carrierSlug,
            'service_type' => 'ukrposhta_' . strtolower($shippingMethod->get_option('service_type')),
            'billing' => [
                'paid_by' => wc_ukr_shipping_get_option('wcus_ukrposhta_ttn_default_payer'),
                'payment_method' => 'cash',
            ],
            'shipment' => [
                'external_order_id' => (string)$this->order->get_id(),
                'ship_date' => date('Y-m-d'),
            ]
        ];

        if ($this->order->get_meta('_smartyparcel_order_id')) {
            $labelRequest['order_id'] = $this->order->get_meta('_smartyparcel_order_id');
        }

        $labelRequest['shipment']['ship_to'] = $this->buildRecipient($orderShipping);
        $labelRequest['shipment']['parcels'] = $this->buildParcels();
        $labelRequest['service_options'] = [
            'ukrposhta_on_fail_receive' => wc_ukr_shipping_get_option('wcus_ukrposhta_on_fail_receive'),
            'ukrposhta_check_on_delivery' => (int)wc_ukr_shipping_get_option('wcus_ukrposhta_check_on_delivery') === 1,
            'ukrposhta_sms_notification' => (int)wc_ukr_shipping_get_option('wcus_ukrposhta_sms_notification') === 1,
        ];

        $labelRequest = $this->buildCOD($labelRequest);

        return $labelRequest;
    }

    private function buildRecipient(\WC_Order_Item_Shipping $orderShipping): array
    {
        $maybeDifferentAddress = (int)$this->order->get_meta('_wcus_ship_to_different_address') === 1;

        $recipient = [
            'first_name' => $maybeDifferentAddress
                ? $this->order->get_shipping_first_name()
                : $this->order->get_billing_first_name(),
            'last_name' => $maybeDifferentAddress
                ? $this->order->get_shipping_last_name()
                : $this->order->get_billing_last_name(),
            'middle_name' => $this->order->get_meta('wcus_middlename') ?? '',
            'phone' => $maybeDifferentAddress && $this->order->get_meta('wcus_shipping_phone')
                ? $this->order->get_meta('wcus_shipping_phone')
                : $this->order->get_billing_phone(),
            'warehouse_id' => $orderShipping->get_meta('wcus_ukrposhta_warehouse_id'),
        ];

        return [
            'name' => sprintf(
                '%s %s%s',
                $recipient['first_name'],
                $recipient['last_name'],
                $recipient['middle_name']
                    ? ' ' . $recipient['middle_name']
                    : '',
            ),
            'phone' => WCUSHelper::preparePhone($recipient['phone']),
            'country_code' => 'UA',
            'pudo_point_id' => $recipient['warehouse_id'],
        ];
    }

    private function buildParcels(): array
    {
        $factory = new ProductFactory();
        $productDimensionService = wcus_container()->make(ProductDimensionService::class);
        $orderProducts = [];
        foreach ($this->order->get_items() as $item) {
            /** @var \WC_Order_Item_Product $item */
            $orderProducts[] = $factory->makeOrderItemProduct($item);
        }

        $dimensions = $productDimensionService->getTotalDimensions($orderProducts);
        $description = wc_ukr_shipping_get_option('wcus_ttn_description') ?: 'Order #' . $this->order->get_id();
        $description = apply_filters('wcus_ttn_form_description', $description, $this->order);

        return [
            [
                'declared_value' => [
                    'amount' => $this->getDeclaredPrice(),
                    'currency' => 'UAH',
                ],
                'weight' => [
                    'value' => $this->calculateWeight($orderProducts),
                    'unit' => 'kg',
                ],
                'dimensions' => [
                    'width' => $dimensions['width'],
                    'height' => $dimensions['height'],
                    'length' => $dimensions['length'],
                    'unit' => 'cm',
                ],
                'description' => $description,
            ],
        ];
    }

    private function buildCOD(array $labelRequest): array
    {
        $codPaymentId = wc_ukr_shipping_get_option('wcus_cod_payment_id');
        if ($codPaymentId !== $this->order->get_payment_method()) {
            return $labelRequest;
        }

        $labelRequest['service_options']['cod'] = [
            'payment_method' => 'cash',
            'paid_by' => wc_ukr_shipping_get_option('wcus_ukrposhta_cod_payer'),
            'value' => [
                'amount' => $this->getDeclaredPrice(),
                'currency' => 'UAH',
            ]
        ];

        $sender = WCUSHelper::safeGetJsonOption('wcus_ukrposhta_ttn_sender', [
            'type' => 'individual',
            'first_name' => '',
            'last_name' => '',
            'middle_name' => '',
            'company_name' => '',
            'phone' => '',
            'email' => '',
            'tin' => '',
            'iban' => '',
        ]);

        if ($sender['type'] === 'private_entrepreneur') {
            $labelRequest['service_options']['cod']['payment_method'] = 'cash_equivalent';
            $labelRequest['service_options']['cod']['recipient_iban'] = $sender['iban'];
        }

        return $labelRequest;
    }

    private function calculateWeight(array $orderProducts): float
    {
        $defaultWeight = wc_ukr_shipping_get_option('wcus_ttn_weight_default') ?: 0.1;
        $weight = 0;

        foreach ($orderProducts as $product) {
            $weight += $product->getWeight() * $product->getQuantity();
        }

        return max($weight, (float)$defaultWeight);
    }

    private function getDeclaredPrice(): float
    {
        return $this->order->get_subtotal() + (float)$this->order->get_total_fees() + (float)$this->order->get_total_tax('') - $this->order->get_total_discount();
    }
}
