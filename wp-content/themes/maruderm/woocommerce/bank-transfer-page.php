<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit();
}

$order = \Maruderm\Checkout\CheckoutResultPage::currentOrder();

if (!$order instanceof \WC_Order) {
    return;
}

get_header('shop');

(new \Maruderm\Checkout\CheckoutResultRenderer($order))->renderBankTransfer();

get_footer('shop');
