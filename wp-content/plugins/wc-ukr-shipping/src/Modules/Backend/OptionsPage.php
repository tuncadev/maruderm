<?php

namespace kirillbdev\WCUkrShipping\Modules\Backend;

use kirillbdev\WCUkrShipping\Component\Carriers\Meest\Label\MeestOrderCollector;
use kirillbdev\WCUkrShipping\Component\Carriers\RozetkaDelivery\Label\PurchaseLabelDataCollector;
use kirillbdev\WCUkrShipping\Component\Carriers\RozetkaDelivery\Label\RozetkaOrderCollector;
use kirillbdev\WCUkrShipping\Component\Carriers\Ukrposhta\Label\SingleLabelDataCollector;
use kirillbdev\WCUkrShipping\Component\ListTable\AutomationListTable;
use kirillbdev\WCUkrShipping\Component\SmartyParcel\BaseOrderCollector;
use kirillbdev\WCUkrShipping\DB\Repositories\AutomationRulesRepository;
use kirillbdev\WCUkrShipping\DB\Repositories\LegacyTtnRepository;
use kirillbdev\WCUkrShipping\DB\Repositories\ShippingLabelsRepository;
use kirillbdev\WCUkrShipping\Enums\CarrierSlug;
use kirillbdev\WCUkrShipping\Foundation\NovaGlobalAddress;
use kirillbdev\WCUkrShipping\Foundation\State;
use kirillbdev\WCUkrShipping\Helpers\SmartyParcelHelper;
use kirillbdev\WCUkrShipping\Helpers\WCUSHelper;
use kirillbdev\WCUkrShipping\Http\Controllers\AddressBookController;
use kirillbdev\WCUkrShipping\Http\Controllers\AutomationController;
use kirillbdev\WCUkrShipping\Http\Controllers\OptionsController;
use kirillbdev\WCUkrShipping\Http\Controllers\SmartyParcelController;
use kirillbdev\WCUkrShipping\Http\Controllers\ToolsController;
use kirillbdev\WCUkrShipping\Model\Document\TTNStore;
use kirillbdev\WCUkrShipping\Services\SmartyParcelService;
use kirillbdev\WCUkrShipping\States\OptionsPageState;
use kirillbdev\WCUkrShipping\States\OrdersState;
use kirillbdev\WCUkrShipping\States\WarehouseLoaderState;
use kirillbdev\WCUSCore\Contracts\ModuleInterface;
use kirillbdev\WCUSCore\Foundation\View;
use kirillbdev\WCUSCore\Http\Routing\Route;

if ( ! defined('ABSPATH')) {
    exit;
}

class OptionsPage implements ModuleInterface
{
    private SmartyParcelService $smartyParcelService;
    private ShippingLabelsRepository $shippingLabelsRepository;
    private AutomationRulesRepository $automationRulesRepository;
    private LegacyTtnRepository $legacyTtnRepository;
    private AutomationListTable $table;

    public function __construct(
        SmartyParcelService $smartyParcelService,
        ShippingLabelsRepository $shippingLabelsRepository,
        AutomationRulesRepository $automationRulesRepository,
        LegacyTtnRepository $legacyTtnRepository
    ) {
        $this->smartyParcelService = $smartyParcelService;
        $this->shippingLabelsRepository = $shippingLabelsRepository;
        $this->automationRulesRepository = $automationRulesRepository;
        $this->legacyTtnRepository = $legacyTtnRepository;
    }

    public function init()
    {
        add_action('admin_menu', [$this, 'registerOptionsPage'], 99);
        add_filter('wcus_load_admin_i18n', [$this, 'registerTranslates']);
    }

    public function routes()
    {
        return [
            new Route('wcus_save_options', OptionsController::class, 'save'),
            new Route('wcus_load_areas', AddressBookController::class, 'loadAreas'),
            new Route('wcus_load_cities', AddressBookController::class, 'loadCities'),
            new Route('wcus_load_warehouses', AddressBookController::class, 'loadWarehouses'),

            new Route('wcus_smartyparcel_api', SmartyParcelController::class, 'sendApiRequest'),
            new Route('wcus_smartyparcel_disconnect', SmartyParcelController::class, 'disconnect'),
            new Route('wcus_smarty_parcel_create_label', SmartyParcelController::class, 'createShippingLabel'),
            new Route('wcus_smarty_parcel_create_label_batch', SmartyParcelController::class, 'createLabelBatch'),
            new Route('wcus_smarty_parcel_void_label', SmartyParcelController::class, 'voidLabel'),
            new Route('wcus_attach_label', SmartyParcelController::class, 'attachShippingLabel'),
            new Route('wcus_automation_save_rule', AutomationController::class, 'saveRule'),

            // Tools
            new Route('wcus_tools_sync_legacy_ttn', ToolsController::class, 'syncLegacyTtn'),
        ];
    }

