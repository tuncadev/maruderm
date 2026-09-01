<?php
/**
 * Imports Russian product presentation records without duplicating commerce identity.
 *
 * Run with WP-CLI:
 *   wp eval-file scripts/import-russian-product-translations.php
 *   MARUDERM_TRANSLATION_MODE=execute wp eval-file scripts/import-russian-product-translations.php
 *   MARUDERM_TRANSLATION_MODE=rollback wp eval-file scripts/import-russian-product-translations.php
 *
 * @package Maruderm
 */

if (! defined('ABSPATH')) {
    exit;
}

final class RussianProductTranslationImporter
{
    private const CANONICAL_META_KEY = '_maruderm_canonical_product_id';
    private const PRESENTATION_META_KEY = '_maruderm_translation_presentation';
    private const SOURCE_META_KEY = '_maruderm_translation_source';
    // Stable cohort marker retained across merged/generated source files so
    // rollback can remove every presentation record created by this importer.
    private const SOURCE_NAME = 'prom-product-translations-ru.json';
    private const SUPPORTED_MODES = ['dry-run', 'execute', 'rollback'];

    public function __construct(
        private readonly string $sourcePath,
        private readonly string $mode
    ) {
    }

    /** @return array<string, mixed> */
    public function run(): array
    {
        if (! in_array($this->mode, self::SUPPORTED_MODES, true)) {
            return $this->failure('Unsupported mode. Use dry-run, execute, or rollback.');
        }

        if (! function_exists('pll_set_post_language') || ! function_exists('pll_save_post_translations')) {
            return $this->failure('Polylang is not active.');
        }

        if ($this->mode === 'rollback') {
            return $this->rollback();
        }

        $source = $this->readSource();

        if (isset($source['error'])) {
            return $source;
        }

        $preflight = $this->preflight($source['products']);

        if ($preflight['errors'] !== []) {
            return [
                'status' => 'failed',
                'mode' => $this->mode,
                'source' => $this->sourcePath,
                'sourceCount' => count($source['products']),
                'errors' => $preflight['errors'],
                'summary' => $preflight['summary'],
            ];
        }

        if ($this->mode === 'dry-run') {
            return [
                'status' => 'ok',
                'mode' => 'dry-run',
                'source' => $this->sourcePath,
                'sourceCount' => count($source['products']),
                'summary' => $preflight['summary'],
                'planned' => $preflight['planned'],
                'errors' => [],
            ];
        }

        return $this->execute($preflight['planned']);
    }

    /** @return array{products?: array<int, array<string, string>>, error?: string, status: string} */
    private function readSource(): array
    {
        if (! is_readable($this->sourcePath)) {
            return $this->failure('Translation source is not readable.');
        }

        $decoded = json_decode((string) file_get_contents($this->sourcePath), true);
        $products = is_array($decoded) ? ($decoded['products'] ?? null) : null;

        if (! is_array($products)) {
            return $this->failure('Translation source does not contain a products array.');
        }

        return ['status' => 'ok', 'products' => $products];
    }

    /**
     * @param array<int, array<string, string>> $records
     * @return array{
     *   errors: string[],
     *   summary: array<string, int>,
     *   planned: array<int, array<string, mixed>>
     * }
     */
    private function preflight(array $records): array
    {
        $errors = [];
        $planned = [];
        $seenSkus = [];
        $summary = ['create' => 0, 'update' => 0, 'unchanged' => 0];

        foreach ($records as $index => $record) {
            $sku = trim((string) ($record['sku'] ?? ''));
            $title = trim((string) ($record['title_ru'] ?? ''));
            $description = trim((string) ($record['description_ru'] ?? ''));

            if ($sku === '' || $title === '' || $description === '') {
                $errors[] = sprintf('Record %d is missing SKU, title, or description.', $index);
                continue;
            }

            if (isset($seenSkus[$sku])) {
                $errors[] = sprintf('Duplicate source SKU: %s.', $sku);
                continue;
            }

            $seenSkus[$sku] = true;
            $canonicalId = (int) wc_get_product_id_by_sku($sku);
            $canonicalProduct = $canonicalId > 0 ? wc_get_product($canonicalId) : null;

            if (! $canonicalProduct instanceof WC_Product || $canonicalProduct->get_status() !== 'publish') {
                $errors[] = sprintf('SKU %s does not map to one published WooCommerce product.', $sku);
                continue;
            }

            if (pll_get_post_language($canonicalId, 'slug') !== 'uk') {
                $errors[] = sprintf('Canonical product %d for SKU %s is not Ukrainian.', $canonicalId, $sku);
                continue;
            }

            $translationId = (int) pll_get_post($canonicalId, 'ru');
            $action = 'create';

            if ($translationId > 0) {
                if (get_post_meta($translationId, self::PRESENTATION_META_KEY, true) !== '1') {
                    $errors[] = sprintf(
                        'SKU %s already has unmanaged Russian product %d.',
                        $sku,
                        $translationId
                    );
                    continue;
                }

                $translation = get_post($translationId);
                $sanitizedDescription = wp_kses_post($description);
                $expectedSlug = $this->productSlug($title, $translationId);
                $action = $translation instanceof WP_Post
                    && $translation->post_title === $title
                    && trim($translation->post_content) === trim($sanitizedDescription)
                    && $translation->post_name === $expectedSlug
                    ? 'unchanged'
                    : 'update';
            }

            $summary[$action]++;
            $planned[] = [
                'action' => $action,
                'sku' => $sku,
                'canonicalId' => $canonicalId,
                'translationId' => $translationId,
                'title' => $title,
                'description' => $description,
            ];
        }

        return ['errors' => $errors, 'summary' => $summary, 'planned' => $planned];
    }

