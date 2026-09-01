<?php

declare(strict_types=1);

namespace Maruderm\Homepage;

use Maruderm\LandingPage\LandingPageCatalog;

if (!defined('ABSPATH')) {
    exit();
}

/** Renders the canonical homepage carousel with live, category-matched WooCommerce products. */
final class HomepageHeroRenderer
{
    private const CAMPAIGNS = [
        [
            'theme' => 'skin',
            'image_position' => 'right',
            'category_slug' => 'zasoby-dlya-doglyadu-za-shkiroyu',
            'preferred_product_id' => 6062,
            'eyebrow' => 'Косметика нового покоління',
            'heading' => 'Догляд, що працює <em>у твоєму ритмі.</em>',
            'description' => 'Активні формули для щоденних ритуалів — зрозуміло, красиво й без зайвого.',
            'primary_label' => 'Знайти свій догляд',
            'secondary_label' => 'Дивитися новинки',
            'secondary_target' => 'new-products',
            'note_top' => 'skin first',
            'note_bottom' => 'made for real life',
            'product_label' => 'Новинка',
        ],
        [
            'theme' => 'hair',
            'image_position' => 'left',
            'category_slug' => 'zasoby-dlya-doglyadu-za-volossyam',
            'preferred_product_id' => 6034,
            'eyebrow' => 'Догляд за волоссям',
            'heading' => 'Сильне волосся <em>у твоєму ритмі.</em>',
            'description' => 'Очищення, відновлення, стайлінг і захист для щоденного догляду без перевантаження.',
            'primary_label' => 'Обрати догляд',
            'secondary_label' => 'Пройти діагностику',
            'secondary_target' => 'hair-analysis',
            'note_top' => 'hair care',
            'note_bottom' => 'daily protection',
            'product_label' => 'Догляд за волоссям',
        ],
        [
            'theme' => 'sun',
            'image_position' => 'right',
            'category_slug' => 'sonczezahysnyj-doglyad',
            'legacy_category_slug' => 'gunes-bakim-urunleri',
            'preferred_product_id' => 6027,
            'eyebrow' => 'Сонцезахисний догляд',
            'heading' => 'SPF щодня. <em>Легко і без компромісів.</em>',
            'description' => 'Захист і комфорт для сонячних днів у місті, подорожах і щоденній рутині.',
            'primary_label' => 'Обрати SPF',
            'secondary_label' => 'Дивитися всі',
            'secondary_target' => 'category',
            'note_top' => 'sun care',
            'note_bottom' => 'daily shield',
            'product_label' => 'Сонцезахисний догляд',
        ],
        [
            'theme' => 'makeup',
            'image_position' => 'left',
            'category_slug' => 'zasoby-dlya-doglyadu-za-tilom',
            'preferred_product_id' => 6032,
            'eyebrow' => 'Догляд за тілом',
            'heading' => 'Комфорт для шкіри <em>на кожен день.</em>',
            'description' => 'Очищення, зволоження й відновлення для м’якої та доглянутої шкіри тіла.',
            'primary_label' => 'Обрати догляд для тіла',
            'secondary_label' => 'Дивитися всі',
            'secondary_target' => 'category',
            'note_top' => 'body care',
            'note_bottom' => 'daily comfort',
            'product_label' => 'Догляд за тілом',
        ],
    ];

    private LandingPageCatalog $catalog;

    public function __construct(?LandingPageCatalog $catalog = null)
    {
        $this->catalog = $catalog ?? new LandingPageCatalog();
    }

    /** @param \WP_Term[] $categories @param array<string, mixed> $settings */
    public function render(array $categories, array $settings): void
    {
        $slides = $this->slides($settings);

        if ($slides === []) {
            return;
        }

        $slideCount = count($slides);
        $firstSlide = $slides[0];

        echo '<section class="home-hero" data-home-hero data-image-position="' . esc_attr($firstSlide['image_position']) . '" data-slide-theme="' . esc_attr($firstSlide['theme']) . '" aria-label="Головні пропозиції Maruderm" aria-roledescription="карусель">';
        echo '<div class="home-hero__slides" data-home-hero-slides aria-live="off">';

        foreach ($slides as $index => $slide) {
            $this->renderSlide($slide, $index, $slideCount, $categories);
        }

        echo '</div>';

        if ($slideCount > 1) {
            $this->renderControls($slideCount);
        }

        echo '<p class="sr-only" data-home-hero-status aria-live="polite">Слайд 1 з ' . esc_html((string) $slideCount) . '</p>';
        echo '</section>';
    }

