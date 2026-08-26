<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Component\Block;

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;

class ShippingBlockIntegration implements IntegrationInterface
{
    public function get_name()
    {
        return 'smartyparcel-shipping-block';
    }

    public function initialize()
    {
        return;
        wp_register_script(
            'my-shipping-blocks',
            WC_UKR_SHIPPING_PLUGIN_URL . '/assets/js/block-test.js',
            [
                'wc-blocks-registry',
                'wc-settings',
                'wc-blocks-checkout',
                'wp-element'
            ],
            '1.0',
            true
        );
    }

    public function get_script_handles()
    {
        return [];
        return [ 'my-shipping-blocks' ];
    }

    public function get_editor_script_handles()
    {
        return [];
    }

    public function get_script_data()
    {
        return [];
    }
}
