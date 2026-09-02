<?php

/**
 * PHP version 5.6
 *
 * Request class
 *
 * @category Integration
 * @package  WC_Keycrm_Client
 * @author   KeyCRM <dev@keycrm.app>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     https://docs.keycrm.app/#/
 */

if ( ! class_exists( 'WC_Keycrm_Request' ) ) {
    include_once(WC_Integration_Keycrm::checkCustomFile('include/api/class-wc-keycrm-request.php'));
}

if ( ! class_exists( 'WC_Keycrm_Response' ) ) {
    include_once(WC_Integration_Keycrm::checkCustomFile('include/api/class-wc-keycrm-response.php'));
}


class WC_Keycrm_Client_V6
{
    /**
     * @var WC_Keycrm_Request
     */
    protected $client;

    /**
     * Site code
     */
    protected $siteCode;

    CONST API_URL = 'https://openapi.keycrm.app/v1';

    /**
     * Client creating
     *
     * @param string $url    api url
     * @param string $apiKey api key
     * @param string $site   site code
     *
     * @throws InvalidArgumentException
     *
     */
    public function __construct($url, $apiKey, $version = null, $site = null)
    {
        $this->client = new WC_Keycrm_Request(self::API_URL, array('apiKey' => $apiKey));
        $this->siteCode = $site;
    }

    /**
     * This method need only for validate api key
     *
     * @return WC_Keycrm_Response
     */
    public function apiVersions()
    {
        return $this->orderMethodsList();
    }

    /**
     *
     * Method for backward compatibility
     *
     * @return string
     */
    public function getApiUrl()
    {
        return self::API_URL;
    }
    /**
     * Returns filtered orders list
     *
     * @param array $filter (default: array())
     * @param int   $page   (default: null)
     * @param int   $limit  (default: null)
     *
     * @throws InvalidArgumentException
     * @throws WC_Keycrm_Exception_Curl
     * @throws WC_Keycrm_Exception_Json
     *
     * @return WC_Keycrm_Response
     */
    public function ordersList(array $filter = array(), $page = null, $limit = null)
    {
        $parameters = array();

        if (count($filter)) {
            $parameters['filter'] = $filter;
        }
        if (null !== $page) {
            $parameters['page'] = (int) $page;
        }
        if (null !== $limit) {
            $parameters['limit'] = (int) $limit;
        }

        return $this->client->makeRequest(
            '/order',
            WC_Keycrm_Request::METHOD_GET,
            $parameters
        );
    }

    /**
     * Create a order
     *
     * @param array  $order order data
     * @param string $site  (default: null)
     *
     * @throws InvalidArgumentException
     * @throws WC_Keycrm_Exception_Curl
     * @throws WC_Keycrm_Exception_Json
     *
     * @return WC_Keycrm_Response
     */
    public function ordersCreate(array $order, $site = null)
    {
        if (!count($order)) {
            throw new InvalidArgumentException('Parameter `order` must contains a data');
        }

        return $this->client->makeRequest(
            '/order',
            WC_Keycrm_Request::METHOD_POST,
            $this->fillSite($site, array('order' => json_encode($order)))
        );
    }

    /**
     * Upload array of the orders
     *
     * @param array  $orders array of orders
     * @param string $site   (default: null)
     *
     * @throws InvalidArgumentException
     * @throws WC_Keycrm_Exception_Curl
     * @throws WC_Keycrm_Exception_Json
     *
     * @return WC_Keycrm_Response
     */
    public function ordersUpload(array $orders, $site = null)
    {
        if (!count($orders)) {
            throw new InvalidArgumentException(
                'Parameter `orders` must contains array of the orders'
            );
        }

        return $this->client->makeRequest(
            '/orders/import',
            WC_Keycrm_Request::METHOD_POST,
            $this->fillSite($site, array('orders' => json_encode($orders)))
        );
    }