    public function registerOptionsPage()
    {
        State::add('warehouse_loader', WarehouseLoaderState::class);
        State::add('options', OptionsPageState::class);
        State::add('orders', OrdersState::class);

        add_menu_page(
            __('Settings', 'wc-ukr-shipping-i18n'),
            'WC Ukr Shipping',
            'manage_options',
            'wc_ukr_shipping_options',
            [$this, 'html'],
            WC_UKR_SHIPPING_PLUGIN_URL . 'image/menu-icon.png',
            56.15
        );

        add_submenu_page(
            'wc_ukr_shipping_options',
            __('Smarty Parcel', 'wc-ukr-shipping-i18n'),
            __('Smarty Parcel', 'wc-ukr-shipping-i18n'),
            'manage_options',
            'wcus_smarty_parcel',
            [$this, 'smartyParcelHtml']
        );

        add_submenu_page(
            '',
            __('Create TTN', 'wc-ukr-shipping-i18n'),
            __('Create TTN', 'wc-ukr-shipping-i18n'),
            'manage_woocommerce',
            'wc_ukr_shipping_ttn',
            [$this, 'ttnHtml']
        );

        add_submenu_page(
            'wc_ukr_shipping_options',
            __('Orders', 'wc-ukr-shipping-i18n'),
            __('Orders', 'wc-ukr-shipping-i18n'),
            'manage_woocommerce',
            'wc_ukr_shipping_ttn_list',
            [$this, 'orderListHtml']
        );

        $automationPage = add_submenu_page(
            'wc_ukr_shipping_options',
            __('Automation', 'wc-ukr-shipping-i18n'),
            __('Automation', 'wc-ukr-shipping-i18n'),
            'manage_woocommerce',
            'wcus_automation',
            [$this, 'automationHtml']
        );
        add_action("load-$automationPage", function () {
            $this->table = new AutomationListTable($this->automationRulesRepository);
        });

        add_submenu_page(
            '',
            __('Create', 'wc-ukr-shipping-i18n'),
            __('Create', 'wc-ukr-shipping-i18n'),
            'manage_woocommerce',
            'wcus_automation_rule_create',
            [$this, 'automationRuleFormHtml']
        );

        add_submenu_page(
            '',
            __('Edit', 'wc-ukr-shipping-i18n'),
            __('Edit', 'wc-ukr-shipping-i18n'),
            'manage_woocommerce',
            'wcus_automation_rule_edit',
            [$this, 'automationRuleFormHtml']
        );

        add_submenu_page(
            'wc_ukr_shipping_options',
            __('Tools', 'wc-ukr-shipping-i18n'),
            __('Tools', 'wc-ukr-shipping-i18n'),
            'manage_options',
            'wc_ukr_shipping_tools',
            [$this, 'toolsHtml']
        );
    }

    public function registerTranslates($i18n): array
    {
        return array_merge($i18n, [
            'warehouse_loader' => [
                'title' => __('Warehouses data of Nova Poshta', 'wc-ukr-shipping-i18n'),
                'last_update' => __('Last update date:', 'wc-ukr-shipping-i18n'),
                'status' => __('Status:', 'wc-ukr-shipping-i18n'),
                'status_not_completed' => __('Not completed', 'wc-ukr-shipping-i18n'),
                'status_completed' => __('Completed', 'wc-ukr-shipping-i18n'),
                'status_unknown' => __('Unknown', 'wc-ukr-shipping-i18n'),
                'update' => __('Update warehouses', 'wc-ukr-shipping-i18n'),
                'continue' => __('Continue update', 'wc-ukr-shipping-i18n'),
                'load_areas' => __('Load areas...', 'wc-ukr-shipping-i18n'),
                'load_cities' => __('Load cities...', 'wc-ukr-shipping-i18n'),
                'load_warehouses' => __('Load warehouses...', 'wc-ukr-shipping-i18n'),
                'success_updated' => __('Warehouses db updated successfully', 'wc-ukr-shipping-i18n'),
            ],
            'smarty_parcel' => [],
            'text_confirm_re_run_migrations' => __('Are you sure to restart migrations? This action cannot be canceled.', 'wc-ukr-shipping-i18n'),
        ]);
    }

