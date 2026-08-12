<?php
    use kirillbdev\WCUkrShipping\Helpers\HtmlHelper;
?>

<?php if (isset($successMsg)) { ?>
    <div id="wcus-automation-success" class="notice inline notice-success notice-alt" style="padding-top: 10px; padding-bottom: 10px;">
        <?= $successMsg; ?>
    </div>
<?php } ?>

<form id="wcus-automation-rule-form" method="POST" action="#">
    <div class="wcus-settings wcus-settings--full">
        <div class="wcus-settings__header">
            <h1 class="wcus-settings__title"><?php esc_html_e('Rule constructor', 'wc-ukr-shipping-i18n'); ?></h1>
            <div class="wcus-settings__head-buttons">
                <button type="submit" class="wcus-settings__submit wcus-btn wcus-btn--primary wcus-btn--md">
                    <?php esc_html_e('Save', 'wc-ukr-shipping-i18n'); ?>
                </button>
            </div>
        </div>
        <div class="wcus-settings__content">
            <input type="hidden" name="rule_id" value="<?php echo esc_attr($model !== null ? $model->id : 0); ?>" />
            <?php
                HtmlHelper::textField(
                    'rule_name',
                    __('Name', 'wc-ukr-shipping-i18n'),
                    $model->name ?? ''
                );

                HtmlHelper::switcherField(
                    'active',
                    __('Active', 'wc-ukr-shipping-i18n'),
                    $model !== null ? (bool)$model->active : true
                );
            ?>
            <div id="wcus-automation-app"></div>
        </div>
    </div>
</form>
<script>
    (function ($) {
        $(function () {
            <?php if ($model !== null) { ?>
                window.WcusAutomation.init({
                    event: {
                        type: '<?php echo esc_js($model->event_name); ?>',
                        params: <?php echo $model->event_data; ?>
                    },
                    actions: <?php echo json_encode($model->actions); ?>
                });
            <?php } else { ?>
                window.WcusAutomation.init();
            <?php } ?>
        });
    })(jQuery);
</script>