<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Modules\Backend;

use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use kirillbdev\WCUkrShipping\Api\SmartyParcelWPApi;
use kirillbdev\WCUkrShipping\Helpers\SmartyParcelHelper;
use kirillbdev\WCUkrShipping\Helpers\WCUSHelper;
use kirillbdev\WCUkrShipping\Http\Controllers\OrdersController;
use kirillbdev\WCUkrShipping\Http\WpHttpClient;
use kirillbdev\WCUSCore\Foundation\View;
use kirillbdev\WCUkrShipping\DB\Repositories\ShippingLabelsRepository;
use kirillbdev\WCUSCore\Contracts\ModuleInterface;
use kirillbdev\WCUSCore\Http\Routing\Route;

class Orders implements ModuleInterface
{
    private ShippingLabelsRepository $shippingLabelsRepository;
    private SmartyParcelWPApi $smartyParcelApi;

    public function __construct(
        ShippingLabelsRepository $shippingLabelsRepository,
        SmartyParcelWPApi $smartyParcelApi
    ) {
        $this->shippingLabelsRepository = $shippingLabelsRepository;
        $this->smartyParcelApi = $smartyParcelApi;
    }

    public function init()
    {
        // Admin orders
        add_filter('manage_edit-shop_order_columns', [$this, 'extendOrderColumns']);
        add_action('manage_shop_order_posts_custom_column', [$this, 'renderTTNButton'], 10, 2);

        // Admin orders HPOS
        add_filter('manage_woocommerce_page_wc-orders_columns', [$this, 'extendOrderColumns']);
        add_action('manage_woocommerce_page_wc-orders_custom_column', [$this, 'renderTTNButtonHPOS'], 10, 2);

        add_action('init', [$this, 'handlePrintPage']);
        add_action('init', [$this, 'handleBatchDownloadPage']);

        add_action('add_meta_boxes', [$this, 'addTTNBlockToOrderEdit']);
        add_action('woocommerce_after_order_itemmeta', [$this, 'renderEditBtn'], 10, 2);
    }

    public function routes()
    {
        return [
            new Route('wcus_orders_list', OrdersController::class, 'getOrders'),
        ];
    }

    public function extendOrderColumns($columns)
    {
        $columns['wcus_ttn_actions'] = '<span class="wcus-sp-label-col">SmartyParcel</span>';

        return $columns;
    }

    public function renderTTNButton($column, $postId)
    {
        $this->renderTtnInfo(wc_get_order($postId), $column);
    }

    public function renderTTNButtonHPOS($column, $order)
    {
        $this->renderTtnInfo($order, $column);
    }

    public function handlePrintPage(): void
    {
        $isPrintLabelPage = isset($_GET['page'])
            && $_GET['page'] === 'wc_ukr_shipping_print_label'
            && isset($_GET['label_id'])
            && isset($_GET['format']);

        if (!is_admin() || !$isPrintLabelPage) {
            return;
        }

        if (!get_option(WCUS_OPTION_SMARTY_PARCEL_API_KEY)) {
            return;
        }

        $shippingLabel = $this->shippingLabelsRepository->findById((int)sanitize_text_field($_GET['label_id']));
        if ($shippingLabel === null) {
            return;
        }

        $validFormats = array_keys(WCUSHelper::getLabelDownloadFormats($shippingLabel['carrier_slug']));
        $format = sanitize_text_field($_GET['format']);
        if (count($validFormats) === 0 || !in_array($format, $validFormats, true)) {
            return;
        }

        $response = $this->smartyParcelApi->sendRequest(
            '/v1/downloads/l5/:uuid.pdf',
            null,
            [
                'layout' => $format,
            ],
            [
                'uuid' => $shippingLabel['label_id'],
            ]
        );

        header('Location: ' . $response['url']);
        exit;
    }

    public function handleBatchDownloadPage(): void
    {
        $isCurrentRoute = isset($_GET['page'])
                && $_GET['page'] === 'wc_ukr_shipping_batch_download'
                && isset($_GET['batch_id']);

        if (!is_admin() || !$isCurrentRoute) {
            return;
        }

        if (!get_option(WCUS_OPTION_SMARTY_PARCEL_API_KEY)) {
            return;
        }

        $response = $this->smartyParcelApi->sendRequest(
            '/v1/downloads/b6/:uuid.pdf',
            null,
            [],
            [
                'uuid' => sanitize_text_field($_GET['batch_id']),
            ]
        );

        header('Location: ' . $response['url']);
        exit;
    }

