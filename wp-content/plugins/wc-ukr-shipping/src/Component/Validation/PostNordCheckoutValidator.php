<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Component\Validation;

use kirillbdev\WCUkrShipping\Helpers\WCUSHelper;

class PostNordCheckoutValidator implements CheckoutValidatorInterface
{
    public function validate(array $data): void
    {
        $this->validateWarehouseShipping(WCUSHelper::getCheckoutFieldGroup($data), $data);
    }

    private function validateWarehouseShipping(string $type, array $data): void
    {
        if (empty($data['wcus_post_nord_' . $type . '_pudo_id'])) {
            wc_add_notice(
                __('Please choice a Service Point', 'wc-ukr-shipping'),
                'error'
            );
        }
    }
}
