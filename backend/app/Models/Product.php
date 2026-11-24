<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Models\Tenant;

/**
 * Product Model
 *
 * Handles database operations for tenant storefront products,
 * including multi-image storage, variants/sizes & colors with individual stock,
 * and category relations.
 */
class Product extends Model
{
    protected string $table = 'products';

    protected function execute(string $sql, array $params = []): bool
    {
        return $this->query($sql, $params)->rowCount() >= 0;
    }

    /**
     * Find single product by UUID
     */
    public function findByUuid(string $uuid, ?string $tenantUuid = null): ?array
    {
        return $this->findByIdentifier($uuid, $tenantUuid);
    }

    /**
     * Find single product by UUID or slug
     */
    public function findByIdentifier(string $identifier, ?string $tenantUuid = null): ?array
    {
        $sql = "SELECT p.*, c.name AS category_name, c.slug AS category_slug
                FROM {$this->table} p
                LEFT JOIN categories c ON p.category_uuid = c.uuid
                WHERE (p.uuid = :uuid_val OR p.slug = :slug_val)";
        $params = [
            'uuid_val' => $identifier,
            'slug_val' => $identifier,
        ];

        if ($tenantUuid !== null) {
            $sql .= " AND p.tenant_uuid = :tenant_uuid";
            $params['tenant_uuid'] = $tenantUuid;
        }

        $sql .= " LIMIT 1";
        $row = $this->fetch($sql, $params);

        return $row ? $this->formatProduct($row) : null;
    }

    /**
     * Find single product by slug and tenant
     */
    public function findBySlug(string $slug, string $tenantUuid): ?array
    {
        $sql = "SELECT p.*, c.name AS category_name, c.slug AS category_slug
                FROM {$this->table} p
                LEFT JOIN categories c ON p.category_uuid = c.uuid
                WHERE p.slug = :slug AND p.tenant_uuid = :tenant_uuid
                LIMIT 1";
        $row = $this->fetch($sql, ['slug' => $slug, 'tenant_uuid' => $tenantUuid]);

        return $row ? $this->formatProduct($row) : null;
    }

