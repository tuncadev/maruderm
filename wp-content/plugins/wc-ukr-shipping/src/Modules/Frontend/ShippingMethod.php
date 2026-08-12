<?php

namespace kirillbdev\WCUkrShipping\Modules\Frontend;

use kirillbdev\WCUkrShipping\Enums\CarrierSlug;
use kirillbdev\WCUkrShipping\Foundation\NovaGlobalAddress;
use kirillbdev\WCUkrShipping\Foundation\NovaPoshtaShipping;
use kirillbdev\WCUkrShipping\Foundation\NovaPostShipping;
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
        add_filter('woocommerce_calculated_total', [$this, 'calculateCartTotal'], 10, 2);
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

    public function calculateCartTotal(float $total, \WC_Cart $cart): float
    {
        // Safe skip feature if any problems with WooCommerce session detected
        if ( ! is_callable([WC()->session, 'get'])) {
            return $total;
        }

        $chosenMethods = WC()->session->get( 'chosen_shipping_methods', []);
        $chosenInstance = reset($chosenMethods);
        if (!$chosenInstance) {
            return $total;
        }

        [$methodId, $instanceId] = explode(':', $chosenInstance);
        $shippingInstance = null;
        switch ($methodId) {
            case WC_UKR_SHIPPING_NP_SHIPPING_NAME:
                $shippingInstance = new NovaPoshtaShipping((int)$instanceId);
                break;
            case WCUS_SHIPPING_METHOD_UKRPOSHTA:
                $shippingInstance = new UkrPoshtaShipping((int)$instanceId);
                break;
            case WCUS_SHIPPING_METHOD_ROZETKA:
                $shippingInstance = new RozetkaDeliveryShipping((int)$instanceId);
                break;
            case WCUS_SHIPPING_METHOD_MEEST:
                $shippingInstance = new MeestShipping((int)$instanceId);
                break;
            case WCUS_SHIPPING_METHOD_MEEST_ADDRESS:
                $shippingInstance = new MeestAddressShipping((int)$instanceId);
                break;
            case WCUS_SHIPPING_METHOD_NOVA_POST:
                $shippingInstance = new NovaPostShipping((int)$instanceId);
                break;
            case WCUS_SHIPPING_METHOD_NOVA_GLOBAL_ADDRESS:
                $shippingInstance = new NovaGlobalAddress((int)$instanceId);
                break;
        }

        return $shippingInstance !== null && $shippingInstance->get_option('add_cost_to_order') === 'no'
            ? $total - $cart->get_shipping_total()
            : $total;
    }
}
