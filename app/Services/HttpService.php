<?php

namespace App\Services;

class HttpService
{
    public static function isLocalIp(string $ip): bool
    {
        if ($ip === '::1') {
            return true;
        }

        return filter_var($ip, FILTER_VALIDATE_IP) && (
            preg_match('/^127\./', $ip) ||                       // Loopback
                preg_match('/^10\./', $ip) ||                        // Private Class A
                preg_match('/^172\.(1[6-9]|2[0-9]|3[0-1])\./', $ip) || // Private Class B
                preg_match('/^192\.168\./', $ip)                     // Private Class C
        );
    }
}
