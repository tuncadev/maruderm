<?php

namespace kirillbdev\WCUkrShipping\Model\Document;

use kirillbdev\WCUkrShipping\Address\Provider\AddressProviderInterface;
use kirillbdev\WCUkrShipping\Api\SmartyParcelWPApi;
use kirillbdev\WCUkrShipping\Factories\ProductFactory;
use kirillbdev\WCUkrShipping\Helpers\WCUSHelper;
use kirillbdev\WCUkrShipping\Http\Resources\OrderResource;
use kirillbdev\WCUkrShipping\Model\OrderProduct;
use kirillbdev\WCUkrShipping\Services\Calculation\ProductDimensionService;
use kirillbdev\WCUkrShipping\Services\SmartyParcelService;
use kirillbdev\WCUkrShipping\Services\TranslateService;

if ( ! defined('ABSPATH')) {
    exit;
}

class TTNStore
{
    /**
     * @var \WC_Order
     */
    private $order;

    /**
     * @var TranslateService
     */
    private $translateService;

    /**
     * @var \WC_Order_Item_Shipping
     */
    private $orderShipping;

    /**
     * @var OrderProduct[]
     */
    private $orderProducts = [];

    /**
     * @var array
     */
    private $data = [];

    private array $defaults = [];

    private ProductDimensionService $productDimensionService;
    private SmartyParcelService $smartyParcelService;
    private SmartyParcelWPApi $smartyParcelApi;

