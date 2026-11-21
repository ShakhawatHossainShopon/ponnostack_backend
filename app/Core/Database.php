<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

/**
 * High-Performance Singleton Database Connection Manager
 * 
 * Configured dynamically via environment variables (.env).
 */
class Database
{
    private static ?PDO $instance = null;

    /**
     * Get or initialize the active PDO connection
     */
    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $host     = env('DB_HOST', 'localhost');
            $port     = (int)env('DB_PORT', 3306);
            $database = env('DB_DATABASE', 'ponnostack_db');
            $username = env('DB_USERNAME', 'root');
            $password = env('DB_PASSWORD', '');

            $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_TIMEOUT            => 5,
            ];

            self::$instance = new PDO($dsn, $username, $password, $options);
        }

        return self::$instance;
    }

    /**
     * Test connection and retrieve system diagnostic metadata
     *
     * @return array{connected: bool, host: string, database: string, user: string, version: string, error: ?string}
     */
    public static function testConnection(): array
    {
        $host     = (string)env('DB_HOST', 'localhost');
        $database = (string)env('DB_DATABASE', 'ponnostack_db');
        $user     = (string)env('DB_USERNAME', 'root');

        try {
            $pdo = self::getConnection();
            $version = (string)$pdo->query('SELECT VERSION()')->fetchColumn();

            return [
                'connected' => true,
                'host'      => $host,
                'database'  => $database,
                'user'      => $user,
                'version'   => $version,
                'error'     => null,
            ];
        } catch (PDOException $e) {
            return [
                'connected' => false,
                'host'      => $host,
                'database'  => $database,
                'user'      => $user,
                'version'   => 'N/A',
                'error'     => $e->getMessage(),
            ];
        }
    }
}
