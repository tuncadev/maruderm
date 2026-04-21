<?php
/**
 * Product quantity inputs
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/global/quantity-input.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 10.1.0
 *
 * @var bool   $readonly If the input should be set to readonly mode.
 * @var string $type     The input type attribute.
 */

defined( 'ABSPATH' ) || exit;

if( ! isset( $classes ) || empty( $classes ) ) {
    $classes[] = 'input-text qty text';
}

/* translators: %s: Quantity. */
$label = ! empty( $args['product_name'] ) ? sprintf( esc_html__( '%s quantity', 'martfury' ), wp_strip_all_tags( $args['product_name'] ) ) : esc_html__( 'Quantity', 'martfury' );

// In some cases we wish to display the quantity but not allow for it to be changed.
$type = 'number';
if ( $max_value && $min_value === $max_value ) {
	$is_readonly = true;
	$input_value = $min_value;
    $type = 'text';
} else {
	$is_readonly = false;
}
?>
<div class="quantity">
	<?php
	/**
	 * Hook to output something before the quantity input field.
	 *
	 * @since 7.2.0
	 */
	do_action( 'woocommerce_before_quantity_input_field' );
	?>
	<label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>"><?php echo esc_html( $label ); ?></label>
	<label class="label"
               for="<?php echo esc_attr( $input_id ); ?>"><?php esc_html_e( 'Quantity', 'martfury' ); ?>:</label>
    <div class="qty-box">
        <span class="decrease  icon_minus-06"></span>
        <input
		type="<?php echo ! empty($is_readonly) ? 'text' : 'number'; ?>"
		<?php echo ! empty($is_readonly) ? 'readonly="readonly"' : ''; ?>
            id="<?php echo esc_attr( $input_id ); ?>"
            class="<?php echo esc_attr( join( ' ', (array) $classes ) ); ?>"
            name="<?php echo esc_attr( $input_name ); ?>"
            value="<?php echo esc_attr( $input_value ); ?>"
            title="<?php echo esc_attr_x( 'Qty', 'Product quantity input tooltip', 'martfury' ); ?>"
            <?php if ( in_array( $type, array( 'text', 'search', 'tel', 'url', 'email', 'password' ), true ) ) : ?>
			size="4"
			<?php endif; ?>
            min="<?php echo esc_attr( $min_value ); ?>"
            max="<?php echo esc_attr( 0 < $max_value ? $max_value : '' ); ?>"
            <?php if ( ! $is_readonly ): ?>
                step="<?php echo esc_attr( $step ); ?>"
                placeholder="<?php echo esc_attr( $placeholder ); ?>"
                inputmode="<?php echo esc_attr( $inputmode ); ?>"
                autocomplete="<?php echo esc_attr( isset( $autocomplete ) ? $autocomplete : 'on' ); ?>"
            <?php endif; ?>

        />
        <span class="increase icon_plus"></span>
    </div>
	<?php
	/**
	 * Hook to output something after quantity input field
	 *
	 * @since 3.6.0
	 */
	do_action( 'woocommerce_after_quantity_input_field' );
	?>
</div>
<?php
