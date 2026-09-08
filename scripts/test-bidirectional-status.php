<?php
/** Isolated status transport tests: no WordPress database or external API. */
define('ABSPATH', __DIR__);
$options = ['woocommerce_integration-keycrm_settings' => ['api_key' => 'fixture', 'webhook_secret_key' => 'fixture']];
$calls = []; $fail = false; $wp_filter = [];
class WP_Hook { public array $callbacks = []; }
function add_action($name, $callback, $priority = 10, $args = 1) { $GLOBALS['wp_filter'][$name] ??= new WP_Hook(); $GLOBALS['wp_filter'][$name]->callbacks[$priority][] = ['function' => $callback, 'accepted_args' => $args]; }
function remove_action($name, $callback, $priority) { foreach ($GLOBALS['wp_filter'][$name]->callbacks[$priority] ?? [] as $key => $entry) if ($entry['function'] === $callback) unset($GLOBALS['wp_filter'][$name]->callbacks[$priority][$key]); }
function apply_filters($name, $value) { return $value; }
function get_option($key, $default = false) { return $GLOBALS['options'][$key] ?? $default; }
function add_option($key, $value, $deprecated, $autoload) { if (isset($GLOBALS['options'][$key])) return false; $GLOBALS['options'][$key] = $value; return true; }
function wp_generate_uuid4() { return uniqid(); }
function wp_cache_delete($key, $group) {}
function wp_json_encode($value) { return json_encode($value); }
function absint($value) { return abs((int) $value); }
function wc_get_order($id) { return $GLOBALS['order']->get_id() === $id ? $GLOBALS['order'] : null; }
function wc_get_orders($args) { return [1]; }
function wc_get_logger() { return new class { public function warning($message, $context) {} public function log($level, $message, $context) {} }; }
class WP_Error { public function __construct(public string $code, public string $message, public array $data = []) {} }
class WP_REST_Response { public function __construct(public array $data, public int $status = 200) {} }
class WP_REST_Request {
    public function __construct(public array $data) {}
    public function get_body() { return json_encode($this->data); }
    public function get_json_params() { return $this->data; }
    public function get_header($name) { return 'fixture'; }
    public function get_param($name) { return null; }
}
function is_wp_error($value) { return $value instanceof WP_Error; }
function wp_remote_retrieve_response_code($r) { return $r['code']; }
function wp_remote_retrieve_body($r) { return json_encode($r['data']); }
function wp_remote_get($url, $args) { $args['method'] = 'GET'; return wp_remote_request($url, $args); }
function wp_remote_request($url, $args) {
    (new ReflectionProperty(Maruderm_KeyCRM_Order_Status_Sync::class, 'lastRequest'))->setValue(null, 0);
    $GLOBALS['calls'][] = [$args['method'], $url, json_decode($args['body'] ?? '{}', true)];
    if ($GLOBALS['fail']) return ['code' => 503, 'data' => []];
    if ($args['method'] === 'GET' && isset($GLOBALS['afterRead'])) {
        $callback = $GLOBALS['afterRead']; unset($GLOBALS['afterRead']); $callback();
    }
    if ($args['method'] === 'PUT') {
        $body = json_decode($args['body'], true);
        check(array_keys($body) === ['status_id'], 'Only order stage may be written');
        $GLOBALS['remote']['status']['id'] = $body['status_id'];
    }
    return ['code' => 200, 'data' => $GLOBALS['remote']];
}
$wpdb = new class {
    public string $options = 'options';
    public function prepare($sql, ...$args) { return $args; }
    public function query($args) { [$key, $token] = $args; if (($GLOBALS['options'][$key] ?? null) === $token) unset($GLOBALS['options'][$key]); }
};
class WC_Order {
    public array $meta = ['_keycrm_order_id' => 7]; public array $notes = [];
    public function __construct(public string $status) {}
    public function get_id() { return 1; }
    public function get_status() { return $this->status; }
    public function get_meta($key) { return $this->meta[$key] ?? ''; }
    public function update_meta_data($key, $value) { $this->meta[$key] = $value; }
    public function delete_meta_data($key) { unset($this->meta[$key]); }
    public function save_meta_data() {}
    public function add_order_note($note) { $this->notes[] = $note; }
    public function update_status($status, $note, $manual) {
        $old = $this->status; $this->status = $status;
        $GLOBALS['sync']->changed(1, $old, $status, $this);
    }
}
class Maruderm_KeyCRM_Status_Config {
    public static function instance() { return new self(); }
    public function mappings() {
        $slugs = [1=>'keycrm-new',2=>'confirmed',4=>'wait-prepayment',8=>'ttn-created',9=>'ready-to-send',10=>'departing',12=>'keycrm-12',19=>'keycrm-19',20=>'keycrm-20'];
        $result=[]; foreach ($slugs as $id=>$slug) $result[$id]=['slug'=>$slug,'include'=>true,'fallback'=>$id===12?'completed':($id===19?'cancelled':'processing')];return $result;
    }
    public function target_status($id, $group) { return $this->mappings()[$id]['slug'] ?? ''; }
}
function wc_get_order_statuses() { $result=[];foreach(Maruderm_KeyCRM_Status_Config::instance()->mappings() as $m)$result['wc-'.$m['slug']]=$m['slug'];return $result; }
function check($ok, $message) { if (!$ok) throw new RuntimeException($message); }
require __DIR__.'/../wp-content/mu-plugins/keycrm-order-status-sync.php';
require __DIR__.'/../wp-content/mu-plugins/keycrm-order-status-webhook.php';
$sync = new Maruderm_KeyCRM_Order_Status_Sync();
$handler = new Maruderm_KeyCRM_Order_Status_Webhook(Maruderm_KeyCRM_Status_Config::instance());
function resetCase($status = 'processing', $remoteStatus = 1) {
    $GLOBALS['order'] = new WC_Order($status); $GLOBALS['calls'] = []; $GLOBALS['fail'] = false;
    $GLOBALS['remote']=['id'=>7,'source_id'=>2,'source_uuid'=>'1','status'=>['id'=>$remoteStatus,'group_id'=>2]];
}
function inbound() { return $GLOBALS['handler']->handle_status_request(new WP_REST_Request(['event'=>'order.change_order_status','context'=>['id'=>7,'source_uuid'=>'1','status_group_id'=>2]])); }
foreach(Maruderm_KeyCRM_Status_Config::instance()->mappings() as $id=>$m) {
    resetCase($m['slug']); $sync->changed(1,'old',$m['slug'],$order);
    check($remote['status']['id']===$id, 'Outbound custom stage '.$id);
    check($order->get_meta(Maruderm_KeyCRM_Order_Status_Sync::PENDING)==='', 'Successful intent cleared');
    resetCase('pending',$id); $r=inbound();
    check(!is_wp_error($r), 'Inbound custom stage '.$id);
    check(Maruderm_KeyCRM_Order_Status_Sync::target($order->get_status())===$id, 'Inbound state matches');
    check(count(array_filter($calls,fn($c)=>$c[0]==='PUT'))===0, 'Inbound must not echo outbound');
}
foreach(['pending'=>1,'processing'=>2,'on-hold'=>4,'completed'=>12,'cancelled'=>19] as $slug=>$id) {
    resetCase($slug);$sync->changed(1,'old',$slug,$order);check($remote['status']['id']===$id,'Native alias '.$slug);
}
foreach(['failed','refunded','checkout-draft'] as $slug) {
    resetCase($slug);$sync->changed(1,'old',$slug,$order);check(!$calls,'Unmapped stage must not guess');
}
resetCase('cancelled',1);$r=inbound();check(is_wp_error($r)&&$order->get_status()==='cancelled','Inbound cannot resurrect cancelled order');
resetCase('cancelled',19);$r=inbound();check(!is_wp_error($r)&&$order->get_status()==='cancelled','Equivalent terminal alias preserved');
resetCase('ready-to-send',8);$order->meta[Maruderm_KeyCRM_Order_Status_Sync::PENDING]='ready-to-send';$r=inbound();check(is_wp_error($r),'Pending local intent wins over stale incoming status');
resetCase();$fail=true;$r=inbound();check(is_wp_error($r)&&$order->get_status()==='processing','No status-group fallback on API failure');
resetCase();$remote['source_id']=99;$r=inbound();check(is_wp_error($r),'Inbound source mismatch');
resetCase('cancelled');$remote['source_uuid']='99';$sync->changed(1,'pending','cancelled',$order);check(count($calls)===1,'Outbound source mismatch must not write');
resetCase('cancelled');$fail=true;$sync->changed(1,'pending','cancelled',$order);check($order->get_meta(Maruderm_KeyCRM_Order_Status_Sync::PENDING)==='cancelled','Failed attempt keeps intent');
$fail=false;$sync->retryPending();check($remote['status']['id']===19&&$order->get_meta(Maruderm_KeyCRM_Order_Status_Sync::PENDING)==='','Retry converges');
resetCase('processing',12);$remote['status']['group_id']=5;$sync->changed(1,'pending','processing',$order);check(count($calls)===1,'No automatic reopening');
resetCase('confirmed');$GLOBALS['afterRead']=function(){ $GLOBALS['order']->status='cancelled';$GLOBALS['order']->meta[Maruderm_KeyCRM_Order_Status_Sync::PENDING]='cancelled'; };
$sync->changed(1,'pending','confirmed',$order);check(count($calls)===1,'Concurrent newer change prevents stale PUT');$sync->retryPending();check($remote['status']['id']===19,'Newest intent wins on retry');
resetCase();$token=Maruderm_KeyCRM_Order_Status_Sync::lock(1);check(Maruderm_KeyCRM_Order_Status_Sync::lock(1)==='','Concurrent lock rejected');Maruderm_KeyCRM_Order_Status_Sync::unlock(1,'wrong-token');check(Maruderm_KeyCRM_Order_Status_Sync::lock(1)==='','Wrong owner cannot release lock');Maruderm_KeyCRM_Order_Status_Sync::unlock(1,$token);
class WC_Keycrm_Base { public function update_order_status() {} }
class Maruderm_KeyCRM_Order_Payment_Sync { public function handle_status_change() {} public function handle_payment_complete() {} }
$vendor=new WC_Keycrm_Base();$payment=new Maruderm_KeyCRM_Order_Payment_Sync();
add_action('woocommerce_order_status_changed',[$vendor,'update_order_status'],11,4);add_action('woocommerce_order_status_changed',[$payment,'handle_status_change'],20,4);add_action('woocommerce_payment_complete',[$payment,'handle_payment_complete'],20);
$sync->separatePayments();check(empty($wp_filter['woocommerce_order_status_changed']->callbacks[11])&&empty($wp_filter['woocommerce_order_status_changed']->callbacks[20]),'Stage callbacks cannot change payment');check(!empty($wp_filter['woocommerce_payment_complete']->callbacks[20]),'Real payment completion remains');
echo "PASS: nine stages both directions, native aliases, unmapped statuses, loop/identity/terminal guards, retries, locks, payment isolation\n";
