<div class="page-head">
    <div>
        <h1><?= e($title ?? 'Persona') ?></h1>
        <p class="small">Los campos obligatorios salen del Excel: nombres, apellidos, dirección, teléfono, fecha de nacimiento, profesión y DPI.</p>
    </div>
    <a class="btn-secondary btn" href="<?= e($backUrl ?? '/persons') ?>">Volver</a>
</div>

<?php
$record = $record ?? [];
$currentProfile = $profile ?? 'all';
$form = [
    'first_name' => old('first_name', $record['first_name'] ?? ''),
    'last_name' => old('last_name', $record['last_name'] ?? ''),
    'address' => old('address', $record['address'] ?? ''),
    'phone_primary' => old('phone_primary', $record['phone_primary'] ?? ''),
    'phone_secondary' => old('phone_secondary', $record['phone_secondary'] ?? ''),
    'birth_date' => old('birth_date', $record['birth_date'] ?? ''),
    'profession' => old('profession', $record['profession'] ?? ''),
    'dpi' => old('dpi', $record['dpi'] ?? ''),
    'email' => old('email', $record['email'] ?? ''),
    'is_candidate' => old('is_candidate', !empty($record['candidate_profile_id']) || $currentProfile === 'candidate'),
    'is_leader' => old('is_leader', !empty($record['leader_profile_id']) || $currentProfile === 'leader'),
    'is_affiliate' => old('is_affiliate', !empty($record['affiliate_profile_id']) || $currentProfile === 'affiliate'),
    'leader_department_id' => old('leader_department_id', $record['leader_department_id'] ?? ''),
    'leader_municipality_id' => old('leader_municipality_id', $record['leader_municipality_id'] ?? ''),
    'leader_community_id' => old('leader_community_id', $record['leader_community_id'] ?? ''),
];
?>

<div class="card">
    <form method="post" action="<?= e($submitUrl ?? '/persons/create') ?>">
        <?= csrf_field() ?>
        <div class="form-grid">
            <div>
                <label>Nombres</label>
                <input type="text" name="first_name" value="<?= e($form['first_name']) ?>" required>
            </div>
            <div>
                <label>Apellidos</label>
                <input type="text" name="last_name" value="<?= e($form['last_name']) ?>" required>
            </div>
            <div class="full">
                <label>Dirección</label>
                <input type="text" name="address" value="<?= e($form['address']) ?>" required>
            </div>
            <div>
                <label>Celular 1</label>
                <input type="text" name="phone_primary" value="<?= e($form['phone_primary']) ?>" required>
            </div>
            <div>
                <label>Celular 2</label>
                <input type="text" name="phone_secondary" value="<?= e($form['phone_secondary']) ?>">
            </div>
            <div>
                <label>Fecha de nacimiento</label>
                <input type="date" name="birth_date" value="<?= e($form['birth_date']) ?>" required>
            </div>
            <div>
                <label>Profesión u oficio</label>
                <input type="text" name="profession" value="<?= e($form['profession']) ?>" required>
            </div>
            <div>
                <label>DPI</label>
                <input type="text" name="dpi" maxlength="13" value="<?= e($form['dpi']) ?>" required>
            </div>
            <div>
                <label>Correo</label>
                <input type="email" name="email" value="<?= e($form['email']) ?>">
            </div>
            <div class="full">
                <label>Perfiles</label>
                <div class="checkline">
                    <label><input type="checkbox" name="is_candidate" value="1" <?= checked((bool) $form['is_candidate']) ?>> Candidato</label>
                    <label><input type="checkbox" name="is_leader" value="1" <?= checked((bool) $form['is_leader']) ?>> Líder comunitario</label>
                    <label><input type="checkbox" name="is_affiliate" value="1" <?= checked((bool) $form['is_affiliate']) ?>> Afiliado</label>
                </div>
            </div>
        </div>

        <div class="section-divider">Datos adicionales para líder comunitario</div>

        <div class="form-grid">
            <div>
                <label>Departamento</label>
                <select name="leader_department_id" id="leader_department_id">
                    <option value="">Seleccione</option>
                    <?php foreach ($departments as $row): ?>
                        <option value="<?= e($row['id']) ?>" <?= selected($form['leader_department_id'], $row['id']) ?>><?= e($row['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Municipio</label>
                <select name="leader_municipality_id" id="leader_municipality_id">
                    <option value="">Seleccione</option>
                    <?php foreach ($municipalities as $row): ?>
                        <option value="<?= e($row['id']) ?>" data-department="<?= e($row['department_id']) ?>" <?= selected($form['leader_municipality_id'], $row['id']) ?>><?= e($row['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="full">
                <label>Comunidad (opcional)</label>
                <select name="leader_community_id" id="leader_community_id">
                    <option value="">Seleccione</option>
                    <?php foreach ($communities as $row): ?>
                        <option
                            value="<?= e($row['id']) ?>"
                            data-department="<?= e($row['department_id']) ?>"
                            data-municipality="<?= e($row['municipality_id']) ?>"
                            <?= selected($form['leader_community_id'], $row['id']) ?>>
                            <?= e($row['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="full">
                <button type="submit">Guardar persona</button>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    AppForms.bindLocationFilters('leader_department_id', 'leader_municipality_id', 'leader_community_id');
});
</script>
