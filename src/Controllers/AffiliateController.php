<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AccessScope;
use App\Core\Audit;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use Throwable;

final class AffiliateController
{
    public static function index(): void
    {
        Auth::requireLogin();

        $pdo = Database::connection();
        $affiliates = $pdo->query('
            SELECT ap.id, CONCAT(p.first_name, " ", p.last_name, " - DPI ", p.dpi) AS full_name
              FROM affiliate_profiles ap
              INNER JOIN persons p ON p.id = ap.person_id
          ORDER BY p.first_name, p.last_name
        ')->fetchAll();

        [$leadersCondition, $leadersParams] = AccessScope::queryCondition('lp.department_id', 'lp.municipality_id', 'lp.community_id');
        $stmt = $pdo->prepare("
            SELECT lp.id, lp.department_id, lp.municipality_id, lp.community_id,
                   CONCAT(p.first_name, ' ', p.last_name, ' - DPI ', p.dpi) AS full_name
              FROM leader_profiles lp
              INNER JOIN persons p ON p.id = lp.person_id
             WHERE {$leadersCondition}
          ORDER BY p.first_name, p.last_name
        ");
        $stmt->execute($leadersParams);
        $leaders = $stmt->fetchAll();

        $departments = AccessScope::filterDepartments($pdo->query('SELECT id, name FROM departments ORDER BY name')->fetchAll());
        $municipalities = AccessScope::filterMunicipalities($pdo->query('SELECT id, department_id, name FROM municipalities ORDER BY name')->fetchAll());
        $communities = AccessScope::filterCommunities($pdo->query('SELECT id, department_id, municipality_id, name FROM communities WHERE is_active = 1 ORDER BY name')->fetchAll());

        [$condition, $params] = AccessScope::queryCondition('aa.department_id', 'aa.municipality_id', 'aa.community_id', 'p.created_by_user_id');
        $stmt = $pdo->prepare("
            SELECT aa.*,
                   CONCAT(p.first_name, ' ', p.last_name) AS affiliate_name,
                   d.name AS department_name,
                   m.name AS municipality_name,
                   c.name AS community_name,
                   CONCAT(lp2.first_name, ' ', lp2.last_name) AS leader_name
              FROM affiliate_assignments aa
              INNER JOIN affiliate_profiles ap ON ap.id = aa.affiliate_profile_id
              INNER JOIN persons p ON p.id = ap.person_id
              INNER JOIN departments d ON d.id = aa.department_id
              INNER JOIN municipalities m ON m.id = aa.municipality_id
              LEFT JOIN communities c ON c.id = aa.community_id
              LEFT JOIN leader_profiles lp ON lp.id = aa.leader_profile_id
              LEFT JOIN persons lp2 ON lp2.id = lp.person_id
             WHERE {$condition}
          ORDER BY aa.id DESC
        ");
        $stmt->execute($params);
        $assignments = $stmt->fetchAll();

        $editRecord = null;
        $editId = request_int('id');
        if ($editId) {
            $stmt = $pdo->prepare("
                SELECT aa.*
                  FROM affiliate_assignments aa
                  INNER JOIN affiliate_profiles ap ON ap.id = aa.affiliate_profile_id
                  INNER JOIN persons p ON p.id = ap.person_id
                 WHERE aa.id = :id
                   AND {$condition}
                 LIMIT 1
            ");
            $stmt->execute($params + ['id' => $editId]);
            $editRecord = $stmt->fetch();
        }

        view('affiliates/index', compact('affiliates', 'leaders', 'departments', 'municipalities', 'communities', 'assignments', 'editRecord'));
    }

    public static function store(): void
    {
        Auth::requireLogin();
        self::persist(false);
    }

    public static function update(): void
    {
        Auth::requireLogin();
        self::persist(true);
    }

    public static function delete(): void
    {
        Auth::requireLogin();

        if (!Csrf::validate($_POST['_token'] ?? null)) {
            flash('error', 'Token CSRF inválido.');
            redirect('/affiliate-assignments');
        }

        $id = request_int('id');
        if (!$id) {
            flash('error', 'Vinculación no encontrada.');
            redirect('/affiliate-assignments');
        }

        Database::connection()->prepare('DELETE FROM affiliate_assignments WHERE id = :id')->execute(['id' => $id]);
        Audit::log('affiliate_assignments', 'delete', (string) $id);
        flash('success', 'Vinculación eliminada.');
        redirect('/affiliate-assignments');
    }

    private static function persist(bool $isUpdate): void
    {
        if (!Csrf::validate($_POST['_token'] ?? null)) {
            flash('error', 'Token CSRF inválido.');
            redirect('/affiliate-assignments');
        }

        $id = request_int('id');
        $data = [
            'affiliate_profile_id' => request_int('affiliate_profile_id'),
            'department_id' => request_int('department_id'),
            'municipality_id' => request_int('municipality_id'),
            'community_id' => request_int('community_id'),
            'leader_profile_id' => request_int('leader_profile_id'),
            'notes' => request_str('notes'),
        ];

        remember_old($_POST);

        [$valid, $message] = self::validate($data);
        if (!$valid) {
            flash('error', $message);
            redirect('/affiliate-assignments' . ($isUpdate && $id ? '?id=' . $id : ''));
        }

        try {
            if ($isUpdate && $id) {
                Database::connection()->prepare('
                    UPDATE affiliate_assignments
                       SET affiliate_profile_id = :affiliate_profile_id,
                           department_id = :department_id,
                           municipality_id = :municipality_id,
                           community_id = :community_id,
                           leader_profile_id = :leader_profile_id,
                           notes = :notes,
                           updated_at = NOW()
                     WHERE id = :id
                ')->execute($data + ['id' => $id]);

                Audit::log('affiliate_assignments', 'update', (string) $id, $data);
                clear_old();
                flash('success', 'Vinculación de afiliado actualizada.');
                redirect('/affiliate-assignments');
            }

            Database::connection()->prepare('
                INSERT INTO affiliate_assignments (
                    affiliate_profile_id, department_id, municipality_id, community_id, leader_profile_id, notes,
                    created_by_user_id, created_at, updated_at
                ) VALUES (
                    :affiliate_profile_id, :department_id, :municipality_id, :community_id, :leader_profile_id, :notes,
                    :created_by_user_id, NOW(), NOW()
                )
            ')->execute($data + ['created_by_user_id' => Auth::id()]);

            Audit::log('affiliate_assignments', 'create', (string) Database::connection()->lastInsertId(), $data);
            clear_old();
            flash('success', 'Vinculación de afiliado registrada.');
            redirect('/affiliate-assignments');
        } catch (Throwable $e) {
            flash('error', 'No fue posible guardar la vinculación: ' . $e->getMessage());
            redirect('/affiliate-assignments' . ($isUpdate && $id ? '?id=' . $id : ''));
        }
    }

    private static function validate(array $data): array
    {
        if (!$data['affiliate_profile_id'] || !$data['department_id'] || !$data['municipality_id']) {
            return [false, 'Afiliado, departamento y municipio son obligatorios.'];
        }

        if (!AccessScope::assertAllowed((int) $data['department_id'], (int) $data['municipality_id'], $data['community_id'])) {
            return [false, 'No tienes permiso para registrar afiliados fuera de tu alcance.'];
        }

        if ($data['leader_profile_id']) {
            $stmt = Database::connection()->prepare('SELECT department_id, municipality_id, community_id FROM leader_profiles WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $data['leader_profile_id']]);
            $leader = $stmt->fetch();

            if (!$leader) {
                return [false, 'El líder comunitario seleccionado no existe.'];
            }

            if ((int) $leader['department_id'] !== (int) $data['department_id'] || (int) $leader['municipality_id'] !== (int) $data['municipality_id']) {
                return [false, 'El líder comunitario debe pertenecer al mismo departamento y municipio del afiliado.'];
            }
        }

        return [true, ''];
    }
}
