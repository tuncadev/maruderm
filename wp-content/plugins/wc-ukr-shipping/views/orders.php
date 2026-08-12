<div class="wcus-layout">
    <div class="wcus-settings wcus-settings--full" style="width: 100%;">
        <div class="wcus-settings__header">
            <h1 class="wcus-settings__title"><?php esc_html_e('Orders', 'wc-ukr-shipping-i18n'); ?></h1>
        </div>
        <div class="wcus-settings__content">
            <div id="wcus-order-list"></div>
        </div>
    </div>
</div>

<script>
    (function ($) {
        $(function () {
            window.WcusOrders.init({});
        });
    })(jQuery);
</script>