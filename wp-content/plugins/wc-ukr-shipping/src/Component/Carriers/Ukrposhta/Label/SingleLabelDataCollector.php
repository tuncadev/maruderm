<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Component\Carriers\Ukrposhta\Label;

use kirillbdev\WCUkrShipping\Api\SmartyParcelWPApi;
use kirillbdev\WCUkrShipping\Factories\ProductFactory;
use kirillbdev\WCUkrShipping\Foundation\UkrPoshtaShipping;
use kirillbdev\WCUkrShipping\Helpers\WCUSHelper;
use kirillbdev\WCUkrShipping\Services\Calculation\ProductDimensionService;
use kirillbdev\WCUkrShipping\Services\SmartyParcelService;

class SingleLabelDataCollector
{
    private array $data;
    private \WC_Order $order;
    private \WC_Order_Item_Shipping $orderShipping;
    private array $orderProducts;
    private ProductDimensionService $productDimensionService;
    private SmartyParcelService $smartyParcelService;
    private SmartyParcelWPApi $smartyParcelApi;

    public function __construct(\WC_Order $order)
    {
        $this->order = $order;
        $this->orderShipping = WCUSHelper::getOrderShippingMethod($this->order);
        $this->data = [];

        $factory = new ProductFactory();
        $this->productDimensionService = wcus_container()->make(ProductDimensionService::class);
        $this->smartyParcelService = wcus_container()->make(SmartyParcelService::class);
        $this->smartyParcelApi = wcus_container()->make(SmartyParcelWPApi::class);

        foreach ($this->order->get_items() as $item) {
            /** @var \WC_Order_Item_Product $item */
            $product = $factory->makeOrderItemProduct($item);
            $this->orderProducts[] = $product;
        }
    }

    public function collect(): array
    {
        $this->data['carrier'] = 'ukrposhta';

        $this->collectCommonData();
        $this->collectSender();
        $this->collectParcelsData();
        $this->collectRecipient();
        $this->collectAdditionalServices();
        $this->collectCOD();

        return $this->data;
    }

    private function collectCommonData(): void
    {
        $this->data['order_id'] = $this->order->get_id();

        $shippingMethod = new UkrPoshtaShipping((int)$this->orderShipping->get_instance_id());
        $this->data['common']['service_type'] = 'ukrposhta_' . strtolower($shippingMethod->get_option('service_type'));

        $this->data['common']['paid_by'] = wc_ukr_shipping_get_option('wcus_ukrposhta_ttn_default_payer');
        $description = wc_ukr_shipping_get_option('wcus_ttn_description') ?: 'Order #' . $this->order->get_id();
        $this->data['common']['description'] = apply_filters('wcus_ttn_form_description', $description, $this->order);
        $this->data['common']['external_order_id'] = $this->order->get_id();
        $this->data['common']['declared_price'] = $this->getDeclaredPrice();
    }

    private function collectParcelsData(): void
    {
        $dimensions = $this->productDimensionService->getTotalDimensions($this->orderProducts);

        $this->data['common']['parcels'] = [
            [
                'width' => $dimensions['width'],
                'height' => $dimensions['height'],
                'length' => $dimensions['length'],
                'weight' => $this->calculateWeight(),
            ]
        ];
    }

    private function collectSender(): void
    {
        $accounts = $this->smartyParcelService->getCarrierAccounts('ukrposhta');
        $defaultAcc = $accounts[0]['id'] ?? '';
        foreach ($accounts as $account) {
            if ($account['is_default']) {
                $defaultAcc = $account['id'];
                break;
            }
        }

        $this->data['carrier_accounts'] = $accounts;
        $this->data['sender'] = [
            'carrier_account_id' => $defaultAcc,
        ];

        // V2 address flow
        try {
            $this->data['sender']['addresses'] = $this->smartyParcelApi->sendRequest(
                '/v1/addresses',
                null,
                [
                    'carrier_slug' => 'ukrposhta',
                ]
            )['addresses'] ?? [];
        } catch (\Exception $e) {
            $this->data['sender']['addresses'] = [];
        }

        $this->data['sender']['selected_address_id'] = '';
        foreach ($this->data['sender']['addresses'] as $address) {
            if ($address['is_default']) {
                $this->data['sender']['selected_address_id'] = $address['id'];
                break;
            }
        }
    }

