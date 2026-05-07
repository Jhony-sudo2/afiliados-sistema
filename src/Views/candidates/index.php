<div class="page-head">
    <div>
        <h1>Vinculación de candidato</h1>
    </div>
</div>

<!-- Tabs -->
<div class="tab-bar">
    <button class="tab-btn <?= !isset($_GET['tab']) || $_GET['tab'] === 'list' ? 'active' : '' ?>"
        onclick="switchTab('list')">Ver vinculaciones</button>
    <button class="tab-btn <?= isset($_GET['tab']) && $_GET['tab'] === 'new' ? 'active' : '' ?>"
        onclick="switchTab('new')"><?= $editRecord ? 'Editar vínculo' : 'Nuevo vínculo' ?></button>
</div>

<!-- Panel: Lista -->
<div id="tab-list" class="tab-panel <?= !isset($_GET['tab']) || $_GET['tab'] === 'list' ? '' : 'hidden' ?>">
    <div class="card">
        <!-- Filtros -->
        <form method="get" action="<?= app_url('candidate-assignments') ?>" class="filter-bar">
            <input type="hidden" name="tab" value="list">
            <select name="f_position">
                <option value="">Todos los puestos</option>
                <?php foreach ($positions as $row): ?>
                    <option value="<?= e($row['id']) ?>" <?= selected($_GET['f_position'] ?? '', $row['id']) ?>>
                        <?= e($row['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="f_status">
                <option value="">Todos los estados</option>
                <option value="1" <?= ($_GET['f_status'] ?? '') === '1' ? 'selected' : '' ?>>Confirmado</option>
                <option value="0" <?= ($_GET['f_status'] ?? '') === '0' ? 'selected' : '' ?>>En espera</option>
            </select>
            <select name="f_department" id="filter_department_id">
                <option value="">Todos los departamentos</option>
                <?php foreach ($departments as $row): ?>
                    <option value="<?= e($row['id']) ?>" <?= selected($_GET['f_department'] ?? '', $row['id']) ?>>
                        <?= e($row['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="f_municipality" id="filter_municipality_id">
                <option value="">Todos los municipios</option>
                <?php foreach ($municipalities as $row): ?>
                    <option value="<?= e($row['id']) ?>" data-department="<?= e($row['department_id']) ?>"
                        <?= selected($_GET['f_municipality'] ?? '', $row['id']) ?>>
                        <?= e($row['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Filtrar</button>
            <a class="btn-secondary btn" href="<?= app_url('candidate-assignments') ?>">Limpiar</a>
        </form>

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
                            <td>
                                <span class="badge <?= $row['confirmed'] ? 'badge-success' : 'badge-pending' ?>">
                                    <?= $row['confirmed'] ? 'Confirmado' : 'En espera' ?>
                                </span>
                            </td>
                            <td><?= e($row['slot'] ?: '-') ?></td>
                            <td class="actions">
                                <a class="btn-secondary btn-sm"
                                    href="<?= e(app_url('candidate-assignments?id=' . urlencode((string) $row['id']) . '&tab=new')) ?>">
                                    Editar
                                </a>
                                <form method="post" action="<?= app_url('candidate-assignments/delete') ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= e($row['id']) ?>">
                                    <button type="submit" class="btn-secondary btn-sm">Eliminar</button>
                                </form>
                                <?php if (!$row['confirmed']): ?>
                                <form method="post" action="<?= app_url('candidate-assignments/confirm') ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= e($row['id']) ?>">
                                    <button type="submit" class="btn-success btn-sm">Confirmar</button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$assignments): ?>
                        <tr><td colspan="7" class="small">No hay vinculaciones visibles.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Panel: Formulario -->
<div id="tab-new" class="tab-panel <?= isset($_GET['tab']) && $_GET['tab'] === 'new' ? '' : 'hidden' ?>">
    <div class="card">
        <h3><?= $editRecord ? 'Editar vínculo' : 'Nuevo vínculo' ?></h3>
<form method="post" action="<?= $editRecord ? app_url('candidate-assignments/edit?id=' . urlencode((string) $editRecord['id'])) : app_url('candidate-assignments/create') ?>">
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
                    <a class="btn-secondary btn" href="<?= app_url('candidate-assignments?tab=list') ?>">Cancelar</a>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function switchTab(tab) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.add('hidden'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + tab).classList.remove('hidden');
    event.target.classList.add('active');
    history.replaceState(null, '', '?tab=' + tab);
}

document.addEventListener('DOMContentLoaded', function () {
    AppForms.bindLocationFilters('candidate_department_id', 'candidate_municipality_id');
    AppForms.bindLocationFilters('filter_department_id', 'filter_municipality_id');
    AppForms.bindPositionRules('candidate_position_id', 'candidate_department_id', 'candidate_municipality_id', 'candidate_slot');
});
</script>