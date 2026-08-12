<?php

namespace MarudermNovaPoshta;

use MarudermNovaPoshta\Api\Client;
use MarudermNovaPoshta\Api\TokenProvider;
use MarudermNovaPoshta\Service\DivisionService;
use MarudermNovaPoshta\Service\ShipmentService;
use MarudermNovaPoshta\Service\WebhookService;
use MarudermNovaPoshta\Woo\CheckoutService;

class Plugin
{
    private static ?self $instance = null;

    public static function boot(string $pluginFile): void
    {
        if (self::$instance instanceof self) {
            return;
        }

        self::$instance = new self();
        self::$instance->registerHooks($pluginFile);
    }

    private function registerHooks(string $pluginFile): void
    {
        register_activation_hook($pluginFile, [$this, 'onActivate']);

        add_action('plugins_loaded', function (): void {
            if (! class_exists('WooCommerce')) {
                return;
            }

            require_once __DIR__ . '/Woo/ShippingMethod.php';

            add_action('woocommerce_shipping_init', static function (): void {
                // Class is already loaded through require_once above.
            });

            add_filter('woocommerce_shipping_methods', static function (array $methods): array {
                $methods['maruderm_nova_poshta'] = \MarudermNovaPoshta\Woo\ShippingMethod::class;
                return $methods;
            });

            $config = new Config();
            $tokenProvider = new TokenProvider($config);
            $client = new Client($config, $tokenProvider);

            $divisionService = new DivisionService($client, $config);
            $shipmentService = new ShipmentService($client, $config, $divisionService);
            $webhookService = new WebhookService($client, $config);
            $checkoutService = new CheckoutService($config, $divisionService, $shipmentService, $webhookService);

            $checkoutService->registerHooks();

            // Keep subscriber synced in background when webhook config exists.
            add_action('init', [$webhookService, 'ensureSubscriber']);
        });
    }

    public function onActivate(): void
    {
        $defaults = [
            'nova_pochta_environment' => 'stage',
            'nova_pochta_webhook_secret_header' => 'X-Nova-Poshta-Token',
        ];

        $current = get_option(Config::OPTION_KEY, []);
        $current = is_array($current) ? $current : [];

        update_option(Config::OPTION_KEY, array_merge($defaults, $current));
    }
}
