<?php
include 'keycrm-funcs.php';
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

function get_wc_shipping_methods_by_zones($enhanced = false) {
    $result = array();

    $shippingZones = WC_Shipping_Zones::get_zones();
    $defaultZone = WC_Shipping_Zones::get_zone_by();

    $shippingZones[$defaultZone->get_id()] = array(
        $defaultZone->get_data(),
        'zone_id' => $defaultZone->get_id(),
        'formatted_zone_location' => $defaultZone->get_formatted_location(),
        'shipping_methods' => $defaultZone->get_shipping_methods(false)
    );

    if ($shippingZones) {
        foreach ($shippingZones as $code => $shippingZone) {
            foreach ($shippingZone['shipping_methods'] as $key => $shipping_method) {
                $shipping_methods = array(
                    'id' => $shipping_method->id,
                    'instance_id' => $shipping_method->instance_id,
                    'title' => $shipping_method->title
                );

                if ($enhanced) {
                    $shipping_code = $shipping_method->id;
                } else {
                    $shipping_code = $shipping_method->id . ':' . $shipping_method->instance_id;
                }

                if (!isset($result[$shipping_code])) {
                    $result[$shipping_code] = array(
                        'name' => $shipping_method->method_title,
                        'enabled' => $shipping_method->enabled,
                        'description' => $shipping_method->method_description,
                        'title' => $shipping_method->title
                    );
                }

                if ($enhanced) {
                    $result[$shipping_method->id]['shipping_methods'][$shipping_method->id . ':' . $shipping_method->instance_id] = $shipping_methods;
                    unset($shipping_methods);
                }
            }
        }
    }

    return $result;
}

function get_wc_shipping_methods() {
    $zones = WC_Shipping_Zones::get_zones('zone_id',0);
    $result = array();

    $rest_of_world_zone = WC_Shipping_Zones::get_zone_by();
    if ($rest_of_world_zone) {
        $zones[0] = array(
            'zone_id' => 0,
            'shipping_methods' => $rest_of_world_zone->get_shipping_methods(true)
        );
    }

    foreach ($zones as $zone) {
        if (isset($zone['shipping_methods'])) {
            foreach ($zone['shipping_methods'] as $shipping_method) {
                $method_id = $shipping_method->id;
                $instance_id = $shipping_method->instance_id;

                $result[$method_id] = array(
                    'name' => $shipping_method->method_title,
                    'enabled' => $shipping_method->enabled == 'yes',
                    'description' => $shipping_method->method_description,
                    'title' => $shipping_method->title ? $shipping_method->title : $shipping_method->method_title,
                );
            }
        }
    }

    return apply_filters('keycrm_shipping_list', WC_Keycrm_Plugin::clearArray($result));
}
function keycrm_get_delivery_service($method_id, $instance_id) {
    $shippings_by_zone = get_wc_shipping_methods_by_zones(true);
    $method = explode(':', $method_id);
    $method_id = $method[0];
    $shipping = isset($shippings_by_zone[$method_id]) ? $shippings_by_zone[$method_id] : array();

    if ($shipping && isset($shipping['shipping_methods'][$method_id . ':' . $instance_id])) {
        return $shipping['shipping_methods'][$method_id . ':' . $instance_id];
    }

    return false;
}

/**
 * @param $id
 * @param $settings
 *
 * @return false|WC_Product|null
 */
function keycrm_get_wc_product($id, $settings) {
    if (isset($settings['bind_by_sku'])
        && $settings['bind_by_sku'] == WC_Keycrm_Base::YES
    ) {
        $id = wc_get_product_id_by_sku($id);
    }

    return wc_get_product($id);
}

/**
 * Returns true if either wordpress debug mode or module debugging is enabled
 *
 * @return bool
 */
function keycrm_is_debug() {
    return (defined('WP_DEBUG') && WP_DEBUG == true)
        || (defined('RCRM_DEBUG') && RCRM_DEBUG == true);
}

/**
 *  Returns true if current page equals wp-login
 *
 * @return bool
 */
function is_wplogin()
{
    $ABSPATH_MY = str_replace(array('\\','/'), DIRECTORY_SEPARATOR, ABSPATH);

    return ((in_array($ABSPATH_MY.'wp-login.php', get_included_files())
        || in_array($ABSPATH_MY.'wp-register.php', get_included_files()))
        || (isset($_GLOBALS['pagenow']) && $GLOBALS['pagenow'] === 'wp-login.php')
        || $_SERVER['PHP_SELF']== '/wp-login.php'
    );
}
