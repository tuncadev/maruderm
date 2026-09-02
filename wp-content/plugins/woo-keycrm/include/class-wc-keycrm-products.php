<?php
/**
 * KeyCRM Products Import/Export
 *
 * @package  WC_Keycrm_Products
 * @category Integration
 * @author   KeyCRM
 */

if (!class_exists('WC_Keycrm_Products')) {

    /**
     * Class WC_Keycrm_Products
     */
    class WC_Keycrm_Products
    {
        /** @var WC_Keycrm_Client_V6 */
        protected $apiClient;

        /** @var array */
        protected $settings;


        /** @var string */
        protected $importStatusKey = 'keycrm_products_imported';

        /** @var int Delay between API requests in milliseconds */
        protected $requestDelay = 1000;

        /** @var bool Flag to prevent recursion during bulk import */
        protected $isBulkImport = false;

        /**
         * Constructor
         *
         * @param WC_Keycrm_Client_V6 $apiClient
         * @param array $settings
         */
        public function __construct($apiClient, $settings)
        {
            $this->apiClient = $apiClient;
            $this->settings = $settings;
        }

        /**
         * Add delay between API requests to avoid rate limiting
         */
        protected function addRequestDelay()
        {
            if ($this->requestDelay > 0) {
                usleep($this->requestDelay * 1000);
            }
        }

        /**
         * Import all products to KeyCRM
         *
         * @return array Result of import
         */
        public function importAllProducts()
        {
            if ($this->isProductsImported()) {
                WC_Keycrm_Logger::add(__('Products import attempted but products have already been imported to KeyCRM', 'keycrm'));
                return array(
                    'success' => false,
                    'message' => __('Products have already been imported to KeyCRM', 'keycrm'),
                    'count' => 0
                );
            }

            if (defined('DOING_KEYCRM_PRODUCT_IMPORT') && DOING_KEYCRM_PRODUCT_IMPORT) {
                WC_Keycrm_Logger::add(__('Products import attempted but import is already in progress', 'keycrm'));
                return array(
                    'success' => false,
                    'message' => __('Import already in progress', 'keycrm')
                );
            }

            if (!defined('DOING_KEYCRM_PRODUCT_IMPORT')) {
                define('DOING_KEYCRM_PRODUCT_IMPORT', true);
            }

            $this->isBulkImport = true;

            $importedCount = 0;
            $failedCount = 0;
            $errors = array();

            $product_types = array('simple', 'variable', 'grouped', 'external', 'composite', 'bundle', 'subscription', 'variable-subscription');

            WC_Keycrm_Logger::add(sprintf(__('Starting bulk products import. Product types to process: %s', 'keycrm'), implode(', ', $product_types)));

            foreach ($product_types as $type) {
                $args = array(
                    'status' => 'publish',
                    'limit' => -1,
                    'type' => $type,
                );

                $products = wc_get_products($args);

                foreach ($products as $product) {
                    try {
                        $this->addRequestDelay();
                        $result = $this->importProduct($product, true);

                        if ($result['success']) {
                            $importedCount++;
                        } else {
                            $failedCount++;
                            $errorMsg = sprintf(__('Product %s (ID: %d, SKU: %s): %s', 'keycrm'),
                                $product->get_name(),
                                $product->get_id(),
                                $product->get_sku(),
                                $result['message']
                            );
                            $errors[] = $errorMsg;
                        }

                    } catch (Exception $e) {
                        $failedCount++;
                        $errorMsg = sprintf(__('Product %s: %s', 'keycrm'),
                            $product->get_name(),
                            $e->getMessage()
                        );
                        $errors[] = $errorMsg;
                    }
                }
            }

            $this->isBulkImport = false;

            if ($importedCount > 0) {
                update_option($this->importStatusKey, time());
            }
            WC_Keycrm_Logger::add(sprintf(__('Imported %d products, failed: %d', 'keycrm'), $importedCount, $failedCount));
            return array(
                'success' => $importedCount > 0,
                'imported' => $importedCount,
                'failed' => $failedCount,
                'errors' => $errors,
                'message' => sprintf(__('Imported %d products, failed: %d', 'keycrm'), $importedCount, $failedCount)
            );
        }

        /**
         * Import single product to KeyCRM
         *
         * @param WC_Product $product
         * @param bool $isBulkImport Whether this is part of bulk import
         * @return array
         */
        public function importProduct($product, $isBulkImport = false)
        {
            $productId = $product->get_id();
            $productSku = $product->get_sku();
            $productName = $product->get_name();

            if (!$isBulkImport && !$this->isBulkImport && defined('DOING_KEYCRM_PRODUCT_IMPORT') && DOING_KEYCRM_PRODUCT_IMPORT) {
                WC_Keycrm_Logger::add(sprintf(__('Product import skipped - already in progress: ID=%d, SKU=%s, Name="%s"', 'keycrm'),
                    $productId, $productSku, $productName
                ));
                return array(
                    'success' => false,
                    'message' => __('Import already in progress', 'keycrm')
                );
            }

            $existingKeycrmId = get_post_meta($productId, '_keycrm_product_id', true);
            if ($existingKeycrmId) {
                WC_Keycrm_Logger::add(sprintf(__('Product already exists in KeyCRM: ID=%d, SKU=%s, Name="%s", KeyCRM ID=%s skip', 'keycrm'),
                    $productId, $productSku, $productName, $existingKeycrmId
                ));
                return array(
                    'success' => false,
                    'message' => sprintf(__('Product already exists in KeyCRM: %s - %s skip', 'keycrm'), $productName, $productSku)
                );
            }

            $productData = $this->prepareProductData($product);

            $response = $this->apiClient->productsCreate($productData);

            if ($response->isSuccessful()) {
                if ($response->getId()) {
                    $keycrmProductId = $response->getId();
                    update_post_meta($product->get_id(), '_keycrm_product_id', $keycrmProductId);

                    if ($product->is_type('variable')) {
                        $variationsResult = $this->importProductVariations($product, $keycrmProductId);

                        if (!$variationsResult['success']) {
                            WC_Keycrm_Logger::add(sprintf(__('Product created but variations failed: ID=%d, KeyCRM ID=%s. Error: %s', 'keycrm'),
                                $productId, $keycrmProductId, $variationsResult['message']
                            ));
                            return array(
                                'success' => false,
                                'message' => __('Product created but variations failed: ', 'keycrm') . $variationsResult['message']
                            );
                        }
                    }

                    WC_Keycrm_Logger::add(sprintf(__('Product import successfully: WooCommerce ID=%d, KeyCRM ID=%s, SKU=%s, Name="%s"', 'keycrm'),
                        $productId, $keycrmProductId, $productSku, $productName
                    ));

                    return array(
                        'success' => true,
                        'product_id' => $keycrmProductId,
                        'message' => __('Product imported successfully', 'keycrm')
                    );
                }
            }

            $rawResponse = $response->getRawResponse();
            return array(
                'success' => false,
                'message' => __('Failed to import product: ', 'keycrm') . $rawResponse
            );
        }


        /**
         * Import product variations
         *
         * @param WC_Product_Variable $product
         * @param int $keycrmProductId
         * @return array
         */
        public function importProductVariations($product, $keycrmProductId)
        {
            $productId = $product->get_id();
            $variations = $product->get_available_variations();

            WC_Keycrm_Logger::add(sprintf(__('Starting variations import for product: WooCommerce ID=%d, KeyCRM ID=%s. Found %d variations', 'keycrm'),
                $productId, $keycrmProductId, count($variations)
            ));

            if (empty($variations)) {
                WC_Keycrm_Logger::add(sprintf(__('No variations to import for product: ID=%d, KeyCRM ID=%s', 'keycrm'),
                    $productId, $keycrmProductId
                ));
                return array(
                    'success' => true,
                    'count' => 0,
                    'message' => __('No variations to import', 'keycrm')
                );
            }

            $offers = array();
            foreach ($variations as $index => $variationData) {
                $variationId = $variationData['variation_id'];
                $variationProduct = wc_get_product($variationId);

                if ($variationProduct) {
                    WC_Keycrm_Logger::add(sprintf(__('Preparing variation %d/%d: Variation ID=%d, SKU=%s for product ID=%d', 'keycrm'),
                        $index + 1, count($variations), $variationId, $variationProduct->get_sku(), $productId
                    ));

                    $offerData = $this->prepareVariationData($variationProduct);
                    $offers[] = $offerData;
                } else {
                    WC_Keycrm_Logger::add(sprintf(__('Failed to load variation product: Variation ID=%d for product ID=%d', 'keycrm'),
                        $variationId, $productId
                    ));
                }
            }

            if (!empty($offers)) {
                $response = $this->apiClient->productsCreateOffers($keycrmProductId, array('offers' => $offers));

                if ($response->isSuccessful()) {
                    $importedCount = count($offers);

                    foreach ($variations as $variation) {
                        update_post_meta($variation['variation_id'], '_keycrm_product_id', $keycrmProductId);
                    }

                    WC_Keycrm_Logger::add(sprintf(__('Variations imported successfully: %d variations for product KeyCRM ID=%s', 'keycrm'),
                        $importedCount, $keycrmProductId
                    ));

                    return array(
                        'success' => true,
                        'count' => $importedCount,
                        'message' => sprintf(__('Imported %d variations', 'keycrm'), $importedCount)
                    );
                } else {
                    $errorMessage = $response->getRawResponse();
                    WC_Keycrm_Logger::add(sprintf(__('Failed to import variations for product KeyCRM ID=%s. API Error: %s', 'keycrm'),
                        $keycrmProductId, $errorMessage
                    ));
                    return array(
                        'success' => false,
                        'count' => 0,
                        'message' => __('Failed to import variations: ', 'keycrm') . $errorMessage
                    );
                }
            }

            WC_Keycrm_Logger::add(sprintf(__('No valid variations to import for product: KeyCRM ID=%s', 'keycrm'),
                $keycrmProductId
            ));

            return array(
                'success' => true,
                'count' => 0,
                'message' => __('No variations to import', 'keycrm')
            );
        }


        /**
         * Prepare product data for KeyCRM API
         *
         * @param WC_Product $product
         * @return array
         */
        protected function prepareProductData($product)
        {
            if (!$product || !is_a($product, 'WC_Product')) {
                return array();
            }

            $images = array();
            if (method_exists($product, 'get_image_id') && $product->get_id() > 0) {
                $featuredImageId = $product->get_image_id();
                if ($featuredImageId) {
                    $featuredImageUrl = wp_get_attachment_image_url($featuredImageId, 'full');
                    if ($featuredImageUrl) {
                        $images[] = $featuredImageUrl;
                    }
                }
            }

            if (method_exists($product, 'get_gallery_image_ids') && $product->get_id() > 0) {
                $attachmentIds = $product->get_gallery_image_ids();
                if (is_array($attachmentIds)) {
                    foreach ($attachmentIds as $attachmentId) {
                        $imageUrl = wp_get_attachment_image_url($attachmentId, 'full');
                        if ($imageUrl) {
                            $images[] = $imageUrl;
                        }
                    }
                }
            }

            $length = $product->get_length() ? (float) $product->get_length() : 0;
            $width = $product->get_width() ? (float) $product->get_width() : 0;
            $height = $product->get_height() ? (float) $product->get_height() : 0;
            $weight = $product->get_weight() ? (float) $product->get_weight() : 0;

            $price = $product->get_price();
            if (empty($price) || $price <= 0) {
                $price = 0;
            }

            $productData = array(
                'name' => $product->get_name(),
                'description' => $product->get_description(),
                'sku' => $product->get_sku(),
                'price' => (float) $price,
                'currency_code' => get_woocommerce_currency(),
                'unit_type' => '',
                'weight' => $weight,
                'length' => $length,
                'width' => $width,
                'height' => $height,
            );

            if (!empty($images)) {
                $productData['pictures'] = $images;
            }

            $barcode = $product->get_meta('_barcode');
            if ($barcode) {
                $productData['barcode'] = $barcode;
            }

            if ($product->is_type('variable')) {
                $productData['has_offers'] = true;
            }

            return $productData;
        }

        /**
         * Prepare variation data for KeyCRM API
         *
         * @param WC_Product_Variation $variation
         * @return array
         */
        protected function prepareVariationData($variation)
        {
            $imageUrl = null;
            $imageId = $variation->get_image_id();
            if ($imageId) {
                $imageUrl = wp_get_attachment_image_url($imageId, 'full');
            }

            $properties = array();
            $attributes = $variation->get_attributes();
            foreach ($attributes as $attributeName => $attributeValue) {

                if (!$attributeValue) {
                    continue;
                }

                $label = wc_attribute_label($attributeName, $variation);

                // value
                $value = $attributeValue;

                if (taxonomy_exists($attributeName)) {
                    $term = get_term_by('slug', $attributeValue, $attributeName);
                    if ($term && !is_wp_error($term)) {
                        $value = $term->name;
                    }
                }

                // safety decode if it was urlencoded somewhere earlier
                if (strpos($value, '%') !== false) {
                    $decoded = rawurldecode($value);
                    if (mb_check_encoding($decoded, 'UTF-8')) {
                        $value = $decoded;
                    }
                }

                $properties[] = array(
                    'name'  => $label,
                    'value' => $value
                );
            }

            $length = $variation->get_length() ? (float) $variation->get_length() : 0;
            $width  = $variation->get_width() ? (float) $variation->get_width() : 0;
            $height = $variation->get_height() ? (float) $variation->get_height() : 0;
            $weight = $variation->get_weight() ? (float) $variation->get_weight() : 0;

            $price = $variation->get_price();
            if (empty($price) || $price <= 0) {
                $price = 0;
            }

            $variationData = array(
                'price'            => (float) $price,
                'weight'           => $weight,
                'length'           => $length,
                'width'            => $width,
                'height'           => $height,
            );

            if (!empty($variation->get_sku()))
                $variationData['sku'] = $variation->get_sku();

            if ($imageUrl) {
                $variationData['image_url'] = $imageUrl;
            }

            if (!empty($properties)) {
                $variationData['properties'] = $properties;
            }

            $barcode = $variation->get_meta('_barcode');
            if ($barcode) {
                $variationData['barcode'] = $barcode;
            }

            return $variationData;
        }


        /**
         * Get KeyCRM category ID by WooCommerce category ID
         *
         * @param int $wcCategoryId
         * @return int
         */
        protected function getKeycrmCategoryId($wcCategoryId)
        {
            $mapping = get_option('keycrm_category_mapping', array());

            if (isset($mapping[$wcCategoryId])) {
                return $mapping[$wcCategoryId];
            }

            $defaultCategoryId = 1;

            $wcCategory = get_term($wcCategoryId, 'product_cat');

            if ($wcCategory && !is_wp_error($wcCategory)) {
                $mapping[$wcCategoryId] = $defaultCategoryId;
                update_option('keycrm_category_mapping', $mapping);
            }

            return $defaultCategoryId;
        }

        /**
         * Create product in KeyCRM when created in WooCommerce
         *
         * @param int $productId
         * @param WC_Product $product
         * @return array
         */
        public function createProductInKeycrm($productId, $product)
        {
            if (!$this->isProductsImported()) {
                return array(
                    'success' => false,
                    'message' => __('Products not yet imported to KeyCRM', 'keycrm')
                );
            }

            if (defined('DOING_KEYCRM_PRODUCT_IMPORT') && DOING_KEYCRM_PRODUCT_IMPORT) {
                return array(
                    'success' => false,
                    'message' => __('Import already in progress', 'keycrm')
                );
            }

            $this->addRequestDelay();
            return $this->importProduct($product, false);
        }

        /**
         * Update product in KeyCRM
         *
         * @param int $productId
         * @param WC_Product $product
         * @return array
         */
        public function updateProductInKeycrm($productId, $product)
        {
            $keycrmProductId = get_post_meta($productId, '_keycrm_product_id', true);

            if (!$keycrmProductId) {
                return $this->createProductInKeycrm($productId, $product);
            }

            static $processedProducts = [];

            if (isset($processedProducts[$productId])) {
                return array(
                    'success' => false,
                    'message' => __('Product update already processed in this request', 'keycrm')
                );
            }

            $processedProducts[$productId] = true;

            $this->addRequestDelay();
            return array(
                'success' => false,
                'message' => __('Product updates are disabled', 'keycrm')
            );
        }

        /**
         * Create variation in KeyCRM
         *
         * @param int $variationId
         * @param int $productId
         * @return array
         */
        public function createVariationInKeycrm($variationId, $productId)
        {
            $parentProduct = wc_get_product($productId);
            $variation = wc_get_product($variationId);
            $keycrmProductId = get_post_meta($productId, '_keycrm_product_id', true);
            $keycrmVariationtId = get_post_meta($variationId, '_keycrm_product_id', true);

            if ($keycrmVariationtId) {
                return array(
                    'success' => false,
                    'message' => sprintf(__('Product variation already added skip Variation ID=%d, Name="%s", SKU=%s, Parent KeyCRM ID=%s', 'keycrm'),$variationId,
                        $variation->get_name(),
                        $variation->get_sku(),
                        $keycrmProductId)
                );
            }

            if (!$parentProduct || !$variation || !$keycrmProductId) {
                return array(
                    'success' => false,
                    'message' => sprintf(__('Parent product or variation not found Variation ID=%d, Name="%s", SKU=%s, Parent KeyCRM ID=%s', 'keycrm'),$variationId,
                        $variation->get_name(),
                        $variation->get_sku(),
                        $keycrmProductId)
                );
            }

            $offerData = $this->prepareVariationData($variation);

            $this->addRequestDelay();
            $response = $this->apiClient->productsCreateOffers($keycrmProductId, array('offers' => array($offerData)));

            if ($response->isSuccessful()) {
                update_post_meta($variationId, '_keycrm_product_id', $keycrmProductId);
                WC_Keycrm_Logger::add(sprintf(__('Variation created in KeyCRM: Variation ID=%d, Name="%s", SKU=%s, Parent KeyCRM ID=%s', 'keycrm'),
                    $variationId,
                    $variation->get_name(),
                    $variation->get_sku(),
                    $keycrmProductId
                ));
                return array(
                    'success' => true,
                    'message' => __('Variation created successfully', 'keycrm')
                );
            } else {
                $errorMessage = $response->getRawResponse();
                return array(
                    'success' => false,
                    'message' => __('Failed to create variation: ', 'keycrm') . $errorMessage
                );
            }
        }

        /**
         * Check if products have already been imported
         *
         * @return bool
         */
        public function isProductsImported()
        {
            return (bool) get_option($this->importStatusKey, false);
        }

        /**
         * Get import status
         *
         * @return array
         */
        public function getImportStatus()
        {
            $importTime = get_option($this->importStatusKey);

            if (!$importTime) {
                return array(
                    'imported' => false,
                    'date' => null
                );
            }

            return array(
                'imported' => true,
                'date' => date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $importTime)
            );
        }
    }
}