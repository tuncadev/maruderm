<?php
/**
 * Plugin Name: Maruderm Multilingual Bridge
 * Description: Keeps translated product presentation separate from canonical WooCommerce commerce identity.
 *
 * @package Maruderm
 */

if (! defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/maruderm-multilingual/class-product-identity-resolver.php';
require_once __DIR__ . '/maruderm-multilingual/class-russian-slugger.php';
require_once __DIR__ . '/maruderm-multilingual/class-taxonomy-presentation-resolver.php';
require_once __DIR__ . '/maruderm-multilingual/class-product-detail-translator.php';
require_once __DIR__ . '/maruderm-multilingual/class-graphql-controller.php';

add_action('plugins_loaded', static function (): void {
    $controller = new \Maruderm\Multilingual\GraphqlController(
        new \Maruderm\Multilingual\ProductIdentityResolver()
    );
    $controller->register();
});
