<?php

if(file_exists('custom-property-handler.php'))
    include 'custom-property-handler.php';

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * @param $path
 * @param $parameters
 * @return mixed
 */
function globalConvert_kcrm ($path, $parameters)
{
    switch ($path) {
        case '/order':
            $parameters['order'] = covertOrder_kcrm($parameters['order'], $parameters['source']);
            return $parameters;
            break;
        case '/order/payment':
                $parameters['payment'] = convertPayment_kcrm($parameters['payment']);
                $parameters['url'] = '/order/payment';
            return $parameters;
            break;
        case '/reference/delivery-types':
            $parameters['url'] = '/order/delivery-service';
            $parameters['limit'] = '50';
            $parameters['page'] = '1';
            return $parameters;
            break;
        case '/reference/payment-types':
            $parameters['url'] = '/order/payment-method';
            $parameters['limit'] = '50';
            $parameters['page'] = '1';
            return $parameters;
            break;
        case '/api-versions':
            $parameters['url'] = '/order/tag';
            $parameters['limit'] = '50';
            $parameters['page'] = '1';
            return $parameters;
            break;
        case '/reference/statuses':
            $parameters['url'] = '/order/status';
            $parameters['limit'] = '50';
            $parameters['page'] = '1';
            return $parameters;
            break;
        case '/reference/order-methods':
            $parameters['url'] = '/order/source';
            $parameters['filter'] = ['driver' => 'wordpress'];
            $parameters['limit'] = '50';
            $parameters['page'] = '1';
            return $parameters;
            break;
    }

    return $parameters;
}

/**
 * @param $payment
 * @param $wcOrder
 * @return array
 */
function convertPayment_kcrm($payment, $wcOrder = null)
{

    if ($wcOrder instanceof WC_Order) {
        $paymentStatus = getPaymentStatus_kcrm($wcOrder);
        $paymentDate = getPaymentDate_kcrm($wcOrder);
    } else {
        $paymentStatus = $payment['status'] === 'paid' ? 'paid' : 'not_paid';
        $paymentDate = $payment['paidAt'] ?: date('Y-m-d H:i:s');
    }

    $convertedPayment = [
        'payment_method_id' => (int)$payment['type'],
        'amount' => (float)$payment['amount'],
        'status' => $paymentStatus,
        'payment_date' => $paymentDate
    ];

    if (isset($payment['externalId'])) {
        $convertedPayment['source_uuid'] = $payment['externalId'];
    }
    return $convertedPayment;
}

/**
 * @param $order
 * @param $source
 * @return false|string
 */