    /** @param array<string, mixed> $settings @return array<int, array<string, mixed>> */
    private function slides(array $settings): array
    {
        $slides = [];
        $usedProductIds = [];

        foreach (self::CAMPAIGNS as $index => $campaign) {
            if ($index === 0) {
                $campaign = $this->firstCampaignSettings($campaign, $settings);
            }

            $category = $this->resolveCategory($campaign, $index === 0 ? $settings : []);

            if (!$category instanceof \WP_Term) {
                continue;
            }

            $product = $this->catalog->heroProduct(
                (int) $campaign['preferred_product_id'],
                $category,
                $usedProductIds
            );

            if (!$product instanceof \WC_Product) {
                continue;
            }

            $usedProductIds[] = $product->get_id();
            $campaign['category'] = $category;
            $campaign['product'] = $product;
            $slides[] = $campaign;
        }

        return $slides;
    }

    /** @param array<string, mixed> $campaign @param array<string, mixed> $settings @return array<string, mixed> */
    private function firstCampaignSettings(array $campaign, array $settings): array
    {
        foreach (['eyebrow', 'heading', 'description'] as $field) {
            $value = trim((string) ($settings[$field] ?? ''));

            if ($value !== '') {
                $campaign[$field] = $value;
            }
        }

        $selectedProductId = (int) ($settings['product_id'] ?? 0);

        if ($selectedProductId > 0) {
            $campaign['preferred_product_id'] = $selectedProductId;
        }

        return $campaign;
    }

    /** @param array<string, mixed> $campaign @param array<string, mixed> $settings */
    private function resolveCategory(array $campaign, array $settings): ?\WP_Term
    {
        $selectedCategoryId = (int) ($settings['primary_category_id'] ?? 0);

        if ($selectedCategoryId > 0) {
            $selectedCategory = get_term($selectedCategoryId, 'product_cat');

            if ($selectedCategory instanceof \WP_Term) {
                return $selectedCategory;
            }
        }

        $selectedProductId = (int) ($settings['product_id'] ?? 0);

        if ($selectedProductId > 0) {
            $productCategory = $this->catalog->topLevelCategoryForProduct($selectedProductId);

            if ($productCategory instanceof \WP_Term) {
                return $productCategory;
            }
        }

        $category = $this->catalog->categoryBySlug((string) $campaign['category_slug']);

        if ($category instanceof \WP_Term) {
            return $category;
        }

        $legacyCategorySlug = (string) ($campaign['legacy_category_slug'] ?? '');

        return $legacyCategorySlug !== '' ? $this->catalog->categoryBySlug($legacyCategorySlug) : null;
    }

