<?php

namespace Martfury\Modules\Product_Bought_Together;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Main class of plugin for admin
 */
class Frontend {

	/**
	 * Instance
	 *
	 * @var $instance
	 */
	private static $instance;

	/**
	 * Has variation images
	 *
	 * @var $has_variation_images
	 */
	protected static $has_variation_images = null;


	/**
	 * Initiator
	 *
	 * @since 1.0.0
	 * @return object
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Instantiate the object.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'woocommerce_after_single_product_summary', array( $this, 'product_bought_together' ), 5 );

		add_action( 'wp_loaded', array( $this, 'add_to_cart_action' ), 20 );

		add_action( 'woocommerce_add_to_cart', [ $this, 'add_to_cart' ], 10, 6 );
		add_filter( 'woocommerce_add_cart_item_data', [ $this, 'add_cart_item_data' ], 10, 2 );
		add_action( 'woocommerce_cart_item_removed', [ $this, 'cart_item_removed' ], 10, 2 );

		// Cart contents
		add_action( 'woocommerce_before_mini_cart_contents', [ $this, 'before_mini_cart_contents' ], 10 );
		add_action( 'woocommerce_before_calculate_totals', [ $this, 'before_calculate_totals' ], 9999 );
	}

		/**
	 * Enqueue scripts
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		wp_enqueue_style( 'martfury-product-bought-together', get_template_directory_uri() . '/inc/modules/product-bought-together/assets/product-bought-together.css', array(), '1.0.0' );
		wp_enqueue_script('martfury-product-bought-together', get_template_directory_uri() . '/inc/modules/product-bought-together/assets/product-bought-together.js',  array('jquery'), '1.0.0' );

		if ( is_singular( 'product' ) ) {
			$martfury_data = array(
				'currency_pos'    => get_option( 'woocommerce_currency_pos' ),
				'currency_symbol' => function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : '',
				'thousand_sep'    => function_exists( 'wc_get_price_thousand_separator' ) ? wc_get_price_thousand_separator() : '',
				'decimal_sep'     => function_exists( 'wc_get_price_decimal_separator' ) ? wc_get_price_decimal_separator() : '',
				'price_decimals'  => function_exists( 'wc_get_price_decimals' ) ? wc_get_price_decimals() : '',
				'check_all'       => get_post_meta( get_the_ID(), 'martfury_pbt_checked_all', true ),
				'pbt_alert' 		  => esc_html__( 'Please select a purchasable variation for [name] before adding this product to the cart.', 'martfury' ),
				'pbt_alert_multiple' => esc_html__( 'Please select a purchasable variation for the selected variable products before adding them to the cart.', 'martfury' ),
			);

			wp_localize_script(
				'martfury-product-bought-together', 'martfuryPbt', $martfury_data
			);
		}
	}

	/**
	 * Frequently Bought Together
	 */
	function product_bought_together() {
		global $product;

		$product_ids = maybe_unserialize( get_post_meta( $product->get_id(), 'mf_pbt_product_ids', true ) );
		$product_ids = apply_filters( 'martfury_pbt_product_ids', $product_ids, $product );
		if ( empty( $product_ids ) || ! is_array( $product_ids ) ) {
			return;
		}

		$current_product = array( $product->get_id() );
		$product_ids     = array_merge( $current_product, $product_ids );
		$title           = martfury_get_option( 'product_fbt_title' );

		$total_price  = 0;
		$product_list = array();

		$discount = intval( get_post_meta( $product->get_id(), 'martfury_pbt_discount_all', true ) );
		$checked_all = get_post_meta( $product->get_id(), 'martfury_pbt_checked_all', true );
		$quantity_discount_all = intval( get_post_meta( $product->get_id(), 'martfury_pbt_quantity_discount_all', true ) );

		$countProduct = ! empty( $checked_all ) && $checked_all == 'no' ? 1 : count( $product_ids );

		?>
        <div class="mf-product-fbt" id="mf-product-fbt">
            <h3 class="fbt-title"><?php echo esc_html( $title ); ?></h3>
            <ul class="products">
				<?php
				$dupplicate_id = 0;
				foreach ( $product_ids as $product_id ) {
					$product_id = apply_filters( 'wpml_object_id', $product_id, 'product' );
					$item       = wc_get_product( $product_id );
					$classPrice = '';


					if ( empty( $item ) ) {
						continue;
					}

					if ( $item->get_stock_status() == 'outofstock' || $item->is_type( 'grouped' ) || $item->is_type( 'external' ) ) {
						$key = array_search( $product_id, $product_ids );
						if ( $key !== false ) {
							unset( $product_ids[ $key ] );
						}
						continue;
					}

					$data_id = $item->get_id();
					if ( $item->get_parent_id() > 0 ) {
						$data_id = $item->get_parent_id();
					}

					if( ! empty( $checked_all ) && $checked_all == 'no' ) {
						$total_price = $product->is_type( 'variable' ) ? 0 : wc_get_price_to_display( $product );
					} else {
						$total_price  += $item->is_type( 'variable' ) ? 0 : wc_get_price_to_display( $item );
					}

					$current_class_li = $current_class = $current_item = '';
					if ( $item->get_id() == $product->get_id() ) {
						$current_item = sprintf( '<strong>%s</strong>', esc_html__( 'This item:', 'martfury' ) );
						$current_class_li = 'product-primary';
						$current_class = 'product-current';
					}

					if( $item->get_id() !== $product->get_id() && ( ! empty( $checked_all ) && $checked_all == 'no' ) ) {
						$current_class_li .= ' un-active';
						$current_class .= ' uncheck';
					}

					$pids[] = $item->is_type( 'variable' ) ? 0 : $item->get_id();

					$product_name = $item->get_title() . $this->fbt_product_variation( $item );

					$product_list[] = sprintf(
						'<li data-type="%s"  data-name="%s" class="products-list__item %s %s pbt-product-%s"><a class="product-id" href="%s" data-id="%s" data-title="%s"><span class="p-title">%s %s</span></a><span class="s-price" data-price="%s">(%s)</span>%s</li>',
						$item->get_type() == 'variation' ? 'variable' : esc_attr( $item->get_type() ),
						esc_attr( $item->get_name() ),
						esc_attr( $current_class_li ),
						esc_attr($current_class),
						esc_attr( $item->get_id() ),
						esc_url( $item->get_permalink() ),
						esc_attr( $item->get_id() ),
						esc_attr( $product_name ),
						$current_item,
						$product_name,
						$item->is_type( 'variable' ) ? 0 : esc_attr( $item->get_price() ),
						$item->get_price_html(),
						$item->is_type( 'variable' ) ? '<span class="s-attrs hidden" data-attrs=""></span>' : ''
					);

					$product_price = $item->is_type( 'variable' ) ? 0 : $item->get_price();
					$product_type = $item->get_type() == 'variation' ? 'variable' : $item->get_type();
					?>
                    <li class="product <?php echo esc_attr( $current_class_li ); ?> <?php echo esc_attr($current_class); ?> pbt-product-<?php echo esc_attr( $item->get_id() ); ?>" data-id="<?php echo esc_attr( $data_id ); ?>"
					data-price="<?php echo esc_attr( $product_price ); ?>" id="fbt-product-<?php echo esc_attr( $item->get_id() ); ?>" data-type="<?php echo esc_attr( $product_type ); ?>" data-ptype="<?php echo esc_attr( $item->get_type() ); ?>" data-name="<?php echo esc_attr( $item->get_name() ); ?>">
                        <div class="product-content">
                            <a class="thumbnail" href="<?php echo esc_url( $item->get_permalink() ) ?>">
								<span class="thumb-ori">
									<?php echo martfury_get_image_html( $item->get_image_id(), 'shop_catalog' ); ?>
								</span>
								<?php if( $item->is_type( 'variable' ) ) : ?>
									<span class="thumb-new"></span>
								<?php endif; ?>
                            </a>

                            <h2>
                                <a href="<?php echo esc_url( $item->get_permalink() ) ?>">
									<?php echo esc_html( $product_name ); ?>
                                </a>
                            </h2>

                            <?php if ( ! $item->is_type( 'variable' ) && $discount !== 0 ) : ?>
							<?php $classPrice = $quantity_discount_all <= $countProduct ? '' : 'hidden'; ?>
								<div class="price price-new <?php echo esc_attr( $classPrice ); ?>">
									<?php
										$sale_price = $item->get_price() * ( 100 - (float) $discount ) / 100;
										$save_price = $item->get_price() - $sale_price;
										echo wc_format_sale_price( $item->get_price(), $sale_price ) . $item->get_price_suffix( $sale_price );
										$classPrice = empty( $classPrice ) ? 'price-ori hidden' : 'price-ori';
									?>
								</div>
							<?php endif; ?>
							<div class="price <?php echo esc_attr( $classPrice ); ?>">
								<?php echo wp_kses_post( $item->get_price_html() ); ?>
                            </div>
							<?php
							if( $item->is_type( 'variable' ) ) {
								$attributes           = $item->get_variation_attributes();
								$available_variations = $item->get_available_variations();

								if ( is_array( $attributes ) && ( count( $attributes ) > 0 ) ) {

									if( $discount !== 0 ) {
										foreach( $available_variations as $key => $available_variation ) {
											$_p = $available_variation['display_price'];
											$_p_html = $available_variation['price_html'];
											$_class = $quantity_discount_all <= $countProduct ? 'active' : '';
											$_ps = $_p * ( 100 - (float) $discount ) / 100;
											$available_variations[$key]['price_html'] = '<div class="product-variation-price ' . esc_attr( $_class ) . '">' . $_p_html . '<span class="price price-new">' . wc_format_sale_price( $_p, $_ps ) . $item->get_price_suffix( $_ps ) . '</span></div>';
										}
									}

									$attribute_keys  = array_keys( $attributes );
									$variations_json = wp_json_encode( $available_variations );
									$variations_attr = function_exists( 'wc_esc_json' ) ? wc_esc_json( $variations_json ) : _wp_specialchars( $variations_json, ENT_QUOTES, 'UTF-8', true );
									$variations_classes = $item->get_id() == $product->get_id() ? '' : 'form-cart-pbt';
									?>
									<form class="variations_form cart form-pbt <?php echo esc_attr( $variations_classes ); ?>" action="<?php echo esc_url( apply_filters( 'woocommerce_add_to_cart_form_action', $item->get_permalink() ) ); ?>" method="post" enctype='multipart/form-data' data-product_id="<?php echo absint( $item->get_id() ); ?>" data-product_variations="<?php echo esc_attr( $variations_attr ); ?>">

										<?php if ( empty( $available_variations ) && false !== $available_variations ) : ?>
											<p class="stock out-of-stock"><?php echo esc_html( apply_filters( 'woocommerce_out_of_stock_message', __( 'This product is currently out of stock and unavailable.', 'martfury' ) ) ); ?></p>
										<?php else : ?>
											<table class="variations" cellspacing="0" role="presentation">
												<tbody>
													<?php foreach ( $attributes as $attribute_name => $options ) : ?>
														<tr>
															<th class="label"><label for="<?php echo esc_attr( sanitize_title( $attribute_name ) ); ?>"><?php echo wc_attribute_label( $attribute_name ); // WPCS: XSS ok. ?></label></th>
															<td class="value">
																<?php
																	wc_dropdown_variation_attribute_options(
																		array(
																			'options'   => $options,
																			'attribute' => $attribute_name,
																			'product'   => $item,
																		)
																	);
																?>
															</td>
														</tr>
													<?php endforeach; ?>
												</tbody>
											</table>

											<div class="single_variation_wrap">
												<?php
													/**
													 * Hook: woocommerce_single_variation. Used to output the cart button and placeholder for variation data.
													 *
													 * @since 2.4.0
													 * @hooked woocommerce_single_variation - 10 Empty div for variation data.
													 * @hooked woocommerce_single_variation_add_to_cart_button - 20 Qty and cart button.
													 */
													do_action( 'woocommerce_single_variation' );
												?>
											</div>
										<?php endif; ?>
									</form>
								<?php
								}
							}
							?>
							<?php
							if ( $dupplicate_id != $data_id ) {
								if ( shortcode_exists( 'wcboost_wishlist_button' ) ) {
									echo do_shortcode( '[wcboost_wishlist_button class="fbt-wishlist add_to_wishlist single_add_to_wishlist" product_id="' . $data_id . '"]' );
								} else if ( shortcode_exists( 'yith_wcwl_add_to_wishlist' ) ) {
									echo do_shortcode( '[yith_wcwl_add_to_wishlist link_classes="fbt-wishlist add_to_wishlist single_add_to_wishlist" product_id ="' . $data_id . '"]' );
								}

								$dupplicate_id = $data_id;
							}
							?>
                        </div>
						<div class="product-select <?php echo esc_attr($current_class); ?> hidden">
							<?php
								echo sprintf(
									'<a class="product-id" href="%s" data-id="%s" data-title="%s"><span class="select"></span><span class="p-title">%s</span></a>
									<span class="s-price hidden" data-price="%s">(%s)</span>%s',
									esc_url( $item->get_permalink() ),
									esc_attr( $item->get_id() ),
									esc_attr( $product_name ),
									esc_html__( 'Add to Package', 'martfury' ),
									$item->is_type( 'variable' ) ? 0 : esc_attr( $item->get_price() ),
									$item->get_price_html(),
									$item->is_type( 'variable' ) ? '<span class="s-attrs hidden" data-attrs=""></span>' : ''
								);
							?>
						</div>
						<?php ?>
                    </li>
					<?php
				}
				?>
                <li class="product product-buttons">
					<?php
						if( ! empty( $checked_all ) && $checked_all == 'no' ) {
							$pids = $product->get_id();
							$numberProduct = count( (array) $pids );
						} else {
							$numberProduct = count( $pids );
							$pids = implode( ',', $pids );
						}
					?>
					<?php if( $discount !== 0 ) : ?>
						<?php
							if( $product->is_type( 'variable' ) ) {
								$save_price = 0;
							} else {
								$save_price = ( $total_price / 100 ) * (float) $discount;
							}
						?>
						<div class="price-box price-box__subtotal">
							<span class="label"><?php esc_html_e( 'SubTotal: ', 'martfury' ); ?></span>
							<span class="s-price martfury-pbt-subtotal"><?php echo wc_price( $total_price ); ?></span>
							<input type="hidden" data-price="<?php echo esc_attr( $total_price ); ?>" id="martfury-data_subtotal">
						</div>
						<div class="price-box price-box__save">
							<span class="label"><?php esc_html_e( 'Save: ', 'martfury' ); ?></span>
							<span class="s-price martfury-pbt-save-price"><?php echo wc_price( $quantity_discount_all <= $numberProduct ? $save_price : 0 ); ?> (<span class="percent"><?php echo esc_html( $quantity_discount_all <= $numberProduct ? $discount : 0 ); ?></span>%)</span>
							<input type="hidden" data-price="<?php echo esc_attr( $save_price ); ?>" id="martfury-data_save-price">
							<input type="hidden" data-discount="<?php echo esc_attr( $discount ); ?>" id="martfury-data_discount-all">
							<input type="hidden" data-quantity="<?php echo esc_attr( $quantity_discount_all ); ?>" id="martfury-data_quantity-discount-all">
						</div>
						<?php $total_price = $quantity_discount_all <= $numberProduct ? $total_price - $save_price : $total_price; ?>
					<?php else : ?>
						<div class="price-box price-box__subtotal hidden">
							<input type="hidden" data-price="<?php echo esc_attr( $total_price ); ?>" id="martfury-data_subtotal">
						</div>
					<?php endif; ?>
                    <div class="price-box price-box__total">
                        <span class="label"><?php esc_html_e( 'Total Price: ', 'martfury' ); ?></span>
                        <span class="s-price mf-total-price"><?php echo wc_price( $total_price ); ?></span>
                        <input type="hidden" data-price="<?php echo esc_attr( $total_price ); ?>" id="mf-data_price">
                    </div>
                    <form class="fbt-cart" action="<?php echo esc_url( $product->get_permalink() ); ?>" method="post"
                          enctype="multipart/form-data">

						<input type="hidden" name="martfury_variation_id" class="martfury_variation_id" value="0">
						<input type="hidden" name="martfury_variation_attrs" class="martfury_variation_attrs" value="0">
						<input class="martfury_product_id" name="martfury_product_id" type="hidden" value="<?php echo esc_attr( $product->is_type( 'variable' ) || ! $product->is_in_stock() ? 0 : $product->get_id() ) ?>">
                        <button type="submit" name="mf-add-to-cart" value="<?php echo esc_attr( $pids ); ?>"
                                class="btn-primary-small mf_add_to_cart_button ajax_add_to_cart"><?php esc_html_e( 'Add All To Cart', 'martfury' ); ?></button>
                    </form>
					<?php if ( function_exists( 'YITH_WCWL' ) || shortcode_exists( 'wcboost_wishlist_button' ) ) : ?>
                        <a href="<?php echo esc_url( $product->get_permalink() ); ?>"
                           class="btn-primary-small-outline btn-add-to-wishlist mf-wishlist-button"><span> <?php esc_html_e( 'Add All To Wishlist', 'martfury' ); ?></span></a>
                        <a href="<?php echo esc_url( $this->get_wishlist_url() ); ?>"
                           class="btn-primary-small-outline btn-view-to-wishlist mf-wishlist-button"><span><?php esc_html_e( 'Browse Wishlist', 'martfury' ); ?></span></a>
					<?php endif; ?>
                </li>
            </ul>
            <div class="clear"></div>
            <ul class="products-list">
				<?php echo implode( '', $product_list ); ?>
            </ul>
			<div class="martfury-pbt-alert woocommerce-message" style="display:none;"></div>
        </div>
		<?php
	}

