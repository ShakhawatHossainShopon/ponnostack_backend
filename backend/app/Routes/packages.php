<?php

declare(strict_types=1);

use App\Controllers\PackageController;

/** @var \App\Core\Router $router */

// Public Dynamic Pricing API for Homepage
$router->get('/api/packages', [PackageController::class, 'index']);

// Protected Admin Price Update API
$router->put('/api/admin/packages/{id}', [PackageController::class, 'updatePrice']);
$router->post('/api/admin/packages/{id}', [PackageController::class, 'updatePrice']);
