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

        return $logs;
    }
}
