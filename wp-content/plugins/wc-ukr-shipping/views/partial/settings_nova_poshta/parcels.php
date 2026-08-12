<?php
    if ( ! defined('ABSPATH')) {
        exit;
    }

    use \kirillbdev\WCUkrShipping\Helpers\HtmlHelper;
?>

<div id="wcus-pane-parcels" class="wcus-tab-pane">

    <div class="wcus-message wcus-message--warning wcus-mb-2">
        <?php esc_html_e('The plugin must be connected to the Smarty Parcel service to use the functionality of creating a TTN', 'wc-ukr-shipping-i18n'); ?>
        <a href="https://smartyparcel.com/docs/wcus-smarty-parcel-connect/" target="_blank"><?php esc_html_e('Documentation', 'wc-ukr-shipping-i18n'); ?></a>
    </div>

    <?php $payer = wc_ukr_shipping_get_option('wc_ukr_shipping_np_ttn_payer_default'); ?>
    <div class="wcus-form-group">
        <label for="wc_ukr_shipping_np_ttn_payer_default"><?php esc_html_e('Delivery payer', 'wc-ukr-shipping-i18n'); ?></label>
        <select id="wc_ukr_shipping_np_ttn_payer_default"
                name="wc_ukr_shipping[np_ttn_payer_default]"
                class="wcus-form-control">
            <option value="Sender" <?php echo $payer === 'Sender' ? 'selected' : ''; ?>><?php esc_html_e('Sender', 'wc-ukr-shipping-i18n'); ?></option>
            <option value="Recipient" <?php echo $payer === 'Recipient' ? 'selected' : ''; ?>><?php esc_html_e('Recipient', 'wc-ukr-shipping-i18n'); ?></option>
        </select>
    </div>

    <?php $paymentMethod = wc_ukr_shipping_get_option('wcus_np_payment_method_default'); ?>
    <div class="wcus-form-group">
        <label for="wcus_np_payment_method_default"><?php esc_html_e('Payment method', 'wc-ukr-shipping-i18n'); ?></label>
        <select id="wcus_np_payment_method_default"
                name="wcus[np_payment_method_default]"
                class="wcus-form-control">
            <option value="Cash" <?php echo $paymentMethod === 'Cash' ? 'selected' : ''; ?>><?php esc_html_e('Cash', 'wc-ukr-shipping-i18n'); ?></option>
            <option value="NonCash" <?php echo $paymentMethod === 'NonCash' ? 'selected' : ''; ?>><?php esc_html_e('NonCash', 'wc-ukr-shipping-i18n'); ?></option>
        </select>
    </div>

    <?php
        HtmlHelper::switcherField(
            'wcus[ttn_pay_control_default]',
            __('Payment control', 'wc-ukr-shipping-i18n'),
            $payment_control_default === 1
        );

        HtmlHelper::switcherField(
            'wcus[ttn_global_params_default]',
            __('Global params as default', 'wc-ukr-shipping-i18n'),
            (int)wc_ukr_shipping_get_option('wcus_ttn_global_params_default') === 1
        );
    ?>

</div>
