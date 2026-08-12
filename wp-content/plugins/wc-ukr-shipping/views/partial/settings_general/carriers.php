<?php
    if ( ! defined('ABSPATH')) {
        exit;
    }

    use \kirillbdev\WCUkrShipping\Helpers\HtmlHelper;
    use kirillbdev\WCUkrShipping\Enums\CarrierSlug;
    use kirillbdev\WCUkrShipping\Helpers\WCUSHelper;
?>

<div id="wcus-pane-carriers" class="wcus-tab-pane active">

    <div class="wcus-message wcus-message--info wcus-mb-3">
        <div style="font-size: 14px; line-height: 1.4;">
            <?php esc_html_e('On this page, you can enable the carriers you want to integrate with your store.', 'wc-ukr-shipping-i18n'); ?>
            <a target="_blank" href="https://smartyparcel.com/docs/platform-supported-carriers/"><?php esc_html_e('List of supported carriers', 'wc-ukr-shipping-i18n'); ?></a>
        </div>
    </div>

    <?php
        $activeCarriers = WCUSHelper::safeGetJsonOption('wcus_active_carriers');
        $carrierOptions = [
            CarrierSlug::NOVA_POSHTA => 'Nova Poshta (Ukraine)',
            CarrierSlug::UKRPOSHTA => 'Ukrposhta',
            CarrierSlug::ROZETKA_DELIVERY => 'Rozetka Delivery (Ukraine)',
            CarrierSlug::NOVA_POST => 'Nova Post',
            CarrierSlug::NOVA_GLOBAL => 'Nova Global',
            CarrierSlug::MEEST => 'Meest',
        ];

        foreach ($carrierOptions as $carrierSlug => $name) {
            HtmlHelper::switcherField(
                'wcus[active_carriers][]',
                $name,
                in_array($carrierSlug, $activeCarriers),
                null,
                $carrierSlug
            );
        }
    ?>

</div>
