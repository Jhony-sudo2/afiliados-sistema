<div class="page-head">
    <div>
        <h1>Puestos</h1>
        <p class="small">Configura los puestos y sus reglas de asignación: departamento, municipio y casilla.</p>
    </div>
</div>

<div class="split">
    <div class="card">
        <h3><?= $editRecord ? 'Editar puesto' : 'Nuevo puesto' ?></h3>
        <form method="post" action="<?= $editRecord ? '/positions/edit?id=' . e($editRecord['id']) : '/positions/create' ?>">
            <?= csrf_field() ?>
            <div class="form-grid">
                <div class="full">
                    <label>Nombre</label>
                    <input type="text" name="name" value="<?= e(old('name', $editRecord['name'] ?? '')) ?>" required>
                </div>
                <div class="full checkline">
                    <?php
                    $reqDep = (bool) old('requires_department', $editRecord['requires_department'] ?? false);
                    $reqMun = (bool) old('requires_municipality', $editRecord['requires_municipality'] ?? false);
                    $reqSlot = (bool) old('requires_slot', $editRecord['requires_slot'] ?? false);
                    ?>
                    <label><input type="checkbox" name="requires_department" value="1" <?= checked($reqDep) ?>> Requiere departamento</label>
                    <label><input type="checkbox" name="requires_municipality" value="1" <?= checked($reqMun) ?>> Requiere municipio</label>
                    <label><input type="checkbox" name="requires_slot" value="1" <?= checked($reqSlot) ?>> Requiere casilla</label>
                </div>
                <div>
                    <label>Casilla mínima</label>
                    <input type="number" name="slot_min" value="<?= e(old('slot_min', $editRecord['slot_min'] ?? '')) ?>">
                </div>
                <div>
                    <label>Casilla máxima</label>
                    <input type="number" name="slot_max" value="<?= e(old('slot_max', $editRecord['slot_max'] ?? '')) ?>">
                </div>
                <div class="full">
                    <button type="submit"><?= $editRecord ? 'Actualizar' : 'Registrar' ?></button>
                    <?php if ($editRecord): ?>
                        <a class="btn-secondary btn" href="<?= app_url('positions') ?>">Cancelar</a>
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
                        <th>Puesto</th>
                        <th>Departamento</th>
                        <th>Municipio</th>
                        <th>Casilla</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($positions as $row): ?>
                        <tr>
                            <td><?= e($row['name']) ?></td>
                            <td><?= bool_icon($row['requires_department']) ?></td>
                            <td><?= bool_icon($row['requires_municipality']) ?></td>
                            <td><?= $row['requires_slot'] ? e(($row['slot_min'] ?? '-') . ' a ' . ($row['slot_max'] ?? '-')) : 'No' ?></td>
                            <td class="actions">
                                <?php if (has_role('ADMINISTRADOR')): ?>
                                    <a class="btn-secondary btn-sm" href="<?= app_url('positions?id=' . urlencode((string) $row['id'])) ?>">
    Editar
</a>
                                    <?php if ((int) $row['is_active'] === 1): ?>
<form method="post" action="<?= app_url('positions/delete') ?>">                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= e($row['id']) ?>">
                                            <button type="submit" class="btn-secondary btn-sm">Desactivar</button>
                                        </form>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="small">Solo lectura</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$positions): ?>
                        <tr><td colspan="5" class="small">No hay puestos registrados.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
