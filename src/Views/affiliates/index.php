<div class="page-head">
    <div>
        <h1>Vinculación de afiliado</h1>
        <p class="small">El departamento y municipio son obligatorios; comunidad y líder comunitario son opcionales.</p>
    </div>
</div>

<div class="split">
    <div class="card">
        <h3><?= $editRecord ? 'Editar vínculo' : 'Nuevo vínculo' ?></h3>
        <form method="post" action="<?= $editRecord ? '/affiliate-assignments/edit?id=' . e($editRecord['id']) : '/affiliate-assignments/create' ?>">
            <?= csrf_field() ?>
            <div class="form-grid">
                <div class="full">
                    <label>Afiliado</label>
                    <?php $val = old('affiliate_profile_id', $editRecord['affiliate_profile_id'] ?? ''); ?>
                    <select name="affiliate_profile_id" required>
                        <option value="">Seleccione</option>
                        <?php foreach ($affiliates as $row): ?>
                            <option value="<?= e($row['id']) ?>" <?= selected($val, $row['id']) ?>><?= e($row['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Departamento</label>
                    <?php $depVal = old('department_id', $editRecord['department_id'] ?? ''); ?>
                    <select name="department_id" id="affiliate_department_id" required>
                        <option value="">Seleccione</option>
                        <?php foreach ($departments as $row): ?>
                            <option value="<?= e($row['id']) ?>" <?= selected($depVal, $row['id']) ?>><?= e($row['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Municipio</label>
                    <?php $munVal = old('municipality_id', $editRecord['municipality_id'] ?? ''); ?>
                    <select name="municipality_id" id="affiliate_municipality_id" required>
                        <option value="">Seleccione</option>
                        <?php foreach ($municipalities as $row): ?>
                            <option value="<?= e($row['id']) ?>" data-department="<?= e($row['department_id']) ?>" <?= selected($munVal, $row['id']) ?>><?= e($row['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Comunidad</label>
                    <?php $comVal = old('community_id', $editRecord['community_id'] ?? ''); ?>
                    <select name="community_id" id="affiliate_community_id">
                        <option value="">Seleccione</option>
                        <?php foreach ($communities as $row): ?>
                            <option value="<?= e($row['id']) ?>" data-department="<?= e($row['department_id']) ?>" data-municipality="<?= e($row['municipality_id']) ?>" <?= selected($comVal, $row['id']) ?>><?= e($row['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Líder comunitario</label>
                    <?php $leaderVal = old('leader_profile_id', $editRecord['leader_profile_id'] ?? ''); ?>
                    <select name="leader_profile_id" id="affiliate_leader_profile_id">
                        <option value="">Seleccione</option>
                        <?php foreach ($leaders as $row): ?>
                            <option value="<?= e($row['id']) ?>" data-department="<?= e($row['department_id']) ?>" data-municipality="<?= e($row['municipality_id']) ?>" data-community="<?= e($row['community_id']) ?>" <?= selected($leaderVal, $row['id']) ?>><?= e($row['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="full">
                    <label>Notas</label>
                    <textarea name="notes"><?= e(old('notes', $editRecord['notes'] ?? '')) ?></textarea>
                </div>
                <div class="full">
                    <button type="submit"><?= $editRecord ? 'Actualizar' : 'Registrar' ?></button>
                    <?php if ($editRecord): ?>
    <a class="btn-secondary btn" href="<?= app_url('affiliate-assignments') ?>">Cancelar</a>
<?php endif; ?>
                </div>
            </div>
        </form>
    </div>

    <div class="card">
        <h3>Vinculaciones registradas</h3>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Afiliado</th>
                        <th>Departamento</th>
                        <th>Municipio</th>
                        <th>Comunidad</th>
                        <th>Líder</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($assignments as $row): ?>
                        <tr>
                            <td><?= e($row['affiliate_name']) ?></td>
                            <td><?= e($row['department_name']) ?></td>
                            <td><?= e($row['municipality_name']) ?></td>
                            <td><?= e($row['community_name'] ?: '-') ?></td>
                            <td><?= e($row['leader_name'] ?: '-') ?></td>
                            <td class="actions">
<a class="btn-secondary btn-sm" href="<?= e(app_url('affiliate-assignments?id=' . urlencode((string) $row['id']))) ?>">Editar</a>                                
                                <form method="post" action="/affiliate-assignments/delete">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= e($row['id']) ?>">
                                    <button type="submit" class="btn-secondary btn-sm">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$assignments): ?>
                        <tr><td colspan="6" class="small">No hay vinculaciones visibles.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    AppForms.bindLocationFilters('affiliate_department_id', 'affiliate_municipality_id', 'affiliate_community_id');
    AppForms.bindLeaderFilters('affiliate_department_id', 'affiliate_municipality_id', 'affiliate_community_id', 'affiliate_leader_profile_id');
});
</script>
