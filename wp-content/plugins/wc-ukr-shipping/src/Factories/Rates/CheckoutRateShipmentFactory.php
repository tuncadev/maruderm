<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Factories\Rates;

use kirillbdev\WCUkrShipping\Dto\Rates\RateShipmentDTO;
use kirillbdev\WCUkrShipping\Factories\ProductFactory;
use kirillbdev\WCUkrShipping\Model\OrderProduct;
use kirillbdev\WCUkrShipping\Services\Calculation\ProductDimensionService;

class CheckoutRateShipmentFactory
{
    protected string $carrierSlug;
    protected array $data;
    protected string $fieldGroup;
    protected bool $useDimensions;

    private ProductDimensionService $productDimensionService;

    public function __construct(string $carrierSlug, bool $useDimensions = true)
    {
        $this->carrierSlug = $carrierSlug;
        $this->useDimensions = $useDimensions;
        $this->productDimensionService = wcus_container()->make(ProductDimensionService::class);

        if ($_GET['wc-ajax'] === 'update_order_review') {
            parse_str(sanitize_text_field($_POST['post_data']), $data);
        } elseif ($_GET['wc-ajax'] === 'checkout') {
            $data = $_POST;
        } else {
            $data = [];
        }
        if (isset($data['ship_to_different_address']) && (int)$data['ship_to_different_address'] === 1) {
            $data['ship_to'] = [
                'country' => $_POST['s_country'] ?? '',
                'city' => $_POST['s_city'] ?? null,
                'state' => $_POST['s_state'] ?? null,
                'address_1' => $_POST['s_address'] ?? null,
                'address_2' => $_POST['s_address_2'] ?? null,
                'postal_code' => $_POST['s_postcode'] ?? null,
            ];
        } else {
            $data['ship_to'] = [
                'country' => $_POST['country'] ?? '',
                'city' => $_POST['city'] ?? null,
                'state' => $_POST['state'] ?? null,
                'address_1' => $_POST['address'] ?? null,
                'address_2' => $_POST['address_2'] ?? null,
                'postal_code' => $_POST['postcode'] ?? null,
            ];
        }

        $this->data = $data;
        $this->fieldGroup = isset($data['ship_to_different_address']) && (int)$data['ship_to_different_address'] === 1
            ? 'shipping'
            : 'billing';
    }

    public function createRateShipment(): RateShipmentDTO
    {
        $products = $this->getCartProducts();

        return new RateShipmentDTO(
            $this->carrierSlug,
            $this->get($this->fieldGroup . '_country', 'UA'),
            (float)wc()->cart->get_subtotal(),
            $this->productDimensionService->getTotalWeight($products),
            $this->get('payment_method', ''),
            $this->getDeliveryType(),
            $this->isFull(),
            $this->useDimensions ? $this->productDimensionService->getTotalDimensions($products) : null,
            $this->getShipToCarrierCityId(),
            $this->getShipToPUDOPointId(),
            $this->getServiceType(),
            $products,
            $this->getShipToCity(),
            $this->getShipToPostalCode()
        );
    }

    protected function getDeliveryType(): string
    {
        return 'w2w';
    }

    protected function isFull(): bool
    {
        return true;
    }

    protected function getShipToCarrierCityId(): ?string
    {
        return null;
    }

    protected function getShipToPUDOPointId(): ?string
    {
        return null;
    }

    protected function getServiceType():?string
    {
        return null;
    }

    protected function getShipToCity():?string
    {
        return null;
    }

    protected function getShipToPostalCode():?string
    {
        return null;
    }

    /**
     * @return OrderProduct[]
     */
    protected function getCartProducts(): array
    {
        $products = [];
        /** @var ProductFactory $factory */
        $factory = wcus_container()->make(ProductFactory::class);
        $items = wc()->cart->get_cart();

        foreach ($items as $item) {
            $product = $factory->makeCartItemProduct($item);

            if ($product) {
                $products[] = $product;
            }
        }

        return $products;
    }

    /**
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function get(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }
}
