<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Admin Model
 * Real Database CRUD Operations for Admin Accounts
 */
class Admin extends Model
{
    /**
     * Get all admin records (excluding password hash)
     */
    public function all(): array
    {
        return $this->fetchAll(
            "SELECT `id`, `name`, `email`, `role`, `status`, `created_at`, `updated_at` 
             FROM `admins` 
             ORDER BY `id` ASC"
        );
    }

    /**
     * Find admin by ID
     */
    public function findById(int $id): ?array
    {
        return $this->fetch(
            "SELECT `id`, `name`, `email`, `role`, `status`, `created_at`, `updated_at` 
             FROM `admins` 
             WHERE `id` = :id",
            [':id' => $id]
        );
    }

    /**
     * Find admin by email (includes password hash for login verification)
     */
    public function findByEmail(string $email): ?array
    {
        return $this->fetch(
            "SELECT * FROM `admins` WHERE `email` = :email",
            [':email' => $email]
        );
    }

    /**
     * Create a new admin
     */
    public function create(array $data): int
    {
        $passwordHash = password_hash($data['password'], PASSWORD_BCRYPT);

        $sql = "INSERT INTO `admins` (`name`, `email`, `password`, `role`, `status`) 
                VALUES (:name, :email, :password, :role, :status)";

        $this->query($sql, [
            ':name'     => trim($data['name']),
            ':email'    => strtolower(trim($data['email'])),
            ':password' => $passwordHash,
            ':role'     => $data['role'] ?? 'admin',
            ':status'   => $data['status'] ?? 'active',
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Update an existing admin
     */
    public function update(int $id, array $data): bool
    {
        $fields = [
            '`name` = :name',
            '`email` = :email',
            '`role` = :role',
            '`status` = :status',
        ];
        $params = [
            ':id'     => $id,
            ':name'   => trim($data['name']),
            ':email'  => strtolower(trim($data['email'])),
            ':role'   => $data['role'] ?? 'admin',
            ':status' => $data['status'] ?? 'active',
        ];

        if (!empty($data['password'])) {
            $fields[] = '`password` = :password';
            $params[':password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }

        $sql = "UPDATE `admins` SET " . implode(', ', $fields) . " WHERE `id` = :id";
        $this->query($sql, $params);

        return true;
    }

    /**
     * Delete an admin by ID
     */
    public function delete(int $id): bool
    {
        $this->query("DELETE FROM `admins` WHERE `id` = :id", [':id' => $id]);
        return true;
    }

    /**
     * Count total super admins in the system
     */
    public function countSuperAdmins(): int
    {
        $res = $this->fetch("SELECT COUNT(*) AS total FROM `admins` WHERE `role` = 'super_admin'");
        return (int)($res['total'] ?? 0);
    }
}
