<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Uploader;
use App\Models\Tenant;
use App\Models\TenantPayment;

/**
 * TenantController
 * 
 * Admin API Controller for managing client stores, approval workflows,
 * subscription extensions, monthly renewals, and payment histories.
 */
class TenantController extends Controller
{
    private Tenant $tenantModel;
    private TenantPayment $paymentModel;

    public function __construct()
    {
        $this->tenantModel = new Tenant();
        $this->paymentModel = new TenantPayment();

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Session Authentication Guard
     */
    private function checkAuth(): bool
    {
        if (empty($_SESSION['admin_user'])) {
            $this->error('অননুমোদিত অ্যাক্সেস। অনুগ্রহ করে লগইন করুন।', 401);
            return false;
        }
        return true;
    }

    /**
     * GET /api/admin/tenants
     * List all tenants with stats & optional filter/search
     */
    public function index(): void
    {
        if (!$this->checkAuth()) {
            return;
        }

        $status = $_GET['status'] ?? null;
        $search = $_GET['search'] ?? null;

        $tenants = $this->tenantModel->getAll($status, $search);
        $stats = $this->tenantModel->getStats();

        $this->success([
            'tenants' => $tenants,
            'stats'   => $stats,
        ], 'স্টোরফ্রন্ট তালিকা সফলভাবে পাওয়া গেছে।');
    }

    /**
     * GET /api/admin/tenants/{id}
     * Get single tenant details + payment ledger
     */
    public function show(string|int $id): void
    {
        if (!$this->checkAuth()) {
            return;
        }

        $tenantId = (int)$id;
        $tenant = $this->tenantModel->find($tenantId);

        if (!$tenant) {
            $this->error('স্টোরটি পাওয়া যায়নি।', 404);
            return;
        }

        $payments = $this->paymentModel->getByTenantId($tenantId);

        $this->success([
            'tenant'   => $tenant,
            'payments' => $payments,
        ], 'স্টোরের বিবরণ সফলভাবে লোড হয়েছে।');
    }

    /**
     * POST /api/admin/tenants
     * Manual Store Creation by Admin
     */
    public function store(): void
    {
        if (!$this->checkAuth()) {
            return;
        }

        $data = !empty($_POST) ? $_POST : (json_decode(file_get_contents('php://input'), true) ?? []);

        $name = trim((string)($data['name'] ?? ''));
        $slug = strtolower(trim((string)($data['slug'] ?? '')));
        $ownerName = trim((string)($data['owner_name'] ?? ''));
        $ownerPhone = trim((string)($data['owner_phone'] ?? ''));
        $ownerEmail = strtolower(trim((string)($data['owner_email'] ?? '')));
        $password = (string)($data['password'] ?? '123456');

        if (empty($name) || empty($slug) || empty($ownerName) || empty($ownerPhone) || empty($ownerEmail)) {
            $this->error('দয়া করে স্টোরের নাম, স্লাগ, মালিকের নাম, ফোন ও ইমেইল প্রদান করুন।', 422);
            return;
        }

        if (!$this->tenantModel->isSlugAvailable($slug)) {
            $this->error('এই স্লাগটি ইতিমধ্যে ব্যবহৃত হয়েছে। অন্য স্লাগ চেষ্টা করুন।', 422);
            return;
        }

        if (!$this->tenantModel->isEmailAvailable($ownerEmail)) {
            $this->error('এই ইমেইল ঠিকানা দিয়ে ইতিমধ্যে একটি স্টোর নিবন্ধিত আছে।', 422);
            return;
        }

        // Handle optional logo upload
        $logoPath = null;
        if (!empty($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = Uploader::upload($_FILES['logo'], 'uploads/logos');
            if ($uploadResult['success']) {
                $logoPath = $uploadResult['path'];
            }
        }

        $billingDay = (int)($data['billing_day'] ?? 10);
        $status = in_array($data['status'] ?? '', ['active', 'pending', 'suspended'], true) ? $data['status'] : 'active';
        $domain = !empty($data['domain']) ? trim((string)$data['domain']) : null;
        $adminUrl = !empty($data['admin_url']) ? trim((string)$data['admin_url']) : null;

        // Calculate subscription_ends_at if active
        $subscriptionEndsAt = null;
        if ($status === 'active') {
            $subscriptionEndsAt = !empty($data['subscription_ends_at'])
                ? date('Y-m-d 23:59:59', strtotime((string)$data['subscription_ends_at']))
                : Tenant::calculateNextBillingDate($billingDay);
        }

        $monthlyFee = (int)($data['monthly_fee'] ?? 199);

        $payload = [
            'name'                 => $name,
            'slug'                 => $slug,
            'domain'               => $domain,
            'admin_url'            => $adminUrl,
            'owner_name'           => $ownerName,
            'owner_email'          => $ownerEmail,
            'owner_phone'          => $ownerPhone,
            'password'             => $password,
            'address'              => $data['address'] ?? null,
            'logo'                 => $logoPath,
            'monthly_fee'          => $monthlyFee,
            'package_name'         => $data['package_name'] ?? 'স্ট্যান্ডার্ড প্যাকেজ',
            'package_slug'         => $data['package_slug'] ?? 'starter',
            'product_limit'        => (int)($data['product_limit'] ?? 50),
            'payment_method'       => $data['payment_method'] ?? 'bKash',
            'payment_sender'       => $data['payment_sender'] ?? $ownerPhone,
            'payment_trx_id'       => $data['payment_trx_id'] ?? 'DIRECT_ADMIN',
            'billing_day'          => $billingDay,
            'subscription_ends_at' => $subscriptionEndsAt,
            'status'               => $status,
            'source'               => 'admin',
        ];

        $tenantId = $this->tenantModel->create($payload);

        // Record initial payment record
        $this->paymentModel->create([
            'tenant_id'      => $tenantId,
            'tenant_uuid'    => $payload['uuid'] ?? '',
            'store_name'     => $name,
            'sender_number'  => $payload['payment_sender'],
            'trx_id'         => $payload['payment_trx_id'],
            'amount'         => $monthlyFee,
            'billing_month'  => date('F Y'),
            'payment_type'   => 'initial',
            'payment_method' => $payload['payment_method'],
            'status'         => ($status === 'active') ? 'approved' : 'pending',
            'admin_note'     => 'অ্যাডমিন কর্তৃক সরাসরি অনবোর্ড ও তৈরি',
        ]);

        $created = $this->tenantModel->find($tenantId);
        $this->success($created, 'নতুন স্টোরফ্রন্ট সফলভাবে তৈরি হয়েছে!', 201);
    }

    /**
     * POST /api/admin/tenants/{id}/approve
     * Approve Store: Assign domain, admin URL, billing day, and activate
     */
    /**
     * POST /api/admin/tenants/{id}/approve-payment
     * Stage 1: Approve initial bKash payment, transitions store to setup_pending
     */
    public function approvePayment(string|int $id): void
    {
        if (!$this->checkAuth()) {
            return;
        }

        $tenantId = (int)$id;
        $tenant = $this->tenantModel->find($tenantId);

        if (!$tenant) {
            $this->error('স্টোরটি পাওয়া যায়নি।', 404);
            return;
        }

        $this->tenantModel->approvePayment($tenantId);
        $updated = $this->tenantModel->find($tenantId);

        $this->success($updated, 'পেমেন্ট সফলভাবে অনুমোদিত হয়েছে! স্টোরটি এখন সেটআপ পেন্ডিং ট্যাবে স্থানান্তরিত হয়েছে।');
    }

    /**
     * POST /api/admin/tenants/{id}/setup
     * Stage 2: Configure Live URL & Client Admin URL, then Activate
     */
    public function setupStore(string|int $id): void
    {
        if (!$this->checkAuth()) {
            return;
        }

        $tenantId = (int)$id;
        $tenant = $this->tenantModel->find($tenantId);

        if (!$tenant) {
            $this->error('স্টোরটি পাওয়া যায়নি।', 404);
            return;
        }

        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true) ?? $_POST;

        $domain = trim((string)($data['domain'] ?? ''));
        if (empty($domain)) {
            $this->error('লাইভ স্টোরফ্রন্ট URL (ডোমেইন / ভার্সেল লিংক) প্রদান করা আবশ্যক।', 422);
            return;
        }

        $adminUrl = trim((string)($data['admin_url'] ?? ''));

        $this->tenantModel->setupStore($tenantId, [
            'domain'    => $domain,
            'admin_url' => $adminUrl,
        ]);

        $updated = $this->tenantModel->find($tenantId);
        $this->success($updated, 'স্টোরফ্রন্ট ইউআরএল সফলভাবে সেটআপ এবং স্টোরটি লাইভ ও সক্রিয় (Active) করা হয়েছে!');
    }

    /**
     * POST /api/admin/tenants/{id}/approve
     * Legacy/Fallback Store Approval
     */
    public function approve(string|int $id): void
    {
        if (!$this->checkAuth()) {
            return;
        }

        $tenantId = (int)$id;
        $tenant = $this->tenantModel->find($tenantId);

        if (!$tenant) {
            $this->error('স্টোরটি পাওয়া যায়নি।', 404);
            return;
        }

        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true) ?? $_POST;

        $domain = trim((string)($data['domain'] ?? ''));
        if (empty($domain)) {
            $this->error('লাইভ স্টোরফ্রন্ট ইউআরএল (ডোমেইন / ভার্সেল লিংক) প্রদান করা আবশ্যক।', 422);
            return;
        }

        $adminUrl = trim((string)($data['admin_url'] ?? ''));
        $billingDay = (int)($data['billing_day'] ?? date('j'));
        $subscriptionEndsAt = !empty($data['subscription_ends_at']) ? (string)$data['subscription_ends_at'] : null;

        $this->tenantModel->approveStore($tenantId, [
            'domain'               => $domain,
            'admin_url'            => $adminUrl,
            'billing_day'          => $billingDay,
            'subscription_ends_at' => $subscriptionEndsAt,
        ]);

        $updated = $this->tenantModel->find($tenantId);
        $this->success($updated, 'স্টোরটি সফলভাবে অনুমোদিত ও সক্রিয় (Active) করা হয়েছে!');
    }

