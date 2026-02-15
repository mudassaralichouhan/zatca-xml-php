<?php

namespace App\Services;

use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use App\Config\Config;
use App\Services\RedisService;

class CacheFactory
{
    public static function create()
    {
        if (Config::STORAGE_DRIVER('file') === 'redis') {
            return new RedisAdapter(
                RedisService::getClient()
            );
        }
        
        return new FilesystemAdapter(
            '',
            0,
            Config::STORAGE_FILE_PATH()
        );
    }
}
