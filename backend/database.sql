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

SET FOREIGN_KEY_CHECKS = 1;
