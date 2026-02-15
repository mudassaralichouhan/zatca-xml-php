<?php

declare(strict_types=1);

header("Content-type: application/json; charset=utf-8");

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-API-KEY, Zatca-Mode");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

set_error_handler(function (int $severity, string $message, string $file, int $line) {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

register_shutdown_function(function () {
    $err = error_get_last();
    if ($err !== null && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        throw new ErrorException($err['message'], 0, $err['type'], $err['file'], $err['line']);
    }
});

require 'vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use App\Kernel;
use App\Services\ApiResponse;
use App\Config\Config;
use App\Helpers\Helper;

Config::init(__DIR__);

date_default_timezone_set(Config::APP_TIMEZONE('UTC'));

if (Helper::isProduction()) {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
}

try {
    $request = Request::createFromGlobals();
    $response = new Kernel()->handle($request);
    ApiResponse::normalize($response)->send();
} catch (\App\Exceptions\ValidationException | \App\Exceptions\BadRequestException | \App\Exceptions\UnauthorizedException $e) {
    ApiResponse::error($e->getCode() ?: 400, [
        'message' => $e->getMessage(),
        'errors' => $e->getErrors(),
    ])->send();
} catch (Throwable $e) {
    $message = Helper::isProduction() ? 'Internal server error' : $e->getMessage();
    ApiResponse::error(500, [
        'message' => $message,
        'file' => Helper::isProduction() ? null : $e->getFile(),
        'line' => Helper::isProduction() ? null : $e->getLine(),
    ])->send();
}
