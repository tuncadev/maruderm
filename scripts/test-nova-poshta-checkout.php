<?php
namespace kirillbdev\WCUkrShipping\DB\Repositories {
    class CityRepository {
        public function getCityByRef($ref) {
            return $ref === 'city' ? (object) ['description' => 'Мерефа', 'area_ref' => '7150813b-9b87-11de-822f-000c2965ae0e'] : null;
        }
    }
    class WarehouseRepository {
        public function getWarehouseByRef($ref) {
            return $ref === 'locker' ? (object) ['city_ref' => 'city', 'description' => 'Поштомат №123'] : null;
        }
    }
}
namespace Automattic\WooCommerce\StoreApi\Exceptions {
    class RouteException extends \RuntimeException {
        public function __construct($code, $message, $status) { parent::__construct($message, $status); }
    }
}
namespace {
    define('ABSPATH', __DIR__);
    $hooks = [];
    function add_action($name, $callback, $priority, $args) { $GLOBALS['hooks'][$name] = [$callback, $priority, $args]; }
    function add_filter($name, $callback) {}
    function WC() { return $GLOBALS['wc']; }
    class WC_Order_Item_Shipping {
        public array $meta = ['wcus_street_ref' => 'old-courier'];
        public bool $saved = false;
        public function __construct(private string $method = 'nova_poshta_shipping') {}
        public function get_method_id() { return $this->method; }
        public function delete_meta_data($key) { unset($this->meta[$key]); }
        public function update_meta_data($key, $value) { $this->meta[$key] = $value; }
        public function save() { $this->saved = true; }
    }
    class WC_Order {
        public array $fields = ['shipping_state' => 'UA51', 'shipping_postcode' => '65000', 'status' => 'pending', 'payment' => 'untouched'];
        public function __construct(public WC_Order_Item_Shipping $item) {}
        public function get_items($type) { return [$this->item]; }
        public function update_meta_data($key, $value) { $this->fields[$key] = $value; }
        public function __call($method, $args) { $this->fields[substr($method, 4)] = $args[0]; }
    }
    class WP_REST_Request {
        public function __construct(private array $data) {}
        public function get_param($key) { return $this->data[$key] ?? null; }
    }
    function check($condition, $message) { if (!$condition) throw new \RuntimeException($message); }
    require __DIR__ . '/../wp-content/mu-plugins/maruderm-nova-poshta/class-checkout.php';
    $adapter = new \Maruderm_Nova_Poshta_Checkout();
    $GLOBALS['wc'] = (object) [
        'session' => new class { public array $rates = ['nova_poshta_shipping:1']; public function get($key, $fallback) { return $this->rates; } },
        'countries' => new class { public array $locale = ['UA' => ['postcode' => ['required' => true]]]; public function get_country_locale() { return $this->locale; } },
    ];
    $adapter->register();
    check(isset($hooks['woocommerce_store_api_checkout_update_order_from_request']), 'Must persist before processed hook');
    $data = ['payment_data' => [
        ['key' => 'maruderm_np_city_ref', 'value' => 'city'],
        ['key' => 'maruderm_np_warehouse_ref', 'value' => 'locker'],
    ]];
    $order = new WC_Order(new WC_Order_Item_Shipping());
    $adapter->updateOrder($order, new WP_REST_Request($data));
    check($order->item->saved && $order->item->meta['wcus_warehouse_ref'] === 'locker', 'CRM must see persisted warehouse');
    check(!isset($order->item->meta['wcus_street_ref']), 'Stale courier fields removed');
    check($order->fields['shipping_city'] === 'Мерефа' && $order->fields['shipping_state'] === 'UA63', 'Carrier canonical city and region used');
    check($order->fields['billing_postcode'] === '' && $order->fields['shipping_postcode'] === '', 'No fabricated postcode');
    check($order->fields['status'] === 'pending' && $order->fields['payment'] === 'untouched', 'Status/payment preserved');
    check($GLOBALS['wc']->countries->locale['UA']['postcode']['required'] === false, 'Previously cached postcode validation updated');
    foreach (['wrong-city', 'missing-warehouse', 'pickup'] as $scenario) {
        $invalid = $data;
        if ($scenario === 'wrong-city') $invalid['payment_data'][0]['value'] = 'other';
        if ($scenario === 'missing-warehouse') array_pop($invalid['payment_data']);
        $candidate = new WC_Order(new WC_Order_Item_Shipping($scenario === 'pickup' ? 'local_pickup' : 'nova_poshta_shipping'));
        try {
            $adapter->updateOrder($candidate, new WP_REST_Request($invalid));
            throw new \RuntimeException('Invalid selection accepted');
        } catch (\Automattic\WooCommerce\StoreApi\Exceptions\RouteException $error) {
            check(!$candidate->item->saved, 'Rejected selection must not write shipping item');
        }
    }
    $legacy = new WC_Order(new WC_Order_Item_Shipping());
    $adapter->updateOrder($legacy, new WP_REST_Request([]));
    check(!$legacy->item->saved, 'Older checkout stays compatible during deployment');
    $GLOBALS['wc'] = (object) ['session' => new class { public array $rates = ['nova_poshta_shipping:1']; public function get($key, $fallback) { return $this->rates; } }];
    check($adapter->postcodeOptional(['UA' => ['postcode' => ['required' => true]]])['UA']['postcode']['required'] === false, 'Pickup postcode optional');
    $GLOBALS['wc']->session->rates = ['local_pickup:2'];
    check($adapter->postcodeOptional(['UA' => ['postcode' => ['required' => true]]])['UA']['postcode']['required'] === true, 'Other delivery validation preserved');
    echo "PASS: checkout persistence, canonical address, rejection, compatibility, payment preservation, postcode scope\n";
}
