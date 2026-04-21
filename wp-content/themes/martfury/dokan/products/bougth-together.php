<div class="dokan-fbt-options dokan-edit-row dokan-clearfix">
    <div class="dokan-section-heading" data-togglehandler="dokan_fbt_options">
        <h2><i class="fas fa-cog" aria-hidden="true"></i> <?php esc_html_e( 'Frequently Bought Together', 'martfury' ); ?></h2>
        <a href="#" class="dokan-section-toggle">
            <i class="fas fa-sort-down fa-flip-vertical" aria-hidden="true"></i>
        </a>
        <div class="dokan-clearfix"></div>
    </div>

    <div class="dokan-section-content">
        <div class="dokan-form-group">
            <label for="mf_pbt_product_ids" class="form-label"><?php esc_html_e( 'Select Products', 'martfury' ); ?></label>
            <select class="dokan-form-control dokan-coupon-product-select dokan-product-search" multiple="multiple" style="width: 100%;" id="mf_pbt_product_ids[]" name="mf_pbt_product_ids[]" data-placeholder="<?php esc_attr_e( 'Search for a product&hellip;', 'martfury' ); ?>" data-action="dokan_json_search_products_and_variations" data-user_ids="<?php echo dokan_get_current_user_id(); ?>">
                <?php
                $product_ids = maybe_unserialize( get_post_meta( $post->ID, 'mf_pbt_product_ids', true ) );
                foreach ( $product_ids as $product_id ) {
                    $product = wc_get_product( $product_id );
                    if ( is_object( $product ) ) {
                        echo '<option value="' . esc_attr( $product_id ) . '"' . selected( true, true, false ) . '>' . wp_kses_post( $product->get_formatted_name() ) . '</option>';
                    }
                }
                ?>
            </select>
        </div>
        <div class="dokan-form-group">
            <label for="martfury_pbt_discount_all" class="form-label"><?php esc_html_e( 'Discount', 'martfury' ); ?></label>
            <?php dokan_post_input_box( $post_id, 'martfury_pbt_discount_all', array(), 'text' ); ?>
        </div>
        <div class="dokan-form-group">
            <?php
            $all = get_post_meta($post_id, 'martfury_pbt_checked_all', true);
            dokan_post_input_box(
                $post_id,
                'martfury_pbt_checked_all',
                [
                    'value' => $all,
                    'label' => __( 'Check All', 'martfury' ),
                ],
                'checkbox'
            );
            ?>
        </div>
        <div class="dokan-form-group">
            <label for="martfury_pbt_quantity_discount_all" class="form-label"><?php esc_html_e( 'Number of items to get discount', 'martfury' ); ?></label>
            <?php dokan_post_input_box( $post_id, 'martfury_pbt_quantity_discount_all', array(), 'text' ); ?>
        </div>
    </div>
</div><!-- .dokan-other-options -->
