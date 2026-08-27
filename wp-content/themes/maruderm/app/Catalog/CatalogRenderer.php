<?php

declare(strict_types=1);

namespace Maruderm\Catalog;

use Maruderm\WooCommerce\ProductCardRenderer;

if (!defined('ABSPATH')) {
    exit();
}

final class CatalogRenderer
{
    private const ATTRIBUTE_GROUPS = [
        'skinTypes' => ['taxonomy' => 'pa_skin_type', 'label' => 'Тип шкіри', 'open' => true],
        'concerns' => ['taxonomy' => 'pa_skin_problem', 'label' => 'Проблема шкіри', 'open' => false],
        'hairNeeds' => ['taxonomy' => 'pa_hair_need', 'label' => 'Потреби волосся', 'open' => false],
    ];

    private CatalogRepository $repository;
    private ProductCardRenderer $product_cards;

    public function __construct(
        ?CatalogRepository $repository = null,
        ?ProductCardRenderer $product_cards = null
    ) {
        $this->repository = $repository ?? new CatalogRepository();
        $this->product_cards = $product_cards ?? new ProductCardRenderer($this->repository);
    }

    public function render(): void
    {
        $products = $this->repository->productsForCurrentView();
        $categories = $this->repository->categoryOptions($products);
        $title = $this->archiveTitle();
        $description = $this->archiveDescription();
        $initialCategory = $this->repository->initialCategory();

        echo '<main class="maruderm-catalog" data-catalog-root data-catalog-url="' . esc_url(wc_get_page_permalink('shop')) . '" data-catalog-title="Каталог догляду" data-catalog-description="' . esc_attr($this->defaultDescription()) . '" data-site-name="' . esc_attr(get_bloginfo('name')) . '" data-initial-category="' . esc_attr($initialCategory) . '" data-initial-category-label="' . esc_attr($initialCategory !== '' ? $title : '') . '" data-initial-category-description="' . esc_attr($initialCategory !== '' ? $description : '') . '" data-initial-category-url="' . esc_url($this->initialCategoryUrl()) . '">';
        woocommerce_output_all_notices();
        $this->renderHero($title, $description);
        $this->renderCategoryNavigation($this->repository->navigationCategories(), $initialCategory);
        echo '<section class="catalog-content"><div class="shell catalog-layout">';
        $this->renderFilters($products, $categories);
        $this->renderResults($products);
        echo '</div></section><div class="filter-overlay" data-filter-overlay></div></main>';
    }

    private function renderHero(string $title, string $description): void
    {
        echo '<section class="catalog-hero"><div class="shell">';
        echo '<nav class="breadcrumbs" aria-label="Навігаційний ланцюжок"><a href="' . esc_url(home_url('/')) . '">Головна</a><span>/</span>';

        echo '<a href="' . esc_url(wc_get_page_permalink('shop')) . '" data-catalog-breadcrumb-link' . (is_shop() ? ' hidden' : '') . '>Каталог</a><span data-catalog-breadcrumb-separator' . (is_shop() ? ' hidden' : '') . '>/</span>';
        echo '<span data-catalog-breadcrumb-current>' . esc_html($title) . '</span></nav>';
        echo '<div class="catalog-hero__content"><div><span class="kicker">Уся колекція Maruderm</span><h1 data-catalog-heading>' . esc_html($title) . '</h1></div>';
        echo '<p data-catalog-description>' . esc_html($description) . '</p></div></div></section>';
    }

    /**
     * @param array<int, array{value: string, label: string, url: string, image: string, tone: string}> $categories
     */
    private function renderCategoryNavigation(array $categories, string $active_category): void
    {
        if ($categories === []) {
            return;
        }

        echo '<section class="catalog-categories" aria-labelledby="catalog-categories-title"><div class="shell">';
        echo '<h2 class="sr-only" id="catalog-categories-title">Основні категорії товарів</h2>';
        echo '<ul class="catalog-categories__list" data-catalog-category-list>';

        foreach ($categories as $category) {
            $is_active = $category['value'] === $active_category;
            $item_classes = 'catalog-categories__item' . ($is_active ? ' is-active' : '');
            $link_classes = 'catalog-categories__link catalog-categories__link--' . $category['tone'];

            echo '<li class="' . esc_attr($item_classes) . '">';
            echo '<a class="' . esc_attr($link_classes) . '" href="' . esc_url($category['url']) . '"' . ($is_active ? ' aria-current="page"' : '') . '>';
            echo '<span class="catalog-categories__circle"><img src="' . esc_url($category['image']) . '" alt="" loading="lazy"></span>';
            echo '<span class="catalog-categories__label">' . esc_html($category['label']) . '</span>';
            echo '</a></li>';
        }

        echo '</ul></div></section>';
    }

