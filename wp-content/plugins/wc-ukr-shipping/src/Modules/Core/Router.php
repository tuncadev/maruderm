<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Modules\Core;

use kirillbdev\WCUkrShipping\Http\Controllers\JWTController;
use kirillbdev\WCUkrShipping\Http\Controllers\OrdersController;
use kirillbdev\WCUkrShipping\Http\Controllers\SmartyParcelController;
use kirillbdev\WCUSCore\Contracts\ModuleInterface;
use kirillbdev\WCUSCore\Http\Routing\Route;

class Router implements ModuleInterface
{
    public function init(): void
    {
    }

    public function routes(): array
    {
        return [
            // SmartyParcel
            new Route('wcus_smartyparcel_save_label', SmartyParcelController::class, 'saveLabel'),
            new Route('wcus_smartyparcel_remove_label', SmartyParcelController::class, 'removeLabel'),

            // Authx (embedded elements)
            new Route('wcus_smartyparcel_jwt', JWTController::class, 'issueToken'),

            // Orders
            new Route('wcus_get_order_shipping_address', OrdersController::class, 'getOrderShippingAddress'),
            new Route('wcus_update_order_shipping_address', OrdersController::class, 'updateOrderShippingAddress'),
        ];
    }
}
