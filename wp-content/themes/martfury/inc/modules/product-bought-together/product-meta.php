<?php

namespace Martfury\Modules\Product_Bought_Together;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Main class of plugin for admin
 */
class Product_Meta  {

	/**
	 * Instance
	 *
	 * @var $instance
	 */
	private static $instance;

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
		add_action( 'woocommerce_product_data_tabs', array( $this, 'product_meta_tab' ) );
		add_action( 'woocommerce_product_data_panels', array( $this, 'product_data_panel' ) );

		add_action( 'save_post', array( $this, 'save_post' ), 10, 2 );
	}

	/**
	 * Product data tab
	 */
	public function product_meta_tab( $product_data_tabs ) {
		$product_data_tabs['martfury_pbt_product'] = array(
			'label'  => esc_html__( 'Frequently Bought Together', 'martfury' ),
			'target' => 'product_martfury_pbt_product',
			'class'  => array( 'hide_if_grouped', 'hide_if_external', 'hide_if_bundle' ),
		);

		return $product_data_tabs;
	}

	/**
	 * Outputs the size guide panel
     *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function product_data_panel() {
		global $post;
		$post_id = $post->ID;
		?>
		<div id="product_martfury_pbt_product" class="panel woocommerce_options_panel">
            <p class="form-field">
                <label for="mf_pbt_product_ids"><?php esc_html_e( 'Select Products', 'martfury' ); ?></label>
                <select class="wc-product-search" multiple="multiple" style="width: 50%;" id="mf_pbt_product_ids"
                        name="mf_pbt_product_ids[]"
                        data-placeholder="<?php esc_attr_e( 'Search for a product&hellip;', 'martfury' ); ?>"
                        data-action="woocommerce_json_search_products_and_variations"
                        data-exclude="<?php echo intval( $post->ID ); ?>">
					<?php
					$product_ids = maybe_unserialize( get_post_meta( $post->ID, 'mf_pbt_product_ids', true ) );

					if ( $product_ids && is_array( $product_ids ) ) {
						foreach ( $product_ids as $product_id ) {
							$product = wc_get_product( $product_id );
							if ( is_object( $product ) ) {
								echo '<option value="' . esc_attr( $product_id ) . '"' . selected( true, true, false ) . '>' . wp_kses_post( $product->get_formatted_name() ) . '</option>';
							}
						}
					}

					?>
                </select> <?php echo wc_help_tip( __( 'Select products for "Frequently bought together" group.', 'martfury' ) ); ?>
            </p>
			<p class="form-field">
				<label for="product_bought_together_discount_all"><?php esc_html_e( 'Discount', 'martfury' ); ?></label>
				<input id="product_bought_together_discount_all" type="number" name="martfury_pbt_discount_all" value="<?php echo get_post_meta( $post_id, 'martfury_pbt_discount_all', true ); ?>">
				<span class="description">%</span>
			</p>
			<p class="form-field">
				<?php
					$martfury_pbt_checked_all = get_post_meta( $post_id, 'martfury_pbt_checked_all', true );
					$checked = empty( $martfury_pbt_checked_all ) || $martfury_pbt_checked_all !== 'no' ? 'yes' : 'no';
				?>
				<label for="product_bought_together_checked_all"><?php esc_html_e( 'Checked All', 'martfury' ); ?></label>
				<input type="checkbox" class="checkbox" name="martfury_pbt_checked_all" id="product_bought_together_checked_all" value="yes" <?php echo checked( $checked, 'yes', false ); ?>>
			</p>
			<p class="form-field">
				<label for="product_bought_together_quantity_discount_all"><?php esc_html_e( 'Number of items to get discount', 'martfury' ); ?></label>
				<input id="product_bought_together_quantity_discount_all" type="number" name="martfury_pbt_quantity_discount_all" min="2" value="<?php echo get_post_meta( $post_id, 'martfury_pbt_quantity_discount_all', true ); ?>">
			</p>
		</div>
		<?php
	}


	/**
	 * Save meta box content.
     *
	 * @since 1.0.0
	 *
	 * @param int $post_id
	 * @param object $post
     *
	 * @return void
	 */
	public function save_post( $post_id, $post ) {
		//If not the flex post.
		if ( 'product' != $post->post_type ) {
			return;
		}

		// Check if user has permissions to save data.
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
		}

		// Check if not an autosave.
        if ( wp_is_post_autosave( $post_id ) ) {
            return;
		}

		if ( isset( $_POST['mf_pbt_product_ids'] ) ) {
			$woo_data = $_POST['mf_pbt_product_ids'];
			update_post_meta( $post_id, 'mf_pbt_product_ids', $woo_data );
		} else {
			update_post_meta( $post_id, 'mf_pbt_product_ids', 0 );
		}

		if ( isset( $_POST['martfury_pbt_discount_all'] ) ) {
			$woo_data = intval( $_POST['martfury_pbt_discount_all'] );
			update_post_meta( $post_id, 'martfury_pbt_discount_all', $woo_data );
		} else {
			update_post_meta( $post_id, 'martfury_pbt_discount_all', 0 );
		}

		if ( array_key_exists( 'martfury_pbt_checked_all', $_POST ) ) {
			update_post_meta( $post_id, 'martfury_pbt_checked_all', 'yes' );
		} else {
			update_post_meta( $post_id, 'martfury_pbt_checked_all', 'no' );
		}

		if ( isset( $_POST['martfury_pbt_quantity_discount_all'] ) && intval( $_POST['martfury_pbt_quantity_discount_all'] ) !== 0 && intval( $_POST['martfury_pbt_quantity_discount_all'] ) !== 1 ) {
			$woo_data = intval( $_POST['martfury_pbt_quantity_discount_all'] );
			update_post_meta( $post_id, 'martfury_pbt_quantity_discount_all', $woo_data );
		} else {
			update_post_meta( $post_id, 'martfury_pbt_quantity_discount_all', 2 );
		}
	}

}