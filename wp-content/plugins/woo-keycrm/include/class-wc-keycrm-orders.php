<?php
/**
 * KeyCRM Integration.
 *
 * @package  WC_Keycrm_Orders
 * @category Integration
 * @author   KeyCRM
 */

if ( ! class_exists( 'WC_Keycrm_Orders' ) ) :

    /**
     * Class WC_Keycrm_Orders
     */
    class WC_Keycrm_Orders
    {
        /** @var bool|WC_Keycrm_Proxy|\WC_Keycrm_Client_V6 */
        protected $keycrm;

        /** @var array */
        protected $keycrm_settings;

        /** @var WC_Keycrm_Order_Item */
        protected $order_item;

        /** @var WC_Keycrm_Order_Address */
        protected $order_address;

        /** @var WC_Keycrm_Order_Payment */
        protected $order_payment;

        /** @var WC_Keycrm_Customers */
        protected $customers;

        /** @var WC_Keycrm_Order */
        protected $orders;

        /** @var array */
        private $ordersGetRequestCache = array();

        /** @var array */
        private $order = array();

        /** @var array */
        private $payment = array();

        public function __construct(
            $keycrm,
            $keycrm_settings,
            $order_item,
            $order_address,
            $customers,
            $orders,
            $order_payment
        ) {
            if ($keycrm === null) {
                $keycrm = false;
            }
            $this->keycrm = $keycrm;
            $this->keycrm_settings = $keycrm_settings;
            $this->order_item = $order_item;
            $this->order_address = $order_address;
            $this->customers = $customers;
            $this->orders = $orders;
            $this->order_payment = $order_payment;
        }

        /**
         * Upload orders to CRM
         *
         * @param array $include
         *
         * @return array $uploadOrders | null
         * @throws \Exception
         */
        public function ordersUpload($include = array())
        {
            if (!$this->keycrm) {
                return null;
            }

            $uploader = new WC_Keycrm_Customers(
                $this->keycrm,
                $this->keycrm_settings,
                new WC_Keycrm_Customer_Address()
            );

            $orders = array();

            if (function_exists('wc_get_orders')) {
                $orders = wc_get_orders(array(
                    'limit' => -1,
                    'status' => array_keys(wc_get_order_statuses()),
                    'post__in' => $include
                ));
            } else {
                $posts = get_posts(array(
                    'numberposts' => -1,
                    'post_type' => wc_get_order_types('view-orders'),
                    'post_status' => array_keys(wc_get_order_statuses()),
                    'post__in' => $include
                ));

                foreach ($posts as $post) {
                    $orders[] = wc_get_order($post->ID);
                }
            }

            $regularUploadErrors = array();
            $corporateUploadErrors = array();

            foreach ($orders as $order) {
                $orderId = is_object($order) && method_exists($order, 'get_id') ? $order->get_id() : $order->ID;
                if (!$order->get_meta('_keycrm_order_id')) {
                    $errorMessage = $this->orderCreate($orderId);

                    if (is_string($errorMessage)) {
                        if ($this->keycrm->getCorporateEnabled() && self::isCorporateOrder($order)) {
                            $corporateUploadErrors[$orderId] = $errorMessage;
                        } else {
                            $regularUploadErrors[$orderId] = $errorMessage;
                        }
                    }
                    sleep(1);
                }else{
                    WC_Keycrm_Logger::add(sprintf("Order %s already exists in KeyCRM, no need to update", $orderId));
                }
            }

            static::logOrdersUploadErrors($regularUploadErrors, 'Error while uploading these regular orders');
            static::logOrdersUploadErrors($corporateUploadErrors, 'Error while uploading these corporate orders');

            return array();
        }

        /**
         * Create or update order. Returns wc_get_order data or error string.
         *
         * @param      $order_id
         *
         * @return bool|WC_Order|WC_Order_Refund|string
         * @throws \Exception
         */
        public function orderCreate($order_id)
        {
            if (!$this->keycrm) {
                return null;
            }

            $this->order_payment->reset_data();

            $wcOrder = wc_get_order($order_id);

            if ($wcOrder->get_meta('_keycrm_order_id')) {
                return $this->updateOrder($order_id);
            }

            $this->processOrder($wcOrder);

            try {
                $response = $this->keycrm->ordersCreate($this->order);

                if ($response instanceof WC_Keycrm_Response) {
                    if ($response->isSuccessful()) {
                        if (isset($response['id'])) {
                            $wcOrder->add_meta_data('_keycrm_order_id',$response['id']);
                            $wcOrder->save_meta_data();
                        }
                        return $wcOrder;
                    }

                    return $response->getErrorString();
                }
            } catch (InvalidArgumentException $exception) {
                return $exception->getMessage();
            }

            return $wcOrder;
        }

        /**
         * Process order customer data
         *
         * @param \WC_Order $wcOrder
         * @param bool      $update
         *
         * @return bool Returns false if order cannot be processed
         * @throws \Exception
         */
        protected function processOrderCustomerInfo($wcOrder, $update = false)
        {
            $customerWasChanged = false;
            $wpUser = $wcOrder->get_user();

           /* if ($update) {
                $response = $this->getCrmOrder($wcOrder->get_id());

                if (!empty($response)) {
                    $customerWasChanged = self::isOrderCustomerWasChanged($wcOrder, $response);
                }
            }

            if ($wpUser instanceof WP_User) {
                if (!$this->customers->isCustomer($wpUser)) {
                    return false;
                }

                $wpUserId = (int) $wpUser->get('ID');

                if (!$update || ($update && $customerWasChanged)) {
                    $this->fillOrderCreate($wpUserId, $wpUser->get('billing_email'), $wcOrder);
                }
            } else {
                $wcCustomer = $this->customers->buildCustomerFromOrderData($wcOrder);

                if (!$update || ($update && $customerWasChanged)) {
                    $this->fillOrderCreate(0, $wcCustomer->get_billing_email(), $wcOrder);
                }
            }*/

            if ($update && $customerWasChanged) {
                $firstName = $wcOrder->get_shipping_first_name();
                $lastName = $wcOrder->get_shipping_last_name();

                if(empty($firstName) && empty($lastName))
                {
                    $firstName = $wcOrder->get_billing_first_name();
                    $lastName = $wcOrder->get_billing_last_name();
                }

                $this->order['firstName'] = $firstName;
                $this->order['lastName'] = $lastName;
            }

            return true;
        }

        /**
         * Fill order on create
         *
         * @param int       $wcCustomerId
         * @param string    $wcCustomerEmail
         * @param \WC_Order $wcOrder
         *
         * @throws \Exception
         */
        protected function fillOrderCreate($wcCustomerId, $wcCustomerEmail, $wcOrder)
        {
            $foundCustomerId = '';
            $foundCustomer = $this->customers->findCustomerEmailOrId($wcCustomerId, $wcCustomerEmail);

            if (empty($foundCustomer)) {
                $foundCustomerId = $this->customers->createCustomer($wcCustomerId, $wcOrder);

                if (!empty($foundCustomerId)) {
                    $this->order['customer']['id'] = $foundCustomerId;
                }
            } else {
                $this->order['customer']['id'] = $foundCustomer['id'];
                $foundCustomerId = $foundCustomer['id'];
            }

            $this->order['contragent']['contragentType'] = 'individual';

            if ($this->keycrm->getCorporateEnabled() && static::isCorporateOrder($wcOrder)) {
                unset($this->order['contragent']['contragentType']);

                $crmCorporate = $this->customers->searchCorporateCustomer(array(
                    'contactIds' => array($foundCustomerId),
                    'companyName' => $wcOrder->get_billing_company()
                ));

                if (empty($crmCorporate)) {
                    $crmCorporate = $this->customers->searchCorporateCustomer(array(
                        'companyName' => $wcOrder->get_billing_company()
                    ));
                }

                if (empty($crmCorporate)) {
                    $corporateId = $this->customers->createCorporateCustomerForOrder(
                        $foundCustomerId,
                        $wcCustomerId,
                        $wcOrder
                    );
                    $this->order['customer']['id'] = $corporateId;
                } else {
                    $this->customers->fillCorporateAddress(
                        $crmCorporate['id'],
                        new WC_Customer($wcCustomerId),
                        $wcOrder
                    );
                    $this->order['customer']['id'] = $crmCorporate['id'];
                }

                $companiesResponse = $this->keycrm->customersCorporateCompanies(
                    $this->order['customer']['id'],
                    array(),
                    null,
                    null,
                    'id'
                );

                if (!empty($companiesResponse) && $companiesResponse->isSuccessful()) {
                    foreach ($companiesResponse['companies'] as $company) {
                        if ($company['name'] == $wcOrder->get_billing_company()) {
                            $this->order['company'] = array(
                                'id' => $company['id'],
                                'name' => $company['name']
                            );
                            break;
                        }
                    }
                }

                $this->order['contact']['id'] = $foundCustomerId;
            }
        }

        /**
         * Edit order in CRM
         *
         * @param int $order_id
         *
         * @return WC_Order $order | null
         * @throws \Exception
         */
        public function updateOrder($order_id)
        {
            if (!$this->keycrm) {
                return null;
            }

            $wcOrder = wc_get_order($order_id);
            $crmOrderId = $wcOrder->get_meta('_keycrm_order_id');

            if (empty($crmOrderId)) {
                WC_Keycrm_Logger::add("Order {$order_id} not found in CRM for update");
                return null;
            }

            $this->processOrder($wcOrder, true);

            $address1 = $wcOrder->get_shipping_address_1() ? $wcOrder->get_shipping_address_1() : $wcOrder->get_billing_address_1();
            $address2 = $wcOrder->get_shipping_address_2();

            $products = array();
            if (isset($this->order['items'])) {
                foreach ($this->order['items'] as $item) {
                    $product_id = isset($item['offer']['externalId']) ? (int)$item['offer']['externalId'] : 0;

                    if ($product_id <= 0) continue;

                    $productData = array(
                        'id' => $product_id,
                        'name' => isset($item['productName']) ? $item['productName'] : '',
                        'comment' => '',
                        'price' => isset($item['initialPrice']) ? (float)$item['initialPrice'] : 0,
                        'purchased_price' => isset($item['initialPrice']) ? (float)$item['initialPrice'] : 0,
                        'quantity' => isset($item['quantity']) ? (int)$item['quantity'] : 1,
                        'discount_amount' => isset($item['discountManualAmount']) ? (float)$item['discountManualAmount'] : 0,
                        'discount_percent' => isset($item['discountManualPercent']) ? (float)$item['discountManualPercent'] : 0,
                        'product_status_id' => 1
                    );

                    $wcProduct = wc_get_product($product_id);
                    if ($wcProduct && $wcProduct->get_sku()) {
                        $productData['sku'] = $wcProduct->get_sku();
                    }

                    $products[] = $productData;
                }
            }

            $shipping = array();
            if (isset($this->order['delivery']['code']) && $this->order['delivery']['code']) {
                $shipping['delivery_service_id'] = (int)$this->order['delivery']['code'];

                if (isset($this->order['delivery']['address'])) {
                    $address = $this->order['delivery']['address'];
                    $shipping['shipping_address_city'] = isset($address['city']) ? $address['city'] : '';
                    $shipping['shipping_address_region'] = isset($address['region']) ? $address['region'] : '';
                    $shipping['shipping_address_zip'] = isset($address['index']) ? $address['index'] : '';
                }

                $shipping['shipping_address_country'] = isset($this->order['countryIso']) ? $this->order['countryIso'] : '';
                $shipping['shipping_receive_point'] = trim($address1 . ' ' . $address2);
                $shipping['shipping_secondary_line'] = '';
                $shipping['tracking_code'] = '';
                $shipping['warehouse_ref'] = '';
                $shipping['shipping_date'] = '';
                $shipping['recipient_full_name'] = isset($this->order['firstName']) && isset($this->order['lastName']) ? $this->order['firstName'] . ' ' . $this->order['lastName'] : '';
                $shipping['recipient_phone'] = isset($this->order['phone']) ? $this->order['phone'] : '';
                $shipping = apply_filters('keycrm_shipping_data', $shipping, $wcOrder);
            }

            $updateData = array(
                'id' => $crmOrderId,
                'buyer_comment' => isset($this->order['customerComment']) ? $this->order['customerComment'] : '',
                'manager_comment' => isset($this->order['managerComment']) ? $this->order['managerComment'] : '',
                'discount_amount' => isset($this->order['discountManualAmount']) ? (float)$this->order['discountManualAmount'] : 0,
                'discount_percent' => isset($this->order['discountManualPercent']) ? (float)$this->order['discountManualPercent'] : 0,
                'products' => $products,
                'shipping' => $shipping,
                'custom_fields' => array()
            );


            try {
                $response = $this->keycrm->ordersEdit($updateData, 'id');

                if (!$response || !$response->isSuccessful()) {
                    WC_Keycrm_Logger::add("Order {$order_id} update failed: " . $response->getErrorString());
                }

                return $wcOrder;

            } catch (Exception $e) {
                WC_Keycrm_Logger::add("Failed to update order {$order_id} in CRM: " . $e->getMessage());
                return null;
            }
        }

        /**
         * Update order payment type
         *
         * @param WC_Order $order
         *
         * @return null | array $payment
         */
        public function updateOrderStatusAndPayment($order)
        {
            if (!isset($this->keycrm_settings[$order->get_payment_method()])) {
                return null;
            }
            $keycrm_order_id=$order->get_meta('_keycrm_order_id');
            $keycrm_payment_id=$order->get_meta('_keycrm_payment_id');
            $last_synced_status = $order->get_meta('_keycrm_last_payment_status');

            if (!$keycrm_payment_id)
            {
                $crmOrder = $this->keycrm->ordersGet($keycrm_order_id);
                $keycrm_payment_id = $crmOrder['payments'][0]['id'];
                $order->add_meta_data('_keycrm_payment_id',$keycrm_payment_id);
                $order->save_meta_data();
            }

            $paymentStatus = getPaymentStatus_kcrm($order);
            if ($last_synced_status === $paymentStatus) {
                return null;
            }

            $updateData = [
                'status' => $paymentStatus,
            ];

            try {
                $response = $this->keycrm->ordersPaymentUpdate($keycrm_order_id, $keycrm_payment_id, $updateData);

                if ($response && $response->isSuccessful()) {
                    $order->update_meta_data('_keycrm_last_payment_status', $paymentStatus);
                    $order->save_meta_data();
                    return $updateData;
                }
            } catch (Exception $e) {
                WC_Keycrm_Logger::add("Error updating payment: " . $e->getMessage());
            }

            return null;
        }

        /**
         * process to combine order data
         *
         * @param WC_Order $order
         * @param boolean  $update
         *
         * @return void
         * @throws \Exception
         */
        protected function processOrder($order, $update = false)
        {
            if (!$order instanceof WC_Order) {
                return;
            }

            if ($order->get_status() == 'auto-draft') {
                return;
            }

            if ($update === true) {
                $this->orders->is_new = false;
            }

            $order_data = $this->orders->build($order)->get_data();

            if ($order->get_items('shipping')) {
                $shippings = $order->get_items('shipping');
                $shipping = reset($shippings);
                $shipping_code = explode(':', $shipping['method_id']);

                if (isset($this->keycrm_settings[$shipping['method_id']])) {
                    $shipping_method = $shipping['method_id'];
                } elseif (isset($this->keycrm_settings[$shipping_code[0]])) {
                    $shipping_method = $shipping_code[0];
                } else {
                    $shipping_method = $shipping['method_id'] . ':' . $shipping['instance_id'];
                }

                $shipping_cost = $shipping['total'] + $shipping['total_tax'];

                if (!empty($shipping_method) && !empty($this->keycrm_settings[$shipping_method])) {
                    $order_data['delivery']['code'] = $this->keycrm_settings[$shipping_method];
                    $service = keycrm_get_delivery_service($shipping['method_id'], $shipping['instance_id']);

                    if ($service) {
                        $order_data['delivery']['service'] = array(
                            'name' => $service['title'],
                            'code' => $service['instance_id'],
                            'active' => true
                        );
                    }
                }

                if (!empty($shipping_cost)) {
                    $order_data['delivery']['cost'] = $shipping_cost;
                }

                $activeNetCost = null;

                if (isset($this->keycrm_settings['send_delivery_net_cost'])){
                    $activeNetCost = $this->keycrm_settings['send_delivery_net_cost'];
                }

                if ($shipping['total'] && $activeNetCost != 'yes') {
                    $order_data['delivery']['netCost'] = $shipping['total'];
                }
            }

            $order_items = array();
            $order_data['delivery']['address'] = $this->order_address
                ->setFallbackToBilling(true)
                ->setWCAddressType(WC_Keycrm_Abstracts_Address::ADDRESS_TYPE_SHIPPING)
                ->build($order)
                ->get_data();

            /** @var WC_Order_Item_Product $item */
            foreach ($order->get_items() as $item) {
                $order_items[] = $this->order_item->build($item)->get_data();
                $this->order_item->reset_data();
            }

            $order_data['items'] = $order_items;

            $order_data['discountManualAmount'] = 0;
            $order_data['discountManualPercent'] = 0;

            if (!$update && $order->get_total() > 0) {
                $this->order_payment->is_new = true;
                $order_data['payments'][] = $this->order_payment->build($order)->get_data();
            }

            $this->order = WC_Keycrm_Plugin::clearArray($order_data);
            $this->processOrderCustomerInfo($order, $update);

            $this->order = apply_filters(
                'keycrm_process_order',
                WC_Keycrm_Plugin::clearArray($this->order),
                $order
            );
        }

        /**
         * ordersGet wrapper with cache (in order to minimize request count).
         *
         * @param int|string $orderId
         * @param bool       $cached
         *
         * @return array
         */
        protected function getCrmOrder($orderId, $cached = true)
        {
            if ($cached && isset($this->ordersGetRequestCache[$orderId])) {
                return (array) $this->ordersGetRequestCache[$orderId];
            }

            $crmOrder = array();
            $wcOrder = wc_get_order($orderId);
            $keycrm_order_id = $wcOrder->get_meta('_keycrm_order_id');
            $response = $this->keycrm->ordersGet($keycrm_order_id);

            if (!empty($response) && $response->isSuccessful() && isset($response['order'])) {
                $crmOrder = (array) $response['order'];
                $this->ordersGetRequestCache[$orderId] = $crmOrder;
            }

            return $crmOrder;
        }

        /**
         * @return array
         */
        public function getOrder()
        {
            return $this->order;
        }

        /**
         * @return array
         */
        public function getPayment()
        {
            return $this->payment;
        }

        /**
         * Returns true if provided order is for corporate customer
         *
         * @param WC_Order $order
         *
         * @return bool
         */
        public static function isCorporateOrder($order)
        {
            $billingCompany = $order->get_billing_company();

            return !empty($billingCompany);
        }

        /**
         * Returns true if passed crm order is corporate
         *
         * @param array|\ArrayAccess $order
         *
         * @return bool
         */
        public static function isCorporateCrmOrder($order)
        {
            return (is_array($order) || $order instanceof ArrayAccess)
                && isset($order['customer'])
                && isset($order['customer']['type'])
                && $order['customer']['type'] == 'customer_corporate';
        }

        /**
         * Returns true if customer in order was changed. `true` will be returned if one of these four conditions is met:
         *
         *  1. If CMS order is corporate and keyCRM order is not corporate or vice versa, then customer obviously
         *     needs to be updated in keyCRM.
         *  2. If billing company from CMS order is not the same as the one in the keyCRM order,
         *     then company needs to be updated.
         *  3. If contact person or individual externalId is different from customer ID in the CMS order, then
         *     contact person or customer in keyCRM should be updated (even if customer id in the order is not set).
         *  4. If contact person or individual email is not the same as the CMS order billing email, then
         *     contact person or customer in keyCRM should be updated.
         *
         * @param \WC_Order $wcOrder
         * @param array|\ArrayAccess $crmOrder
         *
         * @return bool
         */
        public static function isOrderCustomerWasChanged($wcOrder, $crmOrder)
        {
            if (!isset($crmOrder['customer'])) {
                return false;
            }

            $customerWasChanged = self::isCorporateOrder($wcOrder) != self::isCorporateCrmOrder($crmOrder);
            $synchronizableUserData = self::isCorporateCrmOrder($crmOrder)
                ? $crmOrder['contact'] : $crmOrder['customer'];

            if (!$customerWasChanged) {
                if (self::isCorporateCrmOrder($crmOrder)) {
                    $currentCrmCompany = isset($crmOrder['company']) ? $crmOrder['company']['name'] : '';

                    if (!empty($currentCrmCompany) && $currentCrmCompany != $wcOrder->get_billing_company()) {
                        $customerWasChanged = true;
                    }
                }

                if (isset($synchronizableUserData['externalId'])
                    && $synchronizableUserData['externalId'] != $wcOrder->get_customer_id()
                ) {
                    $customerWasChanged = true;
                } elseif (isset($synchronizableUserData['email'])
                    && $synchronizableUserData['email'] != $wcOrder->get_billing_email()
                ) {
                    $customerWasChanged = true;
                }
            }

            return $customerWasChanged;
        }

        /**
         * Logs orders upload errors with prefix log message.
         * Array keys must be orders ID's in WooCommerce, values must be strings (error messages).
         *
         * @param array  $errors
         * @param string $prefix
         */
        public static function logOrdersUploadErrors($errors, $prefix = 'Errors while uploading these orders')
        {
            if (empty($errors)) {
                return;
            }

            WC_Keycrm_Logger::add($prefix);

            foreach ($errors as $orderId => $error) {
                WC_Keycrm_Logger::add(sprintf("[%d] => %s", $orderId, $error));
            }

            WC_Keycrm_Logger::add('==================================');
        }
    }
endif;
