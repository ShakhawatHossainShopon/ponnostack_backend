<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Models\Tenant;

/**
 * Delivery Charge Model
 *
 * Handles database operations for tenant delivery charges and zones.
 */
class DeliveryCharge extends Model
{
    protected string $table = 'delivery_charges';

    protected function execute(string $sql, array $params = []): bool
    {
        return $this->query($sql, $params)->rowCount() >= 0;
    }

    /**
     * Ensure default delivery charges (Inside Dhaka ৳80 & Outside Dhaka ৳150) exist for tenant
     */
    public function ensureDefaults(int $tenantId, string $tenantUuid): void
    {
        $existing = $this->fetchAll(
            "SELECT code FROM {$this->table} WHERE tenant_uuid = :tenant_uuid",
            ['tenant_uuid' => $tenantUuid]
        );
        $codes = array_column($existing, 'code');

        if (!in_array('inside_dhaka', $codes)) {
            $this->execute(
                "INSERT INTO {$this->table} 
                (uuid, tenant_id, tenant_uuid, title, code, amount, description, is_default, status, sort_order)
                VALUES (:uuid, :tenant_id, :tenant_uuid, :title, :code, :amount, :description, 1, 'active', 1)",
                [
                    'uuid'        => Tenant::generateUuidV4(),
                    'tenant_id'   => $tenantId,
                    'tenant_uuid' => $tenantUuid,
                    'title'       => 'ঢাকার ভেতরে (Inside Dhaka)',
                    'code'        => 'inside_dhaka',
                    'amount'      => 80.00,
                    'description' => 'হোম ডেলিভারি (২৪-৪৮ ঘন্টা)'
                ]
            );
        }

        if (!in_array('outside_dhaka', $codes)) {
            $this->execute(
                "INSERT INTO {$this->table} 
                (uuid, tenant_id, tenant_uuid, title, code, amount, description, is_default, status, sort_order)
                VALUES (:uuid, :tenant_id, :tenant_uuid, :title, :code, :amount, :description, 0, 'active', 2)",
                [
                    'uuid'        => Tenant::generateUuidV4(),
                    'tenant_id'   => $tenantId,
                    'tenant_uuid' => $tenantUuid,
                    'title'       => 'ঢাকার বাইরে (Outside Dhaka)',
                    'code'        => 'outside_dhaka',
                    'amount'      => 150.00,
                    'description' => 'কুরিয়ার ডেলিভারি (২-৩ কার্যদিবস)'
                ]
            );
        }
    }

    /**
     * Get all delivery charges for tenant
     */
    public function allByTenant(string $tenantUuid, bool $activeOnly = false): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE tenant_uuid = :tenant_uuid";
        if ($activeOnly) {
            $sql .= " AND status = 'active'";
        }
        $sql .= " ORDER BY sort_order ASC, id ASC";

        $rows = $this->fetchAll($sql, ['tenant_uuid' => $tenantUuid]);
        return array_map([$this, 'formatRow'], $rows);
    }

    /**
     * Find single record by uuid and tenant
     */
    public function findByUuid(string $uuid, ?string $tenantUuid = null): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE uuid = :uuid";
        $params = ['uuid' => $uuid];
        if ($tenantUuid) {
            $sql .= " AND tenant_uuid = :tenant_uuid";
            $params['tenant_uuid'] = $tenantUuid;
        }
        $row = $this->fetch($sql, $params);
        return $row ? $this->formatRow($row) : null;
    }

    /**
     * Create a new delivery charge
     */
    public function create(array $data): string
    {
        $uuid = Tenant::generateUuidV4();

        // If marked as default, unset other defaults
        if (!empty($data['is_default'])) {
            $this->execute(
                "UPDATE {$this->table} SET is_default = 0 WHERE tenant_uuid = :tenant_uuid",
                ['tenant_uuid' => $data['tenant_uuid']]
            );
        }

        $sql = "INSERT INTO {$this->table} 
                (uuid, tenant_id, tenant_uuid, title, code, amount, description, is_default, status, sort_order)
                VALUES 
                (:uuid, :tenant_id, :tenant_uuid, :title, :code, :amount, :description, :is_default, :status, :sort_order)";

        $this->execute($sql, [
            'uuid'        => $uuid,
            'tenant_id'   => (int)$data['tenant_id'],
            'tenant_uuid' => $data['tenant_uuid'],
            'title'       => trim($data['title']),
            'code'        => trim($data['code']),
            'amount'      => (float)($data['amount'] ?? 0.00),
            'description' => trim($data['description'] ?? ''),
            'is_default'  => !empty($data['is_default']) ? 1 : 0,
            'status'      => in_array($data['status'] ?? 'active', ['active', 'inactive']) ? $data['status'] : 'active',
            'sort_order'  => (int)($data['sort_order'] ?? 0)
        ]);

        return $uuid;
    }

    /**
     * Update an existing delivery charge
     */
    public function update(string $uuid, array $data, string $tenantUuid): bool
    {
        // If marked as default, unset other defaults
        if (!empty($data['is_default'])) {
            $this->execute(
                "UPDATE {$this->table} SET is_default = 0 WHERE tenant_uuid = :tenant_uuid",
                ['tenant_uuid' => $tenantUuid]
            );
        }

        $fields = [];
        $params = [
            'uuid'        => $uuid,
            'tenant_uuid' => $tenantUuid
        ];

        if (isset($data['title'])) {
            $fields[] = "`title` = :title";
            $params['title'] = trim($data['title']);
        }
        if (isset($data['code'])) {
            $fields[] = "`code` = :code";
            $params['code'] = trim($data['code']);
        }
        if (isset($data['amount'])) {
            $fields[] = "`amount` = :amount";
            $params['amount'] = (float)$data['amount'];
        }
        if (isset($data['description'])) {
            $fields[] = "`description` = :description";
            $params['description'] = trim($data['description']);
        }
        if (isset($data['is_default'])) {
            $fields[] = "`is_default` = :is_default";
            $params['is_default'] = !empty($data['is_default']) ? 1 : 0;
        }
        if (isset($data['status'])) {
            $fields[] = "`status` = :status";
            $params['status'] = in_array($data['status'], ['active', 'inactive']) ? $data['status'] : 'active';
        }
        if (isset($data['sort_order'])) {
            $fields[] = "`sort_order` = :sort_order";
            $params['sort_order'] = (int)$data['sort_order'];
        }

        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE uuid = :uuid AND tenant_uuid = :tenant_uuid";
        return $this->execute($sql, $params);
    }

    /**
     * Delete delivery charge
     */
    public function delete(string $uuid, string $tenantUuid): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE uuid = :uuid AND tenant_uuid = :tenant_uuid";
        return $this->execute($sql, [
            'uuid'        => $uuid,
            'tenant_uuid' => $tenantUuid
        ]);
    }

    /**
     * Format a delivery charge row
     */
    public function formatRow(array $row): array
    {
        $row['id'] = (int)$row['id'];
        $row['tenant_id'] = (int)$row['tenant_id'];
        $row['amount'] = (float)$row['amount'];
        $row['is_default'] = (int)$row['is_default'];
        $row['sort_order'] = (int)$row['sort_order'];
        return $row;
    }
}
