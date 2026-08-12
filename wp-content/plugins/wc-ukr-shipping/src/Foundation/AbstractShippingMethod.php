<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Foundation;

use kirillbdev\WCUkrShipping\Factories\Rates\CheckoutRateShipmentFactory;
use kirillbdev\WCUkrShipping\Helpers\WCUSHelper;
use kirillbdev\WCUkrShipping\Services\CalculationService;

abstract class AbstractShippingMethod extends \WC_Shipping_Method
{
    protected string $carrierSlug = '';

    abstract protected function getCheckoutRateShipmentFactory(): CheckoutRateShipmentFactory;

    public function calculate_shipping($package = []): void
    {
        if ( ! $this->shouldCalculated()) {
            $this->add_rate([
                'label' => $this->title,
                'cost' => 0,
                'package' => $package,
            ]);
            return;
        }

        $service = new CalculationService();
        $factory = $this->getCheckoutRateShipmentFactory();
        $shippingCost = $service->calculateRates($factory->createRateShipment(), $this);

        $rate = [
            'label' => $this->get_option('enable_free_shipping') === 'yes' && $shippingCost !== null && $shippingCost <= 0
                ? $this->get_option('free_shipping_title')
                : $this->title,
            'cost' => $shippingCost,
            'package' => $package,
        ];
        $this->add_rate($rate);
    }

    /**
     * Is this method available?
     * @param array $package
     * @return bool
     */
    public function is_available($package)
    {
        return $this->is_enabled();
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    public function sanitizePrice($value)
    {
        if ($value !== '' && !is_numeric($value)) {
            throw new \InvalidArgumentException('Field value must be an integer or a float');
        }

        return $value;
    }

    protected function shouldCalculated(): bool
    {
        if ( ! isset($_GET['wc-ajax'])) {
            return false;
        }

        if (!WCUSHelper::hasChosenShippingMethodInstance($this)) {
            return false;
        }

        return ($_GET['wc-ajax'] === 'update_order_review' && ! empty($_POST['post_data']))
            || $_GET['wc-ajax'] === 'checkout';
    }
}
