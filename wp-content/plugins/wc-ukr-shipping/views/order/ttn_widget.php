<?php
    if ( ! defined('ABSPATH')) {
        exit;
    }
?>

<?php if ($ttn !== null) { ?>
    <div class="wcus-icon-block" style="text-align: center;">
        <div class="wcus-label-widget j-wcus-label-widget">
            <span class="wcus-label-widget__label <?php echo esc_attr($carrier !== null ? 'wcus-label-widget__label--' . $carrier : ''); ?>">
                <?php echo esc_html($ttn['tracking_number']); ?>
            </span>
            <?php if ($ttn['carrier_slug'] === 'wcus_pro') { ?>
                <span style="color: #ff4500; font-size: 12px; margin-left: 4px;" title="WC Ukraine Shipping PRO">*</span>
            <?php } ?>
        </div>
        <div style="text-align: center;">
            <a href="#" class="wcus-svg-btn wcus-svg-btn--error j-wcus-label-delete"
               style="font-size: 13px; color: #f00;"
               data-label-id="<?php echo esc_attr($ttn['id']); ?>">
                <?php esc_html_e('Delete', 'wc-ukr-shipping-i18n'); ?>
            </a>
        </div>
    </div>
<?php } else { ?>
    <div style="text-align: center;">
        <a href="<?php echo esc_attr(admin_url('admin.php?page=wc_ukr_shipping_ttn&order_id=' . $order->get_id())); ?>"
           class="wcus-svg-btn" style="margin-right: 8px;">
            <?php esc_html_e('Create', 'wc-ukr-shipping-i18n'); ?>
        </a>
        <a href="#" class="wcus-svg-btn j-wcus-label-attach" data-order-id="<?php echo esc_attr($order->get_id()); ?>">
            <?php esc_html_e('Attach', 'wc-ukr-shipping-i18n'); ?>
        </a>
    </div>
<?php } ?>
