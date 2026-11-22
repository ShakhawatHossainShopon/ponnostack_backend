<?php

declare(strict_types=1);

/**
 * Customer Portal - Monthly Renewal Bill Submission API
 * 
 * Endpoint: POST /portal/api/pay-bill.php
 * Authenticated merchant submits their monthly renewal bKash payment details.
 */

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/auth.php';

$tenant = requirePortalAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'শুধুমাত্র POST মেথড গ্রহণযোগ্য।'], JSON_UNESCAPED_UNICODE);
    exit;
}

$raw = file_get_contents('php://input');
$input = json_decode($raw, true) ?? $_POST;

$amount = (int)($input['amount'] ?? $tenant['monthly_fee'] ?? 199);
$senderNumber = trim((string)($input['sender_number'] ?? ''));
$trxId = strtoupper(trim((string)($input['trx_id'] ?? '')));
$billingMonth = trim((string)($input['billing_month'] ?? ''));
$note = trim((string)($input['note'] ?? ''));

if (empty($billingMonth)) {
    $bnMonths = [
        'January' => 'জানুয়ারি', 'February' => 'ফেব্রুয়ারি', 'March' => 'মার্চ',
        'April' => 'এপ্রিল', 'May' => 'মে', 'June' => 'জুন',
        'July' => 'জুলাই', 'August' => 'আগস্ট', 'September' => 'সেপ্টেম্বর',
        'October' => 'অক্টোবর', 'November' => 'নভেম্বর', 'December' => 'ডিসেম্বর'
    ];
    $billingMonth = ($bnMonths[date('F')] ?? date('F')) . ' ' . date('Y');
}

if (empty($senderNumber) || strlen($senderNumber) < 10) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'যে বিকাশ নম্বর থেকে টাকা পাঠিয়েছেন তা উল্লেখ করুন।'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (empty($trxId) || strlen($trxId) < 6) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'সঠিক বিকাশ ট্রানজেকশন আইডি (TrxID) প্রদান করুন।'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $paymentModel = new \App\Models\TenantPayment();
    
    // Check if this TrxID has already been submitted
    $existing = (new \App\Core\Database())->getConnection()
        ->query("SELECT id FROM `tenant_payments` WHERE `trx_id` = " . (new \App\Core\Database())->getConnection()->quote($trxId))
        ->fetch();
    if ($existing) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'এই ট্রানজেকশন আইডি (TrxID) ইতিমধ্যে একবার ব্যবহার করা হয়েছে। অনুগ্রহ করে যাচাই করুন।'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $paymentId = $paymentModel->create([
        'tenant_id'      => $tenant['id'],
        'tenant_uuid'    => $tenant['uuid'],
        'store_name'     => $tenant['name'],
        'sender_number'  => $senderNumber,
        'trx_id'         => $trxId,
        'amount'         => $amount,
        'billing_month'  => $billingMonth,
        'payment_type'   => 'renewal',
        'payment_method' => 'bKash',
        'status'         => 'pending',
        'admin_note'     => !empty($note) ? "মার্চেন্ট নোট: {$note}" : 'কাস্টমার পোর্টাল হতে মাসিক রিনিউয়াল সাবমিশন',
    ]);

    $createdPayment = $paymentModel->find($paymentId);

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'আপনার মাসিক বিলের পেমেন্ট তথ্য সফলভাবে জমা দেওয়া হয়েছে! অ্যাডমিন যাচাই করার পর স্বয়ংক্রিয়ভাবে অনুমোদিত হবে।',
        'data'    => $createdPayment
    ], JSON_UNESCAPED_UNICODE);
} catch (\Throwable $e) {
    error_log('Portal pay-bill error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'পেমেন্ট তথ্য সংরক্ষণ করতে সার্ভারে সমস্যা হয়েছে। অনুগ্রহ করে পুনরায় চেষ্টা করুন।'
    ], JSON_UNESCAPED_UNICODE);
}
