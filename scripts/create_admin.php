<?php

declare(strict_types=1);

use App\Core\Database;

require dirname(__DIR__) . '/src/bootstrap.php';

if ($argc < 4) {
    echo "Uso: php scripts/create_admin.php correo contraseña nombre [departamento_id] [municipio_id]\n";
    exit(1);
}

[$script, $email, $password, $name] = $argv;
$departmentId = $argv[4] ?? null;
$municipalityId = $argv[5] ?? null;

$pdo = Database::connection();
$roleId = (int) $pdo->query("SELECT id FROM roles WHERE name = 'ADMINISTRADOR' LIMIT 1")->fetchColumn();

$stmt = $pdo->prepare('
    INSERT INTO users (
        role_id, department_id, municipality_id, community_id, name, email,
        password_hash, verification_token, email_verified_at, is_active, created_at, updated_at
    ) VALUES (
        :role_id, :department_id, :municipality_id, NULL, :name, :email,
        :password_hash, NULL, NOW(), 1, NOW(), NOW()
    )
');

$stmt->execute([
    'role_id' => $roleId,
    'department_id' => $departmentId ?: null,
    'municipality_id' => $municipalityId ?: null,
    'name' => $name,
    'email' => mb_strtolower($email),
    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
]);

echo "Administrador creado correctamente.\n";
