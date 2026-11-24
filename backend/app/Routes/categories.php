<?php

declare(strict_types=1);

use App\Controllers\CategoryController;

/** @var \App\Core\Router $router */

// Generic Category Endpoints
$router->get('/api/categories', [CategoryController::class, 'index']);
$router->post('/api/categories/upload-image', [CategoryController::class, 'uploadImage']);
$router->post('/api/categories', [CategoryController::class, 'store']);
$router->get('/api/categories/{uuid}', [CategoryController::class, 'show']);
$router->put('/api/categories/{uuid}', [CategoryController::class, 'update']);
$router->post('/api/categories/{uuid}', [CategoryController::class, 'update']);
$router->delete('/api/categories/{uuid}', [CategoryController::class, 'destroy']);
$router->post('/api/categories/{uuid}/delete', [CategoryController::class, 'destroy']);

// Scoped Tenant Category Endpoints
$router->get('/api/tenants/{uuid}/categories', [CategoryController::class, 'index']);
$router->post('/api/tenants/{uuid}/categories', [CategoryController::class, 'store']);
$router->get('/api/storefront/{uuid}/categories', [CategoryController::class, 'index']);
