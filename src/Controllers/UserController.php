<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AccessScope;
use App\Core\Audit;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Mailer;
use Throwable;

final class UserController
{
    public static function index(): void
    {
        Auth::requireRole(['ADMINISTRADOR']);

        $users = Database::connection()->query('
            SELECT u.id, u.name, u.email, u.is_active, u.email_verified_at, u.last_login_at,
                   r.name AS role_name,
                   d.name AS department_name,
                   m.name AS municipality_name,
                   c.name AS community_name
              FROM users u
              INNER JOIN roles r ON r.id = u.role_id
              LEFT JOIN departments d ON d.id = u.department_id
              LEFT JOIN municipalities m ON m.id = u.municipality_id
              LEFT JOIN communities c ON c.id = u.community_id
          ORDER BY u.id DESC
        ')->fetchAll();

        view('users/index', ['users' => $users]);
    }

    public static function createForm(): void
    {
        Auth::requireRole(['ADMINISTRADOR']);
        view('users/form', self::formData());
    }

    public static function editForm(): void
    {
        Auth::requireRole(['ADMINISTRADOR']);
        $id = request_int('id');
        if (!$id) {
            flash('error', 'Usuario no encontrado.');
            redirect('/users');
        }

        $stmt = Database::connection()->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $record = $stmt->fetch();

        if (!$record) {
            flash('error', 'Usuario no encontrado.');
            redirect('/users');
        }

        view('users/form', self::formData($record));
    }

    public static function store(): void
    {
        Auth::requireRole(['ADMINISTRADOR']);
        if (!Csrf::validate($_POST['_token'] ?? null)) {
            flash('error', 'Token CSRF inválido.');
            redirect('/users/create');
        }

        $data = self::collectData();
        remember_old($_POST);

        [$valid, $message] = self::validate($data, true);
        if (!$valid) {
            flash('error', $message);
            redirect('/users/create');
        }

        $token = bin2hex(random_bytes(32));
        $pdo = Database::connection();

        try {
            $pdo->prepare('
                INSERT INTO users (
                    role_id, department_id, municipality_id, community_id, name, email,
                    password_hash, verification_token, is_active, created_at, updated_at
                ) VALUES (
                    :role_id, :department_id, :municipality_id, :community_id, :name, :email,
                    :password_hash, :verification_token, 1, NOW(), NOW()
                )
            ')->execute([
                'role_id' => $data['role_id'],
                'department_id' => $data['department_id'],
                'municipality_id' => $data['municipality_id'],
                'community_id' => $data['community_id'],
                'name' => $data['name'],
                'email' => $data['email'],
                'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
                'verification_token' => $token,
            ]);

            $userId = (string) $pdo->lastInsertId();
            Mailer::sendVerificationEmail($data['email'], $data['name'], $token);
            Audit::log('users', 'create', $userId, [
                'email' => $data['email'],
                'role_id' => $data['role_id'],
            ]);
            clear_old();
            flash('success', 'Usuario creado correctamente.');
            redirect('/users');
        } catch (Throwable $e) {
            flash('error', 'No fue posible crear el usuario: ' . $e->getMessage());
            redirect('/users/create');
        }
    }