    /**
     * Get order by id or externalId
     *
     * @param string $id   order identification
     * @param string $by   (default: 'id')
     * @param string $site (default: null)
     *
     * @throws InvalidArgumentException
     * @throws WC_Keycrm_Exception_Curl
     * @throws WC_Keycrm_Exception_Json
     *
     * @return WC_Keycrm_Response
     */
    public function ordersGet($id, $by = 'id', $site = null)
    {
        $this->checkIdParameter($by);

        return $this->client->makeRequest(
            "/order/$id",
            WC_Keycrm_Request::METHOD_GET,
            $this->fillSite($site, array('by' => $by,'include'=>'payments'))
        );
    }

    /**
     * Edit a order
     *
     * @param array  $order order data
     * @param string $by    (default: 'id')
     * @param string $site  (default: null)
     *
     * @throws InvalidArgumentException
     * @throws WC_Keycrm_Exception_Curl
     * @throws WC_Keycrm_Exception_Json
     *
     * @return WC_Keycrm_Response
     */
    public function ordersEdit(array $order, $by = 'id', $site = null)
    {
        if (!count($order)) {
            throw new InvalidArgumentException('Parameter `order` must contains a data');
        }

        $this->checkIdParameter($by);

        if (!array_key_exists($by, $order)) {
            throw new InvalidArgumentException(
                sprintf('Order array must contain the "%s" parameter.', $by)
            );
        }

        return $this->client->makeRequest(
            sprintf('/order/%s', $order[$by]),
            WC_Keycrm_Request::METHOD_PUT,
            $this->fillSite(
                $site,
                array('order' => json_encode($order), 'by' => $by)
            )
        );
    }


    /**
     * Create an order payment
     *
     * @param array $payment order data
     *
     * @throws InvalidArgumentException
     * @throws WC_Keycrm_Exception_Curl
     * @throws WC_Keycrm_Exception_Json
     *
     * @return WC_Keycrm_Response
     */
    public function ordersPaymentCreate(array $payment)
    {
        if (!count($payment)) {
            throw new InvalidArgumentException('Parameter `payment` must contains a data');
        }

        if (!isset($payment['order']['id'])) {
            throw new InvalidArgumentException('Payment must contain order ID');
        }

        $wcOrder = null;
        if (isset($payment['order']['externalId'])) {
            $wcOrder = wc_get_order($payment['order']['externalId']);
        }

        $convertedPayment = convertPayment_kcrm($payment, $wcOrder);

        return $this->client->makeRequest(
            sprintf('/order/%s/payment', $payment['order']['id']),
            WC_Keycrm_Request::METHOD_POST,
            array('payment' => json_encode($convertedPayment))
        );
    }

    /**
     * Update order payment status
     */
    public function ordersPaymentUpdate($orderId, $paymentId, array $paymentData)
    {

        if (!$orderId || !$paymentId) {
            throw new InvalidArgumentException('Order ID and Payment ID must be set');
        }

        return $this->client->makeRequest(
            sprintf('/order/%s/payment/%s', $orderId, $paymentId),
            WC_Keycrm_Request::METHOD_PUT,
            array('payment' => json_encode($paymentData))
        );
    }

    /**
     * Returns filtered customers list
     *
     * @param array $filter (default: array())
     * @param int   $page   (default: null)
     * @param int   $limit  (default: null)
     *
     * @throws InvalidArgumentException
     * @throws WC_Keycrm_Exception_Curl
     * @throws WC_Keycrm_Exception_Json
     *
     * @return WC_Keycrm_Response
     */
    public function customersList(array $filter = array(), $page = null, $limit = null)
    {
        $parameters = array();

        if (count($filter)) {
            $parameters['filter'] = $filter;
        }
        if (null !== $page) {
            $parameters['page'] = (int) $page;
        }
        if (null !== $limit) {
            $parameters['limit'] = (int) $limit;
        }

        return $this->client->makeRequest(
            '/buyer',
            WC_Keycrm_Request::METHOD_GET,
            $parameters
        );
    }