    public function addTTNBlockToOrderEdit()
    {
        /** @var CustomOrdersTableController $controller */
        $controller = wcus_wc_container_safe_get(CustomOrdersTableController::class);
        $screen = $controller !== null && $controller ->custom_orders_table_usage_is_enabled()
            ? wc_get_page_screen_id('shop-order')
            : 'shop_order';

        add_meta_box(
            'wcus_edit_order_ttn_metabox',
            __('SmartyParcel', 'wc-ukr-shipping-i18n'),
            [$this, 'editOrderTTNMetaboxHtml'],
            $screen,
            'side'
        );
    }

    public function editOrderTTNMetaboxHtml($editedOrder)
    {
        $order = ($editedOrder instanceof \WP_Post) ? wc_get_order($editedOrder->ID) : $editedOrder;
        if (!$order) {
            return;
        }

        if (!SmartyParcelHelper::canPurchaseLabelForOrder($order)) {
            esc_html_e('Purchase label is not available for this order.', 'wc-ukr-shipping-i18n');
            return;
        }

        $data['shipping_label'] = $this->shippingLabelsRepository->findByOrderId((int)$order->get_id());
        if ($data['shipping_label'] === null && $order->get_meta('_smartyparcel_label_id')) {
            $data['shipping_label'] = $this->shippingLabelsRepository->findById((int)$order->get_meta('_smartyparcel_label_id'));
        }

        $data['order_id'] = $order->get_id();
        $data['carrier'] = $data['shipping_label']['carrier_slug'] ?? null;
        if ($data['carrier'] === 'wcus_pro') {
            $data['carrier'] = 'nova_poshta';
        }
        $data['download_formats'] = WCUSHelper::getLabelDownloadFormats($data['carrier']);

        if ($order->get_meta('_wcus_automation_error')) {
            $data['automationError'] = $order->get_meta('_wcus_automation_error');
            $order->delete_meta_data('_wcus_automation_error');
            $order->save_meta_data();
        }

        echo View::render('order/edit_order_metabox', $data);
    }

    /**
     * @param int $itemId
     * @param \WC_Order_Item_Shipping $item
     */
    public function renderEditBtn(int $itemId, $item): void
    {
        if ( ! is_a($item, 'WC_Order_Item_Shipping') || $item->get_method_id() !== WC_UKR_SHIPPING_NP_SHIPPING_NAME) {
            return;
        }

        ?>
        <div class="wcus-order-shipping__edit-wrap">
            <button id="wcus-edit-shipping-btn" data-order-id="<?php echo esc_attr($item->get_order_id()); ?>" class="wcus-btn wcus-btn--default wcus-btn--xs">
                <?php esc_html_e('Edit shipping address', 'wc-ukr-shipping-i18n'); ?>
            </button>
        </div>
        <?php
    }

    /**
     * @param \WC_Order $order
     * @param string $column
     * @return void
     */
    private function renderTtnInfo($order, string $column): void
    {
        if ($column === 'wcus_ttn_actions') {
            if (!SmartyParcelHelper::canPurchaseLabelForOrder($order)) {
                return;
            }

            $ttn = $this->shippingLabelsRepository->findByOrderId((int)$order->get_id());
            if ($ttn === null && $order->get_meta('_smartyparcel_label_id')) {
                $ttn = $this->shippingLabelsRepository->findById((int)$order->get_meta('_smartyparcel_label_id'));
            }

            $carrier = null;
            if ($ttn !== null) {
                $carrier = $ttn['carrier_slug'];
                if (in_array($carrier, ['wcus_pro', 'nova_global'])) {
                    $carrier = 'nova_poshta';
                }
            }

            echo View::render('order/ttn_widget', [
                'ttn' => $ttn,
                'carrier' => $carrier,
                'order' => $order,
            ]);
        }
    }
}
