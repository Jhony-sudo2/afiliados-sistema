<div class="page-head">
    <div>
        <h1><?= e($title ?? 'Usuario') ?></h1>
        <p class="small">Asigna rol y alcance geográfico del usuario.</p>
    </div>
    <a class="btn-secondary btn" href="/users">Volver</a>
</div>

<?php
$record = $record ?? [];
$form = [
    'name' => old('name', $record['name'] ?? ''),
    'email' => old('email', $record['email'] ?? ''),
    'role_id' => old('role_id', $record['role_id'] ?? ''),
    'department_id' => old('department_id', $record['department_id'] ?? ''),
    'municipality_id' => old('municipality_id', $record['municipality_id'] ?? ''),
    'community_id' => old('community_id', $record['community_id'] ?? ''),
];
?>

<div class="card">
    <form method="post" action="<?= e($submitUrl ?? '/users/create') ?>">
        <?= csrf_field() ?>
        <div class="form-grid">
            <div>
                <label>Nombre</label>
                <input type="text" name="name" value="<?= e($form['name']) ?>" required>
            </div>
            <div>
                <label>Correo</label>
                <input type="email" name="email" value="<?= e($form['email']) ?>" required>
            </div>
            <div>
                <label>Rol</label>
                <select name="role_id" required>
                    <option value="">Seleccione</option>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?= e($role['id']) ?>" <?= selected($form['role_id'], $role['id']) ?>><?= e($role['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label><?= ($record ?? null) ? 'Nueva contraseña (opcional)' : 'Contraseña' ?></label>
                <input type="password" name="password" <?= ($record ?? null) ? '' : 'required' ?>>
            </div>
            <div>
                <label>Departamento</label>
                <select name="department_id" id="user_department_id" data-selected="<?= e((string) $form['department_id']) ?>">
                    <option value="">Seleccione</option>
                    <?php foreach ($departments as $row): ?>
                        <option value="<?= e($row['id']) ?>" <?= selected($form['department_id'], $row['id']) ?>><?= e($row['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Municipio</label>
                <select name="municipality_id" id="user_municipality_id" data-selected="<?= e((string) $form['municipality_id']) ?>">
                    <option value="">Seleccione</option>
                    <?php foreach ($municipalities as $row): ?>
                        <option value="<?= e($row['id']) ?>" data-department="<?= e($row['department_id']) ?>" <?= selected($form['municipality_id'], $row['id']) ?>><?= e($row['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="full">
                <label>Comunidad</label>
                <select name="community_id" id="user_community_id" data-selected="<?= e((string) $form['community_id']) ?>">
                    <option value="">Seleccione</option>
                    <?php foreach ($communities as $row): ?>
                        <option
                            value="<?= e($row['id']) ?>"
                            data-department="<?= e($row['department_id']) ?>"
                            data-municipality="<?= e($row['municipality_id']) ?>"
                            <?= selected($form['community_id'], $row['id']) ?>>
                            <?= e($row['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="full">
                <button type="submit">Guardar usuario</button>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    AppForms.bindLocationFilters('user_department_id', 'user_municipality_id', 'user_community_id');
});
</script>