    public function html()
    {
        $data = [];
        $gateways = wcus_is_woocommerce_active() ? wc()->payment_gateways()->payment_gateways() : [];
        $paymentMethods = [];
        foreach ($gateways as $id => $gateway) {
            $paymentMethods[$id] = $gateway->get_title();
        }
        $data['payment_methods'] = $paymentMethods;
        $data['cod_payment_id'] = wc_ukr_shipping_get_option('wcus_cod_payment_id');
        $data['payment_control_default'] = (int)wc_ukr_shipping_get_option('wcus_ttn_pay_control_default');
        $data['carrierAccounts'] = $this->smartyParcelService->getCarrierAccounts();

        $section = $_GET['section'] ?? null;
        switch ($section) {
            case 'nova_poshta':
                $view = 'settings_nova_poshta';
                break;
            case 'ukrposhta':
                $view = 'settings_ukrposhta';
                break;
            case 'rozetka':
                $view = 'settings_rozetka';
                break;
            default:
                $view = 'settings_general';
        }

        echo View::render($view, $data);
    }

    public function smartyParcelHtml()
    {
        echo View::render('smarty_parcel');
    }

    public function ttnHtml(): void
    {
        if (!SmartyParcelHelper::isConnected()) {
            echo View::render('ttn/ttn_forbidden');
            return;
        }

        $label = $this->shippingLabelsRepository->findByOrderId((int)$_GET['order_id']);
        if ($label !== null) {
            return;
        }

        $order = wc_get_order((int)$_GET['order_id']);
        if ( ! $order) {
            throw new \InvalidArgumentException('Order #' . (int)$_GET['order_id'] . ' not found.');
        }
        $shippingMethod = WCUSHelper::getOrderShippingMethod($order);

        // Hardcoded yet: detect and process elements-sdk flow
        $v2Methods = [
            WCUS_SHIPPING_METHOD_NOVA_GLOBAL_ADDRESS,
            WCUS_SHIPPING_METHOD_ROZETKA,
            WCUS_SHIPPING_METHOD_MEEST,
            WCUS_SHIPPING_METHOD_MEEST_ADDRESS,
            WCUS_SHIPPING_METHOD_NOVA_POST,
        ];
        if ($shippingMethod !== null && in_array($shippingMethod->get_method_id(), $v2Methods, true)) {
            $this->processPurchaseLabelV2($order, $shippingMethod);
            return;
        }
        // Check UkrPoshta Intl flow
        if (
            ($shippingMethod !== null && $shippingMethod->get_method_id() === WCUS_SHIPPING_METHOD_UKRPOSHTA_ADDRESS)
            || ($_GET['carrier'] ?? null) === 'ukrposhta'
        ) {
            if ($order->get_billing_country() !== 'UA' || $order->get_shipping_country() !== 'UA') {
                $this->processPurchaseLabelV2($order, $shippingMethod, $_GET['carrier'] ?? null);
                return;
            }
        }

        wp_enqueue_script(
            'smarty_parcel_elements_js',
            WC_UKR_SHIPPING_PLUGIN_URL . 'assets/js/smartyparcel/elements.min.js',
            [ 'jquery' ],
            filemtime(WC_UKR_SHIPPING_PLUGIN_DIR . 'assets/js/smartyparcel/elements.min.js'),
            true
        );

        wp_enqueue_script(
            'wcus_ttn_form_js',
            WC_UKR_SHIPPING_PLUGIN_URL . 'assets/js/ttn-form.min.js',
            [ 'jquery' ],
            filemtime(WC_UKR_SHIPPING_PLUGIN_DIR . 'assets/js/ttn-form.min.js'),
            true
        );

        if (isset($_GET['carrier'])) {
            $carrier = $_GET['carrier'];
        } else {
            $carrier = SmartyParcelHelper::getOrderCarrierSlug($order);
        }

        $store = null;
        switch ($carrier) {
            case 'nova_poshta':
                $store = new TTNStore((int)$_GET['order_id']);
                break;
            case 'ukrposhta':
                $store = new SingleLabelDataCollector($order);
                break;
        }

        if ($store === null) {
            echo View::render('ttn/ttn_custom', [
                'shippingMethod' => $shippingMethod !== null ? $shippingMethod->get_name() : null,
                'novaPoshtaFormUrl' => admin_url(
                    'admin.php?page=wc_ukr_shipping_ttn&order_id=' . $order->get_id() . '&carrier=nova_poshta'
                ),
                'ukrposhtaFormUrl' => admin_url(
                    'admin.php?page=wc_ukr_shipping_ttn&order_id=' . $order->get_id() . '&carrier=ukrposhta'
                ),
                'rozetkaFormUrl' => admin_url(
                    'admin.php?page=wc_ukr_shipping_ttn&order_id=' . $order->get_id() . '&carrier=rozetka_delivery'
                ),
            ]);
        } else {
            wp_localize_script('wcus_ttn_form_js', 'wcus_ttn_form_state', $store->collect());
            echo View::render('ttn/ttn');
        }
    }

