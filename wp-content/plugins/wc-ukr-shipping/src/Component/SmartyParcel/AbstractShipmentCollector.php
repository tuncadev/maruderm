<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Component\SmartyParcel;

use kirillbdev\WCUkrShipping\Factories\ProductFactory;
use kirillbdev\WCUkrShipping\Helpers\WCUSHelper;
use kirillbdev\WCUkrShipping\Services\Calculation\ProductDimensionService;
use kirillbdev\WCUkrShipping\Services\SmartyParcelService;

/**
 * @deprecated
 */
abstract class AbstractShipmentCollector
{
    protected array $data = [];
    protected \WC_Order $order;
    protected string $carrierSlug;
    protected \WC_Order_Item_Shipping $orderShipping;
    protected array $orderProducts;
    protected ProductDimensionService $productDimensionService;
    protected SmartyParcelService $smartyParcelService;

    public function __construct(\WC_Order $order, string $carrierSlug)
    {
        $this->order = $order;
        $this->carrierSlug = $carrierSlug;
        $this->orderShipping = WCUSHelper::getOrderShippingMethod($this->order);

        $factory = new ProductFactory();
        $this->productDimensionService = wcus_container()->make(ProductDimensionService::class);
        $this->smartyParcelService = wcus_container()->make(SmartyParcelService::class);

        foreach ($this->order->get_items() as $item) {
            /** @var \WC_Order_Item_Product $item */
            $product = $factory->makeOrderItemProduct($item);
            $this->orderProducts[] = $product;
        }
    }

    public function collect(): array
    {
        $this->data['carrier_slug'] = $this->carrierSlug;
        $this->data['order_id'] = $this->order->get_id();
        $this->collectCarrierAccounts();
        $this->collectBilling();
        $this->collectShipment();
        $this->collectServiceOptions();

        return $this->data;
    }

    abstract protected function collectShipToAAddress(): array;

    protected function collectBilling(): void
    {
        $this->data['billing'] = [
            'paid_by' => 'recipient',
            'payment_method' => 'cash',
        ];
    }

    protected function collectShipment(): void
    {
        $this->data['shipment'] = [
            'external_order_id' => (string)$this->order->get_id(),
            'ship_date' => date('Y-m-d'),
            'parcels' => $this->collectParcels(),
        ];

        $recipient = $this->getRecipientInfo();
        $this->data['shipment']['ship_to'] = array_merge([
            'name' => $recipient['first_name'] . ' ' . $recipient['last_name'] . ' ' . $recipient['middle_name'],
            'phone' => WCUSHelper::preparePhone($recipient['phone']),
            'email' => $recipient['email'],
        ], $this->collectShipToAAddress());
    }

    protected function collectServiceOptions(): void
    {
        $this->data['service_options'] = [];
        $this->collectCOD();
    }

    protected function collectParcels(): array
    {
        $dimensions = $this->productDimensionService->getTotalDimensions($this->orderProducts);
        $description = wc_ukr_shipping_get_option('wcus_ttn_description') ?: 'Order #' . $this->order->get_id();

        return [
            [
                'declared_value' => [
                    'amount' => $this->getDeclaredPrice(),
                    'currency' => 'UAH',
                ],
                'weight' => [
                    'value' => $this->calculateWeight(),
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

    private function collectCarrierAccounts(): void
    {
        $accounts = $this->smartyParcelService->getCarrierAccounts($this->carrierSlug);

        $this->data['carrier_accounts'] = $accounts;
        $this->data['carrier_account_id'] = $accounts[0]['id'] ?? '';;
    }

    private function getRecipientInfo(): array
    {
        $maybeDifferentAddress = (int)$this->order->get_meta('_wcus_ship_to_different_address') === 1;

        return [
            'first_name' => $maybeDifferentAddress
                ? $this->order->get_shipping_first_name()
                : $this->order->get_billing_first_name(),
            'last_name' => $maybeDifferentAddress
                ? $this->order->get_shipping_last_name()
                : $this->order->get_billing_last_name(),
            'middle_name' => $this->order->get_meta('wcus_middlename') ?? '',
            'city' => $this->order->get_shipping_city(),
            'address_1' => $this->order->get_shipping_address_1(),
            'phone' => $maybeDifferentAddress && $this->order->get_meta('wcus_shipping_phone')
                ? $this->order->get_meta('wcus_shipping_phone')
                : $this->order->get_billing_phone(),
            'email' => $this->order->get_billing_email(),
        ];
    }

    protected function calculateWeight(): float
    {
        $defaultWeight = wc_ukr_shipping_get_option('wcus_ttn_weight_default') ?: 0.1;
        $weight = 0;

        foreach ($this->orderProducts as $product) {
            $weight += $product->getWeight() * $product->getQuantity();
        }

        return max($weight, (float)$defaultWeight);
    }

    protected function getDeclaredPrice(): float
    {
        return $this->order->get_subtotal() + (float)$this->order->get_total_fees() + (float)$this->order->get_total_tax('') - $this->order->get_total_discount();
    }

    protected function collectCOD(): void
    {
        $codPaymentId = wc_ukr_shipping_get_option('wcus_cod_payment_id');
        if ($codPaymentId && $codPaymentId === $this->order->get_payment_method()) {
            $this->data['service_options']['cod'] = [
                'paid_by' => 'recipient',
                'payment_method' => 'cash',
                'value' => [
                    'amount' => $this->getDeclaredPrice(),
                    'currency' => 'UAH',
                ]
            ];
        }
    }
}
