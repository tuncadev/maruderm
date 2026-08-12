<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit();
}

get_header('shop');

(new \Maruderm\Checkout\PaymentRenderer())->render();

get_footer('shop');
