<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AccessScope;
use App\Core\Auth;
use App\Core\Database;

final class DashboardController
{
    public static function index(): void
    {
        Auth::requireLogin();

        $pdo = Database::connection();
        [$communityCondition, $communityParams] = AccessScope::queryCondition('department_id', 'municipality_id', 'id', 'created_by_user_id');
        [$candidateCondition, $candidateParams] = AccessScope::queryCondition('ca.department_id', 'ca.municipality_id', null, 'p.created_by_user_id');
        [$affiliateCondition, $affiliateParams] = AccessScope::queryCondition('aa.department_id', 'aa.municipality_id', 'aa.community_id', 'p.created_by_user_id');
        [$personCondition, $personParams] = AccessScope::queryCondition(
            'COALESCE(ca.department_id, aa.department_id, lp.department_id)',
            'COALESCE(ca.municipality_id, aa.municipality_id, lp.municipality_id)',
            'COALESCE(aa.community_id, lp.community_id)',
            'p.created_by_user_id'
        );

        $stats = [
            'usuarios' => has_role('ADMINISTRADOR')
                ? (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn()
                : 0,
            'comunidades' => (int) self::count("SELECT COUNT(*) FROM communities WHERE is_active = 1 AND {$communityCondition}", $communityParams),
            'personas' => (int) self::count("
                SELECT COUNT(DISTINCT p.id)
                  FROM persons p
                  LEFT JOIN candidate_profiles cp ON cp.person_id = p.id
                  LEFT JOIN candidate_assignments ca ON ca.candidate_profile_id = cp.id
                  LEFT JOIN affiliate_profiles ap ON ap.person_id = p.id
                  LEFT JOIN affiliate_assignments aa ON aa.affiliate_profile_id = ap.id
                  LEFT JOIN leader_profiles lp ON lp.person_id = p.id
                 WHERE {$personCondition}
            ", $personParams),
            'candidatos' => (int) self::count("
                SELECT COUNT(DISTINCT cp.id)
                  FROM candidate_profiles cp
                  INNER JOIN persons p ON p.id = cp.person_id
                  LEFT JOIN candidate_assignments ca ON ca.candidate_profile_id = cp.id
                 WHERE {$candidateCondition}
            ", $candidateParams),
            'lideres' => (int) self::count("
                SELECT COUNT(DISTINCT lp.id)
                  FROM leader_profiles lp
                  INNER JOIN persons p ON p.id = lp.person_id
                 WHERE {$personCondition}
            ", $personParams),
            'afiliados' => (int) self::count("
                SELECT COUNT(DISTINCT ap.id)
                  FROM affiliate_profiles ap
                  INNER JOIN persons p ON p.id = ap.person_id
                  LEFT JOIN affiliate_assignments aa ON aa.affiliate_profile_id = ap.id
                 WHERE {$affiliateCondition}
            ", $affiliateParams),
            'vinculos_candidato' => (int) self::count("SELECT COUNT(*) FROM candidate_assignments ca WHERE {$candidateCondition}", $candidateParams),
            'vinculos_afiliado' => (int) self::count("SELECT COUNT(*) FROM affiliate_assignments aa INNER JOIN affiliate_profiles ap ON ap.id = aa.affiliate_profile_id INNER JOIN persons p ON p.id = ap.person_id WHERE {$affiliateCondition}", $affiliateParams),
        ];

        $recentPersons = self::fetch("
            SELECT DISTINCT p.id,
                   CONCAT(p.first_name, ' ', p.last_name) AS full_name,
                   p.dpi,
                   p.created_at
              FROM persons p
              LEFT JOIN candidate_profiles cp ON cp.person_id = p.id
              LEFT JOIN candidate_assignments ca ON ca.candidate_profile_id = cp.id
              LEFT JOIN affiliate_profiles ap ON ap.person_id = p.id
              LEFT JOIN affiliate_assignments aa ON aa.affiliate_profile_id = ap.id
              LEFT JOIN leader_profiles lp ON lp.person_id = p.id
             WHERE {$personCondition}
          ORDER BY p.id DESC
             LIMIT 8
        ", $personParams);

        view('dashboard/index', [
            'stats' => $stats,
            'recentPersons' => $recentPersons,
            'scopeLabel' => AccessScope::describe(),
        ]);
    }

    private static function count(string $sql, array $params = []): int
    {
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    private static function fetch(string $sql, array $params = []): array
    {
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }
}
