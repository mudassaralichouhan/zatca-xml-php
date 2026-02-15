<?php

namespace App\Controllers;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use App\Services\ApiResponse;
use App\Services\IpDetectionService;
use App\Logging\Logger;
use App\Helper\Helper;

class HealthController extends BaseController
{
    public function check(Request $request): Response
    {
        // Get basic system information
        $clientInfo = IpDetectionService::getClientInfo($request);

        Logger::systemInfo('Health check', [
            'ip' => $clientInfo['ip'] ?? null,
            'user_agent' => $clientInfo['user_agent'] ?? null,
        ]);

        $pro = Helper::isProduction();

        $healthData = [
            'status' => 'healthy',
            'timestamp' => date('Y-m-d H:i:s'),
            'uptime' => $this->getUptime(),
            'memory_usage' => $pro ? [] : $this->getMemoryUsage(),
            'client' => $clientInfo,
            'environment' => $pro ? [] : [
                'php_version' => PHP_VERSION,
                'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown'
            ]
        ];

        return ApiResponse::success($healthData);
    }

    private function getUptime(): string
    {
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            return implode(', ', $load);
        }
        return 'N/A';
    }

    private function getMemoryUsage(): array
    {
        return [
            'current' => $this->formatBytes(memory_get_usage(true)),
            'peak' => $this->formatBytes(memory_get_peak_usage(true)),
            'limit' => ini_get('memory_limit')
        ];
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
