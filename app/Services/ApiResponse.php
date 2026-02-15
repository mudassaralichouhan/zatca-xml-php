<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\Response;

final class ApiResponse
{
    /**
     * Create a standardized JSON response envelope.
     */
    public static function make(int $status = 200, array $data = [], array $errors = [], array $meta = []): Response
    {
        $payload = [
            'success' => $status >= 200 && $status < 300,
            'code' => $status,
            'data' => $data, // Keep as array to preserve numeric indices
            'errors' => $errors,
            'meta' => $meta,
        ];

        return new Response(
            json_encode($payload),
            $status,
            self::defaultHeaders()
        );
    }

    public static function error(int $status, array $errors, array $meta = []): Response
    {
        return self::make($status, [], $errors, $meta);
    }

    public static function success(array $data = [], int $status = 200, array $meta = []): Response
    {
        return self::make($status, $data, [], $meta);
    }

    /**
     * Normalize an existing Response into the envelope if JSON; otherwise wrap as text.
     */
    public static function normalize(Response $response): Response
    {
        $status = $response->getStatusCode();
        $content = $response->getContent();
        $ctype = $response->headers->get('Content-Type', 'application/json');

        if (is_string($ctype) && str_contains($ctype, 'application/json')) {
            $decoded = json_decode($content, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                // If already in envelope shape, just ensure headers
                if (is_array($decoded) && array_key_exists('success', $decoded) && array_key_exists('data', $decoded) && array_key_exists('errors', $decoded)) {
                    foreach (self::defaultHeaders() as $k => $v) {
                        $response->headers->set($k, $v);
                    }
                    return $response;
                }

                // Heuristic: map common error shapes
                if ($status >= 400) {
                    $errors = isset($decoded['errors']) && is_array($decoded['errors'])
                        ? $decoded['errors']
                        : (isset($decoded['message']) ? ['message' => $decoded['message']] : [$decoded]);
                    return self::error($status, $errors);
                }

                $data = is_array($decoded) ? $decoded : ['result' => $decoded];
                return self::success($data, $status);
            }
        }

        // Non-JSON or invalid JSON content
        if ($status >= 400) {
            return self::error($status, ['message' => 'Request failed', 'body' => $content]);
        }
        return self::success(['body' => $content], $status);
    }

    private static function defaultHeaders(): array
    {
        return [
            'Content-Type' => 'application/json; charset=utf-8',
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'Referrer-Policy' => 'no-referrer',
        ];
    }
}
