<?php
if ( ! defined('ABSPATH')) {
    exit;
}
?>

<div class="wcus-layout">

    <div class="wcus-settings-layout" style="width: 90%;">
        <div id="wcus-smarty-parcel-settings" class="wcus-settings">
            <div class="wcus-settings__header">
                <h1 class="wcus-settings__title"><?php esc_html_e('New TTN', 'wc-ukr-shipping-i18n'); ?></h1>
            </div>
            <div class="wcus-settings__content">
                <div class="wcus-mb-2">
                    <?php esc_html_e('The order has non-standard shipping method', 'wc-ukr-shipping-i18n'); ?> - <strong><?php echo esc_html($shippingMethod); ?></strong>
                </div>

                <a href="<?php echo esc_attr($novaPoshtaFormUrl); ?>"
                   class="wcus-btn wcus-btn--default wcus-btn--sm"
                   style="padding: 10px 24px;">
                    <?php esc_html_e('Create for Nova Poshta', 'wc-ukr-shipping-i18n'); ?>
                </a>
                <a href="<?php echo esc_attr($ukrposhtaFormUrl); ?>"
                   class="wcus-btn wcus-btn--default wcus-btn--sm"
                   style="padding: 10px 24px;">
                    <?php esc_html_e('Create for Ukrposhta', 'wc-ukr-shipping-i18n'); ?>
                </a>

            </div>
        </div>
    </div>

</div>
