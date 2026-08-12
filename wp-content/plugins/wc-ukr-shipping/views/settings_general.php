<?php
  if ( ! defined('ABSPATH')) {
      exit;
  }

  use \kirillbdev\WCUSCore\Foundation\View;
?>

<?php echo View::render('partial/settings_top', ['section' => 'general']); ?>

<div class="wcus-layout">

    <div class="wcus-settings-layout">

        <div id="wc-ukr-shipping-settings" class="wcus-settings wcus-settings--full">
            <div class="wcus-settings__header">
                <div class="wcus-card-icon"><?php echo wc_ukr_shipping_import_svg('settings.svg') ?></div>
                <h1 class="wcus-settings__title">
                    <?php esc_html_e('Settings', 'wc-ukr-shipping-i18n'); ?>
                </h1>
                <div class="wcus-settings__head-buttons">
                    <button type="submit" form="wc-ukr-shipping-settings-form" class="wcus-settings__submit wcus-btn wcus-btn--primary wcus-btn--md">
                        <?php esc_html_e('Save', 'wc-ukr-shipping-i18n'); ?>
                    </button>
                </div>
                <div id="wcus-settings-success-msg" class="wcus-settings__success wcus-message wcus-message--success"></div>
            </div>
            <div class="wcus-settings__content">
                <form id="wc-ukr-shipping-settings-form" action="/" method="POST">
                    <ul class="wcus-tabs">
                        <li data-pane="wcus-pane-carriers" class="active"><?php esc_html_e('Carriers', 'wc-ukr-shipping-i18n'); ?></li>
                        <li data-pane="wcus-pane-pickup-points"><?php esc_html_e('Pickup points', 'wc-ukr-shipping-i18n'); ?></li>
                        <li data-pane="wcus-pane-checkout"><?php esc_html_e('Checkout', 'wc-ukr-shipping-i18n'); ?></li>
                        <li data-pane="wcus-pane-parcels"><?php esc_html_e('Shipping Label', 'wc-ukr-shipping-i18n'); ?></li>
                    </ul>
                    <?php echo View::render('partial/settings_general/carriers'); ?>
                    <?php echo View::render('partial/settings_general/pickup_points'); ?>
                    <?php echo View::render('partial/settings_general/checkout'); ?>
                    <?php echo View::render('partial/settings_general/parcels', [
                        'payment_methods' => $payment_methods,
                        'cod_payment_id' => $cod_payment_id,
                    ]); ?>
                </form>
            </div>
        </div>

    </div>

    <?php echo View::render('partial/pro_promotion'); ?>

</div>