    /**
     * Retrieve all products for a tenant with optional filtering
     */
    public function allByTenant(string $tenantUuid, array $filters = []): array
    {
        $sql = "SELECT p.*, c.name AS category_name, c.slug AS category_slug
                FROM {$this->table} p
                LEFT JOIN categories c ON p.category_uuid = c.uuid
                WHERE p.tenant_uuid = :tenant_uuid";
        $params = ['tenant_uuid' => $tenantUuid];

        if (!empty($filters['category_uuid'])) {
            $sql .= " AND p.category_uuid = :category_uuid";
            $params['category_uuid'] = $filters['category_uuid'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND p.status = :status";
            $params['status'] = $filters['status'];
        }

        if (isset($filters['in_stock']) && $filters['in_stock'] === true) {
            $sql .= " AND p.stock > 0";
        } elseif (isset($filters['in_stock']) && $filters['in_stock'] === false) {
            $sql .= " AND p.stock <= 0";
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (p.name LIKE :search OR p.slug LIKE :search OR p.description LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['discount_only'])) {
            $now = date('Y-m-d H:i:s');
            $sql .= " AND p.discount_percent > 0 AND (p.discount_starts_at IS NULL OR p.discount_starts_at <= '{$now}') AND (p.discount_ends_at IS NULL OR p.discount_ends_at >= '{$now}')";
        }

        $sql .= " ORDER BY p.id DESC";

        if (!empty($filters['limit'])) {
            $limit = (int)$filters['limit'];
            $offset = (int)($filters['offset'] ?? 0);
            $sql .= " LIMIT {$offset}, {$limit}";
        }

        $rows = $this->fetchAll($sql, $params);

        return array_map([$this, 'formatProduct'], $rows);
    }

    /**
     * Count products for a tenant
     */
    public function countByTenant(string $tenantUuid, array $filters = []): int
    {
        $sql = "SELECT COUNT(*) as cnt FROM {$this->table} WHERE tenant_uuid = :tenant_uuid";
        $params = ['tenant_uuid' => $tenantUuid];

        if (!empty($filters['category_uuid'])) {
            $sql .= " AND category_uuid = :category_uuid";
            $params['category_uuid'] = $filters['category_uuid'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND status = :status";
            $params['status'] = $filters['status'];
        }

        $res = $this->fetch($sql, $params);
        return (int)($res['cnt'] ?? 0);
    }

    /**
     * Create a new product
     */
    public function create(array $data): ?array
    {
        $uuid = Tenant::generateUuidV4();

        $sizes = is_array($data['sizes'] ?? null) ? json_encode($data['sizes'], JSON_UNESCAPED_UNICODE) : ($data['sizes'] ?? '[]');
        $colors = is_array($data['colors'] ?? null) ? json_encode($data['colors'], JSON_UNESCAPED_UNICODE) : ($data['colors'] ?? '[]');
        $images = is_array($data['images'] ?? null) ? json_encode($data['images'], JSON_UNESCAPED_SLASHES) : ($data['images'] ?? '[]');

        $sql = "INSERT INTO {$this->table} (
            uuid, tenant_id, tenant_uuid, category_uuid, name, slug, description,
            main_price, retailer_price, discount_percent, discount_starts_at, discount_ends_at,
            stock, sizes, colors, images, status
        ) VALUES (
            :uuid, :tenant_id, :tenant_uuid, :category_uuid, :name, :slug, :description,
            :main_price, :retailer_price, :discount_percent, :discount_starts_at, :discount_ends_at,
            :stock, :sizes, :colors, :images, :status
        )";

        $params = [
            'uuid'               => $uuid,
            'tenant_id'          => (int)$data['tenant_id'],
            'tenant_uuid'        => $data['tenant_uuid'],
            'category_uuid'      => !empty($data['category_uuid']) ? $data['category_uuid'] : null,
            'name'               => trim((string)$data['name']),
            'slug'               => trim((string)$data['slug']),
            'description'        => $data['description'] ?? null,
            'main_price'         => (float)($data['main_price'] ?? 0),
            'retailer_price'     => (float)($data['retailer_price'] ?? 0),
            'discount_percent'   => (int)($data['discount_percent'] ?? 0),
            'discount_starts_at' => !empty($data['discount_starts_at']) ? $data['discount_starts_at'] : null,
            'discount_ends_at'   => !empty($data['discount_ends_at']) ? $data['discount_ends_at'] : null,
            'stock'              => (int)($data['stock'] ?? 0),
            'sizes'              => $sizes,
            'colors'             => $colors,
            'images'             => $images,
            'status'             => in_array($data['status'] ?? 'active', ['active', 'inactive'], true) ? $data['status'] : 'active',
        ];

        $this->execute($sql, $params);

        return $this->findByUuid($uuid, $data['tenant_uuid']);
    }

    /**
     * Update an existing product
     */
    public function update(string $uuid, array $data): bool
    {
        $fields = [];
        $params = ['uuid' => $uuid];

        $allowed = [
            'category_uuid', 'name', 'slug', 'description', 'main_price', 'retailer_price',
            'discount_percent', 'discount_starts_at', 'discount_ends_at', 'stock',
            'sizes', 'colors', 'images', 'status'
        ];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $val = $data[$field];
                if (in_array($field, ['sizes', 'colors', 'images'], true) && is_array($val)) {
                    $val = json_encode($val, $field === 'images' ? JSON_UNESCAPED_SLASHES : JSON_UNESCAPED_UNICODE);
                }
                $fields[] = "`{$field}` = :{$field}";
                $params[$field] = $val;
            }
        }

        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE uuid = :uuid";
        return $this->execute($sql, $params);
    }

    /**
     * Delete product by UUID
     */
    public function delete(string $uuid): bool
    {
        return $this->execute("DELETE FROM {$this->table} WHERE uuid = :uuid", ['uuid' => $uuid]);
    }

    /**
     * Set stock or adjust stock
     */
    public function adjustStock(string $uuid, int $change): bool
    {
        $sql = "UPDATE {$this->table} SET stock = GREATEST(0, stock + :change) WHERE uuid = :uuid";
        return $this->execute($sql, ['uuid' => $uuid, 'change' => $change]);
    }

    /**
     * Format raw product database row into clean structured response
     */
    public function formatProduct(array $row): array
    {
        $mainPrice = (float)($row['main_price'] ?? 0);
        $retailerPrice = (float)($row['retailer_price'] ?? 0);
        $profit = round($retailerPrice - $mainPrice, 2);
        $profitMargin = $retailerPrice > 0 ? round(($profit / $retailerPrice) * 100, 1) : 0;

        // Decode JSON fields
        $sizes = [];
        if (!empty($row['sizes'])) {
            $decoded = json_decode($row['sizes'], true);
            $sizes = is_array($decoded) ? $decoded : [];
        }

        $colors = [];
        if (!empty($row['colors'])) {
            $decoded = json_decode($row['colors'], true);
            $colors = is_array($decoded) ? $decoded : [];
        }

        $images = [];
        if (!empty($row['images'])) {
            $decoded = json_decode($row['images'], true);
            $images = is_array($decoded) ? $decoded : [];
        }

        $discountPercent   = (int)($row['discount_percent'] ?? 0);
        $discountStartsAt  = $row['discount_starts_at'] ?? null;
        $discountEndsAt    = $row['discount_ends_at'] ?? null;
        $now = date('Y-m-d H:i:s');
        $hasDiscount = false;
        $effectivePrice = $retailerPrice;

        if ($discountPercent > 0) {
            $started = empty($discountStartsAt) || $discountStartsAt <= $now;
            $notEnded = empty($discountEndsAt) || $discountEndsAt >= $now;
            if ($started && $notEnded) {
                $hasDiscount = true;
                $effectivePrice = round($retailerPrice * (1 - $discountPercent / 100), 2);
            }
        }

        return [
            'id'                 => (int)$row['id'],
            'uuid'               => $row['uuid'],
            'tenant_id'          => (int)$row['tenant_id'],
            'tenant_uuid'        => $row['tenant_uuid'],
            'category_uuid'      => $row['category_uuid'],
            'category_name'      => $row['category_name'] ?? 'Unnamed',
            'category_slug'      => $row['category_slug'] ?? 'unnamed',
            'name'               => $row['name'],
            'slug'               => $row['slug'],
            'description'        => $row['description'] ?? '',
            'main_price'         => $mainPrice,
            'retailer_price'     => $retailerPrice,
            'profit'             => $profit,
            'profit_margin'      => $profitMargin,
            'discount_percent'   => $discountPercent,
            'discount_starts_at' => $discountStartsAt,
            'discount_ends_at'   => $discountEndsAt,
            'has_discount'       => $hasDiscount,
            'effective_price'    => $effectivePrice,
            'stock'              => (int)($row['stock'] ?? 0),
            'sizes'              => $sizes,
            'colors'             => $colors,
            'images'             => $images,
            'status'             => $row['status'] ?? 'active',
            'created_at'         => $row['created_at'],
            'updated_at'         => $row['updated_at'],
        ];
    }
}
