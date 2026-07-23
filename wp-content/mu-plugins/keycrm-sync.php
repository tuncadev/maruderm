<?php

if (!defined('WP_CLI') || !WP_CLI) return;

class KeyCRM_Sync_Command {

    private $api_url = 'https://openapi.keycrm.app/v1/products';
    private $category_url = 'https://openapi.keycrm.app/v1/products/categories';

    /*
     * PRODUCTS
     */
    public function products($args, $assoc_args) {

        $paged = 1;
        $per_page = 50;

        do {
            $products = wc_get_products([
                'limit' => $per_page,
                'paged' => $paged,
                'status' => ['publish'],
            ]);

            if (!$products) break;

            foreach ($products as $p) {
                $this->sync_product($p);
            }

            $paged++;

        } while (count($products) === $per_page);

        WP_CLI::success("Products sync done.");
    }

    /*
     * CATEGORIES (parents only)
     */
    public function categories($args, $assoc_args) {

        $terms = get_terms([
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
            'parent' => 0,
        ]);

        foreach ($terms as $term) {

            // skip if already mapped
            if (get_term_meta($term->term_id, '_keycrm_id', true)) {
                WP_CLI::log("SKIP {$term->name} (already mapped)");
                continue;
            }

            $payload = [
                "name" => $term->name,
                "parent_id" => null,
            ];

            $response = wp_remote_post($this->category_url, [
	                'headers' => [
	                    'Authorization' => 'Bearer ' . $this->get_token(),
	                    'Content-Type' => 'application/json',
	                    'Accept' => 'application/json',
                ],
                'body' => json_encode($payload),
                'timeout' => 20,
            ]);

            if (is_wp_error($response)) {
                WP_CLI::warning("ERROR {$term->term_id}: " . $response->get_error_message());
                continue;
            }

            $code = wp_remote_retrieve_response_code($response);
            $body = json_decode(wp_remote_retrieve_body($response), true);

            if ($code >= 200 && $code < 300 && !empty($body['id'])) {

                update_term_meta($term->term_id, '_keycrm_id', $body['id']);
                WP_CLI::log("OK {$term->name} => {$body['id']}");

            } else {
                WP_CLI::warning("FAIL {$term->name} => {$code}");
                WP_CLI::log(wp_remote_retrieve_body($response));
            }
        }

        WP_CLI::success("Categories synced.");
    }

    /*
     * SINGLE PRODUCT
     */

    private function product_exists_by_sku($sku) {

        $url = $this->api_url . '?search=' . urlencode($sku);

        $response = wp_remote_get($url, [
	            'headers' => [
	                'Authorization' => 'Bearer ' . $this->get_token(),
	                'Accept' => 'application/json',
	            ],
            'timeout' => 20,
        ]);

        if (is_wp_error($response)) {
            WP_CLI::warning("SKU check failed: " . $response->get_error_message());
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (!empty($body['data'])) {
            foreach ($body['data'] as $item) {
                if (!empty($item['sku']) && $item['sku'] === $sku) {
                    return true;
                }
            }
        }

        return false;
    }
    private function sync_product($p) {

        // --- IMAGES ---
        $pictures = [];

        $thumb = get_the_post_thumbnail_url($p->get_id(), 'full');
        if ($thumb && $this->is_public_url($thumb)) {
            $pictures[] = $thumb;
        }

        foreach ($p->get_gallery_image_ids() as $gid) {
            $url = wp_get_attachment_url($gid);
            if ($url && $this->is_public_url($url)) {
                $pictures[] = $url;
            }
        }

        // --- CATEGORY (child -> parent fallback) ---
        $terms = wp_get_post_terms($p->get_id(), 'product_cat');

        $category_id = null;

        if (!empty($terms)) {
            $term = $terms[0];

            if ($term->parent) {
                $category_id = get_term_meta($term->parent, '_keycrm_id', true);
            } else {
                $category_id = get_term_meta($term->term_id, '_keycrm_id', true);
            }
        }

        if (!$category_id) {
            WP_CLI::warning("SKIP {$p->get_id()} → no mapped category");
            return;
        }

        // --- PRICE ---
        $price = (float)$p->get_price();
        if ($price <= 0) {
            WP_CLI::warning("SKIP {$p->get_id()} → invalid price");
            return;
        }
        $sku = $p->get_sku() ?: "product-" . $p->get_id();

// SKIP duplicates
        if ($this->product_exists_by_sku($sku)) {
            WP_CLI::log("SKIP {$p->get_id()} → SKU exists ({$sku})");
            return;
        }
        // --- PAYLOAD ---
        $payload = [
            "name" => $p->get_name(),
            "description" => $p->get_description(),
            "pictures" => $pictures,
            "currency_code" => get_woocommerce_currency(),
            "sku" => $sku,
            "price" => $price,
            "purchased_price" => $price,
            "unit_type" => get_post_meta($p->get_id(), "unit_type", true) ?: "шт",
            "weight" => (float)$p->get_weight(),
            "length" => (float)$p->get_length(),
            "width" => (float)$p->get_width(),
            "height" => (float)$p->get_height(),
            "category_id" => (int)$category_id,
        ];

        // --- REQUEST ---
        $response = wp_remote_post($this->api_url, [
	            'headers' => [
	                'Authorization' => 'Bearer ' . $this->get_token(),
	                'Content-Type' => 'application/json',
	                'Accept' => 'application/json',
            ],
            'body' => json_encode($payload),
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            WP_CLI::warning("ERROR {$p->get_id()}: " . $response->get_error_message());
            return;
        }

        $code = wp_remote_retrieve_response_code($response);

        if ($code >= 200 && $code < 300) {
            WP_CLI::log("OK {$p->get_id()}");
        } else {
            WP_CLI::warning("FAIL {$p->get_id()} => {$code}");
            WP_CLI::log(wp_remote_retrieve_body($response));
        }
    }

    /*
	 * HELPERS
	 */
	private function get_token() {
	    $token = getenv('KEYCRM_API_TOKEN');

	    if ((!is_string($token) || trim($token) === '') && defined('KEYCRM_API_TOKEN')) {
	        $token = (string) constant('KEYCRM_API_TOKEN');
	    }

	    $token = is_string($token) ? trim($token) : '';

	    if ($token === '') {
	        WP_CLI::error('KEYCRM_API_TOKEN is not configured.');
	    }

	    return $token;
	}

	private function is_public_url($url) {
	    return !str_contains($url, '.local') && !str_contains($url, 'localhost');
	}
}

if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::add_command('keycrm', 'KeyCRM_Sync_Command');
}