    public function processPurchaseLabelV2(\WC_Order $order, \WC_Order_Item_Shipping $orderShipping, ?string $carrierSlug = null): void
    {
        wp_enqueue_script(
        'smartyparcel_labels_js',
            WC_UKR_SHIPPING_PLUGIN_URL . 'assets/js/labels.min.js',
            ['smartyparcel_elements_sdk_js'],
            filemtime(WC_UKR_SHIPPING_PLUGIN_DIR . 'assets/js/labels.min.js'),
            true
        );

        if ($carrierSlug !== null) {
            $carrier = $carrierSlug;
        } else {
            $carrier = SmartyParcelHelper::getCarrierFromShippingMethod($orderShipping->get_method_id());
        }

        if ($carrier === null) {
            esc_html_e('Unable to detect carrier for order', 'wc-ukr-shipping-i18n');
            return;
        }

        switch ($carrier) {
            case CarrierSlug::ROZETKA_DELIVERY:
                $collector = new RozetkaOrderCollector($order);
                break;
            case CarrierSlug::MEEST:
                $collector = new MeestOrderCollector($order);
                break;
            default:
                $collector = new BaseOrderCollector($order, $carrier);
        }

        wp_localize_script('smartyparcel_labels_js', 'wcus_sp_label_data', $collector->collect());

        echo View::render('ttn/ttn_v2');
    }

    public function orderListHtml(): void
    {
        echo View::render('orders');
    }

    public function automationHtml()
    {
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
            if (wp_verify_nonce($_GET['_wpnonce'] ?? '', 'wcus_automation_delete')) {
                $this->automationRulesRepository->delete((int)$_GET['id']);
            }
        }

        $this->table->prepare_items();
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php echo esc_html(get_admin_page_title()); ?></h1>
            <a href="<?php echo esc_attr(admin_url('admin.php?page=wcus_automation_rule_create')); ?>"
               class="page-title-action"><?php esc_html_e('Add rule', 'wc-ukr-shipping-i18n'); ?></a>
            <hr class="wp-header-end">
            <form action="" method="POST">
                <?php $this->table->display(); ?>
            </form>
        </div>
        <?php
    }

    public function automationRuleFormHtml(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $model = null;
        if ($id > 0) {
            $model = $this->automationRulesRepository->findById($id);
            if ($model === null) {
                echo sprintf(
                    '<div class="notice notice-error">%s</div>',
                    esc_html(__('Rule not found', 'wc-ukr-shipping-i18n'))
                );
                return;
            }
        }

        echo View::render('automation', [
            'model' => $model,
            'successMsg' => isset($_GET['success']) && $_GET['success'] === '1'
                ? __('Rule saved successfully', 'wc-ukr-shipping-i18n')
                : null,
        ]);
    }

    public function toolsHtml(): void
    {
        echo View::render('tools', [
            'legacyTtnCount' => $this->legacyTtnRepository->getCountTtn(),
        ]);
    }
}
