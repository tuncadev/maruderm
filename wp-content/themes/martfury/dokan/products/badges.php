<div class="dokan-badges-options dokan-edit-row dokan-clearfix">
    <div class="dokan-section-heading" data-togglehandler="dokan_badges_options">
        <h2><i class="fas fa-cog" aria-hidden="true"></i> <?php esc_html_e( 'Badges Options', 'martfury' ); ?></h2>
        <a href="#" class="dokan-section-toggle">
            <i class="fas fa-sort-down fa-flip-vertical" aria-hidden="true"></i>
        </a>
        <div class="dokan-clearfix"></div>
    </div>

    <div class="dokan-section-content">
        <div class="dokan-form-group">
            <?php
            $is_new = get_post_meta($post_id, '_is_new', true);
            dokan_post_input_box(
                $post_id,
                '_is_new',
                [
                    'value' => $is_new,
                    'label' => __( 'New product?', 'martfury' ),
                ],
                'checkbox'
            );
            ?>
        </div>

        <div class="dokan-form-group">
            <label for="custom_badges_text" class="form-label"><?php esc_html_e( 'Custom Badge Text', 'martfury' ); ?></label>
            <?php dokan_post_input_box( $post_id, 'custom_badges_text', array(), 'textarea' ); ?>
        </div>
    </div>
</div><!-- .dokan-other-options -->
