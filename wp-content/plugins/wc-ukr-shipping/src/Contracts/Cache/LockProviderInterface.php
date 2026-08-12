<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Contracts\Cache;

interface LockProviderInterface
{
    public function lock(string $key, int $seconds): bool;

    public function releaseLock(string $key): bool;
}
