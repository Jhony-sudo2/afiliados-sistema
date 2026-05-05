<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AccessScope;
use App\Core\Audit;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use Throwable;

final class CandidateController
{
    public static function index(): void
    {
        Auth::requireRole(['ADMINISTRADOR', 'DELEGADO DEPARTAMENTAL', 'DELEGADO MUNICIPAL']);

        $pdo = Database::connection();
        $candidates = $pdo->query('
            SELECT cp.id, CONCAT(p.first_name, " ", p.last_name, " - DPI ", p.dpi) AS full_name
              FROM candidate_profiles cp
              INNER JOIN persons p ON p.id = cp.person_id
          ORDER BY p.first_name, p.last_name
        ')->fetchAll();

        $positions = $pdo->query('SELECT * FROM positions WHERE is_active = 1 ORDER BY name')->fetchAll();
        $departments = AccessScope::filterDepartments($pdo->query('SELECT id, name FROM departments ORDER BY name')->fetchAll());
        $municipalities = AccessScope::filterMunicipalities($pdo->query('SELECT id, department_id, name FROM municipalities ORDER BY name')->fetchAll());

        [$condition, $params] = AccessScope::queryCondition('ca.department_id', 'ca.municipality_id', null, 'p.created_by_user_id');
        $stmt = $pdo->prepare("
            SELECT ca.*,
                   CONCAT(p.first_name, ' ', p.last_name) AS candidate_name,
                   pos.name AS position_name,
                   d.name AS department_name,
                   m.name AS municipality_name
              FROM candidate_assignments ca
              INNER JOIN candidate_profiles cp ON cp.id = ca.candidate_profile_id
              INNER JOIN persons p ON p.id = cp.person_id
              INNER JOIN positions pos ON pos.id = ca.position_id
              LEFT JOIN departments d ON d.id = ca.department_id
              LEFT JOIN municipalities m ON m.id = ca.municipality_id
             WHERE {$condition}
          ORDER BY ca.id DESC
        ");
        $stmt->execute($params);
        $assignments = $stmt->fetchAll();

        $editRecord = null;
        $editId = request_int('id');
        if ($editId) {
            $stmt = $pdo->prepare("
                SELECT ca.*
                  FROM candidate_assignments ca
                  INNER JOIN candidate_profiles cp ON cp.id = ca.candidate_profile_id
                  INNER JOIN persons p ON p.id = cp.person_id
                 WHERE ca.id = :id
                   AND {$condition}
                 LIMIT 1
            ");
            $stmt->execute($params + ['id' => $editId]);
            $editRecord = $stmt->fetch();
        }

        view('candidates/index', compact('candidates', 'positions', 'departments', 'municipalities', 'assignments', 'editRecord'));
    }

    public static function store(): void
    {
        Auth::requireRole(['ADMINISTRADOR', 'DELEGADO DEPARTAMENTAL', 'DELEGADO MUNICIPAL']);
        self::persist(false);
    }

    public static function update(): void
    {
        Auth::requireRole(['ADMINISTRADOR', 'DELEGADO DEPARTAMENTAL', 'DELEGADO MUNICIPAL']);
        self::persist(true);
    }

    public static function delete(): void
    {
        Auth::requireRole(['ADMINISTRADOR', 'DELEGADO DEPARTAMENTAL', 'DELEGADO MUNICIPAL']);

        if (!Csrf::validate($_POST['_token'] ?? null)) {
            flash('error', 'Token CSRF inválido.');
            redirect('/candidate-assignments');
        }

        $id = request_int('id');
        if (!$id) {
            flash('error', 'Vinculación no encontrada.');
            redirect('/candidate-assignments');
        }

        Database::connection()->prepare('DELETE FROM candidate_assignments WHERE id = :id')->execute(['id' => $id]);
        Audit::log('candidate_assignments', 'delete', (string) $id);
        flash('success', 'Vinculación eliminada.');
        redirect('/candidate-assignments');
    }

    private static function persist(bool $isUpdate): void
    {
        if (!Csrf::validate($_POST['_token'] ?? null)) {
            flash('error', 'Token CSRF inválido.');
            redirect('/candidate-assignments');
        }

        $id = request_int('id');
        $data = [
            'candidate_profile_id' => request_int('candidate_profile_id'),
            'position_id' => request_int('position_id'),
            'department_id' => request_int('department_id'),
            'municipality_id' => request_int('municipality_id'),
            'slot' => request_int('slot'),
            'notes' => request_str('notes'),
        ];

        remember_old($_POST);

        [$valid, $message] = self::validate($data);
        if (!$valid) {
            flash('error', $message);
            redirect('/candidate-assignments' . ($isUpdate && $id ? '?id=' . $id : ''));
        }

        try {
            if ($isUpdate && $id) {
                Database::connection()->prepare('
                    UPDATE candidate_assignments
                       SET candidate_profile_id = :candidate_profile_id,
                           position_id = :position_id,
                           department_id = :department_id,
                           municipality_id = :municipality_id,
                           slot = :slot,
                           notes = :notes,
                           updated_at = NOW()
                     WHERE id = :id
                ')->execute($data + ['id' => $id]);

                Audit::log('candidate_assignments', 'update', (string) $id, $data);
                clear_old();
                flash('success', 'Vinculación de candidato actualizada.');
                redirect('/candidate-assignments');
            }

            Database::connection()->prepare('
                INSERT INTO candidate_assignments (
                    candidate_profile_id, position_id, department_id, municipality_id, slot, notes,
                    created_by_user_id, created_at, updated_at
                ) VALUES (
                    :candidate_profile_id, :position_id, :department_id, :municipality_id, :slot, :notes,
                    :created_by_user_id, NOW(), NOW()
                )
            ')->execute($data + ['created_by_user_id' => Auth::id()]);

            Audit::log('candidate_assignments', 'create', (string) Database::connection()->lastInsertId(), $data);
            clear_old();
            flash('success', 'Vinculación de candidato registrada.');
            redirect('/candidate-assignments');
        } catch (Throwable $e) {
            flash('error', 'No fue posible guardar la vinculación: ' . $e->getMessage());
            redirect('/candidate-assignments' . ($isUpdate && $id ? '?id=' . $id : ''));
        }
    }

    private static function validate(array $data): array
    {
        if (!$data['candidate_profile_id'] || !$data['position_id']) {
            return [false, 'Debes seleccionar candidato y puesto.'];
        }

        $stmt = Database::connection()->prepare('SELECT * FROM positions WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $data['position_id']]);
        $position = $stmt->fetch();

        if (!$position) {
            return [false, 'El puesto seleccionado no existe.'];
        }

        if ((int) $position['requires_department'] === 1 && !$data['department_id']) {
            return [false, 'El puesto seleccionado requiere departamento.'];
        }

        if ((int) $position['requires_municipality'] === 1 && !$data['municipality_id']) {
            return [false, 'El puesto seleccionado requiere municipio.'];
        }

        if ((int) $position['requires_slot'] === 1) {
            if (!$data['slot']) {
                return [false, 'El puesto seleccionado requiere casilla.'];
            }

            $min = $position['slot_min'] !== null ? (int) $position['slot_min'] : 1;
            $max = $position['slot_max'] !== null ? (int) $position['slot_max'] : 10;
            if ((int) $data['slot'] < $min || (int) $data['slot'] > $max) {
                return [false, 'La casilla debe estar entre ' . $min . ' y ' . $max . '.'];
            }
        }

        if ($data['department_id'] && !AccessScope::assertAllowed((int) $data['department_id'], $data['municipality_id'])) {
            return [false, 'No tienes permiso para registrar candidatos fuera de tu alcance.'];
        }

        return [true, ''];
    }
}
