<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Mailer;
use App\Models\Order;
use App\Models\Tenant;
use App\Models\Product;
use Exception;

/**
 * Order Controller
 *
 * Handles order placement, public tracking (no-auth), admin management,
 * and status transitions across the 4 statuses: pending, processing, delivered, cancel.
 */
class OrderController extends Controller
{
    protected Order $orderModel;
    protected Tenant $tenantModel;
    protected Product $productModel;

    public function __construct()
    {
        $this->orderModel = new Order();
        $this->tenantModel = new Tenant();
        $this->productModel = new Product();
    }

    /**
     * Resolve target tenant from parameter, query, headers, or body
     */
    protected function resolveTenant(?string $paramUuid = null): ?array
    {
        $raw = preg_replace('/^\xEF\xBB\xBF/', '', (string)file_get_contents('php://input'));
        $input = json_decode($raw ?: '[]', true) ?? $_POST;

        $allHeaders = function_exists('getallheaders') ? array_change_key_case(getallheaders(), CASE_LOWER) : [];

        $candidates = [
            $paramUuid,
            $_GET['store'] ?? null,
            $_GET['store_uuid'] ?? null,
            $_GET['tenant_uuid'] ?? null,
            $input['store'] ?? null,
            $input['store_uuid'] ?? null,
            $input['tenant_uuid'] ?? null,
            $_SERVER['HTTP_X_STORE_UUID'] ?? null,
            $_SERVER['HTTP_X_TENANT_UUID'] ?? null,
            $_SERVER['REDIRECT_HTTP_X_STORE_UUID'] ?? null,
            $_SERVER['REDIRECT_HTTP_X_TENANT_UUID'] ?? null,
            $allHeaders['x-store-uuid'] ?? null,
            $allHeaders['x-tenant-uuid'] ?? null,
            $allHeaders['store-uuid'] ?? null,
        ];

        $uuid = '';
        foreach ($candidates as $cand) {
            if ($cand !== null && trim((string)$cand) !== '') {
                $uuid = trim((string)$cand);
                break;
            }
        }

        if (!empty($uuid)) {
            $tenant = $this->tenantModel->findByUuid($uuid);
            if (!$tenant) {
                $tenant = $this->tenantModel->findBySlug($uuid);
            }
            if ($tenant) {
                return $tenant;
            }
        }

        // Check active session if available
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }
        if (!empty($_SESSION['tenant_uuid'])) {
            $tenant = $this->tenantModel->findByUuid($_SESSION['tenant_uuid']);
            if ($tenant) {
                return $tenant;
            }
        }