    /**
     * @param array<int, array<string, mixed>> $planned
     * @return array<string, mixed>
     */
    private function execute(array $planned): array
    {
        $result = ['created' => [], 'updated' => [], 'unchanged' => [], 'errors' => []];

        foreach ($planned as $item) {
            if ($item['action'] === 'unchanged') {
                $result['unchanged'][] = $item['translationId'];
                continue;
            }

            $createdNow = $item['action'] === 'create';
            $postData = [
                'ID' => $createdNow ? 0 : (int) $item['translationId'],
                'post_type' => 'product',
                'post_status' => 'publish',
                'post_title' => wp_strip_all_tags((string) $item['title']),
                'post_name' => $this->productSlug(
                    (string) $item['title'],
                    $createdNow ? 0 : (int) $item['translationId']
                ),
                'post_content' => wp_kses_post((string) $item['description']),
                'post_excerpt' => '',
                'post_parent' => 0,
            ];
            $translationId = wp_insert_post($postData, true);

            if (is_wp_error($translationId)) {
                $result['errors'][] = sprintf(
                    'SKU %s failed: %s',
                    $item['sku'],
                    $translationId->get_error_message()
                );
                continue;
            }

            $translationId = (int) $translationId;

            try {
                $this->configurePresentationProduct(
                    $translationId,
                    (int) $item['canonicalId']
                );
                $result[$createdNow ? 'created' : 'updated'][] = $translationId;
            } catch (Throwable $exception) {
                if ($createdNow) {
                    wp_delete_post($translationId, true);
                }
                $result['errors'][] = sprintf('SKU %s failed: %s', $item['sku'], $exception->getMessage());
            }
        }

        return [
            'status' => $result['errors'] === [] ? 'ok' : 'partial',
            'mode' => 'execute',
            'source' => $this->sourcePath,
            'summary' => [
                'created' => count($result['created']),
                'updated' => count($result['updated']),
                'unchanged' => count($result['unchanged']),
                'errors' => count($result['errors']),
            ],
            'translationIds' => $result,
        ];
    }

    private function configurePresentationProduct(int $translationId, int $canonicalId): void
    {
        update_post_meta($translationId, self::CANONICAL_META_KEY, $canonicalId);
        update_post_meta($translationId, self::PRESENTATION_META_KEY, '1');
        update_post_meta($translationId, self::SOURCE_META_KEY, self::SOURCE_NAME);

        foreach (['_thumbnail_id', '_product_image_gallery'] as $metaKey) {
            $value = get_post_meta($canonicalId, $metaKey, true);

            if ($value !== '') {
                update_post_meta($translationId, $metaKey, $value);
            }
        }

        foreach (['_sku', '_price', '_regular_price', '_sale_price', '_stock', '_stock_status'] as $metaKey) {
            delete_post_meta($translationId, $metaKey);
        }

        wp_set_object_terms($translationId, 'simple', 'product_type', false);
        wp_set_object_terms(
            $translationId,
            ['exclude-from-catalog', 'exclude-from-search'],
            'product_visibility',
            false
        );
        pll_set_post_language($translationId, 'ru');
        pll_save_post_translations(['uk' => $canonicalId, 'ru' => $translationId]);
        clean_post_cache($translationId);
        clean_post_cache($canonicalId);
    }

    private function productSlug(string $title, int $translationId): string
    {
        $baseSlug = (new \Maruderm\Multilingual\RussianSlugger())->slug($title);

        return wp_unique_post_slug($baseSlug, $translationId, 'publish', 'product', 0);
    }

    /** @return array<string, mixed> */
    private function rollback(): array
    {
        $ids = get_posts([
            'post_type' => 'product',
            'post_status' => 'any',
            'numberposts' => -1,
            'fields' => 'ids',
            'suppress_filters' => true,
            'meta_query' => [
                [
                    'key' => self::PRESENTATION_META_KEY,
                    'value' => '1',
                ],
                [
                    'key' => self::SOURCE_META_KEY,
                    'value' => self::SOURCE_NAME,
                ],
            ],
        ]);
        $deleted = [];
        $errors = [];

        foreach ($ids as $id) {
            $deletedPost = wp_delete_post((int) $id, true);

            if ($deletedPost instanceof WP_Post) {
                $deleted[] = (int) $id;
            } else {
                $errors[] = sprintf('Could not delete translation %d.', $id);
            }
        }

        return [
            'status' => $errors === [] ? 'ok' : 'partial',
            'mode' => 'rollback',
            'summary' => ['deleted' => count($deleted), 'errors' => count($errors)],
            'deletedIds' => $deleted,
            'errors' => $errors,
        ];
    }

    /** @return array{status: string, error: string} */
    private function failure(string $message): array
    {
        return ['status' => 'failed', 'error' => $message];
    }
}

$sourcePath = getenv('MARUDERM_TRANSLATION_SOURCE');
$sourcePath = is_string($sourcePath) && $sourcePath !== ''
    ? $sourcePath
    : ABSPATH . 'products/product-translations-ru-full.json';
$mode = getenv('MARUDERM_TRANSLATION_MODE');
$mode = is_string($mode) && $mode !== '' ? $mode : 'dry-run';
$report = (new RussianProductTranslationImporter($sourcePath, $mode))->run();

echo wp_json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;

if (($report['status'] ?? 'failed') !== 'ok') {
    exit(1);
}
