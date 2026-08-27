<?php

declare(strict_types=1);

namespace Maruderm\WooCommerce;

use Maruderm\Kernel\Loadable;
use Maruderm\Kernel\Registrable;

if (!defined('ABSPATH')) {
    exit();
}

/** Registers the theme-owned product asset location and resolves barcode folders. */
final class ProductAssets implements Registrable
{
    use Loadable;

    public const RELATIVE_DIRECTORY = 'assets/products';

    private ProductImageRepository $images;

    public function __construct(?ProductImageRepository $images = null)
    {
        $this->images = $images ?? new ProductImageRepository();
    }

    public function register(): void
    {
        if (!defined('MARUDERM_PRODUCT_ASSETS_PATH')) {
            define('MARUDERM_PRODUCT_ASSETS_PATH', self::rootPath());
        }

        if (!defined('MARUDERM_PRODUCT_ASSETS_URL')) {
            define('MARUDERM_PRODUCT_ASSETS_URL', self::rootUrl());
        }

        add_filter('woocommerce_product_get_image', [$this, 'preferFolderImage'], 20, 5);
    }

    /** @param string|array<int, int> $size @param array<string, mixed> $attributes */
    public function preferFolderImage(
        string $html,
        \WC_Product $product,
        string|array $size,
        array $attributes,
        bool $placeholder
    ): string {
        return $this->images->folderImageHtml($product, $size, $attributes) ?? $html;
    }

    public static function rootPath(): string
    {
        return untrailingslashit(get_theme_file_path(self::RELATIVE_DIRECTORY));
    }

    public static function rootUrl(): string
    {
        return untrailingslashit(get_theme_file_uri(self::RELATIVE_DIRECTORY));
    }

    public static function directoryForSku(string $sku): ?string
    {
        $sku = self::normalizeSku($sku);

        return $sku === null ? null : self::rootPath() . '/' . $sku;
    }

    public static function urlForSku(string $sku): ?string
    {
        $sku = self::normalizeSku($sku);

        return $sku === null ? null : self::rootUrl() . '/' . rawurlencode($sku);
    }

    private static function normalizeSku(string $sku): ?string
    {
        $sku = trim($sku);

        return preg_match('/^[0-9]{13}$/D', $sku) === 1 ? $sku : null;
    }
}
