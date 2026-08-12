<?php

declare(strict_types=1);

namespace Maruderm\LandingPage;

use Maruderm\Settings\HomepageSettings;
use Maruderm\WooCommerce\ProductBadges;

if (!defined('ABSPATH')) {
    exit();
}

final class LandingPageRenderer
{
    private LandingPageCatalog $catalog;
    private LandingPageContent $content;
    private ProductBadges $badges;
    private HomepageSettings $homepageSettings;
    private bool $renderingHomepage = false;

    public function __construct(
        ?LandingPageCatalog $catalog = null,
        ?LandingPageContent $content = null,
        ?ProductBadges $badges = null,
        ?HomepageSettings $homepageSettings = null
    )
    {
        $this->catalog = $catalog ?? new LandingPageCatalog();
        $this->content = $content ?? new LandingPageContent();
        $this->badges = $badges ?? new ProductBadges();
        $this->homepageSettings = $homepageSettings ?? new HomepageSettings();
    }

    public function render(): void
    {
        $this->renderLandingPage();
    }

    public function renderHomepage(): void
    {
        $this->renderingHomepage = true;
        $settings = $this->homepageSettings->all();
        $category_ids = is_array($settings['categories']['category_ids']) ? $settings['categories']['category_ids'] : [];
        $product_category_ids = is_array($settings['new_products']['category_ids']) ? $settings['new_products']['category_ids'] : [];
        $categories = $this->catalog->categories(6, $category_ids);
        $new_arrivals = $this->catalog->products(
            'latest',
            (int) $settings['new_products']['product_limit'],
            [],
            $product_category_ids
        );
        $editorial_ids = array_values(array_filter([
            (int) $settings['editorial']['primary_category_id'],
            (int) $settings['editorial']['secondary_category_id'],
        ]));
        $editorial_categories = $editorial_ids === []
            ? $categories
            : $this->catalog->categories(2, $editorial_ids);

        echo '<main class="md-landing md-homepage" id="main-content">';
        $this->renderHero($categories);
        $this->renderCategoryRail($categories);
        $this->renderProductShelf('Щойно у Maruderm', 'Новинки для твоєї полиці', $new_arrivals, 'mint', 'new_products');
        $this->renderEditorial($editorial_categories);
        $this->renderRoutine();
        $this->renderClosingCallout();
        echo '</main>';
        $this->renderingHomepage = false;
    }

    private function renderLandingPage(): void
    {
        $categories = $this->catalog->categories();
        $new_arrivals = $this->catalog->products('latest');
        $new_arrival_ids = array_map(
            static fn (\WC_Product $product): int => $product->get_id(),
            $new_arrivals
        );
        $popular = $this->catalog->products('popular', 8, $new_arrival_ids);

        echo '<main class="md-landing" id="main-content">';
        $this->renderHero($categories);
        $this->renderCategoryRail($categories);
        $this->renderProductShelf('Щойно у Maruderm', 'Новинки для твоєї полиці', $new_arrivals, 'mint');
        $this->renderEditorial($categories);
        $this->renderRoutine();
        $this->renderProductShelf('Обирають зараз', 'Формули, з яких варто почати', $popular, 'lavender');
        $this->renderBenefits();
        $this->renderClosingCallout();
        echo '</main>';
    }

