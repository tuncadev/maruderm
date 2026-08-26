<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Component\SmartyParcel;

use kirillbdev\WCUkrShipping\Api\SmartyParcelWPApi;
use kirillbdev\WCUkrShipping\Factories\ProductFactory;
use kirillbdev\WCUkrShipping\Helpers\SmartyParcelHelper;
use kirillbdev\WCUkrShipping\Helpers\WCUSHelper;
use kirillbdev\WCUkrShipping\Http\Resources\OrderResource;
use kirillbdev\WCUkrShipping\Services\Calculation\ProductDimensionService;

/**
 * Main class to collect order data that used in SmartyParcel Elements SDK for purchase labels
 */
class BaseOrderCollector
{
    protected string $carrierSlug;
    protected array $data = [];
    protected \WC_Order $order;
    protected \WC_Order_Item_Shipping $orderShipping;
    protected ProductDimensionService $productDimensionService;
    protected ProductFactory $productFactory;
    protected SmartyParcelWPApi $smartyParcelApi;
    protected array $defaults = [];

    public function __construct(\WC_Order $order, string $carrierSlug)
    {
        $this->order = $order;
        $this->carrierSlug = $carrierSlug;
        $this->orderShipping = WCUSHelper::getOrderShippingMethod($this->order);

        $this->productFactory = new ProductFactory();
        $this->productDimensionService = wcus_container()->make(ProductDimensionService::class);
        $this->smartyParcelApi = wcus_container()->make(SmartyParcelWPApi::class);
    }

    protected function collectShipToAAddress(): array
    {
        return [];
    }

    public function collect(): array
    {
        $this->data['carrierSlug'] = $this->carrierSlug;

        $this->collectPreparedDefaults();
        $this->data['order'] = $this->collectOrder();
        $this->data['cod'] = $this->collectCOD();
        $this->data['defaults'] = $this->collectDefaults();

        return $this->data;
    }

    protected function collectOrder(): array
    {
        $data = [
            'orderNumber' => $this->order->get_order_number(),
            'placedAt' => $this->order->get_date_created() !== null
                ? $this->order->get_date_created()->format(DATE_ATOM)
                : null,
            'notes' => $this->order->get_customer_note(),
            'shippingMethod' => $this->order->get_shipping_method(),
            'paymentMethod' => $this->order->get_payment_method(),
            'sourcePlatform' => 'woocommerce',
            'total' => $this->getOrderTotal(),
            'subTotal' => (float)$this->order->get_subtotal(),
            'totalFee' => (float)$this->order->get_total_fees(),
            'totalTax' => (float)$this->order->get_total_tax(''),
            'shippingCost' => (float)$this->order->get_shipping_total(),
            'shipTo' => $this->collectShipTo(),
            'currency' => get_woocommerce_currency(),
        ];

        $lineItems = [];
        /** @var \WC_Order_Item_Product $item */
        foreach ($this->order->get_items() as $item) {
            $product = $this->productFactory->makeOrderItemProduct($item);
            $lineItem = [
                'description' => $item->get_name(),
                'quantity' => $item->get_quantity(),
                'sku' => $product->getOriginalProduct()->get_sku(),
                'price' => (float)$product->getOriginalProduct()->get_price(),
                'currency' => get_woocommerce_currency(),
                'externalProductId' => $product->getId(),
                'weight' => [
                    'value' => $product->getWeight(),
                    'unit' => get_option('woocommerce_weight_unit'),
                ],
            ];
            if ($product->getWidth() > 0 && $product->getHeight() > 0 && $product->getLength() > 0) {
                $lineItem['dimensions'] = [
                    'width' => $product->getWidth(),
                    'height' => $product->getHeight(),
                    'length' => $product->getLength(),
                    'unit' => get_option('woocommerce_dimension_unit'),
                ];
            }

            $lineItems[] = $lineItem;
        }
        $data['lineItems'] = $lineItems;

        return $data;
    }

    protected function collectShipTo(): array
    {
        $maybeDifferentAddress = (int)$this->order->get_meta('_wcus_ship_to_different_address') === 1;

        return [
            'name' => trim(sprintf(
                '%s %s %s',
                $maybeDifferentAddress
                    ? $this->order->get_shipping_first_name()
                    : $this->order->get_billing_first_name(),
                $maybeDifferentAddress
                    ? $this->order->get_shipping_last_name()
                    : $this->order->get_billing_last_name(),
                $this->order->get_meta('wcus_middlename') ?? ''
            )),
            'countryCode' => $this->order->get_shipping_country(),
            'city' => $this->order->get_shipping_city(),
            'address1' => $this->order->get_shipping_address_1(),
            'address2' => $this->order->get_shipping_address_2(),
            'postalCode' => $this->order->get_shipping_postcode(),
            'state' => SmartyParcelHelper::getOrderStateName(
                $this->order->get_shipping_country(),
                $this->order->get_shipping_state()
            ),
            'phone' => $maybeDifferentAddress && $this->order->get_meta('wcus_shipping_phone')
                ? $this->order->get_meta('wcus_shipping_phone')
                : $this->order->get_billing_phone(),
            'email' => $this->order->get_billing_email(),
        ];
    }

    protected function getOrderTotal(): float
    {
        return $this->order->get_subtotal() + (float)$this->order->get_total_fees() + (float)$this->order->get_total_tax('') - $this->order->get_total_discount();
    }

    protected function collectPreparedDefaults(): void
    {
        $response = $this->prepareLabelRequest();
        $prepared = $response['result'] ?? [];
        $parcel = $prepared['shipment']['parcels'][0] ?? [];

        $this->defaults = [
            'paid_by' => $prepared['billing']['paid_by'] ?? null,
            'description' => $parcel['description'] ?? 'Order #' . $this->order->get_id(),
            'weight' => [
                'value' => (float)($parcel['weight']['value'] ?? 0.1),
                'unit' => $parcel['weight']['unit'] ?? get_option('woocommerce_weight_unit'),
            ],
            'dimensions' => [
                'width' => (int)($parcel['dimensions']['width'] ?? 10),
                'height' => (int)($parcel['dimensions']['height'] ?? 10),
                'length' => (int)($parcel['dimensions']['length'] ?? 10),
                'unit' => $parcel['dimensions']['unit'] ?? get_option('woocommerce_dimension_unit'),
            ],
            'cod' => $prepared['service_options']['cod'] ?? null,
        ];
    }

    protected function collectCOD(): ?array
    {
        $cod = $this->defaults['cod'] ?? null;
        if ($cod === null) {
            return null;
        }

        return [
            'amount' => (float)($cod['value']['amount'] ?? $this->getOrderTotal()),
            'currency' => $cod['value']['currency'] ?? get_woocommerce_currency(),
        ];
    }

    protected function collectDefaults(): array
    {
        return [
            'weight' => $this->defaults['weight'],
            'dimensions' => $this->defaults['dimensions'],
            'description' => $this->defaults['description'],
            'paidBy' => $this->defaults['paid_by'] ?? 'recipient',
        ];
    }

    protected function prepareLabelRequest(): array
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
