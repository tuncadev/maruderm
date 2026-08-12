<?php
if ( ! defined('ABSPATH')) {
    exit;
}
?>

<div class="wcus-pro-features">
    <div class="wcus-card wcus-mb-2">
        <div class="wcus-card__header">
            <div class="wcus-card-icon"><?php echo wc_ukr_shipping_import_svg('help.svg') ?></div>
            <div class="wcus-card__title wcus-pro-features__title">
                <?php esc_html_e('Need help?', 'wc-ukr-shipping-i18n'); ?>
            </div>
        </div>
        <div class="wcus-card__content">
            <a target="_blank"
               href="https://smartyparcel.com/docs/knowledge-base-woocommerce/"
               class="wcus-btn wcus-btn--docs wcus-btn--md wcus-btn--block wcus-mb-1">
                <?php echo wc_ukr_shipping_import_svg('docs.svg'); ?>
                <?php esc_html_e('Documentation', 'wc-ukr-shipping-i18n'); ?>
            </a>

            <a target="_blank"
               href="https://t.me/smarty_parcel_support_bot"
               class="wcus-btn wcus-btn--telegram wcus-btn--md wcus-btn--block">
                <?php echo wc_ukr_shipping_import_svg('support.svg'); ?>
                <?php esc_html_e('Open support chat', 'wc-ukr-shipping-i18n'); ?>
            </a>
        </div>
    </div>

    <?php if (!isset($hidePromo)) { ?>
        <div class="wcus-card">
            <div class="wcus-card__content">
                <div class="wcus-pro-features__header">
                    <div class="wcus-pro-features__logo">
                        <img src="<?php echo esc_attr(WC_UKR_SHIPPING_PLUGIN_URL . '/image/smarty-parcel.jpg'); ?>" />
                    </div>
                    <div class="wcus-card__title wcus-pro-features__title"><?php esc_html_e('Unlock more features with paid plans', 'wc-ukr-shipping-i18n'); ?></div>
                </div>
                <div class="wcus-pro-features__list">
                    <div class="wcus-pro-features__feature">
                        <?php esc_html_e('More shipments limit', 'wc-ukr-shipping-i18n'); ?>
                    </div>
                    <div class="wcus-pro-features__feature">
                        <?php esc_html_e('More carrier accounts', 'wc-ukr-shipping-i18n'); ?>
                    </div>
                    <div class="wcus-pro-features__feature">
                        <?php esc_html_e('Automatic calculation of shipping costs.', 'wc-ukr-shipping-i18n'); ?>
                    </div>
                    <div class="wcus-pro-features__feature">
                        <?php esc_html_e('Shipping calculation based on order total', 'wc-ukr-shipping-i18n'); ?>
                    </div>
                    <div class="wcus-pro-features__feature">
                        <?php esc_html_e('Possibility of mass generation of TTN in one click', 'wc-ukr-shipping-i18n'); ?>
                    </div>
                    <div class="wcus-pro-features__feature">
                        <?php esc_html_e('Extended parcels analytics', 'wc-ukr-shipping-i18n'); ?>
                    </div>
                    <div class="wcus-pro-features__feature">
                        <?php esc_html_e('Premium support', 'wc-ukr-shipping-i18n'); ?>
                    </div>
                </div>

                <a target="_blank"
                   href="https://smartyparcel.com/app-pricing/"
                   class="wcus-btn wcus-btn--md wcus-btn--block wcus-pro-features__btn">
                    <?php echo wc_ukr_shipping_import_svg('star.svg'); ?>
                    <?php esc_html_e('Switch to paid plan', 'wc-ukr-shipping-i18n'); ?>
                </a>

            </div>
        </div>
    <?php } ?>
</div>
