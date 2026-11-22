<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Authentication Helper
 */
class Auth
{
    /**
     * Start session safely if not already started
     */
    private static function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Get currently logged-in admin user
     */
    public static function admin(): ?array
    {
        self::startSession();
        return $_SESSION['admin_user'] ?? null;
    }

    /**
     * Alias for admin()
     */
    public static function user(): ?array
    {
        return self::admin();
    }

    /**
     * Check if an admin is logged in
     */
    public static function check(): bool
    {
        return self::admin() !== null;
    }

    /**
     * Get logged-in admin ID
     */
    public static function id(): ?int
    {
        $admin = self::admin();
        return isset($admin['id']) ? (int)$admin['id'] : null;
    }

    /**
     * Store admin in session
     */
    public static function login(array $admin): void
    {
        self::startSession();
        unset($admin['password']);
        $_SESSION['admin_user'] = $admin;
    }

    /**
     * Log out admin
     */
    public static function logout(): void
    {
        self::startSession();
        unset($_SESSION['admin_user']);
        session_destroy();
    }
}
