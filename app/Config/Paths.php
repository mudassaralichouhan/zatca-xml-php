<?php

namespace App\Config;

final class Paths
{
    public static function getDatabasePath(string $mode): string
    {
        return match ($mode) {
            'developer-portal' => self::ROOT . 'storage/db/dev.sqlite',
            'core' => self::ROOT . 'storage/db/prod.sqlite',
            'simulation' => self::ROOT . 'storage/db/sim.sqlite',
            default => self::ROOT . 'storage/db/dev.sqlite',
        };
    }

    public const ROOT       = __DIR__ . '/../../';
    public const SCHEMAS    = self::ROOT . 'app/schemas/';
    public const STATIC_PATH    = self::ROOT . 'app/static/';
    public const STORAGE    = self::ROOT . 'storage/';
    public const LOG    = self::ROOT . 'storage/logs';
    public const DATABASE    = self::ROOT . 'storage/db/app.sqlite';
    public const CACHE            = self::ROOT . 'storage/cache/';
    public const COMPLIANCE_RESPONSE    = self::ROOT . 'compliance_response/';
    public const CRYPTOGRAPHY = self::ROOT . 'cryptography/';
}
