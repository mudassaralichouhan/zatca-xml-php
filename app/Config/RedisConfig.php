<?php

namespace App\Config;

class RedisConfig
{
    public static function parameters(): array
    {
        return [
            'scheme' => 'tcp',
            'host' => Config::REDIS_HOST(),
            'port' => Config::REDIS_PORT(),
            'password' => Config::REDIS_PASSWORD(),
            'persistent' => true,
            'timeout' => Config::REDIS_TIMEOUT(),
            'read_write_timeout' => Config::REDIS_READ_WRITE_TIMEOUT(),
        ];
    }

    public static function options(): array
    {
        return [
            'parameters' => [
                'persistent' => true,
            ],
            'retry_interval' => Config::REDIS_RETRY_INTERVAL(),
        ];
    }
}
