<?php

defined('ABSPATH') || exit;

get_header('shop');

while (have_posts()) {
    the_post();

    do_action('woocommerce_before_single_product');

    if (post_password_required()) {
        echo get_the_password_form(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        continue;
    }

    $product = wc_get_product(get_the_ID());

    if ($product instanceof \WC_Product) {
        (new \Maruderm\WooCommerce\SingleProductRenderer())->render($product);

        if (WC()->structured_data instanceof \WC_Structured_Data) {
            WC()->structured_data->generate_product_data($product);
        }
    }

    do_action('woocommerce_after_single_product');
}

get_footer('shop');
