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
                <?php esc_html_e('The plugin must be connected to the Smarty Parcel service to use the functionality of creating a TTN', 'wc-ukr-shipping-i18n'); ?>
                <a href="https://smartyparcel.com/ua/docs/wcus-smarty-parcel-connect/"
                   target="_blank"
                   class="wcus-btn wcus-btn--primary wcus-btn--sm"
                   style="margin-left: 8px; padding: 6px 24px;">
                    <?php esc_html_e('Read more', 'wc-ukr-shipping-i18n'); ?>
                </a>
            </div>
        </div>
    </div>

</div>
