-- =======================================================================
-- Ponnostack Multi-Tenant Platform - Database Schema & Initial Seeds
-- Version: Clean Rebuild v2.0
-- Generated: 2026-11-21
-- Note: Always kept updated with every database change as a backup/reference.
-- =======================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- --------------------------------------------------------
-- Table structure for table `admins`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `admins` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Initial Seed for table `admins`
-- Default Super Admin (admin@ponnostack.com / Admin@123456)
-- --------------------------------------------------------

INSERT INTO `admins` (`id`, `name`, `email`, `password`, `role`, `status`) 
VALUES (
    1,
    'Super Admin',
    'admin@ponnostack.com',
    '$2y$10$wKz0bBq1u2o.C5eM4E7Bke4N5H0yNqWkI6E7t5rO3qU1l1o2p3q4e', -- Bcrypt for Admin@123456
    'super_admin',
    'active'
) ON DUPLICATE KEY UPDATE `email` = VALUES(`email`);

-- --------------------------------------------------------
-- Table structure for table `packages`
-- Strictly 3 fixed packages. Only price (Taka) is editable.
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `packages` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Initial Seed for table `packages`
-- --------------------------------------------------------

INSERT INTO `packages` (`id`, `slug`, `name_bn`, `name_en`, `badge_bn`, `badge_en`, `price`, `product_limit`, `billing_period_bn`, `billing_period_en`, `is_popular`) 
VALUES 
(1, 'starter', 'স্ট্যান্ডার্ড প্যাকেজ', 'Standard Plan', 'সবচেয়ে জনপ্রিয়', 'Most Popular', 200, 50, '/ মাস', '/ month', 1),
(2, 'professional', 'প্রিমিয়াম প্যাকেজ', 'Premium Plan', 'গ্রোথ ফোকাস্ড', 'Growth Focused', 400, 100, '/ মাস', '/ month', 0),
(3, 'enterprise', 'এন্টারপ্রাইজ প্যাকেজ', 'Enterprise Plan', 'আনলিমিটেড স্কেল', 'Unlimited Scale', 800, 0, '/ মাস', '/ month', 0)
ON DUPLICATE KEY UPDATE `id` = VALUES(`id`);

-- --------------------------------------------------------
-- Table structure for table `tenants`
-- Multi-tenant merchant storefront stores
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `tenants` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `tenant_payments`
-- Store subscription payments & bKash transactions
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `tenant_payments` (
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
    INDEX `idx_pay_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `contact_messages`
-- Customer inquiries & contact form messages
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `contact_messages` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
