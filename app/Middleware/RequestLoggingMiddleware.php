<?php

namespace App\Middleware;

use App\Logging\Logger;
use App\Services\IpDetectionService;
use Symfony\Component\HttpFoundation\{Request, Response};

final class RequestLoggingMiddleware implements Middleware
{
    public function __invoke(Request $r): ?Response
    {
        $requestId = bin2hex(random_bytes(12));
        $r->attributes->set('request_id', $requestId);
        $r->attributes->set('request_start', microtime(true));

        $client = IpDetectionService::getClientInfo($r);

        Logger::systemInfo('request:start', [
            'id' => $requestId,
            'method' => $r->getMethod(),
            'path' => $r->getPathInfo(),
            'ip' => $client['ip'] ?? null,
            'host' => $client['host'] ?? null,
            'ua' => $client['user_agent'] ?? null,
        ]);

        return null;
    }
}
