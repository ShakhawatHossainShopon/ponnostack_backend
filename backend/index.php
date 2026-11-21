<?php

declare(strict_types=1);

/**
 * Ponnostack Platform - Backend API Entry Point
 * 
 * Clean, Modular MVC Architecture
 */

// 1. Register PSR-4 Autoloader
require_once __DIR__ . '/app/Core/Autoloader.php';
\App\Core\Autoloader::register();

// 2. Load Environment Variables (Checks backend/.env or root .env)
$envPath = file_exists(__DIR__ . '/.env') ? __DIR__ . '/.env' : dirname(__DIR__) . '/.env';
\App\Core\Env::load($envPath);

// 3. Auto-Migrate Database (Ensures required tables & default seeds exist)
try {
    \App\Core\Migration::run();
} catch (\Throwable $e) {
    // If DB fails, let it be caught during query execution or log error
    error_log('Migration check: ' . $e->getMessage());
}

// 4. Initialize Router
$router = new \App\Core\Router();

// 5. Auto-register Route files from app/Routes/
$routesDir = __DIR__ . '/app/Routes';
if (is_dir($routesDir)) {
    $routeFiles = glob($routesDir . '/*.php') ?: [];
    foreach ($routeFiles as $routeFile) {
        require $routeFile;
    }
}

// 6. Dispatch Request
$router->dispatch();
