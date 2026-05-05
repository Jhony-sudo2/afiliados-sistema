<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AccessScope;
use App\Core\Audit;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use Throwable;

final class CommunityController
{
    public static function index(): void
    {
        Auth::requireLogin();

        $pdo = Database::connection();
        $departments = AccessScope::filterDepartments($pdo->query('SELECT id, name FROM departments ORDER BY name')->fetchAll());
        $municipalities = AccessScope::filterMunicipalities($pdo->query('SELECT id, department_id, name FROM municipalities ORDER BY name')->fetchAll());

        [$condition, $params] = AccessScope::queryCondition('c.department_id', 'c.municipality_id', 'c.id', 'c.created_by_user_id');
        $stmt = $pdo->prepare("
            SELECT c.*, d.name AS department_name, m.name AS municipality_name
              FROM communities c
              INNER JOIN departments d ON d.id = c.department_id
              INNER JOIN municipalities m ON m.id = c.municipality_id
             WHERE {$condition}
          ORDER BY c.id DESC
        ");
        $stmt->execute($params);
        $communities = $stmt->fetchAll();

        $editRecord = null;
        $editId = request_int('id');
        if ($editId) {
            $stmt = $pdo->prepare("
                SELECT c.*
                  FROM communities c
                 WHERE c.id = :id
                   AND {$condition}
                 LIMIT 1
            ");
            $stmt->execute($params + ['id' => $editId]);
            $editRecord = $stmt->fetch();
        }

        view('communities/index', compact('departments', 'municipalities', 'communities', 'editRecord'));
    }

    public static function store(): void
    {
        Auth::requireRole(['ADMINISTRADOR', 'DELEGADO DEPARTAMENTAL', 'DELEGADO MUNICIPAL']);

        if (!Csrf::validate($_POST['_token'] ?? null)) {
            flash('error', 'Token CSRF inválido.');
            redirect('/communities');
        }

        $data = self::collectData();
        remember_old($_POST);

        [$valid, $message] = self::validate($data);
        if (!$valid) {
            flash('error', $message);
            redirect('/communities');
        }

        try {
            Database::connection()->prepare('
                INSERT INTO communities (department_id, municipality_id, name, is_active, created_by_user_id, created_at, updated_at)
                VALUES (:department_id, :municipality_id, :name, 1, :created_by_user_id, NOW(), NOW())
            ')->execute($data + ['created_by_user_id' => Auth::id()]);

            $id = (string) Database::connection()->lastInsertId();
            Audit::log('communities', 'create', $id, $data);
            clear_old();
            flash('success', 'Comunidad registrada correctamente.');
            redirect('/communities');
        } catch (Throwable $e) {
            flash('error', 'No fue posible registrar la comunidad: ' . $e->getMessage());
            redirect('/communities');
        }
    }

    public static function update(): void
    {
        Auth::requireRole(['ADMINISTRADOR', 'DELEGADO DEPARTAMENTAL', 'DELEGADO MUNICIPAL']);

        if (!Csrf::validate($_POST['_token'] ?? null)) {
            flash('error', 'Token CSRF inválido.');
            redirect('/communities');
        }

        $id = request_int('id');
        if (!$id) {
            flash('error', 'Comunidad no encontrada.');
            redirect('/communities');
        }

        $data = self::collectData();
        remember_old($_POST);

        [$valid, $message] = self::validate($data);
        if (!$valid) {
            flash('error', $message);
            redirect('/communities', ['id' => $id]);
        }

        try {
            Database::connection()->prepare('
                UPDATE communities
                   SET department_id = :department_id,
                       municipality_id = :municipality_id,
                       name = :name,
                       updated_at = NOW()
                 WHERE id = :id
            ')->execute($data + ['id' => $id]);

            Audit::log('communities', 'update', (string) $id, $data);
            clear_old();
            flash('success', 'Comunidad actualizada correctamente.');
            redirect('/communities');
        } catch (Throwable $e) {
            flash('error', 'No fue posible actualizar la comunidad: ' . $e->getMessage());
            redirect('/communities', ['id' => $id]);
        }
    }

    public static function delete(): void
    {
        Auth::requireRole(['ADMINISTRADOR', 'DELEGADO DEPARTAMENTAL', 'DELEGADO MUNICIPAL']);

        if (!Csrf::validate($_POST['_token'] ?? null)) {
            flash('error', 'Token CSRF inválido.');
            redirect('/communities');
        }

        $id = request_int('id');
        if (!$id) {
            flash('error', 'Comunidad no encontrada.');
            redirect('/communities');
        }

        Database::connection()->prepare('UPDATE communities SET is_active = 0, updated_at = NOW() WHERE id = :id')
            ->execute(['id' => $id]);

        Audit::log('communities', 'deactivate', (string) $id);
        flash('success', 'Comunidad desactivada.');
        redirect('/communities');
    }

    private static function collectData(): array
    {
        return [
            'department_id' => request_int('department_id'),
            'municipality_id' => request_int('municipality_id'),
            'name' => request_str('name'),
        ];
    }

    private static function validate(array $data): array
    {
        if (!$data['department_id'] || !$data['municipality_id'] || $data['name'] === '') {
            return [false, 'Departamento, municipio y nombre son obligatorios.'];
        }

        if (!AccessScope::assertAllowed((int) $data['department_id'], (int) $data['municipality_id'])) {
            return [false, 'No tienes permiso para registrar comunidades fuera de tu alcance.'];
        }

        return [true, ''];
    }
}
