<?php

namespace kirillbdev\WCUkrShipping\Foundation;

use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use kirillbdev\WCUkrShipping\Address\Provider\AddressProviderInterface;
use kirillbdev\WCUkrShipping\Api\SmartyParcelAddressBook;
use kirillbdev\WCUkrShipping\Component\Cache\TransientLockProvider;
use kirillbdev\WCUkrShipping\Contracts\Cache\LockProviderInterface;
use kirillbdev\WCUkrShipping\Contracts\Customer\CustomerStorageInterface;
use kirillbdev\WCUkrShipping\Contracts\NovaPoshtaAddressProviderInterface;
use kirillbdev\WCUkrShipping\DB\Repositories\AreaRepositoryInterface;
use kirillbdev\WCUkrShipping\DB\Repositories\HardcodedAreaRepository;
use kirillbdev\WCUkrShipping\DB\Repositories\Orders\HposOrderRepository;
use kirillbdev\WCUkrShipping\DB\Repositories\Orders\OrderRepository;
use kirillbdev\WCUkrShipping\DB\Repositories\Orders\OrderRepositoryInterface;
use kirillbdev\WCUkrShipping\Includes\Customer\LoggedCustomerStorage;
use kirillbdev\WCUkrShipping\Includes\Customer\NullCustomerStorage;
use kirillbdev\WCUkrShipping\Includes\Customer\SessionCustomerStorage;
use kirillbdev\WCUkrShipping\Modules\Core\Activator;
use kirillbdev\WCUSCore\DB\Migrator;
use kirillbdev\WCUkrShipping\Address\Provider\MySqlAddressProvider;

if ( ! defined('ABSPATH')) {
    exit;
}

final class Dependencies
{
    public static function all()
    {
        return [
            // Contracts
            CustomerStorageInterface::class => function ($container) {
                $customer = wc()->customer;
                $session = wc()->session;

                if (!$customer && !$session) {
                    return $container->make(NullCustomerStorage::class);
                }

                return $container->make($customer ? LoggedCustomerStorage::class : SessionCustomerStorage::class);
            },
            NovaPoshtaAddressProviderInterface::class => function ($container) {
                return $container->make(SmartyParcelAddressBook::class);
            },
            AddressProviderInterface::class => function ($container) {
                return $container->make(MySqlAddressProvider::class);
            },
            AreaRepositoryInterface::class => function ($container) {
                return $container->make(HardcodedAreaRepository::class);
            },
            LockProviderInterface::class => function ($container) {
                return $container->make(TransientLockProvider::class);
            },
            OrderRepositoryInterface::class => function ($container) {
                $controller = wcus_wc_container_safe_get(CustomOrdersTableController::class);
                return $controller !== null && $controller->custom_orders_table_usage_is_enabled()
                    ? $container->make(HposOrderRepository::class)
                    : $container->make(OrderRepository::class);
            },
            // Modules
            Activator::class => function ($container) {
                return new Activator($container->make(Migrator::class));
            },
        ];
    }
}
