<?php

namespace App\Routing;

use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class Router
{
    private RouteCollection $routes;
    private array  $stack   = [];
    private string $prefix  = '';

    public function __construct(RouteCollection $routes)
    {
        $this->routes = $routes;
    }

    public function group(string $prefix, callable $cb, array $mw = []): void
    {
        [$oldP, $oldS] = [$this->prefix, $this->stack];
        $this->prefix .= $prefix;
        $this->stack   = [...$this->stack, ...$mw];
        $cb($this);
        [$this->prefix,$this->stack] = [$oldP,$oldS];
    }

    public function add(
        string $method,
        string $path,
        array|callable $ctrl,
        array $mw = [],
        array $extras = []
    ): void {
        $route = new Route(
            $this->prefix.$path,
            array_merge([
                '_controller' => $ctrl,
                '_mw'        => [...$this->stack,...$mw],
            ], $extras),
            [],
            [],
            '',
            [],
            [$method]
        );
        $this->routes->add(md5($method.$this->prefix.$path), $route);
    }
}
