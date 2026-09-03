<?php
/**
 * KeyCRM Integration.
 *
 * @package  WC_Keycrm_Base
 * @category Integration
 * @author   KeyCRM
 */

if (!class_exists('WC_Keycrm_Base')) {
    if (!class_exists('WC_Keycrm_Abstracts_Settings')) {
        include_once(WC_Integration_Keycrm::checkCustomFile('include/abstracts/class-wc-keycrm-abstracts-settings.php'));
    }

    /**
     * Class WC_Keycrm_Base
     */
    class WC_Keycrm_Base extends WC_Keycrm_Abstracts_Settings
    {
        /** @var string */
        protected $api_url;

        /** @var string */
        protected $api_key;

        /** @var \WC_Keycrm_Proxy|WC_Keycrm_Client_V6|bool */
        protected $apiClient;

        /** @var mixed */
        protected $order_item;

        /** @var mixed */
        protected $order_address;

        /** @var \WC_Keycrm_Customers */
        protected $customers;

        /** @var \WC_Keycrm_Orders */
        protected $orders;
        /**
         * @var WC_Keycrm_Forms
         */
        private $forms;

        /** @var \WC_Keycrm_Products */
        protected $products;

        /**
         * Init and hook in the integration.
         * @param \WC_Keycrm_Proxy|WC_Keycrm_Client_V6|bool $keycrm (default = false)
         */
        public function __construct($keycrm = false) {
            parent::__construct();

            if (!class_exists( 'WC_Keycrm_Proxy')) {
                include_once(WC_Integration_Keycrm::checkCustomFile('include/api/class-wc-keycrm-proxy.php'));
            }

            if ($keycrm === false) {
                $this->apiClient = $this->getApiClient();
            } else {
                $this->apiClient = $keycrm;
            }

            $this->customers = new WC_Keycrm_Customers(
                $this->apiClient,
                $this->settings,
                new WC_Keycrm_Customer_Address
            );

            $this->orders = new WC_Keycrm_Orders(
                $this->apiClient,
                $this->settings,
                new WC_Keycrm_Order_Item($this->settings),
                new WC_Keycrm_Order_Address,
                $this->customers,
                new WC_Keycrm_Order($this->settings),
                new WC_Keycrm_Order_Payment($this->settings)
            );


            $this->products = new WC_Keycrm_Products($this->apiClient, $this->settings);
            $this->forms = new WC_Keycrm_Forms($this->apiClient, $this->settings);
            $this->forms->init_hooks();

            // Actions.
            add_action('woocommerce_update_options_integration_' .  $this->id, array($this, 'process_admin_options'));
            add_filter('woocommerce_settings_api_sanitized_fields_' . $this->id, array($this, 'api_sanitized'));
            add_action('admin_bar_menu', array($this, 'add_keycrm_button'), 100 );
            add_action('woocommerce_checkout_order_processed', array($this, 'keycrm_process_order'), 10, 1);
            add_action('woocommerce_store_api_checkout_order_processed', array($this, 'keycrm_process_store_api_order'), 10, 1);
            add_action('keycrm_history', array($this, 'keycrm_history_get'));
            add_action('keycrm_icml', array($this, 'generate_icml'));
            add_action('keycrm_inventories', array($this, 'load_stocks'));
            add_action('wp_ajax_do_upload', array($this, 'upload_to_crm'));
            add_action('wp_ajax_generate_icml', array($this, 'generate_icml'));
            add_action('wp_ajax_order_upload', array($this, 'order_upload'));
            add_action('wp_ajax_import_products', array($this, 'import_products'));
            add_action('admin_print_footer_scripts', array($this, 'ajax_upload'), 99);
            add_action('admin_print_footer_scripts', array($this, 'ajax_generate_icml'), 99);
            add_action('admin_print_footer_scripts', array($this, 'ajax_selected_order'), 99);
            add_action('admin_print_footer_scripts', array($this, 'ajax_import_products'), 99);
            add_action('woocommerce_order_status_changed', array($this, 'update_order_status'), 11, 4);

            add_action('woocommerce_update_product', array($this, 'handle_product_update'), 10, 2);
            add_action('woocommerce_update_product_variation', array($this, 'handle_variation_save'), 20, 2);

            if (!$this->get_option('deactivate_update_order')
                || $this->get_option('deactivate_update_order') == static::NO
            ) {
                add_action('woocommerce_process_shop_order_meta', array($this, 'update_order'), 11, 1);
                add_action('wp_ajax_woocommerce_save_order_items', array($this, 'update_order'), 11, 1);
            }
            add_filter('keycrm_shipping_data', array($this, 'keycrm_handle_shipping_data'), 10, 3);

            // Deactivate hook
            add_action('keycrm_deactivate', array($this, 'deactivate'));
        }

        /**
         * Handle product update
         *
         * @param int $product_id
         * @param WC_Product $product
         */
        public function handle_product_update($product_id, $product = null)
        {
            // Skip auto-drafts
            if (get_post_status($product_id) === 'auto-draft') {
                return;
            }

            if (!$product) {
                $product = wc_get_product($product_id);
            }

            if (!$product) {
                return;
            }

            // Skip products with empty names or auto-draft names
            if (empty($product->get_name()) || $product->get_name() === 'AUTO-DRAFT') {
                return;
            }

            // Skip trashed products
            if ($product->get_status() === 'trash') {
                return;
            }

            $existingKeycrmId = get_post_meta($product_id, '_keycrm_product_id', true);

            if (!$existingKeycrmId && $product->get_status() === 'publish') {
                $this->create_product_in_keycrm($product_id, $product);
            }
//             else if ($existingKeycrmId) {
//                  $this->update_product_in_keycrm($product_id, $product);
//             }
        }

        /**
         * Handle variation save
         *
         * @param int $variation_id Variation ID
         * @param int $product_id   Parent product ID
         */
        public function handle_variation_save($variation_id, $product)
        {
            // Use transient to prevent duplicate calls within 3 seconds
            $transient_key = 'keycrm_variation_update_' . $variation_id;
            if (get_transient($transient_key)) {
                return;
            }

            // Set transient for 3 seconds
            set_transient($transient_key, true, 3);

            // Handle the variation update
            $this->update_product_variation_in_keycrm($variation_id, $product->get_parent_id());
        }

        /**
         * Process admin options
         */
        public function process_admin_options() {
            parent::process_admin_options();

            $this->apiClient = $this->getApiClient();
            $this->init_settings_fields();
        }

        /**
         * Init settings fields
         */
        public function init_settings_fields()
        {
            $this->init_form_fields();
            $this->init_settings();
        }

         /**
         * @param $settings
         *
         * @return array
         */
        public function api_sanitized($settings)
        {
            if (isset($settings['sync']) && $settings['sync'] == static::YES) {
                if (!wp_next_scheduled('keycrm_inventories')) {
                    wp_schedule_event(time(), 'fiveteen_minutes', 'keycrm_inventories');
                }
            } elseif (isset($settings['sync']) && $settings['sync'] == static::NO) {
                wp_clear_scheduled_hook('keycrm_inventories');
            }

            if (isset($settings['history']) && $settings['history'] == static::YES) {
                if (!wp_next_scheduled('keycrm_history')) {
                    wp_schedule_event(time(), 'five_minutes', 'keycrm_history');
                }
            } elseif (isset($settings['history']) && $settings['history'] == static::NO) {
                wp_clear_scheduled_hook('keycrm_history');
            }

            if (isset($settings['icml']) && $settings['icml'] == static::YES) {
                if (!wp_next_scheduled('keycrm_icml')) {
                    wp_schedule_event(time(), 'three_hours', 'keycrm_icml');
                }
            } elseif (isset($settings['icml']) && $settings['icml'] == static::NO) {
                wp_clear_scheduled_hook('keycrm_icml');
            }

            if (!$this->get_errors() && !get_option('keycrm_active_in_crm')) {
                $this->activate_integration($settings);
            }

            return $settings;
        }

        public function generate_icml() {
            /*
             * A temporary solution.
             * We have rebranded the module and changed the name of the ICML file.
             * This solution checks the url specified to the ICML file and updates it if necessary.
             */

            $codeSite   = '';
            $infoApiKey = $this->apiClient->credentials();

            if (empty($infoApiKey) === false && $infoApiKey->isSuccessful() === true) {
                if (empty($infoApiKey['siteAccess']) === false && $infoApiKey['siteAccess'] === 'access_selective') {
                    if (empty($infoApiKey['sitesAvailable']) === false && count($infoApiKey['sitesAvailable']) === 1) {
                        $codeSite = $infoApiKey['sitesAvailable'][0];
                    }
                }
            }

            if (empty($codeSite) === false) {
                $getSites = $this->apiClient->sitesList();

                if (empty($getSites['sites']) === false && $getSites->isSuccessful() === true) {
                    if(empty($getSites['sites'][$codeSite]) === false) {
                        $dataSite = $getSites['sites'][$codeSite];

                        if (empty($dataSite['ymlUrl']) === false) {
                            $ymlUrl = $dataSite['ymlUrl'];

                            if (strpos($ymlUrl, 'simla') === false) {
	                            $ymlUrl = str_replace('/keycrm.xml', '/simla.xml', $ymlUrl);
                                $dataSite['ymlUrl'] = $ymlUrl;

                                $this->apiClient->sitesEdit($dataSite);
                            }
                        }
                    }
                }
            }

            $keyCrmIcml = new WC_Keycrm_Icml();
            $keyCrmIcml->generate();

        }


        /**
         * Get history
         */
        public function keycrm_history_get() {
            $keycrm_history = new WC_Keycrm_History($this->apiClient);
            $keycrm_history->getHistory();
        }

        /**
         * @param int $order_id
         * @throws Exception
         */
        public function keycrm_process_order($order_id) {
            $this->orders->orderCreate($order_id);
        }

        /**
         * Send orders created by the Checkout Block or Store API to KeyCRM.
         *
         * @param WC_Order $order Processed WooCommerce order.
         * @throws Exception
         */
        public function keycrm_process_store_api_order($order) {
            if (!$order instanceof WC_Order || $order->get_meta('_keycrm_order_id')) {
                return;
            }

            $this->keycrm_process_order($order->get_id());
        }

        /**
         * Load stock from keyCRM
         */
        public function load_stocks() {
            $inventories = new WC_Keycrm_Inventories($this->apiClient);
            $inventories->updateQuantity();
        }

        /**
         * Upload selected orders
         */
        public function order_upload() {
            $ids = false;

            if (isset($_GET['order_ids_keycrm'])) {
                $appendix = array();
                $ids = explode(',', $_GET['order_ids_keycrm']);

                foreach ($ids as $key => $id) {
                    if (stripos($id, '-') !== false) {
                        $idSplit = explode('-', $id);

                        if (count($idSplit) == 2) {
                            $expanded = array();
                            $first = (int) $idSplit[0];
                            $last = (int) $idSplit[1];

                            for ($i = $first; $i <= $last; $i++) {
                                $expanded[] = $i;
                            }

                            $appendix = array_merge($appendix, $expanded);
                            unset($ids[$key]);
                        }
                    }
                }

                $ids = array_unique(array_merge($ids, $appendix));
            }

            if ($ids) {
                $this->orders->ordersUpload($ids);
            }
        }

        /**
         * AJAX handler for products import
         */
        public function import_products()
        {
            // Check nonce for security
            if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'keycrm_import_products')) {
                wp_send_json(array(
                        'success' => false,
                        'message' => __('Security check failed.', 'keycrm')
                ));
                return;
            }

            // Check user permissions
            if (!current_user_can('manage_woocommerce')) {
                wp_send_json(array(
                        'success' => false,
                        'message' => __('You do not have sufficient permissions to access this page.', 'keycrm')
                ));
                return;
            }

            try {
                $result = $this->products->importAllProducts();

                wp_send_json($result);
            } catch (Exception $e) {
                wp_send_json(array(
                        'success' => false,
                        'message' => $e->getMessage(),
                        'errors' => array($e->getMessage())
                ));
            }
        }


        /**
         * AJAX script for products import
         */
        public function ajax_import_products()
        {
            // Check if we are on the integration settings page
            if (!function_exists('get_current_screen')) {
                return;
            }

            $screen = get_current_screen();
            if (!$screen || $screen->id !== 'woocommerce_page_wc-settings') {
                return;
            }

            if (!isset($_GET['tab']) || $_GET['tab'] !== 'integration' || !isset($_GET['section']) || $_GET['section'] !== 'integration-keycrm') {
                return;
            }

            $ajax_url = admin_url('admin-ajax.php');
            ?>
            <script type="text/javascript">
                jQuery(document).ready(function($) {
                    $('#import-products-keycrm').on('click', function(e) {
                        e.preventDefault();

                        if (!confirm('<?php echo esc_js(__('Are you sure you want to import all products to KeyCRM? This action can only be performed once.', 'keycrm')); ?>')) {
                            return;
                        }

                        var $button = $(this);
                        $button.prop('disabled', true).addClass('disabled');

                        $.ajax({
                            type: "POST",
                            url: '<?php echo esc_url($ajax_url); ?>',
                            data: {
                                action: 'import_products',
                                _wpnonce: '<?php echo wp_create_nonce("keycrm_import_products"); ?>'
                            },
                            dataType: 'json',
                            success: function(response) {
                                $button.prop('disabled', false).removeClass('disabled');

                                if (response.success) {
                                    alert('<?php echo esc_js(__('Import completed successfully!', 'keycrm')); ?>');
                                    window.location.reload();
                                } else {
                                    alert('<?php echo esc_js(__('Import failed:', 'keycrm')); ?> ' + response.message);
                                }
                            },
                            error: function() {
                                $button.prop('disabled', false).removeClass('disabled');
                                alert('<?php echo esc_js(__('AJAX request failed.', 'keycrm')); ?>');
                            }
                        });
                    });
                });
            </script>
            <?php
        }

        /**
         * Upload archive customers and order to keyCRM
         */
        public function upload_to_crm()
        {
            $options = array_filter(get_option(static::$option_key));

            // $this->customers->customersUpload();
            $this->orders->ordersUpload();

            $options['uploads'] = static::YES;
            update_option(static::$option_key, $options);
        }

        /**
         * Create customer in keyCRM
         *
         * @param int $customer_id
         *
         * @return void
         * @throws \Exception
         */
        public function create_customer($customer_id)
        {
            if (WC_Keycrm_Plugin::history_running() === true) {
                return;
            }

	        $client = $this->getApiClient();

	        if (empty($client)) {
		        return;
	        }

	        $wcCustomer = new WC_Customer($customer_id);
	        $email = $wcCustomer->get_billing_email();

	        if (empty($email)) {
	            $email = $wcCustomer->get_email();
            }

	        if (empty($email)) {
	            return;
            } else {
	            $wcCustomer->set_billing_email($email);
	            $wcCustomer->save();
            }

	        $response = $client->customersList(array('email' => $email));

	        if (!empty($response)
                && $response->isSuccessful()
                && isset($response['customers'])
                && count($response['customers']) > 0
            ) {
		        $customers = $response['customers'];
		        $customer = reset($customers);

		        if (isset($customer['id'])) {
		            $this->customers->updateCustomerById($customer_id, $customer['id']);
		            $builder = new WC_Keycrm_WC_Customer_Builder();
		            $builder
                        ->setWcCustomer($wcCustomer)
                        ->setPhones(isset($customer['phones']) ? $customer['phones'] : array())
                        ->setAddress(isset($customer['address']) ? $customer['address'] : false)
                        ->build()
                        ->getResult()
                        ->save();
                }
	        } else {
                $this->customers->createCustomer($customer_id);
            }
        }

        /**
         * Edit customer in keyCRM
         * @param int $customer_id
         * @throws Exception
         */
        public function update_customer($customer_id)
        {
            if (WC_Keycrm_Plugin::history_running() === true) {
                return;
            }

            if (empty($customer_id)) {
                return;
            }

            $this->customers->updateCustomer($customer_id);
        }

        /**
         * Update product in KeyCRM when updated in WooCommerce
         *
         * @param int $product_id
         * @param WC_Product $product
         */
        public function update_product_in_keycrm($product_id, $product)
        {
            if (!$product) {
                $product = wc_get_product($product_id);
            }

            if (!$product) {
                return;
            }

            if (empty($product->get_name()) || $product->get_name() === 'AUTO-DRAFT') {
                return;
            }

            $result = $this->products->updateProductInKeycrm($product_id, $product);

            if (!$result['success']) {
                WC_Keycrm_Logger::add(sprintf('Failed to update product in KeyCRM: %s', $result['message']));
            }
        }

        /**
         * Update product variation in KeyCRM
         *
         * @param int $variation_id
         * @param int $product_id
         */
        public function update_product_variation_in_keycrm($variation_id, $product_id)
        {
            $result = $this->products->createVariationInKeycrm($variation_id, $product_id);

            if (!$result['success']) {
                WC_Keycrm_Logger::add(sprintf('Failed to update variation in KeyCRM: %s', $result['message']));
            }
        }

        /**
         * Create product in KeyCRM when created in WooCommerce
         *
         * @param int $product_id
         * @param WC_Product $product
         */
        public function create_product_in_keycrm($product_id, $product)
        {
            if (!$product) {
                $product = wc_get_product($product_id);
            }

            if (!$product) {
                return;
            }

            // Skip products with empty names
            if (empty($product->get_name()) || $product->get_name() === 'AUTO-DRAFT') {
                return;
            }

            $result = $this->products->createProductInKeycrm($product_id, $product);

            if (!$result['success']) {
                WC_Keycrm_Logger::add(sprintf('Failed to create product in KeyCRM: %s', $result['message']));
            }
        }

        /**
         * Create order in keyCRM from admin panel
         *
         * @param int $order_id
         * @throws Exception
         */
        public function create_order($order_id)
        {
            if (is_admin()) {
                $this->keycrm_process_order($order_id);
            }
        }

        /**
         * Edit order in keyCRM
         *
         * @param int $order_id
         *
         * @throws \Exception
         */
        public function update_order($order_id) {
            if (WC_Keycrm_Plugin::history_running() === true) {
                return;
            }

            $wcOrder = wc_get_order($order_id);

            if (in_array($wcOrder->get_status(), ['pending', 'on-hold'])) {
                return;
            }

            $this->orders->updateOrder($order_id);

        }

        /**
         * @throws Exception
         */
        public function update_order_status($order_id, $old_status, $new_status, $order)
        {
            if ($old_status == $new_status)
                return;

            $this->orders->updateOrderStatusAndPayment($order);
        }

        /**
        * Get keycrm api client
        *
        * @return bool|WC_Keycrm_Proxy|\WC_Keycrm_Client_V6
        */
        public function getApiClient()
        {
            if ($this->get_option('api_url') && $this->get_option('api_key')) {
                return new WC_Keycrm_Proxy(
                    $this->get_option('api_url'),
                    $this->get_option('api_key'),
                    $this->get_option('corporate_enabled', 'no') === 'yes'
                );
            }

            return false;
        }

        /**
         *
         * Shipping data override by plugin
         *
         * @param $shipping_data
         * @param $order
         * @param $wc_order
         * @return mixed
         */
        function keycrm_handle_shipping_data($shipping_data, $wc_order) {
            $shipping_manager = Keycrm_Shipping_Manager::get_instance();
            $custom_data = $shipping_manager->get_shipping_data($wc_order);

            return array_merge($shipping_data, $custom_data);
        }

        /**
         * Deactivate module in marketplace keyCRM
         *
         * @return void
         */
        public function deactivate()
        {
            $api_client = $this->getApiClient();
            $clientId = get_option('keycrm_client_id');

            WC_Keycrm_Plugin::integration_module($api_client, $clientId, false);
            delete_option('keycrm_active_in_crm');
        }

        /**
         * @param $settings
         *
         * @return void
         */
        private function activate_integration($settings)
        {
            $client_id = get_option('keycrm_client_id');

            if (!$client_id) {
                $client_id = uniqid();
            }

            if ($settings['api_url'] && $settings['api_key']) {
                $api_client = new WC_Keycrm_Proxy(
                    $settings['api_url'],
                    $settings['api_key'],
                    $settings['corporate_enabled'] === 'yes'
                );

                $result = WC_Keycrm_Plugin::integration_module($api_client, $client_id);

                if ($result) {
                    update_option('keycrm_active_in_crm', true);
                    update_option('keycrm_client_id', $client_id);
                }
            }
        }
    }
}
