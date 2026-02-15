<?php

namespace App\Config;

use Dotenv\Dotenv;

final class Config
{
    private static array $values = [];
    private static bool $initialized = false;

    public static function init(string $path): void
    {
        if (self::$initialized) {
            return;
        }

        // Load .env file
        if (file_exists("$path/.env")) {
            $dotenv = Dotenv::createImmutable($path);
            $dotenv->load();
        }

        // Load environment into internal array
        foreach ($_ENV as $key => $value) {
            self::$values[$key] = $value;
        }

        foreach (getenv() as $key => $value) {
            self::$values[$key] = $value;
        }

        self::$initialized = true;
    }

    public static function get(string $key, $default = null)
    {
        return self::$values[$key] ?? $default;
    }

    public static function __callStatic(string $name, array $arguments)
    {
        $key = strtoupper($name);

        return self::$values[$key] 
            ?? ($arguments[0] ?? throw new \RuntimeException("Missing config: {$key}. Please check your .env file."));
    }

    public static function validate(array $required = []): void
    {
        $missing = [];

        foreach ($required as $key) {
            if (!isset(self::$values[$key]) || self::$values[$key] === '') {
                $missing[] = $key;
            }
        }

        if (!empty($missing)) {
            throw new \RuntimeException(
                'Missing required configuration: ' . implode(', ', $missing)
            );
        }
    }

    public static function all(): array
    {
        return self::$values;
    }
}
