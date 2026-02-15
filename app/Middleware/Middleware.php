<?php

namespace App\Middleware;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface Middleware
{
    public function __invoke(Request $request): ?Response;
}
