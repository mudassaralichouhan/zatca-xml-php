<?php

namespace App\Services;

use Symfony\Contracts\Cache\ItemInterface;

class CacheService
{
    private static $cache = null;

    private static function init()
    {
        if (self::$cache === null) {
            self::$cache = CacheFactory::create();
        }
    }

    public static function get(string $key, ?callable $callback = null, int $ttl = 3600)
    {
        self::init();

        if ($callback === null) {
            $item = self::$cache->getItem($key);
            return $item->isHit() ? $item->get() : null;
        }

        return self::$cache->get($key, function (ItemInterface $item) use ($callback, $ttl) {
            $item->expiresAfter($ttl);
            return $callback();
        });
    }

    public static function set(string $key, mixed $value, int $ttl = 3600): bool
    {
        self::init();

        $item = self::$cache->getItem($key);
        $item->set($value);
        $item->expiresAfter($ttl);

        return self::$cache->save($item);
    }

    public static function exists(string $key): bool
    {
        self::init();
        return self::$cache->hasItem($key);
    }

    public static function delete(string $key): bool
    {
        self::init();
        return self::$cache->deleteItem($key);
    }

    public static function increment(string $key, int $amount = 1, int $ttl = 3600): int
    {
        self::init();

        $value = self::get($key) ?? 0;
        $value += $amount;

        self::set($key, $value, $ttl);

        return $value;
    }

    public static function clear(): bool
    {
        self::init();
        return self::$cache->clear();
    }
}
