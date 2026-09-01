<?php
/**
 * Imports linked Russian global-attribute terms for presentation products.
 *
 * Defaults to dry-run. Set MARUDERM_TRANSLATION_MODE=execute to mutate locally.
 *
 * @package Maruderm
 */

if (! defined('ABSPATH')) {
    exit;
}

final class RussianProductAttributeImporter
{
    private const MARKER_META_KEY = '_maruderm_translation_presentation';

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

        $records = json_decode((string) file_get_contents($this->sourcePath), true);
        if (! is_array($records)) {
            return ['status' => 'failed', 'error' => 'Attribute source is invalid.'];
        }

        $errors = [];
        $planned = [];
        $summary = ['create' => 0, 'update' => 0, 'unchanged' => 0];
        $seen = [];

        foreach ($records as $record) {
            $termId = absint($record['term_id'] ?? 0);
            $taxonomy = sanitize_key((string) ($record['taxonomy'] ?? ''));
            $name = trim((string) ($record['name_ru'] ?? ''));
            $term = $termId > 0 && taxonomy_exists($taxonomy) ? get_term($termId, $taxonomy) : null;

            if (! $term instanceof WP_Term || $name === '' || isset($seen[$termId])) {
                $errors[] = sprintf('Invalid or duplicate attribute term %d.', $termId);
                continue;
            }
            if (pll_get_term_language($termId, 'slug') !== 'uk') {
                $errors[] = sprintf('Canonical attribute term %d is not Ukrainian.', $termId);
                continue;
            }

            $seen[$termId] = true;
            $translationId = (int) pll_get_term($termId, 'ru');
            $expectedSlug = $this->termSlug($name, $taxonomy, $translationId);
            $action = 'create';

            if ($translationId > 0) {
                if (get_term_meta($translationId, self::MARKER_META_KEY, true) !== '1') {
                    $errors[] = sprintf('Attribute term %d has unmanaged Russian term %d.', $termId, $translationId);
                    continue;
                }
                $translation = get_term($translationId, $taxonomy);
                $action = $translation instanceof WP_Term
                    && $translation->name === $name
                    && $translation->slug === $expectedSlug
                    ? 'unchanged'
                    : 'update';
            }

            $summary[$action]++;
            $planned[] = compact('termId', 'taxonomy', 'name', 'translationId', 'expectedSlug', 'action');
        }

        if ($errors !== [] || $this->mode === 'dry-run') {
            return [
                'status' => $errors === [] ? 'ok' : 'failed',
                'mode' => $this->mode,
                'sourceCount' => count($records),
                'summary' => $summary,
                'errors' => $errors,
            ];
        }

        $result = ['created' => 0, 'updated' => 0, 'unchanged' => 0, 'errors' => []];

        foreach ($planned as $item) {
            $translationId = $item['translationId'];

            if ($item['action'] === 'create') {
                $inserted = wp_insert_term($item['name'], $item['taxonomy'], ['slug' => $item['expectedSlug']]);
                if (is_wp_error($inserted)) {
                    $result['errors'][] = sprintf('Term %d failed: %s', $item['termId'], $inserted->get_error_message());
                    continue;
                }
                $translationId = (int) $inserted['term_id'];
                $result['created']++;
            } elseif ($item['action'] === 'update') {
                $updated = wp_update_term($translationId, $item['taxonomy'], [
                    'name' => $item['name'],
                    'slug' => $item['expectedSlug'],
                ]);
                if (is_wp_error($updated)) {
                    $result['errors'][] = sprintf('Term %d failed: %s', $item['termId'], $updated->get_error_message());
                    continue;
                }
                $result['updated']++;
            } else {
                $result['unchanged']++;
            }

            update_term_meta($translationId, self::MARKER_META_KEY, '1');
            update_term_meta($translationId, '_maruderm_translation_source', 'product-attribute-translations-ru.json');
            pll_set_term_language($translationId, 'ru');
            pll_save_term_translations(['uk' => $item['termId'], 'ru' => $translationId]);
        }

        $assignedProducts = $result['errors'] === [] ? $this->assignPresentationProducts($records) : 0;

        return [
            'status' => $result['errors'] === [] ? 'ok' : 'partial',
            'mode' => 'execute',
            'summary' => [
                'created' => $result['created'],
                'updated' => $result['updated'],
                'unchanged' => $result['unchanged'],
                'assignedProducts' => $assignedProducts,
                'errors' => count($result['errors']),
            ],
            'errors' => $result['errors'],
        ];
    }

    /** @param array<int, array<string, mixed>> $records */
    private function assignPresentationProducts(array $records): int
    {
        $taxonomies = array_values(array_unique(array_column($records, 'taxonomy')));
        $translationIds = get_posts([
            'post_type' => 'product',
            'post_status' => 'publish',
            'numberposts' => -1,
            'fields' => 'ids',
            'suppress_filters' => true,
            'meta_key' => '_maruderm_translation_presentation',
            'meta_value' => '1',
        ]);

        foreach ($translationIds as $translationId) {
            $canonicalId = absint(get_post_meta((int) $translationId, '_maruderm_canonical_product_id', true));

            foreach ($taxonomies as $taxonomy) {
                $canonicalTerms = wp_get_object_terms($canonicalId, $taxonomy, ['fields' => 'ids']);
                if (is_wp_error($canonicalTerms)) {
                    continue;
                }
                $localizedTerms = array_values(array_filter(array_map(
                    static fn (int $termId): int => (int) pll_get_term($termId, 'ru'),
                    $canonicalTerms
                )));
                wp_set_object_terms((int) $translationId, $localizedTerms, $taxonomy, false);
            }
        }

        return count($translationIds);
    }

    private function termSlug(string $name, string $taxonomy, int $translationId): string
    {
        $baseSlug = (new \Maruderm\Multilingual\RussianSlugger())->slug($name);
        $existing = get_term_by('slug', $baseSlug, $taxonomy);

        if (! $existing instanceof WP_Term || $existing->term_id === $translationId) {
            return $baseSlug;
        }

        return $baseSlug . '-ru';
    }
}

$sourcePath = getenv('MARUDERM_TRANSLATION_SOURCE');
$sourcePath = is_string($sourcePath) && $sourcePath !== ''
    ? $sourcePath
    : ABSPATH . 'products/product-attribute-translations-ru.json';
$mode = getenv('MARUDERM_TRANSLATION_MODE');
$mode = is_string($mode) && $mode !== '' ? $mode : 'dry-run';
$report = (new RussianProductAttributeImporter($sourcePath, $mode))->run();

echo wp_json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;

if (($report['status'] ?? 'failed') !== 'ok') {
    exit(1);
}
