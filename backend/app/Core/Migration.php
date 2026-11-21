<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

/**
 * Automatic Database Migration & Schema Seeder
 * 
 * Automatically ensures required tables and default initial records
 * exist in the database without requiring manual phpMyAdmin SQL imports.
 */
class Migration
{
    /**
     * Run all migrations
     *
     * @return array Array of status logs
     */
    public static function run(): array
    {
        $db = Database::getConnection();
        $logs = [];

        // 1. Create 'admins' table if not exists
        $sqlAdmins = "CREATE TABLE IF NOT EXISTS `admins` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) NOT NULL,
            `email` VARCHAR(150) NOT NULL UNIQUE,
            `password` VARCHAR(255) NOT NULL,
            `role` ENUM('super_admin', 'admin') NOT NULL DEFAULT 'admin',
            `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_admin_email` (`email`),
            INDEX `idx_admin_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $db->exec($sqlAdmins);
        $logs[] = "Table 'admins' verified / created successfully.";

        // 2. Check if default super admin exists; if table is empty, auto-seed!
        $stmt = $db->query("SELECT COUNT(*) FROM `admins`");
        $count = (int)$stmt->fetchColumn();

        if ($count === 0) {
            $defaultPasswordHash = password_hash('Admin@123456', PASSWORD_BCRYPT);
            $seedStmt = $db->prepare(
                "INSERT INTO `admins` (`name`, `email`, `password`, `role`, `status`) 
                 VALUES (:name, :email, :password, :role, :status)"
            );
            $seedStmt->execute([
                ':name'     => 'Super Admin',
                ':email'    => 'admin@ponnostack.com',
                ':password' => $defaultPasswordHash,
                ':role'     => 'super_admin',
                ':status'   => 'active',
            ]);
            $logs[] = "Default Super Admin created: admin@ponnostack.com (Password: Admin@123456)";
        } else {
            $logs[] = "Admins already exist in database ({$count} accounts found).";
        }

        // 3. Create 'packages' table if not exists (Strictly 3 fixed packages)
        $sqlPackages = "CREATE TABLE IF NOT EXISTS `packages` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `slug` VARCHAR(50) NOT NULL UNIQUE,
            `name_bn` VARCHAR(100) NOT NULL,
            `name_en` VARCHAR(100) NOT NULL,
            `badge_bn` VARCHAR(100) NULL,
            `badge_en` VARCHAR(100) NULL,
            `price` INT UNSIGNED NOT NULL DEFAULT 200,
            `product_limit` INT NOT NULL DEFAULT 50,
            `billing_period_bn` VARCHAR(30) NOT NULL DEFAULT '/ মাস',
            `billing_period_en` VARCHAR(30) NOT NULL DEFAULT '/ month',
            `is_popular` TINYINT(1) NOT NULL DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_package_slug` (`slug`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $db->exec($sqlPackages);
        
        // Ensure product_limit column exists if upgraded from older schema
        $cols = $db->query("SHOW COLUMNS FROM `packages` LIKE 'product_limit'")->fetchAll();
        if (empty($cols)) {
            $db->exec("ALTER TABLE `packages` ADD COLUMN `product_limit` INT NOT NULL DEFAULT 50 AFTER `price`");
        }
        
        $logs[] = "Table 'packages' verified / created successfully.";

        // 4. Seed default 3 packages if table is empty
        $stmtPkg = $db->query("SELECT COUNT(*) FROM `packages`");
        $pkgCount = (int)$stmtPkg->fetchColumn();

        if ($pkgCount === 0) {
            $seedPkgStmt = $db->prepare(
                "INSERT INTO `packages` (`id`, `slug`, `name_bn`, `name_en`, `badge_bn`, `badge_en`, `price`, `product_limit`, `billing_period_bn`, `billing_period_en`, `is_popular`) 
                 VALUES 
                 (1, 'starter', 'স্ট্যান্ডার্ড প্যাকেজ', 'Standard Plan', 'সবচেয়ে জনপ্রিয়', 'Most Popular', 200, 50, '/ মাস', '/ month', 1),
                 (2, 'professional', 'প্রিমিয়াম প্যাকেজ', 'Premium Plan', 'গ্রোথ ফোকাস্ড', 'Growth Focused', 400, 100, '/ মাস', '/ month', 0),
                 (3, 'enterprise', 'এন্টারপ্রাইজ প্যাকেজ', 'Enterprise Plan', 'আনলিমিটেড স্কেল', 'Unlimited Scale', 800, 0, '/ মাস', '/ month', 0)"
            );
            $seedPkgStmt->execute();
            $logs[] = "Seeded 3 default packages: starter (৳200, 50 prods), professional (৳400, 100 prods), enterprise (৳800, Unlimited).";
        } else {
            $logs[] = "Packages already exist in database ({$pkgCount} packages found).";
        }

        return $logs;
    }
}
