<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOStatement;

/**
 * Base Model
 * Interacts with the database using PDO prepared statements.
 */
abstract class Model
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    protected function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    protected function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
    }

    protected function fetch(string $sql, array $params = []): ?array
    {
        $res = $this->query($sql, $params)->fetch(PDO::FETCH_ASSOC);
        return $res !== false ? $res : null;
    }
}
