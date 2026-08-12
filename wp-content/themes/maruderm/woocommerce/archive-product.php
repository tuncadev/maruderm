<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit();
}

get_header('shop');

(new \Maruderm\Catalog\CatalogRenderer())->render();

get_footer('shop');
