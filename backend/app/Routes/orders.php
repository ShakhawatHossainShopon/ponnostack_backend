<?php

use App\Controllers\OrderController;

/** @var \App\Core\Router $router */

// Public Storefront Endpoints
$router->post('/api/orders', [OrderController::class, 'store']);
$router->get('/api/orders/track/{orderNumber}', [OrderController::class, 'track']);

// Admin Order Management Endpoints (specific subpaths must precede generic {uuid})
$router->get('/api/orders/stats', [OrderController::class, 'stats']);
$router->get('/api/orders', [OrderController::class, 'index']);
$router->get('/api/orders/{uuid}', [OrderController::class, 'show']);
$router->put('/api/orders/{uuid}/status', [OrderController::class, 'updateStatus']);
$router->post('/api/orders/{uuid}/status', [OrderController::class, 'updateStatus']);
