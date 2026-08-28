<?php

namespace kirillbdev\WCUkrShipping\Modules\Frontend;

use kirillbdev\WCUkrShipping\Component\Block\ShippingBlockIntegration;
use kirillbdev\WCUkrShipping\Enums\CarrierSlug;
use kirillbdev\WCUkrShipping\Foundation\NovaGlobalAddress;
use kirillbdev\WCUkrShipping\Foundation\NovaPoshtaShipping;
use kirillbdev\WCUkrShipping\Foundation\NovaPostShipping;
use kirillbdev\WCUkrShipping\Foundation\PostNordAddressShipping;
use kirillbdev\WCUkrShipping\Foundation\PostNordShipping;
use kirillbdev\WCUkrShipping\Foundation\RozetkaDeliveryShipping;
use kirillbdev\WCUkrShipping\Foundation\MeestShipping;
use kirillbdev\WCUkrShipping\Foundation\MeestAddressShipping;
use kirillbdev\WCUkrShipping\Foundation\UkrPoshtaAddressShipping;
use kirillbdev\WCUkrShipping\Foundation\UkrPoshtaShipping;
use kirillbdev\WCUkrShipping\Helpers\WCUSHelper;
use kirillbdev\WCUSCore\Contracts\ModuleInterface;

class ShippingMethod implements ModuleInterface
{
    private static ?string $cachedRateHash = null;

    /**
     * Boot function
     *
     * @return void
     */
    public function init()
    {
        add_filter('woocommerce_shipping_methods', [ $this, 'registerShippingMethod' ]);
        add_filter('woocommerce_cart_shipping_packages', [$this, 'calculatePackageRateHash']);
        add_filter('woocommerce_cart_shipping_method_full_label', [$this, 'appendViewCostToRateLabel'], 10, 2);
        add_filter('woocommerce_order_shipping_to_display', [$this, 'displayOrderViewCost'], 10, 2);

        // test
        add_action( 'woocommerce_blocks_loaded', function () {

            add_action(
                'woocommerce_blocks_checkout_block_registration',
                function ( $integration_registry ) {
                    $integration_registry->register(
                        new ShippingBlockIntegration()
                    );
                }
            );
        });
    }

    public function registerShippingMethod($methods)
    {
        $activeCarriers = WCUSHelper::safeGetJsonOption('wcus_active_carriers');
        if (in_array(CarrierSlug::NOVA_POSHTA, $activeCarriers)) {
            $methods[WCUS_SHIPPING_METHOD_NOVA_POSHTA] = NovaPoshtaShipping::class;
        }
        if (in_array(CarrierSlug::UKRPOSHTA, $activeCarriers)) {
            $methods[WCUS_SHIPPING_METHOD_UKRPOSHTA] = UkrPoshtaShipping::class;
            $methods[WCUS_SHIPPING_METHOD_UKRPOSHTA_ADDRESS] = UkrPoshtaAddressShipping::class;
        }
        if (in_array(CarrierSlug::ROZETKA_DELIVERY, $activeCarriers)) {
            $methods[WCUS_SHIPPING_METHOD_ROZETKA] = RozetkaDeliveryShipping::class;
        }
        if (in_array(CarrierSlug::NOVA_POST, $activeCarriers)) {
            $methods[WCUS_SHIPPING_METHOD_NOVA_POST] = NovaPostShipping::class;
        }
        if (in_array(CarrierSlug::NOVA_GLOBAL, $activeCarriers)) {
            $methods[WCUS_SHIPPING_METHOD_NOVA_GLOBAL_ADDRESS] = NovaGlobalAddress::class;
        }
        if (in_array(CarrierSlug::MEEST, $activeCarriers)) {
            $methods[WCUS_SHIPPING_METHOD_MEEST] = MeestShipping::class;
            $methods[WCUS_SHIPPING_METHOD_MEEST_ADDRESS] = MeestAddressShipping::class;
        }
        if (in_array(CarrierSlug::POST_NORD, $activeCarriers)) {
            $methods[WCUS_SHIPPING_METHOD_POST_NORD] = PostNordShipping::class;
            $methods[WCUS_SHIPPING_METHOD_POST_NORD_ADDRESS] = PostNordAddressShipping::class;
        }

        return $methods;
    }

    public function calculatePackageRateHash(array $packages): array
    {
        // We need to perform calculation only for ajax refresh checkout and place order
        if (!isset($_GET['wc-ajax'])
            || !in_array($_GET['wc-ajax'], ['update_order_review', 'checkout'], true)) {
            return $packages;
        }

        $chosenMethods = wc_get_chosen_shipping_method_ids();
        $supportedMethods = [
            WC_UKR_SHIPPING_NP_SHIPPING_NAME,
            WCUS_SHIPPING_METHOD_UKRPOSHTA,
            WCUS_SHIPPING_METHOD_UKRPOSHTA_ADDRESS,
            WCUS_SHIPPING_METHOD_NOVA_POST,
            WCUS_SHIPPING_METHOD_ROZETKA,
            WCUS_SHIPPING_METHOD_MEEST,
            WCUS_SHIPPING_METHOD_MEEST_ADDRESS,
            WCUS_SHIPPING_METHOD_NOVA_GLOBAL_ADDRESS,
            WCUS_SHIPPING_METHOD_POST_NORD,
            WCUS_SHIPPING_METHOD_POST_NORD_ADDRESS,
        ];
        foreach ($packages as $key => &$package) {
            if (isset($chosenMethods[$key])
                && in_array($chosenMethods[$key], $supportedMethods, true)) {
                // todo: bad solution! provide array cache implementation instead
                if (self::$cachedRateHash === null) {
                    self::$cachedRateHash = md5(
                        sprintf('%s_%f', $chosenMethods[$key], microtime(true))
                    );
                }
                $package['wcus_rates_hash'] = self::$cachedRateHash;
            }
        }

        return $packages;
    }

    /**
     * The rate cost is zero in view only mode, so the calculated cost is rendered from the rate meta.
     *
     * @param string $label
     * @param \WC_Shipping_Rate $rate
     */
    public function appendViewCostToRateLabel($label, $rate): string
    {
        $viewCost = WCUSHelper::getRateViewCost($rate);

        return $viewCost === null
            ? $label
            : $label . ': ' . wc_price($viewCost);
    }

    /**
     * @param string $shipping
     * @param \WC_Order $order
     */
    public function displayOrderViewCost($shipping, $order): string
    {
        $viewCost = WCUSHelper::getOrderViewShippingCost($order);

        return $viewCost === null
            ? $shipping
            : wc_price($viewCost, ['currency' => $order->get_currency()]) . ' (' . $shipping . ')';
    }
}
