<?php

declare(strict_types=1);

namespace Maruderm\Homepage;

use Maruderm\LandingPage\LandingPageCatalog;
use Maruderm\LandingPage\LandingPageContent;
use Maruderm\Settings\HomepageSettings;
use Maruderm\WooCommerce\ProductCardRenderer;

if (!defined('ABSPATH')) {
    exit();
}

/** Renders the live homepage with the canonical maruderm.html structure. */
final class HomepageRenderer
{
    private LandingPageCatalog $catalog;
    private LandingPageContent $content;
    private HomepageSettings $settings;
    private ProductCardRenderer $productCards;

    public function __construct(
        ?LandingPageCatalog $catalog = null,
        ?LandingPageContent $content = null,
        ?HomepageSettings $settings = null,
        ?ProductCardRenderer $productCards = null
    ) {
        $this->catalog = $catalog ?? new LandingPageCatalog();
        $this->content = $content ?? new LandingPageContent();
        $this->settings = $settings ?? new HomepageSettings();
        $this->productCards = $productCards ?? new ProductCardRenderer();
    }

    public function render(): void
    {
        $settings = $this->settings->all();
        $categoryIds = is_array($settings['categories']['category_ids'] ?? null)
            ? $settings['categories']['category_ids']
            : [];
        $productCategoryIds = is_array($settings['new_products']['category_ids'] ?? null)
            ? $settings['new_products']['category_ids']
            : [];
        $categories = $this->catalog->categories(5, $categoryIds);
        $products = $this->catalog->products(
            'latest',
            (int) ($settings['new_products']['product_limit'] ?? 8),
            [],
            $productCategoryIds
        );
        $editorialIds = array_values(array_filter([
            (int) ($settings['editorial']['primary_category_id'] ?? 0),
            (int) ($settings['editorial']['secondary_category_id'] ?? 0),
        ]));
        $editorialCategories = $editorialIds === []
            ? array_slice($categories, 0, 2)
            : $this->catalog->categories(2, $editorialIds);

        echo '<main id="main-content">';
        $this->renderHero($categories, $settings['hero']);
        $this->renderCategories($categories, $settings['categories']);
        $this->renderProducts($products, $settings['new_products']);
        $this->renderEditorial($editorialCategories, $settings['editorial']);
        $this->renderRoutine($settings['routine']);
        $this->renderClosing($settings['closing']);
        echo '</main>';
    }

    /** @param \WP_Term[] $categories */
    private function renderHero(array $categories, array $settings): void
    {
        $copy = $this->content->hero();
        $product = $this->catalog->heroProduct((int) ($settings['product_id'] ?? 0));
        $primaryCategories = (int) ($settings['primary_category_id'] ?? 0) > 0
            ? $this->catalog->categories(1, [(int) $settings['primary_category_id']])
            : [];
        $primaryCategory = $primaryCategories[0] ?? ($categories[0] ?? null);
        $catalogUrl = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/catalog/');
        $primaryUrl = $primaryCategory instanceof \WP_Term
            ? $this->catalog->categoryUrl($primaryCategory)
            : $catalogUrl;
        $publishedProducts = (int) (wp_count_posts('product')->publish ?? 0);

        echo '<section class="home-hero"><div class="shell home-hero__grid"><div class="home-hero__copy">';
        echo '<span class="kicker">' . esc_html((string) ($settings['eyebrow'] ?? $copy['eyebrow'])) . '</span>';
        echo '<h1>' . wp_kses((string) ($settings['heading'] ?? $copy['title']), ['em' => [], 'br' => []]) . '</h1>';
        echo '<p>' . esc_html((string) ($settings['description'] ?? $copy['description'])) . '</p>';
        echo '<div class="home-hero__actions"><a class="button button--dark" href="' . esc_url($primaryUrl) . '">';
        echo esc_html($copy['primary_label']) . $this->arrowIcon() . '</a>';
        echo '<a class="text-link" href="#new-products">' . esc_html($copy['secondary_label']) . '</a></div>';
        echo '<div class="home-hero__proof"><span><strong>' . esc_html((string) $publishedProducts) . '+</strong> засобів</span>';
        echo '<span><strong>' . esc_html((string) count($categories)) . '</strong> категорій догляду</span></div></div>';
        echo '<div class="home-hero__visual"><span class="home-hero__orbit home-hero__orbit--one"></span>';
        echo '<span class="home-hero__orbit home-hero__orbit--two"></span>';

        if ($product instanceof \WC_Product) {
            echo wp_kses_post($product->get_image('woocommerce_single', [
                'loading' => 'eager',
                'fetchpriority' => 'high',
            ]));
        }

        echo '<span class="home-hero__note home-hero__note--top">skin first</span>';
        echo '<span class="home-hero__note home-hero__note--bottom">made for real life</span>';

        if ($product instanceof \WC_Product) {
            echo '<div class="home-hero__product-label"><small>Новинка</small><strong>'
                . esc_html($product->get_name()) . '</strong></div>';
        }

        echo '</div></div></section>';
    }

