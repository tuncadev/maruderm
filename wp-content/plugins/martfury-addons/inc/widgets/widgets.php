<?php
/**
 * Load and register widgets
 *
 * @package Martfury
 */

/**
 * Register widgets
 *
 * @since  1.0
 *
 * @return void
 */

if ( ! function_exists( 'martfury_register_widgets' ) ) {
	function martfury_register_widgets() {
		require_once MARTFURY_ADDONS_DIR . '/inc/widgets/social-media-links.php';
		require_once MARTFURY_ADDONS_DIR . '/inc/widgets/account.php';
		require_once MARTFURY_ADDONS_DIR . '/inc/widgets/search.php';

		register_widget( 'Martfury_Social_Links_Widget' );
		register_widget( 'Martfury_Account' );
		register_widget( 'Martfury_Search' );

		if ( class_exists( 'WC_Widget' ) ) {
			require_once MARTFURY_ADDONS_DIR . '/inc/widgets/product-categories.php';
			require_once MARTFURY_ADDONS_DIR . '/inc/widgets/widget-layered-nav.php';
			require_once MARTFURY_ADDONS_DIR . '/inc/widgets/widget-brands-nav.php';
			require_once MARTFURY_ADDONS_DIR . '/inc/widgets/widget-rating-filter.php';
			require_once MARTFURY_ADDONS_DIR . '/inc/widgets/widget-layered-nav-filters.php';
			require_once MARTFURY_ADDONS_DIR . '/inc/widgets/widget-products.php';
			require_once MARTFURY_ADDONS_DIR . '/inc/widgets/currencies.php';
			require_once MARTFURY_ADDONS_DIR . '/inc/widgets/product-tag.php';
			require_once MARTFURY_ADDONS_DIR . '/inc/widgets/products-on-sale.php';


			register_widget( 'Martfury_Widget_Product_Categories' );
			register_widget( 'Martfury_Widget_Layered_Nav' );
			register_widget( 'Martfury_Widget_Brands_Nav' );
			register_widget( 'Martfury_Widget_Rating_Filter' );
			register_widget( 'Martfury_Widget_Layered_Nav_Filters' );
			register_widget( 'Martfury_Widget_Products' );
			register_widget( 'Martfury_Currencies_Widget' );
			register_widget( 'Martfury_Widget_Product_Tag_Cloud' );
			register_widget( 'Martfury_Widget_Products_On_Sale' );

			if ( class_exists( 'WCV_Vendors' ) || class_exists( 'WCMp' ) ) {
				require_once MARTFURY_ADDONS_DIR . '/inc/widgets/wc-vendor-info.php';
				register_widget( 'Martfury_Widget_WC_Vendor_Info' );
			}
		}
	}

	add_action( 'widgets_init', 'martfury_register_widgets' );
}

/**
 * Get product categories by vendor
 *
 * @since  1.0
 *
 * @return array
 */
function martfury_get_categories_by_vendor( $taxonomy = 'product_cat' ) {
	global $wpdb;
	$author_id = get_query_var( 'author' );
	if ( empty( $author_id ) ) {
		return;
	}
	$product_ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type ='product' AND post_author = '%s';", wc_clean( $author_id ) ) );

	if ( ! $product_ids ) {
		return;
	}

	$term_list = array();
	foreach ( $product_ids as $id ) {
		$term_ids  = wp_get_post_terms( $id, $taxonomy, array( 'fields' => 'ids' ) );
		$term_list = array_merge( $term_list, $term_ids );
	}

	return $term_list;
}

function martfury_categories_count_span( $links ) {
	$links = str_replace( '</a> (', '</a><span class="count">(', $links );
	$links = str_replace( ')', ')</span>', $links );
	return $links;
}
add_filter( 'wp_list_categories', 'martfury_categories_count_span' );