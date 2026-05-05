<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AccessScope;
use App\Core\Auth;
use App\Core\Database;

final class ReportController
{
    public static function index(): void
    {
        Auth::requireLogin();

        [$candidateCondition, $candidateParams] = AccessScope::queryCondition('ca.department_id', 'ca.municipality_id');
        $candidateSummary = self::fetch("
            SELECT d.name AS department_name, COUNT(*) AS total
              FROM candidate_assignments ca
              LEFT JOIN departments d ON d.id = ca.department_id
             WHERE {$candidateCondition}
          GROUP BY d.name
          ORDER BY total DESC, d.name
        ", $candidateParams);

        [$affiliateCondition, $affiliateParams] = AccessScope::queryCondition('aa.department_id', 'aa.municipality_id', 'aa.community_id');
        $affiliateSummary = self::fetch("
            SELECT d.name AS department_name, m.name AS municipality_name, COUNT(*) AS total
              FROM affiliate_assignments aa
              INNER JOIN departments d ON d.id = aa.department_id
              INNER JOIN municipalities m ON m.id = aa.municipality_id
             WHERE {$affiliateCondition}
          GROUP BY d.name, m.name
          ORDER BY total DESC, d.name, m.name
        ", $affiliateParams);

        [$leaderCondition, $leaderParams] = AccessScope::queryCondition('lp.department_id', 'lp.municipality_id', 'lp.community_id');
        $leaderSummary = self::fetch("
            SELECT d.name AS department_name, m.name AS municipality_name, COUNT(*) AS total
              FROM leader_profiles lp
              INNER JOIN departments d ON d.id = lp.department_id
              INNER JOIN municipalities m ON m.id = lp.municipality_id
             WHERE {$leaderCondition}
          GROUP BY d.name, m.name
          ORDER BY total DESC, d.name, m.name
        ", $leaderParams);

        view('reports/index', compact('candidateSummary', 'affiliateSummary', 'leaderSummary'));
    }

    public static function export(): void
    {
        Auth::requireLogin();

        $type = request_str('type', 'candidates');

        if ($type === 'candidates') {
            [$condition, $params] = AccessScope::queryCondition('ca.department_id', 'ca.municipality_id');
            $rows = self::fetch("
                SELECT CONCAT(p.first_name, ' ', p.last_name) AS candidato,
                       p.dpi,
                       pos.name AS puesto,
                       d.name AS departamento,
                       m.name AS municipio,
                       ca.slot AS casilla,
                       ca.notes AS notas
                  FROM candidate_assignments ca
                  INNER JOIN candidate_profiles cp ON cp.id = ca.candidate_profile_id
                  INNER JOIN persons p ON p.id = cp.person_id
                  INNER JOIN positions pos ON pos.id = ca.position_id
                  LEFT JOIN departments d ON d.id = ca.department_id
                  LEFT JOIN municipalities m ON m.id = ca.municipality_id
                 WHERE {$condition}
              ORDER BY candidato
            ", $params);

            self::csvDownload('reporte_candidatos.csv', $rows);
        }

        if ($type === 'leaders') {
            [$condition, $params] = AccessScope::queryCondition('lp.department_id', 'lp.municipality_id', 'lp.community_id');
            $rows = self::fetch("
                SELECT CONCAT(p.first_name, ' ', p.last_name) AS lider_comunitario,
                       p.dpi,
                       d.name AS departamento,
                       m.name AS municipio,
                       c.name AS comunidad
                  FROM leader_profiles lp
                  INNER JOIN persons p ON p.id = lp.person_id
                  INNER JOIN departments d ON d.id = lp.department_id
                  INNER JOIN municipalities m ON m.id = lp.municipality_id
                  LEFT JOIN communities c ON c.id = lp.community_id
                 WHERE {$condition}
              ORDER BY lider_comunitario
            ", $params);

            self::csvDownload('reporte_lideres.csv', $rows);
        }

        [$condition, $params] = AccessScope::queryCondition('aa.department_id', 'aa.municipality_id', 'aa.community_id');
        $rows = self::fetch("
            SELECT CONCAT(p.first_name, ' ', p.last_name) AS afiliado,
                   p.dpi,
                   d.name AS departamento,
                   m.name AS municipio,
                   c.name AS comunidad,
                   CONCAT(lp2.first_name, ' ', lp2.last_name) AS lider_comunitario,
                   aa.notes AS notas
              FROM affiliate_assignments aa
              INNER JOIN affiliate_profiles ap ON ap.id = aa.affiliate_profile_id
              INNER JOIN persons p ON p.id = ap.person_id
              INNER JOIN departments d ON d.id = aa.department_id
              INNER JOIN municipalities m ON m.id = aa.municipality_id
              LEFT JOIN communities c ON c.id = aa.community_id
              LEFT JOIN leader_profiles lp ON lp.id = aa.leader_profile_id
              LEFT JOIN persons lp2 ON lp2.id = lp.person_id
             WHERE {$condition}
          ORDER BY afiliado
        ", $params);

        self::csvDownload('reporte_afiliados.csv', $rows);
    }

    private static function fetch(string $sql, array $params = []): array
    {
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    private static function csvDownload(string $filename, array $rows): never
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'wb');

        if ($rows !== []) {
            fputcsv($output, array_keys($rows[0]));
            foreach ($rows as $row) {
                fputcsv($output, $row);
            }
        }

        fclose($output);
        exit;
    }
}
