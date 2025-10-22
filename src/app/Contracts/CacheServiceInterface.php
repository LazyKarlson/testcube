<?php

namespace App\Contracts;

interface CacheServiceInterface
{
    /**
     * Retrieve an item from the cache or execute the callback.
     */
    public function remember(string $key, int $ttl, callable $callback): mixed;

    /**
     * Remove an item from the cache.
     */
    public function forget(string $key): bool;

    /**
     * Remove multiple items from the cache.
     */
    public function forgetMany(array $keys): void;

    /**
     * Clear all cache.
     */
    public function flush(): bool;
}
