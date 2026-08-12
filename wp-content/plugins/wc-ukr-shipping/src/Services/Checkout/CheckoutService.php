<?php

namespace kirillbdev\WCUkrShipping\Services\Checkout;

if ( ! defined('ABSPATH')) {
    exit;
}

class CheckoutService
{
    public function renderCheckoutFields(string $type)
    {
        ?>
        <div id="wcus-<?php echo esc_attr($type); ?>-fields"></div>
        <?php
    }
}
