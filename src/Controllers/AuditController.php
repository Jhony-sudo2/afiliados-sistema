<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;

final class AuditController
{
    public static function index(): void
    {
        Auth::requireRole(['ADMINISTRADOR']);

        $logs = Database::connection()->query('
            SELECT a.*, u.name AS user_name
              FROM audit_logs a
              LEFT JOIN users u ON u.id = a.user_id
          ORDER BY a.id DESC
             LIMIT 300
        ')->fetchAll();

        view('audit/index', ['logs' => $logs]);
    }
}
