<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AccessScope;
use App\Core\Auth;
use App\Core\Database;

final class ApiController
{
    public static function municipalities(): void
    {
        Auth::requireLogin();

        $departmentId = request_int('department_id');
        $stmt = Database::connection()->prepare('
            SELECT id, department_id, name
              FROM municipalities
             WHERE (:department_id IS NULL OR department_id = :department_id)
          ORDER BY name
        ');
        $stmt->execute(['department_id' => $departmentId]);
        $rows = AccessScope::filterMunicipalities($stmt->fetchAll());

        json_response(['items' => $rows]);
    }

    public static function communities(): void
    {
        Auth::requireLogin();

        $departmentId = request_int('department_id');
        $municipalityId = request_int('municipality_id');

        $sql = '
            SELECT id, department_id, municipality_id, name
              FROM communities
             WHERE is_active = 1
               AND (:department_id IS NULL OR department_id = :department_id)
               AND (:municipality_id IS NULL OR municipality_id = :municipality_id)
          ORDER BY name
        ';

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([
            'department_id' => $departmentId,
            'municipality_id' => $municipalityId,
        ]);

        $rows = AccessScope::filterCommunities($stmt->fetchAll());
        json_response(['items' => $rows]);
    }

    public static function leaders(): void
    {
        Auth::requireLogin();

        $departmentId = request_int('department_id');
        $municipalityId = request_int('municipality_id');
        $communityId = request_int('community_id');

        $sql = '
            SELECT lp.id,
                   lp.department_id,
                   lp.municipality_id,
                   lp.community_id,
                   CONCAT(p.first_name, " ", p.last_name, " - DPI ", p.dpi) AS name
              FROM leader_profiles lp
              INNER JOIN persons p ON p.id = lp.person_id
             WHERE (:department_id IS NULL OR lp.department_id = :department_id)
               AND (:municipality_id IS NULL OR lp.municipality_id = :municipality_id)
               AND (:community_id IS NULL OR lp.community_id = :community_id)
          ORDER BY p.first_name, p.last_name
        ';

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([
            'department_id' => $departmentId,
            'municipality_id' => $municipalityId,
            'community_id' => $communityId,
        ]);

        $rows = $stmt->fetchAll();
        [$condition, $params] = AccessScope::queryCondition('lp.department_id', 'lp.municipality_id', 'lp.community_id');
        if ($condition !== '1=1') {
            $sql = '
                SELECT lp.id,
                       lp.department_id,
                       lp.municipality_id,
                       lp.community_id,
                       CONCAT(p.first_name, " ", p.last_name, " - DPI ", p.dpi) AS name
                  FROM leader_profiles lp
                  INNER JOIN persons p ON p.id = lp.person_id
                 WHERE (:department_id IS NULL OR lp.department_id = :department_id)
                   AND (:municipality_id IS NULL OR lp.municipality_id = :municipality_id)
                   AND (:community_id IS NULL OR lp.community_id = :community_id)
                   AND ' . $condition . '
              ORDER BY p.first_name, p.last_name
            ';
            $stmt = Database::connection()->prepare($sql);
            $stmt->execute($params + [
                'department_id' => $departmentId,
                'municipality_id' => $municipalityId,
                'community_id' => $communityId,
            ]);
            $rows = $stmt->fetchAll();
        }

        json_response(['items' => $rows]);
    }
}
