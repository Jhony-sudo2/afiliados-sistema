<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AccessScope;
use App\Core\Audit;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use Throwable;

final class PersonController
{
    public static function index(): void
    {
        Auth::requireLogin();

        $profile = request_str('profile', 'all');
        $search = request_str('q');

        [$condition, $params] = AccessScope::queryCondition(
            'COALESCE(ca.department_id, aa.department_id, lp.department_id)',
            'COALESCE(ca.municipality_id, aa.municipality_id, lp.municipality_id)',
            'COALESCE(aa.community_id, lp.community_id)',
            'p.created_by_user_id'
        );

        $profileCondition = match ($profile) {
            'candidate' => 'AND cp.id IS NOT NULL',
            'leader' => 'AND lp.id IS NOT NULL',
            'affiliate' => 'AND ap.id IS NOT NULL',
            default => '',
        };

        $sql = "
            SELECT DISTINCT p.*,
                   cp.id AS candidate_profile_id,
                   lp.id AS leader_profile_id,
                   ap.id AS affiliate_profile_id,
                   lp.department_id AS leader_department_id,
                   lp.municipality_id AS leader_municipality_id,
                   lp.community_id AS leader_community_id
              FROM persons p
              LEFT JOIN candidate_profiles cp ON cp.person_id = p.id
              LEFT JOIN candidate_assignments ca ON ca.candidate_profile_id = cp.id
              LEFT JOIN affiliate_profiles ap ON ap.person_id = p.id
              LEFT JOIN affiliate_assignments aa ON aa.affiliate_profile_id = ap.id
              LEFT JOIN leader_profiles lp ON lp.person_id = p.id
             WHERE {$condition}
               {$profileCondition}
               AND (
                    :search = ''
                    OR p.first_name LIKE CONCAT('%', :search, '%')
                    OR p.last_name LIKE CONCAT('%', :search, '%')
                    OR p.dpi LIKE CONCAT('%', :search, '%')
               )
          ORDER BY p.id DESC
             LIMIT 200
        ";

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params + ['search' => $search]);
        $persons = $stmt->fetchAll();

        $listingMeta = self::listingMeta($profile);