    public function __construct(int $orderId)
    {
        $this->order = wc_get_order($orderId);
        if ( ! $this->order) {
            throw new \InvalidArgumentException('Order #' . sanitize_text_field($orderId) . ' not found.');
        }

        $this->translateService = new TranslateService();
        $this->orderShipping = WCUSHelper::getOrderShippingMethod($this->order);

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

    public function collect()
    {
        $this->data['carrier'] = 'nova_poshta';

        $this->collectDefaults();
        $this->collectCommonData();
        $this->collectSeatsData();
        $this->calculateCost();
        $this->collectBackwardDelivery();
        $this->collectPaymentControl();
        $this->collectSender();
        $this->collectRecipient();
        $this->collectHelpers();

        return apply_filters('wcus_collect_ttn_form', $this->data, $this->order);
    }

    private function collectDefaults(): void
    {
        $response = $this->prepareLabelRequest();
        $prepared = $response['result'];
        $parcel = $prepared['shipment']['parcels'][0];

        $this->defaults = [
            'paid_by' => ucfirst($prepared['billing']['paid_by']),
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
        ];
    }

    private function collectCommonData(): void
    {
        // Payer
        $payerType = apply_filters(
            'wcus_ttn_form_payer_type',
            $this->defaults['paid_by'],
            $this->order
        );
        if (!in_array($payerType, ['Sender', 'Recipient'], true)) {
            throw new \InvalidArgumentException("Invalid param `payerType`");
        }

        // Payment method
        $paymentMethodValue = $this->defaults['payment_method'];
        $paymentMethod = apply_filters(
            'wcus_ttn_form_payment_method',
            $paymentMethodValue,
            $this->order
        );
        if (!in_array($paymentMethod, ['Cash', 'NonCash'], true)) {
            throw new \InvalidArgumentException("Invalid param 'paymentMethod'");
        }

        $date = apply_filters('wcus_ttn_form_date', $this->defaults['ship_date'], $this->order);
        if (!($date instanceof \DateTimeInterface)) {
            throw new \InvalidArgumentException("Parameter 'date' must be correct date");
        }

        $globalParams = (int)wc_ukr_shipping_get_option('wcus_ttn_global_params_default') === 1;
        $this->data['ttn'] = [
            'order_id' => $this->order->get_id(),
            'payer_type' => $payerType,
            'payment_method' => $paymentMethod,
            'global_params' => apply_filters('wcus_ttn_form_global_params', $globalParams, $this->order),
            'seats_amount' => apply_filters('wcus_ttn_form_seats_amount', 1, $this->order),
            'weight' => $this->calculateWeight(),
            'volumetric_weight' => apply_filters('wcus_ttn_form_volumetric_weight', '', $this->order),
            'date' => $date->format('Y-m-d'),
            'description' => apply_filters('wcus_ttn_form_description', $this->defaults['description'], $this->order),
            'barcode' => apply_filters('wcus_ttn_form_barcode', $this->order->get_id(), $this->order),
            'additional' => apply_filters('wcus_ttn_form_additional', '', $this->order)
        ];
    }

    private function collectSeatsData(): void
    {
        $dimensions = $this->productDimensionService->getTotalDimensions($this->orderProducts, false);
        if ($dimensions === null) {
            $dimensions = $this->defaults['dimensions'];
        }
        $dimensions = apply_filters('wcus_ttn_form_dimensions', $dimensions, $this->order);

        $this->data['ttn']['seats'] = [
            [
                'id' => 0,
                'width' => $dimensions['width'],
                'height' => $dimensions['height'],
                'length' => $dimensions['length'],
                'weight' => $this->calculateWeight(),
                'special' => 0
            ]
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

    private function calculateCost(): void
    {
        $this->data['ttn']['cost'] = apply_filters(
            'wcus_ttn_form_cost',
            $this->defaults['declared_value'],
            $this->order
        );
    }

    private function collectSender(): void
    {
        $accounts = $this->smartyParcelService->getCarrierAccounts('nova_poshta');

        $this->data['sender']['carrier_accounts'] = $accounts;
        $this->data['sender']['carrier_account_id'] = $this->defaults['carrier_account_id'];
        $this->data['sender']['area_ref'] = '';
        $this->data['sender']['ship_from_source'] = 'smarty_parcel';

        try {
            $this->data['sender']['addresses'] = $this->smartyParcelApi->sendRequest(
                '/v1/addresses',
                null,
                [
                    'carrier_slug' => 'nova_poshta',
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
        $data = [];
        $data['firstname'] = $maybeDifferentAddress
            ? $this->order->get_shipping_first_name()
            : $this->order->get_billing_first_name();

        $data['lastname'] = $maybeDifferentAddress
            ? $this->order->get_shipping_last_name()
            : $this->order->get_billing_last_name();

        $data['middlename'] = $this->order->get_meta('wcus_middlename') ?? '';

        $data['phone'] = $this->order->get_billing_phone();
        if ($maybeDifferentAddress && $this->order->get_meta('wcus_shipping_phone')) {
            $data['phone'] = $this->order->get_meta('wcus_shipping_phone');
        }
        $data['email'] = $this->order->get_billing_email();

        $this->data['recipient']['firstname'] = $data['firstname'];
        $this->data['recipient']['lastname'] = $data['lastname'];
        $this->data['recipient']['middlename'] = $data['middlename'];
        $this->data['recipient']['phone'] = $data['phone'];
        $this->data['recipient']['email'] = $data['email'];
        $this->data['recipient']['type'] = 'private_person';
        $this->data['recipient']['address_instructions'] = '';

        $shippingAddress = $this->order->has_shipping_method(WC_UKR_SHIPPING_NP_SHIPPING_NAME)
            ? new ShippingRecipientAddress($this->order, $this->orderShipping)
            : new CustomRecipientAddress($this->order);

        $shippingAddress->writeData($this->data);
    }

    private function collectHelpers()
    {
        $this->data['helpers']['default_cities'] = array_map(function($item) {
            return [
                'name' => $item[$this->translateService->getCurrentLanguage() === 'ua' ? 'description' : 'description_ru'],
                'value' => $item['ref']
            ];
        }, WCUSHelper::getDefaultCities());
    }

    private function collectBackwardDelivery(): void
    {
        $this->data['ttn']['backward_delivery'] = $this->defaults['cod'] !== null;
        $this->data['ttn']['backward_delivery_type'] = 'Money';
        $this->data['ttn']['backward_delivery_payer'] = ucfirst($this->defaults['cod']['paid_by'] ?? 'Recipient');

        /**
         * Enable third-party code to control cost of COD feature
         * @since 1.16.6
         */
        $cost = apply_filters(
            'wcus_ttn_form_cod_cost',
            $this->defaults['cod']['value']['amount'] ?? 0,
            $this->order
        );

        $this->data['ttn']['backward_delivery_cost'] = $cost;
    }

    private function collectPaymentControl(): void
    {
        $paymentControlActive = ($this->defaults['cod']['payment_method'] ?? 'cash') === 'cash_equivalent';
        if ($paymentControlActive) {
            $this->data['ttn']['backward_delivery'] = 0;
            $this->data['ttn']['payment_control'] = 1;
        } else {
            $this->data['ttn']['payment_control'] = 0;
        }

        /**
         * Enable third-party code to control cost of Payment Control feature
         * @since 1.16.6
         */
        $cost = apply_filters(
            'wcus_ttn_form_payment_control_cost',
            $this->defaults['cod']['value']['amount'] ?? 0,
            $this->order
        );
        $this->data['ttn']['payment_control_cost'] = $cost;
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
