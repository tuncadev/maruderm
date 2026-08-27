<?php

declare(strict_types=1);

namespace Maruderm\WooCommerce;

if (!defined('ABSPATH')) {
    exit();
}

/** Resolves browser-safe product imagery from SKU folders with native WooCommerce fallback. */
final class ProductImageRepository
{
    private const PROMOTION_LIMIT = 3;
    private const SUPPORTED_EXTENSIONS = ['avif', 'gif', 'jpeg', 'jpg', 'png', 'webp'];
    private const SOURCE_DIRECTORIES = [
        ['category' => 'product', 'directory' => 'product'],
        ['category' => 'root', 'directory' => ''],
        ['category' => 'promotion', 'directory' => 'promotion'],
    ];

    /** @var array<string, array<int, array<string, int|string|bool>>> */
    private static array $folderCache = [];

    /** @var array<string, array{width: int, height: int}> */
    private static array $dimensionCache = [];

    /** @return array<int, array<string, int|string|bool>> */
    public function images(\WC_Product $product): array
    {
        $folderImages = $this->folderImages($product);

        return $folderImages !== [] ? $folderImages : $this->woocommerceImages($product);
    }

    /** @return array<int, array<string, int|string|bool>> */
    public function folderImages(\WC_Product $product): array
    {
        $sku = trim($product->get_sku());

        if (array_key_exists($sku, self::$folderCache)) {
            return self::$folderCache[$sku];
        }

        $rootPath = ProductAssets::directoryForSku($sku);
        $rootUrl = ProductAssets::urlForSku($sku);

        if ($rootPath === null || $rootUrl === null || !is_dir($rootPath)) {
            self::$folderCache[$sku] = [];

            return [];
        }

        $images = [];

        foreach (self::SOURCE_DIRECTORIES as $source) {
            $directoryName = $source['directory'];
            $directoryPath = $directoryName === '' ? $rootPath : $rootPath . '/' . $directoryName;

            if (!is_dir($directoryPath)) {
                continue;
            }

            $categoryImages = [];

            try {
                $files = new \FilesystemIterator($directoryPath, \FilesystemIterator::SKIP_DOTS);
            } catch (\UnexpectedValueException) {
                continue;
            }

            foreach ($files as $file) {
                if (!$file->isFile() || !$file->isReadable()) {
                    continue;
                }

                $filename = $file->getFilename();
                $extension = strtolower((string) pathinfo($filename, PATHINFO_EXTENSION));

                if (!in_array($extension, self::SUPPORTED_EXTENSIONS, true)) {
                    continue;
                }

                $relativeUrl = $directoryName === ''
                    ? rawurlencode($filename)
                    : rawurlencode($directoryName) . '/' . rawurlencode($filename);
                $categoryImages[] = [
                    'attachment_id' => 0,
                    'category' => $source['category'],
                    'filename' => $filename,
                    'is_folder' => true,
                    'key' => 'folder:' . $sku . ':' . $source['category'] . ':' . $filename,
                    'path' => $file->getPathname(),
                    'srcset' => '',
                    'thumbnail_url' => $rootUrl . '/' . $relativeUrl,
                    'url' => $rootUrl . '/' . $relativeUrl,
                ];
            }

            usort(
                $categoryImages,
                fn (array $left, array $right): int => $this->compareFilenames(
                    (string) $left['filename'],
                    (string) $right['filename'],
                    $sku
                )
            );

            if ($source['category'] === 'promotion') {
                $categoryImages = array_slice($categoryImages, 0, self::PROMOTION_LIMIT);
            }

            array_push($images, ...$categoryImages);
        }

        self::$folderCache[$sku] = $images;

        return $images;
    }

    public function primaryUrl(\WC_Product $product, string $size = 'woocommerce_single'): string
    {
        $folderImages = $this->folderImages($product);

        if ($folderImages !== []) {
            return (string) $folderImages[0]['url'];
        }

        $imageId = (int) $product->get_image_id();
        $url = $imageId > 0 ? wp_get_attachment_image_url($imageId, $size) : false;

        return is_string($url) ? $url : wc_placeholder_img_src($size);
    }

