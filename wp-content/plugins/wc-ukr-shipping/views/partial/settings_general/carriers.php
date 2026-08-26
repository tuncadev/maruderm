<?php
    if ( ! defined('ABSPATH')) {
        exit;
    }

    /** @var array<int, array<string, mixed>> $carriers */
?>

<div class="wcus-message wcus-message--info wcus-mb-3">
    <div style="font-size: 14px; line-height: 1.4;">
        <div class="wcus-mb-2">
            <?php esc_html_e('Enable the carriers you want to integrate with your store.', 'wc-ukr-shipping'); ?>
        </div>
        <a target="_blank" href="https://smartyparcel.com/supported-carriers/">
            <?php esc_html_e('List of supported carriers and features', 'wc-ukr-shipping'); ?>
        </a>
    </div>
</div>

<div id="wcus-carriers" class="wcus-carriers">
    <?php foreach ($carriers as $carrier) { ?>
        <div class="wcus-carrier <?php echo $carrier['enabled'] ? '' : 'wcus-carrier--disabled'; ?>"
             data-carrier="<?php echo esc_attr($carrier['slug']); ?>">

            <img class="wcus-carrier__icon"
                 src="<?php echo esc_url($carrier['icon']); ?>"
                 alt="<?php echo esc_attr($carrier['name']); ?>">

            <div class="wcus-carrier__info">
                <div class="wcus-carrier__name"><?php echo esc_html($carrier['name']); ?></div>
                <div class="wcus-carrier__features">
                    <?php esc_html_e('Features:', 'wc-ukr-shipping'); ?>
                    <?php foreach ($carrier['features'] as $feature) { ?>
                        <span class="wcus-carrier__feature"><?php echo esc_html($feature); ?></span>
                    <?php } ?>
                </div>
            </div>

            <div class="wcus-carrier__actions">
                <?php if ($carrier['hasOptions']) { ?>
                    <button type="button" class="wcus-btn wcus-btn--outline wcus-btn--sm j-wcus-carrier-settings">
                        <?php esc_html_e('Settings', 'wc-ukr-shipping'); ?>
                    </button>
                <?php } ?>

                <label class="wcus-switcher">
                    <input type="checkbox"
                           class="j-wcus-carrier-toggle"
                           value="1"
                           aria-label="<?php echo esc_attr($carrier['name']); ?>" <?php checked($carrier['enabled']); ?>>
                    <span class="wcus-switcher__control"></span>
                </label>
            </div>

        </div>
    <?php } ?>

    <div class="wcus-carrier wcus-carrier--universal">
        <div class="wcus-carrier__icon wcus-carrier__icon--glyph">
            <?php echo wc_ukr_shipping_import_svg('truck.svg'); ?>
        </div>

        <div class="wcus-carrier__info">
            <div class="wcus-carrier__name"><?php esc_html_e('1000+ other carriers', 'wc-ukr-shipping'); ?></div>
            <div class="wcus-carrier__features">
                <span class="wcus-carrier__feature"><?php esc_html_e('Tracking', 'wc-ukr-shipping'); ?></span>
            </div>
            <div class="wcus-carrier__note">
                <?php esc_html_e('Shipments sent with any other carrier can be tracked by their tracking number.', 'wc-ukr-shipping'); ?>
            </div>
        </div>
    </div>
</div>
