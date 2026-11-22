<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * ContactMessage Model
 * Handles database operations for customer inquiries and contact messages.
 */
class ContactMessage
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO `contact_messages` (`name`, `email`, `phone`, `subject`, `message`, `status`, `ip_address`)
            VALUES (:name, :email, :phone, :subject, :message, :status, :ip_address)
        ");

        $stmt->execute([
            ':name'       => trim((string)($data['name'] ?? '')),
            ':email'      => !empty($data['email']) ? strtolower(trim((string)$data['email'])) : null,
            ':phone'      => trim((string)($data['phone'] ?? '')),
            ':subject'    => !empty($data['subject']) ? trim((string)$data['subject']) : null,
            ':message'    => trim((string)($data['message'] ?? '')),
            ':status'     => $data['status'] ?? 'unread',
            ':ip_address' => $data['ip_address'] ?? ($_SERVER['REMOTE_ADDR'] ?? null),
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function getAll(?string $status = null, int $limit = 100, int $offset = 0): array
    {
        $sql = "SELECT * FROM `contact_messages`";
        $params = [];

        if ($status !== null && $status !== '' && $status !== 'all') {
            $sql .= " WHERE `status` = :status";
            $params[':status'] = $status;
        }

        $sql .= " ORDER BY `id` DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM `contact_messages` WHERE `id` = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function markAsRead(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE `contact_messages` SET `status` = 'read' WHERE `id` = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function updateStatus(int $id, string $status): bool
    {
        $stmt = $this->db->prepare("UPDATE `contact_messages` SET `status` = :status WHERE `id` = :id");
        return $stmt->execute([':id' => $status, ':id' => $id]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM `contact_messages` WHERE `id` = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function getUnreadCount(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM `contact_messages` WHERE `status` = 'unread'");
        return (int)$stmt->fetchColumn();
    }
}
