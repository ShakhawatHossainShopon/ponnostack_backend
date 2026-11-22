<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Tenant Payment & Invoice Model
 * 
 * Manages store subscription payments, monthly renewals, invoices,
 * and bKash transaction verifications.
 */
class TenantPayment extends Model
{
    /**
     * Get all payment submissions & invoices
     *
     * @param string|null $status 'pending', 'approved', 'rejected'
     * @param string|null $type 'initial', 'renewal', 'extension'
     * @param string|null $search Search store name, trx_id, invoice_no, phone
     * @return array
     */
    public function getAll(?string $status = null, ?string $type = null, ?string $search = null): array
    {
        $params = [];
        $sql = "SELECT p.*, 
                       t.name as tenant_name, 
                       t.slug as tenant_slug, 
                       t.owner_name, 
                       t.owner_phone, 
                       t.owner_email,
                       t.source as tenant_source,
                       t.monthly_fee as tenant_fee,
                       t.package_name as tenant_package,
                       t.subscription_ends_at,
                       t.billing_day,
                       t.status as tenant_status
                FROM `tenant_payments` p
                LEFT JOIN `tenants` t ON p.tenant_uuid = t.uuid
                WHERE 1=1";

        if ($status && in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $sql .= " AND p.`status` = :status";
            $params[':status'] = $status;
        }

        if ($type && in_array($type, ['initial', 'renewal', 'extension'], true)) {
            $sql .= " AND p.`payment_type` = :payment_type";
            $params[':payment_type'] = $type;
        }

        if (!empty($search)) {
            $sql .= " AND (p.`invoice_no` LIKE :search 
                        OR p.`trx_id` LIKE :search 
                        OR p.`store_name` LIKE :search 
                        OR p.`sender_number` LIKE :search 
                        OR t.`owner_name` LIKE :search 
                        OR t.`owner_phone` LIKE :search)";
            $params[':search'] = '%' . trim($search) . '%';
        }

        $sql .= " ORDER BY p.`id` DESC";
        $rows = $this->fetchAll($sql, $params);

