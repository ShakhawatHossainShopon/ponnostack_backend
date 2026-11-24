<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\DeliveryCharge;
use App\Models\Tenant;

/**
 * Delivery Charge Controller
 *
 * Handles CRUD operations for tenant delivery charges and zones.
 */
class DeliveryChargeController extends Controller
{
    protected DeliveryCharge $deliveryModel;
    protected Tenant $tenantModel;

    public function __construct()
    {
        $this->deliveryModel = new DeliveryCharge();
        $this->tenantModel = new Tenant();
    }

    /**
     * Resolve target tenant from parameter, query, headers, or body
     */
    protected function resolveTenant(?string $paramUuid = null): ?array
    {
        $raw = preg_replace('/^\xEF\xBB\xBF/', '', (string)file_get_contents('php://input'));
        $input = json_decode($raw ?: '[]', true) ?? $_POST;

        $candidates = [
            $paramUuid,
            $_GET['store'] ?? null,
            $_GET['store_uuid'] ?? null,
            $_GET['tenant_uuid'] ?? null,
            $input['store_uuid'] ?? null,
            $input['tenant_uuid'] ?? null,
            $_SERVER['HTTP_X_STORE_UUID'] ?? null,
            $_SERVER['HTTP_X_TENANT_UUID'] ?? null,
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
                return ['id' => (int)$tenant['id'], 'uuid' => $tenant['uuid']];
            }
        }