    /**
     * Create a customer
     *
     * @param array  $customer customer data
     * @param string $site     (default: null)
     *
     * @throws InvalidArgumentException
     * @throws WC_Keycrm_Exception_Curl
     * @throws WC_Keycrm_Exception_Json
     *
     * @return WC_Keycrm_Response
     */
    public function customersCreate(array $customer, $site = null)
    {
        if (!count($customer)) {
            throw new InvalidArgumentException('Parameter `customer` must contains a data');
        }

        return $this->client->makeRequest(
            '/buyer',
            WC_Keycrm_Request::METHOD_POST,
            $this->fillSite($site, array('customer' => json_encode($customer)))
        );
    }


    /**
     * Upload array of the customers
     *
     * @param array  $customers array of customers
     * @param string $site      (default: null)
     *
     * @throws InvalidArgumentException
     * @throws WC_Keycrm_Exception_Curl
     * @throws WC_Keycrm_Exception_Json
     *
     * @return WC_Keycrm_Response
     */
    public function customersUpload(array $customers, $site = null)
    {
        if (!count($customers)) {
            throw new InvalidArgumentException(
                'Parameter `customers` must contains array of the customers'
            );
        }

        return $this->client->makeRequest(
            '/buyer/import',
            WC_Keycrm_Request::METHOD_POST,
            $this->fillSite($site, array('customers' => json_encode($customers)))
        );
    }

    /**
     * Get customer by id or externalId
     *
     * @param string $id   customer identificator
     * @param string $by   (default: 'id')
     * @param string $site (default: null)
     *
     * @throws InvalidArgumentException
     * @throws WC_Keycrm_Exception_Curl
     * @throws WC_Keycrm_Exception_Json
     *
     * @return WC_Keycrm_Response
     */
    public function customersGet($id, $by = 'externalId', $site = null)
    {
        $this->checkIdParameter($by);

        return $this->client->makeRequest(
            "/buyer/$id",
            WC_Keycrm_Request::METHOD_GET,
            $this->fillSite($site, array('by' => $by))
        );
    }

    /**
     * Edit a customer
     *
     * @param array  $customer customer data
     * @param string $by       (default: 'id')
     * @param string $site     (default: null)
     *
     * @throws InvalidArgumentException
     * @throws WC_Keycrm_Exception_Curl
     * @throws WC_Keycrm_Exception_Json
     *
     * @return WC_Keycrm_Response
     */
    public function customersEdit(array $customer, $by = 'externalId', $site = null)
    {
        if (!count($customer)) {
            throw new InvalidArgumentException(
                'Parameter `customer` must contains a data'
            );
        }

        $this->checkIdParameter($by);

        if (!array_key_exists($by, $customer)) {
            throw new InvalidArgumentException(
                sprintf('Customer array must contain the "%s" parameter.', $by)
            );
        }

        return $this->client->makeRequest(
            sprintf('/buyer/%s', $customer[$by]),
            WC_Keycrm_Request::METHOD_PUT,
            $this->fillSite(
                $site,
                array('customer' => json_encode($customer), 'by' => $by)
            )
        );
    }



    /**
     * Get purchase prices & stock balance
     *
     * @param array $filter (default: array())
     * @param int   $page   (default: null)
     * @param int   $limit  (default: null)
     *
     * @throws InvalidArgumentException
     * @throws WC_Keycrm_Exception_Curl
     * @throws WC_Keycrm_Exception_Json
     *
     * @return WC_Keycrm_Response
     */
    public function storeInventories(array $filter = array(), $page = null, $limit = null)
    {
        $parameters = array();

        if (count($filter)) {
            $parameters['filter'] = $filter;
        }
        if (null !== $page) {
            $parameters['page'] = (int) $page;
        }
        if (null !== $limit) {
            $parameters['limit'] = (int) $limit;
        }

        return $this->client->makeRequest(
            '/store/inventories',
            WC_Keycrm_Request::METHOD_GET,
            $parameters
        );
    }