        return array_map([self::class, 'formatRow'], $rows);
    }

    /**
     * Get aggregate statistics for billing & invoices
     */
    public function getBillingStats(): array
    {
        $sql = "SELECT 
            COUNT(*) as total_count,
            SUM(CASE WHEN `status` = 'approved' THEN `amount` ELSE 0 END) as total_collected,
            SUM(CASE WHEN `status` = 'pending' THEN `amount` ELSE 0 END) as pending_amount,
            SUM(CASE WHEN `status` = 'pending' THEN 1 ELSE 0 END) as total_pending,
            SUM(CASE WHEN `status` = 'pending' AND `payment_type` = 'renewal' THEN 1 ELSE 0 END) as pending_renewals,
            SUM(CASE WHEN `status` = 'approved' AND `payment_type` = 'initial' THEN 1 ELSE 0 END) as initial_count,
            SUM(CASE WHEN `status` = 'approved' AND `payment_type` = 'renewal' THEN 1 ELSE 0 END) as renewal_count,
            SUM(CASE WHEN `status` = 'approved' AND MONTH(`created_at`) = MONTH(CURRENT_DATE()) AND YEAR(`created_at`) = YEAR(CURRENT_DATE()) THEN `amount` ELSE 0 END) as monthly_collected
        FROM `tenant_payments`";

        $res = $this->fetch($sql) ?: [];

        return [
            'total_count'       => (int)($res['total_count'] ?? 0),
            'total_collected'   => (int)($res['total_collected'] ?? 0),
            'pending_amount'    => (int)($res['pending_amount'] ?? 0),
            'total_pending'     => (int)($res['total_pending'] ?? 0),
            'pending_renewals'  => (int)($res['pending_renewals'] ?? 0),
            'initial_count'     => (int)($res['initial_count'] ?? 0),
            'renewal_count'     => (int)($res['renewal_count'] ?? 0),
            'monthly_collected' => (int)($res['monthly_collected'] ?? 0),
        ];
    }

    /**
     * Get payments for a specific tenant (newest first)
     */
    public function getByTenantId(int $tenantId): array
    {
        $sql = "SELECT * FROM `tenant_payments` WHERE `tenant_id` = :tenant_id ORDER BY `id` DESC";
        $rows = $this->fetchAll($sql, [':tenant_id' => $tenantId]);
        return array_map([self::class, 'formatRow'], $rows);
    }

    /**
     * Get payments for a specific tenant by UUID
     */
    public function getByTenantUuid(string $uuid): array
    {
        $sql = "SELECT * FROM `tenant_payments` WHERE `tenant_uuid` = :uuid ORDER BY `id` DESC";
        $rows = $this->fetchAll($sql, [':uuid' => $uuid]);
        return array_map([self::class, 'formatRow'], $rows);
    }

    /**
     * Find single payment by ID
     */
    public function find(int $id): ?array
    {
        $sql = "SELECT p.*, 
                       t.name as tenant_name, 
                       t.slug as tenant_slug, 
                       t.owner_name, 
                       t.owner_phone, 
                       t.owner_email,
                       t.address as tenant_address,
                       t.source as tenant_source,
                       t.monthly_fee as tenant_fee,
                       t.package_name as tenant_package,
                       t.subscription_ends_at,
                       t.billing_day,
                       t.status as tenant_status
                FROM `tenant_payments` p
                LEFT JOIN `tenants` t ON p.tenant_uuid = t.uuid
                WHERE p.`id` = :id LIMIT 1";
        $row = $this->fetch($sql, [':id' => $id]);
        return $row ? self::formatRow($row) : null;
    }

    /**
     * Find single payment by Invoice Number
     */
    public function findByInvoiceNo(string $invoiceNo): ?array
    {
        $sql = "SELECT p.*, 
                       t.name as tenant_name, 
                       t.slug as tenant_slug, 
                       t.owner_name, 
                       t.owner_phone, 
                       t.owner_email,
                       t.address as tenant_address,
                       t.source as tenant_source,
                       t.monthly_fee as tenant_fee,
                       t.package_name as tenant_package,
                       t.subscription_ends_at,
                       t.billing_day,
                       t.status as tenant_status
                FROM `tenant_payments` p
                LEFT JOIN `tenants` t ON p.tenant_uuid = t.uuid
                WHERE p.`invoice_no` = :invoice_no LIMIT 1";
        $row = $this->fetch($sql, [':invoice_no' => trim($invoiceNo)]);
        return $row ? self::formatRow($row) : null;
    }

    /**
     * Create a payment record with auto-generated Invoice Number
     */
    public function create(array $data): int
    {
        $invoiceNo = !empty($data['invoice_no']) ? trim((string)$data['invoice_no']) : null;

        $sql = "INSERT INTO `tenant_payments` (
            `invoice_no`, `tenant_id`, `tenant_uuid`, `store_name`, `sender_number`, `trx_id`,
            `amount`, `billing_month`, `payment_type`, `payment_method`, `status`, `admin_note`, `created_at`
        ) VALUES (
            :invoice_no, :tenant_id, :tenant_uuid, :store_name, :sender_number, :trx_id,
            :amount, :billing_month, :payment_type, :payment_method, :status, :admin_note, NOW()
        )";

        $stmt = $this->query($sql, [
            ':invoice_no'     => $invoiceNo,
            ':tenant_id'      => (int)$data['tenant_id'],
            ':tenant_uuid'    => (string)($data['tenant_uuid'] ?? ''),
            ':store_name'     => (string)($data['store_name'] ?? ''),
            ':sender_number'  => (string)($data['sender_number'] ?? ''),
            ':trx_id'         => strtoupper(trim((string)($data['trx_id'] ?? ''))),
            ':amount'         => (int)($data['amount'] ?? 200),
            ':billing_month'  => !empty($data['billing_month']) ? (string)$data['billing_month'] : null,
            ':payment_type'   => (string)($data['payment_type'] ?? 'renewal'),
            ':payment_method' => (string)($data['payment_method'] ?? 'bKash'),
            ':status'         => (string)($data['status'] ?? 'pending'),
            ':admin_note'     => !empty($data['admin_note']) ? (string)$data['admin_note'] : null,
        ]);

        $id = (int)$this->db->lastInsertId();

        // If invoice_no was not pre-assigned, generate deterministic standard format: INV-YYYYMM-XXXX
        if (empty($invoiceNo)) {
            $generatedInvoice = 'INV-' . date('Ym') . '-' . str_pad((string)$id, 4, '0', STR_PAD_LEFT);
            $this->query("UPDATE `tenant_payments` SET `invoice_no` = :inv WHERE `id` = :id", [
                ':inv' => $generatedInvoice,
                ':id'  => $id
            ]);
        }

        return $id;
    }

    /**
     * Approve a payment and advance tenant subscription by 1 month to recurring billing day
     */
    public function approveRenewal(int $id, ?string $adminNote = null): bool
    {
        $payment = $this->find($id);
        if (!$payment) {
            return false;
        }

        // 1. Mark payment approved
        $sql = "UPDATE `tenant_payments` 
                SET `status` = 'approved', `approved_at` = NOW(), `admin_note` = :admin_note 
                WHERE `id` = :id";
        $this->query($sql, [
            ':id'         => $id,
            ':admin_note' => $adminNote,
        ]);

        // 2. Fetch tenant and advance their recurring validity
        $tenantModel = new Tenant();
        $tenant = $tenantModel->find((int)$payment['tenant_id']);
        if ($tenant) {
            $billingDay = (int)($tenant['billing_day'] ?? 10);
            
            // Calculate next recurring billing date (advances by 1 month from current expiry or today)
            $currentExpiry = !empty($tenant['subscription_ends_at']) ? strtotime($tenant['subscription_ends_at']) : time();
            $baseTimestamp = ($currentExpiry > time()) ? $currentExpiry : time();

            // Next month date with tenant's permanent billing day
            $nextMonth = date('Y-m', strtotime('+1 month', $baseTimestamp));
            $daysInNextMonth = (int)date('t', strtotime($nextMonth . '-01'));
            $effectiveDay = min($billingDay, $daysInNextMonth);
            $newEndsAt = "{$nextMonth}-" . str_pad((string)$effectiveDay, 2, '0', STR_PAD_LEFT) . " 23:59:59";

            $this->query("UPDATE `tenants` 
                          SET `status` = 'active', 
                              `subscription_ends_at` = :new_ends_at 
                          WHERE `id` = :tenant_id", [
                ':new_ends_at' => $newEndsAt,
                ':tenant_id'   => $tenant['id']
            ]);
        }

        return true;
    }

    /**
     * Simple status update
     */
    public function approve(int $id, ?string $adminNote = null): bool
    {
        return $this->approveRenewal($id, $adminNote);
    }

    /**
     * Delete a payment record
     */
    public function delete(int $id): bool
    {
        $stmt = $this->query("DELETE FROM `tenant_payments` WHERE `id` = :id", [':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Format a payment / invoice row with badges & labels
     */
    public static function formatRow(array $row): array
    {
        $row['id'] = (int)$row['id'];
        $row['tenant_id'] = (int)$row['tenant_id'];
        $row['amount'] = (int)$row['amount'];
        $row['payment_type'] = $row['payment_type'] ?? 'renewal';
        $row['billing_month'] = $row['billing_month'] ?? null;
        $row['invoice_no'] = $row['invoice_no'] ?? ('INV-' . date('Ym', strtotime($row['created_at'] ?? 'now')) . '-' . str_pad((string)$row['id'], 4, '0', STR_PAD_LEFT));

        // Formatted dates
        $row['formatted_date'] = !empty($row['created_at']) 
            ? date('d M, Y h:i A', strtotime($row['created_at'])) 
            : '-';
        $row['formatted_date_short'] = !empty($row['created_at']) 
            ? date('d M, Y', strtotime($row['created_at'])) 
            : '-';
        $row['formatted_approved'] = !empty($row['approved_at']) 
            ? date('d M, Y h:i A', strtotime($row['approved_at'])) 
            : null;

        // Payment Type Label & Badge
        switch ($row['payment_type']) {
            case 'initial':
                $row['type_label'] = 'নতুন সাবস্ক্রিপশন';
                $row['type_badge_cls'] = 'bg-purple-50 text-purple-700 border border-purple-200';
                $row['type_icon'] = 'sparkles';
                break;
            case 'extension':
                $row['type_label'] = 'গ্রেস এক্সটেনশন';
                $row['type_badge_cls'] = 'bg-amber-50 text-amber-800 border border-amber-200';
                $row['type_icon'] = 'clock';
                break;
            case 'renewal':
            default:
                $row['type_label'] = 'মাসিক রিনিউয়াল';
                $row['type_badge_cls'] = 'bg-blue-50 text-blue-700 border border-blue-200';
                $row['type_icon'] = 'repeat';
                break;
        }

        // Status Label & Badge
        switch ($row['status']) {
            case 'approved':
                $row['status_label'] = 'পরিশোধিত (Paid)';
                $row['status_badge_cls'] = 'bg-emerald-50 text-emerald-800 border border-emerald-200';
                $row['status_icon'] = 'check-circle-2';
                break;
            case 'rejected':
                $row['status_label'] = 'বাতিল (Rejected)';
                $row['status_badge_cls'] = 'bg-rose-50 text-rose-800 border border-rose-200';
                $row['status_icon'] = 'x-circle';
                break;
            case 'pending':
            default:
                $row['status_label'] = 'অপেক্ষমাণ (Pending)';
                $row['status_badge_cls'] = 'bg-amber-50 text-amber-800 border border-amber-200 animate-pulse';
                $row['status_icon'] = 'hourglass';
                break;
        }

        // Tenant Source Badge
        $source = $row['tenant_source'] ?? 'website';
        if ($source === 'admin') {
            $row['source_label'] = 'অ্যাডমিন তৈরি';
            $row['source_badge_cls'] = 'bg-slate-100 text-slate-700 border border-slate-200';
            $row['source_icon'] = 'user-check';
        } else {
            $row['source_label'] = 'অনলাইন অর্ডার';
            $row['source_badge_cls'] = 'bg-sky-50 text-sky-700 border border-sky-200';
            $row['source_icon'] = 'globe';
        }

        return $row;
    }
}

