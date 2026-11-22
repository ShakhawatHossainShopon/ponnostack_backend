<?php

declare(strict_types=1);

use App\Controllers\TenantController;

/** @var \App\Core\Router $router */

// Admin Tenant & Storefront Lifecycle APIs
$router->get('/api/admin/tenants', [TenantController::class, 'index']);
$router->post('/api/admin/tenants', [TenantController::class, 'store']);
$router->get('/api/admin/tenants/{id}', [TenantController::class, 'show']);
$router->post('/api/admin/tenants/{id}/approve-payment', [TenantController::class, 'approvePayment']);
$router->post('/api/admin/tenants/{id}/setup', [TenantController::class, 'setupStore']);
$router->post('/api/admin/tenants/{id}/approve', [TenantController::class, 'approve']);
$router->post('/api/admin/tenants/{id}/extend', [TenantController::class, 'extend']);
$router->post('/api/admin/tenants/{id}/status', [TenantController::class, 'toggleStatus']);
$router->post('/api/admin/tenants/{id}/payments', [TenantController::class, 'recordPayment']);
$router->get('/api/admin/tenants/{id}/payments', [TenantController::class, 'getPayments']);
$router->delete('/api/admin/tenants/{id}', [TenantController::class, 'destroy']);

// Dedicated Billing & Invoice Management APIs
$router->get('/api/admin/payments', [TenantController::class, 'getAllPayments']);
$router->post('/api/admin/payments/manual', [TenantController::class, 'recordManualBill']);
$router->get('/api/admin/payments/{id}/invoice', [TenantController::class, 'getInvoice']);
$router->post('/api/admin/payments/{id}/approve', [TenantController::class, 'approvePaymentRecord']);
$router->delete('/api/admin/payments/{id}', [TenantController::class, 'deletePaymentRecord']);
