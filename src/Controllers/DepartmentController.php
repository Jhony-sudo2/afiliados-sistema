<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AccessScope;
use App\Core\Auth;
use App\Core\Database;

final class DepartmentController
{
    public static function index(): void
    {
        Auth::requireLogin();

        $departments = Database::connection()->query('
            SELECT d.id, d.name, COUNT(m.id) AS municipality_count
              FROM departments d
              LEFT JOIN municipalities m ON m.department_id = d.id
          GROUP BY d.id, d.name
          ORDER BY d.name
        ')->fetchAll();

        $municipalities = Database::connection()->query('
            SELECT m.id, m.department_id, m.name, d.name AS department_name
              FROM municipalities m
              INNER JOIN departments d ON d.id = m.department_id
          ORDER BY d.name, m.name
        ')->fetchAll();

        $departments = AccessScope::filterDepartments($departments);
        $municipalities = AccessScope::filterMunicipalities($municipalities);

        view('departments/index', compact('departments', 'municipalities'));
    }
}
