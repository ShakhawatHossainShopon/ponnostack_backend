<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Lightweight REST Router
 */
class Router
{
    private array $routes = [];

    public function get(string $path, callable|array $handler): self
    {
        return $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, callable|array $handler): self
    {
        return $this->addRoute('POST', $path, $handler);
    }

    public function put(string $path, callable|array $handler): self
    {
        return $this->addRoute('PUT', $path, $handler);
    }

    public function delete(string $path, callable|array $handler): self
    {
        return $this->addRoute('DELETE', $path, $handler);
    }

    private function addRoute(string $method, string $path, callable|array $handler): self
    {
        // Normalize path and convert {param} to regex named group
        $normalized = '/' . trim($path, '/');
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $normalized);
        $pattern = '#^' . $pattern . '$#';

        $this->routes[$method][] = [
            'pattern' => $pattern,
            'handler' => $handler,
        ];

        return $this;
    }

    public function dispatch(): void
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        
        // Handle CORS Preflight
        if ($method === 'OPTIONS') {
            http_response_code(204);
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
            exit;
        }

        // Get sanitized request URI relative to entry point
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        if ($scriptDir !== '/' && !empty($scriptDir) && str_starts_with($uri, $scriptDir)) {
            $uri = substr($uri, strlen($scriptDir));
        }
        $uri = '/' . trim($uri, '/');

        // Match route
        if (isset($this->routes[$method])) {
            foreach ($this->routes[$method] as $route) {
                if (preg_match($route['pattern'], $uri, $matches)) {
                    $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                    $handler = $route['handler'];

                    if (is_array($handler)) {
                        [$class, $action] = $handler;
                        $controller = new $class();
                        $controller->$action(...$params);
                        return;
                    }

                    if (is_callable($handler)) {
                        $handler(...$params);
                        return;
                    }
                }
            }
        }

        Response::error("Route {$method} {$uri} not found", 404);
    }
}
