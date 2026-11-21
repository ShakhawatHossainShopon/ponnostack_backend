<?php

declare(strict_types=1);

/**
 * Ponnostack Platform - Global Public Dynamic Packages API
 * 
 * Provides live package pricing & product limits for homepage and external clients.
 * URL: /api/packages or /api/packages.php
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');
header('Cache-Control: no-cache, no-store, must-revalidate');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Helper to convert English digits to Bengali digits
function convertToBn(int|string $num): string {
    $en = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    $bn = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];
    return str_replace($en, $bn, (string)$num);
}

function formatPkgData(array $row): array {
    $price = (int)$row['price'];
    $prodLimit = (int)($row['product_limit'] ?? 0);

    return [
        'id'                         => (int)$row['id'],
        'slug'                       => (string)$row['slug'],
        'name_bn'                    => (string)$row['name_bn'],
        'name_en'                    => (string)$row['name_en'],
        'badge_bn'                   => (string)($row['badge_bn'] ?? ''),
        'badge_en'                   => (string)($row['badge_en'] ?? ''),
        'price'                      => $price,
        'formatted_price_bn'         => convertToBn($price),
        'formatted_price_en'         => (string)$price,
        'product_limit'              => $prodLimit,
        'formatted_product_limit_bn' => ($prodLimit === 0) ? 'আনলিমিটেড প্রোডাক্ট আপলোড' : (convertToBn($prodLimit) . 'টি প্রোডাক্ট আপলোড'),
        'formatted_product_limit_en' => ($prodLimit === 0) ? 'Unlimited Products Upload' : ($prodLimit . ' Products Upload'),
        'product_limit_label_bn'     => ($prodLimit === 0) ? 'আনলিমিটেড' : (convertToBn($prodLimit) . ' টি'),
        'product_limit_label_en'     => ($prodLimit === 0) ? 'Unlimited' : ($prodLimit . ' Products'),
        'billing_period_bn'          => (string)$row['billing_period_bn'],
        'billing_period_en'          => (string)$row['billing_period_en'],
        'is_popular'                 => (int)$row['is_popular'],
    ];
}

// 1. Fallback default dataset
$defaultRows = [
    [
        'id'                => 1,
        'slug'              => 'starter',
        'name_bn'           => 'স্ট্যান্ডার্ড প্যাকেজ',
        'name_en'           => 'Standard Plan',
        'badge_bn'          => 'সবচেয়ে জনপ্রিয়',
        'badge_en'          => 'Most Popular',
        'price'             => 200,
        'product_limit'     => 50,
        'billing_period_bn' => '/ মাস',
        'billing_period_en' => '/ month',
        'is_popular'        => 1,
    ],
    [
        'id'                => 2,
        'slug'              => 'professional',
        'name_bn'           => 'প্রিমিয়াম প্যাকেজ',
        'name_en'           => 'Premium Plan',
        'badge_bn'          => 'গ্রোথ ফোকাস্ড',
        'badge_en'          => 'Growth Focused',
        'price'             => 400,
        'product_limit'     => 100,
        'billing_period_bn' => '/ মাস',
        'billing_period_en' => '/ month',
        'is_popular'        => 0,
    ],
    [
        'id'                => 3,
        'slug'              => 'enterprise',
        'name_bn'           => 'এন্টারপ্রাইজ প্যাকেজ',
        'name_en'           => 'Enterprise Plan',
        'badge_bn'          => 'আনলিমিটেড স্কেল',
        'badge_en'          => 'Unlimited Scale',
        'price'             => 800,
        'product_limit'     => 0,
        'billing_period_bn' => '/ মাস',
        'billing_period_en' => '/ month',
        'is_popular'        => 0,
    ],
];

// 2. Fetch live data from database
try {
    $envPath = dirname(__DIR__) . '/.env';
    if (!class_exists(\App\Core\Env::class)) {
        require_once dirname(__DIR__) . '/app/Core/Env.php';
    }
    if (!class_exists(\App\Core\Database::class)) {
        require_once dirname(__DIR__) . '/app/Core/Database.php';
    }

    \App\Core\Env::load($envPath);
    $pdo = \App\Core\Database::getConnection();

    $stmt = $pdo->query("SELECT * FROM `packages` ORDER BY `id` ASC");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($rows)) {
        $packages = array_map('formatPkgData', $rows);
    } else {
        $packages = array_map('formatPkgData', $defaultRows);
    }
} catch (\Throwable $e) {
    // If DB is temporarily unreachable, return graceful fallback
    $packages = array_map('formatPkgData', $defaultRows);
}

echo json_encode([
    'success' => true,
    'count'   => count($packages),
    'data'    => $packages,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
