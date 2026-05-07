<div class="page-head">
    <div>
        <h1>Comunidades</h1>
        <p class="small">Módulo derivado del Excel para registrar comunidades dependientes de departamento y municipio.</p>
    </div>
</div>

<div class="split">
    <div class="card">
        <h3><?= $editRecord ? 'Editar comunidad' : 'Nueva comunidad' ?></h3>
        <form method="post" action="<?= $editRecord ? '/communities/edit?id=' . e($editRecord['id']) : '/communities/create' ?>">
            <?= csrf_field() ?>
            <div class="form-grid">
                <div>
                    <label>Departamento</label>
                    <select name="department_id" id="community_department_id">
                        <option value="">Seleccione</option>
                        <?php $value = old('department_id', $editRecord['department_id'] ?? ''); ?>
                        <?php foreach ($departments as $row): ?>
                            <option value="<?= e($row['id']) ?>" <?= selected($value, $row['id']) ?>><?= e($row['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Municipio</label>
                    <select name="municipality_id" id="community_municipality_id">
                        <option value="">Seleccione</option>
                        <?php $valueMunicipality = old('municipality_id', $editRecord['municipality_id'] ?? ''); ?>
                        <?php foreach ($municipalities as $row): ?>
                            <option value="<?= e($row['id']) ?>" data-department="<?= e($row['department_id']) ?>" <?= selected($valueMunicipality, $row['id']) ?>><?= e($row['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="full">
                    <label>Nombre de comunidad</label>
                    <input type="text" name="name" value="<?= e(old('name', $editRecord['name'] ?? '')) ?>" required>
                </div>
                <div class="full">
                    <button type="submit"><?= $editRecord ? 'Actualizar' : 'Registrar' ?></button>
                    <?php if ($editRecord): ?>
<a class="btn-secondary btn" href="<?= app_url('communities') ?>">Cancelar</a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>

    <div class="card">
        <h3>Listado</h3>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Comunidad</th>
                        <th>Departamento</th>
                        <th>Municipio</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($communities as $row): ?>
                        <tr>
                            <td><?= e($row['id']) ?></td>
                            <td><?= e($row['name']) ?></td>
                            <td><?= e($row['department_name']) ?></td>
                            <td><?= e($row['municipality_name']) ?></td>
                            <td><?= (int) $row['is_active'] === 1 ? 'Activo' : 'Inactivo' ?></td>
                            <td class="actions">
                                <a class="btn-secondary btn-sm" href="<?= e(app_url('communities?id=' . urlencode((string) $row['id']))) ?>">Editar</a>
                                <?php if ((int) $row['is_active'] === 1): ?>
<form method="post" action="<?= app_url('communities/delete') ?>">
                                    <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= e($row['id']) ?>">
                                        <button type="submit" class="btn-secondary btn-sm">Desactivar</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$communities): ?>
                        <tr><td colspan="6" class="small">No hay comunidades visibles.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    AppForms.bindLocationFilters('community_department_id', 'community_municipality_id');
});
</script>
