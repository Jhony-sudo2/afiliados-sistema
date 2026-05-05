<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Mailer;
use Throwable;

final class AuthController
{
    public static function showLogin(): void
    {
        if (Auth::check()) {
            redirect('/');
        }

        view('auth/login');
    }

    public static function login(): void
    {
        if (!Csrf::validate($_POST['_token'] ?? null)) {
            flash('error', 'Token CSRF inválido.');
            redirect('/login');
        }

        $email = mb_strtolower(request_str('email'));
        $password = (string) ($_POST['password'] ?? '');
        remember_old(['email' => $email]);

        if ($email === '' || $password === '') {
            flash('error', 'Debes ingresar correo y contraseña.');
            redirect('/login');
        }

        if (!Auth::attempt($email, $password)) {
            flash('error', flash('error') ?? 'Credenciales inválidas.');
            redirect('/login');
        }

        clear_old();
        Audit::log('auth', 'login', (string) Auth::id(), ['email' => $email]);
        flash('success', 'Bienvenido al sistema.');
        redirect('/');
    }

    public static function logout(): void
    {
        if (!Csrf::validate($_POST['_token'] ?? null)) {
            flash('error', 'Token CSRF inválido.');
            redirect('/');
        }

        Audit::log('auth', 'logout', (string) Auth::id());
        Auth::logout();
        flash('success', 'Sesión finalizada.');
        redirect('/login');
    }

    public static function verifyEmail(): void
    {
        $token = request_str('token');

        if ($token === '') {
            flash('error', 'Token de verificación inválido.');
            redirect('/login');
        }

        $stmt = Database::connection()->prepare('
            UPDATE users
               SET email_verified_at = NOW(),
                   verification_token = NULL,
                   updated_at = NOW()
             WHERE verification_token = :token
               AND email_verified_at IS NULL
             LIMIT 1
        ');
        $stmt->execute(['token' => $token]);

        if ($stmt->rowCount() < 1) {
            flash('error', 'El enlace de verificación ya fue utilizado o no es válido.');
            redirect('/login');
        }

        Audit::log('auth', 'verify_email', $token);
        flash('success', 'Correo verificado correctamente. Ya puedes iniciar sesión.');
        redirect('/login');
    }

    public static function showForgotPassword(): void
    {
        view('auth/forgot-password');
    }

    public static function sendResetLink(): void
    {
        if (!Csrf::validate($_POST['_token'] ?? null)) {
            flash('error', 'Token CSRF inválido.');
            redirect('/forgot-password');
        }

        $email = mb_strtolower(request_str('email'));
        remember_old(['email' => $email]);

        if ($email === '') {
            flash('error', 'Debes ingresar el correo electrónico.');
            redirect('/forgot-password');
        }

        $stmt = Database::connection()->prepare('SELECT id, name, email FROM users WHERE email = :email AND is_active = 1 LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user) {
            $token = bin2hex(random_bytes(32));

            Database::connection()->prepare('
                INSERT INTO password_reset_tokens (user_id, token, expires_at, created_at)
                VALUES (:user_id, :token, DATE_ADD(NOW(), INTERVAL 60 MINUTE), NOW())
            ')->execute([
                'user_id' => $user['id'],
                'token' => $token,
            ]);

            Mailer::sendPasswordResetEmail($user['email'], $user['name'], $token);
            Audit::log('auth', 'request_reset', (string) $user['id']);
        }

        clear_old();
        flash('success', 'Si el correo existe, se envió un enlace para restablecer la contraseña.');
        redirect('/forgot-password');
    }

    public static function showResetPassword(): void
    {
        $token = request_str('token');

        if ($token === '') {
            flash('error', 'Token inválido.');
            redirect('/forgot-password');
        }

        $stmt = Database::connection()->prepare('
            SELECT id
              FROM password_reset_tokens
             WHERE token = :token
               AND used_at IS NULL
               AND expires_at >= NOW()
             LIMIT 1
        ');
        $stmt->execute(['token' => $token]);

        if (!$stmt->fetch()) {
            flash('error', 'El enlace de recuperación ya expiró o no es válido.');
            redirect('/forgot-password');
        }

        view('auth/reset-password', ['token' => $token]);
    }

    public static function resetPassword(): void
    {
        if (!Csrf::validate($_POST['_token'] ?? null)) {
            flash('error', 'Token CSRF inválido.');
            redirect('/forgot-password');
        }

        $token = request_str('token');
        $password = (string) ($_POST['password'] ?? '');
        $passwordConfirmation = (string) ($_POST['password_confirmation'] ?? '');

        if ($token === '' || $password === '' || $passwordConfirmation === '') {
            flash('error', 'Completa todos los campos.');
            redirect('/reset-password', ['token' => $token]);
        }

        if (strlen($password) < 8) {
            flash('error', 'La contraseña debe tener al menos 8 caracteres.');
            redirect('/reset-password', ['token' => $token]);
        }

        if ($password !== $passwordConfirmation) {
            flash('error', 'Las contraseñas no coinciden.');
            redirect('/reset-password', ['token' => $token]);
        }

        $pdo = Database::connection();

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare('
                SELECT id, user_id
                  FROM password_reset_tokens
                 WHERE token = :token
                   AND used_at IS NULL
                   AND expires_at >= NOW()
                 LIMIT 1
                 FOR UPDATE
            ');
            $stmt->execute(['token' => $token]);
            $record = $stmt->fetch();

            if (!$record) {
                $pdo->rollBack();
                flash('error', 'El enlace de recuperación ya expiró o no es válido.');
                redirect('/forgot-password');
            }

            $pdo->prepare('UPDATE users SET password_hash = :hash, updated_at = NOW() WHERE id = :id')
                ->execute([
                    'hash' => password_hash($password, PASSWORD_DEFAULT),
                    'id' => $record['user_id'],
                ]);

            $pdo->prepare('UPDATE password_reset_tokens SET used_at = NOW() WHERE id = :id')
                ->execute(['id' => $record['id']]);

            $pdo->commit();
            Audit::log('auth', 'reset_password', (string) $record['user_id']);
            flash('success', 'Contraseña actualizada correctamente.');
            redirect('/login');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            flash('error', 'No fue posible actualizar la contraseña: ' . $e->getMessage());
            redirect('/reset-password', ['token' => $token]);
        }
    }
}
