<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\States;

use kirillbdev\WCUkrShipping\Includes\AppState;

class OrdersState extends AppState
{
    protected function getState(): array
    {
        $limits = apply_filters('wcus_orders_order_limits', [20, 50, 100]);

        return [
            'orderLimits' => $limits,
            'defaultLimit' => reset($limits),
        ];
    }
}
