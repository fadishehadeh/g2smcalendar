<?php

declare(strict_types=1);

namespace App\Core;

final class Auth
{
    public static function attempt(string $email, string $password): bool
    {
        $user = Database::fetch(
            'SELECT users.*, roles.name AS role_name FROM users JOIN roles ON roles.id = users.role_id WHERE users.email = :email AND users.status = :status',
            ['email' => $email, 'status' => 'active']
        );

        if (!$user || !password_verify($password, $user['password'])) {
            return false;
        }

        unset($user['password']);
        $_SESSION['auth_user'] = $user;

        Database::query('UPDATE users SET last_login_at = NOW() WHERE id = :id', ['id' => $user['id']]);

        return true;
    }

    public static function user(): ?array
    {
        return $_SESSION['auth_user'] ?? null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function requireRole(array|string $roles): void
    {
        if (!self::check()) {
            header('Location: index.php?route=login');
            exit;
        }

        $roles = (array) $roles;
        $user = self::user();

        if (!in_array($user['role_name'], $roles, true)) {
            http_response_code(403);
            View::render('errors/403', ['title' => 'Forbidden']);
            exit;
        }
    }

    public static function logout(): void
    {
        unset($_SESSION['auth_user']);
        session_regenerate_id(true);
    }

    public static function refreshUser(int $userId): void
    {
        $user = Database::fetch(
            'SELECT users.*, roles.name AS role_name FROM users JOIN roles ON roles.id = users.role_id WHERE users.id = :id AND users.status = :status',
            ['id' => $userId, 'status' => 'active']
        );

        if (!$user) {
            self::logout();
            return;
        }

        unset($user['password']);
        $_SESSION['auth_user'] = $user;
    }
}