        return null;
    }

    /**
     * POST /api/orders
     * Public order placement from storefront (Checkout)
     */
    public function store(): void
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            $this->error('স্টোরফ্রন্ট খুঁজে পাওয়া যায়নি অথবা Store UUID অনুপস্থিত।', 404);
            return;
        }

        $raw = preg_replace('/^\xEF\xBB\xBF/', '', (string)file_get_contents('php://input'));
        $input = json_decode($raw ?: '[]', true) ?? $_POST;

        // 1. Validation
        $name = trim((string)($input['customer_name'] ?? ''));
        $phone = trim((string)($input['customer_phone'] ?? ''));
        $division = trim((string)($input['division'] ?? ''));
        $district = trim((string)($input['district'] ?? ''));
        $upazilla = trim((string)($input['upazilla'] ?? ''));
        $address = trim((string)($input['delivery_address'] ?? ''));
        $items = $input['items'] ?? [];

        if (empty($name) || empty($phone) || empty($division) || empty($district) || empty($upazilla) || empty($address)) {
            $this->error('অনুগ্রহ করে কাস্টমার নাম, মোবাইল নম্বর এবং সম্পূর্ণ ডেলিভারি ঠিকানা সঠিকভাবে পূরণ করুন।', 422);
            return;
        }

        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($cleanPhone) < 11) {
            $this->error('অনুগ্রহ করে সঠিক ১১ ডিজিটের মোবাইল নম্বর দিন।', 422);
            return;
        }

        if (empty($items) || !is_array($items)) {
            $this->error('অর্ডারের জন্য অন্তত একটি পণ্য থাকতে হবে।', 422);
            return;
        }

        // 2. Process Line Items & Calculate Subtotal
        $subtotal = 0.00;
        $processedItems = [];

        foreach ($items as $idx => $it) {
            $pUuid = trim((string)($it['product_uuid'] ?? ''));
            if (empty($pUuid)) {
                $this->error("পণ্য রেফারেন্স (#" . ($idx + 1) . ") সঠিক নয়।", 422);
                return;
            }

            $prod = $this->productModel->findByIdentifier($pUuid, $tenant['uuid']);
            if (!$prod) {
                $this->error("পণ্যটি এই স্টোরে পাওয়া যায়নি।", 422);
                return;
            }

            $qty = max(1, (int)($it['quantity'] ?? 1));

            // Stock check
            $availableStock = (int)$prod['stock'];
            if ($availableStock <= 0) {
                $this->error("\"{$prod['name']}\" বর্তমানে আউট অফ স্টক রয়েছে।", 422);
                return;
            }
            if ($qty > $availableStock) {
                $this->error("\"{$prod['name']}\" এর পর্যাপ্ত স্টক নেই (বর্তমান স্টক: {$availableStock}টি)।", 422);
                return;
            }

            $unitPrice = isset($it['unit_price']) ? (float)$it['unit_price'] : (float)$prod['retailer_price'];
            $lineTotal = $unitPrice * $qty;
            $subtotal += $lineTotal;

            $images = !empty($prod['images']) ? json_decode((string)$prod['images'], true) : [];
            $primaryImg = !empty($images[0]) ? $images[0] : null;

            $processedItems[] = [
                'product_id'    => (int)$prod['id'],
                'product_uuid'  => $prod['uuid'],
                'product_name'  => $prod['name'],
                'product_image' => $it['product_image'] ?? $primaryImg,
                'size'          => !empty($it['size']) ? trim((string)$it['size']) : null,
                'color'         => !empty($it['color']) ? trim((string)$it['color']) : null,
                'unit_price'    => $unitPrice,
                'quantity'      => $qty,
                'total_price'   => $lineTotal,
            ];
        }

        // Delivery Charge
        $deliveryZone = trim((string)($input['delivery_zone'] ?? 'inside_dhaka'));
        $deliveryCharge = isset($input['delivery_charge']) ? (float)$input['delivery_charge'] : 60.00;
        $totalAmount = $subtotal + $deliveryCharge;

        $orderData = [
            'tenant_id'        => (int)$tenant['id'],
            'tenant_uuid'      => $tenant['uuid'],
            'customer_name'    => $name,
            'customer_phone'   => $phone,
            'customer_email'   => !empty($input['customer_email']) ? trim((string)$input['customer_email']) : null,
            'division'         => $division,
            'district'         => $district,
            'upazilla'         => $upazilla,
            'delivery_address' => $address,
            'delivery_zone'    => $deliveryZone,
            'delivery_charge'  => $deliveryCharge,
            'subtotal'         => $subtotal,
            'total_amount'     => $totalAmount,
            'payment_method'   => $input['payment_method'] ?? 'cash_on_delivery',
            'notes'            => !empty($input['notes']) ? trim((string)$input['notes']) : null,
        ];

        try {
            $newOrder = $this->orderModel->createOrder($orderData, $processedItems);

            // Send Email confirmation / notification
            try {
                Mailer::sendOrderNotificationEmail($newOrder, $tenant);
            } catch (\Throwable $mailErr) {
                error_log("Order notification email failed: " . $mailErr->getMessage());
            }

            $this->success($newOrder, 'আপনার অর্ডারটি সফলভাবে গ্রহণ করা হয়েছে!', 201);
        } catch (Exception $e) {
            $this->error('অর্ডার প্রক্রিয়াকরণে ত্রুটি হয়েছে: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/orders/track/{orderNumber}
     * Public order tracking by Order Number / Invoice ID
     */
    public function track(mixed $orderNumber): void
    {
        $orderNumber = trim((string)$orderNumber);
        if (empty($orderNumber)) {
            $this->error('অর্ডার বা ইনভয়েস নম্বর প্রয়োজন।', 400);
            return;
        }

        $tenant = $this->resolveTenant();
        $tenantUuid = $tenant ? $tenant['uuid'] : null;

        $order = $this->orderModel->findByOrderNumber($orderNumber, $tenantUuid);
        if (!$order) {
            $this->error('প্রদত্ত নম্বরে কোনো অর্ডার খুঁজে পাওয়া যায়নি। অনুগ্রহ করে সঠিক অর্ডার আইডি দিন।', 404);
            return;
        }

        $roadmap = $this->buildRoadmap($order);

        $this->success([
            'order'   => $order,
            'roadmap' => $roadmap,
        ], 'অর্ডার ট্র্যাকিং তথ্য লোড হয়েছে');
    }

    /**
     * Build visual roadmap stepper state
     */
    protected function buildRoadmap(array $order): array
    {
        $status = strtolower($order['status'] ?? 'pending');

        $steps = [
            [
                'key'         => 'pending',
                'title'       => 'অর্ডার গৃহীত হয়েছে',
                'subtitle'    => 'আপনার অর্ডারটি নিশ্চিত ও গ্রহণ করা হয়েছে।',
                'completed'   => in_array($status, ['pending', 'processing', 'delivered']),
                'current'     => ($status === 'pending'),
                'icon'        => 'clipboard-check',
            ],
            [
                'key'         => 'processing',
                'title'       => 'প্রসেসিং ও প্যাকেজিং',
                'subtitle'    => 'পণ্যটি মান যাচাই ও প্যাকেজিং সম্পন্ন করে প্রস্তুত করা হচ্ছে।',
                'completed'   => in_array($status, ['processing', 'delivered']),
                'current'     => ($status === 'processing'),
                'icon'        => 'package',
            ],
            [
                'key'         => 'delivered',
                'title'       => 'ডেলিভারি সম্পন্ন',
                'subtitle'    => 'পার্সেলটি আপনার ঠিকানায় সফলভাবে পৌঁছে দেওয়া হয়েছে।',
                'completed'   => ($status === 'delivered'),
                'current'     => ($status === 'delivered'),
                'icon'        => 'check-circle-2',
            ]
        ];

        return [
            'status'         => $status,
            'is_cancelled'   => ($status === 'cancel'),
            'cancel_message' => ($status === 'cancel') ? 'এই অর্ডারটি বাতিল করা হয়েছে।' : null,
            'steps'          => $steps,
        ];
    }

    /**
     * GET /api/orders
     * List all orders for tenant (Admin)
     */
    public function index(): void
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            $this->error('Unauthorized or Store UUID missing', 401);
            return;
        }

        // Auto-purge trash: cancelled orders older than 30 days
        $this->orderModel->purgeOldCancelledOrders($tenant['uuid'], 30);

        $filters = [
            'status' => $_GET['status'] ?? null,
            'search' => $_GET['search'] ?? null,
            'limit'  => $_GET['limit'] ?? null,
            'offset' => $_GET['offset'] ?? null,
        ];

        $orders = $this->orderModel->allByTenant($tenant['uuid'], $filters);
        $this->success($orders, 'Orders retrieved successfully');
    }

    /**
     * GET /api/orders/stats
     * Get order counts and financial totals by status (Admin)
     */
    public function stats(): void
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            $this->error('Unauthorized or Store UUID missing', 401);
            return;
        }

        // Auto-purge trash: cancelled orders older than 30 days
        $this->orderModel->purgeOldCancelledOrders($tenant['uuid'], 30);

        $stats = $this->orderModel->getOrderStats($tenant['uuid']);
        $this->success($stats, 'Order statistics retrieved successfully');
    }

    /**
     * GET /api/orders/{uuid}
     * Get full details of a single order (Admin)
     */
    public function show(mixed $uuid): void
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            $this->error('Unauthorized or Store UUID missing', 401);
            return;
        }

        $order = $this->orderModel->findByUuid((string)$uuid, $tenant['uuid']);
        if (!$order) {
            $this->error('Order not found', 404);
            return;
        }

        $this->success($order, 'Order details retrieved successfully');
    }

    /**
     * PUT /api/orders/{uuid}/status
     * Admin updates order status (pending, processing, delivered, cancel)
     */
    public function updateStatus(mixed $uuid): void
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            $this->error('Unauthorized or Store UUID missing', 401);
            return;
        }

        $raw = preg_replace('/^\xEF\xBB\xBF/', '', (string)file_get_contents('php://input'));
        $input = json_decode($raw ?: '[]', true) ?? $_POST;

        $newStatus = strtolower(trim((string)($input['status'] ?? '')));
        // Normalize status synonyms
        if ($newStatus === 'completed') $newStatus = 'delivered';
        if ($newStatus === 'cancelled') $newStatus = 'cancel';

        $validStatuses = ['pending', 'processing', 'delivered', 'cancel'];
        if (!in_array($newStatus, $validStatuses)) {
            $this->error('ভুল স্ট্যাটাস প্রদান করা হয়েছে। গ্রহণযোগ্য স্ট্যাটাস: pending, processing, delivered, cancel', 422);
            return;
        }

        try {
            $updated = $this->orderModel->updateStatus((string)$uuid, $newStatus, $tenant['uuid']);
            if (!$updated) {
                $this->error('অর্ডার পাওয়া যায়নি অথবা স্ট্যাটাস পরিবর্তন ব্যর্থ হয়েছে।', 404);
                return;
            }

            $order = $this->orderModel->findByUuid((string)$uuid, $tenant['uuid']);
            $this->success($order, 'অর্ডার স্ট্যাটাস সফলভাবে আপডেট করা হয়েছে।');
        } catch (Exception $e) {
            $this->error('স্ট্যাটাস পরিবর্তনে ত্রুটি: ' . $e->getMessage(), 500);
        }
    }
}
