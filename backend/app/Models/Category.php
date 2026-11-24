<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Models\Tenant;

/**
 * Category Model
 *
 * Handles database operations for tenant storefront categories.
 * Enforces the default "Unnamed" category for each tenant.
 */
class Category extends Model
{
    protected string $table = 'categories';

    protected function execute(string $sql, array $params = []): bool
    {
        return $this->query($sql, $params)->rowCount() >= 0;
    }

    /**
     * Ensure the default "Unnamed" category exists for a given tenant
     *
     * @param int $tenantId
     * @param string $tenantUuid
     * @return array The default "Unnamed" category row
     */
    public function ensureDefaultCategory(int $tenantId, string $tenantUuid): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE tenant_uuid = :tenant_uuid AND (is_default = 1 OR slug = 'unnamed') LIMIT 1";
        $default = $this->fetch($sql, ['tenant_uuid' => $tenantUuid]);

        if ($default) {
            return $default;
        }

        // Create default "Unnamed" category
        $uuid = Tenant::generateUuidV4();
        $insertSql = "INSERT INTO {$this->table} 
                      (uuid, tenant_id, tenant_uuid, name, slug, image, is_default)
                      VALUES 
                      (:uuid, :tenant_id, :tenant_uuid, :name, :slug, NULL, 1)";
        $this->execute($insertSql, [
            'uuid'        => $uuid,
            'tenant_id'   => $tenantId,
            'tenant_uuid' => $tenantUuid,
            'name'        => 'Unnamed',
            'slug'        => 'unnamed',
        ]);

        return $this->findByUuid($uuid) ?? [
            'uuid'        => $uuid,
            'tenant_id'   => $tenantId,
            'tenant_uuid' => $tenantUuid,
            'name'        => 'Unnamed',
            'slug'        => 'unnamed',
            'image'       => null,
            'is_default'  => 1,
        ];
    }

    /**
     * Get all categories for a specific tenant (with product counts)
     *
     * @param string $tenantUuid
     * @param bool $excludeDefault If true, excludes the default "Unnamed" category (e.g. for storefront)
     * @return array
     */
    public function allByTenant(string $tenantUuid, bool $excludeDefault = false): array
    {
        $sql = "SELECT c.*, 
                (SELECT COUNT(*) FROM products p WHERE p.category_uuid = c.uuid) AS product_count
                FROM {$this->table} c
                WHERE c.tenant_uuid = :tenant_uuid";

        if ($excludeDefault) {
            $sql .= " AND c.is_default = 0 AND c.slug != 'unnamed'";
        }

        $sql .= " ORDER BY c.is_default DESC, c.name ASC";

        return $this->fetchAll($sql, ['tenant_uuid' => $tenantUuid]);
    }

    /**
     * Find category by UUID
     *
     * @param string $uuid
     * @param string|null $tenantUuid
     * @return array|null
     */
    public function findByUuid(string $uuid, ?string $tenantUuid = null): ?array
    {
        $params = ['uuid' => $uuid];
        $sql = "SELECT c.*, 
                (SELECT COUNT(*) FROM products p WHERE p.category_uuid = c.uuid) AS product_count
                FROM {$this->table} c
                WHERE c.uuid = :uuid";

        if ($tenantUuid !== null) {
            $sql .= " AND c.tenant_uuid = :tenant_uuid";
            $params['tenant_uuid'] = $tenantUuid;
        }

        $sql .= " LIMIT 1";
        return $this->fetch($sql, $params);
    }

    /**
     * Find category by slug for a tenant
     *
     * @param string $slug
     * @param string $tenantUuid
     * @param string|null $excludeUuid
     * @return array|null
     */
    public function findBySlug(string $slug, string $tenantUuid, ?string $excludeUuid = null): ?array
    {
        $params = [
            'slug'        => $slug,
            'tenant_uuid' => $tenantUuid
        ];
        $sql = "SELECT * FROM {$this->table} WHERE slug = :slug AND tenant_uuid = :tenant_uuid";

        if ($excludeUuid !== null) {
            $sql .= " AND uuid != :exclude_uuid";
            $params['exclude_uuid'] = $excludeUuid;
        }

        $sql .= " LIMIT 1";
        return $this->fetch($sql, $params);
    }

    /**
     * Create a new category
     *
     * @param array $data
     * @return string Generated UUID
     */
    public function create(array $data): string
    {
        $uuid = Tenant::generateUuidV4();

        $sql = "INSERT INTO {$this->table}
                (uuid, tenant_id, tenant_uuid, name, slug, image, is_default)
                VALUES
                (:uuid, :tenant_id, :tenant_uuid, :name, :slug, :image, :is_default)";

        $this->execute($sql, [
            'uuid'        => $uuid,
            'tenant_id'   => (int)$data['tenant_id'],
            'tenant_uuid' => trim((string)$data['tenant_uuid']),
            'name'        => trim((string)$data['name']),
            'slug'        => strtolower(trim((string)$data['slug'])),
            'image'       => !empty($data['image']) ? trim((string)$data['image']) : null,
            'is_default'  => !empty($data['is_default']) ? 1 : 0,
        ]);

        return $uuid;
    }

    /**
     * Update an existing category
     *
     * @param string $uuid
     * @param array $data
     * @param string|null $tenantUuid
     * @return bool
     */
    public function update(string $uuid, array $data, ?string $tenantUuid = null): bool
    {
        $fields = [];
        $params = ['uuid' => $uuid];

        if (isset($data['name'])) {
            $fields[] = "`name` = :name";
            $params['name'] = trim((string)$data['name']);
        }
        if (isset($data['slug'])) {
            $fields[] = "`slug` = :slug";
            $params['slug'] = strtolower(trim((string)$data['slug']));
        }
        if (array_key_exists('image', $data)) {
            $fields[] = "`image` = :image";
            $params['image'] = !empty($data['image']) ? trim((string)$data['image']) : null;
        }

        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE `uuid` = :uuid";
        if ($tenantUuid !== null) {
            $sql .= " AND `tenant_uuid` = :tenant_uuid";
            $params['tenant_uuid'] = $tenantUuid;
        }

        return $this->execute($sql, $params);
    }

    /**
     * Delete a category (Default "Unnamed" cannot be deleted)
     *
     * @param string $uuid
     * @param string|null $tenantUuid
     * @return bool
     */
    public function delete(string $uuid, ?string $tenantUuid = null): bool
    {
        // Prevent deleting default category
        $cat = $this->findByUuid($uuid, $tenantUuid);
        if (!$cat || !empty($cat['is_default']) || strtolower($cat['slug']) === 'unnamed') {
            return false;
        }

        // Before deleting, reassign any products in this category to default "Unnamed"
        $defaultCat = $this->ensureDefaultCategory((int)$cat['tenant_id'], $cat['tenant_uuid']);
        $reassignSql = "UPDATE products SET category_uuid = :default_uuid WHERE category_uuid = :cat_uuid";
        $this->execute($reassignSql, [
            'default_uuid' => $defaultCat['uuid'],
            'cat_uuid'     => $uuid,
        ]);

        $sql = "DELETE FROM {$this->table} WHERE `uuid` = :uuid AND `is_default` = 0";
        $params = ['uuid' => $uuid];
        if ($tenantUuid !== null) {
            $sql .= " AND `tenant_uuid` = :tenant_uuid";
            $params['tenant_uuid'] = $tenantUuid;
        }

        return $this->execute($sql, $params);
    }
}
