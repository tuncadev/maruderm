<?php
    if ( ! defined('ABSPATH')) {
        exit;
    }

    use kirillbdev\WCUkrShipping\Helpers\HtmlHelper;
?>
<form method="POST" action="options.php">
    <div class="wcus-layout">

        <div class="wcus-settings-layout">
            <div class="wcus-settings">
                <div class="wcus-settings__header">
                    <h1 class="wcus-settings__title"><?php esc_html_e('Tools', 'wc-ukr-shipping'); ?></h1>
                    <div class="wcus-settings__head-buttons">
                        <button type="submit" class="wcus-settings__submit wcus-btn wcus-btn--primary wcus-btn--md">
                            <?php esc_html_e('Save', 'wc-ukr-shipping'); ?>
                        </button>
                    </div>
                </div>
                <div class="wcus-settings__content">
                    <?php settings_fields('wcus_settings_tools'); ?>

                    <?php if (($_GET['settings-updated'] ?? '') === 'true') { ?>
                        <div class="notice notice-success is-dismissible" style="margin: 0;">
                            <p><?php esc_html_e('Settings saved successfully!', 'wc-ukr-shipping'); ?></p>
                        </div>
                    <?php } ?>

                    <h3><?php esc_html_e('Migrate from WC Ukraine Shipping PRO', 'wc-ukr-shipping'); ?></h3>

                    <div class="wcus-control-group">
                        <div class="wcus-control-group__title"><?php esc_html_e('Sync old TTNs', 'wc-ukr-shipping'); ?></div>
                        <div class="wcus-control-group__content">
                            <div class="wcus-mb-2">
                                <?php echo sprintf(__('Available <strong>%d TTNs</strong> to sync', 'wc-ukr-shipping'), $legacyTtnCount); ?>
                            </div>
                            <div class="wcus-mb-1">
                                <button id="wcus-tools-migrate-ttn" class="wcus-settings__update-data wcus-btn wcus-btn--outline wcus-btn--sm">
                                    <?php esc_html_e('Start sync', 'wc-ukr-shipping'); ?>
                                </button>
                            </div>
                            <div id="wcus-tools-migrate-logs" class="wcus-warehouse-loader__logs"></div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <?php echo \kirillbdev\WCUSCore\Foundation\View::render('partial/pro_promotion'); ?>

    </div>
</form>