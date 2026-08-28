<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Component\Carriers;

use kirillbdev\WCUkrShipping\Dto\Carrier\CarrierDefinition;
use kirillbdev\WCUkrShipping\Dto\Carrier\CarrierOptionField;
use kirillbdev\WCUkrShipping\Dto\Carrier\CarrierOptionGroup;
use kirillbdev\WCUkrShipping\Enums\CarrierFeature;
use kirillbdev\WCUkrShipping\Enums\CarrierSlug;

if ( ! defined('ABSPATH')) {
    exit;
}

/**
 * Declaration of the carriers available for the store: name, icon and own options.
 */
final class CarrierCatalog
{
    /**
     * @var CarrierDefinition[]|null
     */
    private ?array $definitions = null;

    /**
     * @return CarrierDefinition[] Definitions indexed by carrier slug.
     */
    public function all(): array
    {
        if ($this->definitions === null) {
            $this->definitions = $this->declare();
        }

        return $this->definitions;
    }

    public function find(string $slug): ?CarrierDefinition
    {
        return $this->all()[$slug] ?? null;
    }

    /**
     * @return CarrierDefinition[]
     */
    private function declare(): array
    {
        $definitions = [
            new CarrierDefinition(
                CarrierSlug::NOVA_POSHTA,
                'Nova Poshta (Ukraine)',
                'nova-poshta-icon.png',
                [
                    CarrierFeature::PICKUP_POINTS,
                    CarrierFeature::ADDRESS_DELIVERY,
                    CarrierFeature::RATE_CALCULATION,
                    CarrierFeature::SHIPPING_LABELS,
                    CarrierFeature::TRACKING,
                ],
                [
                    new CarrierOptionGroup(
                        __('Pickup points', 'wc-ukr-shipping'),
                        [],
                        CarrierOptionGroup::WIDGET_NP_WAREHOUSE_LOADER
                    ),
                    new CarrierOptionGroup(__('Checkout', 'wc-ukr-shipping'), [
                        CarrierOptionField::select(
                            'wc_ukr_shipping_np_lang',
                            __('Display language of cities and departments', 'wc-ukr-shipping'),
                            [
                                'uk' => __('Ukrainian', 'wc-ukr-shipping'),
                                'ru' => __('Russian', 'wc-ukr-shipping'),
                            ],
                            'uk'
                        ),
                        CarrierOptionField::switcher(
                            'wcus_np_use_online_directory',
                            __('Use Nova Poshta online directory for search settlements', 'wc-ukr-shipping'),
                            __('If enabled, the plugin will use online directory api for both warehouse and address delivery', 'wc-ukr-shipping')
                        ),
                    ]),
                    new CarrierOptionGroup(__('Shipping label', 'wc-ukr-shipping'), [
                        CarrierOptionField::switcher(
                            'wcus_ttn_global_params_default',
                            __('Global params as default', 'wc-ukr-shipping')
                        ),
                    ]),
                ]
            ),
            new CarrierDefinition(
                CarrierSlug::UKRPOSHTA,
                'Ukrposhta',
                'ukrposhta-icon.png',
                [
                    CarrierFeature::PICKUP_POINTS,
                    CarrierFeature::ADDRESS_DELIVERY,
                    CarrierFeature::RATE_CALCULATION,
                    CarrierFeature::SHIPPING_LABELS,
                    CarrierFeature::TRACKING,
                ],
                [
                    new CarrierOptionGroup(__('Pickup points', 'wc-ukr-shipping'), [
                        CarrierOptionField::text(
                            'wcus_ukrposhta_bearer_ecom',
                            __('Bearer eCom token of Ukrposhta', 'wc-ukr-shipping'),
                            __('Bearer eCom token is required to search for warehouses across Ukraine.', 'wc-ukr-shipping')
                        ),
                    ]),
                ]
            ),
            new CarrierDefinition(
                CarrierSlug::ROZETKA_DELIVERY,
                'Rozetka Delivery (Ukraine)',
                'rozetka-icon.png',
                [
                    CarrierFeature::PICKUP_POINTS,
                    CarrierFeature::SHIPPING_LABELS,
                    CarrierFeature::TRACKING,
                ]
            ),
            new CarrierDefinition(
                CarrierSlug::NOVA_POST,
                'Nova Post',
                // todo: replace with an own icon when it is ready
                'nova-poshta-icon.png',
                [
                    CarrierFeature::PICKUP_POINTS,
                    CarrierFeature::RATE_CALCULATION,
                    CarrierFeature::SHIPPING_LABELS,
                    CarrierFeature::TRACKING,
                ],
                [
                    new CarrierOptionGroup(__('Pickup points', 'wc-ukr-shipping'), [
                        CarrierOptionField::text(
                            'wcus_nova_post_api_key',
                            __('API Key of Nova Post', 'wc-ukr-shipping'),
                            __('API key is required to search for warehouses and PUDO across Europe', 'wc-ukr-shipping')
                        ),
                    ]),
                ]
            ),
            new CarrierDefinition(
                CarrierSlug::NOVA_GLOBAL,
                'Nova Global',
                // todo: replace with an own icon when it is ready
                'nova-poshta-icon.png',
                [
                    CarrierFeature::ADDRESS_DELIVERY,
                    CarrierFeature::RATE_CALCULATION,
                    CarrierFeature::SHIPPING_LABELS,
                    CarrierFeature::TRACKING,
                ]
            ),
            new CarrierDefinition(
                CarrierSlug::MEEST,
                'Meest',
                'meest-icon.png',
                [
                    CarrierFeature::PICKUP_POINTS,
                    CarrierFeature::ADDRESS_DELIVERY,
                    CarrierFeature::RATE_CALCULATION,
                    CarrierFeature::SHIPPING_LABELS,
                    CarrierFeature::TRACKING,
                ],
                [
                    new CarrierOptionGroup(__('Pickup points', 'wc-ukr-shipping'), [
                        CarrierOptionField::text(
                            'wcus_meest_api_token',
                            __('API Token of Meest Post', 'wc-ukr-shipping'),
                            __('API token is required to search for warehouses and PUDO of Meest Post', 'wc-ukr-shipping')
                        ),
                    ]),
                ]
            ),
            new CarrierDefinition(
                CarrierSlug::POST_NORD,
                'PostNord',
                'postnord-icon.png',
                [
                    CarrierFeature::PICKUP_POINTS,
                    CarrierFeature::ADDRESS_DELIVERY,
                    CarrierFeature::TRACKING,
                ],
                requireStoreConnection: true
            ),
        ];

        $indexed = [];

        foreach ($definitions as $definition) {
            $indexed[$definition->slug] = $definition;
        }

        return $indexed;
    }
}
