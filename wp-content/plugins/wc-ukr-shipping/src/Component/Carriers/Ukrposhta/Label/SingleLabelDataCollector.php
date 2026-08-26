<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Component\Carriers\Ukrposhta\Label;

use kirillbdev\WCUkrShipping\Api\SmartyParcelWPApi;
use kirillbdev\WCUkrShipping\Factories\ProductFactory;
use kirillbdev\WCUkrShipping\Helpers\WCUSHelper;
use kirillbdev\WCUkrShipping\Http\Resources\OrderResource;
use kirillbdev\WCUkrShipping\Services\Calculation\ProductDimensionService;
use kirillbdev\WCUkrShipping\Services\SmartyParcelService;

class SingleLabelDataCollector
{
    private array $data;
    private \WC_Order $order;
    private array $orderProducts;
    private ProductDimensionService $productDimensionService;
    private SmartyParcelService $smartyParcelService;
    private SmartyParcelWPApi $smartyParcelApi;
    private array $defaults;

    public function __construct(\WC_Order $order)
    {
        $this->order = $order;
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

        $this->collectDefaults();
        $this->collectCommonData();
        $this->collectSender();
        $this->collectParcelsData();
        $this->collectRecipient();
        $this->collectAdditionalServices();
        $this->collectCOD();

        return $this->data;
    }

    private function collectDefaults(): void
    {
        $response = $this->prepareLabelRequest();
        $prepared = $response['result'];
        $parcel = $prepared['shipment']['parcels'][0];

        $this->defaults = [
            'service_type' => $prepared['service_type'],
            'paid_by' => $prepared['billing']['paid_by'],
            'payment_method' => $prepared['billing']['payment_method'] === 'cash' ? 'Cash' : 'NonCash',
            'ship_date' => new \DateTime($prepared['shipment']['ship_date']),
            'description' => $parcel['description'] ?? 'Order #' . $this->order->get_id(),
            'weight' => $parcel['weight']['value'] ?? 0.1,
            'declared_value' => $parcel['declared_value']['amount'],
            'dimensions' => [
                'width' => $parcel['dimensions']['width'] ?? 10,
                'height' => $parcel['dimensions']['height'] ?? 10,
                'length' => $parcel['dimensions']['length'] ?? 10,
            ],
            'carrier_account_id' => $prepared['carrier_account_id'] ?? '',
            'ship_from_address_id' => $prepared['shipment']['ship_from_address_id'] ?? '',
            'cod' => $prepared['service_options']['cod'] ?? null,
            'service_options' => $prepared['service_options'] ?? [],
        ];
    }

    private function collectCommonData(): void
    {
        $this->data['order_id'] = $this->order->get_id();
        $this->data['common']['service_type'] = $this->defaults['service_type'];

        $this->data['common']['paid_by'] = $this->defaults['paid_by'];
        $description = $this->defaults['description'] ?? 'Order #' . $this->order->get_id();
        $this->data['common']['description'] = apply_filters('wcus_ttn_form_description', $description, $this->order);
        $this->data['common']['external_order_id'] = $this->order->get_id();
        $this->data['common']['declared_price'] = $this->defaults['declared_value'];
    }

    private function collectParcelsData(): void
    {
        $dimensions = $this->productDimensionService->getTotalDimensions($this->orderProducts, false);
        if ($dimensions === null) {
            $dimensions = $this->defaults['dimensions'];
        }

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

        $this->data['carrier_accounts'] = $accounts;
        $this->data['sender'] = [
            'carrier_account_id' => $this->defaults['carrier_account_id'],
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

        $this->data['sender']['selected_address_id'] = $this->defaults['ship_from_address_id'];
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
            'on_fail_receive' => $this->defaults['service_options']['ukrposhta_on_fail_receive'] ?? 'return',
            'check_on_delivery' => (bool)($this->defaults['service_options']['ukrposhta_check_on_delivery'] ?? 0),
            'sms_notification' => (bool)($this->defaults['service_options']['ukrposhta_sms_notification'] ?? 0),
        ];
    }

    private function calculateWeight(): float
    {
        $defaultWeight = $this->defaults['weight'];
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
        $this->data['cod'] = [
            'active' => $this->defaults['cod'] !== null,
            'paid_by' => $this->defaults['cod']['paid_by'] ?? 'recipient',
            'amount' => $this->defaults['cod']['value']['amount'] ?? 0,
        ];
    }

    private function prepareLabelRequest(): array
    {
        try {
            $orderPayload = (new OrderResource($this->order))->toArray();

            return $this->smartyParcelApi->sendRequest('/v1/labels/prepare', [
                'order' => $orderPayload,
            ]);
        } catch (\Throwable $e) {
            throw new \Exception('Unable to prepare label request. Please try again.');
        }
    }
}
