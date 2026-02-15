<?php

namespace App\Middleware;

use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\RateLimiter\RateLimiterFactory;
use App\Services\ApiResponse;

final class RateLimiterMiddleware implements Middleware
{
    /** @param array<string,RateLimiterFactory> $buckets */
    public function __construct(private array $buckets)
    {
    }

    public function __invoke(Request $r): ?Response
    {
        $profile = $r->attributes->get('_rate', 'default');
        $key     = $r->getClientIp() . ':' . $r->getPathInfo();

        $limiter = $this->buckets[$profile]->create($key);
        return $limiter->consume()->isAccepted()
            ? null : ApiResponse::error(429, ['message' => 'Too many attempts.']);
    }
}
