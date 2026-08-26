<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Http\Middleware;

use kirillbdev\WCUSCore\Http\Request;

final class CheckManageWooPermission
{
    public function handle(Request $request): void
    {
        if ( ! current_user_can('manage_woocommerce')) {
            wp_send_json([
                'success' => false,
                'error' => [
                    'message' => __('You do not have permission to access this action.', 'wc-ukr-shipping')
                ]
            ]);
        }
    }
}
