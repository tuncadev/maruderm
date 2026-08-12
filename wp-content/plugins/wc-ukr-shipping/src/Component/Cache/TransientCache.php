<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Component\Cache;

use kirillbdev\WCUkrShipping\Contracts\Cache\CacheInterface;

class TransientCache implements CacheInterface
{
    public function get(string $key, $default = null)
    {
        $value = get_transient($this->getKey($key));

        return $value ?: $default;
    }

    public function set(string $key, $value, ?int $ttl = null): bool
    {
        return set_transient($this->getKey($key), $value, $ttl ===  null ? 0 : $ttl);
    }

    public function delete(string $key): bool
    {
        return delete_transient($this->getKey($key));
    }

    public function clear(): bool
    {
        // Not implemented now
        return true;
    }

    public function has(string $key): bool
    {
        // Not implemented now
        return false;
    }

    private function getKey(string $key): string
    {
        return "wcus_cache_$key";
    }
}
