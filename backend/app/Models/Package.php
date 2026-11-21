<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Package Model
 * 
 * Manages the 3 fixed platform subscription packages.
 * Only the price (Taka) and product upload limit are editable per user specifications.
 */
class Package extends Model
{
    /**
     * Retrieve all 3 packages ordered by id
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAll(): array
    {
        $rows = $this->fetchAll("SELECT * FROM `packages` ORDER BY `id` ASC");
        
        return array_map([self::class, 'formatRow'], $rows);
    }

    /**
     * Find single package by ID
     */
    public function find(int $id): ?array
    {
        $row = $this->fetch("SELECT * FROM `packages` WHERE `id` = :id LIMIT 1", [':id' => $id]);
        if (!$row) {
            return null;
        }

        return self::formatRow($row);
    }

    /**
     * Find package by slug ('starter', 'professional', 'enterprise')
     */
    public function findBySlug(string $slug): ?array
    {
        $row = $this->fetch("SELECT * FROM `packages` WHERE `slug` = :slug LIMIT 1", [':slug' => $slug]);
        if (!$row) {
            return null;
        }

        return self::formatRow($row);
    }

    /**
     * Update price and product upload limit for a package
     */
    public function updatePackage(int $id, int $price, int $productLimit): bool
    {
        $stmt = $this->query(
            "UPDATE `packages` SET `price` = :price, `product_limit` = :product_limit WHERE `id` = :id LIMIT 1",
            [
                ':id'            => $id,
                ':price'         => $price,
                ':product_limit' => $productLimit,
            ]
        );

        return $stmt->rowCount() > 0;
    }

    /**
     * Format row with localized numbers and labels
     */
    public static function formatRow(array $row): array
    {
        $price = (int)$row['price'];
        $productLimit = (int)($row['product_limit'] ?? 0);

        $row['price'] = $price;
        $row['product_limit'] = $productLimit;

        $row['formatted_price_en'] = number_format($price);
        $row['formatted_price_bn'] = self::toBengaliDigits($price);

        if ($productLimit === 0) {
            $row['formatted_product_limit_bn'] = 'আনলিমিটেড প্রোডাক্ট আপলোড';
            $row['formatted_product_limit_en'] = 'Unlimited Products Upload';
            $row['product_limit_label_bn']     = 'আনলিমিটেড';
            $row['product_limit_label_en']     = 'Unlimited';
        } else {
            $row['formatted_product_limit_bn'] = self::toBengaliDigits($productLimit) . 'টি প্রোডাক্ট আপলোড';
            $row['formatted_product_limit_en'] = $productLimit . ' Products Upload';
            $row['product_limit_label_bn']     = self::toBengaliDigits($productLimit) . ' টি';
            $row['product_limit_label_en']     = $productLimit . ' Products';
        }

        return $row;
    }

    /**
     * Convert English digits to Bengali numerals
     */
    public static function toBengaliDigits(int|string $number): string
    {
        $en = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $bn = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];
        return str_replace($en, $bn, (string)$number);
    }
}
