<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Contracts\Cache;

interface CacheInterface
{
    /**
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function get(string $key, $default = null);

    /**
     * @param string $key
     * @param mixed $value
     * @param int|null $ttl
     *
     * @return bool
     */
    public function set(string $key, $value, ?int $ttl = null): bool;

    public function delete(string $key): bool;

    public function clear(): bool;

    public function has(string $key): bool;
}
