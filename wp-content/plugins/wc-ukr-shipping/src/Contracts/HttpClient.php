<?php

namespace kirillbdev\WCUkrShipping\Contracts;

use kirillbdev\WCUkrShipping\Exceptions\ApiServiceException;

if ( ! defined('ABSPATH')) {
    exit;
}

interface HttpClient
{
    /**
     * @param string $url
     * @param array $headers
     *
     * @return mixed
     */
    public function get(string $url, array $headers = []);

    /**
     * @param string $url
     * @param mixed $body
     * @param array $headers
     *
     * @return mixed
     *
     * @throws ApiServiceException
     */
    public function post($url, $body = null, $headers = []);
}