    /**
     * POST /api/admin/tenants/{id}/extend
     * Stage 3: Extend Grace Period (e.g. +2 days, +5 days, +7 days)
     * billing_day is strictly preserved!
     */
    public function extend(string|int $id): void
    {
        if (!$this->checkAuth()) {
            return;
        }

        $tenantId = (int)$id;
        $tenant = $this->tenantModel->find($tenantId);

        if (!$tenant) {
            $this->error('স্টোরটি পাওয়া যায়নি।', 404);
            return;
        }

        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true) ?? $_POST;

        $days = (int)($data['days'] ?? 0);
        $note = !empty($data['note']) ? (string)$data['note'] : null;

        if ($days <= 0) {
            $this->error('বাড়তি গ্রেস পিরিয়ডের দিনের সংখ্যা (যেমন: ২, ৫ বা ৭ দিন) নির্বাচন করুন।', 422);
            return;
        }

        $this->tenantModel->extendGracePeriod($tenantId, $days, $note);

        $updated = $this->tenantModel->find($tenantId);
        $this->success($updated, "গ্রেস পিরিয়ড সফলভাবে {$days} দিন বৃদ্ধি করা হয়েছে! (স্থায়ী বিলিং ডেট অপরিবর্তিত রয়েছে)");
    }

    /**
     * POST /api/admin/tenants/{id}/status
     * Toggle status: active, suspended, inactive
     */
    public function toggleStatus(string|int $id): void
    {
        if (!$this->checkAuth()) {
            return;
        }

        $tenantId = (int)$id;
        $tenant = $this->tenantModel->find($tenantId);

        if (!$tenant) {
            $this->error('স্টোরটি পাওয়া যায়নি।', 404);
            return;
        }

        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true) ?? $_POST;

        $status = (string)($data['status'] ?? '');
        if (!in_array($status, ['active', 'suspended', 'inactive'], true)) {
            $this->error('অবৈধ স্ট্যাটাস নির্বাচন করা হয়েছে।', 422);
            return;
        }

        $this->tenantModel->updateStatus($tenantId, $status);
        $updated = $this->tenantModel->find($tenantId);

        $msg = match ($status) {
            'active'    => 'স্টোরটি সক্রিয় (Active) করা হয়েছে।',
            'suspended' => 'স্টোরটি সাময়িকভাবে স্থগিত (Suspended) করা হয়েছে।',
            'inactive'  => 'স্টোরটি নিষ্ক্রিয় (Inactive) করা হয়েছে।',
            default     => 'স্ট্যাটাস আপডেট হয়েছে।',
        };

        $this->success($updated, $msg);
    }

    /**
     * POST /api/admin/tenants/{id}/payments
     * Record a monthly renewal payment and advance subscription date
     */
    public function recordPayment(string|int $id): void
    {
        if (!$this->checkAuth()) {
            return;
        }

        $tenantId = (int)$id;
        $tenant = $this->tenantModel->find($tenantId);

        if (!$tenant) {
            $this->error('স্টোরটি পাওয়া যায়নি।', 404);
            return;
        }

        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true) ?? $_POST;

        $amount = (int)($data['amount'] ?? $tenant['monthly_fee']);
        $trxId = strtoupper(trim((string)($data['trx_id'] ?? '')));
        $senderNumber = trim((string)($data['sender_number'] ?? $tenant['owner_phone']));
        $paymentMethod = (string)($data['payment_method'] ?? 'bKash');
        $billingMonth = trim((string)($data['billing_month'] ?? date('F Y')));
        $nextDueDate = !empty($data['next_due_date']) ? (string)$data['next_due_date'] : null;
        $adminNote = (string)($data['admin_note'] ?? 'মাসিক সাবস্ক্রিপশন বিল কালেকশন');

        if (empty($trxId)) {
            $this->error('TrxID বা লেনদেন নম্বর দেওয়া আবশ্যক।', 422);
            return;
        }

        $this->tenantModel->recordRenewalPayment($tenantId, [
            'amount'         => $amount,
            'trx_id'         => $trxId,
            'sender_number'  => $senderNumber,
            'payment_method' => $paymentMethod,
            'billing_month'  => $billingMonth,
            'next_due_date'  => $nextDueDate,
            'admin_note'     => $adminNote,
        ]);

        $updated = $this->tenantModel->find($tenantId);
        $this->success($updated, 'মাসিক বিল সফলভাবে লিপিবদ্ধ হয়েছে এবং সাবস্ক্রিপশন মেয়াদ বৃদ্ধি করা হয়েছে!');
    }

    /**
     * GET /api/admin/tenants/{id}/payments
     * Get payment history for modal
     */
    public function getPayments(string|int $id): void
    {
        if (!$this->checkAuth()) {
            return;
        }

        $tenantId = (int)$id;
        $payments = $this->paymentModel->getByTenantId($tenantId);
        $this->success($payments, 'পেমেন্ট হিস্ট্রি সফলভাবে লোড হয়েছে।');
    }

    /**
     * DELETE /api/admin/tenants/{id}
     */
    public function destroy(string|int $id): void
    {
        if (!$this->checkAuth()) {
            return;
        }

        $tenantId = (int)$id;
        $tenant = $this->tenantModel->find($tenantId);

        if (!$tenant) {
            $this->error('স্টোরটি পাওয়া যায়নি।', 404);
            return;
        }

        $this->tenantModel->delete($tenantId);
        $this->success(null, 'স্টোরফ্রন্টটি সফলভাবে মুছে ফেলা হয়েছে।');
    }

    /**
     * GET /api/admin/payments
     */
    public function getAllPayments(): void
    {
        if (!$this->checkAuth()) {
            return;
        }

        $status = $_GET['status'] ?? null;
        $type = $_GET['type'] ?? null;
        $search = $_GET['search'] ?? null;

        $payments = $this->paymentModel->getAll($status, $type, $search);
        $stats = $this->paymentModel->getBillingStats();

        $this->success([
            'payments' => $payments,
            'stats'    => $stats,
        ], 'পেমেন্ট ও ইনভয়েস তালিকা লোড হয়েছে।');
    }

    /**
     * POST /api/admin/payments/{id}/approve
     */
    public function approvePaymentRecord(string|int $id): void
    {
        if (!$this->checkAuth()) {
            return;
        }

        $paymentId = (int)$id;
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true) ?? $_POST;
        $adminNote = (string)($data['admin_note'] ?? 'অ্যাডমিন কর্তৃক অনুমোদিত');

        $success = $this->paymentModel->approveRenewal($paymentId, $adminNote);
        if ($success) {
            $payment = $this->paymentModel->find($paymentId);
            $this->success($payment, 'পেমেন্ট অনুমোদিত হয়েছে এবং স্টোরের সাবস্ক্রিপশন মেয়াদ বৃদ্ধি করা হয়েছে!');
        } else {
            $this->error('পেমেন্ট অনুমোদন করা যায়নি।', 400);
        }
    }

    /**
     * DELETE /api/admin/payments/{id}
     */
    public function deletePaymentRecord(string|int $id): void
    {
        if (!$this->checkAuth()) {
            return;
        }

        $paymentId = (int)$id;
        $success = $this->paymentModel->delete($paymentId);
        if ($success) {
            $this->success(null, 'পেমেন্ট রেকর্ড সফলভাবে মুছে ফেলা হয়েছে।');
        } else {
            $this->error('পেমেন্ট রেকর্ড মুছা সম্ভব হয়নি।', 400);
        }
    }

    /**
     * GET /api/admin/payments/{id}/invoice
     */
    public function getInvoice(string|int $id): void
    {
        if (!$this->checkAuth()) {
            return;
        }

        $payment = is_numeric($id) ? $this->paymentModel->find((int)$id) : $this->paymentModel->findByInvoiceNo((string)$id);
        if (!$payment) {
            $this->error('ইনভয়েস পাওয়া যায়নি।', 404);
            return;
        }

        $this->success($payment, 'ইনভয়েস বিবরণ লোড হয়েছে।');
    }

    /**
     * POST /api/admin/payments/manual
     */
    public function recordManualBill(): void
    {
        if (!$this->checkAuth()) {
            return;
        }

        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true) ?? $_POST;

        $tenantId = (int)($data['tenant_id'] ?? 0);
        $tenant = $this->tenantModel->find($tenantId);
        if (!$tenant) {
            $this->error('স্টোরফ্রন্ট নির্বাচন করুন।', 422);
            return;
        }

        $amount = (int)($data['amount'] ?? $tenant['monthly_fee']);
        $trxId = strtoupper(trim((string)($data['trx_id'] ?? 'OFFLINE_' . time())));
        $senderNumber = trim((string)($data['sender_number'] ?? $tenant['owner_phone'] ?? 'DIRECT_CASH'));
        $billingMonth = trim((string)($data['billing_month'] ?? date('F Y')));
        $adminNote = trim((string)($data['admin_note'] ?? 'অ্যাডমিন কর্তৃক অফলাইন বিল গ্রহণ'));

        $paymentId = $this->paymentModel->create([
            'tenant_id'      => $tenant['id'],
            'tenant_uuid'    => $tenant['uuid'],
            'store_name'     => $tenant['name'],
            'sender_number'  => $senderNumber,
            'trx_id'         => $trxId,
            'amount'         => $amount,
            'billing_month'  => $billingMonth,
            'payment_type'   => 'renewal',
            'payment_method' => (string)($data['payment_method'] ?? 'Cash'),
            'status'         => 'approved',
            'admin_note'     => $adminNote,
        ]);

        $this->paymentModel->approveRenewal($paymentId, $adminNote);
        $created = $this->paymentModel->find($paymentId);

        $this->success($created, 'অফলাইন বিল সফলভাবে সংরক্ষিত ও ইনভয়েস তৈরি হয়েছে!', 201);
    }

    /**
     * Public Storefront API Gateway
     * GET /api/storefront/{uuid}
     */
    public function getStorefront(string|int $uuid): void
    {
        $identifier = trim((string)$uuid);
        if (empty($identifier)) {
            $this->error('Store UUID প্রদান করা আবশ্যক।', 400);
            return;
        }

        $store = $this->tenantModel->findByUuid($identifier);
        if (!$store) {
            // Also fallback to search by slug if uuid lookup misses
            $store = $this->tenantModel->findBySlug($identifier);
        }

        if (!$store) {
            $this->error('প্রদত্ত UUID অনুযায়ী কোনো স্টোরফ্রন্ট খুঁজে পাওয়া যায়নি।', 404);
            return;
        }

        // Clean internal/sensitive fields for public consumption
        unset($store['password'], $store['admin_password'], $store['admin_note']);

        // Dynamically resolve package product limit from packages table
        if (!empty($store['package_slug'])) {
            $packageModel = new \App\Models\Package();
            $pkg = $packageModel->findBySlug($store['package_slug']);
            if ($pkg && isset($pkg['product_limit'])) {
                $store['product_limit'] = (int)$pkg['product_limit'];
            }
        }

        $this->success($store, 'স্টোরফ্রন্ট তথ্য সফলভাবে লোড হয়েছে।');
    }

    /**
     * Store Owner Admin Login endpoint for child storefronts
     * Authenticates by email and password (or checkout TrxID)
     */
    public function storefrontLogin(): void
    {
        $raw = file_get_contents('php://input');
        $input = json_decode($raw ?: '[]', true) ?? $_POST;

        $uuid = trim((string)($input['store_uuid'] ?? ($_SERVER['HTTP_X_STORE_UUID'] ?? '')));
        $email = strtolower(trim((string)($input['email'] ?? '')));
        $password = (string)($input['password'] ?? '');
        $trxId = trim((string)($input['trx_id'] ?? ''));

        if (empty($email)) {
            $this->error('ইমেইল ঠিকানা প্রদান করুন।', 422);
            return;
        }

        if (empty($password) && empty($trxId)) {
            $this->error('পাসওয়ার্ড বা TrxID প্রদান করুন।', 422);
            return;
        }

        // Find tenant by UUID or by Email
        $tenant = null;
        if (!empty($uuid)) {
            $tenant = $this->tenantModel->findByUuid($uuid);
        }
        if (!$tenant) {
            $tenant = $this->tenantModel->findByEmail($email);
        }

        if (!$tenant || strtolower($tenant['owner_email']) !== $email) {
            $this->error('ইমেইল অথবা পাসওয়ার্ড সঠিক নয়।', 401);
            return;
        }

        // Authenticate password or TrxID
        $validPassword = !empty($tenant['password']) && password_verify($password, $tenant['password']);
        $validTrx = (!empty($tenant['payment_trx_id']) && (
            (!empty($password) && strcasecmp($password, $tenant['payment_trx_id']) === 0) ||
            (!empty($trxId) && strcasecmp($trxId, $tenant['payment_trx_id']) === 0)
        ));

        if (!$validPassword && !$validTrx) {
            $this->error('ইমেইল অথবা পাসওয়ার্ড সঠিক নয়।', 401);
            return;
        }

        $token = bin2hex(random_bytes(32));

        // Format clean tenant data for client
        $storeData = $this->tenantModel->formatRow($tenant);
        unset($storeData['password'], $storeData['admin_password'], $storeData['admin_note']);

        $this->success([
            'token' => $token,
            'tenant' => $storeData,
        ], 'লগইন সফল হয়েছে!');
    }

    /**
     * GET /api/tenants/{uuid}/profile
     * Fetch store profile for client dashboard
     */
    public function profile(string|int $uuid): void
    {
        $identifier = trim((string)$uuid);
        $tenant = $this->tenantModel->findByUuid($identifier);
        if (!$tenant) {
            $tenant = $this->tenantModel->findBySlug($identifier);
        }
        if (!$tenant) {
            $this->error('Store profile not found', 404);
            return;
        }

        $formatted = $this->tenantModel->formatRow($tenant);
        unset($formatted['password'], $formatted['admin_password'], $formatted['admin_note']);
        $this->success($formatted, 'Store profile retrieved');
    }

    /**
     * PUT /api/tenants/{uuid}/profile
     * Update store information from client dashboard (template, palette, etc.)
     */
    public function updateProfile(string|int $uuid): void
    {
        $identifier = trim((string)$uuid);
        $tenant = $this->tenantModel->findByUuid($identifier);
        if (!$tenant) {
            $tenant = $this->tenantModel->findBySlug($identifier);
        }
        if (!$tenant) {
            $this->error('Store not found', 404);
            return;
        }

        $raw = file_get_contents('php://input');
        $input = json_decode($raw ?: '[]', true) ?? $_POST;

        $allowedToUpdate = ['name', 'slug', 'owner_phone', 'address', 'social_links', 'template_id', 'color_palette'];
        if (!empty($input['password'])) {
            $allowedToUpdate[] = 'password';
        }

        $updateData = [];
        foreach ($allowedToUpdate as $key) {
            if (array_key_exists($key, $input)) {
                $val = $input[$key];
                if ($key === 'social_links') {
                    if (is_string($val)) {
                        $val = json_decode($val, true) ?: [];
                    }
                    if (is_array($val)) {
                        $cleanSocials = [];
                        foreach (['facebook', 'instagram', 'whatsapp', 'youtube'] as $network) {
                            $cleanSocials[$network] = isset($val[$network]) ? trim((string)$val[$network]) : '';
                        }
                        $val = $cleanSocials;
                    }
                }
                $updateData[$key] = $val;
            }
        }

        $this->tenantModel->update((int)$tenant['id'], $updateData);
        $updated = $this->tenantModel->find((int)$tenant['id']);
        $formatted = $updated ?: [];
        unset($formatted['password'], $formatted['admin_password'], $formatted['admin_note']);

        $this->success($formatted, 'Store profile updated successfully');
    }

    /**
     * POST /api/tenants/{uuid}/banners
     * Upload up to 5 uncompressed high-resolution banners (max 5MB each)
     */
    public function uploadBanner(string|int $uuid): void
    {
        $identifier = trim((string)$uuid);
        $tenant = $this->tenantModel->findByUuid($identifier);
        if (!$tenant) {
            $tenant = $this->tenantModel->findBySlug($identifier);
        }

        if (!$tenant) {
            $this->error('প্রদত্ত UUID অনুযায়ী কোনো স্টোরফ্রন্ট পাওয়া যায়নি।', 404);
            return;
        }

        $currentBanners = is_array($tenant['banners'] ?? null) ? $tenant['banners'] : [];
        if (count($currentBanners) >= 5) {
            $this->error('সর্বোচ্চ ৫টি ব্যানার আপলোড করা যাবে। নতুন ব্যানার যোগ করতে আগে পুরনো ব্যানার মুছুন।', 422);
            return;
        }

        // 1. Check for Base64 JSON upload (100% immune to shared hosting WAF & ModSecurity multipart file blocks)
        $raw = preg_replace('/^\xEF\xBB\xBF/', '', (string)file_get_contents('php://input'));
        $input = json_decode($raw ?: '[]', true) ?? $_POST;
        $base64List = $input['banners_base64'] ?? $input['banners'] ?? $input['banner_base64'] ?? null;

        if (!empty($base64List)) {
            if (!is_array($base64List)) {
                $base64List = [$base64List];
            }
            $remainingSlots = 5 - count($currentBanners);
            $base64List = array_slice($base64List, 0, $remainingSlots);

            foreach ($base64List as $b64Item) {
                if (empty($b64Item) || !is_string($b64Item)) continue;
                $upload = Uploader::uploadBase64($b64Item, 'uploads/banners', 5 * 1024 * 1024, false);
                if (!$upload['success']) {
                    $this->error($upload['error'], 422);
                    return;
                }
                $currentBanners[] = $upload['relative_path'];
            }

            $this->tenantModel->updateBanners($tenant['uuid'], $currentBanners);
            $updated = $this->tenantModel->findByUuid($tenant['uuid']);
            unset($updated['password'], $updated['admin_password'], $updated['admin_note']);

            $this->success($updated, 'ব্যানার সফলভাবে আপলোড হয়েছে।');
            return;
        }

        // 2. Fallback: Standard Multipart $_FILES upload
        $filesToProcess = [];
        $rawFiles = $_FILES['banners'] ?? $_FILES['banner'] ?? null;

        if (!$rawFiles || empty($rawFiles['name'])) {
            $this->error('কোনো ব্যানার ফাইল নির্বাচন করা হয়নি।', 400);
            return;
        }

        if (is_array($rawFiles['name'])) {
            foreach ($rawFiles['name'] as $idx => $name) {
                if (($rawFiles['error'][$idx] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                    $filesToProcess[] = [
                        'name'     => $rawFiles['name'][$idx],
                        'type'     => $rawFiles['type'][$idx],
                        'tmp_name' => $rawFiles['tmp_name'][$idx],
                        'error'    => $rawFiles['error'][$idx],
                        'size'     => $rawFiles['size'][$idx],
                    ];
                }
            }
        } else {
            if ($rawFiles['error'] === UPLOAD_ERR_OK) {
                $filesToProcess[] = $rawFiles;
            }
        }

        if (empty($filesToProcess)) {
            $this->error('কোনো বৈধ ফাইল পাওয়া যায়নি অথবা ফাইল আপলোডে সমস্যা হয়েছে।', 400);
            return;
        }

        $remainingSlots = 5 - count($currentBanners);
        $filesToProcess = array_slice($filesToProcess, 0, $remainingSlots);
        $maxBannerBytes = 5 * 1024 * 1024; // Strict 5MB limit

        foreach ($filesToProcess as $fileItem) {
            if ($fileItem['size'] > $maxBannerBytes) {
                $this->error("ব্যানারের সাইজ সর্বোচ্চ ৫MB হতে পারবে। '{$fileItem['name']}' ফাইলটি ৫MB এর চেয়ে বড়।", 422);
                return;
            }

            $upload = Uploader::upload(
                $fileItem,
                'uploads/banners',
                Uploader::IMAGE_TYPES,
                $maxBannerBytes,
                false // Uncompressed raw quality
            );

            if (!$upload['success']) {
                $this->error($upload['error'], 422);
                return;
            }

            $currentBanners[] = $upload['relative_path'];
        }

        $this->tenantModel->updateBanners($tenant['uuid'], $currentBanners);
        $updated = $this->tenantModel->findByUuid($tenant['uuid']);
        unset($updated['password'], $updated['admin_password'], $updated['admin_note']);

        $this->success($updated, 'ব্যানার সফলভাবে আপলোড হয়েছে।');
    }

    /**
     * POST /api/tenants/{uuid}/banners/delete
     * Delete a specific banner from the store's hero carousel
     */
    public function deleteBanner(string|int $uuid): void
    {
        $identifier = trim((string)$uuid);
        $tenant = $this->tenantModel->findByUuid($identifier);
        if (!$tenant) {
            $tenant = $this->tenantModel->findBySlug($identifier);
        }

        if (!$tenant) {
            $this->error('স্টোরফ্রন্ট খুঁজে পাওয়া যায়নি।', 404);
            return;
        }

        $raw = file_get_contents('php://input');
        $input = json_decode($raw ?: '[]', true) ?? $_POST;

        $bannerPath = trim((string)($input['banner_path'] ?? ''));
        $bannerIndex = isset($input['index']) ? (int)$input['index'] : -1;

        $currentBanners = is_array($tenant['banners'] ?? null) ? $tenant['banners'] : [];
        $removed = false;

        if ($bannerPath !== '') {
            foreach ($currentBanners as $idx => $path) {
                if ($path === $bannerPath || str_ends_with($bannerPath, $path) || str_ends_with($path, $bannerPath)) {
                    $removed = true;
                    unset($currentBanners[$idx]);
                    $projectRoot = dirname(__DIR__, 3);
                    $fullPath = $projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, ltrim($path, '/'));
                    if (is_file($fullPath)) {
                        @unlink($fullPath);
                    }
                    break;
                }
            }
        }

        if (!$removed && $bannerIndex >= 0 && isset($currentBanners[$bannerIndex])) {
            $pathToDelete = $currentBanners[$bannerIndex];
            unset($currentBanners[$bannerIndex]);
            $projectRoot = dirname(__DIR__, 3);
            $fullPath = $projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, ltrim($pathToDelete, '/'));
            if (is_file($fullPath)) {
                @unlink($fullPath);
            }
            $removed = true;
        }

        $currentBanners = array_values($currentBanners);
        $this->tenantModel->updateBanners($tenant['uuid'], $currentBanners);

        $updated = $this->tenantModel->findByUuid($tenant['uuid']);
        unset($updated['password'], $updated['admin_password'], $updated['admin_note']);

        $this->success($updated, 'ব্যানার সফলভাবে মুছে ফেলা হয়েছে।');
    }

    /**
     * GET /api/tenants/{uuid}/payments
     * GET /api/storefront/{uuid}/payments
     * Fetch store subscription validity countdown and billing payments history
     */
    public function getStorefrontPayments(string $uuid): void
    {
        $tenant = $this->tenantModel->findByUuid($uuid);
        if (!$tenant) {
            $tenant = $this->tenantModel->findBySlug($uuid);
        }

        if (!$tenant) {
            $this->error('স্টোরফ্রন্ট খুঁজে পাওয়া যায়নি।', 404);
            return;
        }

        $subscriptionEnds = !empty($tenant['subscription_ends_at']) ? $tenant['subscription_ends_at'] : null;
        $now = time();
        $endsTime = $subscriptionEnds ? strtotime($subscriptionEnds) : null;
        $diffDays = $endsTime ? (int)ceil(($endsTime - $now) / 86400) : null;
        $isOverdue = ($endsTime !== null && $endsTime < $now) || in_array($tenant['status'], ['expired', 'suspended', 'inactive']);
        $isExpToday = ($diffDays === 0);
        $monthlyFee = (float)($tenant['monthly_fee'] ?? 999.00);
        $billingDay = (int)($tenant['billing_day'] ?? 10);
        $universalBkash = function_exists('env') ? env('BKASH_NUMBER', '01642956206') : '01642956206';

        $payments = $this->paymentModel->getByTenantUuid($tenant['uuid']);

        $this->success([
            'tenant' => [
                'id'                   => (int)$tenant['id'],
                'uuid'                 => $tenant['uuid'],
                'name'                 => $tenant['name'],
                'owner_name'           => $tenant['owner_name'],
                'owner_phone'          => $tenant['owner_phone'],
                'owner_email'          => $tenant['owner_email'],
                'status'               => $tenant['status'],
                'package_name'         => $tenant['package_name'] ?? 'Pro',
                'package_slug'         => $tenant['package_slug'] ?? 'pro',
                'monthly_fee'          => $monthlyFee,
                'billing_day'          => $billingDay,
                'subscription_ends_at' => $subscriptionEnds,
                'days_remaining'       => $diffDays,
                'is_overdue'           => $isOverdue,
                'is_exp_today'         => $isExpToday,
            ],
            'bkash_number' => $universalBkash,
            'payments'     => $payments
        ], 'পেমেন্ট ও সাবস্ক্রিপশন তথ্য সফলভাবে লোড হয়েছে।');
    }

    /**
     * POST /api/tenants/{uuid}/payments
     * POST /api/storefront/{uuid}/payments
     * Submit a 2-step bKash manual renewal payment
     */
    public function submitStorefrontPayment(string $uuid): void
    {
        $tenant = $this->tenantModel->findByUuid($uuid);
        if (!$tenant) {
            $tenant = $this->tenantModel->findBySlug($uuid);
        }

        if (!$tenant) {
            $this->error('স্টোরফ্রন্ট খুঁজে পাওয়া যায়নি।', 404);
            return;
        }

        $raw = preg_replace('/^\xEF\xBB\xBF/', '', (string)file_get_contents('php://input'));
        $input = json_decode($raw ?: '[]', true) ?? $_POST;

        $senderNumber = trim((string)($input['sender_number'] ?? ''));
        $trxId = strtoupper(trim((string)($input['trx_id'] ?? '')));
        $amount = !empty($input['amount']) ? (float)$input['amount'] : (float)($tenant['monthly_fee'] ?? 999.00);
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

        if (empty($senderNumber) || strlen(preg_replace('/[^0-9]/', '', $senderNumber)) < 11) {
            $this->error('অনুগ্রহ করে সঠিক ১১ ডিজিটের বিকাশ প্রেরক নম্বর প্রদান করুন।', 422);
            return;
        }

        if (!empty($trxId)) {
            $db = \App\Core\Database::getConnection();
            $stmt = $db->prepare("SELECT id FROM `tenant_payments` WHERE `trx_id` = :trx_id LIMIT 1");
            $stmt->execute([':trx_id' => $trxId]);
            if ($stmt->fetch()) {
                $this->error('এই ট্রানজেকশন আইডি (TrxID) ইতিমধ্যে একবার ব্যবহার করা হয়েছে। অনুগ্রহ করে যাচাই করুন।', 409);
                return;
            }
        }

        $paymentId = $this->paymentModel->create([
            'tenant_id'      => (int)$tenant['id'],
            'tenant_uuid'    => $tenant['uuid'],
            'store_name'     => $tenant['name'],
            'sender_number'  => $senderNumber,
            'trx_id'         => $trxId ?: 'MANUAL_' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8)),
            'amount'         => $amount,
            'billing_month'  => $billingMonth,
            'payment_type'   => 'renewal',
            'payment_method' => 'bKash',
            'status'         => 'pending',
            'admin_note'     => !empty($note) ? "মার্চেন্ট নোট: {$note}" : 'স্টোর অ্যাডমিন প্যানেল হতে মাসিক রিনিউয়াল সাবমিশন',
        ]);

        $created = $this->paymentModel->find($paymentId);

        $this->success($created, 'আপনার মাসিক বিলের পেমেন্ট তথ্য সফলভাবে জমা দেওয়া হয়েছে! অ্যাডমিন যাচাই করার পর স্বয়ংক্রিয়ভাবে অনুমোদিত হবে।', 201);
    }

    /**
     * GET /api/tenants/{uuid}/payments/{id}/invoice
     * GET /api/storefront/{uuid}/payments/{id}/invoice
     * Get single invoice details for print/download
     */
    public function getStorefrontInvoice(string $uuid, string|int $id): void
    {
        $tenant = $this->tenantModel->findByUuid($uuid);
        if (!$tenant) {
            $tenant = $this->tenantModel->findBySlug($uuid);
        }

        if (!$tenant) {
            $this->error('স্টোরফ্রন্ট খুঁজে পাওয়া যায়নি।', 404);
            return;
        }

        $payment = is_numeric($id)
            ? $this->paymentModel->find((int)$id)
            : $this->paymentModel->findByInvoiceNo((string)$id);

        if (!$payment || $payment['tenant_uuid'] !== $tenant['uuid']) {
            $this->error('ইনভয়েস পাওয়া যায়নি।', 404);
            return;
        }

        $this->success([
            'payment' => $payment,
            'tenant'  => [
                'name'        => $tenant['name'],
                'owner_name'  => $tenant['owner_name'],
                'owner_phone' => $tenant['owner_phone'],
                'owner_email' => $tenant['owner_email'],
                'address'     => $tenant['address'] ?? 'ঢাকা, বাংলাদেশ',
            ]
        ], 'ইনভয়েস লোড হয়েছে।');
    }
}
