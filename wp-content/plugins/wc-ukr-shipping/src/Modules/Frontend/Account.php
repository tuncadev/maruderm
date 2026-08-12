<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Modules\Frontend;

use kirillbdev\WCUkrShipping\DB\Repositories\ShippingLabelsRepository;
use kirillbdev\WCUSCore\Contracts\ModuleInterface;

class Account implements ModuleInterface
{
    private ShippingLabelsRepository $labelsRepository;

    public function __construct(ShippingLabelsRepository $labelsRepository)
    {
        $this->labelsRepository = $labelsRepository;
    }

    public function init(): void
    {
        add_filter('woocommerce_account_orders_columns', [$this, 'extendAccountOrderColumns']);
        add_action( 'woocommerce_my_account_my_orders_column_wcus_shipment', [$this, 'renderTrackingNumber']);
    }

    public function extendAccountOrderColumns(array $columns): array
    {
        $newColumns = [];
        foreach ($columns as $key => $column) {
            $newColumns[$key] = $column;
            if ($key === 'order-total') {
                $newColumns['wcus_shipment'] = __('Shipping label', 'wc-ukr-shipping-i18n');
            }
        }

        return $newColumns;
    }

    /**
     * @param \WC_Order $order
     * @return void
     */
    public function renderTrackingNumber(\WC_Order $order): void
    {
        $label = $this->labelsRepository->findByOrderId($order->get_id());
        if ($label !== null) {
            echo esc_html(apply_filters(
                'wcus_my_account_tracking_number_html', $label['tracking_number'], $label, $order
            ));
        }
    }
}
