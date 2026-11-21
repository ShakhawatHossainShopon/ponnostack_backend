<?php

declare(strict_types=1);

// 1. Boot Core Autoloader & Environment
$backendRoot = dirname(dirname(__DIR__)); // C:\xampp\htdocs\backend

require_once $backendRoot . '/app/Core/Autoloader.php';
\App\Core\Autoloader::register();

$envPath = file_exists($backendRoot . '/.env') ? $backendRoot . '/.env' : dirname($backendRoot) . '/.env';
\App\Core\Env::load($envPath);

// 2. Session Management
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 3. Session Gatekeeper: If not logged in, redirect to login page
if (empty($_SESSION['admin_user'])) {
    header('Location: login.php');
    exit;
}

$currentUser = $_SESSION['admin_user'];
