<?php

declare(strict_types=1);

use App\Controllers\TenantController;

/** @var \App\Core\Router $router */

// Admin Tenant & Storefront Lifecycle APIs
$router->get('/api/admin/tenants', [TenantController::class, 'index']);
$router->post('/api/admin/tenants', [TenantController::class, 'store']);
$router->get('/api/admin/tenants/{id}', [TenantController::class, 'show']);
$router->post('/api/admin/tenants/{id}/update', [TenantController::class, 'update']);
$router->put('/api/admin/tenants/{id}', [TenantController::class, 'update']);
$router->post('/api/admin/tenants/{id}', [TenantController::class, 'update']);
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

// Public Client Storefront Gateway API
$router->get('/api/storefront/{uuid}', [TenantController::class, 'getStorefront']);
$router->post('/api/storefront/auth/login', [TenantController::class, 'storefrontLogin']);
$router->get('/api/tenants/{uuid}', [TenantController::class, 'getStorefront']);
$router->post('/api/tenants/auth/login', [TenantController::class, 'storefrontLogin']);
$router->get('/api/storefront/store/{uuid}', [TenantController::class, 'getStorefront']);

// Client Store Profile & Design Update APIs
$router->get('/api/tenants/{uuid}/profile', [TenantController::class, 'profile']);
$router->put('/api/tenants/{uuid}/profile', [TenantController::class, 'updateProfile']);
$router->post('/api/tenants/{uuid}/profile', [TenantController::class, 'updateProfile']);
$router->get('/api/storefront/{uuid}/profile', [TenantController::class, 'profile']);
$router->put('/api/storefront/{uuid}/profile', [TenantController::class, 'updateProfile']);
$router->post('/api/storefront/{uuid}/profile', [TenantController::class, 'updateProfile']);

// Client Storefront Carousel Banners Upload & Delete APIs
$router->post('/api/tenants/{uuid}/banners', [TenantController::class, 'uploadBanner']);
$router->post('/api/tenants/{uuid}/banners/delete', [TenantController::class, 'deleteBanner']);
$router->post('/api/storefront/{uuid}/banners', [TenantController::class, 'uploadBanner']);
$router->post('/api/storefront/{uuid}/banners/delete', [TenantController::class, 'deleteBanner']);

// Client Storefront Subscription & Billing Payments APIs
$router->get('/api/tenants/{uuid}/payments', [TenantController::class, 'getStorefrontPayments']);
$router->post('/api/tenants/{uuid}/payments', [TenantController::class, 'submitStorefrontPayment']);
$router->get('/api/storefront/{uuid}/payments', [TenantController::class, 'getStorefrontPayments']);
$router->post('/api/storefront/{uuid}/payments', [TenantController::class, 'submitStorefrontPayment']);
$router->get('/api/tenants/{uuid}/payments/{id}/invoice', [TenantController::class, 'getStorefrontInvoice']);
$router->get('/api/storefront/{uuid}/payments/{id}/invoice', [TenantController::class, 'getStorefrontInvoice']);
