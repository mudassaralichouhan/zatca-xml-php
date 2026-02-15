<?php

namespace App\Middleware;

use App\Database\Database;
use App\Services\JWTService;
use App\Services\RedisService;
use App\Services\ZatcaModeHeaderService;
use Illuminate\Database\Capsule\Manager as DB;
use Symfony\Component\HttpFoundation\{Request, Response};
use Firebase\JWT\{JWT, Key};
use App\Services\ApiResponse;

final class JwtMiddleware implements Middleware
{
    public function __construct(private string $secret)
    {
    }

    public function __invoke(Request $r): ?Response
    {
        $hdr = $r->headers->get('Authorization');
        if (!$hdr || !str_starts_with($hdr, 'Bearer ')) {
            return ApiResponse::error(401, ['token' => 'Unauthorized']);
        }

        try {
            // Small leeway to account for clock skew
            \Firebase\JWT\JWT::$leeway = 5;
            $payload = JWT::decode(substr($hdr, 7), new Key($this->secret, 'HS256'));
            $modeEnum = ZatcaModeHeaderService::mapMode($payload->zatca_mode);
        } catch (\Throwable) {
            return ApiResponse::error(401, ['token' => 'Invalid token']);
        }

        // Optional Redis blacklist check for revoked tokens
        if (isset($payload->jti) && JWTService::isJtiBlacklisted($payload->jti)) {
            return ApiResponse::error(401, ['token' => 'Token revoked']);
        }

        // Prefer per-token cache when jti is available, else fall back to user/email hash
        $cacheKey = isset($payload->jti)
            ? 'jwt:user:jti:' . $payload->jti
            : 'jwt:user:' . md5($payload->email . '_' . $payload->id);
        $cachedUserJson = RedisService::get($cacheKey);

        if ($cachedUserJson !== null) {
            $r->attributes->set('user', json_decode($cachedUserJson));
            $r->attributes->set('zatca_mode', $payload->zatca_mode);
            $r->attributes->set('zatca_enum', $modeEnum);
            return null;
        }

        // Short-lived per-user status cache to avoid DB on hot paths
        $statusKey = 'user:status:' . $payload->zatca_mode . ':' . $payload->id . ':' . sha1((string)$payload->email);
        $status = RedisService::get($statusKey);
        if ($status === 'active') {
            $user = (object) [
                'id' => $payload->id,
                'email' => $payload->email,
                'zatca_mode' => $payload->zatca_mode,
                'iat' => $payload->iat ?? null,
                'exp' => $payload->exp ?? null,
            ];
            // Cache the full user object under token key until token expiry
            if (!empty($payload->exp)) {
                $ttl = $payload->exp - time();
                if ($ttl > 0) {
                    RedisService::set($cacheKey, json_encode($user), $ttl);
                }
            }
            $r->attributes->set('user', $user);
            $r->attributes->set('zatca_mode', $payload->zatca_mode);
            $r->attributes->set('zatca_enum', $modeEnum);
            return null;
        } elseif ($status === 'inactive') {
            return ApiResponse::error(401, ['token' => 'User not found or inactive']);
        }

        Database::get($payload->zatca_mode);
        $user = DB::table('users')
            ->select(['id', 'email'])
            ->where('id', $payload->id)
            ->where('email', $payload->email)
            ->where('is_confirmed', 1)
            ->whereNull('confirmation_token')
            ->where('active', 1)
            ->first();

        if (!$user) {
            return ApiResponse::error(401, ['token' => 'User not found or inactive']);
        }

        $user->zatca_mode = $payload->zatca_mode;
        $user->iat = $payload->iat;
        $user->exp = $payload->exp;

        // Cache the user data as JSON, with TTL same as token expiry
        if (!empty($payload->exp)) {
            $ttl = $payload->exp - time();
            if ($ttl > 0) {
                RedisService::set($cacheKey, json_encode($user), $ttl);
            }
        }
        // Also cache user status briefly to avoid repeated DB hits across tokens
        RedisService::set($statusKey, 'active', 60);

        $r->attributes->set('user', $user);
        $r->attributes->set('zatca_mode', $payload->zatca_mode);
        $r->attributes->set('zatca_enum', $modeEnum);

        return null;
    }
}
