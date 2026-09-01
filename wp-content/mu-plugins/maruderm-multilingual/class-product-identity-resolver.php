<?php

namespace Maruderm\Multilingual;

use WC_Product;
use WP_Post;

final class ProductIdentityResolver
{
    private const DEFAULT_LANGUAGE = 'uk';
    private const SUPPORTED_LANGUAGES = ['uk', 'ru'];

    /**
     * @return array{
     *   canonicalDatabaseId: int,
     *   presentationDatabaseId: int,
     *   requestedLanguage: string,
     *   resolvedLanguage: string,
     *   fallbackUsed: bool,
     *   canonicalSlug: string,
     *   localizedSlug: string
     * }|null
     */
    public function resolveBySlug(string $slug, string $requestedLanguage): ?array
    {
        $post = get_page_by_path(sanitize_title($slug), OBJECT, 'product');

        if (! $post instanceof WP_Post || $post->post_status !== 'publish') {
            return null;
        }

        return $this->resolveByProductId($post->ID, $requestedLanguage);
    }

    /**
     * @return array{
     *   canonicalDatabaseId: int,
     *   presentationDatabaseId: int,
     *   requestedLanguage: string,
     *   resolvedLanguage: string,
     *   fallbackUsed: bool,
     *   canonicalSlug: string,
     *   localizedSlug: string
     * }|null
     */
    public function resolveByProductId(int $productId, string $requestedLanguage): ?array
    {
        $language = $this->normalizeLanguage($requestedLanguage);
        $canonicalId = $this->canonicalId($productId);

        $canonicalProduct = wc_get_product($canonicalId);

        if (! $canonicalProduct instanceof WC_Product) {
            return null;
        }

        $presentationId = $this->translationId($canonicalId, $language);
        $presentationPost = get_post($presentationId);

        if (! $presentationPost instanceof WP_Post || $presentationPost->post_status !== 'publish') {
            $presentationId = $canonicalId;
            $presentationPost = get_post($canonicalId);
        }

        if (! $presentationPost instanceof WP_Post) {
            return null;
        }

        $resolvedLanguage = $this->postLanguage($presentationId);

        return [
            'canonicalDatabaseId' => $canonicalId,
            'presentationDatabaseId' => $presentationId,
            'requestedLanguage' => $language,
            'resolvedLanguage' => $resolvedLanguage,
            'fallbackUsed' => $resolvedLanguage !== $language,
            'canonicalSlug' => $canonicalProduct->get_slug(),
            'localizedSlug' => $presentationPost->post_name,
        ];
    }

    public function presentationPost(int $canonicalId, string $requestedLanguage): ?WP_Post
    {
        $presentationId = $this->translationId($canonicalId, $requestedLanguage);
        $presentationPost = get_post($presentationId);

        if ($presentationPost instanceof WP_Post && $presentationPost->post_status === 'publish') {
            return $presentationPost;
        }

        $canonicalPost = get_post($canonicalId);

        return $canonicalPost instanceof WP_Post ? $canonicalPost : null;
    }

    public function canonicalId(int $productId): int
    {
        if (! function_exists('pll_get_post_translations')) {
            return $productId;
        }

        $translations = pll_get_post_translations($productId);

        return isset($translations[self::DEFAULT_LANGUAGE])
            ? (int) $translations[self::DEFAULT_LANGUAGE]
            : $productId;
    }

    public function translationId(int $canonicalId, string $requestedLanguage): int
    {
        $language = $this->normalizeLanguage($requestedLanguage);

        if (! function_exists('pll_get_post')) {
            return $canonicalId;
        }

        $translationId = (int) pll_get_post($canonicalId, $language);

        return $translationId > 0 ? $translationId : $canonicalId;
    }

    public function normalizeLanguage(string $language): string
    {
        $normalized = strtolower((string) strtok(str_replace('_', '-', $language), '-'));

        return in_array($normalized, self::SUPPORTED_LANGUAGES, true)
            ? $normalized
            : self::DEFAULT_LANGUAGE;
    }

    private function postLanguage(int $postId): string
    {
        if (! function_exists('pll_get_post_language')) {
            return self::DEFAULT_LANGUAGE;
        }

        $language = pll_get_post_language($postId, 'slug');

        return is_string($language) && $language !== ''
            ? $language
            : self::DEFAULT_LANGUAGE;
    }
}
