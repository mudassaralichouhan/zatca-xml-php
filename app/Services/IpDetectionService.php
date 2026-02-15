<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\{Request};

class IpDetectionService
{
    private static function isLocalhost(): bool
    {
        $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '';

        return (
            $remoteAddr === '127.0.0.1' ||
            $remoteAddr === '::1'
        );
    }

    public static function getClientIp(): string
    {
        if (self::isLocalhost()) {
            return '127.0.0.1';
        }

        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        if (!filter_var($ipAddress, FILTER_VALIDATE_IP)) {
            $ipAddress = 'unknown';
        }

        return $ipAddress;
    }

    public static function getClientHost(Request $r): string
    {
        if (self::isLocalhost()) {
            return 'localhost';
        }

        $ip = self::getClientIp();
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return 'unknown';
        }

        $host = $_SERVER['REMOTE_HOST'] ?? $r->server->get('REMOTE_HOST');

        if (empty($host)) {
            $host = gethostbyaddr($ip);

            if ($host !== $ip) {
                $resolvedIps = gethostbynamel($host) ?: [];
                if (!in_array($ip, $resolvedIps, true)) {
                    $host = null; // mismatch → possible spoof
                }
            } else {
                $host = null;
            }
        }

        return $host ?: $ip;
    }

    public static function getClientInfo(Request $r): array
    {
        $ipAddress = self::getClientIp();
        $host = self::getClientHost($r);
        $userAgent = $r->headers->get('User-Agent') ?? ($_SERVER['HTTP_USER_AGENT'] ?? null);

        return [
            'ip' => $ipAddress,
            'host' => $host,
            'user_agent' => $userAgent
        ];
    }

    public function reverse_lookup(string $ip): array
    {
        $ip = trim($ip);
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return [];
        }

        $hosts = [];
        // try simple gethostbyaddr()
        $g = gethostbyaddr($ip);
        if ($g && $g !== $ip) {
            $hosts[] = $g;
        }

        // fallback: PTR DNS query
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $octets = explode('.', $ip);
            $arpa = implode('.', array_reverse($octets)) . '.in-addr.arpa';
            $records = @dns_get_record($arpa, DNS_PTR) ?: [];
            foreach ($records as $r) {
                if (!empty($r['target'])) {
                    $hosts[] = rtrim($r['target'], '.');
                }
            }
        } else { // IPv6
            $bin = @inet_pton($ip);
            if ($bin !== false) {
                $hex = bin2hex($bin);
                $nibbles = array_reverse(str_split($hex));
                $arpa = implode('.', $nibbles) . '.ip6.arpa';
                $records = @dns_get_record($arpa, DNS_PTR) ?: [];
                foreach ($records as $r) {
                    if (!empty($r['target'])) {
                        $hosts[] = rtrim($r['target'], '.');
                    }
                }
            }
        }

        // unique and return
        $hosts = array_values(array_unique($hosts));
        return $hosts;
    }
}
