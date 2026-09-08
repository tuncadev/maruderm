<?php
/** Isolated payment regressions: never calls a live API. */
define('ABSPATH', __DIR__);
$GLOBALS['calls']=[];
function add_action(...$args) {}
function wc_get_order($id) {return $GLOBALS['order'];}
function absint($v) {return abs((int)$v);}
function get_option(...$args) {return ['api_key'=>'test-only','cod'=>6,'hutko'=>2];}
function is_wp_error($v) {return false;}
function wp_json_encode($v) {return json_encode($v);}
function wp_remote_request($url,$args) {
    $GLOBALS['calls'][]=[$url,$args];
    return ['data'=>($args['method']==='GET'?['payments'=>[['id'=>8,'status'=>'not_paid']]]:[])];
}
function wp_remote_retrieve_response_code($v) {return 200;}
function wp_remote_retrieve_body($v) {return json_encode($v['data']);}
class WC_Order {
    public bool $paidStatus=true;
    public $datePaid=null;
    public string $method='cod';
    public array $meta=['_keycrm_order_id'=>9];
    function is_paid(){return $this->paidStatus;}
    function get_date_paid(){return $this->datePaid;}
    function get_meta($k){return $this->meta[$k]??'';}
    function get_payment_method(){return $this->method;}
    function update_meta_data($k,$v){$this->meta[$k]=$v;}
    function save_meta_data(){}
    function get_id(){return 6613;}
}
function check($v,$m){if(!$v)throw new RuntimeException($m);}
require __DIR__.'/../wp-content/mu-plugins/keycrm-order-payment-sync.php';
foreach(['processing','completed','ttn-created'] as $status){
 $GLOBALS['order']=new WC_Order();$GLOBALS['calls']=[];$sync=new Maruderm_KeyCRM_Order_Payment_Sync();
 $sync->handle_status_change(6613,'pending',$status,$GLOBALS['order']);
 $sync->handle_payment_complete(6613);
 check($GLOBALS['calls']===[],'Unpaid COD must never write payment: '.$status);
}
$GLOBALS['order']=new WC_Order();$GLOBALS['calls']=[];$sync=new Maruderm_KeyCRM_Order_Payment_Sync();
$sync->handle_status_change(6613,'pending','processing',$GLOBALS['order']);
$GLOBALS['order']->method='hutko';$GLOBALS['order']->datePaid=new DateTimeImmutable();
$sync->handle_payment_complete(6613);
check(count($GLOBALS['calls'])===2,'Real payment later in the same request still synchronizes');
check(json_decode($GLOBALS['calls'][1][1]['body'],true)===['status'=>'paid'],'Confirmed payment payload');
$sync->handle_payment_complete(6613);
check(count($GLOBALS['calls'])===2,'Confirmed payment is processed once');
echo "PASS: unpaid COD stages/completion skipped; recorded payment succeeds after earlier unpaid callback; deduplication\n";
