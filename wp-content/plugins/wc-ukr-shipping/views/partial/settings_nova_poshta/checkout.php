<?php
    if ( ! defined('ABSPATH')) {
        exit;
    }

    use \kirillbdev\WCUkrShipping\Helpers\HtmlHelper;
    use \kirillbdev\WCUSCore\Foundation\View;
?>

<div id="wcus-pane-checkout" class="wcus-tab-pane active">

    <div class="wcus-form-group">
        <label for="wc_ukr_shipping_np_lang"><?= __('Display language of cities and departments', 'wc-ukr-shipping-i18n'); ?></label>
        <select id="wc_ukr_shipping_np_lang"
                name="wc_ukr_shipping[np_lang]"
                class="wcus-form-control">
            <option value="ru" <?= get_option('wc_ukr_shipping_np_lang', 'uk') === 'ru' ? 'selected' : ''; ?>><?= __('Russian', 'wc-ukr-shipping-i18n'); ?></option>
            <option value="uk" <?= get_option('wc_ukr_shipping_np_lang', 'uk') === 'uk' ? 'selected' : ''; ?>><?= __('Ukrainian', 'wc-ukr-shipping-i18n'); ?></option>
        </select>
    </div>

    <?php
        \kirillbdev\WCUkrShipping\Helpers\HtmlHelper::switcherField(
            'wcus[np_use_online_directory]',
            __('Use Nova Poshta online directory for search settlements', 'wc-ukr-shipping-i18n'),
            (int)wc_ukr_shipping_get_option('wcus_np_use_online_directory'),
            __('If enabled, the plugin will use online directory api for both warehouse and address delivery', 'wc-ukr-shipping-i18n'),
        );
    ?>
</div>
