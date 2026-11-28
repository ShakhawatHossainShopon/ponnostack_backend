<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

/**
 * Tenant Storefront Model
 * 
 * Manages all merchant storefront tenants in the `tenants` table,
 * approval workflows, subscription lifecycles, and billing day calculations.
 */
class Tenant extends Model
{
    /**
     * Retrieve all tenants (ordered newest first, with optional filter & search)
     */
    public function getAll(?string $statusFilter = null, ?string $search = null): array
    {
        // Auto-synchronize overdue active tenants
        try {
            $this->query("UPDATE `tenants` SET `status` = 'expired' WHERE `status` = 'active' AND `subscription_ends_at` IS NOT NULL AND `subscription_ends_at` < NOW()");
        } catch (\Throwable $e) {}

        $params = [];
        $sql = "SELECT * FROM `tenants` WHERE 1=1";

        if ($statusFilter) {
            if ($statusFilter === 'payment_pending') {
                $sql .= " AND `status` IN ('payment_pending', 'pending')";
            } elseif ($statusFilter === 'setup_pending') {
                $sql .= " AND `status` = 'setup_pending'";
            } elseif ($statusFilter === 'active') {
                $sql .= " AND `status` = 'active' AND (`subscription_ends_at` IS NULL OR `subscription_ends_at` >= NOW())";
            } elseif ($statusFilter === 'expiring_today') {
                $sql .= " AND `status` = 'active' AND `subscription_ends_at` IS NOT NULL AND DATE(`subscription_ends_at`) = CURDATE() AND `subscription_ends_at` >= NOW()";
            } elseif ($statusFilter === 'expiring_soon') {
                $sql .= " AND `status` = 'active' AND `subscription_ends_at` IS NOT NULL AND `subscription_ends_at` > NOW() AND `subscription_ends_at` <= DATE_ADD(NOW(), INTERVAL 5 DAY)";
            } elseif ($statusFilter === 'overdue' || $statusFilter === 'suspended' || $statusFilter === 'expired') {
                $sql .= " AND (`status` IN ('suspended', 'expired') OR (`subscription_ends_at` IS NOT NULL AND `subscription_ends_at` < NOW()))";
            } elseif (in_array($statusFilter, ['pending', 'active', 'suspended', 'expired', 'inactive', 'payment_pending', 'setup_pending'], true)) {
                $sql .= " AND `status` = :status";
                $params[':status'] = $statusFilter;
            }
        }

        if (!empty($search)) {
            $sql .= " AND (`name` LIKE :search OR `slug` LIKE :search OR `owner_name` LIKE :search OR `owner_phone` LIKE :search OR `owner_email` LIKE :search OR `domain` LIKE :search OR `payment_trx_id` LIKE :search)";
            $params[':search'] = '%' . trim($search) . '%';
        }

        $sql .= " ORDER BY `id` DESC";

        $rows = $this->fetchAll($sql, $params);
        return array_map([self::class, 'formatRow'], $rows);
    }

