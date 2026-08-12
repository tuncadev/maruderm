<?php
    if ( ! defined('ABSPATH')) {
        exit;
    }

    use \kirillbdev\WCUkrShipping\Helpers\HtmlHelper;
?>

<div id="wcus-pane-checkout" class="wcus-tab-pane">

    <?php
        HtmlHelper::selectField(
            'wc_ukr_shipping[np_translates_type]',
            __('Load translates from', 'wc-ukr-shipping-i18n'),
            [
                '0' => __('Plugin settings', 'wc-ukr-shipping-i18n'),
                '1' => __('Wordpress localization files', 'wc-ukr-shipping-i18n'),
            ],
            wc_ukr_shipping_get_option('wc_ukr_shipping_np_translates_type'),
            __('If you are using language plugins such as WPML or Polylang - select "Wordpress localization files" option', 'wc-ukr-shipping-i18n')
        );

        HtmlHelper::selectField(
            'wc_ukr_shipping[np_block_pos]',
            __('Shipping block position on checkout page', 'wc-ukr-shipping-i18n'),
            [
                'billing' => __('Default section', 'wc-ukr-shipping-i18n'),
                'additional' => __('Additional section', 'wc-ukr-shipping-i18n'),
            ],
            wc_ukr_shipping_get_option('wc_ukr_shipping_np_block_pos')
        );
    ?>

    <div class="wcus-form-group">
        <label for="wc_ukr_shipping_spinner_color"><?= __('Color of spinner in frontend', 'wc-ukr-shipping-i18n'); ?></label>
        <input name="wc_ukr_shipping[spinner_color]" id="wc_ukr_shipping_spinner_color" type="text" value="<?= get_option('wc_ukr_shipping_spinner_color', '#dddddd'); ?>" />
    </div>

    <?php
        HtmlHelper::switcherField(
            'wc_ukr_shipping[np_save_warehouse]',
            __('Save last customer address', 'wc-ukr-shipping-i18n'),
            (int)wc_ukr_shipping_get_option('wc_ukr_shipping_np_save_warehouse')
        );

        HtmlHelper::switcherField(
            'wcus[inject_additional_fields]',
            __('Inject additional shipping fields', 'wc-ukr-shipping-i18n'),
            (int)wc_ukr_shipping_get_option('wcus_inject_additional_fields') === 1
        );

        HtmlHelper::switcherField(
            'wcus[rates_convert_currency]',
            __('Use currency conversion on rates estimation', 'wc-ukr-shipping-i18n'),
            (int)wc_ukr_shipping_get_option('wcus_rates_convert_currency') === 1,
            __('Carriers often return shipping costs in the destination currency. If this option is enabled, SmartyParcel will convert the shipping cost to the store’s selected currency using average worldwide exchange rates.', 'wc-ukr-shipping-i18n'),
        );
    ?>

</div>
