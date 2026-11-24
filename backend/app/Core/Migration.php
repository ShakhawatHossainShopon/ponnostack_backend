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

        // 5. Create 'tenants' table if not exists
        $sqlTenants = "CREATE TABLE IF NOT EXISTS `tenants` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `uuid` VARCHAR(36) NOT NULL UNIQUE,
            `name` VARCHAR(150) NOT NULL,
            `slug` VARCHAR(150) NOT NULL UNIQUE,
            `domain` VARCHAR(255) DEFAULT NULL,
            `admin_url` VARCHAR(255) DEFAULT NULL,
            `owner_name` VARCHAR(100) NOT NULL,
            `owner_email` VARCHAR(150) NOT NULL,
            `password` VARCHAR(255) NOT NULL,
            `owner_phone` VARCHAR(50) DEFAULT NULL,
            `address` TEXT DEFAULT NULL,
            `logo` VARCHAR(255) DEFAULT NULL,
            `banners` TEXT DEFAULT NULL,
            `currency` VARCHAR(10) DEFAULT 'BDT',
            `monthly_fee` INT UNSIGNED NOT NULL DEFAULT 200,
            `package_name` VARCHAR(100) DEFAULT 'স্ট্যান্ডার্ড প্যাকেজ',
            `package_slug` VARCHAR(50) DEFAULT 'starter',
            `product_limit` INT NOT NULL DEFAULT 50,
            `payment_method` VARCHAR(50) DEFAULT 'bKash',
            `payment_sender` VARCHAR(50) DEFAULT NULL,
            `payment_trx_id` VARCHAR(100) DEFAULT NULL,
            `billing_day` INT NOT NULL DEFAULT 10,
            `social_links` LONGTEXT DEFAULT NULL,
            `template_id` VARCHAR(50) NOT NULL DEFAULT 'template_1',
            `color_palette` VARCHAR(50) NOT NULL DEFAULT 'emerald',
            `subscription_ends_at` TIMESTAMP NULL DEFAULT NULL,
            `status` ENUM('payment_pending','setup_pending','active','suspended','expired','inactive') DEFAULT 'payment_pending',
            `source` ENUM('website','admin') NOT NULL DEFAULT 'website',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_tenant_slug` (`slug`),
            INDEX `idx_tenant_uuid` (`uuid`),
            INDEX `idx_tenant_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $db->exec($sqlTenants);
        $logs[] = "Table 'tenants' verified / created successfully.";

        // 6. Create 'tenant_payments' table if not exists
        $sqlPayments = "CREATE TABLE IF NOT EXISTS `tenant_payments` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `invoice_no` VARCHAR(50) DEFAULT NULL UNIQUE,
            `tenant_id` INT UNSIGNED NOT NULL,
            `tenant_uuid` VARCHAR(36) NOT NULL,
            `store_name` VARCHAR(150) NOT NULL,
            `sender_number` VARCHAR(30) NOT NULL,
            `trx_id` VARCHAR(100) NOT NULL,
            `amount` INT UNSIGNED NOT NULL DEFAULT 200,
            `billing_month` VARCHAR(50) DEFAULT NULL,
            `payment_type` ENUM('initial','renewal','extension') DEFAULT 'renewal',
            `payment_method` VARCHAR(30) DEFAULT 'bKash',
            `status` ENUM('pending','approved','rejected') DEFAULT 'pending',
            `admin_note` TEXT DEFAULT NULL,
            `approved_at` TIMESTAMP NULL DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_pay_tenant_uuid` (`tenant_uuid`),
            INDEX `idx_pay_status` (`status`),
            INDEX `idx_pay_invoice_no` (`invoice_no`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $db->exec($sqlPayments);

        // Ensure invoice_no column exists in tenant_payments
        $cols = $db->query("SHOW COLUMNS FROM `tenant_payments` LIKE 'invoice_no'")->fetchAll();
        if (empty($cols)) {
            $db->exec("ALTER TABLE `tenant_payments` ADD COLUMN `invoice_no` VARCHAR(50) DEFAULT NULL UNIQUE AFTER `id`");
            $rows = $db->query("SELECT `id`, `created_at` FROM `tenant_payments` WHERE `invoice_no` IS NULL ORDER BY `id` ASC")->fetchAll();
            foreach ($rows as $r) {
                $invNo = 'INV-' . date('Ym', strtotime($r['created_at'] ?? 'now')) . '-' . str_pad((string)$r['id'], 4, '0', STR_PAD_LEFT);
                $db->exec("UPDATE `tenant_payments` SET `invoice_no` = '{$invNo}' WHERE `id` = {$r['id']}");
            }
        }
        $logs[] = "Table 'tenant_payments' verified / created successfully.";

        // 7. Create 'contact_messages' table if not exists
        $sqlMessages = "CREATE TABLE IF NOT EXISTS `contact_messages` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(150) NOT NULL,
            `email` VARCHAR(150) DEFAULT NULL,
            `phone` VARCHAR(50) NOT NULL,
            `subject` VARCHAR(255) DEFAULT NULL,
            `message` TEXT NOT NULL,
            `status` ENUM('unread','read','replied','archived') DEFAULT 'unread',
            `ip_address` VARCHAR(45) DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_msg_status` (`status`),
            INDEX `idx_msg_created` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $db->exec($sqlMessages);
        $logs[] = "Table 'contact_messages' verified / created successfully.";

        // 8. Create 'delivery_charges' table if not exists
        $sqlDelivery = "CREATE TABLE IF NOT EXISTS `delivery_charges` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `uuid` VARCHAR(36) NOT NULL UNIQUE,
            `tenant_id` INT UNSIGNED NOT NULL,
            `tenant_uuid` VARCHAR(36) NOT NULL,
            `title` VARCHAR(150) NOT NULL,
            `code` VARCHAR(50) NOT NULL,
            `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `description` VARCHAR(255) DEFAULT NULL,
            `is_default` TINYINT(1) NOT NULL DEFAULT 0,
            `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
            `sort_order` INT NOT NULL DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_del_tenant_uuid` (`tenant_uuid`),
            INDEX `idx_del_code` (`code`),
            INDEX `idx_del_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $db->exec($sqlDelivery);
        $logs[] = "Table 'delivery_charges' verified / created successfully.";

        // 9. Create 'categories' table if not exists
        $sqlCategories = "CREATE TABLE IF NOT EXISTS `categories` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `uuid` VARCHAR(36) NOT NULL UNIQUE,
            `tenant_id` INT UNSIGNED NOT NULL,
            `tenant_uuid` VARCHAR(36) NOT NULL,
            `name` VARCHAR(150) NOT NULL,
            `slug` VARCHAR(150) NOT NULL,
            `image` VARCHAR(255) DEFAULT NULL,
            `is_default` TINYINT(1) NOT NULL DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_cat_tenant_uuid` (`tenant_uuid`),
            INDEX `idx_cat_tenant_slug` (`tenant_uuid`, `slug`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $db->exec($sqlCategories);
        $logs[] = "Table 'categories' verified / created successfully.";

        // 10. Create 'products' table if not exists
        $sqlProducts = "CREATE TABLE IF NOT EXISTS `products` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `uuid` VARCHAR(36) NOT NULL UNIQUE,
            `tenant_id` INT UNSIGNED NOT NULL,
            `tenant_uuid` VARCHAR(36) NOT NULL,
            `category_uuid` VARCHAR(36) DEFAULT NULL,
            `name` VARCHAR(255) NOT NULL,
            `slug` VARCHAR(255) NOT NULL,
            `description` TEXT DEFAULT NULL,
            `main_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            `retailer_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            `discount_percent` INT NOT NULL DEFAULT 0,
            `discount_starts_at` DATETIME DEFAULT NULL,
            `discount_ends_at` DATETIME DEFAULT NULL,
            `stock` INT NOT NULL DEFAULT 0,
            `sizes` LONGTEXT DEFAULT NULL,
            `colors` LONGTEXT DEFAULT NULL,
            `images` LONGTEXT DEFAULT NULL,
            `status` ENUM('active','inactive') DEFAULT 'active',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_prod_tenant_uuid` (`tenant_uuid`),
            INDEX `idx_prod_category_uuid` (`category_uuid`),
            INDEX `idx_prod_slug` (`slug`),
            INDEX `idx_prod_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $db->exec($sqlProducts);
        $logs[] = "Table 'products' verified / created successfully.";

        return $logs;
    }
}
