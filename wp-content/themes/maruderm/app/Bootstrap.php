<?php

declare(strict_types=1);

namespace Maruderm;

use Maruderm\Kernel\Application;
use Maruderm\Kernel\Enqueue;

if (!defined('ABSPATH')) {
    exit();
}

require_once __DIR__ . '/Kernel/Registrable.php';
require_once __DIR__ . '/Kernel/Application.php';
require_once __DIR__ . '/Kernel/Dependencies.php';
require_once __DIR__ . '/Kernel/Loadable.php';
require_once __DIR__ . '/Kernel/Helpers.php';
require_once __DIR__ . '/Kernel/Enqueue.php';
require_once __DIR__ . '/Settings/ThemeSettings.php';
require_once __DIR__ . '/Settings/HomepageSettings.php';
require_once __DIR__ . '/Settings/BonusSettings.php';
require_once __DIR__ . '/WooCommerce/ProductBadges.php';
require_once __DIR__ . '/WooCommerce/StockNotificationService.php';
require_once __DIR__ . '/WooCommerce/StockNotificationRenderer.php';
require_once __DIR__ . '/WooCommerce/StockNotificationMailer.php';
require_once __DIR__ . '/WooCommerce/StockNotifications.php';
require_once __DIR__ . '/Catalog/CatalogRoutes.php';
require_once __DIR__ . '/Catalog/CatalogRepository.php';
require_once __DIR__ . '/WooCommerce/ProductCardRenderer.php';
require_once __DIR__ . '/WooCommerce/SingleProductContent.php';
require_once __DIR__ . '/WooCommerce/SingleProductRenderer.php';
require_once __DIR__ . '/WooCommerce/SingleProductPage.php';
require_once __DIR__ . '/Catalog/CatalogRenderer.php';
require_once __DIR__ . '/Cart/CartPage.php';
require_once __DIR__ . '/Cart/CartRenderer.php';
require_once __DIR__ . '/Checkout/DeliveryPage.php';
require_once __DIR__ . '/Checkout/DeliveryRenderer.php';
require_once __DIR__ . '/Checkout/PaymentRenderer.php';
require_once __DIR__ . '/Checkout/CheckoutResultPage.php';
require_once __DIR__ . '/Checkout/CheckoutResultRenderer.php';
require_once __DIR__ . '/LandingPage/LandingPageCatalog.php';
require_once __DIR__ . '/LandingPage/LandingPageContent.php';
require_once __DIR__ . '/LandingPage/LandingPageRenderer.php';
require_once __DIR__ . '/Homepage/HomepagePage.php';
require_once __DIR__ . '/Homepage/HomepageHeroRenderer.php';
require_once __DIR__ . '/Homepage/HomepageRenderer.php';
require_once __DIR__ . '/Layout/FooterRenderer.php';
require_once __DIR__ . '/HairAnalysis/HairAnalysisRenderer.php';
require_once __DIR__ . '/HairAnalysis/HairAnalysisPage.php';
require_once __DIR__ . '/Auth/LoginRenderer.php';
require_once __DIR__ . '/Auth/LoginExperience.php';
require_once __DIR__ . '/Account/AccountRenderer.php';
require_once __DIR__ . '/Account/BonusService.php';
require_once __DIR__ . '/Account/AccountPage.php';
require_once __DIR__ . '/Account/AccountAddressService.php';
require_once __DIR__ . '/Account/AccountAddresses.php';
require_once __DIR__ . '/Account/AccountAvatarService.php';
require_once __DIR__ . '/Account/AccountAvatars.php';

Enqueue::load();
\Maruderm\Settings\ThemeSettings::load();
\Maruderm\Settings\HomepageSettings::load();
\Maruderm\Settings\BonusSettings::load();
\Maruderm\WooCommerce\ProductBadges::load();
\Maruderm\WooCommerce\StockNotifications::load();
\Maruderm\WooCommerce\SingleProductPage::load();
\Maruderm\Catalog\CatalogRoutes::load();
\Maruderm\Cart\CartPage::load();
\Maruderm\Checkout\DeliveryPage::load();
\Maruderm\Checkout\CheckoutResultPage::load();
\Maruderm\Homepage\HomepagePage::load();
\Maruderm\HairAnalysis\HairAnalysisPage::load();
\Maruderm\Auth\LoginExperience::load();
\Maruderm\Account\AccountPage::load();
\Maruderm\Account\AccountAddresses::load();
\Maruderm\Account\AccountAvatars::load();
Application::get_instance()->init();
