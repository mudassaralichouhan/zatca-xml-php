<?php

namespace App\Logging;

use App\Config\Paths;
use Monolog\Logger as MonologLogger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;
use App\Services\ZatcaModeHeaderService;

class Logger
{
    private static ?MonologLogger $logger = null;
    /** @var array<string, MonologLogger> */
    private static array $loggersByMode = [];

    private static function init(): void
    {
        if (self::$logger !== null) {
            return;
        }

        $date = date('Y-m-d');
        $logDir = realpath(Paths::LOG);
        $logPath = "{$logDir}/app-{$date}.log";

        $output = "[%datetime%] %level_name%: %message% %context%\n";
        $formatter = new LineFormatter($output, null, true, true);

        try {
            $logger = new MonologLogger('app');

            $stream = new StreamHandler($logPath, MonologLogger::DEBUG, true, 0664);
            $stream->setFormatter($formatter);

            $logger->pushHandler($stream);

            self::$logger = $logger;
        } catch (\Throwable $e) {
            error_log("Logger setup failed: " . $e->getMessage());
        }
    }

    private static function getLoggerForMode(?string $mode, array $context = []): ?MonologLogger
    {
        $resolvedMode = self::resolveMode($mode, $context);
        if ($resolvedMode === null) {
            throw new \InvalidArgumentException('Mode is required for logging.');
        }

        if (isset(self::$loggersByMode[$resolvedMode])) {
            return self::$loggersByMode[$resolvedMode];
        }

        $date = date('Y-m-d');
        $logDir = realpath(Paths::LOG);
        $safeMode = preg_replace('/[^a-z0-9\-]+/i', '-', $resolvedMode);
        $logPath = "{$logDir}/app-{$safeMode}-{$date}.log";

        $output = "[%datetime%] %level_name%: %message% %context%\n";
        $formatter = new LineFormatter($output, null, true, true);

        try {
            $logger = new MonologLogger('app-' . $safeMode);

            $stream = new StreamHandler($logPath, MonologLogger::DEBUG, true, 0664);
            $stream->setFormatter($formatter);

            $logger->pushHandler($stream);

            self::$loggersByMode[$resolvedMode] = $logger;
            return $logger;
        } catch (\Throwable $e) {
            error_log("Logger setup (mode {$resolvedMode}) failed: " . $e->getMessage());
            return null;
        }
    }

    private static function resolveMode(?string $mode, array $context): ?string
    {
        try {
            $available = ZatcaModeHeaderService::availableModes();

            // If explicit string mode provided, validate and normalize via mapMode()
            if (is_string($mode) && $mode !== '') {
                $enum = ZatcaModeHeaderService::mapMode($mode);
                foreach ($available as $name => $value) {
                    if ($value === $enum) {
                        return $name; // canonical mode name
                    }
                }
            }

            // If context provides string mode
            if (isset($context['zatca_mode']) && is_string($context['zatca_mode'])) {
                $enum = ZatcaModeHeaderService::mapMode($context['zatca_mode']);
                foreach ($available as $name => $value) {
                    if ($value === $enum) {
                        return $name;
                    }
                }
            }

            // If context provides enum directly
            if (isset($context['zatca_enum'])) {
                foreach ($available as $name => $value) {
                    if ($context['zatca_enum'] === $value) {
                        return $name;
                    }
                }
            }
        } catch (\Throwable $e) {
            // ignore mapping failure and fall through
        }

        return null;
    }

    public static function info(string $message, array $context = [], ?string $mode = null): void
    {
        if ($mode === null || $mode === '') {
            $allowed = implode(', ', array_keys(ZatcaModeHeaderService::availableModes()));
            throw new \InvalidArgumentException('Mode is required for logging. Allowed: ' . $allowed);
        }
        $logger = self::getLoggerForMode($mode, $context) ?? null;
        $logger?->info($message, $context);
    }

    public static function error(string $message, array $context = [], ?string $mode = null): void
    {
        if ($mode === null || $mode === '') {
            $allowed = implode(', ', array_keys(ZatcaModeHeaderService::availableModes()));
            throw new \InvalidArgumentException('Mode is required for logging. Allowed: ' . $allowed);
        }
        $logger = self::getLoggerForMode($mode, $context) ?? null;
        $logger?->error($message, $context);
    }

    public static function warning(string $message, array $context = [], ?string $mode = null): void
    {
        if ($mode === null || $mode === '') {
            $allowed = implode(', ', array_keys(ZatcaModeHeaderService::availableModes()));
            throw new \InvalidArgumentException('Mode is required for logging. Allowed: ' . $allowed);
        }
        $logger = self::getLoggerForMode($mode, $context) ?? null;
        $logger?->warning($message, $context);
    }

    public static function debug(string $message, array $context = [], ?string $mode = null): void
    {
        if ($mode === null || $mode === '') {
            $allowed = implode(', ', array_keys(ZatcaModeHeaderService::availableModes()));
            throw new \InvalidArgumentException('Mode is required for logging. Allowed: ' . $allowed);
        }
        $logger = self::getLoggerForMode($mode, $context) ?? null;
        $logger?->debug($message, $context);
    }

    /* System-level shortcuts (no ZATCA mode required) */
    public static function systemInfo(string $message, array $context = []): void
    {
        self::init();
        self::$logger?->info($message, $context);
    }

    public static function systemError(string $message, array $context = []): void
    {
        self::init();
        self::$logger?->error($message, $context);
    }

    public static function systemWarning(string $message, array $context = []): void
    {
        self::init();
        self::$logger?->warning($message, $context);
    }

    public static function systemDebug(string $message, array $context = []): void
    {
        self::init();
        self::$logger?->debug($message, $context);
    }
}
