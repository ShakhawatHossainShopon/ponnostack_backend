<?php

declare(strict_types=1);

use App\Controllers\InboxController;

/** @var \App\Core\Router $router */

// Admin Inbox & Inquiries API
$router->get('/api/admin/inbox', [InboxController::class, 'index']);
$router->get('/api/admin/inbox/{id}', [InboxController::class, 'show']);
$router->put('/api/admin/inbox/{id}/read', [InboxController::class, 'markRead']);
$router->post('/api/admin/inbox/{id}/read', [InboxController::class, 'markRead']);
$router->delete('/api/admin/inbox/{id}', [InboxController::class, 'destroy']);
