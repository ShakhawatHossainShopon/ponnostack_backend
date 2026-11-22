<?php

declare(strict_types=1);

/**
 * Ponnostack Merchant Portal - Auth Helper & Session Guard
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$root = dirname(__DIR__);
require_once $root . '/backend/app/Core/Autoloader.php';
\App\Core\Autoloader::register();

$envPath = file_exists($root . '/.env') ? $root . '/.env' : $root . '/backend/.env';
\App\Core\Env::load($envPath);

function getLoggedInTenant(): ?array
{
    $tenantUuid = $_SESSION['customer_uuid'] ?? $_SESSION['tenant_uuid'] ?? null;

    // Auto-login from 30-day persistent cookie if session expired
    if (!$tenantUuid && !empty($_COOKIE['ponnostack_tenant_uuid'])) {
        $tenantUuid = (string)$_COOKIE['ponnostack_tenant_uuid'];
        $_SESSION['customer_uuid'] = $tenantUuid;
        $_SESSION['tenant_uuid'] = $tenantUuid;
    }

    if (!$tenantUuid) {
        return null;
    }

    try {
        $tenantModel = new \App\Models\Tenant();
        $tenant = $tenantModel->findByUuid($tenantUuid);
        return $tenant ?: null;
    } catch (\Throwable $e) {
        error_log('Portal auth error: ' . $e->getMessage());
        return null;
    }
}

function requirePortalAuth(): array
{
    $tenant = getLoggedInTenant();
    if (!$tenant) {
        header('Location: login.php');
        exit;
    }
    return $tenant;
}
