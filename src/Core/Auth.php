<?php

declare(strict_types=1);

namespace App\Core;

final class Auth
{
    public static function attempt(string $email, string $password): bool
    {
        $sql = <<<SQL
            SELECT u.*, r.name AS role_name
              FROM users u
              INNER JOIN roles r ON r.id = u.role_id
             WHERE u.email = :email
               AND u.is_active = 1
             LIMIT 1
        SQL;

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['email' => mb_strtolower(trim($email))]);
        $user = $stmt->fetch();

        if (!$user) {
            return false;
        }

        if (empty($user['email_verified_at'])) {
            flash('error', 'La cuenta existe, pero el correo aún no fue verificado.');
            return false;
        }

        if (!password_verify($password, $user['password_hash'])) {
            return false;
        }

        Database::connection()->prepare('UPDATE users SET last_login_at = NOW(), updated_at = NOW() WHERE id = :id')
            ->execute(['id' => $user['id']]);

        session_regenerate_id(true);

        $_SESSION['auth'] = [
            'id' => (int) $user['id'],
            'name' => (string) $user['name'],
            'email' => (string) $user['email'],
            'role' => (string) $user['role_name'],
            'department_id' => $user['department_id'] !== null ? (int) $user['department_id'] : null,
            'municipality_id' => $user['municipality_id'] !== null ? (int) $user['municipality_id'] : null,
            'community_id' => $user['community_id'] !== null ? (int) $user['community_id'] : null,
        ];

        return true;
    }

    public static function user(): ?array
    {
        return $_SESSION['auth'] ?? null;
    }

    public static function id(): ?int
    {
        return isset($_SESSION['auth']['id']) ? (int) $_SESSION['auth']['id'] : null;
    }

    public static function role(): ?string
    {
        return $_SESSION['auth']['role'] ?? null;
    }

    public static function check(): bool
    {
        return isset($_SESSION['auth']);
    }

    public static function logout(): void
    {
        unset($_SESSION['auth']);
        session_regenerate_id(true);
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            flash('error', 'Debes iniciar sesión.');
            redirect('/login');
        }
    }

    public static function hasRole(array $roles): bool
    {
        return self::check() && in_array(self::role(), $roles, true);
    }

    public static function requireRole(array $roles): void
    {
        self::requireLogin();

        if (!self::hasRole($roles)) {
            flash('error', 'No tienes permisos para acceder a esta opción.');
            redirect('/');
        }
    }
}
