<?php

namespace kirillbdev\WCUkrShipping\Services;

use kirillbdev\WCUkrShipping\DB\Mappers\OrderListMapper;
use kirillbdev\WCUkrShipping\DB\Repositories\Orders\OrderRepositoryInterface;
use kirillbdev\WCUSCore\Http\Request;

if ( ! defined('ABSPATH')) {
    exit;
}

class OrderService
{
    private OrderListMapper $orderListMapper;
    private OrderRepositoryInterface $orderRepository;

    public function __construct(
        OrderListMapper $orderListMapper
    ) {
        $this->orderRepository = wcus_container()->make(OrderRepositoryInterface::class);
        $this->orderListMapper = $orderListMapper;
    }

    public function getOrdersFromRequest(Request $request): array
    {
        $limit = (int)$request->get('limit', 20);
        $offset = ((int)$request->get('page', 1) - 1) * $limit;
        $orders = $this->orderRepository->getOrdersWithTTN(
            $offset,
            $limit,
            $request->get('filters', [])
        );

        foreach ($orders as &$order) {
            $order['info'] = $this->orderRepository->getOrderInfo($order['id']);
            $order['shipping_method'] = $this->orderRepository->getOrderShippingMethod($order['id']);
        }

        return $this->orderListMapper->fetchOrders($orders);
    }

    public function getCountPagesFromRequest(Request $request): int
    {
        return $this->orderRepository->getCountOrderPages(
            (int)$request->get('limit', 20),
            $request->get('filters', [])
        );
    }
}