    /** @param \WP_Term[] $categories */
    private function renderHero(array $categories): void
    {
        $hero = $this->content->hero();
        $intro = $this->sectionIntro('hero', [
            'eyebrow' => $hero['eyebrow'],
            'heading' => $hero['title'],
            'description' => $hero['description'],
        ]);
        $settings = $this->renderingHomepage ? $this->homepageSettings->section('hero') : [];
        $product = $this->catalog->heroProduct((int) ($settings['product_id'] ?? 0));
        $shop_url = wc_get_page_permalink('shop');
        $primary_categories = $this->renderingHomepage && (int) ($settings['primary_category_id'] ?? 0) > 0
            ? $this->catalog->categories(1, [(int) $settings['primary_category_id']])
            : [];
        $primary_category = $primary_categories[0] ?? ($categories[0] ?? null);
        $category_url = $primary_category instanceof \WP_Term ? $this->catalog->categoryUrl($primary_category) : $shop_url;
        $published_products = wp_count_posts('product')->publish ?? 0;
        $category_count = count($categories);

        echo '<section class="md-hero md-reveal">';
        echo '<div class="md-shell md-hero__grid">';
        echo '<div class="md-hero__copy">';
        echo '<span class="md-kicker">' . esc_html($intro['eyebrow']) . '</span>';
        echo '<h1>' . wp_kses($intro['heading'], ['em' => [], 'br' => []]) . '</h1>';
        echo '<p>' . esc_html($intro['description']) . '</p>';
        echo '<div class="md-actions">';
        echo '<a class="md-button md-button--dark" href="' . esc_url($category_url) . '">' . esc_html($hero['primary_label']) . $this->arrowIcon() . '</a>';
        echo '<a class="md-text-link" href="#md-new-arrivals">' . esc_html($hero['secondary_label']) . '</a>';
        echo '</div>';
        echo '<div class="md-hero__proof"><span><strong>' . esc_html((string) $published_products) . '</strong> засобів</span><span><strong>' . esc_html((string) $category_count) . '</strong> категорій догляду</span></div>';
        echo '</div>';
        echo '<div class="md-hero__visual">';
        echo '<span class="md-orbit md-orbit--one"></span><span class="md-orbit md-orbit--two"></span>';

        if ($product instanceof \WC_Product) {
            echo '<a class="md-hero-product" href="' . esc_url($product->get_permalink()) . '">';
            echo wp_kses_post($product->get_image('woocommerce_single', ['loading' => 'eager', 'fetchpriority' => 'high']));
            echo '<span class="md-hero-product__label"><small>Новинка</small><strong>' . esc_html($product->get_name()) . '</strong></span>';
            echo '</a>';
        }

        echo '<span class="md-floating-note md-floating-note--top">skin first</span>';
        echo '<span class="md-floating-note md-floating-note--bottom">made for real life</span>';
        echo '</div>';
        echo '</div>';
        echo '</section>';
    }

    /** @param \WP_Term[] $categories */
    private function renderCategoryRail(array $categories): void
    {
        if ($categories === []) {
            return;
        }

        echo '<section class="md-section md-categories md-reveal" aria-labelledby="md-categories-title">';
        echo '<div class="md-shell">';
        $intro = $this->sectionIntro('categories', [
            'eyebrow' => 'Обирай за категорією',
            'heading' => 'З чого почнемо?',
            'description' => '',
        ]);
        echo $this->sectionHeading($intro, 'md-categories-title', wc_get_page_permalink('shop'));
        echo '<div class="md-category-grid">';

        $settings = $this->renderingHomepage ? $this->homepageSettings->section('categories') : [];
        $images = isset($settings['category_images']) && is_array($settings['category_images'])
            ? $settings['category_images']
            : [];

        foreach ($categories as $index => $category) {
            $image_id = (int) ($images[$category->term_id] ?? 0);
            echo '<a class="md-category-card md-category-card--' . esc_attr((string) (($index % 5) + 1)) . '" href="' . esc_url($this->catalog->categoryUrl($category)) . '">';
            echo '<span class="md-category-card__image"><img src="' . esc_url($this->catalog->categoryImage($category, 'medium_large', $image_id)) . '" alt="" loading="lazy"></span>';
            echo '<span class="md-category-card__meta"><strong>' . esc_html($category->name) . '</strong><small>' . esc_html((string) $this->catalog->inStockProductCount($category)) . ' засобів</small></span>';
            echo $this->arrowIcon();
            echo '</a>';
        }

        echo '</div></div></section>';
    }

    /** @param \WC_Product[] $products */
    private function renderProductShelf(
        string $eyebrow,
        string $title,
        array $products,
        string $tone,
        string $settingsKey = ''
    ): void
    {
        if ($products === []) {
            return;
        }

        $section_id = $eyebrow === 'Щойно у Maruderm' ? 'md-new-arrivals' : 'md-popular-products';
        echo '<section class="md-section md-products md-products--' . esc_attr($tone) . ' md-reveal" id="' . esc_attr($section_id) . '" aria-labelledby="' . esc_attr($section_id . '-title') . '">';
        echo '<div class="md-shell">';
        $intro = $this->sectionIntro($settingsKey, [
            'eyebrow' => $eyebrow,
            'heading' => $title,
            'description' => '',
        ]);
        echo $this->sectionHeading($intro, $section_id . '-title', wc_get_page_permalink('shop'));
        echo '<div class="md-product-grid">';

        foreach ($products as $product) {
            $this->renderProductCard($product);
        }

        echo '</div></div></section>';
    }

