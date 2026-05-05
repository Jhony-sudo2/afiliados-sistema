<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use Throwable;

final class PositionController
{
    public static function index(): void
    {
        Auth::requireLogin();

        $positions = Database::connection()->query('SELECT * FROM positions ORDER BY id DESC')->fetchAll();
        $editRecord = null;
        $editId = request_int('id');

        if ($editId) {
            $stmt = Database::connection()->prepare('SELECT * FROM positions WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $editId]);
            $editRecord = $stmt->fetch();
        }

        view('positions/index', compact('positions', 'editRecord'));
    }

    public static function store(): void
    {
        Auth::requireRole(['ADMINISTRADOR']);

        if (!Csrf::validate($_POST['_token'] ?? null)) {
            flash('error', 'Token CSRF inválido.');
            redirect('/positions');
        }

        $data = self::collectData();
        remember_old($_POST);

        [$valid, $message] = self::validate($data);
        if (!$valid) {
            flash('error', $message);
            redirect('/positions');
        }

        try {
            Database::connection()->prepare('
                INSERT INTO positions (
                    name, requires_department, requires_municipality, requires_slot, slot_min, slot_max, is_active, created_at, updated_at
                ) VALUES (
                    :name, :requires_department, :requires_municipality, :requires_slot, :slot_min, :slot_max, 1, NOW(), NOW()
                )
            ')->execute($data);

            Audit::log('positions', 'create', (string) Database::connection()->lastInsertId(), $data);
            clear_old();
            flash('success', 'Puesto registrado correctamente.');
            redirect('/positions');
        } catch (Throwable $e) {
            flash('error', 'No fue posible registrar el puesto: ' . $e->getMessage());
            redirect('/positions');
        }
    }

    public static function update(): void
    {
        Auth::requireRole(['ADMINISTRADOR']);

        if (!Csrf::validate($_POST['_token'] ?? null)) {
            flash('error', 'Token CSRF inválido.');
            redirect('/positions');
        }

        $id = request_int('id');
        if (!$id) {
            flash('error', 'Puesto no encontrado.');
            redirect('/positions');
        }

        $data = self::collectData();
        remember_old($_POST);

        [$valid, $message] = self::validate($data);
        if (!$valid) {
            flash('error', $message);
            redirect('/positions', ['id' => $id]);
        }

        try {
            Database::connection()->prepare('
                UPDATE positions
                   SET name = :name,
                       requires_department = :requires_department,
                       requires_municipality = :requires_municipality,
                       requires_slot = :requires_slot,
                       slot_min = :slot_min,
                       slot_max = :slot_max,
                       updated_at = NOW()
                 WHERE id = :id
            ')->execute($data + ['id' => $id]);

            Audit::log('positions', 'update', (string) $id, $data);
            clear_old();
            flash('success', 'Puesto actualizado correctamente.');
            redirect('/positions');
        } catch (Throwable $e) {
            flash('error', 'No fue posible actualizar el puesto: ' . $e->getMessage());
            redirect('/positions', ['id' => $id]);
        }
    }

    public static function delete(): void
    {
        Auth::requireRole(['ADMINISTRADOR']);

        if (!Csrf::validate($_POST['_token'] ?? null)) {
            flash('error', 'Token CSRF inválido.');
            redirect('/positions');
        }

        $id = request_int('id');
        if (!$id) {
            flash('error', 'Puesto no encontrado.');
            redirect('/positions');
        }

        Database::connection()->prepare('UPDATE positions SET is_active = 0, updated_at = NOW() WHERE id = :id')->execute(['id' => $id]);
        Audit::log('positions', 'deactivate', (string) $id);
        flash('success', 'Puesto desactivado.');
        redirect('/positions');
    }

    private static function collectData(): array
    {
        $requiresDepartment = isset($_POST['requires_department']) ? 1 : 0;
        $requiresMunicipality = isset($_POST['requires_municipality']) ? 1 : 0;
        $requiresSlot = isset($_POST['requires_slot']) ? 1 : 0;

        return [
            'name' => request_str('name'),
            'requires_department' => $requiresDepartment,
            'requires_municipality' => $requiresMunicipality,
            'requires_slot' => $requiresSlot,
            'slot_min' => $requiresSlot ? request_int('slot_min') : null,
            'slot_max' => $requiresSlot ? request_int('slot_max') : null,
        ];
    }

    private static function validate(array $data): array
    {
        if ($data['name'] === '') {
            return [false, 'El nombre del puesto es obligatorio.'];
        }

        if ((int) $data['requires_slot'] === 1) {
            if (!$data['slot_min'] || !$data['slot_max']) {
                return [false, 'Debes indicar el rango de casillas cuando el puesto requiere posición.'];
            }

            if ((int) $data['slot_min'] > (int) $data['slot_max']) {
                return [false, 'El rango de casillas es inválido.'];
            }
        }

        return [true, ''];
    }
}
