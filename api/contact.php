<?php

declare(strict_types=1);

/**
 * Ponnostack Platform - Contact Form Submission API
 * Public Endpoint: POST /api/contact.php
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

$root = dirname(__DIR__);
require_once $root . '/backend/app/Core/Autoloader.php';
\App\Core\Autoloader::register();

$envPath = file_exists($root . '/.env') ? $root . '/.env' : $root . '/backend/.env';
\App\Core\Env::load($envPath);

// Parse input (supports JSON & Form-urlencoded)
$input = $_POST;
$rawInput = file_get_contents('php://input');
if (empty($input) && !empty($rawInput)) {
    $json = json_decode($rawInput, true);
    if (is_array($json)) {
        $input = $json;
    }
}

$name = trim((string)($input['name'] ?? ''));
$phone = trim((string)($input['phone'] ?? ''));
$email = trim((string)($input['email'] ?? ''));
$subject = trim((string)($input['subject'] ?? ''));
$message = trim((string)($input['message'] ?? ''));

// 1. Validate Name
if (empty($name) || mb_strlen($name) < 2) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'অনুগ্রহ করে আপনার নাম প্রদান করুন (কমপক্ষে ২ অক্ষর)।'], JSON_UNESCAPED_UNICODE);
    exit;
}

// 2. Validate Phone
if (empty($phone) || strlen($phone) < 10) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'অনুগ্রহ করে একটি সঠিক মোবাইল নম্বর প্রদান করুন।'], JSON_UNESCAPED_UNICODE);
    exit;
}

// 3. Validate Email (Optional, but if given must be valid)
if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'সঠিক ইমেইল ঠিকানা প্রদান করুন অথবা এটি খালি রাখুন।'], JSON_UNESCAPED_UNICODE);
    exit;
}

// 4. Validate Message
if (empty($message) || mb_strlen($message) < 5) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'অনুগ্রহ করে আপনার বার্তার বিস্তারিত লিখুন (কমপক্ষে ৫ অক্ষর)।'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $msgModel = new \App\Models\ContactMessage();
    $insertedId = $msgModel->create([
        'name'       => $name,
        'phone'      => $phone,
        'email'      => $email,
        'subject'    => $subject ?: 'ওয়েবসাইট কন্টাক্ট ইনকোয়ারি',
        'message'    => $message,
        'status'     => 'unread',
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
    ]);

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'ধন্যবাদ! আপনার বার্তাটি সফলভাবে সংরক্ষিত হয়েছে। আমরা শীঘ্রই আপনার সাথে যোগাযোগ করব।',
        'data' => [
            'id' => $insertedId
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit;
} catch (\Throwable $e) {
    error_log('Contact form error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'বার্তাটি পাঠাতে সমস্যা হয়েছে। অনুগ্রহ করে আবার চেষ্টা করুন।'], JSON_UNESCAPED_UNICODE);
    exit;
}
