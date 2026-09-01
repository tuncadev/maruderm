<?php
/**
 * Imports linked Russian product categories and assigns them to presentation products.
 *
 * Defaults to dry-run. Set MARUDERM_TRANSLATION_MODE=execute to mutate locally.
 *
 * @package Maruderm
 */

if (! defined('ABSPATH')) {
    exit;
}

final class RussianProductCategoryImporter
{
    private const MARKER_META_KEY = '_maruderm_translation_presentation';
    private const SOURCE_META_KEY = '_maruderm_translation_source';
    private const SOURCE_NAME = 'product-category-translations-ru.json';

    public function __construct(
        private readonly string $sourcePath,
        private readonly string $mode
    ) {
    }

    /** @return array<string, mixed> */
    public function run(): array
    {
        if (! in_array($this->mode, ['dry-run', 'execute'], true)) {
            return ['status' => 'failed', 'error' => 'Mode must be dry-run or execute.'];
        }

        if (! function_exists('pll_get_term') || ! function_exists('pll_save_term_translations')) {
            return ['status' => 'failed', 'error' => 'Polylang is not active.'];
        }

        $records = json_decode((string) file_get_contents($this->sourcePath), true);

        if (! is_array($records)) {
            return ['status' => 'failed', 'error' => 'Category source is invalid.'];
        }

        $preflight = $this->preflight($records);

        if ($preflight['errors'] !== [] || $this->mode === 'dry-run') {
            return [
                'status' => $preflight['errors'] === [] ? 'ok' : 'failed',
                'mode' => $this->mode,
                'sourceCount' => count($records),
                'summary' => $preflight['summary'],
                'errors' => $preflight['errors'],
            ];
        }

        return $this->execute($preflight['records']);
    }

    /**
     * @param array<int, array<string, mixed>> $records
     * @return array{records: array<int, array<string, mixed>>, errors: string[], summary: array<string, int>}
     */
    private function preflight(array $records): array
    {
        $errors = [];
        $summary = ['create' => 0, 'update' => 0, 'unchanged' => 0];
        $ids = [];

        foreach ($records as &$record) {
            $termId = absint($record['term_id'] ?? 0);
            $name = trim((string) ($record['name_ru'] ?? ''));
            $term = $termId > 0 ? get_term($termId, 'product_cat') : null;

            if (! $term instanceof WP_Term || $name === '') {
                $errors[] = sprintf('Invalid category source record for term %d.', $termId);
                continue;
            }
            if (isset($ids[$termId])) {
                $errors[] = sprintf('Duplicate canonical category ID %d.', $termId);
                continue;
            }
            if (pll_get_term_language($termId, 'slug') !== 'uk') {
                $errors[] = sprintf('Canonical category %d is not Ukrainian.', $termId);
                continue;
            }

            $ids[$termId] = true;
            $translationId = (int) pll_get_term($termId, 'ru');
            $action = 'create';

            if ($translationId > 0) {
                if (get_term_meta($translationId, self::MARKER_META_KEY, true) !== '1') {
                    $errors[] = sprintf('Category %d has unmanaged Russian term %d.', $termId, $translationId);
                    continue;
                }

                $translation = get_term($translationId, 'product_cat');
                $expectedSlug = $this->categorySlug($name, $translationId);
                $action = $translation instanceof WP_Term
                    && $translation->name === $name
                    && $translation->slug === $expectedSlug
                    ? 'unchanged'
                    : 'update';
            }

            $summary[$action]++;
            $record['translation_id'] = $translationId;
            $record['action'] = $action;
        }
        unset($record);

        foreach ($records as $record) {
            $parentId = absint($record['parent'] ?? 0);
            if ($parentId > 0 && ! isset($ids[$parentId])) {
                $errors[] = sprintf('Category %d references missing parent %d.', $record['term_id'], $parentId);
            }
        }

        return compact('records', 'errors', 'summary');
    }

