<?php

use App\Routing\Router;
use App\Controllers as C;

return function (Router $r) {
    $r->add('GET', '/health', [C\HealthController::class, 'check'], mw: ['reqlog'], extras: ['_rate' => 'health']);
    $r->add('GET', '/api/v1/health', [C\HealthController::class, 'check'], mw: ['reqlog'], extras: ['_rate' => 'health']);
    $r->group('/api/v1', function (Router $r) {
        $r->group('', function (Router $r) {
            $r->add('POST', '/csid/compliance', [C\CSIDController::class, 'issueProductionCsidStatus']);
            $r->add('GET', '/csid/compliance', [C\CSIDController::class, 'getComplianceCsidStatus']);
            $r->add('GET', '/csid/production', [C\CSIDController::class, 'getProductionCsidStatus']);
            $r->add('POST', '/clearance', [C\ClearanceController::class, 'clearStandardNotes']);
            $r->add('POST', '/reporting', [C\ReportingController::class, 'reportSimplifiedNotes']);
        }, mw: ['reqlog', 'rate', 'apiKey']);

        $r->group('/auth', function (Router $r) {
            $r->add('POST', '/register', [C\AuthController::class, 'register'], extras: ['_rate' => 'register']);
            $r->add('GET', '/confirm', [C\AuthController::class, 'confirm'], mw: ['rate']);
            $r->add('POST', '/login', [C\AuthController::class, 'login'], extras: ['_rate' => 'login']);
            $r->add('POST', '/mail/resend', [C\AuthController::class, 'resendMail'], extras: ['_rate' => 'resend_register_confirmation']);
        }, mw: ['reqlog']);

        $r->group('/auth', function (Router $r) {
            $r->add('GET', '/me', [C\UserController::class, 'me']);
            $r->add('GET', '/key', [C\ApiKeyController::class, 'all']);
            $r->add('PUT', '/key', [C\ApiKeyController::class, 'regenerate']);
            $r->add('PATCH', '/key', [C\ApiKeyController::class, 'createOrUpdate']);
            $r->add('DELETE', '/key/flush', [C\ApiKeyController::class, 'flush']);
            $r->add('DELETE', '/key', [C\ApiKeyController::class, 'destroy']);
        }, mw: ['reqlog', 'jwt', 'rate']);
    });
};
