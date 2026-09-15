<?php
/** Persist validated carrier selections before Store API order processing. */

if (! defined('ABSPATH')) {
    exit;
}

final class Maruderm_Nova_Poshta_Checkout
{
    // Nova Poshta area refs (carrier directory) to WooCommerce Ukrainian states.
    private const AREA_STATES = [
        '71508128' => 'UA43', '71508129' => 'UA05', '7150812a' => 'UA07',
        '7150812b' => 'UA12', '7150812c' => 'UA14', '7150812d' => 'UA18',
        '7150812e' => 'UA21', '7150812f' => 'UA23', '71508130' => 'UA26',
        '71508131' => 'UA32', '71508132' => 'UA35', '71508133' => 'UA09',
        '71508134' => 'UA46', '71508135' => 'UA48', '71508136' => 'UA51',
        '71508137' => 'UA53', '71508138' => 'UA56', '71508139' => 'UA59',
        '7150813a' => 'UA61', '7150813b' => 'UA63', '7150813c' => 'UA65',
        '7150813d' => 'UA68', '7150813e' => 'UA71', '7150813f' => 'UA77',
        '71508140' => 'UA74',
    ];

    public function register(): void
    {
        add_action('woocommerce_store_api_checkout_update_order_from_request', [$this, 'updateOrder'], 5, 2);
        add_filter('woocommerce_get_country_locale', [$this, 'postcodeOptional']);
    }

    public function stateForCity(string $cityRef): string
    {
        $city = (array) (new \kirillbdev\WCUkrShipping\DB\Repositories\CityRepository())->getCityByRef($cityRef);
        if (($city['description'] ?? '') === 'Київ') {
            return 'UA30';
        }
        return self::AREA_STATES[substr((string) ($city['area_ref'] ?? ''), 0, 8)] ?? '';
    }

    public function resolve(string $cityRef, string $warehouseRef): array
    {
        $city = (array) (new \kirillbdev\WCUkrShipping\DB\Repositories\CityRepository())->getCityByRef($cityRef);
        $warehouse = (array) (new \kirillbdev\WCUkrShipping\DB\Repositories\WarehouseRepository())->getWarehouseByRef($warehouseRef);
        $state = $this->stateForCity($cityRef);
        if (! $city || ! $warehouse || ($warehouse['city_ref'] ?? '') !== $cityRef || $state === '') {
            throw new \InvalidArgumentException('Invalid Nova Poshta city/warehouse selection.');
        }
        return [
            'cityRef' => $cityRef,
            'cityName' => (string) $city['description'],
            'warehouseRef' => $warehouseRef,
            'warehouseName' => (string) $warehouse['description'],
            'state' => $state,
        ];
    }

    public function updateOrder(WC_Order $order, WP_REST_Request $request): void
    {
        $data = [];
        foreach ((array) $request->get_param('payment_data') as $item) {
            if (is_array($item) && isset($item['key'])) {
                $data[$item['key']] = $item['value'] ?? '';
            }
        }
        // Older storefronts use the order-key endpoint until the frontend release.
        if (! isset($data['maruderm_np_city_ref']) && ! isset($data['maruderm_np_warehouse_ref'])) {
            return;
        }
        try {
            $selection = $this->resolve(
                (string) ($data['maruderm_np_city_ref'] ?? ''),
                (string) ($data['maruderm_np_warehouse_ref'] ?? '')
            );
            $this->apply($order, $selection);
            // Countries may have cached locale rules before the cart session loaded.
            WC()->countries->locale = $this->postcodeOptional(WC()->countries->get_country_locale());
        } catch (\InvalidArgumentException $error) {
            $message = ($data['maruderm_language'] ?? '') === 'ru'
                ? 'Выберите действительное отделение Новой Почты в выбранном городе.'
                : 'Оберіть дійсне відділення Нової Пошти у вибраному місті.';
            throw new \Automattic\WooCommerce\StoreApi\Exceptions\RouteException('maruderm_invalid_np_selection', $message, 400);
        }
    }

    public function apply(WC_Order $order, array $selection): void
    {
        $items = $order->get_items('shipping');
        $item = reset($items);
        if (count($items) !== 1 || ! $item instanceof WC_Order_Item_Shipping || $item->get_method_id() !== 'nova_poshta_shipping') {
            throw new \InvalidArgumentException('Order must use Nova Poshta pickup delivery.');
        }
        foreach (['wcus_settlement_ref', 'wcus_settlement_full', 'wcus_settlement_name', 'wcus_settlement_area', 'wcus_settlement_region', 'wcus_street_ref', 'wcus_street_name', 'wcus_street_full', 'wcus_house', 'wcus_flat', 'wcus_api_address'] as $key) {
            $item->delete_meta_data($key);
        }
        foreach (['wcus_city_ref' => 'cityRef', 'wcus_city_name' => 'cityName', 'wcus_warehouse_ref' => 'warehouseRef', 'wcus_warehouse_name' => 'warehouseName'] as $key => $field) {
            $item->update_meta_data($key, $selection[$field]);
        }
        $item->save();
        foreach (['billing', 'shipping'] as $type) {
            $order->{'set_' . $type . '_city'}($selection['cityName']);
            $order->{'set_' . $type . '_address_1'}($selection['warehouseName']);
            $order->{'set_' . $type . '_address_2'}('');
            $order->{'set_' . $type . '_state'}($selection['state']);
            $order->{'set_' . $type . '_postcode'}('');
            $order->{'set_' . $type . '_country'}('UA');
        }
        $order->update_meta_data('wcus_data_version', '3');
        // Store API saves the order before firing its order-processed hook.
    }

    public function postcodeOptional(array $locale): array
    {
        $rates = WC()->session ? (array) WC()->session->get('chosen_shipping_methods', []) : [];
        foreach ($rates as $rate) {
            if (strpos((string) $rate, 'nova_poshta_shipping') === 0) {
                $locale['UA']['postcode']['required'] = false;
                break;
            }
        }
        return $locale;
    }
}