        return null;
    }

    /**
     * GET /api/delivery-charges
     * GET /api/tenants/{uuid}/delivery-charges
     * List all delivery charges for tenant
     */
    public function index(?string $uuid = null): void
    {
        $tenant = $this->resolveTenant($uuid);
        if (!$tenant) {
            $this->error('স্টোরফ্রন্ট খুঁজে পাওয়া যায়নি। অনুগ্রহ করে Store UUID প্রদান করুন।', 404);
            return;
        }

        // Automatically ensure default charges (Inside Dhaka ৳80 & Outside Dhaka ৳150) exist
        $this->deliveryModel->ensureDefaults($tenant['id'], $tenant['uuid']);

        $activeOnly = (!empty($_GET['active_only']) && ($_GET['active_only'] === '1' || $_GET['active_only'] === 'true'));
        $charges = $this->deliveryModel->allByTenant($tenant['uuid'], $activeOnly);

        $this->success($charges, 'ডেলিভারি চার্জ তালিকা সফলভাবে লোড হয়েছে।');
    }

    /**
     * GET /api/delivery-charges/{uuid}
     * Show single delivery charge
     */
    public function show(string $uuid): void
    {
        $tenant = $this->resolveTenant();
        $charge = $this->deliveryModel->findByUuid($uuid, $tenant ? $tenant['uuid'] : null);
        if (!$charge) {
            $this->error('ডেলিভারি চার্জ পাওয়া যায়নি।', 404);
            return;
        }

        $this->success($charge, 'ডেলিভারি চার্জ তথ্য লোড হয়েছে।');
    }

    /**
     * POST /api/delivery-charges
     * POST /api/tenants/{uuid}/delivery-charges
     * Create a new delivery charge
     */
    public function store(?string $uuid = null): void
    {
        $tenant = $this->resolveTenant($uuid);
        if (!$tenant) {
            $this->error('স্টোরফ্রন্ট খুঁজে পাওয়া যায়নি।', 404);
            return;
        }

        $raw = preg_replace('/^\xEF\xBB\xBF/', '', (string)file_get_contents('php://input'));
        $input = json_decode($raw ?: '[]', true) ?? $_POST;

        $title = trim((string)($input['title'] ?? ''));
        $amount = isset($input['amount']) ? (float)$input['amount'] : null;

        if (empty($title)) {
            $this->error('জোনের নাম প্রদান করা আবশ্যক।', 422);
            return;
        }

        if ($amount === null || $amount < 0) {
            $this->error('সঠিক ডেলিভারি ফি প্রদান করুন।', 422);
            return;
        }

        // Generate slug code if not provided
        $code = trim((string)($input['code'] ?? ''));
        if (empty($code)) {
            $code = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '_', $title));
            $code = trim($code, '_') ?: 'zone_' . time();
        }

        $uuid = $this->deliveryModel->create([
            'tenant_id'   => $tenant['id'],
            'tenant_uuid' => $tenant['uuid'],
            'title'       => $title,
            'code'        => $code,
            'amount'      => $amount,
            'description' => trim((string)($input['description'] ?? '')),
            'is_default'  => !empty($input['is_default']) ? 1 : 0,
            'status'      => ($input['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active',
            'sort_order'  => (int)($input['sort_order'] ?? 0)
        ]);

        $created = $this->deliveryModel->findByUuid($uuid, $tenant['uuid']);
        $this->success($created, 'নতুন ডেলিভারি চার্জ সফলভাবে যুক্ত হয়েছে।', 201);
    }

    /**
     * PUT /api/delivery-charges/{uuid}
     * POST /api/delivery-charges/{uuid}
     * Update an existing delivery charge
     */
    public function update(string $uuid): void
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            $this->error('স্টোরফ্রন্ট খুঁজে পাওয়া যায়নি।', 404);
            return;
        }

        $existing = $this->deliveryModel->findByUuid($uuid, $tenant['uuid']);
        if (!$existing) {
            $this->error('ডেলিভারি চার্জ পাওয়া যায়নি।', 404);
            return;
        }

        $raw = preg_replace('/^\xEF\xBB\xBF/', '', (string)file_get_contents('php://input'));
        $input = json_decode($raw ?: '[]', true) ?? $_POST;
        $updateData = [];

        if (isset($input['title'])) {
            $title = trim((string)$input['title']);
            if (empty($title)) {
                $this->error('জোনের নাম খালি হতে পারবে না।', 422);
                return;
            }
            $updateData['title'] = $title;
        }

        if (isset($input['code'])) {
            $code = trim((string)$input['code']);
            if (!empty($code)) {
                $updateData['code'] = $code;
            }
        }

        if (isset($input['amount'])) {
            $amount = (float)$input['amount'];
            if ($amount < 0) {
                $this->error('ডেলিভারি ফি ঋণাত্মক হতে পারবে না।', 422);
                return;
            }
            $updateData['amount'] = $amount;
        }

        if (isset($input['description'])) {
            $updateData['description'] = trim((string)$input['description']);
        }

        if (isset($input['is_default'])) {
            $updateData['is_default'] = !empty($input['is_default']) ? 1 : 0;
        }

        if (isset($input['status'])) {
            $updateData['status'] = in_array($input['status'], ['active', 'inactive']) ? $input['status'] : 'active';
        }

        if (isset($input['sort_order'])) {
            $updateData['sort_order'] = (int)$input['sort_order'];
        }

        if (empty($updateData)) {
            $this->error('আপডেট করার মতো কোনো তথ্য পাওয়া যায়নি।', 400);
            return;
        }

        $this->deliveryModel->update($uuid, $updateData, $tenant['uuid']);
        $updated = $this->deliveryModel->findByUuid($uuid, $tenant['uuid']);

        $this->success($updated, 'ডেলিভারি চার্জ সফলভাবে আপডেট হয়েছে।');
    }

    /**
     * DELETE /api/delivery-charges/{uuid}
     * POST /api/delivery-charges/{uuid}/delete
     * Delete delivery charge
     */
    public function destroy(string $uuid): void
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            $this->error('স্টোরফ্রন্ট খুঁজে পাওয়া যায়নি।', 404);
            return;
        }

        $existing = $this->deliveryModel->findByUuid($uuid, $tenant['uuid']);
        if (!$existing) {
            $this->error('ডেলিভারি চার্জ পাওয়া যায়নি।', 404);
            return;
        }

        $this->deliveryModel->delete($uuid, $tenant['uuid']);
        $this->success(null, 'ডেলিভারি চার্জ সফলভাবে মুছে ফেলা হয়েছে।');
    }
}
