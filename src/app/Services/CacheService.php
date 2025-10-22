<?php

namespace App\Services;

use App\Contracts\CacheServiceInterface;
use Illuminate\Support\Facades\Cache;

class CacheService implements CacheServiceInterface
{
    /**
     * Retrieve an item from the cache or execute the callback.
     */
    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        return Cache::remember($key, $ttl, $callback);
    }

    /**
     * Remove an item from the cache.
     */
    public function forget(string $key): bool
    {
        return Cache::forget($key);
    }

    /**
     * Remove multiple items from the cache.
     */
    public function forgetMany(array $keys): void
    {
        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * Clear all cache.
     */
    public function flush(): bool
    {
        return Cache::flush();
    }
}