    /**
     * Get aggregate statistics for dashboard & metrics
     */
    public function getStats(): array
    {
        // Auto-synchronize overdue active tenants
        try {
            $this->query("UPDATE `tenants` SET `status` = 'expired' WHERE `status` = 'active' AND `subscription_ends_at` IS NOT NULL AND `subscription_ends_at` < NOW()");
        } catch (\Throwable $e) {}

        $sql = "SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN `status` IN ('payment_pending', 'pending') THEN 1 ELSE 0 END) as payment_pending,
            SUM(CASE WHEN `status` = 'setup_pending' THEN 1 ELSE 0 END) as setup_pending,
            SUM(CASE WHEN `status` = 'active' AND (`subscription_ends_at` IS NULL OR `subscription_ends_at` >= NOW()) THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN `status` = 'suspended' THEN 1 ELSE 0 END) as suspended,
            SUM(CASE WHEN `status` = 'expired' OR (`status` = 'active' AND `subscription_ends_at` IS NOT NULL AND `subscription_ends_at` < NOW()) THEN 1 ELSE 0 END) as expired,
            SUM(CASE WHEN `status` IN ('suspended', 'expired') OR (`status` = 'active' AND `subscription_ends_at` IS NOT NULL AND `subscription_ends_at` < NOW()) THEN 1 ELSE 0 END) as overdue,
            SUM(CASE WHEN `status` = 'active' AND (`subscription_ends_at` IS NULL OR `subscription_ends_at` >= NOW()) THEN `monthly_fee` ELSE 0 END) as mrr
        FROM `tenants`";

        $res = $this->fetch($sql) ?: [];

        // Count expiring today
        $todaySql = "SELECT COUNT(*) as expiring_today FROM `tenants` 
                     WHERE `status` = 'active' 
                     AND `subscription_ends_at` IS NOT NULL 
                     AND DATE(`subscription_ends_at`) = CURDATE()
                     AND `subscription_ends_at` >= NOW()";
        $todayRes = $this->fetch($todaySql) ?: [];

        // Count expiring soon (active tenants where subscription_ends_at is within 5 days)
        $expSql = "SELECT COUNT(*) as expiring_soon FROM `tenants` 
                   WHERE `status` = 'active' 
                   AND `subscription_ends_at` IS NOT NULL 
                   AND `subscription_ends_at` > NOW() 
                   AND `subscription_ends_at` <= DATE_ADD(NOW(), INTERVAL 5 DAY)";
        $expRes = $this->fetch($expSql) ?: [];

        return [
            'total'           => (int)($res['total'] ?? 0),
            'pending'         => (int)($res['payment_pending'] ?? 0),
            'payment_pending' => (int)($res['payment_pending'] ?? 0),
            'setup_pending'   => (int)($res['setup_pending'] ?? 0),
            'active'          => (int)($res['active'] ?? 0),
            'expiring_today'  => (int)($todayRes['expiring_today'] ?? 0),
            'expiring_soon'   => (int)($expRes['expiring_soon'] ?? 0),
            'suspended'       => (int)($res['suspended'] ?? 0),
            'expired'         => (int)($res['expired'] ?? 0),
            'overdue'         => (int)($res['overdue'] ?? 0),
            'mrr'             => (int)($res['mrr'] ?? 0),
        ];
    }

    /**
     * Find tenant by ID
     */
    public function find(int $id): ?array
    {
        $row = $this->fetch("SELECT * FROM `tenants` WHERE `id` = :id LIMIT 1", [':id' => $id]);
        return $row ? self::formatRow($row) : null;
    }

    /**
     * Find tenant by UUID
     */
    public function findByUuid(string $uuid): ?array
    {
        $row = $this->fetch("SELECT * FROM `tenants` WHERE `uuid` = :uuid LIMIT 1", [':uuid' => $uuid]);
        return $row ? self::formatRow($row) : null;
    }

    /**
     * Find tenant by slug
     */
    public function findBySlug(string $slug, ?int $excludeId = null): ?array
    {
        $clean = strtolower(trim($slug));
        $params = [':slug' => $clean];
        $sql = "SELECT * FROM `tenants` WHERE `slug` = :slug";
        if ($excludeId !== null) {
            $sql .= " AND `id` != :excludeId";
            $params[':excludeId'] = $excludeId;
        }
        $sql .= " LIMIT 1";

        $row = $this->fetch($sql, $params);
        if (!$row) {
            // Flexible hyphen/underscore-insensitive fallback:
            // e.g. "zaharanurcollection" matches "zahara-nur-collection" and vice versa
            $noHyphens = str_replace(['-', '_'], '', $clean);
            if (!empty($noHyphens)) {
                $fallbackSql = "SELECT * FROM `tenants` WHERE REPLACE(REPLACE(`slug`, '-', ''), '_', '') = :noHyphens";
                $fallbackParams = [':noHyphens' => $noHyphens];
                if ($excludeId !== null) {
                    $fallbackSql .= " AND `id` != :excludeId";
                    $fallbackParams[':excludeId'] = $excludeId;
                }
                $fallbackSql .= " LIMIT 1";
                $row = $this->fetch($fallbackSql, $fallbackParams);
            }
        }
        return $row ? self::formatRow($row) : null;
    }

