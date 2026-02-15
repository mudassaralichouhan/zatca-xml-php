<?php

namespace App\Services;

use App\Services\RedisService;

class JWTService
{
    public static function blacklistJti(string $jti, int $exp): bool
    {
        $ttl = max($exp - time(), 0);
        if ($ttl <= 0) {
            return false;
        }
        $key = self::jwtBlacklistKey($jti);
        RedisService::set($key, '1', $ttl);
        return true;
    }

    public static function isJtiBlacklisted(string $jti): bool
    {
        return RedisService::exists(self::jwtBlacklistKey($jti));
    }

    private static function jwtBlacklistKey(string $jti): string
    {
        return 'jwt:blacklist:' . $jti;
    }
}
