<?php

namespace kirillbdev\WCUkrShipping\Foundation;

use kirillbdev\WCUSCore\Foundation\Container;
use kirillbdev\WCUSCore\Foundation\Kernel;

if ( ! defined('ABSPATH')) {
    exit;
}

final class WCUkrShipping extends Kernel
{
    /**
     * @var WCUkrShipping
     */
    private static $instance = null;

    public static function instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function getContainer(): Container
    {
        return $this->container;
    }

    public function modules()
    {
        return [
            // Core
            \kirillbdev\WCUkrShipping\Modules\Core\Activator::class,
            \kirillbdev\WCUkrShipping\Modules\Core\Localization::class,
            \kirillbdev\WCUkrShipping\Modules\Core\PluginInfo::class,
            \kirillbdev\WCUkrShipping\Modules\Core\Router::class,

            // Legacy
            \kirillbdev\WCUkrShipping\Modules\WcusLegacyCompatibility::class,

            // Backend
            \kirillbdev\WCUkrShipping\Modules\Backend\OptionsPage::class,
            \kirillbdev\WCUkrShipping\Modules\Backend\AssetsLoader::class,
            \kirillbdev\WCUkrShipping\Modules\Backend\ShippingItemDrawer::class,
            \kirillbdev\WCUkrShipping\Modules\Backend\Orders::class,
            \kirillbdev\WCUkrShipping\Modules\Backend\Automation::class,

            // Frontned
            \kirillbdev\WCUkrShipping\Modules\Frontend\AssetsLoader::class,
            \kirillbdev\WCUkrShipping\Modules\Frontend\Address::class,
            \kirillbdev\WCUkrShipping\Modules\Frontend\ShippingMethod::class,
            \kirillbdev\WCUkrShipping\Modules\Frontend\Cart::class,
            \kirillbdev\WCUkrShipping\Modules\Frontend\Checkout::class,
            \kirillbdev\WCUkrShipping\Modules\Frontend\CheckoutValidator::class,
            \kirillbdev\WCUkrShipping\Modules\Frontend\OrderCreator::class,
            \kirillbdev\WCUkrShipping\Modules\Frontend\Account::class,

            \kirillbdev\WCUkrShipping\Modules\SmartyParcel::class,
        ];
    }

    public function dependencies()
    {
        return Dependencies::all();
    }

    public function viewPath()
    {
        return WC_UKR_SHIPPING_PLUGIN_DIR . '/views';
    }
}