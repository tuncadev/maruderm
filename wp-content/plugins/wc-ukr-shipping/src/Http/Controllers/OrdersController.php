<?php

namespace kirillbdev\WCUkrShipping\Http\Controllers;

use kirillbdev\WCUkrShipping\Component\Carriers\NovaPoshta\Order\ShippingAddressMapper;
use kirillbdev\WCUkrShipping\Component\WooCommerce\OrderDataStore;
use kirillbdev\WCUkrShipping\Dto\Rates\RateShipmentDTO;
use kirillbdev\WCUkrShipping\Enums\CarrierSlug;
use kirillbdev\WCUkrShipping\Foundation\NovaPoshtaShipping;
use kirillbdev\WCUkrShipping\Helpers\SmartyParcelHelper;
use kirillbdev\WCUkrShipping\Helpers\WCUSHelper;
use kirillbdev\WCUkrShipping\Services\Calculation\ProductDimensionService;
use kirillbdev\WCUkrShipping\Services\CalculationService;
use kirillbdev\WCUkrShipping\Services\OrderService;
use kirillbdev\WCUSCore\Http\Contracts\ResponseInterface;
use kirillbdev\WCUSCore\Http\Controller;
use kirillbdev\WCUSCore\Http\Request;

if ( ! defined('ABSPATH')) {
    exit;
}

class OrdersController extends Controller
{
    private OrderService $orderService;
    private CalculationService $calculationService;
    private ProductDimensionService $productDimensionService;

    public function __construct(
        OrderService $orderService,
        CalculationService $calculationService,
        ProductDimensionService $productDimensionService
    ) {
        $this->orderService = $orderService;
        $this->calculationService = $calculationService;
        $this->productDimensionService = $productDimensionService;
    }

    public function getOrders(Request $request): ResponseInterface
    {
        return $this->jsonResponse([
            'success' => true,
            'data' => [
                'orders' => $this->orderService->getOrdersFromRequest($request),
                'count_pages' => $this->orderService->getCountPagesFromRequest($request)
            ]
        ]);
    }

    public function getOrderShippingAddress(Request $request): ResponseInterface
    {
        $order = wc_get_order($request->get('order_id'));
        if (!$order) {
            return $this->jsonResponse([
                'success' => false,
                'error' => [
                    'code' => 0,
                    'message' => 'Order not found',
                    'source' => 'internal'
                ]
            ]);
        }

        return $this->jsonResponse([
            'success' => true,
            'data' => [
                'carrier_slug' => SmartyParcelHelper::getOrderCarrierSlug($order),
                'ship_to' => (new ShippingAddressMapper())->getShippingAddress($order),
            ]
        ]);
    }

    public function updateOrderShippingAddress(Request $request): ResponseInterface
    {
        try {
            $order = wc_get_order($request->get('order_id'));
            if (!$order) {
                throw new \Exception('Order not found');
            }

            // todo: extend for other carriers
            $dataStore = new OrderDataStore($order);
            $dataStore->save(
                (new ShippingAddressMapper())->mapAddressToOrderData($order, $request->get('address'))
            );

            // Create shipping instance
            $shippingMethod = WCUSHelper::getOrderShippingMethod($order);
            $shippingInstance = new NovaPoshtaShipping((int)$shippingMethod->get_instance_id());

            // Force create rate shipment DTO for NovaPoshta to perform calculation
            $dto = new RateShipmentDTO(
                CarrierSlug::NOVA_POSHTA,
                'UA',
                $order->get_subtotal(),
                $this->productDimensionService->getDefaultWeight(),
                $order->get_payment_method(),
                $request->get('address')['type'] === 'pudo' ? 'w2w' : 'w2d',
                true,
                null,
                $request->get('address')['type'] === 'pudo'
                    ? $request->get('address')['extra']['pudo_city_id']
                    : $request->get('address')['extra']['np_settlement_id'],
                null,
                null,
                [],
                (string)wc_ukr_shipping_get_option('wc_ukr_shipping_np_sender_city')
            );
            $shippingCost = $this->calculationService->calculateRates($dto, $shippingInstance);

            if ($shippingCost !== null) {
                if ($shippingInstance->isCostViewOnly()) {
                    // Keep the cost out of the order totals, store it as a meta for display only
                    $shippingMethod->set_total(0);
                    $shippingMethod->set_taxes([]);
                    $shippingMethod->update_meta_data(
                        WCUS_SHIPPING_META_VIEW_COST,
                        (string)wc_format_decimal($shippingCost)
                    );
                } else {
                    $shippingMethod->set_total($shippingCost);
                    $shippingMethod->delete_meta_data(WCUS_SHIPPING_META_VIEW_COST);
                }

                $shippingMethod->save();
                $order->calculate_totals();
            }
        return $this->jsonResponse([
            'success' => true,
        ]);
        } catch (\Throwable $e) {
            return $this->jsonResponse([
                'success' => false,
                'error' => [
                    'code' => 0,
                    'message' => $e->getMessage(),
                    'source' => 'internal'
                ]
            ]);
        }
    }
}
