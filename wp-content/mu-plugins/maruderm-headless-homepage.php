<?php
/**
 * Exposes the maruderm.dev homepage (hero, categories, new products, editorial,
 * routine, closing CTA) through WPGraphQL for the headless Next.js frontend.
 *
 * Reuses the existing theme classes (Maruderm\Homepage\HomepageHeroRenderer,
 * Maruderm\LandingPage\LandingPageCatalog, Maruderm\Settings\HomepageSettings,
 * Maruderm\LandingPage\LandingPageContent) so the GraphQL response always
 * matches what the live PHP-rendered homepage shows.
 *
 * @package Maruderm
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('graphql_register_types', 'maruderm_register_homepage_graphql');

function maruderm_register_homepage_graphql(): void
{
    register_graphql_object_type('MarudermSectionCopy', [
        'description' => 'Editable eyebrow/heading/description copy for a homepage section.',
        'fields' => [
            'eyebrow' => ['type' => 'String'],
            'heading' => ['type' => 'String'],
            'description' => ['type' => 'String'],
        ],
    ]);

    register_graphql_object_type('MarudermHeroSlide', [
        'description' => 'One slide of the homepage hero carousel.',
        'fields' => [
            'theme' => ['type' => 'String'],
            'imagePosition' => ['type' => 'String'],
            'isActive' => ['type' => 'Boolean'],
            'eyebrow' => ['type' => 'String'],
            'heading' => ['type' => 'String'],
            'description' => ['type' => 'String'],
            'primaryLabel' => ['type' => 'String'],
            'primaryUrl' => ['type' => 'String'],
            'secondaryLabel' => ['type' => 'String'],
            'secondaryUrl' => ['type' => 'String'],
            'noteTop' => ['type' => 'String'],
            'noteBottom' => ['type' => 'String'],
            'productLabel' => ['type' => 'String'],
            'productName' => ['type' => 'String'],
            'productImageUrl' => ['type' => 'String'],
        ],
    ]);

    register_graphql_object_type('MarudermCategoryCard', [
        'description' => 'A product category card (homepage category grid / editorial spotlight).',
        'fields' => [
            'name' => ['type' => 'String'],
            'slug' => ['type' => 'String'],
            'url' => ['type' => 'String'],
            'imageUrl' => ['type' => 'String'],
            'inStockCount' => ['type' => 'Int'],
        ],
    ]);

    register_graphql_object_type('MarudermProductCard', [
        'description' => 'A simplified WooCommerce product card for homepage grids.',
        'fields' => [
            'databaseId' => ['type' => 'Int'],
            'name' => ['type' => 'String'],
            'slug' => ['type' => 'String'],
            'url' => ['type' => 'String'],
            'imageUrl' => ['type' => 'String'],
            'imageAlt' => ['type' => 'String'],
            'priceHtml' => ['type' => 'String'],
            'categoryLabel' => ['type' => 'String'],
            'inStock' => ['type' => 'Boolean'],
        ],
    ]);

    register_graphql_object_type('MarudermRoutineStep', [
        'description' => 'A static routine step card.',
        'fields' => [
            'tone' => ['type' => 'String'],
            'step' => ['type' => 'String'],
            'title' => ['type' => 'String'],
            'text' => ['type' => 'String'],
        ],
    ]);

    register_graphql_object_type('MarudermHomepage', [
        'description' => 'All data needed to render the maruderm.dev homepage headlessly.',
        'fields' => [
            'hero' => ['type' => ['list_of' => 'MarudermHeroSlide']],
            'heroProductsCount' => ['type' => 'Int'],
            'heroCategoriesCount' => ['type' => 'Int'],
            'categoriesSection' => ['type' => 'MarudermSectionCopy'],
            'categories' => ['type' => ['list_of' => 'MarudermCategoryCard']],
            'newProductsSection' => ['type' => 'MarudermSectionCopy'],
            'newProducts' => ['type' => ['list_of' => 'MarudermProductCard']],
            'editorialSection' => ['type' => 'MarudermSectionCopy'],
            'editorial' => ['type' => ['list_of' => 'MarudermCategoryCard']],
            'routineSection' => ['type' => 'MarudermSectionCopy'],
            'routineSteps' => ['type' => ['list_of' => 'MarudermRoutineStep']],
            'closingSection' => ['type' => 'MarudermSectionCopy'],
        ],
    ]);

    register_graphql_field('RootQuery', 'marudermHomepage', [
        'type' => 'MarudermHomepage',
        'description' => 'Resolved homepage content for the headless frontend.',
        'resolve' => static function () {
            return maruderm_resolve_homepage_graphql();
        },
    ]);
}

function maruderm_resolve_homepage_graphql(): array
{
    $settings = (new \Maruderm\Settings\HomepageSettings())->all();
    $catalog = new \Maruderm\LandingPage\LandingPageCatalog();
    $content = new \Maruderm\LandingPage\LandingPageContent();

    $categoryIds = is_array($settings['categories']['category_ids'] ?? null)
        ? $settings['categories']['category_ids']
        : [];
    $productCategoryIds = is_array($settings['new_products']['category_ids'] ?? null)
        ? $settings['new_products']['category_ids']
        : [];

    $categories = $catalog->categories(5, $categoryIds);
    $products = $catalog->products(
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
        : $catalog->categories(2, $editorialIds);
    $editorialImages = [
        (int) ($settings['editorial']['primary_category_id'] ?? 0) => (int) ($settings['editorial']['primary_image_id'] ?? 0),
        (int) ($settings['editorial']['secondary_category_id'] ?? 0) => (int) ($settings['editorial']['secondary_image_id'] ?? 0),
    ];

    return [
        'hero' => maruderm_resolve_hero_slides($catalog, $settings['hero'] ?? []),
        'heroProductsCount' => (int) (wp_count_posts('product')->publish ?? 0),
        'heroCategoriesCount' => count($categories),
        'categoriesSection' => maruderm_section_copy($settings['categories'] ?? []),
        'categories' => maruderm_map_categories($catalog, $categories, $settings['categories']['category_images'] ?? []),
        'newProductsSection' => maruderm_section_copy($settings['new_products'] ?? []),
        'newProducts' => array_map('maruderm_map_product_card', $products),
        'editorialSection' => maruderm_section_copy($settings['editorial'] ?? []),
        'editorial' => maruderm_map_categories($catalog, $editorialCategories, $editorialImages),
        'routineSection' => maruderm_section_copy($settings['routine'] ?? []),
        'routineSteps' => $content->routines(),
        'closingSection' => maruderm_section_copy($settings['closing'] ?? []),
    ];
}

/** @param array<string, mixed> $section */
function maruderm_section_copy(array $section): array
{
    return [
        'eyebrow' => (string) ($section['eyebrow'] ?? ''),
        'heading' => (string) ($section['heading'] ?? ''),
        'description' => (string) ($section['description'] ?? ''),
    ];
}

