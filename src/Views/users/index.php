<div class="page-head">
    <div>
        <h1>Usuarios</h1>
        <p class="small">Administración de cuentas, correo verificado y alcance territorial.</p>
    </div>
    <a class="btn" href="/users/create">Nuevo usuario</a>
</div>

<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Usuario</th>
                <th>Rol</th>
                <th>Ubicación</th>
                <th>Verificación</th>
                <th>Activo</th>
                <th>Último ingreso</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $row): ?>
                <tr>
                    <td><?= e($row['id']) ?></td>
                    <td>
                        <strong><?= e($row['name']) ?></strong><br>
                        <span class="small"><?= e($row['email']) ?></span>
                    </td>
                    <td><?= e($row['role_name']) ?></td>
                    <td>
                        <span class="small">
                            <?= e($row['department_name'] ?: '-') ?><br>
                            <?= e($row['municipality_name'] ?: '-') ?><br>
                            <?= e($row['community_name'] ?: '-') ?>
                        </span>
                    </td>
                    <td><?= $row['email_verified_at'] ? '<span class="badge success">Verificado</span>' : '<span class="badge warn">Pendiente</span>' ?></td>
                    <td><?= (int) $row['is_active'] === 1 ? 'Sí' : 'No' ?></td>
                    <td><?= e($row['last_login_at'] ?: '-') ?></td>
                    <td class="actions">
                        <a class="btn-secondary btn-sm" href="/users/edit?id=<?= e($row['id']) ?>">Editar</a>
                        <form method="post" action="/users/toggle">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= e($row['id']) ?>">
                            <button type="submit" class="btn-secondary btn-sm"><?= (int) $row['is_active'] === 1 ? 'Desactivar' : 'Activar' ?></button>
                        </form>
                        <?php if (!$row['email_verified_at']): ?>
                            <form method="post" action="/users/resend">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= e($row['id']) ?>">
                                <button type="submit" class="btn-secondary btn-sm">Reenviar correo</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$users): ?>
                <tr><td colspan="8" class="small">No hay usuarios registrados.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
