<?php
    if ( ! defined('ABSPATH')) {
        exit;
    }

    use \kirillbdev\WCUkrShipping\Helpers\HtmlHelper;
    use \kirillbdev\WCUSCore\Foundation\View;
?>

<?php echo View::render('partial/locator_message'); ?>

<?php
    HtmlHelper::selectField(
        'wc_ukr_shipping[np_block_pos]',
        __('Shipping block position on checkout page', 'wc-ukr-shipping'),
        [
            'billing' => __('Default section', 'wc-ukr-shipping'),
            'additional' => __('Additional section', 'wc-ukr-shipping'),
        ],
        wc_ukr_shipping_get_option('wc_ukr_shipping_np_block_pos')
    );
?>

<div class="wcus-form-group">
    <label for="wc_ukr_shipping_spinner_color"><?= __('Color of spinner in frontend', 'wc-ukr-shipping'); ?></label>
    <input name="wc_ukr_shipping[spinner_color]" id="wc_ukr_shipping_spinner_color" type="text" value="<?= get_option('wc_ukr_shipping_spinner_color', '#dddddd'); ?>" />
</div>

<?php
    HtmlHelper::switcherField(
        'wc_ukr_shipping[np_save_warehouse]',
        __('Save last customer address', 'wc-ukr-shipping'),
        (int)wc_ukr_shipping_get_option('wc_ukr_shipping_np_save_warehouse')
    );

    HtmlHelper::switcherField(
        'wcus[inject_additional_fields]',
        __('Inject additional shipping fields', 'wc-ukr-shipping'),
        (int)wc_ukr_shipping_get_option('wcus_inject_additional_fields') === 1
    );

    HtmlHelper::switcherField(
        'wcus[rates_convert_currency]',
        __('Use currency conversion on rates estimation', 'wc-ukr-shipping'),
        (int)wc_ukr_shipping_get_option('wcus_rates_convert_currency') === 1,
        __('Carriers often return shipping costs in the destination currency. If this option is enabled, SmartyParcel will convert the shipping cost to the store’s selected currency using average worldwide exchange rates.', 'wc-ukr-shipping'),
    );
?>
