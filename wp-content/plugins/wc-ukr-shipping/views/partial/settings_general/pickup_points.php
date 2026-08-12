<?php
    if ( ! defined('ABSPATH')) {
        exit;
    }

    use \kirillbdev\WCUkrShipping\Helpers\HtmlHelper;
    use \kirillbdev\WCUSCore\Foundation\View;
?>

<div id="wcus-pane-pickup-points" class="wcus-tab-pane">

    <?php echo View::render('partial/locator_message'); ?>

    <div style="margin-bottom: 20px;">
        <div id="wcus-warehouse-loader"></div>
    </div>

    <?php
        HtmlHelper::textField(
            'wcus[ukrposhta_bearer_ecom]',
            __('Bearer eCom token of Ukrposhta', 'wc-ukr-shipping-i18n'),
            wc_ukr_shipping_get_option('wcus_ukrposhta_bearer_ecom'),
            __('Bearer eCom token is required to search for warehouses across Ukraine.', 'wc-ukr-shipping-i18n')
        );

        HtmlHelper::textField(
            'wcus[nova_post_api_key]',
            __('API Key of Nova Post', 'wc-ukr-shipping-i18n'),
            wc_ukr_shipping_get_option('wcus_nova_post_api_key'),
            __('API key is required to search for warehouses and PUDO across Europe', 'wc-ukr-shipping-i18n')
        );

        HtmlHelper::textField(
            'wcus[meest_api_token]',
            __('API Token of Meest Post', 'wc-ukr-shipping-i18n'),
            wc_ukr_shipping_get_option('wcus_meest_api_token'),
            __('API token is required to search for warehouses and PUDO of Meest Post', 'wc-ukr-shipping-i18n')
        );
    ?>

</div>
