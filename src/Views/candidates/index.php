<div class="page-head">
    <div>
        <h1>Vinculación de candidato</h1>
    </div>
</div>

<div class="split">
    <div class="card">
        <h3><?= $editRecord ? 'Editar vínculo' : 'Nuevo vínculo' ?></h3>
        <form method="post" action="<?= $editRecord ? '/candidate-assignments/edit?id=' . e($editRecord['id']) : '/candidate-assignments/create' ?>">
            <?= csrf_field() ?>
            <div class="form-grid">
                <div class="full">
                    <label>Candidato</label>
                    <?php $val = old('candidate_profile_id', $editRecord['candidate_profile_id'] ?? ''); ?>
                    <select name="candidate_profile_id" required>
                        <option value="">Seleccione</option>
                        <?php foreach ($candidates as $row): ?>
                            <option value="<?= e($row['id']) ?>" <?= selected($val, $row['id']) ?>><?= e($row['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="full">
                    <label>Puesto</label>
                    <?php $posVal = old('position_id', $editRecord['position_id'] ?? ''); ?>
                    <select name="position_id" id="candidate_position_id" required>
                        <option value="">Seleccione</option>
                        <?php foreach ($positions as $row): ?>
                            <option
                                value="<?= e($row['id']) ?>"
                                data-requires-department="<?= e($row['requires_department']) ?>"
                                data-requires-municipality="<?= e($row['requires_municipality']) ?>"
                                data-requires-slot="<?= e($row['requires_slot']) ?>"
                                data-slot-min="<?= e($row['slot_min'] ?? '') ?>"
                                data-slot-max="<?= e($row['slot_max'] ?? '') ?>"
                                <?= selected($posVal, $row['id']) ?>>
                                <?= e($row['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Departamento</label>
                    <?php $depVal = old('department_id', $editRecord['department_id'] ?? ''); ?>
                    <select name="department_id" id="candidate_department_id">
                        <option value="">Seleccione</option>
                        <?php foreach ($departments as $row): ?>
                            <option value="<?= e($row['id']) ?>" <?= selected($depVal, $row['id']) ?>><?= e($row['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Municipio</label>
                    <?php $munVal = old('municipality_id', $editRecord['municipality_id'] ?? ''); ?>
                    <select name="municipality_id" id="candidate_municipality_id">
                        <option value="">Seleccione</option>
                        <?php foreach ($municipalities as $row): ?>
                            <option value="<?= e($row['id']) ?>" data-department="<?= e($row['department_id']) ?>" <?= selected($munVal, $row['id']) ?>><?= e($row['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Casilla</label>
                    <input type="number" name="slot" id="candidate_slot" value="<?= e(old('slot', $editRecord['slot'] ?? '')) ?>">
                </div>
                <div class="full">
                    <label>Notas</label>
                    <textarea name="notes"><?= e(old('notes', $editRecord['notes'] ?? '')) ?></textarea>
                </div>
                <div class="full">
                    <button type="submit"><?= $editRecord ? 'Actualizar' : 'Registrar' ?></button>
                    <?php if ($editRecord): ?>
<a class="btn-secondary btn" href="<?= app_url('candidate-assignments') ?>">Cancelar</a>
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
                        <th>Candidato</th>
                        <th>Puesto</th>
                        <th>Departamento</th>
                        <th>Municipio</th>
                        <th>Estado</th>
                        <th>Casilla</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($assignments as $row): ?>
                        <tr>
                            <td><?= e($row['candidate_name']) ?></td>
                            <td><?= e($row['position_name']) ?></td>
                            <td><?= e($row['department_name'] ?: '-') ?></td>
                            <td><?= e($row['municipality_name'] ?: '-') ?></td>
                            <td> <?= $row['confirmed'] ? 'Confirmado' : 'En espera' ?> </td>
                            <td><?= e($row['slot'] ?: '-') ?></td>
                            <td class="actions">
                                <a class="btn-secondary btn-sm" href="<?= e(app_url('candidate-assignments?id=' . urlencode((string) $row['id']))) ?>">Editar</a>
                                <form method="post" action="/candidate-assignments/delete">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= e($row['id']) ?>">
                                    <button type="submit" class="btn-secondary btn-sm">Eliminar</button>
                                </form>
                                <form method="post" action="/candidate-assignments/confirm">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= e($row['id']) ?>">
                                    <button type="submit" class="btn-secondary btn-sm">Confirmar</button>
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
    AppForms.bindLocationFilters('candidate_department_id', 'candidate_municipality_id');
    AppForms.bindPositionRules('candidate_position_id', 'candidate_department_id', 'candidate_municipality_id', 'candidate_slot');
});
</script>
