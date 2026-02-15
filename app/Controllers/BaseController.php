<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface;

abstract class BaseController
{
    public function __construct()
    {
        // Database::init();
    }

    /**
     * Return a JSON response.
     */
    protected function json(
        ResponseInterface $response,
        mixed             $data,
        int               $status = 200
    ): ResponseInterface {
        $payload = is_string($data) ? ['message' => $data] : $data;
        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        $response->getBody()->write($json);
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}
