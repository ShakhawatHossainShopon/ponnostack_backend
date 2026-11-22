<?php

declare(strict_types=1);

/**
 * Ponnostack Platform - Storefront Availability Check API
 * 
 * Public Endpoint: GET /api/storefront/check-slug.php
 * Supports real-time availability checks for:
 * - slug:       ?slug={store-slug}
 * - email:      ?email={owner_email}
 * - store_name: ?name={store_name}
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$root = dirname(__DIR__, 2);
require_once $root . '/backend/app/Core/Autoloader.php';
\App\Core\Autoloader::register();
$envPath = file_exists($root . '/.env') ? $root . '/.env' : $root . '/backend/.env';
\App\Core\Env::load($envPath);

use App\Models\Tenant;

try {
    $tenantModel = new Tenant();

    // 1. Check Owner Email
    if (isset($_GET['email'])) {
        $email = strtolower(trim((string)$_GET['email']));
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode([
                'available' => false,
                'field'     => 'email',
                'message'   => 'সঠিক ইমেইল ঠিকানা প্রদান করুন।'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $available = $tenantModel->isEmailAvailable($email);
        echo json_encode([
            'available' => $available,
            'field'     => 'email',
            'email'     => $email,
            'message'   => $available ? 'ইমেইলটি ব্যবহারযোগ্য!' : 'স্টোরফ্রন্ট ইমেইল ইতিমধ্যে নিবন্ধিত আছে।'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 2. Check Store Name
    if (isset($_GET['name']) || isset($_GET['store_name'])) {
        $name = trim((string)($_GET['name'] ?? $_GET['store_name']));
        if (empty($name) || mb_strlen($name) < 2) {
            echo json_encode([
                'available' => false,
                'field'     => 'name',
                'message'   => 'কমপক্ষে ২ অক্ষর হতে হবে।'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $available = $tenantModel->isNameAvailable($name);
        echo json_encode([
            'available' => $available,
            'field'     => 'name',
            'name'      => $name,
            'message'   => $available ? 'স্টোরের নামটি ফাঁকা আছে!' : 'এই নামে ইতিমধ্যে একটি স্টোর নিবন্ধিত আছে।'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 3. Check Slug (Default)
    $rawSlug = trim((string)($_GET['slug'] ?? ''));
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $rawSlug), '-'));

    if (empty($slug) || strlen($slug) < 2) {
        echo json_encode([
            'available' => false,
            'field'     => 'slug',
            'slug'      => $slug,
            'message'   => 'কমপক্ষে ২ অক্ষর হতে হবে।'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $reserved = ['api', 'admin', 'backend', 'auth', 'login', 'portal', 'dashboard', 'checkout', 'support', 'help', 'static', 'assets', 'www'];
    if (in_array($slug, $reserved, true)) {
        echo json_encode([
            'available' => false,
            'field'     => 'slug',
            'slug'      => $slug,
            'message'   => 'এই স্লাগটি সংরক্ষিত।'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $available = $tenantModel->isSlugAvailable($slug);

    echo json_encode([
        'available' => $available,
        'field'     => 'slug',
        'slug'      => $slug,
        'preview'   => "https://{$slug}.ponnostack.shop",
        'message'   => $available ? 'স্লাগটি ফাঁকা আছে!' : 'এই স্লাগটি ইতিমধ্যে ব্যবহৃত হয়েছে।'
    ], JSON_UNESCAPED_UNICODE);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'available' => false,
        'message'   => 'সার্ভারে সমস্যা দেখা দিয়েছে: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
