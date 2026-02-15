<?php

namespace App\Middleware;

use App\Database\Database;
use App\Services\CryptoService;
use App\Services\ZatcaModeHeaderService;
use App\Services\IpDetectionService;
use App\Services\RedisService;
use Symfony\Component\HttpFoundation\{Request, Response};
use App\Services\ApiResponse;
use Illuminate\Database\Capsule\Manager as DB;

final class ApiKeyMiddleware implements Middleware
{
    public function __invoke(Request $r): ?Response
    {
        $apiKey = $r->headers->get('X-API-KEY');
        if (!$apiKey) {
            return ApiResponse::error(400, ['api_key' => 'Missing API key in header: X-API-KEY']);
        }

        $clientInfo = IpDetectionService::getClientInfo($r);
        $ip = $clientInfo['ip'];
        $host = $clientInfo['host'];

        $apiKeyHash = sha1($apiKey);
        $cacheKeyDecrypted = "api_key:payload:{$apiKeyHash}";
        $cachedDecrypted = RedisService::get($cacheKeyDecrypted);
        if ($cachedDecrypted) {
            $decrypted_data = json_decode($cachedDecrypted);
        } else {
            try {
                $decrypted = CryptoService::decrypt($apiKey);
                $decrypted_data = json_decode($decrypted);
            } catch (\Exception $e) {
                return ApiResponse::error(400, ['api_key' => 'Malformed API key payload']);
            }
            if (is_object($decrypted_data)) {
                RedisService::set($cacheKeyDecrypted, json_encode($decrypted_data), 300);
            }
        }

        if (!is_object($decrypted_data) || !isset($decrypted_data->id) || !isset($decrypted_data->zatca_mode)) {
            RedisService::delete($cacheKeyDecrypted);
            return ApiResponse::error(400, ['api_key' => 'Malformed API key JSON structure']);
        }

        $id = $decrypted_data->id;
        $mode = $decrypted_data->zatca_mode;
        $modeEnum = ZatcaModeHeaderService::mapMode($mode);

        Database::get($decrypted_data->zatca_mode);

        $cacheKeyUser = "api_key:{$mode}:user:{$apiKeyHash}";
        $cachedUserJson = RedisService::get($cacheKeyUser);
        if ($cachedUserJson) {
            $user = json_decode($cachedUserJson);
        } else {
            $user = DB::table('users')
                ->select(['id', 'email', 'expire_at'])
                ->where('id', $id)
                ->where('api_key', $apiKey)
                ->where('is_confirmed', 1)
                ->whereNull('confirmation_token')
                ->where('active', 1)
                ->first();
            if ($user) {
                $ttl = 300;
                $expiresTs = strtotime((string)($user->expire_at ?? ''));
                if ($expiresTs) {
                    $ttl = max(min($expiresTs - time(), 3600), 60);
                }
                RedisService::set($cacheKeyUser, json_encode($user), $ttl);
            }
        }

        if (!$user) {
            RedisService::delete($cacheKeyUser);
            return ApiResponse::error(403, ['message' => 'Invalid or inactive API key']);
        }

        $expires = strtotime((string)($user->expire_at ?? ''));
        if (!$expires || $expires < time()) {
            RedisService::delete($cacheKeyUser);
            return ApiResponse::error(401, ['api_key' => 'API key expired']);
        }

        $cacheKeyWhitelist = "api_key:{$mode}:whitelist:{$user->id}";
        $cachedWhitelist = RedisService::get($cacheKeyWhitelist);
        if ($cachedWhitelist) {
            $entries = json_decode($cachedWhitelist);
        } else {
            $entries = DB::table('whitelists')
                ->where('user_id', $user->id)
                ->get(['type', 'value']);
            if ($entries) {
                RedisService::set($cacheKeyWhitelist, json_encode($entries), 300);
            }
        }

        $ipAllowed = false;
        $hostAllowed = false;
        foreach ($entries as $entry) {
            if ($entry->type === 'ip') {
                $value = (string)$entry->value;
                if (strpos($value, '/') !== false) {
                    [$subnet, $mask] = explode('/', $value, 2);
                    if (filter_var($subnet, FILTER_VALIDATE_IP) && is_numeric($mask)) {
                        if (self::ipInCidr($ip, $subnet, (int)$mask)) {
                            $ipAllowed = true;
                        }
                    }
                } elseif (filter_var($value, FILTER_VALIDATE_IP) && $value === $ip) {
                    $ipAllowed = true;
                }
            } elseif ($entry->type === 'domain') {
                $value = strtolower((string)$entry->value);
                $reqHost = strtolower((string)$host);
                if ($reqHost === $value || str_ends_with($reqHost, '.' . $value)) {
                    $hostAllowed = true;
                }
            }
        }

        if (!($ipAllowed || $hostAllowed)) {
            RedisService::delete($cacheKeyWhitelist);
            return ApiResponse::error(403, [
                'message' => 'Client IP or domain not whitelisted',
                'ip' => $ip,
                'host' => $host,
            ]);
        }

        $user->zatca_mode = $mode;
        $r->attributes->set('user', $user);
        $r->attributes->set('zatca_mode', $mode);
        $r->attributes->set('zatca_enum', $modeEnum);

        return null;
    }

    private static function ipInCidr(string $ip, string $subnet, int $mask): bool
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP) || !filter_var($subnet, FILTER_VALIDATE_IP)) {
            return false;
        }
        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        $maskLong = -1 << (32 - $mask);
        $subnetLong &= $maskLong;
        return ($ipLong & $maskLong) === $subnetLong;
    }
}
