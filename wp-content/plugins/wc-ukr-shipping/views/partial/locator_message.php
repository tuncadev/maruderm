<?php
    if (!defined('ABSPATH')) {
        exit;
    }

    use \kirillbdev\WCUkrShipping\Helpers\HtmlHelper;
?>

<div class="wcus-message wcus-message--info wcus-mb-3">
    <?php
        HtmlHelper::switcherField(
            'wcus[use_smartyparcel_locator]',
            __('Use SmartyParcel Locator API', 'wc-ukr-shipping-i18n'),
            (int)wc_ukr_shipping_get_option('wcus_use_smartyparcel_locator')
        );
    ?>
    <div style="font-size: 14px; line-height: 1.4;">
        <?php esc_html_e('Connect your store to SmartyParcel to access a unified database of pickup points from multiple carriers — without worrying about data updates or signing additional agreements.', 'wc-ukr-shipping-i18n'); ?>
        <a target="_blank" href="https://smartyparcel.com/docs/smartyparcel-locator-for-woocommerce/"><?php esc_html_e('Learn more', 'wc-ukr-shipping-i18n'); ?></a>
    </div>
</div>
