<?php
/**
 * Public pricing policy for out-of-stock WooCommerce products.
 *
 * @package Maruderm
 */

if (! defined('ABSPATH')) {
    exit;
}

final class Maruderm_Out_Of_Stock_Pricing
{
    private const STOCK_STATUS = 'outofstock';

    public function register(): void
    {
        add_action('woocommerce_before_product_object_save', [$this, 'clear_public_prices_before_save'], 5);
        add_filter('woocommerce_get_price_html', [$this, 'replace_price_html'], 9999, 2);
        add_filter('woocommerce_is_purchasable', [$this, 'prevent_purchase'], 9999, 2);
        add_filter('woocommerce_variation_is_purchasable', [$this, 'prevent_purchase'], 9999, 2);
    }

    public function clear_public_prices_before_save(WC_Product $product): void
    {
        if ($product->get_stock_status('edit') !== self::STOCK_STATUS) {
            return;
        }

        if ((bool) apply_filters('maruderm_preserve_out_of_stock_public_prices', false, $product)) {
            return;
        }

        $product->set_regular_price('');
        $product->set_sale_price('');
        $product->set_price('');
        $product->set_date_on_sale_from(null);
        $product->set_date_on_sale_to(null);
    }

    public function replace_price_html(string $price_html, WC_Product $product): string
    {
        if ($product->get_stock_status() !== self::STOCK_STATUS) {
            return $price_html;
        }

        return '<span class="maruderm-out-of-stock-price">'
            . esc_html__('Out of stock', 'woocommerce')
            . '</span>';
    }

    public function prevent_purchase(bool $purchasable, WC_Product $product): bool
    {
        return $product->get_stock_status() === self::STOCK_STATUS ? false : $purchasable;
    }
}