    /**
     * @param string|array<int, int> $size
     * @param array<string, mixed> $attributes
     */
    public function folderImageHtml(\WC_Product $product, string|array $size, array $attributes = []): ?string
    {
        $folderImages = $this->folderImages($product);

        if ($folderImages === []) {
            return null;
        }

        $image = $folderImages[0];
        $sizeName = is_string($size) ? $size : 'woocommerce_thumbnail';
        $sizeClass = sanitize_html_class($sizeName);
        $customClass = isset($attributes['class']) ? trim((string) $attributes['class']) : '';
        $dimensions = $this->dimensions((string) $image['path']);
        $htmlAttributes = [
            'alt' => array_key_exists('alt', $attributes)
                ? (string) $attributes['alt']
                : wp_strip_all_tags($product->get_name()),
            'class' => trim('attachment-' . $sizeClass . ' size-' . $sizeClass . ' maruderm-folder-product-image ' . $customClass),
            'decoding' => 'async',
            'loading' => 'lazy',
            'src' => (string) $image['url'],
            'data-product-image-source' => 'folder',
        ];

        if ($dimensions !== null) {
            $htmlAttributes['width'] = (string) $dimensions['width'];
            $htmlAttributes['height'] = (string) $dimensions['height'];
        }

        foreach ($attributes as $name => $value) {
            if (in_array($name, ['alt', 'class', 'sizes', 'src', 'srcset'], true)) {
                continue;
            }

            $htmlAttributes[$name] = $value;
        }

        $parts = [];

        foreach ($htmlAttributes as $name => $value) {
            if ($value === false || $value === null || $value === '') {
                continue;
            }

            $parts[] = esc_attr((string) $name) . '="' . esc_attr((string) $value) . '"';
        }

        return '<img ' . implode(' ', $parts) . '>';
    }

    /** @return array<int, array<string, int|string|bool>> */
    private function woocommerceImages(\WC_Product $product): array
    {
        $imageIds = array_values(array_unique(array_filter(array_merge(
            [(int) $product->get_image_id()],
            array_map('intval', $product->get_gallery_image_ids())
        ))));
        $images = [];

        foreach ($imageIds as $imageId) {
            $url = wp_get_attachment_image_url($imageId, 'woocommerce_single');

            if (!is_string($url)) {
                continue;
            }

            $thumbnailUrl = wp_get_attachment_image_url($imageId, 'woocommerce_thumbnail');
            $images[] = [
                'attachment_id' => $imageId,
                'category' => 'woocommerce',
                'filename' => basename((string) get_attached_file($imageId)),
                'is_folder' => false,
                'key' => 'attachment:' . $imageId,
                'path' => (string) get_attached_file($imageId),
                'srcset' => (string) wp_get_attachment_image_srcset($imageId, 'woocommerce_single'),
                'thumbnail_url' => is_string($thumbnailUrl) ? $thumbnailUrl : $url,
                'url' => $url,
            ];
        }

        if ($images !== []) {
            return $images;
        }

        return [[
            'attachment_id' => 0,
            'category' => 'woocommerce',
            'filename' => '',
            'is_folder' => false,
            'key' => 'placeholder:' . $product->get_id(),
            'path' => '',
            'srcset' => '',
            'thumbnail_url' => wc_placeholder_img_src('woocommerce_thumbnail'),
            'url' => wc_placeholder_img_src('woocommerce_single'),
        ]];
    }

    private function compareFilenames(string $left, string $right, string $sku): int
    {
        $priority = $this->filenamePriority($left, $sku) <=> $this->filenamePriority($right, $sku);

        return $priority !== 0 ? $priority : strnatcasecmp($left, $right);
    }

    private function filenamePriority(string $filename, string $sku): int
    {
        $stem = (string) pathinfo($filename, PATHINFO_FILENAME);
        $normalized = strtolower((string) preg_replace('/[^a-z0-9]+/i', '_', trim($stem)));

        if ($normalized === $sku || preg_match('/^' . preg_quote($sku, '/') . '_0*1$/D', $normalized) === 1) {
            return 0;
        }

        return str_starts_with($normalized, $sku . '_') ? 1 : 2;
    }

    /** @return array{width: int, height: int}|null */
    private function dimensions(string $path): ?array
    {
        if (array_key_exists($path, self::$dimensionCache)) {
            return self::$dimensionCache[$path];
        }

        $dimensions = @getimagesize($path);

        if (!is_array($dimensions) || empty($dimensions[0]) || empty($dimensions[1])) {
            return null;
        }

        self::$dimensionCache[$path] = [
            'width' => (int) $dimensions[0],
            'height' => (int) $dimensions[1],
        ];

        return self::$dimensionCache[$path];
    }
}
