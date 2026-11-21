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

SET FOREIGN_KEY_CHECKS = 1;