    private function renderProductCard(\WC_Product $product): void
    {
        if (!$product->is_in_stock()) {
            return;
        }

        $badge = $this->badges->resolve($product);
        $button_label = $product->is_type('simple') ? 'До кошика' : 'Обрати';
        $button_classes = implode(' ', array_filter([
            'md-product-card__cart',
            'button',
            'product_type_' . $product->get_type(),
            $product->supports('ajax_add_to_cart') ? 'add_to_cart_button ajax_add_to_cart' : '',
        ]));
        $category_names = wp_get_post_terms($product->get_id(), 'product_cat', ['fields' => 'names']);
        $category_label = is_wp_error($category_names) || $category_names === [] ? 'Maruderm' : $category_names[0];

        echo '<article class="md-product-card" data-stock-status="' . esc_attr($product->get_stock_status()) . '">';
        echo '<a class="md-product-card__image" href="' . esc_url($product->get_permalink()) . '">';
        echo wp_kses_post($product->get_image('woocommerce_thumbnail', ['loading' => 'lazy']));
        if ($badge !== null) {
            echo '<span class="md-product-card__badge maruderm-product-badge maruderm-product-badge--' . esc_attr($badge['tone']) . '">' . esc_html($badge['label']) . '</span>';
        }
        echo '<span class="md-product-card__quick">Переглянути</span>';
        echo '</a>';
        echo '<div class="md-product-card__body">';
        echo '<span class="md-product-card__category">' . esc_html((string) $category_label) . '</span>';
        echo '<h3><a href="' . esc_url($product->get_permalink()) . '">' . esc_html($product->get_name()) . '</a></h3>';
        echo '<div class="md-product-card__footer"><span class="md-product-card__price">' . wp_kses_post($product->get_price_html()) . '</span>';
        echo '<a class="' . esc_attr($button_classes) . '" href="' . esc_url($product->add_to_cart_url()) . '" data-quantity="1" data-product_id="' . esc_attr((string) $product->get_id()) . '" data-product_sku="' . esc_attr($product->get_sku()) . '" aria-label="' . esc_attr($button_label . ': ' . $product->get_name()) . '"><span>' . esc_html($button_label) . '</span>' . $this->bagIcon() . '</a>';
        echo '</div></div></article>';
    }

    /** @param \WP_Term[] $categories */
    private function renderEditorial(array $categories): void
    {
        if ($categories === []) {
            return;
        }

        $primary = $categories[0];
        $secondary = $categories[1] ?? $categories[0];
        $settings = $this->renderingHomepage ? $this->homepageSettings->section('editorial') : [];
        $primary_image_id = (int) ($settings['primary_image_id'] ?? 0);
        $secondary_image_id = (int) ($settings['secondary_image_id'] ?? 0);

        $intro = $this->sectionIntro('editorial', [
            'eyebrow' => 'Тематичні добірки',
            'heading' => 'Догляд як час для себе',
            'description' => '',
        ]);

        echo '<section class="md-section md-editorial md-reveal" aria-labelledby="md-editorial-title"><div class="md-shell">';
        echo $this->sectionHeading($intro, 'md-editorial-title', wc_get_page_permalink('shop'));
        echo '<div class="md-editorial__grid">';
        echo '<a class="md-story md-story--large" href="' . esc_url($this->catalog->categoryUrl($primary)) . '">';
        echo '<img src="' . esc_url($this->catalog->categoryImage($primary, 'large', $primary_image_id)) . '" alt="" loading="lazy">';
        echo '<span class="md-story__scrim"></span><span class="md-story__copy"><small>Ритуал дня</small><strong>Турбота про шкіру — це час для себе.</strong><span>Відкрити добірку ' . $this->arrowIcon() . '</span></span>';
        echo '</a>';
        echo '<div class="md-story-stack">';
        echo '<article class="md-manifesto"><span>maruderm notes</span><h2>Менше кроків.<br>Більше сенсу.</h2><p>Будуй рутину навколо потреб шкіри, а не трендів.</p></article>';
        echo '<a class="md-story md-story--small" href="' . esc_url($this->catalog->categoryUrl($secondary)) . '">';
        echo '<img src="' . esc_url($this->catalog->categoryImage($secondary, 'large', $secondary_image_id)) . '" alt="" loading="lazy"><span class="md-story__scrim"></span><span class="md-story__copy"><small>Колір і догляд</small><strong>' . esc_html($secondary->name) . '</strong><span>Дивитися ' . $this->arrowIcon() . '</span></span>';
        echo '</a></div></div></div></section>';
    }