    /** @param \WP_Term[] $categories */
    private function renderCategories(array $categories, array $settings): void
    {
        if ($categories === []) {
            return;
        }

        $catalogUrl = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/catalog/');
        $images = is_array($settings['category_images'] ?? null) ? $settings['category_images'] : [];

        echo '<section class="section category-section"><div class="shell"><header class="section-heading"><div>';
        echo '<span class="kicker">' . esc_html((string) ($settings['eyebrow'] ?? 'Обирай за категорією')) . '</span>';
        echo '<h2>' . wp_kses((string) ($settings['heading'] ?? 'З чого почнемо?'), ['em' => [], 'br' => []]) . '</h2>';
        echo '</div><a class="text-link" href="' . esc_url($catalogUrl) . '">Переглянути все' . $this->arrowIcon() . '</a></header>';
        echo '<div class="category-grid" data-category-grid>';

        foreach ($categories as $category) {
            $imageId = (int) ($images[$category->term_id] ?? 0);
            echo '<a class="category-card" href="' . esc_url($this->catalog->categoryUrl($category)) . '">';
            echo '<img src="' . esc_url($this->catalog->categoryImage($category, 'medium_large', $imageId)) . '" alt="' . esc_attr($category->name) . '" loading="lazy">';
            echo '<span class="category-card__meta"><strong>' . esc_html($category->name) . '</strong><small>';
            echo esc_html((string) $this->catalog->inStockProductCount($category)) . ' засобів</small></span></a>';
        }

        echo '</div></div></section>';
    }

    /** @param \WC_Product[] $products */
    private function renderProducts(array $products, array $settings): void
    {
        if ($products === []) {
            return;
        }

        $catalogUrl = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/catalog/');
        echo '<section class="section home-products" id="new-products"><div class="shell"><header class="section-heading"><div>';
        echo '<span class="kicker">' . esc_html((string) ($settings['eyebrow'] ?? 'Щойно у Maruderm')) . '</span>';
        echo '<h2>' . wp_kses((string) ($settings['heading'] ?? 'Новинки для твоєї полиці'), ['em' => [], 'br' => []]) . '</h2>';
        echo '</div><a class="text-link" href="' . esc_url(add_query_arg('sort', 'newest', $catalogUrl)) . '">Переглянути все' . $this->arrowIcon() . '</a></header>';
        echo '<div class="product-grid" data-product-grid data-product-limit="' . esc_attr((string) count($products)) . '" data-product-collection="new">';

        foreach ($products as $product) {
            echo $this->productCards->render($product); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }

        echo '</div></div></section>';
    }

