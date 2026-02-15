<?php

namespace App\Controllers;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use App\Services\ApiResponse;
use App\Services\IpDetectionService;

class UserController extends BaseController
{
    public function me(Request $request): Response
    {
        $user = $request->attributes->get('user');

        $clientInfo = IpDetectionService::getClientInfo($request);

        return ApiResponse::success([
            'user' => $user,
            'ip' => $clientInfo['ip'],
            'host' => $clientInfo['host'],
            'agent' => $clientInfo['user_agent'],
        ]);
    }
}
