<?php

declare(strict_types=1);

use App\Controllers\AdminController;

/** @var \App\Core\Router $router */

// Authentication
$router->post('/api/admin/login', [AdminController::class, 'login']);
$router->post('/api/admin/logout', [AdminController::class, 'logout']);
$router->get('/api/admin/me', [AdminController::class, 'me']);

// Admin Accounts CRUD
$router->get('/api/admin/users', [AdminController::class, 'index']);
$router->post('/api/admin/users', [AdminController::class, 'store']);
$router->put('/api/admin/users/{id}', [AdminController::class, 'update']);
$router->delete('/api/admin/users/{id}', [AdminController::class, 'destroy']);
