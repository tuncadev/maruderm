<?php
  if ( ! defined('ABSPATH')) {
      exit;
  }

  use \kirillbdev\WCUSCore\Foundation\View;

  /** @var array<int, array<string, mixed>> $carriers */
?>

<div class="wcus-layout">

    <div class="wcus-settings-layout">

        <div class="wcus-settings wcus-settings--full">
            <div class="wcus-settings__header">
                <div class="wcus-card-icon"><?php echo wc_ukr_shipping_import_svg('truck.svg') ?></div>
                <h1 class="wcus-settings__title">
                    <?php esc_html_e('Carriers', 'wc-ukr-shipping'); ?>
                </h1>
            </div>
            <div class="wcus-settings__content">
                <?php echo View::render('partial/settings_general/carriers', ['carriers' => $carriers]); ?>
            </div>
        </div>

        <div id="wc-ukr-shipping-settings" class="wcus-settings wcus-settings--full">
            <div class="wcus-settings__header">
                <div class="wcus-card-icon"><?php echo wc_ukr_shipping_import_svg('settings.svg') ?></div>
                <h1 class="wcus-settings__title">
                    <?php esc_html_e('Checkout', 'wc-ukr-shipping'); ?>
                </h1>
                <div class="wcus-settings__head-buttons">
                    <button type="submit" form="wc-ukr-shipping-settings-form" class="wcus-settings__submit wcus-btn wcus-btn--primary wcus-btn--md">
                        <?php esc_html_e('Save', 'wc-ukr-shipping'); ?>
                    </button>
                </div>
                <div id="wcus-settings-success-msg" class="wcus-settings__success wcus-message wcus-message--success"></div>
            </div>
            <div class="wcus-settings__content">
                <form id="wc-ukr-shipping-settings-form" action="/" method="POST">
                    <?php echo View::render('partial/settings_general/checkout'); ?>
                </form>
            </div>
        </div>

    </div>

    <?php echo View::render('partial/pro_promotion'); ?>

</div>
