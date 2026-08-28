<?php

if (!defined('ABSPATH')) {
    exit;
}

// Shipping methods
define('WCUS_SHIPPING_METHOD_NOVA_POSHTA', 'nova_poshta_shipping');
define('WCUS_SHIPPING_METHOD_UKRPOSHTA', 'wcus_ukrposhta_shipping');
define('WCUS_SHIPPING_METHOD_UKRPOSHTA_ADDRESS', 'wcus_ukrposhta_address_shipping');
define('WCUS_SHIPPING_METHOD_NOVA_POST', 'wcus_nova_post_shipping');
define('WCUS_SHIPPING_METHOD_ROZETKA', 'wcus_rozetka_delivery_shipping');
define('WCUS_SHIPPING_METHOD_MEEST', 'wcus_meest_shipping');
define('WCUS_SHIPPING_METHOD_MEEST_ADDRESS', 'wcus_meest_address_shipping');
define('WCUS_SHIPPING_METHOD_NOVA_GLOBAL_ADDRESS', 'wcus_nova_global_address');
define('WCUS_SHIPPING_METHOD_POST_NORD', 'wcus_post_nord_shipping');
define('WCUS_SHIPPING_METHOD_POST_NORD_ADDRESS', 'wcus_post_nord_address');

// Shipping item meta
define('WCUS_SHIPPING_META_VIEW_COST', 'wcus_view_cost');

// Warehouse Type
define('WCUS_WAREHOUSE_TYPE_REGULAR', 1);
define('WCUS_WAREHOUSE_TYPE_CARGO', 2);
define('WCUS_WAREHOUSE_TYPE_POSHTOMAT', 3);

// Shipment defaults, used when the order data is not enough to calculate rates
define('WCUS_DEFAULT_PARCEL_WEIGHT', 1);
define('WCUS_DEFAULT_PARCEL_WIDTH', 10);
define('WCUS_DEFAULT_PARCEL_HEIGHT', 10);
define('WCUS_DEFAULT_PARCEL_LENGTH', 10);
define('WCUS_DEFAULT_COD_PAYMENT_METHOD', 'cod');

// Options
define('WCUS_OPTION_SAVE_CUSTOMER_ADDRESS', 'wc_ukr_shipping_np_save_warehouse');
define('WCUS_OPTION_SHOW_POSHTOMATS', 'wcus_show_poshtomats');
define('WCUS_OPTION_LOADER_LAST_SYNC', 'wcus_loader_last_sync');
define('WCUS_OPTION_SMARTY_PARCEL_API_KEY', 'wcus_smartyparcel_api_key');
define('WCUS_OPTION_SMARTY_PARCEL_USER_STATUS', 'wcus_smartyparcel_user_status');
define('WCUS_OPTION_SMARTY_PARCEL_CARRIERS', 'wcus_smartyparcel_carriers');
define('WCUS_OPTION_INSTALLATION_ID', 'wcus_app_installation_id');