    /** @param \WP_Term[] $categories */
    private function renderEditorial(array $categories, array $settings): void
    {
        if ($categories === []) {
            return;
        }

        $primary = $categories[0];
        $secondary = $categories[1] ?? $primary;
        $primaryImage = $this->catalog->categoryImage($primary, 'large', (int) ($settings['primary_image_id'] ?? 0));
        $secondaryImage = $this->catalog->categoryImage($secondary, 'large', (int) ($settings['secondary_image_id'] ?? 0));

        echo '<section class="section editorial"><div class="shell editorial__grid">';
        echo '<a class="editorial-story editorial-story--large" href="' . esc_url($this->catalog->categoryUrl($primary)) . '">';
        echo '<img src="' . esc_url($primaryImage) . '" alt="' . esc_attr($primary->name) . '" loading="lazy"><span class="editorial-story__shade"></span>';
        echo '<span class="editorial-story__copy"><small>' . esc_html((string) ($settings['eyebrow'] ?? 'Ритуал дня')) . '</small>';
        echo '<strong>' . wp_kses((string) ($settings['heading'] ?? 'Турбота про шкіру — це час для себе.'), ['em' => [], 'br' => []]) . '</strong>';
        echo '<span>Відкрити добірку' . $this->arrowIcon() . '</span></span></a>';
        echo '<div class="editorial__stack"><article class="manifesto"><span>maruderm notes</span><h2>Менше кроків.<br>Більше сенсу.</h2>';
        echo '<p>' . esc_html((string) ($settings['description'] ?? 'Будуй рутину навколо потреб шкіри, а не трендів.')) . '</p></article>';
        echo '<a class="editorial-story editorial-story--small" href="' . esc_url($this->catalog->categoryUrl($secondary)) . '">';
        echo '<img src="' . esc_url($secondaryImage) . '" alt="' . esc_attr($secondary->name) . '" loading="lazy"><span class="editorial-story__shade"></span>';
        echo '<span class="editorial-story__copy"><small>Колір і догляд</small><strong>' . esc_html($secondary->name) . '</strong>';
        echo '<span>Дивитися' . $this->arrowIcon() . '</span></span></a></div></div></section>';
    }

    private function renderRoutine(array $settings): void
    {
        echo '<section class="section routine"><div class="shell"><div class="routine__intro">';
        echo '<span class="kicker">' . esc_html((string) ($settings['eyebrow'] ?? 'Три прості кроки')) . '</span>';
        echo '<h2>' . wp_kses((string) ($settings['heading'] ?? 'Рутина без перевантаження'), ['em' => [], 'br' => []]) . '</h2>';
        echo '<p>' . esc_html((string) ($settings['description'] ?? 'Послідовність, яку легко підтримувати щодня.')) . '</p></div>';
        echo '<div class="routine__grid">';

        foreach ($this->content->routines() as $routine) {
            echo '<article class="routine-card routine-card--' . esc_attr($routine['tone']) . '"><span>' . esc_html($routine['step']) . '</span>';
            echo '<div><h3>' . esc_html($routine['title']) . '</h3><p>' . esc_html($routine['text']) . '</p></div></article>';
        }

        echo '</div></div></section>';
    }

    private function renderClosing(array $settings): void
    {
        $catalogUrl = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/catalog/');
        echo '<section class="section home-cta"><div class="shell"><div class="home-cta__card"><span class="home-cta__shape"></span><div>';
        echo '<span class="kicker">' . esc_html((string) ($settings['eyebrow'] ?? 'Твій догляд, твої правила')) . '</span>';
        echo '<h2>' . wp_kses((string) ($settings['heading'] ?? 'Знайди формули, які хочеться використовувати щодня.'), ['em' => [], 'br' => []]) . '</h2></div>';
        echo '<a class="button" href="' . esc_url($catalogUrl) . '">Перейти до каталогу' . $this->arrowIcon() . '</a></div></div></section>';
    }

    private function arrowIcon(): string
    {
        return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"></path></svg>';
    }
}
