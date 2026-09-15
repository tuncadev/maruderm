<?php
/** Read-only canonical WooCommerce and KeyCRM source. */
if (! defined('ABSPATH')) { exit; }

final class MarudermKastaSourceCollector implements \Maruderm\Kasta\Source
{
    public function collect(): array
    {
        if (! function_exists('pll_get_post_language') || ! class_exists('KeyCRM_Sync_Config')) {
            throw new RuntimeException('Polylang and KeyCRM configuration are required.');
        }
        if (get_woocommerce_currency() !== 'UAH') {
            throw new RuntimeException('Kasta requires UAH prices.');
        }
        $products = [];
        $ids = get_posts(['post_type' => 'product', 'post_status' => 'publish', 'numberposts' => -1,
            'fields' => 'ids', 'orderby' => 'ID', 'order' => 'ASC', 'suppress_filters' => true]);
        foreach ($ids as $id) {
            if (pll_get_post_language($id, 'slug') !== 'uk') {
                continue;
            }
            $parent = wc_get_product($id);
            if (! $parent instanceof WC_Product) {
                throw new RuntimeException("Cannot read product {$id}.");
            }
            if (! $parent->is_type(['simple', 'variable'])) {
                throw new RuntimeException("Unsupported product type: {$id}.");
            }
            $sellableIds = $parent->is_type('variable') ? $parent->get_children() : [$id];
            foreach ($sellableIds as $sellableId) {
                $sellable = wc_get_product($sellableId);
                if (! $sellable instanceof WC_Product || $sellable->get_status() !== 'publish') {
                    continue;
                }
                $products[] = $this->product($sellable, $parent);
            }
        }
        return ['schema_version' => 1, 'generated_at_utc' => gmdate('c'), 'source_site' => home_url(),
            'currency' => 'UAH', 'stock_scope' => 'keycrm_account_total',
            'products' => $products, 'stocks' => $this->stocks(), 'completed_at_utc' => gmdate('c')];
    }

    private function product(WC_Product $product, WC_Product $parent): array
    {
        $translationId = (int) pll_get_post($parent->get_id(), 'ru');
        $translation = $translationId > 0 && pll_get_post_language($translationId, 'slug') === 'ru'
            ? wc_get_product($translationId) : null;
        $terms = wp_get_post_terms($parent->get_id(), 'product_cat');
        if (is_wp_error($terms)) {
            throw new RuntimeException('Cannot read categories.');
        }
        $categories = [];
        foreach ($terms as $term) {
            $categories[] = ['id' => $term->term_id, 'name' => $term->name,
                'depth' => count(get_ancestors($term->term_id, 'product_cat'))];
        }
        $imageIds = array_unique(array_filter(array_merge(
            [$product->get_image_id(), $parent->get_image_id()], $parent->get_gallery_image_ids()
        )));
        $images = [];
        foreach ($imageIds as $imageId) {
            $url = wp_get_original_image_url($imageId);
            if (! $url) {
                throw new RuntimeException("Missing attachment URL: {$imageId}.");
            }
            $images[] = (new \Maruderm\Kasta\Media())->publicUrl($url);
        }
        $params = [];
        foreach ($parent->get_attributes() as $attribute) {
            $label = wc_attribute_label($attribute->get_name(), $parent);
            $value = $product->get_attribute($attribute->get_name());
            if ($value !== '') {
                $params[$label] = $value;
            }
        }
        return ['id' => $product->get_id(), 'parent_id' => $parent->get_id(),
            'type' => $product->get_type(), 'language' => 'uk', 'sku' => trim($product->get_sku('edit')),
            'name_ua' => $product->get_name('edit'),
            'name_ru' => $translation instanceof WC_Product ? $translation->get_name('edit') : '',
            'description_ua' => $product->get_description('edit') ?: $parent->get_description('edit'),
            'description_ru' => $translation instanceof WC_Product ? $translation->get_description('edit') : '',
            'regular_price' => $product->get_regular_price('edit'),
            'images' => $images, 'categories' => $categories, 'params' => $params,
            'weight_kg' => $product->get_weight('edit') !== '' ? wc_get_weight((float) $product->get_weight('edit'), 'kg') : null,
            'dimensions_cm' => array_map(static fn ($value) => $value !== '' ? wc_get_dimension((float) $value, 'cm') : null,
                [$product->get_length('edit'), $product->get_width('edit'), $product->get_height('edit')])];
    }

    private function stocks(): array
    {
        $token = (new KeyCRM_Sync_Config())->get_token();
        if ($token === '') {
            throw new RuntimeException('Missing KeyCRM token.');
        }
        $items = [];
        $expectedTotal = null;
        for ($page = 1; $page <= 1000; $page++) {
            if ($page > 1) {
                usleep(1100000);
            }
            $response = wp_remote_get('https://openapi.keycrm.app/v1/offers/stocks?limit=50&page=' . $page,
                ['headers' => ['Authorization' => 'Bearer ' . $token, 'Accept' => 'application/json'],
                    'redirection' => 0, 'timeout' => 30]);
            if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
                throw new RuntimeException('KeyCRM stock read failed; snapshot is incomplete.');
            }
            $body = json_decode(wp_remote_retrieve_body($response), true, 512, JSON_THROW_ON_ERROR);
            if (! is_array($body['data'] ?? null) || (int) ($body['current_page'] ?? 0) !== $page
                || ! isset($body['total'], $body['last_page'])) {
                throw new RuntimeException('Unexpected KeyCRM pagination.');
            }
            $expectedTotal ??= (int) $body['total'];
            if ($expectedTotal !== (int) $body['total']) {
                throw new RuntimeException('KeyCRM catalog changed during collection; retry a new snapshot.');
            }
            foreach ($body['data'] as $item) {
                $items[] = array_intersect_key($item, array_flip(['id', 'sku', 'quantity', 'reserve']));
            }
            if ($page >= (int) $body['last_page']) {
                if (count($items) !== $expectedTotal) {
                    throw new RuntimeException('Incomplete KeyCRM stock pagination.');
                }
                return $items;
            }
        }
        throw new RuntimeException('KeyCRM page limit exceeded.');
    }
}
