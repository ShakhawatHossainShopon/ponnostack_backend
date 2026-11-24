<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use Exception;
use PDO;

/**
 * Order Model
 *
 * Handles database operations for tenant orders and line items.
 * Implements transaction safety and automatic stock deduction/restoration.
 */
class Order extends Model
{
    protected string $table = 'orders';
    protected string $itemsTable = 'order_items';

    /**
     * Generate UUID v4
     */
    public static function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * Create a new order with line items in a single transaction
     *
     * @param array $orderData
     * @param array $items
     * @return array Created order details
     * @throws Exception
     */
    public function createOrder(array $orderData, array $items): array
    {
        $this->db->beginTransaction();

        try {
            $orderUuid = self::generateUuid();
            
            // Generate clean invoice number (e.g. ORD-261125-A1B2)
            $orderNumber = 'ORD-' . date('ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));

            // Ensure uniqueness
            $checkSql = "SELECT id FROM {$this->table} WHERE order_number = :order_number LIMIT 1";
            while ($this->fetch($checkSql, ['order_number' => $orderNumber])) {
                $orderNumber = 'ORD-' . date('ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
            }

            $insertOrderSql = "INSERT INTO {$this->table} (
                uuid, order_number, tenant_id, tenant_uuid, customer_name, customer_phone, customer_email,
                division, district, upazilla, delivery_address, delivery_zone,
                delivery_charge, subtotal, total_amount, payment_method, status, notes
            ) VALUES (
                :uuid, :order_number, :tenant_id, :tenant_uuid, :customer_name, :customer_phone, :customer_email,
                :division, :district, :upazilla, :delivery_address, :delivery_zone,
                :delivery_charge, :subtotal, :total_amount, :payment_method, 'pending', :notes
            )";

            $this->query($insertOrderSql, [
                'uuid'             => $orderUuid,
                'order_number'     => $orderNumber,
                'tenant_id'        => (int)$orderData['tenant_id'],
                'tenant_uuid'      => $orderData['tenant_uuid'],
                'customer_name'    => trim($orderData['customer_name']),
                'customer_phone'   => trim($orderData['customer_phone']),
                'customer_email'   => !empty($orderData['customer_email']) ? trim($orderData['customer_email']) : null,
                'division'         => trim($orderData['division']),
                'district'         => trim($orderData['district']),
                'upazilla'         => trim($orderData['upazilla']),
                'delivery_address' => trim($orderData['delivery_address']),
                'delivery_zone'    => $orderData['delivery_zone'] ?? 'inside_dhaka',
                'delivery_charge'  => (float)($orderData['delivery_charge'] ?? 60.00),
                'subtotal'         => (float)($orderData['subtotal'] ?? 0.00),
                'total_amount'     => (float)($orderData['total_amount'] ?? 0.00),
                'payment_method'   => $orderData['payment_method'] ?? 'cash_on_delivery',
                'notes'            => !empty($orderData['notes']) ? trim($orderData['notes']) : null,
            ]);

            $orderId = (int)$this->db->lastInsertId();

            // Insert line items & deduct product stock
            $insertItemSql = "INSERT INTO {$this->itemsTable} (
                order_id, order_uuid, product_id, product_uuid, product_name, product_image,
                size, color, unit_price, quantity, total_price
            ) VALUES (
                :order_id, :order_uuid, :product_id, :product_uuid, :product_name, :product_image,
                :size, :color, :unit_price, :quantity, :total_price
            )";

            foreach ($items as $item) {
                $qty = max(1, (int)($item['quantity'] ?? 1));
                $unitPrice = (float)($item['unit_price'] ?? 0);
                $totalPrice = $unitPrice * $qty;

                $this->query($insertItemSql, [
                    'order_id'      => $orderId,
                    'order_uuid'    => $orderUuid,
                    'product_id'    => !empty($item['product_id']) ? (int)$item['product_id'] : null,
                    'product_uuid'  => $item['product_uuid'],
                    'product_name'  => $item['product_name'],
                    'product_image' => $item['product_image'] ?? null,
                    'size'          => !empty($item['size']) ? trim($item['size']) : null,
                    'color'         => !empty($item['color']) ? trim($item['color']) : null,
                    'unit_price'    => $unitPrice,
                    'quantity'      => $qty,
                    'total_price'   => $totalPrice,
                ]);

                // Deduct stock from products table (and variant stocks if applicable)
                $this->adjustProductStock($item['product_uuid'], $orderData['tenant_uuid'], -$qty, $item['size'] ?? null, $item['color'] ?? null);
            }

            $this->db->commit();

            return $this->findByUuid($orderUuid) ?? [];

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Adjust overall and variant stock of a product
     * Positive $delta adds stock, negative $delta deducts stock
     */
    protected function adjustProductStock(string $productUuid, string $tenantUuid, int $delta, ?string $size = null, ?string $color = null): void
    {
        // 1. Fetch current product record
        $prod = $this->fetch("SELECT id, stock, sizes, colors FROM products WHERE uuid = :uuid AND tenant_uuid = :t_uuid LIMIT 1", [
            'uuid'   => $productUuid,
            't_uuid' => $tenantUuid
        ]);

        if (!$prod) {
            return;
        }

        $newStock = max(0, (int)$prod['stock'] + $delta);

        // 2. Adjust variant stock in sizes JSON if size matches
        $sizesJson = $prod['sizes'];
        if (!empty($size) && !empty($sizesJson)) {
            $sizesArr = json_decode((string)$sizesJson, true);
            if (is_array($sizesArr)) {
                $modified = false;
                foreach ($sizesArr as &$sItem) {
                    $sName = is_array($sItem) ? ($sItem['name'] ?? $sItem['label'] ?? '') : (string)$sItem;
                    if (strcasecmp(trim($sName), trim($size)) === 0) {
                        if (is_array($sItem) && isset($sItem['stock'])) {
                            $sItem['stock'] = max(0, (int)$sItem['stock'] + $delta);
                            $modified = true;
                        }
                    }
                }
                if ($modified) {
                    $sizesJson = json_encode($sizesArr, JSON_UNESCAPED_UNICODE);
                }
            }
        }

        // 3. Adjust variant stock in colors JSON if color matches
        $colorsJson = $prod['colors'];
        if (!empty($color) && !empty($colorsJson)) {
            $colorsArr = json_decode((string)$colorsJson, true);
            if (is_array($colorsArr)) {
                $modified = false;
                foreach ($colorsArr as &$cItem) {
                    $cName = is_array($cItem) ? ($cItem['name'] ?? $cItem['label'] ?? '') : (string)$cItem;
                    if (strcasecmp(trim($cName), trim($color)) === 0) {
                        if (is_array($cItem) && isset($cItem['stock'])) {
                            $cItem['stock'] = max(0, (int)$cItem['stock'] + $delta);
                            $modified = true;
                        }
                    }
                }
                if ($modified) {
                    $colorsJson = json_encode($colorsArr, JSON_UNESCAPED_UNICODE);
                }
            }
        }

        // 4. Update product
        $this->query("UPDATE products SET stock = :stock, sizes = :sizes, colors = :colors WHERE id = :id", [
            'stock'  => $newStock,
            'sizes'  => $sizesJson,
            'colors' => $colorsJson,
            'id'     => $prod['id']
        ]);
    }

    /**
     * Find order by UUID with all line items
     */
    public function findByUuid(string $uuid, ?string $tenantUuid = null): ?array
    {
        $params = ['uuid' => $uuid];
        $sql = "SELECT * FROM {$this->table} WHERE uuid = :uuid";
        if ($tenantUuid) {
            $sql .= " AND tenant_uuid = :tenant_uuid";
            $params['tenant_uuid'] = $tenantUuid;
        }
        $sql .= " LIMIT 1";

        $order = $this->fetch($sql, $params);
        if (!$order) {
            return null;
        }

        $order['items'] = $this->getOrderItems($order['uuid']);
        return $order;
    }

    /**
     * Find order by Order Number (Invoice ID) for tracking
     */
    public function findByOrderNumber(string $orderNumber, ?string $tenantUuid = null): ?array
    {
        $orderNumber = trim($orderNumber);
        $params = ['order_number' => $orderNumber];
        $sql = "SELECT * FROM {$this->table} WHERE order_number = :order_number";
        if ($tenantUuid) {
            $sql .= " AND tenant_uuid = :tenant_uuid";
            $params['tenant_uuid'] = $tenantUuid;
        }
        $sql .= " LIMIT 1";

        $order = $this->fetch($sql, $params);
        if (!$order) {
            return null;
        }

        $order['items'] = $this->getOrderItems($order['uuid']);
        return $order;
    }

    /**
     * Fetch line items for an order
     */
    public function getOrderItems(string $orderUuid): array
    {
        $sql = "SELECT * FROM {$this->itemsTable} WHERE order_uuid = :order_uuid ORDER BY id ASC";
        return $this->fetchAll($sql, ['order_uuid' => $orderUuid]);
    }

    /**
     * Get all orders for a tenant with filtering and search
     */
    public function allByTenant(string $tenantUuid, array $filters = []): array
    {
        $params = ['tenant_uuid' => $tenantUuid];
        $sql = "SELECT o.*, 
                (SELECT COUNT(*) FROM {$this->itemsTable} i WHERE i.order_uuid = o.uuid) AS total_items_count,
                (SELECT SUM(quantity) FROM {$this->itemsTable} i WHERE i.order_uuid = o.uuid) AS total_qty_count
                FROM {$this->table} o
                WHERE o.tenant_uuid = :tenant_uuid";

        // Status Filter
        if (!empty($filters['status'])) {
            $st = strtolower(trim($filters['status']));
            if ($st === 'active') {
                $sql .= " AND o.status IN ('pending', 'processing')";
            } elseif (in_array($st, ['pending', 'processing', 'delivered', 'cancel'])) {
                $sql .= " AND o.status = :status";
                $params['status'] = $st;
            }
        }

        // Search Filter (by order_number, customer_name, customer_phone)
        if (!empty($filters['search'])) {
            $sql .= " AND (o.order_number LIKE :search OR o.customer_name LIKE :search OR o.customer_phone LIKE :search)";
            $params['search'] = '%' . trim($filters['search']) . '%';
        }

        $sql .= " ORDER BY o.created_at DESC";

        if (!empty($filters['limit'])) {
            $limit = (int)$filters['limit'];
            $offset = (int)($filters['offset'] ?? 0);
            $sql .= " LIMIT {$offset}, {$limit}";
        }

        $orders = $this->fetchAll($sql, $params);

        // Attach line items preview for each order
        foreach ($orders as &$ord) {
            $ord['items'] = $this->getOrderItems($ord['uuid']);
        }

        return $orders;
    }

    /**
     * Update order status (pending, processing, delivered, cancel)
     * Restores stock if transitioning to cancel, re-deducts if restoring from cancel
     */
    public function updateStatus(string $uuid, string $newStatus, ?string $tenantUuid = null): bool
    {
        $validStatuses = ['pending', 'processing', 'delivered', 'cancel'];
        if (!in_array($newStatus, $validStatuses)) {
            return false;
        }

        $order = $this->findByUuid($uuid, $tenantUuid);
        if (!$order) {
            return false;
        }

        $prevStatus = $order['status'];
        if ($prevStatus === $newStatus) {
            return true;
        }

        $this->db->beginTransaction();

        try {
            $params = ['status' => $newStatus, 'uuid' => $uuid];
            $sql = "UPDATE {$this->table} SET status = :status WHERE uuid = :uuid";
            if ($tenantUuid) {
                $sql .= " AND tenant_uuid = :tenant_uuid";
                $params['tenant_uuid'] = $tenantUuid;
            }

            $this->query($sql, $params);

            // If transitioning to 'cancel' from a non-cancel state, restore product stocks!
            if ($newStatus === 'cancel' && $prevStatus !== 'cancel') {
                $items = $this->getOrderItems($uuid);
                foreach ($items as $item) {
                    $this->adjustProductStock($item['product_uuid'], $order['tenant_uuid'], (int)$item['quantity'], $item['size'] ?? null, $item['color'] ?? null);
                }
            }

            // If transitioning away from 'cancel' back to active, re-deduct stocks!
            if ($prevStatus === 'cancel' && $newStatus !== 'cancel') {
                $items = $this->getOrderItems($uuid);
                foreach ($items as $item) {
                    $this->adjustProductStock($item['product_uuid'], $order['tenant_uuid'], -(int)$item['quantity'], $item['size'] ?? null, $item['color'] ?? null);
                }
            }

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Get counts and stats for tenant orders
     */
    public function getOrderStats(string $tenantUuid): array
    {
        $sql = "SELECT 
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending,
                SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) AS processing,
                SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) AS delivered,
                SUM(CASE WHEN status = 'cancel' THEN 1 ELSE 0 END) AS cancel,
                SUM(CASE WHEN status = 'delivered' THEN subtotal ELSE 0 END) AS delivered_item_revenue,
                SUM(CASE WHEN status = 'delivered' THEN delivery_charge ELSE 0 END) AS delivered_delivery_fee,
                SUM(CASE WHEN status = 'delivered' THEN total_amount ELSE 0 END) AS delivered_revenue
                FROM {$this->table}
                WHERE tenant_uuid = :tenant_uuid";

        $res = $this->fetch($sql, ['tenant_uuid' => $tenantUuid]);
        return [
            'total'                  => (int)($res['total'] ?? 0),
            'pending'                => (int)($res['pending'] ?? 0),
            'processing'             => (int)($res['processing'] ?? 0),
            'delivered'              => (int)($res['delivered'] ?? 0),
            'cancel'                 => (int)($res['cancel'] ?? 0),
            'delivered_item_revenue' => (float)($res['delivered_item_revenue'] ?? 0.00),
            'delivered_delivery_fee' => (float)($res['delivered_delivery_fee'] ?? 0.00),
            'delivered_revenue'      => (float)($res['delivered_revenue'] ?? 0.00),
        ];
    }

    /**
     * Purge cancelled orders older than given days (default 30 days) like a trash bin
     */
    public function purgeOldCancelledOrders(string $tenantUuid, int $days = 30): int
    {
        try {
            $sql = "DELETE FROM {$this->table} 
                    WHERE tenant_uuid = :tenant_uuid 
                      AND status = 'cancel' 
                      AND (
                          (updated_at IS NOT NULL AND updated_at < DATE_SUB(NOW(), INTERVAL :days DAY))
                          OR (updated_at IS NULL AND created_at < DATE_SUB(NOW(), INTERVAL :days2 DAY))
                      )";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':tenant_uuid', $tenantUuid);
            $stmt->bindValue(':days', (int)$days, PDO::PARAM_INT);
            $stmt->bindValue(':days2', (int)$days, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->rowCount();
        } catch (Exception $e) {
            error_log("Purge old cancelled orders failed: " . $e->getMessage());
            return 0;
        }
    }
}
