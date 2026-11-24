<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Standardized JSON API Response Engine
 */
class Response
{
    public static function json(mixed $data = null, string $message = '', int $statusCode = 200, bool $success = true): void
    {
        if (ob_get_level() > 0) {
            ob_clean();
        }

        http_response_code($statusCode);
        header('Content-Type: application/json; charset=UTF-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-Store-UUID, Accept, Origin, Cache-Control');

        echo json_encode([
            'success' => $success,
            'message' => $message,
            'data'    => $data,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        exit;
    }

    public static function success(mixed $data = null, string $message = 'Success', int $statusCode = 200): void
    {
        self::json($data, $message, $statusCode, true);
    }

    public static function error(string $message = 'An error occurred', int $statusCode = 400, mixed $data = null): void
    {
        self::json($data, $message, $statusCode, false);
    }
}
