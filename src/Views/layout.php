<?php
$success = flash('success');
$error = flash('error');
$user = auth_user();
$isAuthView = str_contains($contentView ?? '', DIRECTORY_SEPARATOR . 'auth' . DIRECTORY_SEPARATOR);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(app_env('APP_NAME', 'Sistema')) ?></title>

    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
    <script defer src="<?= asset('js/app.js') ?>"></script>
</head>
<body>
<?php if ($isAuthView): ?>
    <div class="auth-wrap">
        <div class="auth-card card">
            <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
            <?php require $contentView; ?>
        </div>
    </div>
<?php else: ?>
    <div class="layout">
        <aside class="sidebar">
            <h2><?= e(app_env('APP_NAME', 'Sistema')) ?></h2>
            <div class="small sidebar-user">
                <strong><?= e($user['name'] ?? '') ?></strong><br>
                <?= e($user['role'] ?? '') ?>
            </div>

            <a class="<?= active_menu(['/']) ?>" href="<?= app_url() ?>">Dashboard</a>

            <?php if (has_role('ADMINISTRADOR')): ?>
                <a class="<?= active_menu(['/users', '/users/create', '/users/edit']) ?>" href="<?= app_url('users') ?>">Usuarios</a>
            <?php endif; ?>

            <a class="<?= active_menu(['/departments']) ?>" href="<?= app_url('departments') ?>">Departamentos y municipios</a>
            <a class="<?= active_menu(['/communities']) ?>" href="<?= app_url('communities') ?>">Comunidad</a>
            <a class="<?= active_menu(['/positions']) ?>" href="<?= app_url('positions') ?>">Puesto</a>
            <a class="<?= active_menu(['/persons']) ?>" href="<?= app_url('persons?profile=candidate') ?>">Candidato persona</a>
            <a class="<?= active_menu(['/persons']) ?>" href="<?= app_url('persons?profile=leader') ?>">Líder comunitario</a>
            <a class="<?= active_menu(['/persons']) ?>" href="<?= app_url('persons?profile=affiliate') ?>">Afiliado persona</a>

            <?php if (has_role('ADMINISTRADOR', 'DELEGADO DEPARTAMENTAL', 'DELEGADO MUNICIPAL')): ?>
                <a class="<?= active_menu(['/candidate-assignments']) ?>" href="<?= app_url('candidate-assignments') ?>">Candidato</a>
            <?php endif; ?>

            <a class="<?= active_menu(['/affiliate-assignments']) ?>" href="<?= app_url('affiliate-assignments') ?>">Afiliado</a>
            <a class="<?= active_menu(['/reports']) ?>" href="<?= app_url('reports') ?>">Reportes</a>

            <?php if (has_role('ADMINISTRADOR')): ?>
                <a class="<?= active_menu(['/audit-logs']) ?>" href="<?= app_url('audit-logs') ?>">Auditoría</a>
            <?php endif; ?>
        </aside>

        <main class="content">
            <div class="topbar">
                <div class="small">
                    <?= e(App\Core\AccessScope::describe()) ?>
                </div>

                <form method="post" action="<?= app_url('logout') ?>">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn-secondary">Cerrar sesión</button>
                </form>
            </div>

            <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

            <?php require $contentView; ?>
        </main>
    </div>
<?php endif; ?>
</body>
</html>