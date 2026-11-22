<?php

declare(strict_types=1);

/**
 * Ponnostack Platform - Storefront Registration & Onboarding API
 * 
 * Public Endpoint: POST /api/storefront/register or /api/storefront/register.php
 * Handles 3-step checkout store registration, logo file upload, UUID generation,
 * and saving tenant & payment records to database.
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed. Use POST.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// 1. Load Core & Database
$root = dirname(__DIR__, 2);
require_once $root . '/backend/app/Core/Autoloader.php';
\App\Core\Autoloader::register();

$envPath = file_exists($root . '/.env') ? $root . '/.env' : $root . '/backend/.env';
\App\Core\Env::load($envPath);

try {
    \App\Core\Migration::run();
} catch (\Throwable $e) {
    error_log('Migration check: ' . $e->getMessage());
}

use App\Models\Tenant;
use App\Models\TenantPayment;
use App\Models\Package;
use App\Core\Uploader;

try {
    $tenantModel = new Tenant();
    $paymentModel = new TenantPayment();
    $packageModel = new Package();

    // 2. Parse Incoming Payload (Supports FormData & JSON)
    $input = $_POST;
    $rawInput = file_get_contents('php://input');
    if (empty($input) && !empty($rawInput)) {
        $json = json_decode($rawInput, true);
        if (is_array($json)) {
            $input = $json;
        }
    }

    // 3. Validation
    $storeName     = trim((string)($input['store_name'] ?? ''));
    $rawSlug       = trim((string)($input['store_slug'] ?? ($input['slug'] ?? '')));
    $slug          = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $rawSlug), '-'));
    $domainChoice  = trim((string)($input['domain_choice'] ?? 'subdomain'));
    $customDomain  = trim((string)($input['custom_domain'] ?? ($input['domain'] ?? '')));
    $address       = trim((string)($input['address'] ?? ''));

    $ownerName     = trim((string)($input['owner_name'] ?? ''));
    $ownerEmail    = strtolower(trim((string)($input['owner_email'] ?? '')));
    $ownerPhone    = trim((string)($input['owner_phone'] ?? ''));
    $ownerPassword = (string)($input['owner_password'] ?? '');

    $packageSlug   = strtolower(trim((string)($input['package_slug'] ?? 'starter')));
    $packageName   = trim((string)($input['package_name'] ?? 'স্ট্যান্ডার্ড প্যাকেজ'));
    $amount        = isset($input['amount']) ? (int)$input['amount'] : 200;

    $senderNumber  = trim((string)($input['sender_number'] ?? ''));
    $trxId         = strtoupper(trim((string)($input['trx_id'] ?? '')));

    // Validation Checks
    if (empty($storeName) || mb_strlen($storeName) < 2) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'স্টোরের নাম আবশ্যক (কমপক্ষে ২ অক্ষর হতে হবে)।'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Check store name uniqueness
    if (!$tenantModel->isNameAvailable($storeName)) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => "এই নামে ('{$storeName}') ইতিমধ্যে একটি স্টোর নিবন্ধিত আছে। অনুগ্রহ করে অন্য নাম দিন।"], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (empty($slug) || strlen($slug) < 2) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'স্টোর স্লাগ আবশ্যক (ইংরেজি অক্ষর ও হাইফেন ব্যবহার করুন)।'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Check reserved slugs
    $reservedSlugs = ['api', 'admin', 'backend', 'auth', 'login', 'portal', 'dashboard', 'checkout', 'support', 'help', 'static', 'assets', 'www'];
    if (in_array($slug, $reservedSlugs, true)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => "এই স্লাগটি ('{$slug}') সিস্টেমের জন্য সংরক্ষিত। অনুগ্রহ করে অন্য স্লাগ নির্বাচন করুন।"], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Check slug uniqueness
    if (!$tenantModel->isSlugAvailable($slug)) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => "এই স্টোর স্লাগটি ('{$slug}') ইতিমধ্যে ব্যবহৃত হয়েছে। অনুগ্রহ করে অন্য একটি স্লাগ দিন।"], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($domainChoice === 'custom' && empty($customDomain)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'আপনি নিজস্ব কাস্টম ডোমেন অপশন নির্বাচন করেছেন। ডোমেনের নাম দিন।'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (empty($ownerName) || mb_strlen($ownerName) < 2) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'মালিকের পূর্ণ নাম দিন।'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (empty($ownerEmail) || !filter_var($ownerEmail, FILTER_VALIDATE_EMAIL)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'সঠিক ইমেইল ঠিকানা প্রদান করুন।'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Check owner email uniqueness
    if (!$tenantModel->isEmailAvailable($ownerEmail)) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => "স্টোরফ্রন্ট ইমেইল ('{$ownerEmail}') ইতিমধ্যে নিবন্ধিত আছে। অনুগ্রহ করে অন্য ইমেইল দিন বা লগইন করুন।"], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (empty($ownerPhone) || strlen($ownerPhone) < 10) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'সঠিক মোবাইল নম্বর প্রদান করুন।'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (empty($ownerPassword) || strlen($ownerPassword) < 4) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'পাসওয়ার্ড কমপক্ষে ৪ অক্ষরের হতে হবে।'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (empty($senderNumber)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'যে বিকাশ নম্বর থেকে টাকা পাঠিয়েছেন তা উল্লেখ করুন।'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (empty($trxId)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'বিকাশ ট্রানজেকশন আইডি (TrxID) প্রদান করুন।'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Logo validation (Mandatory)
    if (empty($_FILES['logo']) || !is_uploaded_file($_FILES['logo']['tmp_name'])) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'স্টোরের লোগো আপলোড করা আবশ্যক।'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 4. Fetch Live Package Information from DB
    $matchedPackage = $packageModel->findBySlug($packageSlug);
    $productLimit = 50;
    if ($matchedPackage) {
        $packageName  = $matchedPackage['name_bn'];
        $amount       = (int)$matchedPackage['price'];
        $productLimit = (int)$matchedPackage['product_limit'];
    }

    // 5. Handle Logo Upload
    $logoPath = null;
    if (!empty($_FILES['logo']) && is_uploaded_file($_FILES['logo']['tmp_name'])) {
        $uploadRes = Uploader::upload($_FILES['logo'], 'uploads/logos');
        if (!empty($uploadRes['success'])) {
            $logoPath = $uploadRes['relative_path'];
        } else {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'লোগো আপলোড ব্যর্থ হয়েছে: ' . ($uploadRes['message'] ?? 'অজানা সমস্যা')], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    // 6. Social Links parsing
    $socialLinks = [];
    if (!empty($input['social_links']) && is_array($input['social_links'])) {
        foreach ($input['social_links'] as $k => $v) {
            $cleanVal = trim((string)$v);
            if ($cleanVal !== '') {
                $socialLinks[$k] = $cleanVal;
            }
        }
    } else {
        // Check separate keys
        $socialKeys = ['facebook', 'whatsapp', 'instagram', 'youtube', 'tiktok'];
        foreach ($socialKeys as $sk) {
            if (!empty($input[$sk])) {
                $socialLinks[$sk] = trim((string)$input[$sk]);
            }
        }
    }

    // 7. Generate UUID & Insert into `tenants`
    $uuid = Tenant::generateUuidV4();

    $tenantData = [
        'uuid'                 => $uuid,
        'name'                 => $storeName,
        'slug'                 => $slug,
        'domain'               => ($domainChoice === 'custom' && !empty($customDomain)) ? preg_replace('#^https?://#', '', rtrim($customDomain, '/')) : null,
        'owner_name'           => $ownerName,
        'owner_email'          => $ownerEmail,
        'password'             => $ownerPassword,
        'owner_phone'          => $ownerPhone,
        'address'              => !empty($address) ? $address : null,
        'logo'                 => $logoPath,
        'banners'              => null,
        'currency'             => 'BDT',
        'monthly_fee'          => $amount,
        'package_name'         => $packageName,
        'package_slug'         => $packageSlug,
        'product_limit'        => $productLimit,
        'payment_method'       => 'bKash',
        'payment_sender'       => $senderNumber,
        'payment_trx_id'       => $trxId,
        'billing_day'          => 10,
        'social_links'         => $socialLinks,
        'template_id'          => 'template_1',
        'color_palette'        => 'emerald',
        'subscription_ends_at' => null, // Set on admin approval
        'status'               => 'payment_pending',
        'source'               => 'website',
    ];

    $tenantId = $tenantModel->create($tenantData);

    // 8. Insert into `tenant_payments`
    $bnMonths = [
        'January' => 'জানুয়ারি', 'February' => 'ফেব্রুয়ারি', 'March' => 'মার্চ',
        'April' => 'এপ্রিল', 'May' => 'মে', 'June' => 'জুন',
        'July' => 'জুলাই', 'August' => 'আগস্ট', 'September' => 'সেপ্টেম্বর',
        'October' => 'অক্টোবর', 'November' => 'নভেম্বর', 'December' => 'ডিসেম্বর'
    ];
    $currentBnMonth = ($bnMonths[date('F')] ?? date('F')) . ' ' . date('Y');

    $paymentModel->create([
        'tenant_id'      => $tenantId,
        'tenant_uuid'    => $uuid,
        'store_name'     => $storeName,
        'sender_number'  => $senderNumber,
        'trx_id'         => $trxId,
        'amount'         => $amount,
        'billing_month'  => $currentBnMonth,
        'payment_type'   => 'initial',
        'payment_method' => 'bKash',
        'status'         => 'pending',
    ]);

    $createdTenant = $tenantModel->find($tenantId);

    // 9. Send Welcome / Credential Email (Graceful fallback if SMTP unconfigured)
    try {
        \App\Core\Mailer::sendWelcomeEmail($createdTenant, $ownerPassword);
    } catch (\Throwable $mErr) {
        error_log('Welcome email dispatch warning: ' . $mErr->getMessage());
    }

    // 10. Generate Session & 30-Day Auto-Login Token
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $authToken = bin2hex(random_bytes(32));
    $_SESSION['customer_uuid'] = $createdTenant['uuid'];
    $_SESSION['tenant_uuid'] = $createdTenant['uuid'];
    $_SESSION['tenant_auth_token'] = $authToken;
    $_SESSION['tenant_email'] = $createdTenant['owner_email'];
    $_SESSION['tenant_name'] = $createdTenant['name'];

    // 30-Day Cookies
    setcookie('ponnostack_tenant_uuid', $createdTenant['uuid'], time() + (86400 * 30), '/');
    setcookie('ponnostack_tenant_token', $authToken, time() + (86400 * 30), '/');

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'অভিনন্দন! আপনার স্টোরের অর্ডার সফলভাবে সম্পন্ন হয়েছে।',
        'data'    => [
            'tenant' => [
                'id'             => $createdTenant['id'],
                'uuid'           => $createdTenant['uuid'],
                'name'           => $createdTenant['name'],
                'slug'           => $createdTenant['slug'],
                'domain'         => $createdTenant['domain'],
                'subdomain_url'  => $createdTenant['subdomain_url'],
                'owner_name'     => $createdTenant['owner_name'],
                'owner_email'    => $createdTenant['owner_email'],
                'package_name'   => $createdTenant['package_name'],
                'monthly_fee'    => $createdTenant['monthly_fee'],
                'payment_trx_id' => $createdTenant['payment_trx_id'],
                'status'         => $createdTenant['status'],
                'logo_url'       => $createdTenant['logo_url'],
            ],
            'auth' => [
                'token' => $authToken,
                'uuid'  => $createdTenant['uuid'],
                'email' => $createdTenant['owner_email'],
                'name'  => $createdTenant['name'],
                'slug'  => $createdTenant['slug'],
            ]
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} catch (\Throwable $e) {
    error_log('Store registration error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'সার্ভারে ত্রুটি ঘটেছে। অনুগ্রহ করে কিছুক্ষণ পর পুনরায় চেষ্টা করুন।',
        'error'   => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