    /**
     * @param \WC_Product[] $products
     * @param array<int, array{value: string, label: string, count: int, depth: int, url: string, description: string}> $categories
     */
    private function renderFilters(array $products, array $categories): void
    {
        echo '<aside class="catalog-filters" data-filter-panel aria-label="Фільтри товарів">';
        echo '<div class="catalog-filters__mobile-head"><strong>Фільтри</strong><button type="button" aria-label="Закрити фільтри" data-filter-close>' . $this->closeIcon() . '</button></div>';
        echo '<div class="filter-panel__heading"><strong>Фільтри</strong><button type="button" data-clear-all>Очистити все</button></div>';
        $this->renderFilterGroup('category', 'Категорія', $categories, true);

        foreach (self::ATTRIBUTE_GROUPS as $group => $definition) {
            $options = $this->repository->attributeOptions($products, $definition['taxonomy']);
            $this->renderFilterGroup($group, $definition['label'], $options, $definition['open']);
        }

        echo '<div class="filter-group is-open" data-filter-group="price"><button class="filter-group__toggle" type="button" aria-expanded="true"><span>Ціна</span>' . $this->chevronIcon() . '</button><div class="filter-group__body">';

        foreach ([
            ['0-500', 'До 500 ₴'],
            ['500-800', '500–800 ₴'],
            ['800-1200', '800–1200 ₴'],
            ['1200-999999', 'Від 1200 ₴'],
        ] as [$value, $label]) {
            $this->renderFilterOption('price', $value, $label);
        }

        echo '</div></div><div class="catalog-filters__mobile-action"><button class="button button--dark" type="button" data-filter-close>Показати товари <span data-mobile-count></span></button></div></aside>';
    }

    /**
     * @param array<int, array{value: string, label: string, count: int, depth?: int, url?: string, description?: string}> $options
     */
    private function renderFilterGroup(string $group, string $label, array $options, bool $open): void
    {
        if ($options === []) {
            return;
        }

        $classes = 'filter-group' . ($open ? ' is-open' : '');
        echo '<div class="' . esc_attr($classes) . '" data-filter-group="' . esc_attr($group) . '">';
        echo '<button class="filter-group__toggle" type="button" aria-expanded="' . ($open ? 'true' : 'false') . '"><span>' . esc_html($label) . '</span>' . $this->chevronIcon() . '</button>';
        echo '<div class="filter-group__body">';

        foreach ($options as $option) {
            $this->renderFilterOption(
                $group,
                $option['value'],
                $option['label'],
                $option['count'],
                $option['url'] ?? '',
                $option['depth'] ?? 0,
                $option['description'] ?? ''
            );
        }

        echo '</div></div>';
    }

    private function renderFilterOption(
        string $group,
        string $value,
        string $label,
        ?int $count = null,
        string $url = '',
        int $depth = 0,
        string $description = ''
    ): void
    {
        echo '<label class="filter-check" data-depth="' . esc_attr((string) $depth) . '"><input type="checkbox" name="' . esc_attr($group) . '" value="' . esc_attr($value) . '"';

        if ($url !== '') {
            echo ' data-category-url="' . esc_url($url) . '"';
            echo ' data-category-description="' . esc_attr($description) . '"';
        }

        echo '><span class="filter-check__box"></span><span>' . esc_html($label) . '</span>';

        if ($count !== null) {
            echo '<small>' . esc_html((string) $count) . '</small>';
        }

        echo '</label>';
    }

    /** @param \WC_Product[] $products */
    private function renderResults(array $products): void
    {
        echo '<div class="catalog-results"><div class="catalog-toolbar"><div><button class="mobile-filter-button" type="button" data-filter-open>' . $this->filterIcon() . ' Фільтри <span data-active-filter-count></span></button>';
        echo '<p><strong data-result-count>' . esc_html((string) count($products)) . '</strong> товарів</p></div>';
        echo '<label class="catalog-sort"><span>Сортувати:</span><select data-sort><option value="popular">За популярністю</option><option value="newest">Спочатку нові</option><option value="price-asc">Від дешевих</option><option value="price-desc">Від дорогих</option><option value="name">За назвою</option></select></label></div>';
        echo '<div class="active-filters" data-active-filters hidden></div><div class="product-grid" data-product-grid data-collection="catalog">';

        foreach ($products as $product) {
            echo $this->product_cards->render($product); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Shared renderer escapes dynamic values.
        }

        echo '<div class="product-grid-empty" data-catalog-empty hidden><h3>Нічого не знайшлося</h3><p>Спробуй прибрати частину фільтрів або змінити пошук.</p></div>';
        echo '</div></div>';
    }

    private function initialCategoryUrl(): string
    {
        $term = get_queried_object();

        if (!$term instanceof \WP_Term || $term->taxonomy !== 'product_cat') {
            return '';
        }

        $url = get_term_link($term);

        return is_wp_error($url) ? '' : $url;
    }

    private function archiveTitle(): string
    {
        if (is_shop()) {
            return 'Каталог догляду';
        }

        $title = single_term_title('', false);

        return is_string($title) && $title !== '' ? $title : 'Каталог догляду';
    }

    private function archiveDescription(): string
    {
        if (!is_shop()) {
            $description = trim(wp_strip_all_tags(term_description()));

            if ($description !== '') {
                return $description;
            }
        }

        return $this->defaultDescription();
    }

    private function defaultDescription(): string
    {
        return 'Фільтруй за потребами, типом шкіри та категоріями. Знайди формули, що пасують саме твоїй рутині.';
    }

    private function chevronIcon(): string
    {
        return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"></path></svg>';
    }

    private function closeIcon(): string
    {
        return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 5 14 14M19 5 5 19"></path></svg>';
    }

    private function filterIcon(): string
    {
        return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16M7 12h10M10 18h4"></path></svg>';
    }

}
