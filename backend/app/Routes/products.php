<?php

declare(strict_types=1);

use App\Controllers\ProductController;

/** @var \App\Core\Router $router */

// Standard Storefront Admin Product Endpoints
$router->get('/api/products', [ProductController::class, 'index']);
$router->post('/api/products', [ProductController::class, 'store']);
$router->post('/api/products/upload-images', [ProductController::class, 'uploadImages']);
$router->get('/api/products/{uuid}', [ProductController::class, 'show']);
$router->put('/api/products/{uuid}', [ProductController::class, 'update']);
$router->post('/api/products/{uuid}', [ProductController::class, 'update']);
$router->delete('/api/products/{uuid}', [ProductController::class, 'destroy']);
$router->post('/api/products/{uuid}/stock', [ProductController::class, 'adjustStock']);
$router->post('/api/products/{uuid}/adjust-stock', [ProductController::class, 'adjustStock']);
$router->put('/api/products/{uuid}/discount', [ProductController::class, 'setDiscount']);
$router->post('/api/products/{uuid}/discount', [ProductController::class, 'setDiscount']);
$router->delete('/api/products/{uuid}/discount', [ProductController::class, 'removeDiscount']);

// Tenant & Storefront Scoped Product Endpoints
$router->get('/api/tenants/{uuid}/products', [ProductController::class, 'index']);
$router->post('/api/tenants/{uuid}/products', [ProductController::class, 'store']);
$router->post('/api/tenants/{uuid}/products/upload-images', [ProductController::class, 'uploadImages']);
$router->get('/api/storefront/{uuid}/products', [ProductController::class, 'index']);