	function fbt_product_variation( $product ) {
		$current_product_is_variation = $product->is_type( 'variation' );
		if ( ! $current_product_is_variation ) {
			return;
		}
		$attributes = $product->get_variation_attributes();
		$variations = array();

		foreach ( $attributes as $key => $attribute ) {
			$key = str_replace( 'attribute_', '', $key );

			$terms = get_terms( array(
				'taxonomy'   => sanitize_title( $key ),
				'menu_order' => 'ASC',
				'hide_empty' => false
			) );

			foreach ( $terms as $term ) {
				if ( ! is_object( $term ) || ! in_array( $term->slug, array( $attribute ) ) ) {
					continue;
				}
				$variations[] = $term->name;
			}
		}

		if ( ! empty( $variations ) ) {
			return ' &ndash; ' . implode( ', ', $variations );
		}

	}

	function get_wishlist_url() {
		if( shortcode_exists( 'wcboost_wishlist_button' ) ) {
			return wc_get_page_permalink( 'wishlist' );
		} elseif ( function_exists( 'YITH_WCWL' ) ) {
			return YITH_WCWL()->get_wishlist_url();
		} else {
			return get_the_permalink( get_option( 'yith_wcwl_wishlist_page_id' ) );
		}
	}

	/**
	 * Add to cart product bought together
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	function add_to_cart_action() {
		if ( empty( $_REQUEST['mf-add-to-cart'] ) ) {
			return;
		}

		wc_nocache_headers();

		$product_id = $_REQUEST['martfury_product_id'];

		if ( $product_id == 0 ) {
			$product_ids = explode( ',', $_REQUEST['mf-add-to-cart'] );
			$product_id  = $product_ids[0];
		}

		$adding_to_cart    = wc_get_product( $product_id );

		if ( ! $adding_to_cart ) {
			return;
		}

		$was_added_to_cart = false;
		$quantity          = 1;
		$variation_id      = 0;
		$variations        = array();

		if ( $adding_to_cart->is_type( 'variation' ) ) {
			$variation_id = $product_id;
			$product_id   = $adding_to_cart->get_parent_id();
			$variations   = json_decode( stripslashes( $_REQUEST['martfury_variation_attrs'] ) );
			$variations   = (array) json_decode( rawurldecode( $variations->$variation_id ) );
		}

		$passed_validation = apply_filters( 'woocommerce_add_to_cart_validation', true, $product_id, $quantity, $variation_id, $variations );

		if ( $passed_validation && false !== WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $variations ) ) {
			wc_add_to_cart_message( array( $product_id => $quantity ), true );
			$was_added_to_cart = true;
		}

		// If we added the product to the cart we can now optionally do a redirect.
		if ( $was_added_to_cart && 0 === wc_notice_count( 'error' ) ) {
			if ( 'yes' === get_option( 'woocommerce_cart_redirect_after_add' ) ) {
				wp_safe_redirect( wc_get_cart_url() );
				exit;
			}
		}
	}

	function add_to_cart( $cart_item_key, $product_id, $variations ) {
		if ( isset( $_REQUEST['mf-add-to-cart'] ) || isset( $_REQUEST['data']['mf-add-to-cart'] ) ) {
			$ids = '';

			if ( isset( $_REQUEST['mf-add-to-cart'] ) ) {
				$ids = $_REQUEST['mf-add-to-cart'];
				unset( $_REQUEST['mf-add-to-cart'] );
			} elseif ( $_REQUEST['data']['mf-add-to-cart'] ) {
				$ids = $_REQUEST['data']['mf-add-to-cart'];
				unset( $_REQUEST['data']['mf-add-to-cart'] );
			}

			if( ! empty( $_REQUEST['martfury_variation_attrs'] ) ) {
				$variations = json_decode( stripslashes( $_REQUEST['martfury_variation_attrs'] ) );
			}

			$product_ids = array_map( 'absint', explode( ',', $ids ) );

			if ( $product_ids ) {
				// add child products
				self::add_to_cart_items( $product_ids, $cart_item_key, $product_id, $variations );
			}
		}
	}

	function add_to_cart_items( $items, $cart_item_key, $product_id, $variations ) {
		$items = array_filter( $items );

		// add child products
		foreach ( $items as $item_id ) {
			$item_qty          = 1;
			$item_product      = wc_get_product( $item_id );
			$item_variation    = [];
			$item_variation_id = 0;

			if ( ! empty( $variations->$item_id ) ) {
				$item_variation_id = $item_id;
				$item_id           = $item_product->get_parent_id();
				$item_variation    = ! empty($variations->$item_variation_id) ? (array) json_decode( rawurldecode( $variations->$item_variation_id ) ) : array();
			}

			if ( $item_product && $item_product->is_in_stock() && $item_product->is_purchasable() ) {

				if( $item_id == $product_id ) {
					continue;
				}

				// add to cart
				$item_key = WC()->cart->add_to_cart( $item_id, $item_qty, $item_variation_id, $item_variation );

				if ( $item_key ) {
					WC()->cart->cart_contents[ $item_key ]['martfury_pbt_key']         = $item_key;
					WC()->cart->cart_contents[ $item_key ]['martfury_pbt_parent_key']  = $cart_item_key;
					WC()->cart->cart_contents[ $cart_item_key ]['martfury_pbt_keys'][] = $item_key;
				}
			}
		}
	}

	function add_cart_item_data( $cart_item_data ) {
		if ( isset( $_REQUEST['mf-add-to-cart'] ) || isset( $_REQUEST['data']['mf-add-to-cart'] ) ) {
			// make sure that is bought together product
			if ( isset( $_REQUEST['mf-add-to-cart'] ) ) {
				$ids = $_REQUEST['mf-add-to-cart'];
			} elseif ( isset( $_REQUEST['data']['mf-add-to-cart'] ) ) {
				$ids = $_REQUEST['data']['mf-add-to-cart'];
			}

			if ( ! empty( $ids ) ) {
				$cart_item_data['martfury_pbt_ids'] = $ids;
			}
		}

		return $cart_item_data;
	}

	function cart_item_removed( $cart_item_key, $cart ) {
		if ( isset( $cart->removed_cart_contents[ $cart_item_key ]['martfury_pbt_keys'] ) ) {
			$keys = $cart->removed_cart_contents[ $cart_item_key ]['martfury_pbt_keys'];

			foreach ( $keys as $key ) {
				unset( $cart->cart_contents[ $key ]['martfury_pbt_key'] );
				unset( $cart->cart_contents[ $key ]['martfury_pbt_parent_key'] );
			}
		}

		if ( isset( $cart->removed_cart_contents[ $cart_item_key ]['martfury_pbt_key'] ) ) {
			$key = $cart->removed_cart_contents[ $cart_item_key ]['martfury_pbt_key'];
			unset( $cart->cart_contents[ $key ] );

			if( ! empty( $cart->removed_cart_contents[ $cart_item_key ]['martfury_pbt_parent_key'] ) ) {
				$_pkey = $cart->removed_cart_contents[ $cart_item_key ]['martfury_pbt_parent_key'];
				if ( ! empty( $cart->cart_contents[ $_pkey ]['martfury_pbt_keys'] ) ) {
					$_skey = array_search( $key, $cart->cart_contents[ $_pkey ]['martfury_pbt_keys'] );
					unset( $cart->cart_contents[ $_pkey ]['martfury_pbt_keys'][ $_skey ] );
				}
			}
		}
	}

	function before_mini_cart_contents() {
		WC()->cart->calculate_totals();
	}

	function before_calculate_totals( $cart_object ) {
		if ( ! defined( 'DOING_AJAX' ) && is_admin() ) {
			// This is necessary for WC 3.0+
			return;
		}

		$cart_contents = $cart_object->cart_contents;

		foreach ( $cart_contents as $cart_item_key => $cart_item ) {
			if( ! empty( $cart_item['martfury_pbt_ids'] ) ) {
				if ( $cart_item['variation_id'] > 0 ) {
					$item_product = wc_get_product( $cart_item['variation_id'] );
				} else {
					$item_product = wc_get_product( $cart_item['product_id'] );
				}

				$ori_price = $item_product->get_price();

				// has associated products
				$has_associated = false;

				if ( isset( $cart_item['martfury_pbt_keys'] ) ) {
					foreach ( $cart_item['martfury_pbt_keys'] as $key ) {
						if ( isset( $cart_contents[ $key ] ) ) {
							$has_associated = true;
							break;
						}
					}
				}

				// main product
				$discount = get_post_meta( $cart_item['product_id'], 'martfury_pbt_discount_all', true );
				$quantity_discount_all = intval( get_post_meta( $cart_item['product_id'], 'martfury_pbt_quantity_discount_all', true ) );

				if ( $has_associated && $discount && $discount !== 0 && $quantity_discount_all <= count( explode( ',', $cart_item['martfury_pbt_ids'] ) ) ) {
					$discount_price = $ori_price * ( 100 - (float) $discount ) / 100;
					$cart_item['data']->set_price( $discount_price );

					// associated products
					if( ! empty( $cart_item['martfury_pbt_keys'] ) ) {
						foreach ( $cart_item['martfury_pbt_keys'] as $key => $martfury_pbt_keys ) {
							if( ! isset( $cart_contents[ $martfury_pbt_keys ] ) ) {
								continue;
							}

							if ( $cart_contents[$martfury_pbt_keys]['variation_id'] > 0 ) {
								$_item_product = wc_get_product( $cart_contents[$martfury_pbt_keys]['variation_id'] );
							} else {
								$_item_product = wc_get_product( $cart_contents[$martfury_pbt_keys]['product_id'] );
							}

							$ori_price_child = $_item_product->get_price();
							$discount_price_child = $ori_price_child * ( 100 - (float) $discount ) / 100;

							$cart_contents[$martfury_pbt_keys]['data']->set_price( $discount_price_child );
						}
					}
				}
			}
		}
	}
}