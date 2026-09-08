<?php
/** Isolated request tests; no payment or receipt is created. */
define('ABSPATH', __DIR__);
function add_filter(...$args) {}
function wp_strip_all_tags($s) { return strip_tags($s); }
function wp_json_encode($v) { return json_encode($v, JSON_UNESCAPED_UNICODE); }
class TestItem {
    function __construct(public string $name, public float $total, public float $tax=0, public float $qty=1) {}
    function get_name(){return $this->name;}
    function get_quantity(){return $this->qty;}
    function get_total(){return $this->total;}
    function get_total_tax(){return $this->tax;}
}
class WC_Order {
    public array $fees=[];
    public float $shipping=0;
    public float $shippingTax=0;
    function __construct(public array $items,public float $total){}
    function get_items($type){return $type==='fee'?$this->fees:$this->items;}
    function get_total(){return $this->total;}
    function get_shipping_total(){return $this->shipping;}
    function get_shipping_tax(){return $this->shippingTax;}
}
require __DIR__.'/../wp-content/mu-plugins/maruderm-hutko-fiscal-items.php';
function check($v,$message){if(!$v)throw new RuntimeException($message);}
function build($order){
    $params=['order_id'=>'test-123','amount'=>(int)round($order->total*100),'currency'=>'UAH','reservation_data'=>base64_encode(json_encode(['cms_name'=>'Wordpress','products'=>[]]))];
    $result=(new Maruderm_Hutko_Fiscal_Items())->paymentParams($params,$order);
    check($result['order_id']==='test-123'&&$result['amount']===$params['amount']&&$result['currency']==='UAH','Payment identity and charge unchanged');
    $decoded=json_decode(base64_decode($result['reservation_data']),true);
    check($decoded['cms_name']==='Wordpress','Existing reservation metadata preserved');
    $total=0;foreach($decoded['products'] as $p){$unit=(int)round((float)$p['price']*100);$line=(int)round((float)$p['total_amount']*100);check($unit*$p['quantity']===$line,'Exact unit multiplication');$total+=$line;}
    check($total===$params['amount'],'Basket equals charge');
    return [$decoded['products'],$result];
}
$name='Інтенсивно зволожувальний крем для тіла з сечовиною 10% 400 мл';
[$p,$params]=build(new WC_Order([new TestItem($name,533.898305,96.101695)],630));
check($p[0]['name']===$name&&$p[0]['price']==='630.00','Real order tax-inclusive product');
check($params['order_desc']===$name,'Product name fallback');
$o=new WC_Order([new TestItem('Крем',100,20,3),new TestItem('Засіб',50,10,2)],210);$o->shipping=25;$o->shippingTax=5;build($o);
$o=new WC_Order([new TestItem('A',10,0,3)],10);[$p]=build($o);check(count($p)===2&&$p[0]['quantity']===2&&$p[0]['price']==='3.33'&&$p[1]['price']==='3.34','Split one-cent unit rounding');
$o=new WC_Order([new TestItem('A',10),new TestItem('B',20)],26);$o->fees=[new TestItem('Discount',-5),new TestItem('Packaging',1)];build($o);
$o=new WC_Order([new TestItem('A &amp; B',0),new TestItem('C',10)],10);[$p]=build($o);check($p[0]['name']==='A & B','Decoded names/free item preserved');
foreach([new WC_Order([new TestItem('A',10)],11),new WC_Order([new TestItem('',10)],10),new WC_Order([new TestItem('A',10,0,1.5)],10)] as $bad){$rejected=false;try{build($bad);}catch(RuntimeException $e){$rejected=true;}check($rejected,'Invalid/unbalanced basket rejected');}
echo "PASS: tax-inclusive real order, names, multi-item quantities, shipping, discounts/fees, rounding, free items, malformed basket guards\n";
