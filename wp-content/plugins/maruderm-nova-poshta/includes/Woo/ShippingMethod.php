<?php

namespace MarudermNovaPoshta\Woo;

class ShippingMethod extends \WC_Shipping_Method
{
    public function __construct(int $instanceId = 0)
    {
        $this->id = 'maruderm_nova_poshta';
        $this->instance_id = absint($instanceId);
        $this->method_title = __('Nova Poshta', 'maruderm-nova-poshta');
        $this->method_description = __('Delivery to Nova Poshta office or postomat in Ukraine.', 'maruderm-nova-poshta');
        $this->supports = [
            'shipping-zones',
            'instance-settings',
        ];

        $this->init();
    }

    public function init(): void
    {
        $this->instance_form_fields = [
            'title' => [
                'title' => __('Method title', 'maruderm-nova-poshta'),
                'type' => 'text',
                'default' => __('Nova Poshta', 'maruderm-nova-poshta'),
            ],
            'cost' => [
                'title' => __('Cost', 'maruderm-nova-poshta'),
                'type' => 'price',
                'default' => '0',
                'desc_tip' => true,
            ],
        ];

        $this->title = (string) $this->get_option('title', __('Nova Poshta', 'maruderm-nova-poshta'));

        add_action('woocommerce_update_options_shipping_' . $this->id, [$this, 'process_admin_options']);
    }

    /**
     * @param array<string, mixed> $package
     */
    public function calculate_shipping($package = []): void
    {
        $country = strtoupper((string) ($package['destination']['country'] ?? ''));
        if ($country !== 'UA') {
            return;
        }

        $cost = (float) $this->get_option('cost', '0');

        $this->add_rate([
            'id' => $this->get_rate_id(),
            'label' => $this->title,
            'cost' => $cost,
            'package' => $package,
        ]);
    }
}
