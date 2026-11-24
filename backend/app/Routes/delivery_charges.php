<?php

declare(strict_types=1);

use App\Controllers\DeliveryChargeController;

/** @var \App\Core\Router $router */

// Generic Delivery Charges Endpoints
$router->get('/api/delivery-charges', [DeliveryChargeController::class, 'index']);
$router->get('/api/delivery-charges/{uuid}', [DeliveryChargeController::class, 'show']);
$router->post('/api/delivery-charges', [DeliveryChargeController::class, 'store']);
$router->put('/api/delivery-charges/{uuid}', [DeliveryChargeController::class, 'update']);
$router->post('/api/delivery-charges/{uuid}', [DeliveryChargeController::class, 'update']);
$router->delete('/api/delivery-charges/{uuid}', [DeliveryChargeController::class, 'destroy']);
$router->post('/api/delivery-charges/{uuid}/delete', [DeliveryChargeController::class, 'destroy']);

// Tenant Scoped Delivery Charges Endpoints
$router->get('/api/tenants/{uuid}/delivery-charges', [DeliveryChargeController::class, 'index']);
$router->post('/api/tenants/{uuid}/delivery-charges', [DeliveryChargeController::class, 'store']);
$router->get('/api/storefront/{uuid}/delivery-charges', [DeliveryChargeController::class, 'index']);
