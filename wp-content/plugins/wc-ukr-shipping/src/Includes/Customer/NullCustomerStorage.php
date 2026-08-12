<?php

namespace kirillbdev\WCUkrShipping\Includes\Customer;

use kirillbdev\WCUkrShipping\Contracts\Customer\CustomerStorageInterface;

if ( ! defined('ABSPATH')) {
    exit;
}

class NullCustomerStorage implements CustomerStorageInterface
{
    /**
     * @param string $key
     * @return mixed|null
     */
    public function get(string $key)
    {
        return null;
    }

    /**
     * @param string $key
     * @param mixed $value
     */
    public function add(string $key, $value)
    {
        // Do nothing
    }

    /**
     * @param string $key
     */
    public function remove(string $key): void
    {
        // Do nothing
    }
}
