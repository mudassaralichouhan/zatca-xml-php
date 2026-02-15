<?php

namespace App;

use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\{RouteCollection, Matcher\UrlMatcher, RequestContext};
use Symfony\Component\Cache\Adapter\{FilesystemAdapter, RedisAdapter};
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\RateLimiter\Storage\CacheStorage;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use App\Routing\Router;
use App\Middleware\{JwtMiddleware, ApiKeyMiddleware, RateLimiterMiddleware, RequestLoggingMiddleware};
use App\Services\ApiResponse;
use App\Config\Config;
use Predis\Client;
use App\Config\RedisConfig;

final class Kernel
{
    private array $middlewares;
    private RouteCollection $routes;
    private CacheItemPoolInterface $cacheAdapter;

    public function __construct()
    {
        $storageDriver = Config::STORAGE_DRIVER('file');

        if ($storageDriver === 'file') {

            // Local filesystem cache
            $cachePath = getenv('STORAGE_FILE_PATH') ?: __DIR__ . '/../storage/cache';
            $this->cacheAdapter = new FilesystemAdapter(
                namespace: '',
                defaultLifetime: 0,
                directory: $cachePath
            );

        } else {

            // Default = Redis
            $redisClient = new Client(
                RedisConfig::parameters(),
                RedisConfig::options()
            );
            $redisClient->connect();

            $this->cacheAdapter = new RedisAdapter($redisClient);
        }

        $storage = new CacheStorage($this->cacheAdapter);

        $profiles = require 'Config/rate_limits.php';
        $factories = [];
        foreach ($profiles as $name => $cfg) {
            $factories[$name] = new RateLimiterFactory($cfg, $storage);
        }

        $this->middlewares = [
            'reqlog' => new RequestLoggingMiddleware(),
            'jwt' => new JwtMiddleware(Config::JWT_SECRET()),
            'apiKey' => new ApiKeyMiddleware(),
            'rate' => new RateLimiterMiddleware($factories),
        ];

        $this->routes = new RouteCollection();
        (require 'Config/routes.php')(new Router($this->routes));
    }

    public function handle(Request $req): Response
    {
        $matcher = new UrlMatcher($this->routes, new RequestContext()->fromRequest($req));
        $attr = $matcher->match($req->getPathInfo());
        $req->attributes->add($attr);

        foreach ($attr['_mw'] ?? [] as $name) {
            if ($resp = ($this->middlewares[$name])($req)) {
                if ($req->attributes->has('request_id')) {
                    $resp->headers->set('X-Request-ID', (string)$req->attributes->get('request_id'));
                }
                return $resp;
            }
        }

        $controllerDef = $attr['_controller'];
        if (is_array($controllerDef) && count($controllerDef) === 2) {
            [$className, $methodName] = $controllerDef;

            if (!class_exists($className)) {
                return ApiResponse::error(500, ['message' => "Controller class {$className} not found"]);
            }

            $controller = new $className();

            if (!method_exists($controller, $methodName)) {
                return ApiResponse::error(500, ['message' => "Method {$methodName} not found on {$className}"]);
            }

            $resp = $controller->$methodName($req);
            $response = $resp instanceof Response ? ApiResponse::normalize($resp) : ApiResponse::success(['result' => $resp]);

            if ($req->attributes->has('request_id')) {
                $response->headers->set('X-Request-ID', (string)$req->attributes->get('request_id'));
            }

            try {
                $start = (float)($req->attributes->get('request_start') ?? microtime(true));
                $latencyMs = (int) ((microtime(true) - $start) * 1000);
                $mode = $req->attributes->get('zatca_mode');
                \App\Logging\Logger::info('request:done', [
                    'id' => $req->attributes->get('request_id'),
                    'status' => $response->getStatusCode(),
                    'latency_ms' => $latencyMs,
                    'path' => $req->getPathInfo(),
                    'method' => $req->getMethod(),
                ], is_string($mode) ? $mode : null);
            } catch (\Throwable) {
            }

            return $response;
        }

        return ApiResponse::error(500, ['message' => 'Invalid controller format']);
    }
}