function covertOrder_kcrm ($order, $source)
{
    global $woocommerce;

    $order = json_decode($order, JSON_OBJECT_AS_ARRAY);
    $wcOrder = wc_get_order($order['externalId']);
    $wcItems = [];
    foreach ($wcOrder->get_items() as $item_id => $item) {
        $wcItems[$item_id] = $item;
        // prevent combined properties for products with the same SKU
        $usedItems[$item_id] = false;
    }



    if (method_exists($wcOrder, 'get_coupon_codes')) {
        $applied_coupon = $wcOrder->get_coupon_codes();
    } else {
        $applied_coupon = $wcOrder->get_used_coupons();
    }
    $coupon_code = !empty($applied_coupon) ? reset($applied_coupon) : '';

    $address = !empty($wcOrder->get_shipping_address_1()) ? $wcOrder->get_shipping_address_1() : $wcOrder->get_billing_address_1();
    $address2 = $wcOrder->get_shipping_address_2();

    $kItems = [];
    $k_props = [];

    foreach ($order['items'] as $itemKey => $item) {
        $itemProduct = wc_get_product($item['offer']['externalId']);
        // WC_Keycrm_Logger::add(sprintf("itemProduct: \n\n%s\n", json_encode($itemProduct->get_data(), JSON_UNESCAPED_UNICODE)));

        $itemImage = false;
        if ($wcItems) {

            foreach ($usedItems as $i => $value) {
                $wcProduct = wc_get_product($wcItems[$i]->get_product());
                // WC_Keycrm_Logger::add(sprintf("wc_get_product: \n\n%s\n", json_encode($wcProduct->get_data(), JSON_UNESCAPED_UNICODE)));

                if($wcProduct && $wcProduct->get_id() == $item['offer']['externalId'] && !$usedItems[$i]){
                    $wcItemMetaData = $wcItems[$i]->get_meta_data();

                    if (!empty($wcItemMetaData)){
                        foreach ($wcItemMetaData as $attributeIndex => $attributeValue) {
                            $attributeValueData = $attributeValue->get_data();

                            if (is_string($attributeValueData['value'])) {
                                $k_props[] = [
                                    'name' => $attributeValueData['key'],
                                    'value' => $attributeValueData['value']
                                ];
                            } else if (function_exists('customPropertyHandler')) {
                                $k_props = array_merge($k_props, customPropertyHandler($attributeValueData));
                            } else {
                                WC_Keycrm_Logger::add(sprintf("WARNING! Only STRING AttributeValueData is supported. Given: \n\n%s", json_encode($attributeValueData, JSON_UNESCAPED_UNICODE)));
                            }
                        }
                    }
                    if (wp_get_attachment_image_src($wcProduct->get_image_id(), 'full')) {
                        $itemImage = wp_get_attachment_image_src($wcProduct->get_image_id(), 'full')[0];
                    }
                    $usedItems[$i] = true;
                    break;
                }
            }
        }
        $kItems[$itemKey] = [
            'price' => $item['initialPrice'],
            // 'discount_percent' => $item['quantity'], #todo не найдено моделирования
            'discount_amount' => $item['discountManualAmount'],
            'quantity' => $item['quantity'],
            'name' => $item['productName'],
            'picture' => substr(get_the_post_thumbnail_url($item['offer']['externalId']), 0, 4) === 'http' ? get_the_post_thumbnail_url($item['offer']['externalId']) : ($itemImage ? $itemImage : null)
        ];
        if ($itemProduct && $itemProduct->get_sku() != '') {
            $kItems[$itemKey]['sku'] = $itemProduct->get_sku(); // todo Нужно выводить в класс или в функцию отдельно вне модуля
        }
        if (!empty($k_props)) {
            $kItems[$itemKey]['properties'] = $k_props;
        }
        $k_props = array();
    }

    $k_order = [
        'source_uuid' => $order['externalId'], //'4815162342',
        'source_id' => (int)$source, //11, //'4815162342',
        'status_id' => (int)isset($order['status'])?$order['status']:1, //1,
        'promocode' => isset($coupon_code) ? $coupon_code : '', //'MERRYCHRISTMAS
        'discount_amount' => $order['discountManualAmount'], //30.5,
        'shipping_price' => isset($order['delivery']['cost']) ? $order['delivery']['cost'] : 0, //2.5,
        'manager_comment' => isset($order['managerComment']) ? $order['managerComment'] : '', //NULL,
        'buyer_comment' => isset($order['customerComment']) ? $order['customerComment'] : '', //'Hello from buyer',
        // 'gift_message' => $order['id'], //'Happy Birthday Charlie',
        // 'is_gift' => $order['id'], //true,
        'ordered_at' => $order['createdAt'], //'2020-05-16 17:00:07',
        'buyer' => [
            'full_name' =>
              $order['firstName'] .
              ($wcOrder->get_meta('patronymic') ? ' ' . $wcOrder->get_meta('patronymic') : ''). ' '
              . $order['lastName'],
            // our user guide skipped email
            'email' => ($order['email'] !== 'skip@dummyemail.com' ? $order['email'] : ''),
            'phone' => $order['phone'],
        ],
        'shipping' => [
            'shipping_address_city' => $order['delivery']['address']['city'] ? $order['delivery']['address']['city'] : $wcOrder->get_shipping_city(),
            'shipping_address_country' => $order['countryIso'] ? $order['countryIso'] : $wcOrder->get_shipping_country(),
            'shipping_address_region' => $order['delivery']['address']['region'] ? $order['delivery']['address']['region'] : $wcOrder->get_shipping_state(),
            'shipping_address_zip' => $order['delivery']['address']['index'] ? $order['delivery']['address']['index'] : $wcOrder->get_shipping_postcode(),
            'shipping_receive_point' => $address. ' '. $address2,
            'recipient_full_name' => !empty($order['delivery']['recipient_full_name']) ? $order['delivery']['recipient_full_name'] : $order['firstName'] . ' ' . $order['lastName'],
            'recipient_phone' => !empty($order['delivery']['recipient_phone']) ? $order['delivery']['recipient_phone'] : $order['phone'],
        ],
        'products' => $kItems,
        'payments' => [
            [
                'payment_method_id' => (int)$order['payments'][0]['type'], //integer
                'amount' => $wcOrder->get_total(),
                // 'description' => $order['delivery']['index'], #todo не найдено аналога в CMS
                'payment_date' => getPaymentDate_kcrm($wcOrder),
                'status' => getPaymentStatus_kcrm($wcOrder),
            ]
        ],
    ];

    $utm_tags = processUtmTags_kcrm();

    if (!empty($utm_tags)) {
        $k_order['marketing'] = $utm_tags;
    }

    if (isset($order['delivery']['code'])) {
        $k_order['shipping']['delivery_service_id'] = (int)$order['delivery']['code'];
    }
    $k_order['shipping'] = apply_filters('keycrm_shipping_data',$k_order['shipping'],$wcOrder);

    if ($order['payments'][0]['type'] == null){
        $k_order['payments'] = [];
    }
    WC_Keycrm_Logger::add(sprintf(
        'KeyCRM order prepared: source_uuid=%s, products=%d, shipping=%s, payment=%s',
        (string) $k_order['source_uuid'],
        count($kItems),
        !empty($k_order['shipping']) ? 'yes' : 'no',
        !empty($k_order['payments']) ? 'yes' : 'no'
    ));
    return json_encode($k_order);
}