    /**
     * Check if slug is available
     */
    public function isSlugAvailable(string $slug, ?int $excludeId = null): bool
    {
        return $this->findBySlug($slug, $excludeId) === null;
    }

    /**
     * Find tenant by store name
     */
    public function findByName(string $name, ?int $excludeId = null): ?array
    {
        $params = [':name' => trim($name)];
        $sql = "SELECT * FROM `tenants` WHERE `name` = :name";
        if ($excludeId !== null) {
            $sql .= " AND `id` != :excludeId";
            $params[':excludeId'] = $excludeId;
        }
        $sql .= " LIMIT 1";

        $row = $this->fetch($sql, $params);
        return $row ? self::formatRow($row) : null;
    }

    /**
     * Check if store name is available
     */
    public function isNameAvailable(string $name, ?int $excludeId = null): bool
    {
        return $this->findByName($name, $excludeId) === null;
    }

    /**
     * Find tenant by owner email
     */
    public function findByEmail(string $email, ?int $excludeId = null): ?array
    {
        $params = [':email' => strtolower(trim($email))];
        $sql = "SELECT * FROM `tenants` WHERE `owner_email` = :email";
        if ($excludeId !== null) {
            $sql .= " AND `id` != :excludeId";
            $params[':excludeId'] = $excludeId;
        }
        $sql .= " LIMIT 1";

        $row = $this->fetch($sql, $params);
        return $row ? self::formatRow($row) : null;
    }

    /**
     * Check if owner email is available
     */
    public function isEmailAvailable(string $email, ?int $excludeId = null): bool
    {
        return $this->findByEmail($email, $excludeId) === null;
    }

    /**
     * Authenticate tenant by owner email and password
     */
    public function authenticate(string $email, string $password): ?array
    {
        $tenant = $this->findByEmail($email);
        if (!$tenant || empty($tenant['password'])) {
            return null;
        }

        if (!password_verify($password, $tenant['password'])) {
            return null;
        }

        return $tenant;
    }

    /**
     * Create a new tenant record (From checkout or manual admin creation)
     *
     * @param array $data
     * @return int Inserted ID
     */
    public function create(array $data): int
    {
        $uuid = $data['uuid'] ?? self::generateUuidV4();
        $passwordHash = password_hash($data['password'] ?? '123456', PASSWORD_BCRYPT);

        $socialLinks = null;
        if (!empty($data['social_links'])) {
            $socialLinks = is_array($data['social_links'])
                ? json_encode($data['social_links'], JSON_UNESCAPED_UNICODE)
                : $data['social_links'];
        }

        $sql = "INSERT INTO `tenants` (
            `uuid`, `name`, `slug`, `domain`, `admin_url`, `owner_name`, `owner_email`, `password`,
            `owner_phone`, `address`, `logo`, `banners`, `currency`, `monthly_fee`,
            `package_name`, `package_slug`, `product_limit`, `payment_method`,
            `payment_sender`, `payment_trx_id`, `billing_day`, `social_links`,
            `template_id`, `color_palette`, `subscription_ends_at`, `status`, `source`
        ) VALUES (
            :uuid, :name, :slug, :domain, :admin_url, :owner_name, :owner_email, :password,
            :owner_phone, :address, :logo, :banners, :currency, :monthly_fee,
            :package_name, :package_slug, :product_limit, :payment_method,
            :payment_sender, :payment_trx_id, :billing_day, :social_links,
            :template_id, :color_palette, :subscription_ends_at, :status, :source
        )";

