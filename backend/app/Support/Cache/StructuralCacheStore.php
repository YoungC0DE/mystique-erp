<?php

namespace App\Support\Cache;

use Illuminate\Support\Facades\Cache;

abstract class StructuralCacheStore
{
    /**
     * @template T
     *
     * @param  callable(): T  $resolver
     * @return T
     */
    protected function remember(string $key, callable $resolver): mixed
    {
        return Cache::rememberForever($key, $resolver);
    }

    protected function put(string $key, mixed $value): void
    {
        Cache::forever($key, $value);
    }

    protected function forget(string ...$keys): void
    {
        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }
}
