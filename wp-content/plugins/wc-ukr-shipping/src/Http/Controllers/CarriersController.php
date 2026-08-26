<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Http\Controllers;

use kirillbdev\WCUkrShipping\Dto\Carrier\CarrierDefinition;
use kirillbdev\WCUkrShipping\Services\CarrierService;
use kirillbdev\WCUSCore\Http\Contracts\ResponseInterface;
use kirillbdev\WCUSCore\Http\Controller;
use kirillbdev\WCUSCore\Http\Request;

if ( ! defined('ABSPATH')) {
    exit;
}

class CarriersController extends Controller
{
    private CarrierService $carrierService;

    public function __construct(CarrierService $carrierService)
    {
        $this->carrierService = $carrierService;
    }

    public function toggle(Request $request): ResponseInterface
    {
        $carrier = $this->resolveCarrier($request);
        if ($carrier === null) {
            return $this->carrierNotFound();
        }

        $enabled = (int)$request->get('enabled') === 1;
        $this->carrierService->setActive($carrier, $enabled);

        return $this->jsonResponse([
            'success' => true,
            'data' => [
                'enabled' => $enabled,
                'message' => $enabled
                    ? __('Carrier enabled', 'wc-ukr-shipping')
                    : __('Carrier disabled', 'wc-ukr-shipping'),
            ],
        ]);
    }

    public function getOptions(Request $request): ResponseInterface
    {
        $carrier = $this->resolveCarrier($request);
        if ($carrier === null) {
            return $this->carrierNotFound();
        }

        return $this->jsonResponse([
            'success' => true,
            'data' => [
                'carrier' => [
                    'slug' => $carrier->slug,
                    'name' => $carrier->name,
                    'icon' => $carrier->getIconUrl(),
                ],
                'groups' => $this->carrierService->getOptions($carrier),
            ],
        ]);
    }

    public function saveOptions(Request $request): ResponseInterface
    {
        $carrier = $this->resolveCarrier($request);
        if ($carrier === null) {
            return $this->carrierNotFound();
        }

        $values = $request->get('options', []);
        $this->carrierService->saveOptions($carrier, is_array($values) ? $values : []);

        return $this->jsonResponse([
            'success' => true,
            'data' => [
                'message' => __('Settings saved successfully!', 'wc-ukr-shipping'),
            ],
        ]);
    }

    private function resolveCarrier(Request $request): ?CarrierDefinition
    {
        return $this->carrierService->find(
            sanitize_text_field((string)$request->get('carrier', ''))
        );
    }

    private function carrierNotFound(): ResponseInterface
    {
        return $this->jsonResponse([
            'success' => false,
            'error' => [
                'message' => __('Invalid carrier', 'wc-ukr-shipping'),
            ],
        ]);
    }
}