    /**
     * @param array<int, array<string, mixed>> $records
     * @return array<string, mixed>
     */
    private function execute(array $records): array
    {
        $pending = array_column($records, null, 'term_id');
        $translationIds = [];
        $created = [];
        $updated = [];
        $unchanged = [];
        $errors = [];

        while ($pending !== []) {
            $progress = false;

            foreach ($pending as $termId => $record) {
                $parentId = absint($record['parent'] ?? 0);
                if ($parentId > 0 && ! isset($translationIds[$parentId])) {
                    continue;
                }

                $parentTranslationId = $parentId > 0 ? $translationIds[$parentId] : 0;
                $translationId = (int) ($record['translation_id'] ?? 0);

                if ($record['action'] === 'create') {
                    $inserted = wp_insert_term((string) $record['name_ru'], 'product_cat', [
                        'slug' => $this->categorySlug((string) $record['name_ru'], 0),
                        'parent' => $parentTranslationId,
                    ]);

                    if (is_wp_error($inserted)) {
                        $errors[] = sprintf('Category %d failed: %s', $termId, $inserted->get_error_message());
                        unset($pending[$termId]);
                        $progress = true;
                        continue;
                    }

                    $translationId = (int) $inserted['term_id'];
                    $created[] = $translationId;
                } elseif ($record['action'] === 'update') {
                    $updatedTerm = wp_update_term($translationId, 'product_cat', [
                        'name' => (string) $record['name_ru'],
                        'slug' => $this->categorySlug(
                            (string) $record['name_ru'],
                            $translationId
                        ),
                        'parent' => $parentTranslationId,
                    ]);

                    if (is_wp_error($updatedTerm)) {
                        $errors[] = sprintf('Category %d failed: %s', $termId, $updatedTerm->get_error_message());
                        unset($pending[$termId]);
                        $progress = true;
                        continue;
                    }
                    $updated[] = $translationId;
                } else {
                    $unchanged[] = $translationId;
                }

                update_term_meta($translationId, self::MARKER_META_KEY, '1');
                update_term_meta($translationId, self::SOURCE_META_KEY, self::SOURCE_NAME);
                $thumbnailId = get_term_meta((int) $termId, 'thumbnail_id', true);
                if ($thumbnailId !== '') {
                    update_term_meta($translationId, 'thumbnail_id', $thumbnailId);
                }
                pll_set_term_language($translationId, 'ru');
                pll_save_term_translations(['uk' => (int) $termId, 'ru' => $translationId]);
                $translationIds[$termId] = $translationId;
                unset($pending[$termId]);
                $progress = true;
            }

            if (! $progress) {
                $errors[] = 'Could not resolve the remaining category hierarchy.';
                break;
            }
        }

        $assignedProducts = $errors === [] ? $this->assignPresentationProducts() : 0;

        return [
            'status' => $errors === [] ? 'ok' : 'partial',
            'mode' => 'execute',
            'summary' => [
                'created' => count($created),
                'updated' => count($updated),
                'unchanged' => count($unchanged),
                'assignedProducts' => $assignedProducts,
                'errors' => count($errors),
            ],
            'errors' => $errors,
        ];
    }

    private function assignPresentationProducts(): int
    {
        $translationIds = get_posts([
            'post_type' => 'product',
            'post_status' => 'publish',
            'numberposts' => -1,
            'fields' => 'ids',
            'suppress_filters' => true,
            'meta_key' => '_maruderm_translation_presentation',
            'meta_value' => '1',
        ]);
        $assigned = 0;

        foreach ($translationIds as $translationId) {
            $canonicalId = absint(get_post_meta((int) $translationId, '_maruderm_canonical_product_id', true));
            $canonicalTerms = wp_get_object_terms($canonicalId, 'product_cat', ['fields' => 'ids']);
            if (is_wp_error($canonicalTerms)) {
                continue;
            }
            $localizedTerms = array_values(array_filter(array_map(
                static fn (int $termId): int => (int) pll_get_term($termId, 'ru'),
                $canonicalTerms
            )));
            wp_set_object_terms((int) $translationId, $localizedTerms, 'product_cat', false);
            $assigned++;
        }

        return $assigned;
    }

    private function categorySlug(string $name, int $translationId): string
    {
        $baseSlug = (new \Maruderm\Multilingual\RussianSlugger())->slug($name);
        $existing = get_term_by('slug', $baseSlug, 'product_cat');

        if (! $existing instanceof WP_Term || $existing->term_id === $translationId) {
            return $baseSlug;
        }

        $localizedSlug = $baseSlug . '-ru';
        $localizedExisting = get_term_by('slug', $localizedSlug, 'product_cat');

        return ! $localizedExisting instanceof WP_Term || $localizedExisting->term_id === $translationId
            ? $localizedSlug
            : $localizedSlug . '-' . absint($translationId ?: $existing->term_id);
    }
}

$sourcePath = getenv('MARUDERM_TRANSLATION_SOURCE');
$sourcePath = is_string($sourcePath) && $sourcePath !== ''
    ? $sourcePath
    : ABSPATH . 'products/product-category-translations-ru.json';
$mode = getenv('MARUDERM_TRANSLATION_MODE');
$mode = is_string($mode) && $mode !== '' ? $mode : 'dry-run';
$report = (new RussianProductCategoryImporter($sourcePath, $mode))->run();

echo wp_json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;

if (($report['status'] ?? 'failed') !== 'ok') {
    exit(1);
}