/**
 * @param $path
 * @return bool
 */
function allowedPath($path)
{
    $allowedPaths = [
        '/api-versions',
        '/orders',
        '/order',
        '/payment',
        '/products',

        // Reference Data
        '/reference/delivery-types',
        '/reference/payment-types',
        '/reference/statuses',
        '/reference/order-methods',

        '/pipelines',
        '/pipelines/cards'
    ];

    $patternAllowedPaths = [
        '/^\/order\/[^\/]+$/', // /order/123
        '/^\/order\/[^\/]+\/payment$/', // /order/69/payment
        '/^\/order\/[^\/]+\/payment\/[^\/]+$/', // /order/69/payment/123
        '/^\/payment\/[^\/]+$/', // /payment/123
        '/^\/products\/[^\/]+$/', // /products/123
        '/^\/products\/[^\/]+\/offers$/', // /products/123/offers
    ];

    if (in_array($path, $allowedPaths)) {
        return true;
    }

    foreach ($patternAllowedPaths as $pattern) {
        if (preg_match($pattern, $path)) {
            return true;
        }
    }

    return false;
}

/**
 * @param $responseBody
 * @param $path
 * @return false|string
 */
function convertResponse($responseBody, $path){
    $body = json_decode($responseBody, 1);
    switch ($path) {
        case '/reference/delivery-types':
            foreach ($body['data'] as $bodyItemKey => $bodyItem){
                $body['deliveryTypes'][$bodyItemKey]['code'] = $bodyItem['id'];
                $body['deliveryTypes'][$bodyItemKey]['name'] = $bodyItem['name'];
            }
            unset($body['data']);
            break;
        case '/reference/payment-types':
            foreach ($body['data'] as $bodyItemKey => $bodyItem){
                $body['paymentTypes'][$bodyItemKey]['code'] = $bodyItem['id'];
                $body['paymentTypes'][$bodyItemKey]['name'] = $bodyItem['name'];
            }
            unset($body['data']);
            break;
        case '/reference/statuses':
            foreach ($body['data'] as $bodyItemKey => $bodyItem){
                $body['statuses'][$bodyItemKey]['code'] = $bodyItem['id'];
                $body['statuses'][$bodyItemKey]['name'] = $bodyItem['name'];
            }
            unset($body['data']);
            break;
        case '/reference/order-methods':
            foreach ($body['data'] as $bodyItemKey => $bodyItem){
                $body['orderMethods'][$bodyItemKey]['code'] = $bodyItem['id'];
                $body['orderMethods'][$bodyItemKey]['name'] = $bodyItem['name'];
                if (!$bodyItem['deleted_at']) {
                    $body['orderMethods'][$bodyItemKey]['active'] = true;
                }
            }
            unset($body['data']);
            break;

    }
    return json_encode($body);
}

