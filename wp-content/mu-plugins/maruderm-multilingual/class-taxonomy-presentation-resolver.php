<?php

namespace Maruderm\Multilingual;

use WC_Product;
use WP_Term;

final class TaxonomyPresentationResolver
{
    public function translateTerm(WP_Term $term, string $language): WP_Term
    {
        if ($language !== 'ru' || ! function_exists('pll_get_term')) {
            return $term;
        }

        $translationId = (int) pll_get_term($term->term_id, 'ru');
        $translation = $translationId > 0 ? get_term($translationId, $term->taxonomy) : null;

        return $translation instanceof WP_Term ? $translation : $term;
    }

    /** @return string[] */
    public function productTermSlugs(WC_Product $product, string $taxonomy, string $language): array
    {
        $terms = wp_get_object_terms($product->get_id(), $taxonomy);

        if (is_wp_error($terms)) {
            return [];
        }

        return array_values(array_map(
            fn (WP_Term $term): string => $this->translateTerm($term, $language)->slug,
            $terms
        ));
    }

    /**
     * @param array<int, array<string, mixed>> $options
     * @return array<int, array<string, mixed>>
     */
    public function localizeOptions(array $options, string $taxonomy, string $language): array
    {
        if ($language !== 'ru') {
            return $options;
        }

        return array_map(function (array $option) use ($taxonomy, $language): array {
            $option['canonicalValue'] = (string) ($option['value'] ?? '');
            $term = get_term_by('slug', (string) ($option['value'] ?? ''), $taxonomy);

            if (! $term instanceof WP_Term) {
                return $option;
            }

            $translation = $this->translateTerm($term, $language);
            $option['value'] = $translation->slug;
            $option['label'] = $translation->name;
            $option['description'] = $translation->description;

            if ($taxonomy === 'product_cat') {
                $option['url'] = home_url('/ru/catalog/' . $translation->slug . '/');
            }

            return $option;
        }, $options);
    }

    /**
     * @param array<int, array<string, mixed>> $categories
     * @return array<int, array<string, mixed>>
     */
    public function localizeNavigation(array $categories, string $language): array
    {
        if ($language !== 'ru') {
            return $categories;
        }

        return array_map(function (array $category) use ($language): array {
            $category['canonicalValue'] = (string) ($category['value'] ?? '');
            $term = get_term_by('slug', (string) ($category['value'] ?? ''), 'product_cat');

            if (! $term instanceof WP_Term) {
                return $category;
            }

            $translation = $this->translateTerm($term, $language);
            $category['value'] = $translation->slug;
            $category['label'] = $translation->name;
            $category['url'] = home_url('/ru/catalog/' . $translation->slug . '/');

            return $category;
        }, $categories);
    }
}