    private function collectRecipient(): void
    {
        $maybeDifferentAddress = (int)$this->order->get_meta('_wcus_ship_to_different_address') === 1;
        $shippingMethod = WCUSHelper::getOrderShippingMethod($this->order);

        $this->data['recipient'] = [
            'delivery_type' => 'warehouse',
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
            'city' => [
                'value' => $shippingMethod->get_meta('wcus_ukrposhta_city_id'),
                'name' => $shippingMethod->get_meta('wcus_ukrposhta_city_name') ?: '',
            ],
            'warehouse' => [
                'value' => $shippingMethod->get_meta('wcus_ukrposhta_warehouse_id'),
                'name' => $shippingMethod->get_meta('wcus_ukrposhta_warehouse_name') ?: '',
            ],
            'custom_address' => !in_array($shippingMethod->get_method_id(), [WC_UKR_SHIPPING_NP_SHIPPING_NAME, 'wcus_ukrposhta_shipping'])
                ? sprintf(
                    '%s<br/>%s<br/>%s',
                    $this->order->get_billing_state(),
                    $this->order->get_billing_city(),
                    $this->order->get_billing_address_1()
                )
                : '',
        ];

        if ($shippingMethod->get_method_id() === WCUS_SHIPPING_METHOD_UKRPOSHTA_ADDRESS) {
            $this->data['recipient']['delivery_type'] = 'door';
            $this->data['recipient']['ship_to'] = [
                'country_code' => 'UA',
                'city' => $maybeDifferentAddress
                    ? $this->order->get_shipping_city()
                    : $this->order->get_billing_city(),
                'address_1' => $maybeDifferentAddress
                    ? $this->order->get_shipping_address_1()
                    : $this->order->get_billing_address_1(),
                'address_2' => $maybeDifferentAddress
                    ? $this->order->get_shipping_address_2()
                    : $this->order->get_billing_address_2(),
                'postal_code' => $maybeDifferentAddress
                    ? $this->order->get_shipping_postcode()
                    : $this->order->get_billing_postcode(),
            ];
            $this->data['recipient']['ship_to']['address_full'] = sprintf(
                '%s<br/>%s<br/>%s, %s',
                $this->data['recipient']['ship_to']['country_code'],
                $this->data['recipient']['ship_to']['city'],
                $this->data['recipient']['ship_to']['address_1'],
                $this->data['recipient']['ship_to']['postal_code']
            );
        }
    }

    private function collectAdditionalServices(): void
    {
        $this->data['additional_services'] = [
            'on_fail_receive' => wc_ukr_shipping_get_option('wcus_ukrposhta_on_fail_receive'),
            'check_on_delivery' => (int)wc_ukr_shipping_get_option('wcus_ukrposhta_check_on_delivery') === 1,
            'sms_notification' => (int)wc_ukr_shipping_get_option('wcus_ukrposhta_sms_notification') === 1,
        ];
    }

    private function calculateWeight(): float
    {
        $defaultWeight = wc_ukr_shipping_get_option('wcus_ttn_weight_default') ?: 0.1;
        $weight = 0;

        foreach ($this->orderProducts as $product) {
            $weight += $product->getWeight() * $product->getQuantity();
        }

        return max($weight, (float)$defaultWeight);
    }

    private function getDeclaredPrice(): float
    {
        return $this->order->get_subtotal() + (float)$this->order->get_total_fees() + (float)$this->order->get_total_tax('') - $this->order->get_total_discount();
    }

    private function collectCOD(): void
    {
        $codPaymentId = wc_ukr_shipping_get_option('wcus_cod_payment_id');
        $this->data['cod'] = [
            'active' => $codPaymentId && $codPaymentId === $this->order->get_payment_method(),
            'paid_by' => wc_ukr_shipping_get_option('wcus_ukrposhta_cod_payer'),
            'amount' => $this->getDeclaredPrice(),
        ];
    }
}