    /** @param array<string, mixed> $slide @param \WP_Term[] $categories */
    private function renderSlide(array $slide, int $index, int $slideCount, array $categories): void
    {
        /** @var \WC_Product $product */
        $product = $slide['product'];
        /** @var \WP_Term $category */
        $category = $slide['category'];
        $active = $index === 0;
        $primaryUrl = $this->catalog->categoryUrl($category);
        $secondaryUrl = $this->secondaryUrl((string) $slide['secondary_target'], $primaryUrl);
        $imageAttributes = [
            'alt' => $product->get_name(),
            'loading' => $active ? 'eager' : 'lazy',
        ];

        if ($active) {
            $imageAttributes['fetchpriority'] = 'high';
        }

        echo '<article class="home-hero__slide home-hero__slide--' . esc_attr($slide['theme']) . ($active ? ' is-active' : '') . '" data-home-hero-slide data-image-position="' . esc_attr($slide['image_position']) . '" data-slide-theme="' . esc_attr($slide['theme']) . '" role="group" aria-roledescription="слайд" aria-label="' . esc_attr((string) ($index + 1) . ' з ' . (string) $slideCount) . '" aria-hidden="' . ($active ? 'false' : 'true') . '"' . ($active ? '' : ' inert') . '>';
        echo '<div class="shell home-hero__grid"><div class="home-hero__copy">';
        echo '<span class="kicker">' . esc_html((string) $slide['eyebrow']) . '</span>';
        echo '<h1>' . wp_kses((string) $slide['heading'], ['em' => [], 'br' => []]) . '</h1>';
        echo '<p>' . esc_html((string) $slide['description']) . '</p>';
        echo '<div class="home-hero__actions"><a class="button' . ($index === 0 ? ' button--dark' : '') . '" href="' . esc_url($primaryUrl) . '">';
        echo esc_html((string) $slide['primary_label']) . $this->arrowIcon() . '</a>';
        echo '<a class="text-link" href="' . esc_url($secondaryUrl) . '">' . esc_html((string) $slide['secondary_label']) . '</a></div>';

        if ($index === 0) {
            $publishedProducts = (int) (wp_count_posts('product')->publish ?? 0);
            echo '<div class="home-hero__proof"><span><strong>' . esc_html((string) $publishedProducts) . '+</strong> засобів</span>';
            echo '<span><strong>' . esc_html((string) count($categories)) . '</strong> категорій догляду</span></div>';
        }

        echo '</div><div class="home-hero__visual" data-product-id="' . esc_attr((string) $product->get_id()) . '">';
        echo '<span class="home-hero__orbit home-hero__orbit--one"></span><span class="home-hero__orbit home-hero__orbit--two"></span>';
        echo wp_kses_post($product->get_image('woocommerce_single', $imageAttributes));
        echo '<span class="home-hero__note home-hero__note--top">' . esc_html((string) $slide['note_top']) . '</span>';
        echo '<span class="home-hero__note home-hero__note--bottom">' . esc_html((string) $slide['note_bottom']) . '</span>';
        echo '<div class="home-hero__product-label"><small>' . esc_html((string) $slide['product_label']) . '</small><strong>' . esc_html($product->get_name()) . '</strong></div>';
        echo '</div></div></article>';
    }

    private function renderControls(int $slideCount): void
    {
        echo '<div class="shell home-hero__controls"><div class="home-hero__navigation" aria-label="Керування слайдером">';
        echo '<button class="home-hero__arrow home-hero__arrow--previous" type="button" data-home-hero-previous aria-label="Попередній слайд">' . $this->arrowIcon() . '</button>';
        echo '<div class="home-hero__pagination" aria-label="Вибрати слайд">';

        for ($index = 0; $index < $slideCount; $index++) {
            $active = $index === 0;
            echo '<button' . ($active ? ' class="is-active"' : '') . ' type="button" data-home-hero-dot="' . esc_attr((string) $index) . '" aria-label="Показати слайд ' . esc_attr((string) ($index + 1)) . '" aria-current="' . ($active ? 'true' : 'false') . '"></button>';
        }

        echo '</div>';
        echo '<button class="home-hero__arrow home-hero__arrow--next" type="button" data-home-hero-next aria-label="Наступний слайд">' . $this->arrowIcon() . '</button>';
        echo '<button class="home-hero__arrow home-hero__autoplay" type="button" data-home-hero-autoplay aria-label="Призупинити автоматичну зміну слайдів" aria-pressed="false"><span aria-hidden="true"></span></button>';
        echo '</div></div>';
    }

    private function secondaryUrl(string $target, string $categoryUrl): string
    {
        if ($target === 'new-products') {
            return '#new-products';
        }

        if ($target === 'hair-analysis') {
            return home_url('/hair-analysis/');
        }

        return $categoryUrl;
    }

    private function arrowIcon(): string
    {
        return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"></path></svg>';
    }
}
