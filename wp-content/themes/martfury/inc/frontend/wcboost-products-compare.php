<?php

/**
 * Class for all wcboost products compare template modification
 *
 * @version 1.0
 */

function remove_compare_button() {
	// Compare button.
	if( class_exists('\WCBoost\ProductsCompare\Frontend') ) {
		remove_action( 'woocommerce_after_add_to_cart_form', [ \WCBoost\ProductsCompare\Frontend::instance(), 'single_add_to_compare_button' ] );
		remove_action( 'woocommerce_after_shop_loop_item', [ \WCBoost\ProductsCompare\Frontend::instance(), 'loop_add_to_compare_button' ], 15 );
	}

}

add_action( 'wp', 'remove_compare_button' );

/**
 * Update a single cart item.
 *
 * @since 1.0.0
 *
 * @return void
 */
function products_compare_button_icon( $svg, $icon ) {
	$original_icon = get_option( 'wcboost_products_compare_button_icon', 'arrows' );


	if ( $icon === 'check' && $original_icon === 'arrows' ) {
		$svg = '<svg viewBox="0 0 32 32"><path d="M28 32h-25.6c-1.323 0-2.4-1.077-2.4-2.4v-25.6c0-1.323 1.077-2.4 2.4-2.4h25.6c1.323 0 2.4 1.077 2.4 2.4v25.6c0 1.323-1.077 2.4-2.4 2.4zM2.4 3.2c-0.442 0-0.8 0.358-0.8 0.8v25.6c0 0.442 0.358 0.8 0.8 0.8h25.6c0.442 0 0.8-0.358 0.8-0.8v-25.6c0-0.442-0.358-0.8-0.8-0.8h-25.6zM10.4 27.2h-3.2c-0.442 0-0.8-0.358-0.8-0.8v-14.4c0-0.442 0.358-0.8 0.8-0.8h3.2c0.442 0 0.8 0.358 0.8 0.8v14.4c0 0.442-0.358 0.8-0.8 0.8zM8 25.6h1.6v-12.8h-1.6v12.8zM16.8 27.2h-3.2c-0.442 0-0.8-0.358-0.8-0.8v-19.2c0-0.442 0.358-0.8 0.8-0.8h3.2c0.442 0 0.8 0.358 0.8 0.8v19.2c0 0.442-0.358 0.8-0.8 0.8zM14.4 25.6h1.6v-17.6h-1.6v17.6zM23.2 27.2h-3.2c-0.442 0-0.8-0.358-0.8-0.8v-8c0-0.442 0.358-0.8 0.8-0.8h3.2c0.442 0 0.8 0.358 0.8 0.8v8c0 0.442-0.358 0.8-0.8 0.8zM20.8 25.6h1.6v-6.4h-1.6v6.4z"></path></svg>';
	} elseif ( $icon === 'arrows' ) {
		$svg = '<svg viewBox="0 0 32 32"><path d="M28 32h-25.6c-1.323 0-2.4-1.077-2.4-2.4v-25.6c0-1.323 1.077-2.4 2.4-2.4h25.6c1.323 0 2.4 1.077 2.4 2.4v25.6c0 1.323-1.077 2.4-2.4 2.4zM2.4 3.2c-0.442 0-0.8 0.358-0.8 0.8v25.6c0 0.442 0.358 0.8 0.8 0.8h25.6c0.442 0 0.8-0.358 0.8-0.8v-25.6c0-0.442-0.358-0.8-0.8-0.8h-25.6zM10.4 27.2h-3.2c-0.442 0-0.8-0.358-0.8-0.8v-14.4c0-0.442 0.358-0.8 0.8-0.8h3.2c0.442 0 0.8 0.358 0.8 0.8v14.4c0 0.442-0.358 0.8-0.8 0.8zM8 25.6h1.6v-12.8h-1.6v12.8zM16.8 27.2h-3.2c-0.442 0-0.8-0.358-0.8-0.8v-19.2c0-0.442 0.358-0.8 0.8-0.8h3.2c0.442 0 0.8 0.358 0.8 0.8v19.2c0 0.442-0.358 0.8-0.8 0.8zM14.4 25.6h1.6v-17.6h-1.6v17.6zM23.2 27.2h-3.2c-0.442 0-0.8-0.358-0.8-0.8v-8c0-0.442 0.358-0.8 0.8-0.8h3.2c0.442 0 0.8 0.358 0.8 0.8v8c0 0.442-0.358 0.8-0.8 0.8zM20.8 25.6h1.6v-6.4h-1.6v6.4z"></path></svg>';
	} elseif ( $icon === 'check' ) {
        $svg = \WCBoost\ProductsCompare\Helper::get_icon( $original_icon );
    }

	return $svg;
}

add_filter( 'wcboost_products_compare_button_icon', 'products_compare_button_icon', 10, 2 );

/**
 * Show button compare.
 *
 * @since 1.0.0
 *
 * @return void
 */
function products_compare_button_template_args( $args, $product ) {
	$args['class'][] = 'compare-button';

	return $args;
}

add_filter( 'wcboost_products_compare_button_template_args', 'products_compare_button_template_args', 10, 2 );

/**
 * Ajaxify update count compare
 *
 * @since 1.0
 *
 * @param array $fragments
 *
 * @return array
 */
function products_compare_add_to_compare_fragments( $data ) {
	$data['.menu-item-compare .mini-item-counter'] = '<span class="mini-item-counter mf-background-primary" id="mini-compare-counterr">'. \WCBoost\ProductsCompare\Plugin::instance()->list->count_items() . '</span>';

	return $data;
}

add_filter( 'wcboost_products_compare_add_to_compare_fragments', 'products_compare_add_to_compare_fragments', 10, 1 );