<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Base Controller
 */
abstract class Controller
{
    protected function success(mixed $data = null, string $message = 'Success', int $statusCode = 200): void
    {
        Response::success($data, $message, $statusCode);
    }

    protected function error(string $message = 'An error occurred', int $statusCode = 400, mixed $data = null): void
    {
        Response::error($message, $statusCode, $data);
    }
}
