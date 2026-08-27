<?php

declare(strict_types=1);

namespace Maruderm\WooCommerce;

if (!defined('ABSPATH')) {
    exit();
}

/** Resolves live promotion-card copy, imagery and category tone. */
final class ProductCardPromotion
{
    private const CATEGORY_TONES = [
        'zasoby-dlya-doglyadu-za-volossyam' => 'hair',
        'zasoby-dlya-doglyadu-za-tilom' => 'body',
        'gunes-bakim-urunleri' => 'sun',
        'makiyazh' => 'makeup',
        'zasoby-dlya-doglyadu-za-shkiroyu' => 'skin',
    ];

    private ProductGoodFor $goodFor;
    private ProductImageRepository $images;

    public function __construct(
        ?ProductGoodFor $goodFor = null,
        ?ProductImageRepository $images = null
    ) {
        $this->goodFor = $goodFor ?? new ProductGoodFor();
        $this->images = $images ?? new ProductImageRepository();
    }

    /**
     * @param string[] $categorySlugs
     * @return array{heading: string, items: string[], image_url: string, image_source: string, tone: string}|null
     */
    public function resolve(\WC_Product $product, array $categorySlugs): ?array
    {
        $items = $this->goodFor->items($product);

        if ($items === []) {
            return null;
        }

        $promotionImage = $this->promotionImage($product);

        return [
            'heading' => 'Кому та коли радимо?',
            'items' => $items,
            'image_url' => $promotionImage ?? $this->images->primaryUrl($product, 'woocommerce_thumbnail'),
            'image_source' => $promotionImage === null ? 'primary' : 'promotion',
            'tone' => $this->tone($categorySlugs),
        ];
    }

    private function promotionImage(\WC_Product $product): ?string
    {
        foreach ($this->images->folderImages($product) as $image) {
            if (($image['category'] ?? '') === 'promotion') {
                return (string) $image['url'];
            }
        }

        return null;
    }

    /** @param string[] $categorySlugs */
    private function tone(array $categorySlugs): string
    {
        foreach (self::CATEGORY_TONES as $categorySlug => $tone) {
            if (in_array($categorySlug, $categorySlugs, true)) {
                return $tone;
            }
        }

        return 'skin';
    }
}