/** @param \WP_Term[] $categories @param array<int, int> $imageOverrides */
function maruderm_map_categories(\Maruderm\LandingPage\LandingPageCatalog $catalog, array $categories, array $imageOverrides): array
{
    return array_map(static function (\WP_Term $category) use ($catalog, $imageOverrides): array {
        $overrideId = (int) ($imageOverrides[$category->term_id] ?? 0);

        return [
            'name' => $category->name,
            'slug' => $category->slug,
            'url' => $catalog->categoryUrl($category),
            'imageUrl' => $catalog->categoryImage($category, 'medium_large', $overrideId),
            'inStockCount' => $catalog->inStockProductCount($category),
        ];
    }, $categories);
}

function maruderm_map_product_card(\WC_Product $product): array
{
    $imageId = $product->get_image_id();
    $imageUrl = $imageId ? wp_get_attachment_image_url($imageId, 'woocommerce_thumbnail') : false;
    $terms = wp_get_post_terms($product->get_id(), 'product_cat');
    $categoryLabel = (!is_wp_error($terms) && isset($terms[0])) ? $terms[0]->name : 'Maruderm';

    return [
        'databaseId' => $product->get_id(),
        'name' => $product->get_name(),
        'slug' => $product->get_slug(),
        'url' => $product->get_permalink(),
        'imageUrl' => is_string($imageUrl) ? $imageUrl : wc_placeholder_img_src('woocommerce_thumbnail'),
        'imageAlt' => wp_strip_all_tags($product->get_name()),
        'priceHtml' => $product->get_price_html(),
        'categoryLabel' => $categoryLabel,
        'inStock' => $product->is_in_stock(),
    ];
}

/** @param array<string, mixed> $heroSettings @return array<int, array<string, mixed>> */
function maruderm_resolve_hero_slides(\Maruderm\LandingPage\LandingPageCatalog $catalog, array $heroSettings): array
{
    $renderer = new \Maruderm\Homepage\HomepageHeroRenderer($catalog);
    $reflection = new \ReflectionMethod($renderer, 'slides');
    $reflection->setAccessible(true);
    /** @var array<int, array<string, mixed>> $slides */
    $slides = $reflection->invoke($renderer, $heroSettings);

    return array_values(array_map(static function (array $slide, int $index) use ($catalog): array {
        /** @var \WC_Product $product */
        $product = $slide['product'];
        /** @var \WP_Term $category */
        $category = $slide['category'];
        $primaryUrl = $catalog->categoryUrl($category);
        $secondaryUrl = $primaryUrl;

        if (($slide['secondary_target'] ?? '') === 'new-products') {
            $secondaryUrl = '#new-products';
        } elseif (($slide['secondary_target'] ?? '') === 'hair-analysis') {
            $secondaryUrl = home_url('/hair-analysis/');
        }

        $imageId = $product->get_image_id();
        $imageUrl = $imageId ? wp_get_attachment_image_url($imageId, 'woocommerce_single') : false;

        return [
            'theme' => (string) $slide['theme'],
            'imagePosition' => (string) $slide['image_position'],
            'isActive' => $index === 0,
            'eyebrow' => (string) $slide['eyebrow'],
            'heading' => (string) $slide['heading'],
            'description' => (string) $slide['description'],
            'primaryLabel' => (string) $slide['primary_label'],
            'primaryUrl' => $primaryUrl,
            'secondaryLabel' => (string) $slide['secondary_label'],
            'secondaryUrl' => $secondaryUrl,
            'noteTop' => (string) $slide['note_top'],
            'noteBottom' => (string) $slide['note_bottom'],
            'productLabel' => (string) $slide['product_label'],
            'productName' => $product->get_name(),
            'productImageUrl' => is_string($imageUrl) ? $imageUrl : wc_placeholder_img_src('woocommerce_single'),
        ];
    }, $slides, array_keys($slides)));
}