        view('persons/index', compact('persons', 'profile', 'search') + $listingMeta);
    }

    public static function createForm(): void
    {
        Auth::requireLogin();
        view('persons/form', self::formData(null, request_str('profile', 'all')));
    }

    public static function editForm(): void
    {
        Auth::requireLogin();

        $id = request_int('id');
        if (!$id) {
            flash('error', 'Persona no encontrada.');
            redirect('/persons');
        }

        $stmt = Database::connection()->prepare('
    SELECT p.*,
           cp.id AS candidate_profile_id,
           cp.finiquito AS candidate_finiquito,
           cp.antecedente_penal AS candidate_antecedente_penal,
           cp.antecedente_policial AS candidate_antecedente_policial,
           cp.denuncia AS candidate_denuncia,
           lp.id AS leader_profile_id,
           lp.department_id AS leader_department_id,
           lp.municipality_id AS leader_municipality_id,
           lp.community_id AS leader_community_id,
           lp.finiquito AS leader_finiquito,
           lp.antecedente_penal AS leader_antecedente_penal,
           lp.antecedente_policial AS leader_antecedente_policial,
           lp.denuncia AS leader_denuncia,
           ap.id AS affiliate_profile_id
      FROM persons p
      LEFT JOIN candidate_profiles cp ON cp.person_id = p.id
      LEFT JOIN leader_profiles lp ON lp.person_id = p.id
      LEFT JOIN affiliate_profiles ap ON ap.person_id = p.id
     WHERE p.id = :id
     LIMIT 1
');
        $stmt->execute(['id' => $id]);
        $record = $stmt->fetch();

        if (!$record) {
            flash('error', 'Persona no encontrada.');
            redirect('/persons');
        }

        view('persons/form', self::formData($record, request_str('profile', 'all')));
    }

    public static function store(): void
    {
        $profile = request_str('profile', 'all'); // 👈 capturar el profile
        Auth::requireLogin();

        if (!Csrf::validate($_POST['_token'] ?? null)) {
            flash('error', 'Token CSRF inválido.');
            redirect('/persons/create');
        }

        $data = self::collectData();
        remember_old($_POST);

        [$valid, $message] = self::validate($data, true);
        if (!$valid) {
            flash('error', $message);
            redirect('/persons/create', ['profile' => $profile]);
        }

        $pdo = Database::connection();

        try {
            $pdo->beginTransaction();

            $pdo->prepare('
    INSERT INTO persons (
        first_name, last_name, address, phone_primary, phone_secondary, birth_date,
        profession, dpi, email, no_empadronamiento, centro_votacion,
        created_by_user_id, created_at, updated_at
    ) VALUES (
        :first_name, :last_name, :address, :phone_primary, :phone_secondary, :birth_date,
        :profession, :dpi, :email, :no_empadronamiento, :centro_votacion,
        :created_by_user_id, NOW(), NOW()
    )
')->execute($data['person'] + ['created_by_user_id' => Auth::id()]);

            $personId = (int) $pdo->lastInsertId();
            self::syncProfiles($pdo, $personId, $data['profiles']);

            $pdo->commit();
            Audit::log('persons', 'create', (string) $personId, [
                'dpi' => $data['person']['dpi'],
                'profiles' => array_keys(array_filter($data['profiles'])),
            ]);
            clear_old();
            flash('success', 'Persona registrada correctamente.');
            redirect('/persons');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            flash('error', 'No fue posible registrar la persona: ' . $e->getMessage());
            redirect('/persons/create', ['profile' => $profile]);
        }
    }

    public static function update(): void
    {
        $profile = request_str('profile', 'all'); // 👈
        Auth::requireLogin();

        if (!Csrf::validate($_POST['_token'] ?? null)) {
            flash('error', 'Token CSRF inválido.');
            redirect('/persons');
        }

        $id = request_int('id');
        if (!$id) {
            flash('error', 'Persona no encontrada.');
            redirect('/persons');
        }

        $data = self::collectData();
        remember_old($_POST);

        [$valid, $message] = self::validate($data, false, $id);
        if (!$valid) {
            flash('error', $message);
            redirect('/persons/edit', ['id' => $id, 'profile' => $profile]);
        }

        $pdo = Database::connection();

        try {
            $pdo->beginTransaction();

            $pdo->prepare('
    UPDATE persons
       SET first_name = :first_name,
           last_name = :last_name,
           address = :address,
           phone_primary = :phone_primary,
           phone_secondary = :phone_secondary,
           birth_date = :birth_date,
           profession = :profession,
           dpi = :dpi,
           email = :email,
           no_empadronamiento = :no_empadronamiento,
           centro_votacion = :centro_votacion,
           updated_at = NOW()
     WHERE id = :id
')->execute($data['person'] + ['id' => $id]);

            self::syncProfiles($pdo, $id, $data['profiles']);

            $pdo->commit();
            Audit::log('persons', 'update', (string) $id, [
                'dpi' => $data['person']['dpi'],
                'profiles' => array_keys(array_filter($data['profiles'])),
            ]);
            clear_old();
            flash('success', 'Persona actualizada correctamente.');
            redirect('/persons');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            flash('error', 'No fue posible actualizar la persona: ' . $e->getMessage());
            redirect('/persons/edit', ['id' => $id, 'profile' => $profile]);
        }
    }

    private static function formData(?array $record = null, string $profile = 'all'): array
    {
        $pdo = Database::connection();
        $departments = AccessScope::filterDepartments($pdo->query('SELECT id, name FROM departments ORDER BY name')->fetchAll());
        $municipalities = AccessScope::filterMunicipalities($pdo->query('SELECT id, department_id, name FROM municipalities ORDER BY name')->fetchAll());
        $communities = AccessScope::filterCommunities($pdo->query('SELECT id, department_id, municipality_id, name FROM communities WHERE is_active = 1 ORDER BY name')->fetchAll());
        $regions = $pdo->query('SELECT * from regions')->fetchAll();
        $leader_types = $pdo->query('SELECT * FROM leader_type')->fetchAll();

        $listingMeta = self::listingMeta($profile);

        return [
            'record' => $record,
            'regions' => $regions,
            'leader_types' => $leader_types,
            'departments' => $departments,
            'municipalities' => $municipalities,
            'communities' => $communities,
            'title' => $record ? 'Editar persona' : ($listingMeta['createLabel'] ?? 'Registrar persona'),
            'submitUrl' => $record ? '/persons/edit?id=' . (int) $record['id'] : '/persons/create?profile=' . urlencode($profile),
            'profile' => $profile,
            'backUrl' => '/persons' . ($profile !== 'all' ? '?profile=' . urlencode($profile) : ''),
        ];
    }

    private static function listingMeta(string $profile): array
    {
        return match ($profile) {
            'candidate' => [
                'pageTitle' => 'Candidato persona',
                'pageDescription' => 'Listado y mantenimiento de personas con perfil de candidato.',
                'createLabel' => 'Nuevo candidato persona',
            ],
            'leader' => [
                'pageTitle' => 'Líder comunitario',
                'pageDescription' => 'Listado y mantenimiento de personas con perfil de líder comunitario.',
                'createLabel' => 'Nuevo líder comunitario',
            ],
            'affiliate' => [
                'pageTitle' => 'Afiliado persona',
                'pageDescription' => 'Listado y mantenimiento de personas con perfil de afiliado.',
                'createLabel' => 'Nuevo afiliado persona',
            ],
            default => [
                'pageTitle' => 'Personas',
                'pageDescription' => 'Registro unificado de candidato, líder comunitario y afiliado persona.',
                'createLabel' => 'Nueva persona',
            ],
        };
    }

    private static function collectData(): array
    {
        return [
            'person' => [
                'first_name' => request_str('first_name'),
                'last_name' => request_str('last_name'),
                'address' => request_str('address'),
                'phone_primary' => request_str('phone_primary'),
                'phone_secondary' => request_str('phone_secondary'),
                'birth_date' => request_str('birth_date'),
                'profession' => request_str('profession'),
                'dpi' => request_str('dpi'),
                'email' => request_str('email'),
                'no_empadronamiento' => request_str('no_empadronamiento'),  // nuevo
                'centro_votacion' => request_str('centro_votacion'),     // nuevo
            ],
            'profiles' => [
                'candidate' => isset($_POST['is_candidate']),
                'leader' => isset($_POST['is_leader']),
                'affiliate' => isset($_POST['is_affiliate']),
                'leader_department_id' => request_int('leader_department_id'),
                'leader_municipality_id' => request_int('leader_municipality_id'),
                'leader_community_id' => request_int('leader_community_id'),
                // booleanos del perfil
                'finiquito' => isset($_POST['finiquito']),      // nuevo
                'antecedente_penal' => isset($_POST['antecedente_penal']),   // nuevo
                'antecedente_policial' => isset($_POST['antecedente_policial']), // nuevo
                'denuncia' => isset($_POST['denuncia']),       // nuevo
                'leader_type_id' => request_int('leader_type_id'),
                'leader_region_id' => request_int('leader_region_id'),
            ],
        ];
    }

    private static function validate(array $data, bool $isCreate, ?int $ignoreId = null): array
    {
        foreach (['first_name', 'last_name', 'address', 'phone_primary', 'birth_date', 'profession', 'dpi'] as $field) {
            if ($data['person'][$field] === '') {
                return [false, 'Debes completar todos los campos obligatorios.'];
            }
        }

        if (!preg_match('/^\d{13}$/', $data['person']['dpi'])) {
            return [false, 'El DPI debe contener exactamente 13 dígitos.'];
        }

        if (
            !array_filter([
                $data['profiles']['candidate'],
                $data['profiles']['leader'],
                $data['profiles']['affiliate'],
            ])
        ) {
            return [false, 'Debes seleccionar al menos un perfil para la persona.'];
        }

        if ($data['profiles']['leader']) {
            $typeId = (int) $data['profiles']['leader_type_id'];

            if (!$typeId) {
                return [false, 'Debes seleccionar el tipo de líder.'];
            }

            // Validar campos requeridos según tipo
            match ($typeId) {
                1 => self::validateLeaderFields($data, region: true, department: true, municipality: true),
                2 => self::validateLeaderFields($data, department: true, municipality: true),
                3 => self::validateLeaderFields($data, region: true),
                4 => self::validateLeaderFields($data, department: true),
                5 => [true, ''], // NACIONAL: no requiere nada
            };

            [$valid, $message] = match ($typeId) {
                1 => self::validateLeaderFields($data, region: true, department: true, municipality: true),
                2 => self::validateLeaderFields($data, department: true, municipality: true),
                3 => self::validateLeaderFields($data, region: true),
                4 => self::validateLeaderFields($data, department: true),
                default => [true, ''],
            };

            if (!$valid) {
                return [false, $message];
            }

            // AccessScope solo cuando hay departamento/municipio
            if ($data['profiles']['leader_department_id'] && $data['profiles']['leader_municipality_id']) {
                if (
                    !AccessScope::assertAllowed(
                        (int) $data['profiles']['leader_department_id'],
                        (int) $data['profiles']['leader_municipality_id'],
                        $data['profiles']['leader_community_id']
                    )
                ) {
                    return [false, 'No puedes registrar líderes fuera de tu alcance.'];
                }
            }
        }

        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM persons WHERE dpi = :dpi AND (:id IS NULL OR id <> :id)');
        $stmt->execute([
            'dpi' => $data['person']['dpi'],
            'id' => $ignoreId,
        ]);

        if ((int) $stmt->fetchColumn() > 0) {
            return [false, 'Ya existe una persona con ese DPI.'];
        }

        return [true, ''];
    }
    private static function validateLeaderFields(
        array $data,
        bool $region = false,
        bool $department = false,
        bool $municipality = false
    ): array {
        if ($region && !$data['profiles']['leader_region_id']) {
            return [false, 'Para este tipo de líder, la región es obligatoria.'];
        }

        if ($department && !$data['profiles']['leader_department_id']) {
            return [false, 'Para este tipo de líder, el departamento es obligatorio.'];
        }

        if ($municipality && !$data['profiles']['leader_municipality_id']) {
            return [false, 'Para este tipo de líder, el municipio es obligatorio.'];
        }

        return [true, ''];
    }
    private static function syncProfiles(\PDO $pdo, int $personId, array $profiles): void
    {
        $booleans = [
            'finiquito' => (int) $profiles['finiquito'],
            'antecedente_penal' => (int) $profiles['antecedente_penal'],
            'antecedente_policial' => (int) $profiles['antecedente_policial'],
            'denuncia' => (int) $profiles['denuncia'],
        ];

        self::syncSimpleProfile($pdo, 'candidate_profiles', $personId, (bool) $profiles['candidate'], $booleans);
        self::syncSimpleProfile($pdo, 'affiliate_profiles', $personId, (bool) $profiles['affiliate']);

        if ((bool) $profiles['leader']) {
            $stmt = $pdo->prepare('SELECT id FROM leader_profiles WHERE person_id = :person_id LIMIT 1');
            $stmt->execute(['person_id' => $personId]);
            $existingId = $stmt->fetchColumn();

            $leaderData = [
                'person_id' => $personId,
                'leader_type_id' => $profiles['leader_type_id'],
                'region_id' => $profiles['leader_region_id'] ?: null,
                'department_id' => $profiles['leader_department_id'] ?: null,
                'municipality_id' => $profiles['leader_municipality_id'] ?: null,
                'community_id' => $profiles['leader_community_id'] ?: null,
            ];

            if ($existingId) {
                $pdo->prepare('
                UPDATE leader_profiles
                   SET leader_type_id  = :leader_type_id,
                       region_id       = :region_id,
                       department_id   = :department_id,
                       municipality_id = :municipality_id,
                       community_id    = :community_id,
                       finiquito            = :finiquito,
                       antecedente_penal    = :antecedente_penal,
                       antecedente_policial = :antecedente_policial,
                       denuncia             = :denuncia,
                       updated_at      = NOW()
                 WHERE person_id = :person_id
            ')->execute($leaderData + $booleans);
            } else {
                $pdo->prepare('
                INSERT INTO leader_profiles (
                    person_id, leader_type_id, region_id,
                    department_id, municipality_id, community_id,
                    finiquito, antecedente_penal, antecedente_policial, denuncia,
                    created_at, updated_at
                ) VALUES (
                    :person_id, :leader_type_id, :region_id,
                    :department_id, :municipality_id, :community_id,
                    :finiquito, :antecedente_penal, :antecedente_policial, :denuncia,
                    NOW(), NOW()
                )
            ')->execute($leaderData + $booleans);
            }

            return;
        }

        $stmt = $pdo->prepare('
        SELECT COUNT(*) FROM affiliate_assignments aa
        INNER JOIN leader_profiles lp ON lp.id = aa.leader_profile_id
        WHERE lp.person_id = :person_id
    ');
        $stmt->execute(['person_id' => $personId]);
        if ((int) $stmt->fetchColumn() > 0) {
            throw new \RuntimeException('No puedes retirar el perfil de líder porque tiene afiliados vinculados.');
        }

        $pdo->prepare('DELETE FROM leader_profiles WHERE person_id = :person_id')->execute(['person_id' => $personId]);
    }

    private static function syncSimpleProfile(\PDO $pdo, string $table, int $personId, bool $enabled, array $booleans = []): void
    {
        $stmt = $pdo->prepare("SELECT id FROM {$table} WHERE person_id = :person_id LIMIT 1");
        $stmt->execute(['person_id' => $personId]);
        $exists = (bool) $stmt->fetchColumn();

        $hasBooleans = !empty($booleans) && in_array($table, ['candidate_profiles', 'leader_profiles']);

        if ($enabled && !$exists) {
            if ($hasBooleans) {
                $pdo->prepare("
                INSERT INTO {$table} (person_id, finiquito, antecedente_penal, antecedente_policial, denuncia, created_at, updated_at)
                VALUES (:person_id, :finiquito, :antecedente_penal, :antecedente_policial, :denuncia, NOW(), NOW())
            ")->execute(['person_id' => $personId] + $booleans);
            } else {
                $pdo->prepare("INSERT INTO {$table} (person_id, created_at, updated_at) VALUES (:person_id, NOW(), NOW())")
                    ->execute(['person_id' => $personId]);
            }
            return;
        }

        if ($enabled && $exists && $hasBooleans) {
            // Actualizar booleanos si el perfil ya existía
            $pdo->prepare("
            UPDATE {$table}
               SET finiquito = :finiquito,
                   antecedente_penal = :antecedente_penal,
                   antecedente_policial = :antecedente_policial,
                   denuncia = :denuncia,
                   updated_at = NOW()
             WHERE person_id = :person_id
        ")->execute(['person_id' => $personId] + $booleans);
        }

        if (!$enabled && $exists) {
            $assignmentCount = 0;

            if ($table === 'candidate_profiles') {
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM candidate_assignments ca INNER JOIN candidate_profiles cp ON cp.id = ca.candidate_profile_id WHERE cp.person_id = :person_id');
                $stmt->execute(['person_id' => $personId]);
                $assignmentCount = (int) $stmt->fetchColumn();
            }

            if ($table === 'affiliate_profiles') {
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM affiliate_assignments aa INNER JOIN affiliate_profiles ap ON ap.id = aa.affiliate_profile_id WHERE ap.person_id = :person_id');
                $stmt->execute(['person_id' => $personId]);
                $assignmentCount = (int) $stmt->fetchColumn();
            }

            if ($assignmentCount > 0) {
                throw new \RuntimeException('No puedes retirar un perfil que ya posee vinculaciones activas.');
            }

            $pdo->prepare("DELETE FROM {$table} WHERE person_id = :person_id")->execute(['person_id' => $personId]);
        }
    }
}