    private function renderRoutine(): void
    {
        $intro = $this->sectionIntro('routine', [
            'eyebrow' => 'Три прості кроки',
            'heading' => 'Рутина без перевантаження',
            'description' => 'Послідовність, яку легко підтримувати щодня.',
        ]);
        echo '<section class="md-section md-routine md-reveal" aria-labelledby="md-routine-title"><div class="md-shell">';
        echo '<div class="md-routine__intro"><span class="md-kicker">' . esc_html($intro['eyebrow']) . '</span><h2 id="md-routine-title">' . wp_kses($intro['heading'], ['em' => [], 'br' => []]) . '</h2><p>' . esc_html($intro['description']) . '</p></div>';
        echo '<div class="md-routine__steps">';

        foreach ($this->content->routines() as $routine) {
            echo '<article class="md-routine-card md-routine-card--' . esc_attr($routine['tone']) . '">';
            echo '<span class="md-routine-card__number">' . esc_html($routine['step']) . '</span><div><h3>' . esc_html($routine['title']) . '</h3><p>' . esc_html($routine['text']) . '</p></div>';
            echo '</article>';
        }

        echo '</div></div></section>';
    }

    private function renderBenefits(): void
    {
        echo '<section class="md-section md-benefits md-reveal" aria-label="Переваги магазину"><div class="md-shell md-benefits__grid">';

        foreach ($this->content->benefits() as $benefit) {
            echo '<article class="md-benefit"><span class="md-benefit__icon">' . $this->benefitIcon($benefit['icon']) . '</span><div><h3>' . esc_html($benefit['title']) . '</h3><p>' . esc_html($benefit['text']) . '</p></div></article>';
        }

        echo '</div></section>';
    }

    private function renderClosingCallout(): void
    {
        $intro = $this->sectionIntro('closing', [
            'eyebrow' => 'Твій догляд, твої правила',
            'heading' => 'Знайди формули, які хочеться використовувати щодня.',
            'description' => '',
        ]);
        echo '<section class="md-section md-closing md-reveal"><div class="md-shell"><div class="md-closing__card">';
        echo '<span class="md-closing__shape md-closing__shape--one"></span><span class="md-closing__shape md-closing__shape--two"></span>';
        echo '<div><span class="md-kicker">' . esc_html($intro['eyebrow']) . '</span><h2>' . wp_kses($intro['heading'], ['em' => [], 'br' => []]) . '</h2><p class="md-closing__description">' . esc_html($intro['description']) . '</p></div>';
        echo '<a class="md-button md-button--light" href="' . esc_url(wc_get_page_permalink('shop')) . '">Перейти до каталогу' . $this->arrowIcon() . '</a>';
        echo '</div></div></section>';
    }

    /** @param array{eyebrow: string, heading: string, description: string} $intro */
    private function sectionHeading(array $intro, string $id, string $url): string
    {
        $description = $intro['description'] !== ''
            ? '<p class="md-section-heading__description">' . esc_html($intro['description']) . '</p>'
            : '';

        return '<header class="md-section-heading"><div><span class="md-kicker">' . esc_html($intro['eyebrow']) . '</span><h2 id="' . esc_attr($id) . '">' . wp_kses($intro['heading'], ['em' => [], 'br' => []]) . '</h2>' . $description . '</div><a class="md-text-link" href="' . esc_url($url) . '">Переглянути все' . $this->arrowIcon() . '</a></header>';
    }

    /**
     * @param array{eyebrow: string, heading: string, description: string} $fallback
     * @return array{eyebrow: string, heading: string, description: string}
     */
    private function sectionIntro(string $key, array $fallback): array
    {
        if (!$this->renderingHomepage || $key === '') {
            return $fallback;
        }

        return array_merge($fallback, $this->homepageSettings->section($key));
    }

    private function arrowIcon(): string
    {
        return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>';
    }

    private function bagIcon(): string
    {
        return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 8h14l-1 12H6L5 8Z"/><path d="M9 9V6a3 3 0 0 1 6 0v3"/></svg>';
    }

    private function benefitIcon(string $icon): string
    {
        $icons = [
            'spark' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 2 1.5 6.5L20 10l-6.5 1.5L12 18l-1.5-6.5L4 10l6.5-1.5L12 2Z"/></svg>',
            'box' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m4 7 8-4 8 4-8 4-8-4Z"/><path d="m4 7 8 4 8-4v10l-8 4-8-4V7Z"/></svg>',
            'shield' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 5 6v5c0 4.6 2.9 8.2 7 10 4.1-1.8 7-5.4 7-10V6l-7-3Z"/><path d="m9 12 2 2 4-5"/></svg>',
            'heart' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 21l8.8-8.6a5.5 5.5 0 0 0 0-7.8Z"/></svg>',
        ];

        return $icons[$icon] ?? $icons['spark'];
    }
}
