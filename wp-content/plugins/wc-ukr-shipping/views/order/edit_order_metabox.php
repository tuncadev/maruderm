<?php
    if ( ! defined('ABSPATH')) {
        exit;
    }
?>

<div class="wcus-icon-block">
    <?php if ($shipping_label !== null) { ?>
        <?php if (empty($shipping_label['label_id'] ) || $shipping_label['carrier_slug'] === 'wcus_pro') { ?>
            <div style="text-align: center;">
                <div class="wcus-label-widget wcus-label-widget--lg wcus-mb-1 <?php echo esc_attr($carrier !== null ? 'wcus-label-widget__label--' . $carrier : ''); ?>">
                    <?php echo esc_html($shipping_label['tracking_number']); ?>
                    <?php if ($shipping_label['carrier_slug'] === 'wcus_pro') { ?>
                        <span style="color: #ff4500; font-size: 12px; margin-left: 4px;" title="WC Ukraine Shipping PRO">*</span>
                    <?php } ?>
                </div>
                <?php if (!empty($shipping_label['carrier_status_code']) || !empty($shipping_label['carrier_status'])) { ?>
                    <div class="wcus-text-center wcus-mb-1" style="color: #666;">
                        <?php echo esc_html($shipping_label['carrier_status']); ?>
                        [<?php echo esc_html($shipping_label['carrier_status_code']); ?>]
                    </div>
                <?php } elseif (!empty($shipping_label['tracking_status'])) { ?>
                    <div class="wcus-text-center wcus-mb-1" style="color: #666;">
                        Tracking API: <?php echo esc_html($shipping_label['tracking_status']); ?>
                    </div>
                <?php } ?>
                <div class="wcus-mb-1">
                    <a href="#" class="wcus-svg-btn wcus-svg-btn--error j-wcus-label-delete"
                       style="font-size: 13px; color: #f00;"
                       data-label-id="<?php echo esc_attr($shipping_label['id']); ?>">
                        <?php esc_html_e('Delete', 'wc-ukr-shipping-i18n'); ?>
                    </a>
                </div>
            </div>
        <?php } else { ?>
            <div id="smartyparcel-shipment-summary"
                 data-id="<?php echo esc_attr($shipping_label['id']); ?>"
                 data-label-id="<?php echo esc_attr($shipping_label['label_id']); ?>"></div>
        <?php } ?>
    <?php } else { ?>
        <?php if (isset($automationError)) { ?>
            <div class="wcus-mb-1" style="color: #fd3939;"><?php echo esc_html($automationError); ?></div>
        <?php } ?>
        <div style="text-align: center; padding: 16px;">
            <div class="wcus-mb-1">
                <a href="<?php echo esc_attr(admin_url('admin.php?page=wc_ukr_shipping_ttn&order_id=' . $order_id)); ?>"
                   class="wcus-btn wcus-btn--docs wcus-btn--sm">
                    <?php esc_html_e('Create shipping label', 'wc-ukr-shipping-i18n'); ?>
                </a>
            </div>
            <a class="wcus-btn wcus-btn--docs wcus-btn--sm j-wcus-label-attach" data-order-id="<?php echo esc_attr($order_id); ?>">
                <?php esc_html_e('Attach shipping label', 'wc-ukr-shipping-i18n'); ?>
            </a>
        </div>
    <?php } ?>
</div>