        $stmt = $this->query($sql, [
            ':uuid'                 => $uuid,
            ':name'                 => trim($data['name']),
            ':slug'                 => strtolower(trim($data['slug'])),
            ':domain'               => !empty($data['domain']) ? trim($data['domain']) : null,
            ':admin_url'            => !empty($data['admin_url']) ? trim($data['admin_url']) : null,
            ':owner_name'           => trim($data['owner_name']),
            ':owner_email'          => strtolower(trim($data['owner_email'])),
            ':password'             => $passwordHash,
            ':owner_phone'          => $data['owner_phone'] ?? null,
            ':address'              => $data['address'] ?? null,
            ':logo'                 => $data['logo'] ?? null,
            ':banners'              => $data['banners'] ?? null,
            ':currency'             => strtoupper($data['currency'] ?? 'BDT'),
            ':monthly_fee'          => (int)($data['monthly_fee'] ?? 200),
            ':package_name'         => $data['package_name'] ?? 'স্ট্যান্ডার্ড প্যাকেজ',
            ':package_slug'         => $data['package_slug'] ?? 'starter',
            ':product_limit'        => (int)($data['product_limit'] ?? 50),
            ':payment_method'       => $data['payment_method'] ?? 'bKash',
            ':payment_sender'       => $data['payment_sender'] ?? null,
            ':payment_trx_id'       => $data['payment_trx_id'] ?? null,
            ':billing_day'          => (int)($data['billing_day'] ?? 10),
            ':social_links'         => $socialLinks,
            ':template_id'          => $data['template_id'] ?? 'template_1',
            ':color_palette'        => $data['color_palette'] ?? 'emerald',
            ':subscription_ends_at' => !empty($data['subscription_ends_at']) ? $data['subscription_ends_at'] : null,
            ':status'               => $data['status'] ?? 'pending',
            ':source'               => $data['source'] ?? 'website',
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Update generic tenant fields
     */
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = [':id' => $id];

        $allowed = [
            'name', 'slug', 'domain', 'admin_url', 'owner_name', 'owner_email',
            'owner_phone', 'address', 'currency', 'logo', 'banners', 'social_links', 'template_id', 'color_palette',
            'monthly_fee', 'package_name', 'package_slug', 'product_limit', 'billing_day', 'subscription_ends_at', 'status'
        ];

        foreach ($allowed as $key) {
            if (array_key_exists($key, $data)) {
                $val = $data[$key];
                if (in_array($key, ['social_links', 'banners'], true)) {
                    if (is_array($val)) {
                        $val = json_encode($val, JSON_UNESCAPED_UNICODE);
                    }
                }
                $fields[] = "`{$key}` = :{$key}";
                $params[":{$key}"] = $val;
            }
        }

        if (!empty($data['password'])) {
            $fields[] = "`password` = :password";
            $params[':password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }

        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE `tenants` SET " . implode(', ', $fields) . " WHERE `id` = :id";
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount() >= 0;
    }

    /**
     * Update status only
     */
    public function updateStatus(int $id, string $status): bool
    {
        if (!in_array($status, ['pending', 'active', 'suspended', 'expired', 'inactive'], true)) {
            return false;
        }
        $stmt = $this->query("UPDATE `tenants` SET `status` = :status WHERE `id` = :id", [
            ':status' => $status,
            ':id'     => $id
        ]);
        return $stmt->rowCount() >= 0;
    }

    /**
     * Stage 1: Approve Payment (bKash TrxID verification)
     * Transitions store from 'payment_pending' to 'setup_pending'
     * Marks initial payment as approved
     */
    public function approvePayment(int $id): bool
    {
        $tenant = $this->find($id);
        if (!$tenant) {
            return false;
        }

        // Update tenant status to setup_pending
        $this->query("UPDATE `tenants` SET `status` = 'setup_pending' WHERE `id` = :id", [
            ':id' => $id
        ]);

        // Auto approve initial payment in tenant_payments table
        $this->query("UPDATE `tenant_payments` 
                      SET `status` = 'approved', `approved_at` = NOW() 
                      WHERE `tenant_id` = :id AND `payment_type` = 'initial'", [
            ':id' => $id
        ]);

        return true;
    }

    /**
     * Stage 2: Setup Domain/URL & Activate Storefront
     * Transitions store from 'setup_pending' to 'active'
     * Calculates immutable monthly billing_day starting from today's activation day
     */
    public function setupStore(int $id, array $data): bool
    {
        $tenant = $this->find($id);
        if (!$tenant) {
            return false;
        }

        $domain = trim((string)($data['domain'] ?? ''));
        $adminUrl = trim((string)($data['admin_url'] ?? ''));
        if (empty($adminUrl) && !empty($domain)) {
            $adminUrl = rtrim($domain, '/') . '/admin';
        }

        // Immutable billing cycle day starts from today's activation day
        $billingDay = (int)date('j');
        // Initial 1-month validity
        $subscriptionEndsAt = date('Y-m-d 23:59:59', strtotime('+1 month'));

        $sql = "UPDATE `tenants` SET 
                `domain` = :domain,
                `admin_url` = :admin_url,
                `billing_day` = :billing_day,
                `subscription_ends_at` = :subscription_ends_at,
                `status` = 'active'
                WHERE `id` = :id";

        $this->query($sql, [
            ':domain'               => $domain,
            ':admin_url'            => $adminUrl,
            ':billing_day'          => $billingDay,
            ':subscription_ends_at' => $subscriptionEndsAt,
            ':id'                   => $id,
        ]);

        return true;
    }

    /**
     * Approve a store (Combined fallback for legacy/manual flow)
     */
    public function approveStore(int $id, array $data): bool
    {
        $domain = trim((string)($data['domain'] ?? ''));
        $adminUrl = trim((string)($data['admin_url'] ?? ''));
        $billingDay = (int)($data['billing_day'] ?? date('j'));
        $subscriptionEndsAt = !empty($data['subscription_ends_at']) 
            ? date('Y-m-d 23:59:59', strtotime($data['subscription_ends_at']))
            : date('Y-m-d 23:59:59', strtotime('+1 month'));

        $sql = "UPDATE `tenants` SET 
                `domain` = :domain,
                `admin_url` = :admin_url,
                `billing_day` = :billing_day,
                `subscription_ends_at` = :subscription_ends_at,
                `status` = 'active'
                WHERE `id` = :id";

        $this->query($sql, [
            ':domain'               => $domain,
            ':admin_url'            => $adminUrl,
            ':billing_day'          => $billingDay,
            ':subscription_ends_at' => $subscriptionEndsAt,
            ':id'                   => $id,
        ]);

        // Auto approve initial payment in tenant_payments table
        $this->query("UPDATE `tenant_payments` 
                      SET `status` = 'approved', `approved_at` = NOW() 
                      WHERE `tenant_id` = :id AND `status` = 'pending'", [
            ':id' => $id
        ]);

        return true;
    }

    /**
     * Stage 3: Extend Grace Period (+2 days, +5 days, +7 days, etc.)
     * CRITICAL RULE: billing_day remains strictly UNCHANGED!
     * Pushes temporary subscription_ends_at and logs extension in tenant_payments.
     */
    public function extendGracePeriod(int $id, int $days, ?string $note = null): bool
    {
        $tenant = $this->find($id);
        if (!$tenant) {
            return false;
        }

        $days = max(1, $days);

        // Calculate new expiry date based on current expiry or NOW
        $baseTime = (!empty($tenant['subscription_ends_at']) && strtotime($tenant['subscription_ends_at']) > time())
            ? strtotime($tenant['subscription_ends_at'])
            : time();

        $newDate = date('Y-m-d 23:59:59', strtotime("+{$days} days", $baseTime));

        // billing_day is NEVER updated here!
        $this->query("UPDATE `tenants` SET 
                      `subscription_ends_at` = :new_date,
                      `status` = 'active'
                      WHERE `id` = :id", [
            ':new_date' => $newDate,
            ':id'       => $id
        ]);

        // Log extension in tenant_payments for transparency & ledger
        $graceTrx = 'GRACE-' . strtoupper(substr(uniqid(), -6));
        $adminNote = $note ?: "গ্রেস পিরিয়ড বৃদ্ধি (+{$days} দিন)";

        $this->query("INSERT INTO `tenant_payments` (
            `tenant_id`, `tenant_uuid`, `store_name`, `sender_number`, `trx_id`,
            `amount`, `billing_month`, `payment_type`, `payment_method`, `status`,
            `admin_note`, `approved_at`, `created_at`
        ) VALUES (
            :tenant_id, :tenant_uuid, :store_name, :sender_number, :trx_id,
            0, :billing_month, 'extension', 'System', 'approved',
            :admin_note, NOW(), NOW()
        )", [
            ':tenant_id'      => $id,
            ':tenant_uuid'    => $tenant['uuid'],
            ':store_name'     => $tenant['name'],
            ':sender_number'  => $tenant['owner_phone'] ?? '',
            ':trx_id'         => $graceTrx,
            ':billing_month'  => date('F Y'),
            ':admin_note'     => $adminNote,
        ]);

        return true;
    }

    /**
     * Extend validity (Alias for backward compatibility)
     */
    public function extendValidity(int $id, int $days, ?string $customDate = null): bool
    {
        return $this->extendGracePeriod($id, $days, $customDate ? "কাস্টম মেয়াদ বৃদ্ধি: {$customDate}" : null);
    }

    /**
     * Record a monthly renewal payment and advance billing cycle
     */
    public function recordRenewalPayment(int $id, array $paymentData): bool
    {
        $tenant = $this->find($id);
        if (!$tenant) {
            return false;
        }

        $amount = (int)($paymentData['amount'] ?? $tenant['monthly_fee']);
        $senderNumber = (string)($paymentData['sender_number'] ?? $tenant['owner_phone']);
        $trxId = strtoupper(trim((string)($paymentData['trx_id'] ?? '')));
        $paymentMethod = (string)($paymentData['payment_method'] ?? 'bKash');
        $billingMonth = (string)($paymentData['billing_month'] ?? date('F Y'));
        $adminNote = (string)($paymentData['admin_note'] ?? 'মাসিক সাবস্ক্রিপশন নবায়ন পেমেন্ট');

        // Record in tenant_payments table
        $paySql = "INSERT INTO `tenant_payments` (
            `tenant_id`, `tenant_uuid`, `store_name`, `sender_number`, `trx_id`,
            `amount`, `billing_month`, `payment_type`, `payment_method`, `status`,
            `admin_note`, `approved_at`, `created_at`
        ) VALUES (
            :tenant_id, :tenant_uuid, :store_name, :sender_number, :trx_id,
            :amount, :billing_month, 'renewal', :payment_method, 'approved',
            :admin_note, NOW(), NOW()
        )";

        $this->query($paySql, [
            ':tenant_id'      => $id,
            ':tenant_uuid'    => $tenant['uuid'],
            ':store_name'     => $tenant['name'],
            ':sender_number'  => $senderNumber,
            ':trx_id'         => $trxId,
            ':amount'         => $amount,
            ':billing_month'  => $billingMonth,
            ':payment_method' => $paymentMethod,
            ':admin_note'     => $adminNote,
        ]);

        // Advance next subscription_ends_at date:
        // Use custom next date if provided, or calculate +1 month based on billing_day
        if (!empty($paymentData['next_due_date'])) {
            $nextDate = date('Y-m-d 23:59:59', strtotime($paymentData['next_due_date']));
        } else {
            $billingDay = (int)($tenant['billing_day'] ?? 10);
            $baseTime = (!empty($tenant['subscription_ends_at']) && strtotime($tenant['subscription_ends_at']) > time())
                ? strtotime($tenant['subscription_ends_at'])
                : time();
            $nextDate = date('Y-m-d 23:59:59', strtotime("+1 month", $baseTime));
        }

        // Update tenant
        $this->query("UPDATE `tenants` SET 
                      `subscription_ends_at` = :next_date,
                      `status` = 'active'
                      WHERE `id` = :id", [
            ':next_date' => $nextDate,
            ':id'        => $id,
        ]);

        return true;
    }

    /**
     * Delete tenant and payments
     */
    public function delete(int $id): bool
    {
        $this->query("DELETE FROM `tenant_payments` WHERE `tenant_id` = :id", [':id' => $id]);
        $stmt = $this->query("DELETE FROM `tenants` WHERE `id` = :id", [':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Update Tenant Hero Carousel Banners (Max 5)
     */
    public function updateBanners(string $uuid, array $banners): bool
    {
        $clean = array_values(array_slice($banners, 0, 5));
        $sql = "UPDATE `tenants` SET `banners` = :banners WHERE `uuid` = :uuid";
        $stmt = $this->query($sql, [
            ':uuid'    => $uuid,
            ':banners' => json_encode($clean, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ]);
        return true;
    }

    /**
     * Calculate next monthly billing date from a specific day (e.g. 10th of next month)
     */
    public static function calculateNextBillingDate(int $billingDay = 10): string
    {
        $billingDay = max(1, min(28, $billingDay)); // safely cap between 1 and 28
        $currentDay = (int)date('j');
        
        // If current day is before billing day, it's this month's billing day; otherwise next month
        if ($currentDay < $billingDay) {
            $target = date("Y-m-{$billingDay} 23:59:59");
        } else {
            $target = date("Y-m-{$billingDay} 23:59:59", strtotime('+1 month'));
        }

        return $target;
    }

    /**
     * Format a tenant row with dynamic badges, URLs, and days remaining
     */
    public static function formatRow(array $row): array
    {
        $row['id'] = (int)$row['id'];
        $row['monthly_fee'] = (int)$row['monthly_fee'];
        $row['product_limit'] = (int)($row['product_limit'] ?? 50);
        $row['billing_day'] = (int)($row['billing_day'] ?? 10);

        if (!empty($row['social_links'])) {
            $row['social_links'] = is_string($row['social_links'])
                ? (json_decode($row['social_links'], true) ?: [])
                : $row['social_links'];
            if (!is_array($row['social_links'])) {
                $row['social_links'] = [];
            }
        } else {
            $row['social_links'] = [];
        }

        if (!empty($row['banners'])) {
            $row['banners'] = is_string($row['banners'])
                ? (json_decode($row['banners'], true) ?: [])
                : $row['banners'];
            if (!is_array($row['banners'])) {
                $row['banners'] = [];
            }
        } else {
            $row['banners'] = [];
        }

        // Expiry & Days Left Calculation
        $daysLeft = null;
        $isExpiringSoon = false;
        $isOverdue = false;

        if (!empty($row['subscription_ends_at'])) {
            $endsAtTimestamp = strtotime($row['subscription_ends_at']);
            $diffSeconds = $endsAtTimestamp - time();
            $daysLeft = (int)ceil($diffSeconds / 86400);

            if ($diffSeconds <= 0) {
                $isOverdue = true;
                $daysLeft = 0;
                if ($row['status'] === 'active') {
                    $row['status'] = 'expired';
                }
            } elseif ($daysLeft <= 5 && $row['status'] === 'active') {
                $isExpiringSoon = true;
            }
        }

        if ($row['status'] === 'suspended' || $row['status'] === 'expired') {
            $isOverdue = true;
            $daysLeft = 0;
        }

        $row['days_left'] = $daysLeft;
        $row['is_expiring_soon'] = $isExpiringSoon;
        $row['is_overdue'] = $isOverdue;

        // Formatted dates
        $row['formatted_expiry'] = !empty($row['subscription_ends_at'])
            ? date('d M, Y', strtotime($row['subscription_ends_at']))
            : null;
        $row['formatted_created'] = !empty($row['created_at'])
            ? date('d M, Y', strtotime($row['created_at']))
            : null;

        // Generate full logo URL
        $baseUrl = \App\Core\Uploader::getBaseUrl();
        $row['logo_url'] = !empty($row['logo'])
            ? (str_starts_with($row['logo'], 'http') ? $row['logo'] : $baseUrl . '/' . ltrim($row['logo'], '/'))
            : null;

        // Generate full hero carousel banner URLs
        $row['banner_urls'] = [];
        if (!empty($row['banners']) && is_array($row['banners'])) {
            foreach ($row['banners'] as $b) {
                if (!empty($b)) {
                    $row['banner_urls'][] = str_starts_with($b, 'http') ? $b : $baseUrl . '/' . ltrim($b, '/');
                }
            }
        }

        // Subdomain URL preview
        $row['subdomain_url'] = "https://{$row['slug']}.ponnostack.shop";

        return $row;
    }

    /**
     * Generate standard cryptographically secure UUID v4
     */
    public static function generateUuidV4(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40); // set version to 0100
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80); // set bits 6-7 to 10

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }
}