/**
 * Get payment status for KeyCRM based on WooCommerce order
 *
 * @param WC_Order $wcOrder WooCommerce order object
 * @return string Payment status: 'paid', 'not_paid', or 'cancelled'
 */
function getPaymentStatus_kcrm($wcOrder) {
    $datePaid = getPaymentDate_kcrm($wcOrder);

    if (!empty($datePaid) && $wcOrder->get_status() != 'cancelled') {
        return 'paid';
    }

    return $wcOrder->get_status() === 'cancelled' ? 'canceled' : 'not_paid';
}

/**
 * Get payment date for KeyCRM based on WooCommerce order
 *
 * @param WC_Order $wcOrder WooCommerce order object
 * @return string|null Payment date in 'Y-m-d H:i:s' format or null if not paid
 */
function getPaymentDate_kcrm($wcOrder) {
    if (method_exists($wcOrder, 'get_date_paid')) {
        $datePaid = $wcOrder->get_date_paid();
        if (!empty($datePaid)) {
            return $datePaid->date('Y-m-d H:i:s');
        }
    } else {
        $datePaid = $wcOrder->get_meta('_date_paid', true);
        if (!empty($datePaid)) {
            return $datePaid;
        }
    }

    return null;
}

/**
 * Process order UTM tags
 * If utm_source = direct and all other UTM tags = none, returns empty array
 * If all UTM tags are empty after processing, returns empty array
 *
 * @return array Array with processed UTM tags or empty array
 */
function processUtmTags_kcrm() {
    $utm_tags = [
        'utm_source' => isset($_POST['wc_order_attribution_utm_source']) ? $_POST['wc_order_attribution_utm_source'] : '',
        'utm_medium' => isset($_POST['wc_order_attribution_utm_medium']) ? $_POST['wc_order_attribution_utm_medium'] : '',
        'utm_campaign' => isset($_POST['wc_order_attribution_utm_campaign']) ? $_POST['wc_order_attribution_utm_campaign'] : '',
        'utm_term' => isset($_POST['wc_order_attribution_utm_term']) ? $_POST['wc_order_attribution_utm_term'] : '',
        'utm_content' => isset($_POST['wc_order_attribution_utm_content']) ? $_POST['wc_order_attribution_utm_content'] : ''
    ];

    $is_direct = $utm_tags['utm_source'] === '(direct)';
    if (!$is_direct) {
        return $utm_tags;
    }

    $other_utm_fields = [
        'wc_order_attribution_utm_medium',
        'wc_order_attribution_utm_campaign',
        'wc_order_attribution_utm_term',
        'wc_order_attribution_utm_content',
    ];

    foreach ($other_utm_fields as $field) {
        $value = isset($_POST[$field]) ? $_POST[$field] : '';
        if ($value !== '(none)' && $value !== '') {
            return $utm_tags;
        }
    }

    return [];
}
