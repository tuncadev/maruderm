<?php
/**
 * Template for displaying the add-to-compare button on the single product page.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/add-to-compare.php.
 *
 * @author  WCBoost
 * @package WCBoost\ProductsCompare\Templates
 * @version 1.0.5
 */

defined( 'ABSPATH' ) || exit;

global $product;

if( empty( $product ) || ! is_object( $product ) ) {
	return;
}

echo apply_filters(
	'wcboost_products_compare_loop_add_to_compare_link', // WPCS: XSS ok.
	sprintf(
		'<a href="%s" data-product_id="%d" class="%s" aria-label="%s" role="button" title="%s" data-rel="tooltip" data-product-title="%s">
			%s
			<span class="wcboost-products-compare-button__text">%s</span>
		</a>',
		esc_url( isset( $args['url'] ) ? $args['url'] : add_query_arg( [ 'add-to-compare' => $product->get_id() ] ) ),
		esc_attr( isset( $args['product_id'] ) ? $args['product_id'] : $product->get_id() ),
		esc_attr( isset( $args['class'] ) ? $args['class'] : 'wcboost-products-compare-button wcboost-products-compare-button--loop button' ),
		esc_attr( isset( $args['aria-label'] ) ? $args['aria-label'] : sprintf( __( 'Compare %s', 'martfury' ), '&ldquo;' . $product->get_title() . '&rdquo;' ) ),
		esc_html( isset( $args['label'] ) ? $args['label'] : __( 'Compare', 'martfury' ) ),
		$product->get_title(),
		empty( $args['icon'] ) ? '' : '<span class="wcboost-products-compare-button__icon">' . $args['icon'] . '</span>',
		esc_html( isset( $args['label'] ) ? $args['label'] : __( 'Compare', 'martfury' ) )
	),
	$args
);
