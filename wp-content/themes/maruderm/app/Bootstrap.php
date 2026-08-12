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
require_once __DIR__ . '/WooCommerce/ProductBadges.php';
require_once __DIR__ . '/Catalog/CatalogRoutes.php';
require_once __DIR__ . '/Catalog/CatalogRepository.php';
require_once __DIR__ . '/Catalog/CatalogRenderer.php';
require_once __DIR__ . '/LandingPage/LandingPageCatalog.php';
require_once __DIR__ . '/LandingPage/LandingPageContent.php';
require_once __DIR__ . '/LandingPage/LandingPageRenderer.php';
require_once __DIR__ . '/Homepage/HomepageRenderer.php';

Enqueue::load();
\Maruderm\Settings\ThemeSettings::load();
\Maruderm\Settings\HomepageSettings::load();
\Maruderm\WooCommerce\ProductBadges::load();
\Maruderm\Catalog\CatalogRoutes::load();
Application::get_instance()->init();
