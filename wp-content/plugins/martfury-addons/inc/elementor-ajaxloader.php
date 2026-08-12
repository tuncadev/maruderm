<?php

namespace MartfuryAddons;

use Elementor\Group_Control_Image_Size;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Elementor_AjaxLoader {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'wc_ajax_mf_elementor_get_elements', [ $this, 'elementor_get_elements' ] );
	}

	public static function elementor_get_elements() {
		$output = '';

		if ( isset( $_POST['params'] ) && ! empty( $_POST['params'] ) ) {
			$params   = json_decode( stripslashes( $_POST['params'] ), true );
			$settings = array();
			foreach ( $params as $key => $value ) {
				$settings[ $key ] = $value;
			}

			$els = '';
			if ( isset( $_POST['element'] ) && ! empty( $_POST['element'] ) ) {
				$els = $_POST['element'];
			}

			if ( $els == 'productsOfCat' ) {
				ob_start();
				self::get_products_of_category( $settings );
				$output = ob_get_clean();
			} elseif ( $els == 'productsOfCat2' ) {
				ob_start();
				self::get_products_of_category_2( $settings );
				$output = ob_get_clean();
			} elseif ( $els == 'productsTabsCarousel' ) {
				ob_start();
				self::get_product_tabs_handler( $settings );
				$output = ob_get_clean();
			} elseif ( $els == 'productsTabsGrid' ) {
				ob_start();
				self::get_product_tabs_handler( $settings );
				$output = ob_get_clean();
			} elseif ( $els == 'productsCarousel' ) {
				ob_start();
				self::get_product_grid_handler( $settings );
				$output = ob_get_clean();
			} elseif ( $els == 'productsGrid' ) {
				ob_start();
				self::get_product_grid_handler( $settings );
				$output = ob_get_clean();
			}
		}

		wp_send_json_success( $output );
		die();
	}

	public static function get_products_of_category( $settings ) {
		$title = self::get_link_control( $settings['c_link'], $settings['title'], 'cat-title' );
		$title_html = sprintf( '<%1$s class="cats-inner__heading">%2$s</%1$s>', \Elementor\Utils::validate_html_tag( $settings['title_size'] ),  $title  );
		?>
        <div class="cats-info">
            <div class="cats-inner">
				<?php echo $title_html; ?>
				<?php if ( isset($settings['quick_links']) && $settings['quick_links'] != 'none' ) : ?>
                    <ul class="extra-links">
						<?php
						$links = $settings['links_group'];
						if ( $links ) {
							foreach ( $links as $index => $item ) {
								echo sprintf( '<li>%s</li>', self::get_link_control( $item['link_url'], $item['link_text'], 'extra-link' ) );
							}
						}

						?>
                    </ul>
				<?php endif; ?>
            </div>
            <div class="footer-link">
				<?php echo self::get_link_control( $settings['view_all_link'], $settings['view_all_text'], 'link' ) ?>
            </div>
        </div>
		<?php if ( $settings['banners_carousel'] == 'yes' ) : ?>
            <div class="images-slider">
                <div class="images-list">
					<?php
					$banners = $settings['banners'];
					if ( $banners ) {
						foreach ( $banners as $index => $item ) {
							if ( empty( $item['image'] ) ) {
								continue;
							}
							$settings['image']      = $item['image'];
							$settings['image_size'] = 'full';
							$image_url              = Group_Control_Image_Size::get_attachment_image_html( $settings );
							echo self::get_link_control( $item['image_link'], $image_url, 'image-item' );
						}
					}
					?>
                </div>
            </div>
		<?php endif; ?>
        <div class="products-box">
			<?php
			echo Elementor::get_products( $settings );
			?>
        </div>
		<?php
	}

	public static function get_products_of_category_2( $settings ) {
		$output = [];

		// Cat HTML
		$cats_html = [];

		$icon = '';

		if ( $settings['icon_type'] == 'icons' ) {
			if ( $settings['icon'] ) {
				$icon = '<span class="mf-icon"><i class="' . esc_attr( $settings['icon'] ) . '"></i></span>';
			}
		} elseif ( $settings['icon_type'] == 'custom_icons' ) {
			if ( $settings['custom_icon'] && \Elementor\Icons_Manager::is_migration_allowed() ) {
				ob_start();
				\Elementor\Icons_Manager::render_icon( $settings['custom_icon'], [ 'aria-hidden' => 'true' ] );
				$icon = '<span class="mf-icon">' . ob_get_clean() . '</span>';
			}
		}

		if ( $settings['title'] ) {
			$title       = $icon . $settings['title'];
			$cats_html[] = sprintf( '<%1$s class="cats-inner__heading">%2$s</%1$s>',\Elementor\Utils::validate_html_tag( $settings['title_size'] ), self::get_link_control( $settings['link'], $title, 'cat-title' ) );
		}

		$links_group = $settings['links_group'];

		if ( ! empty ( $links_group ) ) {
			$cats_html[] = '<ul class="extra-links">';
			foreach ( $links_group as $index => $item ) {
				$link = $item['link_text'] ? self::get_link_control( $item['link_url'], $item['link_text'], 'extra-link' ) : '';

				$cats_html[] = sprintf( '<li>%s</li>', $link );
			}
			$cats_html[] = '</ul>';
		}

		// Banner Carousel
		$banners        = $settings['banners'];
		$banners_output = [];

		if ( ! empty ( $banners ) ) {
			foreach ( $banners as $index => $item ) {
				$settings['image']      = $item['image'];
				$settings['image_size'] = 'full';
				$btn_image              = Group_Control_Image_Size::get_attachment_image_html( $settings );

				$link = self::get_link_control( $item['image_link'], $btn_image, 'image-item' );

				$banners_output[] = sprintf( '%s', $link );
			}
		}

		$output[]              = sprintf( '<div class="cats-header">%s</div>', implode( ' ', $cats_html ) );
		$product_content_class = $settings['side_products_hide_desktop'] != 'yes' ? 'col-md-9 has-side-product' : 'col-md-12';

		$output[] = '<div class="products-cat row">';
		$output[] = '<div class="' . $product_content_class . ' col-sm-12 col-xs-12 col-product-content">';

		$carousel_settings = [
			'infinite'       => $settings['banners_infinite'],
			'autoplay'       => $settings['banners_autoplay'],
			'autoplay_speed' => $settings['banners_autoplay_speed'],
			'speed'          => $settings['banners_speed'],
			'arrows'         => $settings['banners_arrows'],
		];

		$output[] = sprintf( '<div class="images-slider" data-settings="%s"><div class="images-list">%s</div></div>', esc_attr( wp_json_encode( $carousel_settings ) ), implode( ' ', $banners_output ) );

		$output[] = self::get_product_tabs( $settings );

		$output[] = '</div>'; // .col-product-content


		if ( $settings['side_products_hide_desktop'] != 'yes' ) {
			$output[]     = '<div class="col-md-3 col-sm-12 col-xs-12 side-products">';
			$side_classes = array();
			if ( $settings['side_products_hide_tablet'] == 'yes' ) {
				$side_classes[] = 'elementor-hidden-tablet';
			}

			if ( $settings['side_products_hide_mobile'] == 'yes' ) {
				$side_classes[] = 'elementor-hidden-phone';
			}

			$output[] = sprintf( '<div class="products-side %s">', implode( ' ', $side_classes ) );
			if ( $settings['side_title'] ) {
				$output[] = sprintf( '<h2 class="side-title">%s</h2>', $settings['side_title'] );
			}

			$atts = [
				'per_page' => $settings['side_per_page'],
				'type'     => $settings['side_products'],
				'order'    => $settings['side_product_order'],
				'orderby'  => $settings['side_product_orderby'],
				'category' => is_array( $settings['side_product_cats'] ) ? implode( ',', $settings['side_product_cats'] ) : '',
			];

			$output[] = self::get_products( $atts );

			if ( $settings['side_link_text'] ) {
				$output[] = sprintf( '%s', self::get_link_control( $settings['side_link_url'], $settings['side_link_text'], 'link' ) );
			}

			$output[] = '</div>';
			$output[] = '</div>'; // .side-product
		}


		$output[] = '</div>'; // .products-cat

		echo implode( ' ', $output );
	}

	public static function get_product_tabs_handler( $settings ) {
		$output      = [];
		$header_tabs = [];
		$text_all = '';

		if( $settings['heading_type'] == 'layout-2' && $settings['product_tabs_view_all_tab'] == 'yes' ) {
			$text_all = ! empty( $settings['product_tabs_text_all'] ) ? $settings['product_tabs_text_all'] : esc_html( 'All', 'martfury-addons' );
		}

		if ( ! empty( $settings['title'] ) ) {
			$header_tabs[] = sprintf( '<%1$s class="tabs-cat__heading">%2$s</%1$s>', \Elementor\Utils::validate_html_tag( $settings['title_size'] ), self::get_link_control( $settings['link'], $settings['title'], 'cat-title' ) );
		}


		$tab_content = [];

		$header_tabs[] = '<div class="tabs-header-nav">';

		$header_tabs[] = '<ul class="tabs-nav">';

		if ( $settings['product_tabs_source'] == 'special_products' ) {
			$tabs = $settings['special_products_tabs'];
			$i           = 0;

			if ( $tabs ) {
				foreach ( $tabs as $index => $item ) {

					$class_active = $i == 0 ? 'active' : '';

					if ( isset( $item['title'] ) ) {
						$header_tabs[] = sprintf( '<li><a href="#" data-href="%s" class="%s">%s</a></li>', esc_attr( $item['tab_products'] ), esc_attr( $class_active ), esc_html( $item['title'] ) );
					}

					$tab_atts = array(
						'columns'      => intval( $settings['columns'] ),
						'products'     => $item['tab_products'],
						'order'        => ! empty( $item['tab_order'] ) ? $item['tab_order'] : '',
						'orderby'      => ! empty( $item['tab_orderby'] ) ? $item['tab_orderby'] : '',
						'per_page'     => intval( $settings['per_page'] ),
						'product_cats' => $settings['product_cats'],
					);

					if ( $i == 0 ) {
						$tab_content[] = sprintf( '<div class="tabs-panel tabs-%s tab-loaded active">%s</div>', esc_attr( $item['tab_products'] ), Elementor::get_products( $tab_atts ) );
					} else {
						if ( $settings['lazy_loading'] == 'yes' ) {
							$tab_content[] = sprintf( '<div class="tabs-panel tabs-%s tab-loaded">%s</div>', esc_attr( $item['tab_products'] ), Elementor::get_products( $tab_atts ) );
						} else {
							$tab_content[] = sprintf(
								'<div class="tabs-panel tabs-%s" data-settings="%s"><div class="mf-vc-loading"><div class="mf-vc-loading--wrapper"></div></div></div>',
								esc_attr( $item['tab_products'] ),
								esc_attr( wp_json_encode( $tab_atts ) )
							);
						}

					}

					$i ++;
				}
			}
		} else {
			$cats = $settings['product_cats_tabs'];
			$i           = 1;

			if ( $cats ) {

				if( ! empty($text_all) ) {
					$header_tabs[] = sprintf( '<li><a href="#" class="active" data-href="product_cat_0">%s</a></li>', $text_all );
				}

				$tab_atts = array(
					'columns'      => intval( $settings['columns'] ),
					'products'     => $settings['products'],
					'order'        => $settings['order'],
					'orderby'      => $settings['orderby'],
					'per_page'     => intval( $settings['per_page'] ),
				);
				if( ! empty($text_all) ) {
					$tab_content[] = sprintf( '<div class="tabs-panel tabs-product_cat_0 tab-loaded active">%s</div>', Elementor::get_products( $tab_atts ) );
				}

				foreach ( $cats as $tab ) {
					$term = get_term_by( 'slug', $tab['product_cat'], 'product_cat' );
					$term_class = 'product_cat_' . $i;
					$class_active = $i == 1 && empty($text_all) ? 'active' : '';
					if ( ! is_wp_error( $term ) && $term ) {
						$header_tabs[] = sprintf( '<li><a href="#" data-href="%s" class="%s">%s</a></li>', esc_attr( $term_class ), esc_attr($class_active), esc_html( $term->name ) );
					}

					$tab_atts = array(
						'columns'      => intval( $settings['columns'] ),
						'products'     => $settings['products'],
						'order'        => $settings['order'],
						'orderby'      => $settings['orderby'],
						'per_page'     => intval( $settings['per_page'] ),
						'product_cats' => $tab['product_cat'],
					);
					if ( $i == 1 && empty($text_all) ) {
						$tab_content[] = sprintf( '<div class="tabs-panel tabs-%s tab-loaded active">%s</div>', esc_attr($term_class ), Elementor::get_products( $tab_atts ) );
					} else {
						if ( $settings['lazy_loading'] == 'yes' ) {
							$tab_content[] = sprintf( '<div class="tabs-panel tabs-%s tab-loaded">%s</div>', esc_attr( $term_class ), Elementor::get_products( $tab_atts ) );
						} else {
							$tab_content[] = sprintf(
								'<div class="tabs-panel tabs-%s" data-settings="%s"><div class="mf-vc-loading"><div class="mf-vc-loading--wrapper"></div></div></div>',
								esc_attr( $term_class ),
								esc_attr( wp_json_encode( $tab_atts ) )
							);
						}
					}

					$i ++;

				}
			}

		}

		$header_tabs[] = '</ul>';
		$view_all = '';
		if ( ! empty( $settings['view_all_text'] ) ) {
			if( ! empty( $settings['view_all_icon'] ) ) {
				$view_all = self::get_link_control( $settings['all_link'], $settings['view_all_text'], 'link has-icon' );
			} else {
				$view_all = self::get_link_control( $settings['all_link'], $settings['view_all_text'], 'link' );
			}
		}
		if ( $settings['heading_type'] == 'layout-1' ) {
			$header_tabs[] = $view_all;
		}

		$header_tabs[] = '</div>';

		if ( $settings['heading_type'] == 'layout-2' ) {
			$header_tabs[] = $view_all;
		}

		$output[] = sprintf( '<div class="tabs-header %s">%s</div>', esc_attr( $settings['heading_type'] ), implode( ' ', $header_tabs ) );
		$output[] = sprintf( '<div class="tabs-content">%s</div>', implode( ' ', $tab_content ) );

		echo implode( '', $output );
	}

	public static function get_product_grid_handler( $settings ) {
		$output = array();

		$output[] = '<div class="cat-header">';
		if ( ! empty( $settings['title'] ) ) {
			$output[] = sprintf( '<%1$s class="cat-title">%2$s</%1$s>', \Elementor\Utils::validate_html_tag( $settings['title_size'] ), self::get_link_control( $settings['link'], $settings['title'], '' ) );
		}


		$link_group = $settings['link_group'];


		$output[] = '<ul class="extra-links">';
		if ( ! empty ( $link_group ) ) {
			foreach ( $link_group as $index => $item ) {

				$link = self::get_link_control( $item['link'], $item['title'], 'extra-link' );

				$output[] = sprintf( '<li>%s</li>', $link );
			}
		}

		if ( ! empty( $settings['view_all_text'] ) ) {

			if( ! empty( $settings['view_all_icon'] ) ) {
				$output[] = '<li class="view-all-link has-icon">' . self::get_link_control( $settings['view_all_link'], $settings['view_all_text'], 'all-link' ) . '</li>';
			} else {
				$output[] = '<li class="view-all-link">' . self::get_link_control( $settings['view_all_link'], $settings['view_all_text'], 'all-link' ) . '</li>';
			}
		}

		$output[] = '</ul>';

		$output[] = '</div>';


		$atts = [
			'per_page'       => $settings['per_page'],
			'products'       => $settings['products'],
			'order'          => $settings['order'],
			'orderby'        => $settings['orderby'],
			'product_cats'   => $settings['product_cats'],
			'product_brands' => $settings['product_brands'],
			'product_tags'   => $settings['product_tags'],
			'columns'        => $settings['slidesToShow'],
			'paginate'       => ! empty($settings['pagination_enable']) ? true : false,
		];

		if( ! empty( $settings['ids'] ) ) {
			$atts['ids'] = $settings['ids'];
		}

		$output[] = sprintf( '<div class="products-content">%s</div>', Elementor::get_products( $atts ) );

		echo implode( '', $output );
	}

	/**
	 * Get products tabs
	 *
	 */

	protected static function get_product_tabs( $settings ) {

		$nav        = $settings['products_navigation'];
		$nav_tablet = empty( $settings['products_navigation_tablet'] ) ? $nav : $settings['products_navigation_tablet'];
		$nav_mobile = empty( $settings['products_navigation_mobile'] ) ? $nav : $settings['products_navigation_mobile'];
		$classes    = [
			'mf-products-tabs woocommerce header-style-1 mf-elementor-products-navigation products-of-category-2',
			'navigation-' . $nav,
			'navigation-tablet-' . $nav_tablet,
			'navigation-mobile-' . $nav_mobile
		];

		$output      = [];
		$header_tabs = [];

		$header_tabs[] = '<div class="tabs-header-nav">';

		$tabs = $settings['tabs'];

		$tab_content = [];

		$slides_show_tablet = ! empty( $settings['products_slides_to_show_tablet'] ) ? intval( $settings['products_slides_to_show_tablet'] ) : 3;
		$slides_scroll_tablet = ! empty($settings['products_slides_to_scroll_tablet']) ? $settings['products_slides_to_scroll_tablet'] : 3;
		$slides_show_mobile = ! empty($settings['products_slides_to_show_mobile']) ? $settings['products_slides_to_show_mobile'] : 2;
		$slides_scroll_mobile = ! empty($settings['products_slides_to_scroll_mobile']) ? $settings['products_slides_to_scroll_mobile'] : 2;

		$carousel_settings = [
			'autoplay'       => $settings['products_autoplay'],
			'infinite'       => $settings['products_infinite'],
			'autoplay_speed' => $settings['products_autoplay_speed'],
			'speed'          => $settings['products_speed'],
			'slidesToShow'   => $settings['products_slides_to_show'],
			'slidesToScroll' => $settings['products_slides_to_scroll'],
			'slidesToScroll_tablet' => $slides_scroll_tablet,
			'slidesToScroll_mobile' => $slides_scroll_mobile,
			'slidesToShow_tablet'   => $slides_show_tablet,
			'slidesToShow_mobile'   => $slides_show_mobile,
		];
		$i                 = 0;
		if ( $tabs ) {
			$header_tabs[] = '<ul class="tabs-nav">';
			foreach ( $tabs as $index => $item ) {
				$tab_atts = array(
					'columns'      => $settings['products_slides_to_show'],
					'products'     => $item['tab_products'],
					'order'        => $item['tab_order'],
					'orderby'      => $item['tab_orderby'],
					'per_page'     => intval( $settings['per_page'] ),
					'product_cats' => $settings['product_cats'],
				);

				$class_active = $i == 0 ? 'active' : '';
				if ( isset( $item['tab_title'] ) ) {
					$header_tabs[] = sprintf( '<li><a href="#" data-href="%s" class="%s">%s</a></li>', esc_attr( $item['tab_products'] ), esc_attr( $class_active ), esc_html( $item['tab_title'] ) );
				}

				if ( $i == 0 ) {
					$tab_content[] = sprintf( '<div class="tabs-panel tabs-%s tab-loaded active">%s</div>', esc_attr( $item['tab_products'] ), Elementor::get_products( $tab_atts ) );
				} else {
					if ( $settings['lazy_loading'] == 'yes' ) {
						$tab_content[] = sprintf( '<div class="tabs-panel tabs-%s tab-loaded">%s</div>', esc_attr( $item['tab_products'] ), Elementor::get_products( $tab_atts ) );
					} else {
						$tab_content[] = sprintf(
							'<div class="tabs-panel tabs-%s" data-settings="%s"><div class="mf-vc-loading"><div class="mf-vc-loading--wrapper"></div></div></div>',
							esc_attr( $item['tab_products'] ),
							esc_attr( wp_json_encode( $tab_atts ) )
						);
					}

				}


				$i ++;
			}

			$header_tabs[] = '</ul>';
		}

		if ( ! empty( $settings['view_all_text'] ) ) {
			$header_tabs[] = self::get_link_control( $settings['all_link'], $settings['view_all_text'], 'link' );
		}

		$header_tabs[] = '</div>';

		$output[] = sprintf( '<div class="tabs-header">%s</div>', implode( ' ', $header_tabs ) );
		$output[] = sprintf( '<div class="tabs-content">%s</div>', implode( ' ', $tab_content ) );

		return sprintf(
			'<div class="%s" data-settings="%s">%s</div>',
			implode( ' ', $classes ),
			esc_attr( wp_json_encode( $carousel_settings ) ),
			implode( ' ', $output )
		);
	}

	/**
	 * Get products
	 *
	 */
	protected static function get_products( $atts ) {
		$query_args = self::get_query_args( $atts );

		$products    = get_posts( $query_args );
		$product_ids = [];
		$output      = [];
		$i           = 0;

		$thumbnail_size = 'shop_thumbnail';
		if ( function_exists( 'wc_get_image_size' ) ) {
			$gallery_thumbnail = wc_get_image_size( 'gallery_thumbnail' );
			$thumbnail_size    = apply_filters( 'woocommerce_gallery_thumbnail_size', array(
				$gallery_thumbnail['width'],
				$gallery_thumbnail['height']
			) );
		}
		foreach ( $products as $product ) {
			$id = $product->ID;

			if ( ! in_array( $id, $product_ids ) ) {
				$product_ids[] = $id;

				$productw = new \WC_Product( $id );

				$output[] = sprintf(
					'<li class="product">
						<div class="product-thumbnail">
							<a href="%s">%s</a>
						</div>

						<div class="product-inners">
							<h2>
								<a href="%s">%s</a>
							</h2>
							<span class="price">%s</span>
						</div>
					</li>',
					esc_url( $productw->get_permalink() ),
					$productw->get_image( $thumbnail_size ),
					esc_url( $productw->get_permalink() ),
					$productw->get_title(),
					apply_filters( 'martfury_get_price_html', wp_kses_post( $productw->get_price_html() ) )
				);
			}
			$i ++;
		}
		remove_filter( 'posts_clauses', array( __CLASS__, 'order_by_rating_post_clauses' ) );
		remove_filter( 'posts_clauses', array( __CLASS__, 'order_by_popularity_post_clauses' ) );

		return sprintf( '<ul class="products">%s</ul>', implode( '', $output ) );
	}

	/**
	 * Build query args from shortcode attributes
	 *
	 * @param array $atts
	 *
	 * @return array
	 */
	protected static function get_query_args( $atts ) {
		$args = array(
			'post_type'              => 'product',
			'post_status'            => 'publish',
			'orderby'                => get_option( 'woocommerce_default_catalog_orderby' ),
			'order'                  => 'DESC',
			'ignore_sticky_posts'    => 1,
			'posts_per_page'         => $atts['per_page'],
			'meta_query'             => WC()->query->get_meta_query(),
			'update_post_term_cache' => false,
			'update_post_meta_cache' => false,
		);

		if ( version_compare( WC()->version, '3.0.0', '>=' ) ) {
			$args['tax_query'] = WC()->query->get_tax_query();
		}

		// Ordering
		if ( 'menu_order' == $args['orderby'] || 'price' == $args['orderby'] ) {
			$args['order'] = 'ASC';
		}

		if ( 'price-desc' == $args['orderby'] ) {
			$args['orderby'] = 'price';
		}

		if ( method_exists( WC()->query, 'get_catalog_ordering_args' ) ) {
			$ordering_args   = WC()->query->get_catalog_ordering_args( $args['orderby'], $args['order'] );
			$args['orderby'] = $ordering_args['orderby'];
			$args['order']   = $ordering_args['order'];

			if ( $ordering_args['meta_key'] ) {
				$args['meta_key'] = $ordering_args['meta_key'];
			}
		}

		if ( ! empty( $atts['category'] ) ) {
			$args['product_cat'] = $atts['category'];
		}

		if ( isset( $atts['type'] ) ) {
			switch ( $atts['type'] ) {
				case 'recent':
					$args['order']   = 'DESC';
					$args['orderby'] = 'date';

					break;

				case 'featured':
					if ( version_compare( WC()->version, '3.0.0', '<' ) ) {
						$args['meta_query'][] = array(
							'key'   => '_featured',
							'value' => 'yes',
						);
					} else {
						$args['tax_query'][] = array(
							'taxonomy' => 'product_visibility',
							'field'    => 'name',
							'terms'    => 'featured',
							'operator' => 'IN',
						);
					}

					break;

				case 'sale':
					$args['post__in'] = array_merge( array( 0 ), wc_get_product_ids_on_sale() );
					break;

				case 'best_selling':
					$args['meta_key'] = 'total_sales';
					$args['orderby']  = 'meta_value_num';
					$args['order']    = 'DESC';

					add_filter( 'posts_clauses', array( __CLASS__, 'order_by_popularity_post_clauses' ) );
					break;

				case 'top_rated':
					$args['meta_key'] = '_wc_average_rating';
					$args['orderby']  = 'meta_value_num';
					$args['order']    = 'DESC';
					break;
			}
		}

		return $args;
	}


	/**
	 * WP Core doens't let us change the sort direction for invidual orderby params - https://core.trac.wordpress.org/ticket/17065.
	 *
	 * This lets us sort by meta value desc, and have a second orderby param.
	 *
	 * @access public
	 *
	 * @param array $args
	 *
	 * @return array
	 */
	public static function order_by_popularity_post_clauses( $args ) {
		global $wpdb;
		$args['orderby'] = "$wpdb->postmeta.meta_value+0 DESC, $wpdb->posts.post_date DESC";

		return $args;
	}


	/**
	 * Get the link control
	 *
	 * @return string.
	 */
	public static function get_link_control( $url, $content, $class_css ) {

		$attributes = array();
		if ( $url['is_external'] ) {
			$attributes[] = 'target ="_blank"';
		}

		if ( $url['nofollow'] ) {
			$attributes[] = ' rel ="nofollow"';
		}

		$attr = 'span';
		if ( $url['url'] ) {
			$attributes[] = ' href ="' . $url['url'] . '"';
			$attr         = 'a';
		}

		return sprintf( '<%1$s class="%4$s" %2$s>%3$s</%1$s>', $attr, implode( ' ', $attributes ), $content, $class_css );
	}

}

new Elementor_AjaxLoader();