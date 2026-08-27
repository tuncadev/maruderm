<?php

declare(strict_types=1);

namespace Maruderm\WooCommerce;

use Maruderm\Catalog\CatalogRepository;

if (!defined('ABSPATH')) {
    exit();
}

/** Renders the canonical Maruderm product card for catalog and recommendation grids. */
final class ProductCardRenderer
{
    private CatalogRepository $repository;
    private ProductBadges $badges;
    private StockNotificationRenderer $stock_notifications;
    private ProductCardPromotion $promotions;

    public function __construct(
        ?CatalogRepository $repository = null,
        ?ProductBadges $badges = null,
        ?StockNotificationRenderer $stock_notifications = null,
        ?ProductCardPromotion $promotions = null
    ) {
        $this->repository = $repository ?? new CatalogRepository();
        $this->badges = $badges ?? new ProductBadges();
        $this->stock_notifications = $stock_notifications ?? new StockNotificationRenderer();
        $this->promotions = $promotions ?? new ProductCardPromotion();
    }

    public function render(\WC_Product $product): string
    {
        $categories = $this->repository->topCategories($product);
        $category_slugs = $this->repository->categorySlugs($product);
        $category_label = $categories[0]->name ?? 'Maruderm';
        $badge = $this->badges->resolve($product);
        $promotion = $this->promotions->resolve($product, $category_slugs);
        $created_at = $product->get_date_created();
        $created_timestamp = $created_at !== null ? $created_at->getTimestamp() : 0;
        $button_label = $product->is_type('simple') ? 'Додати до кошика' : 'Обрати варіант';
        $button_classes = implode(' ', array_filter([
            'product-card__cart',
            'product_type_' . $product->get_type(),
            $product->supports('ajax_add_to_cart') ? 'add_to_cart_button ajax_add_to_cart' : '',
        ]));
        $filter_price = $product->is_in_stock() ? (string) (float) $product->get_price() : '';

        ob_start();
        ?>
        <article class="product-card<?= $product->is_in_stock() ? '' : ' is-out-of-stock'; ?>" data-product-id="<?= esc_attr((string) $product->get_id()); ?>" data-product-name="<?= esc_attr(wp_strip_all_tags($product->get_name())); ?>" data-category="<?= esc_attr(implode(' ', $category_slugs)); ?>" data-skin-types="<?= esc_attr(implode(' ', $this->repository->termSlugs($product, 'pa_skin_type'))); ?>" data-concerns="<?= esc_attr(implode(' ', $this->repository->termSlugs($product, 'pa_skin_problem'))); ?>" data-hair-needs="<?= esc_attr(implode(' ', $this->repository->termSlugs($product, 'pa_hair_need'))); ?>" data-price="<?= esc_attr($filter_price); ?>" data-popularity="<?= esc_attr((string) $product->get_total_sales()); ?>" data-created="<?= esc_attr((string) $created_timestamp); ?>" data-in-stock="<?= $product->is_in_stock() ? 'yes' : 'no'; ?>">
            <a class="product-card__image" href="<?= esc_url($product->get_permalink()); ?>" aria-label="<?= esc_attr('Переглянути ' . $product->get_name()); ?>">
                <span class="product-card__media product-card__media--primary">
                    <?= wp_kses_post($product->get_image('woocommerce_thumbnail', ['loading' => 'lazy'])); ?>
                </span>
                <?php if ($promotion !== null) : ?>
                    <div class="product-card__promo product-card__promo--<?= esc_attr($promotion['tone']); ?>" data-product-card-promo data-product-card-promo-source="<?= esc_attr($promotion['image_source']); ?>" aria-hidden="true">
                        <div class="product-card__promo-copy">
                            <strong class="product-card__promo-title"><?= esc_html($promotion['heading']); ?></strong>
                            <ul class="product-card__promo-list">
                                <?php foreach ($promotion['items'] as $item) : ?>
                                    <li><?= esc_html($item); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <img class="product-card__promo-image" src="<?= esc_url($promotion['image_url']); ?>" alt="" loading="lazy" decoding="async">
                    </div>
                <?php endif; ?>
                <?php if ($badge !== null) : ?>
                    <span class="product-card__badge product-card__badge--<?= esc_attr($badge['tone']); ?> maruderm-product-badge maruderm-product-badge--<?= esc_attr($badge['tone']); ?>"><?= esc_html($badge['label']); ?></span>
                <?php endif; ?>
            </a>
            <button class="product-card__heart" type="button" aria-label="Додати в обране" data-wishlist-toggle><?= $this->heartIcon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
            <div class="product-card__body">
                <span class="product-card__category"><?= esc_html($category_label); ?></span>
                <h3><a href="<?= esc_url($product->get_permalink()); ?>"><?= esc_html($product->get_name()); ?></a></h3>
                <div class="product-card__footer">
                    <span class="product-card__price"><?= wp_kses_post($product->get_price_html()); ?></span>
                    <?php if ($product->is_in_stock()) : ?>
                        <a class="<?= esc_attr($button_classes); ?>" href="<?= esc_url($product->add_to_cart_url()); ?>" data-quantity="1" data-product_id="<?= esc_attr((string) $product->get_id()); ?>" data-product_sku="<?= esc_attr($product->get_sku()); ?>" aria-label="<?= esc_attr($button_label . ': ' . $product->get_name()); ?>"><?= $this->bagIcon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
                    <?php else : ?>
                        <?= $this->stock_notifications->cardButton($product); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    <?php endif; ?>
                </div>
            </div>
        </article>
        <?php

        return (string) ob_get_clean();
    }

    private function heartIcon(): string
    {
        return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.8 4.7a5.5 5.5 0 0 0-7.8 0L12 5.8l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 21l8.8-8.5a5.5 5.5 0 0 0 0-7.8Z"></path></svg>';
    }

    private function bagIcon(): string
    {
        return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 8h14l-1 12H6L5 8Z"></path><path d="M9 9V6a3 3 0 0 1 6 0v3"></path></svg>';
    }
}