    /**
     * Create a product
     *
     * @param array $product product data
     *
     * @throws InvalidArgumentException
     * @throws WC_Keycrm_Exception_Curl
     * @throws WC_Keycrm_Exception_Json
     *
     * @return WC_Keycrm_Response
     */
    public function productsCreate(array $product)
    {
        if (!count($product)) {
            throw new InvalidArgumentException('Parameter `product` must contains a data');
        }

        return $this->client->makeRequest(
            '/products',
            WC_Keycrm_Request::METHOD_POST,
            array('product' => json_encode($product))
        );
    }

    /**
     * Create product offers/variations
     *
     * @param int   $productId product ID
     * @param array $offers    offers data
     *
     * @throws InvalidArgumentException
     * @throws WC_Keycrm_Exception_Curl
     * @throws WC_Keycrm_Exception_Json
     *
     * @return WC_Keycrm_Response
     */
    public function productsCreateOffers($productId, array $offers)
    {
        if (!count($offers)) {
            throw new InvalidArgumentException('Parameter `offers` must contains a data');
        }

        return $this->client->makeRequest(
            "/products/{$productId}/offers",
            WC_Keycrm_Request::METHOD_POST,
            array('offers' => json_encode($offers))
        );
    }



    /**
     * Get product by ID
     *
     * @param int $productId product ID
     *
     * @throws InvalidArgumentException
     * @throws WC_Keycrm_Exception_Curl
     * @throws WC_Keycrm_Exception_Json
     *
     * @return WC_Keycrm_Response
     */
    public function productsGet($productId)
    {
        return $this->client->makeRequest(
            "/products/{$productId}",
            WC_Keycrm_Request::METHOD_GET
        );
    }

    /**
     * Search products by filter
     *
     * @param array $filter filter criteria
     * @param int   $page   page number
     * @param int   $limit  items per page
     *
     * @throws InvalidArgumentException
     * @throws WC_Keycrm_Exception_Curl
     * @throws WC_Keycrm_Exception_Json
     *
     * @return WC_Keycrm_Response
     */
    public function productsList(array $filter = array(), $page = null, $limit = null)
    {
        $parameters = array();

        if (count($filter)) {
            $parameters['filter'] = $filter;
        }
        if (null !== $page) {
            $parameters['page'] = (int) $page;
        }
        if (null !== $limit) {
            $parameters['limit'] = (int) $limit;
        }

        return $this->client->makeRequest(
            '/products',
            WC_Keycrm_Request::METHOD_GET,
            $parameters
        );
    }

    /**
     * Update a product
     *
     * @param int   $productId product ID
     * @param array $product   product data
     *
     * @throws InvalidArgumentException
     * @throws WC_Keycrm_Exception_Curl
     * @throws WC_Keycrm_Exception_Json
     *
     * @return WC_Keycrm_Response
     */
    public function productsUpdate($productId, array $product)
    {
        if (!count($product)) {
            throw new InvalidArgumentException('Parameter `product` must contains a data');
        }

        return $this->client->makeRequest(
            "/products/{$productId}",
            WC_Keycrm_Request::METHOD_PUT,
            array('product' => json_encode($product))
        );
    }

    /**
     * Return current site
     *
     * @return string
     */
    public function getSite()
    {
        return $this->siteCode;
    }

    /**
     * getSingleSiteForKey
     *
     * @return string|bool
     */
    public function getSingleSiteForKey()
    {
        $site = $this->getSite();

        if (!empty($site)) {
            return $this->getSite();
        }

        $response = $this->credentials();

        if ($response instanceof WC_Keycrm_Response
            && $response->offsetExists('sitesAvailable')
            && is_array($response['sitesAvailable'])
            && !empty($response['sitesAvailable'])
        ) {
            $this->siteCode = $response['sitesAvailable'][0];
        }

        return $this->getSite();
    }

    /**
     * Set site
     *
     * @param string $site site code
     *
     * @return void
     */
    public function setSite($site)
    {
        $this->siteCode = $site;
    }

