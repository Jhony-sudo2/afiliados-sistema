<?php

declare(strict_types=1);

namespace App\Core;

final class AccessScope
{
    public static function scopeKey(?array $user = null): string
    {
        $user ??= Auth::user();

        if (!$user) {
            return 'all';
        }

        $role = (string) ($user['role'] ?? 'ADMINISTRADOR');
        $map = [
            'ADMINISTRADOR' => app_env('ROLE_SCOPE_ADMINISTRADOR', 'all'),
            'DELEGADO DEPARTAMENTAL' => app_env('ROLE_SCOPE_DELEGADO_DEPARTAMENTAL', 'department'),
            'DELEGADO MUNICIPAL' => app_env('ROLE_SCOPE_DELEGADO_MUNICIPAL', 'municipality'),
            'LIDER COMUNITARIO' => app_env('ROLE_SCOPE_LIDER_COMUNITARIO', 'community'),
        ];

        return $map[$role] ?? 'all';
    }

    public static function describe(?array $user = null): string
    {
        $user ??= Auth::user();
        if (!$user) {
            return 'Sin autenticación';
        }

        return match (self::scopeKey($user)) {
            'department' => 'Alcance departamental',
            'municipality' => 'Alcance municipal',
            'community' => 'Alcance comunitario',
            default => 'Alcance total',
        };
    }

    public static function assertAllowed(?int $departmentId = null, ?int $municipalityId = null, ?int $communityId = null): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        return match (self::scopeKey($user)) {
            'department' => $departmentId !== null && $departmentId === ($user['department_id'] ?? null),
            'municipality' => $municipalityId !== null && $municipalityId === ($user['municipality_id'] ?? null),
            'community' => $communityId !== null
                ? $communityId === ($user['community_id'] ?? null)
                : ($municipalityId !== null && $municipalityId === ($user['municipality_id'] ?? null)),
            default => true,
        };
    }

    public static function queryCondition(
        string $departmentExpr,
        ?string $municipalityExpr = null,
        ?string $communityExpr = null,
        ?string $createdByExpr = null
    ): array {
        $user = Auth::user();
        if (!$user) {
            return ['1=0', []];
        }

        $params = [];

        $allowCreatedBy = $createdByExpr !== null && Auth::id() !== null ? ' OR ' . $createdByExpr . ' = :scope_user_id' : '';
        if ($allowCreatedBy !== '') {
            $params['scope_user_id'] = Auth::id();
        }

        return match (self::scopeKey($user)) {
            'department' => [
                '(' . $departmentExpr . ' = :scope_department_id' . $allowCreatedBy . ')',
                $params + ['scope_department_id' => (int) $user['department_id']],
            ],
            'municipality' => [
                '(' . ($municipalityExpr ?? $departmentExpr) . ' = :scope_municipality_id' . $allowCreatedBy . ')',
                $params + ['scope_municipality_id' => (int) $user['municipality_id']],
            ],
            'community' => [
                '(' . ($communityExpr ?? $municipalityExpr ?? $departmentExpr) . ' = :scope_community_id' . $allowCreatedBy . ')',
                $params + ['scope_community_id' => (int) ($user['community_id'] ?? 0)],
            ],
            default => ['1=1', []],
        };
    }

    public static function filterDepartments(array $departments): array
    {
        $user = Auth::user();
        return match (self::scopeKey($user)) {
            'department', 'municipality', 'community' => array_values(array_filter(
                $departments,
                fn (array $row): bool => (int) $row['id'] === (int) ($user['department_id'] ?? 0)
            )),
            default => $departments,
        };
    }

    public static function filterMunicipalities(array $municipalities): array
    {
        $user = Auth::user();
        return match (self::scopeKey($user)) {
            'department' => array_values(array_filter(
                $municipalities,
                fn (array $row): bool => (int) $row['department_id'] === (int) ($user['department_id'] ?? 0)
            )),
            'municipality', 'community' => array_values(array_filter(
                $municipalities,
                fn (array $row): bool => (int) $row['id'] === (int) ($user['municipality_id'] ?? 0)
            )),
            default => $municipalities,
        };
    }

    public static function filterCommunities(array $communities): array
    {
        $user = Auth::user();
        return match (self::scopeKey($user)) {
            'department' => array_values(array_filter(
                $communities,
                fn (array $row): bool => (int) $row['department_id'] === (int) ($user['department_id'] ?? 0)
            )),
            'municipality' => array_values(array_filter(
                $communities,
                fn (array $row): bool => (int) $row['municipality_id'] === (int) ($user['municipality_id'] ?? 0)
            )),
            'community' => array_values(array_filter(
                $communities,
                fn (array $row): bool => (int) $row['id'] === (int) ($user['community_id'] ?? 0)
            )),
            default => $communities,
        };
    }
}
