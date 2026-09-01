<?php
/**
 * Assign Ukrainian to canonical catalog records before importing translations.
 *
 * Usage:
 *   wp eval-file scripts/prepare-multilingual-canonical-records.php
 *   MARUDERM_TRANSLATION_MODE=execute wp eval-file scripts/prepare-multilingual-canonical-records.php
 */

final class MarudermCanonicalLanguagePreparer
{
    private const TARGET_LANGUAGE = 'uk';

    public function run(string $root): array
    {
        if (! function_exists('pll_get_post_language') || ! function_exists('pll_set_post_language')) {
            return ['status' => 'failed', 'error' => 'Polylang is not active.'];
        }

        $mode = getenv('MARUDERM_TRANSLATION_MODE') === 'execute' ? 'execute' : 'dry-run';
        $records = $this->records($root);
        if (isset($records['error'])) {
            return ['status' => 'failed', 'mode' => $mode, 'error' => $records['error']];
        }

        $errors = [];
        $planned = [];

        foreach ($records['products'] as $sku) {
            $postId = (int) wc_get_product_id_by_sku($sku);
            $this->planPost($postId, sprintf('product SKU %s', $sku), $planned, $errors);
        }

        foreach ($records['categories'] as $termId) {
            $this->planTerm($termId, 'product_cat', $planned, $errors);
        }

        foreach ($records['attributes'] as $record) {
            $this->planTerm((int) $record['term_id'], (string) $record['taxonomy'], $planned, $errors);
        }

        if ($errors !== []) {
            return ['status' => 'failed', 'mode' => $mode, 'errors' => $errors];
        }

        $updated = 0;
        if ($mode === 'execute') {
            foreach ($planned as $record) {
                if ($record['current_language'] === self::TARGET_LANGUAGE) {
                    continue;
                }

                if ($record['type'] === 'post') {
                    pll_set_post_language($record['id'], self::TARGET_LANGUAGE);
                } else {
                    pll_set_term_language($record['id'], self::TARGET_LANGUAGE);
                }
                ++$updated;
            }
        }

        return [
            'status' => 'ok',
            'mode' => $mode,
            'summary' => [
                'records' => count($planned),
                'already_ukrainian' => count(array_filter(
                    $planned,
                    static fn (array $record): bool => $record['current_language'] === self::TARGET_LANGUAGE
                )),
                'assign_ukrainian' => count(array_filter(
                    $planned,
                    static fn (array $record): bool => $record['current_language'] === false
                )),
                'updated' => $updated,
            ],
        ];
    }

    private function records(string $root): array
    {
        $products = $this->decode($root . '/products/product-translations-ru-full.json');
        $categories = $this->decode($root . '/products/product-category-translations-ru.json');
        $attributes = $this->decode($root . '/products/product-attribute-translations-ru.json');

        if (! isset($products['products']) || ! is_array($categories) || ! is_array($attributes)) {
            return ['error' => 'Translation manifests are missing or malformed.'];
        }

        return [
            'products' => array_values(array_filter(array_map(
                static fn (array $record): string => (string) ($record['sku'] ?? ''),
                $products['products']
            ))),
            'categories' => array_map(
                static fn (array $record): int => (int) ($record['term_id'] ?? 0),
                $categories
            ),
            'attributes' => $attributes,
        ];
    }

    private function decode(string $path): array
    {
        if (! is_file($path)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($path), true);
        return is_array($decoded) ? $decoded : [];
    }

    private function planPost(int $postId, string $label, array &$planned, array &$errors): void
    {
        if ($postId <= 0 || get_post_type($postId) !== 'product') {
            $errors[] = sprintf('Could not resolve canonical %s.', $label);
            return;
        }

        $language = pll_get_post_language($postId, 'slug');
        if ($language !== false && $language !== self::TARGET_LANGUAGE) {
            $errors[] = sprintf('Canonical %s already uses language %s.', $label, $language);
            return;
        }

        $planned[] = ['type' => 'post', 'id' => $postId, 'current_language' => $language];
    }

    private function planTerm(int $termId, string $taxonomy, array &$planned, array &$errors): void
    {
        $term = get_term($termId, $taxonomy);
        if (! $term instanceof WP_Term) {
            $errors[] = sprintf('Could not resolve canonical %s term %d.', $taxonomy, $termId);
            return;
        }

        $language = pll_get_term_language($termId, 'slug');
        if ($language !== false && $language !== self::TARGET_LANGUAGE) {
            $errors[] = sprintf('Canonical %s term %d already uses language %s.', $taxonomy, $termId, $language);
            return;
        }

        $planned[] = ['type' => 'term', 'id' => $termId, 'current_language' => $language];
    }
}

$result = (new MarudermCanonicalLanguagePreparer())->run(ABSPATH);
echo wp_json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

if (($result['status'] ?? 'failed') !== 'ok') {
    exit(1);
}