    /**
     * Check ID parameter
     *
     * @param string $by identify by
     *
     * @throws InvalidArgumentException
     *
     * @return bool
     */
    protected function checkIdParameter($by)
    {
        $allowedForBy = array(
            'externalId',
            'id'
        );

        if (!in_array($by, $allowedForBy, false)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Value "%s" for "by" param is not valid. Allowed values are %s.',
                    $by,
                    implode(', ', $allowedForBy)
                )
            );
        }

        return true;
    }

    /**
     * Fill params by site value
     *
     * @param string $site   site code
     * @param array  $params input parameters
     *
     * @return array
     */
    protected function fillSite($site, array $params)
    {
        if ($site) {
            $params['site'] = $site;
        } elseif ($this->siteCode) {
            $params['site'] = $this->siteCode;
        }

        return $params;
    }

    /**
     * Returns orderMethods list
     *
     * @throws InvalidArgumentException
     * @throws WC_Keycrm_Exception_Curl
     * @throws WC_Keycrm_Exception_Json
     *
     * @return WC_Keycrm_Response
     */
    public function orderMethodsList()
    {
        $wordpressFilter = function($source) {
            return isset($source['driver']) && $source['driver'] === 'wordpress';
        };

        return $this->makePaginatedRequest('/order/source', $wordpressFilter);
    }
    /**
     * Returns deliveryTypes list
     *
     * @throws InvalidArgumentException
     * @throws WC_Keycrm_Exception_Curl
     * @throws WC_Keycrm_Exception_Json
     *
     * @return WC_Keycrm_Response
     */
    public function deliveryTypesList()
    {
        return $this->makePaginatedRequest('/order/delivery-service');
    }

    /**
     * Returns paymentTypes list
     *
     * @throws InvalidArgumentException
     * @throws WC_Keycrm_Exception_Curl
     * @throws WC_Keycrm_Exception_Json
     *
     * @return WC_Keycrm_Response
     */
    public function paymentTypesList()
    {
        return $this->makePaginatedRequest('/order/payment-method');
    }

    /**
     * @return array
     */
    public function integrationModulesEdit()
    {
        return [];
    }

    /**
     * Universal paginated request
     *
     * @param string $endpoint
     * @param callable|null $filterCallback
     * @return WC_Keycrm_Response
     */
    private function makePaginatedRequest($endpoint, $filterCallback = null)
    {
        $allData = array();
        $page = 1;
        $limit = 50;

        do {
            $response = $this->client->makeRequest(
                $endpoint,
                WC_Keycrm_Request::METHOD_GET,
                array('page' => $page, 'limit' => $limit)
            );

            if ($response->isSuccessful() && isset($response['data'])) {
                $pageData = $response['data'];

                if ($filterCallback && is_callable($filterCallback)) {
                    $pageData = array_filter($pageData, $filterCallback);
                }

                $allData = array_merge($allData, $pageData);

                if (isset($response['current_page']) &&
                    isset($response['last_page']) &&
                    $response['current_page'] < $response['last_page']) {
                    $page++;
                } else {
                    break;
                }
            } else {
                return $response;
            }

            if (function_exists('usleep')) {
                usleep(200000);
            }
        } while (true);

        return new WC_Keycrm_Response($response->getStatusCode(), json_encode(array(
            'success' => true,
            'data' => array_values($allData)
        )));
    }


    /**
     * Get pipelines list from KeyCRM
     *
     * @throws InvalidArgumentException
     * @throws WC_Keycrm_Exception_Curl
     * @throws WC_Keycrm_Exception_Json
     *
     * @return WC_Keycrm_Response
     */
    public function getPipelinesList()
    {
        return $this->makePaginatedRequest('/pipelines');
    }

    /**
     * Create pipeline card
     *
     * @param array $cardData Card data
     *
     * @throws InvalidArgumentException
     * @throws WC_Keycrm_Exception_Curl
     * @throws WC_Keycrm_Exception_Json
     *
     * @return WC_Keycrm_Response
     */
    public function createPipelineCard(array $cardData)
    {
        if (!count($cardData)) {
            throw new InvalidArgumentException('Parameter `cardData` must contains a data');
        }

        return $this->client->makeRequest(
            '/pipelines/cards',
            WC_Keycrm_Request::METHOD_POST,
            ['pipelines'=>json_encode($cardData)]
        );
    }

}