<?php

declare(strict_types=1);

/**
 * Ponnostack Merchant Portal - Authentication API Endpoint
 * Handles JSON and Form POST login, session creation, and 30-day cookie persistence.
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$root = dirname(__DIR__, 2);
require_once $root . '/backend/app/Core/Autoloader.php';
\App\Core\Autoloader::register();

$envPath = file_exists($root . '/.env') ? $root . '/.env' : $root . '/backend/.env';
\App\Core\Env::load($envPath);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$tenantModel = new \App\Models\Tenant();

// 1. GET: Check current login status
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $tenantUuid = $_SESSION['customer_uuid'] ?? $_SESSION['tenant_uuid'] ?? $_COOKIE['ponnostack_tenant_uuid'] ?? null;
    if ($tenantUuid) {
        $tenant = $tenantModel->findByUuid((string)$tenantUuid);
        if ($tenant) {
            echo json_encode([
                'success' => true,
                'authenticated' => true,
                'data' => [
                    'tenant' => [
                        'uuid'  => $tenant['uuid'],
                        'name'  => $tenant['name'],
                        'slug'  => $tenant['slug'],
                        'email' => $tenant['owner_email'],
                        'status'=> $tenant['status'],
                    ]
                ]
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    echo json_encode(['success' => false, 'authenticated' => false], JSON_UNESCAPED_UNICODE);
    exit;
}

// 2. POST: Handle Login or Logout
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = [];
    $raw = file_get_contents('php://input');
    if (!empty($raw)) {
        $json = json_decode($raw, true);
        if (is_array($json)) {
            $input = $json;
        }
    }
    $input = array_merge($_POST, $input);

    $action = $input['action'] ?? 'login';

    // Handle Logout
    if ($action === 'logout') {
        unset($_SESSION['customer_uuid'], $_SESSION['tenant_uuid'], $_SESSION['tenant_auth_token'], $_SESSION['tenant_email'], $_SESSION['tenant_name']);
        session_destroy();

        setcookie('ponnostack_tenant_uuid', '', time() - 3600, '/');
        setcookie('ponnostack_tenant_token', '', time() - 3600, '/');

        echo json_encode(['success' => true, 'message' => 'লগআউট সফল হয়েছে।'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Handle Login
    $email = strtolower(trim($input['email'] ?? ''));
    $password = (string)($input['password'] ?? '');
    $remember = !empty($input['remember']);

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'সঠিক ইমেইল ঠিকানা প্রদান করুন।'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (empty($password)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'পাসওয়ার্ড প্রদান করা আবশ্যক।'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Authenticate against `tenants` table
    $tenant = $tenantModel->authenticate($email, $password);

    if (!$tenant) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'ইমেইল অথবা পাসওয়ার্ড সঠিক নয়। অনুগ্রহ করে পুনরায় চেকআউটের সময় দেওয়া ক্রেডেনশিয়াল দিন।'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Generate Auth Token
    $authToken = bin2hex(random_bytes(32));

    // Set PHP Session
    $_SESSION['customer_uuid'] = $tenant['uuid'];
    $_SESSION['tenant_uuid'] = $tenant['uuid'];
    $_SESSION['tenant_auth_token'] = $authToken;
    $_SESSION['tenant_email'] = $tenant['owner_email'];
    $_SESSION['tenant_name'] = $tenant['name'];

    // Set 30-Day Cookie if Remember Me is checked (or default 30-day for seamless UX)
    $expireTime = $remember ? time() + (86400 * 30) : 0;
    setcookie('ponnostack_tenant_uuid', $tenant['uuid'], $expireTime, '/');
    setcookie('ponnostack_tenant_token', $authToken, $expireTime, '/');

    echo json_encode([
        'success' => true,
        'message' => 'লগইন সফল হয়েছে! পোর্টালে রিডাইরেক্ট হচ্ছে...',
        'data' => [
            'auth' => [
                'token' => $authToken,
                'uuid'  => $tenant['uuid'],
                'email' => $tenant['owner_email'],
                'name'  => $tenant['name'],
                'slug'  => $tenant['slug'],
            ],
            'tenant' => [
                'id'            => $tenant['id'],
                'uuid'          => $tenant['uuid'],
                'name'          => $tenant['name'],
                'slug'          => $tenant['slug'],
                'domain'        => $tenant['domain'],
                'status'        => $tenant['status'],
                'subdomain_url' => $tenant['subdomain_url'],
            ]
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