    public static function update(): void
    {
        Auth::requireRole(['ADMINISTRADOR']);
        if (!Csrf::validate($_POST['_token'] ?? null)) {
            flash('error', 'Token CSRF inválido.');
            redirect('/users');
        }

        $id = request_int('id');
        if (!$id) {
            flash('error', 'Usuario no encontrado.');
            redirect('/users');
        }

        $data = self::collectData(false);
        remember_old($_POST);

        [$valid, $message] = self::validate($data, false, $id);
        if (!$valid) {
            flash('error', $message);
            redirect('/users/edit', ['id' => $id]);
        }

        $sql = '
            UPDATE users
               SET role_id = :role_id,
                   department_id = :department_id,
                   municipality_id = :municipality_id,
                   community_id = :community_id,
                   name = :name,
                   email = :email,
                   updated_at = NOW()
                   ' . ($data['password'] !== '' ? ', password_hash = :password_hash' : '') . '
             WHERE id = :id
        ';

        $params = [
            'id' => $id,
            'role_id' => $data['role_id'],
            'department_id' => $data['department_id'],
            'municipality_id' => $data['municipality_id'],
            'community_id' => $data['community_id'],
            'name' => $data['name'],
            'email' => $data['email'],
        ];

        if ($data['password'] !== '') {
            $params['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        try {
            Database::connection()->prepare($sql)->execute($params);
            Audit::log('users', 'update', (string) $id, ['email' => $data['email']]);
            clear_old();
            flash('success', 'Usuario actualizado correctamente.');
            redirect('/users');
        } catch (Throwable $e) {
            flash('error', 'No fue posible actualizar el usuario: ' . $e->getMessage());
            redirect('/users/edit', ['id' => $id]);
        }
    }

    public static function toggle(): void
    {
        Auth::requireRole(['ADMINISTRADOR']);
        if (!Csrf::validate($_POST['_token'] ?? null)) {
            flash('error', 'Token CSRF inválido.');
            redirect('/users');
        }

        $id = request_int('id');
        if (!$id || $id === Auth::id()) {
            flash('error', 'No puedes desactivar tu propia cuenta desde esta pantalla.');
            redirect('/users');
        }

        Database::connection()->prepare('UPDATE users SET is_active = IF(is_active = 1, 0, 1), updated_at = NOW() WHERE id = :id')
            ->execute(['id' => $id]);

        Audit::log('users', 'toggle_active', (string) $id);
        flash('success', 'Estado del usuario actualizado.');
        redirect('/users');
    }

    public static function resendVerification(): void
    {
        Auth::requireRole(['ADMINISTRADOR']);
        if (!Csrf::validate($_POST['_token'] ?? null)) {
            flash('error', 'Token CSRF inválido.');
            redirect('/users');
        }

        $id = request_int('id');
        if (!$id) {
            flash('error', 'Usuario no encontrado.');
            redirect('/users');
        }

        $stmt = Database::connection()->prepare('SELECT id, name, email, email_verified_at FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();

        if (!$user) {
            flash('error', 'Usuario no encontrado.');
            redirect('/users');
        }

        if (!empty($user['email_verified_at'])) {
            flash('error', 'El usuario ya verificó su correo.');
            redirect('/users');
        }

        $token = bin2hex(random_bytes(32));

        Database::connection()->prepare('UPDATE users SET verification_token = :token, updated_at = NOW() WHERE id = :id')
            ->execute(['token' => $token, 'id' => $id]);

        Mailer::sendVerificationEmail($user['email'], $user['name'], $token);
        Audit::log('users', 'resend_verification', (string) $id);
        flash('success', 'Se envió nuevamente el correo de verificación.');
        redirect('/users');
    }

    private static function formData(?array $record = null): array
    {
        $pdo = Database::connection();
        $roles = $pdo->query('SELECT id, name FROM roles ORDER BY name')->fetchAll();
        $departments = $pdo->query('SELECT id, name FROM departments ORDER BY name')->fetchAll();
        $municipalities = $pdo->query('SELECT id, department_id, name FROM municipalities ORDER BY name')->fetchAll();
        $communities = $pdo->query('SELECT id, department_id, municipality_id, name FROM communities WHERE is_active = 1 ORDER BY name')->fetchAll();

        return [
            'record' => $record,
            'roles' => $roles,
            'departments' => $departments,
            'municipalities' => $municipalities,
            'communities' => $communities,
            'title' => $record ? 'Editar usuario' : 'Crear usuario',
            'submitUrl' => $record ? '/users/edit?id=' . (int) $record['id'] : '/users/create',
        ];
    }

    private static function collectData(bool $requirePassword = true): array
    {
        return [
            'name' => request_str('name'),
            'email' => mb_strtolower(request_str('email')),
            'role_id' => request_int('role_id', 0),
            'department_id' => request_int('department_id'),
            'municipality_id' => request_int('municipality_id'),
            'community_id' => request_int('community_id'),
            'password' => $requirePassword ? (string) ($_POST['password'] ?? '') : (string) ($_POST['password'] ?? ''),
        ];
    }

    private static function validate(array $data, bool $isCreate, ?int $ignoreId = null): array
    {
        if ($data['name'] === '' || $data['email'] === '' || (int) $data['role_id'] < 1) {
            return [false, 'Nombre, correo y rol son obligatorios.'];
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return [false, 'El correo electrónico no es válido.'];
        }

        if ($isCreate && strlen($data['password']) < 8) {
            return [false, 'La contraseña debe tener al menos 8 caracteres.'];
        }

        if (!$isCreate && $data['password'] !== '' && strlen($data['password']) < 8) {
            return [false, 'La nueva contraseña debe tener al menos 8 caracteres.'];
        }

        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM users WHERE email = :email AND (:id IS NULL OR id <> :id)');
        $stmt->execute(['email' => $data['email'], 'id' => $ignoreId]);

        if ((int) $stmt->fetchColumn() > 0) {
            return [false, 'Ya existe un usuario con ese correo.'];
        }

        $roleNameStmt = Database::connection()->prepare('SELECT name FROM roles WHERE id = :id LIMIT 1');
        $roleNameStmt->execute(['id' => $data['role_id']]);
        $roleName = (string) ($roleNameStmt->fetchColumn() ?: '');

        if (in_array($roleName, ['DELEGADO DEPARTAMENTAL', 'DELEGADO MUNICIPAL', 'LIDER COMUNITARIO'], true) && !$data['department_id']) {
            return [false, 'El departamento es obligatorio para el rol seleccionado.'];
        }

        if (in_array($roleName, ['DELEGADO MUNICIPAL', 'LIDER COMUNITARIO'], true) && !$data['municipality_id']) {
            return [false, 'El municipio es obligatorio para el rol seleccionado.'];
        }

        if ($roleName === 'LIDER COMUNITARIO' && !$data['community_id']) {
            return [false, 'La comunidad es obligatoria para el rol Líder Comunitario.'];
        }

        return [true, ''];
    }
}
