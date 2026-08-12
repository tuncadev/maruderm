<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if( ! class_exists('Martfury_Widget_Products_On_Sale') ) {


	/**
	 * Layered Navigation Widget.
	 *
	 * @author   WooThemes
	 * @category Widgets
	 * @package  WooCommerce/Widgets
	 * @version  2.6.0
	 * @extends  WC_Widget
	 */
	class Martfury_Widget_Products_On_Sale extends WC_Widget {

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->widget_cssclass    = 'woocommerce mf-widget-layered-nav woocommerce-widget-layered-nav';
			$this->widget_description = esc_html__( 'Display products on sale.', 'martfury-addons' );
			$this->widget_id          = 'mf_products_on_sale';
			$this->widget_name        = esc_html__( 'Martfury Products on Sale', 'martfury-addons' );
			parent::__construct();
		}

		/**
		 * Updates a particular instance of a widget.
		 *
		 * @see WP_Widget->update
		 *
		 * @param array $new_instance
		 * @param array $old_instance
		 *
		 * @return array
		 */
		public function update( $new_instance, $old_instance ) {
			$this->init_settings();

			return parent::update( $new_instance, $old_instance );
		}

		/**
		 * Outputs the settings update form.
		 *
		 * @see WP_Widget->form
		 *
		 * @param array $instance
		 */
		public function form( $instance ) {
			$this->init_settings();
			parent::form( $instance );
		}

		/**
		 * Init settings after post types are registered.
		 */
		public function init_settings() {
			$attribute_array      = array();
			$attribute_taxonomies = wc_get_attribute_taxonomies();

			if ( ! empty( $attribute_taxonomies ) ) {
				foreach ( $attribute_taxonomies as $tax ) {
					if ( taxonomy_exists( wc_attribute_taxonomy_name( $tax->attribute_name ) ) ) {
						$attribute_array[ $tax->attribute_name ] = $tax->attribute_name;
					}
				}
			}

			$this->settings = array(
				'title'   => array(
					'type'  => 'text',
					'std'   => esc_html__( 'Sale', 'martfury-addons' ),
					'label' => esc_html__( 'Title', 'martfury-addons' ),
				),
				'text'  => array(
					'type'  => 'text',
					'std'   => esc_html__( 'On Sale', 'martfury-addons' ),
					'label' => esc_html__( 'Text', 'martfury-addons' ),
				),
			);
		}

		/**
		 * Output widget.
		 *
		 * @see WP_Widget
		 *
		 * @param array $args
		 * @param array $instance
		 */
		public function widget( $args, $instance ) {
			if ( ! martfury_is_catalog() ) {
				return;
			}

			ob_start();

			$this->widget_start( $args, $instance );

			$link = $this->get_page_base_url();

			$option_is_set = isset( $_GET['on_sale'] ) ? 1 : 0;

			if ( ! $option_is_set ) {
				$link = add_query_arg( 'on_sale', '1', $link );
			}

			echo '<ul class="woocommerce-widget-layered-nav-list">';
			echo '<li class="woocommerce-widget-layered-nav-list__item wc-layered-nav-term ' . ( $option_is_set ? 'woocommerce-widget-layered-nav-list__item--chosen chosen' : '' ) . '">';
			echo '<a href="' . esc_url( $link ) . '">' . esc_html( $instance['text'] ) . '</a>';
			echo '</li>';

			$this->widget_end( $args );

			echo $this->cache_widget( $args, ob_get_clean() );
		}

		/**
		 * Get current page URL for layered nav items.
		 *
		 * @param string $taxonomy
		 *
		 * @return string
		 */
		protected function get_page_base_url() {
			if ( defined( 'SHOP_IS_ON_FRONT' ) ) {
				$link = home_url();
			} elseif ( is_post_type_archive( 'product' ) || is_page( wc_get_page_id( 'shop' ) ) ) {
				$link = get_post_type_archive_link( 'product' );
			} elseif ( is_product_category() ) {
				$link = get_term_link( get_query_var( 'product_cat' ), 'product_cat' );
			} elseif ( is_product_tag() ) {
				$link = get_term_link( get_query_var( 'product_tag' ), 'product_tag' );
			} else {
				$queried_object = get_queried_object();
				$link           = get_term_link( $queried_object->slug, $queried_object->taxonomy );
			}

			// Min/Max
			if ( isset( $_GET['min_price'] ) ) {
				$link = add_query_arg( 'min_price', wc_clean( $_GET['min_price'] ), $link );
			}

			if ( isset( $_GET['max_price'] ) ) {
				$link = add_query_arg( 'max_price', wc_clean( $_GET['max_price'] ), $link );
			}

			// Order by
			if ( isset( $_GET['orderby'] ) ) {
				$link = add_query_arg( 'orderby', wc_clean( $_GET['orderby'] ), $link );
			}

			/**
			 * Search Arg.
			 * To support quote characters, first they are decoded from &quot; entities, then URL encoded.
			 */
			if ( get_search_query() ) {
				$link = add_query_arg( 's', rawurlencode( htmlspecialchars_decode( get_search_query() ) ), $link );
			}

			// Post Type Arg
			if ( isset( $_GET['post_type'] ) ) {
				$link = add_query_arg( 'post_type', wc_clean( $_GET['post_type'] ), $link );
			}

			// Min Rating Arg
			if ( isset( $_GET['rating_filter'] ) ) {
				$link = add_query_arg( 'rating_filter', wc_clean( $_GET['rating_filter'] ), $link );
			}

			// All current filters
			if ( $_chosen_attributes = WC_Query::get_layered_nav_chosen_attributes() ) {
				foreach ( $_chosen_attributes as $name => $data ) {
					$filter_name = sanitize_title( str_replace( 'pa_', '', $name ) );
					if ( ! empty( $data['terms'] ) ) {
						$link = add_query_arg( 'filter_' . $filter_name, implode( ',', $data['terms'] ), $link );
					}
					if ( 'or' == $data['query_type'] ) {
						$link = add_query_arg( 'query_type_' . $filter_name, 'or', $link );
					}
				}
			}

			return $link;
		}
	}
}