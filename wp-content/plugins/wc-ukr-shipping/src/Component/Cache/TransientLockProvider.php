<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Component\Cache;

use kirillbdev\WCUkrShipping\Contracts\Cache\LockProviderInterface;

class TransientLockProvider implements LockProviderInterface
{
    public function lock(string $key, int $seconds): bool
    {
        $lock = get_transient($this->getKey($key));
        if ($lock !== false) {
            return false;
        }

        set_transient($this->getKey($key), 1, $seconds);

        return true;
    }

    public function releaseLock(string $key): bool
    {
        delete_transient($this->getKey($key));

        return true;
    }

    private function getKey(string $key): string
    {
        return 'wcus_lock:' . md5($key);
    }
}
