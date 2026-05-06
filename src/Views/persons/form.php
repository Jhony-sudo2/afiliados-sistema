<div class="page-head">
    <div>
        <h1><?= e($title ?? 'Persona') ?></h1>
        <p class="small">Los campos obligatorios salen del Excel: nombres, apellidos, dirección, teléfono, fecha de
            nacimiento, profesión y DPI.</p>
    </div>
    <a class="btn-secondary btn" href="<?= e($backUrl ?? '/persons') ?>">Volver</a>
</div>

<?php
$record = $record ?? [];
$currentProfile = $profile ?? 'all';
$isLeader = $currentProfile === 'leader';
$isCandidate = $currentProfile === 'candidate';

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
    'is_candidate' => old('is_candidate', !empty($record['candidate_profile_id']) || $isCandidate),
    'is_leader' => old('is_leader', !empty($record['leader_profile_id']) || $isLeader),
    'is_affiliate' => old('is_affiliate', !empty($record['affiliate_profile_id']) || $currentProfile === 'affiliate'),
    'leader_department_id' => old('leader_department_id', $record['leader_department_id'] ?? ''),
    'leader_municipality_id' => old('leader_municipality_id', $record['leader_municipality_id'] ?? ''),
    'leader_community_id' => old('leader_community_id', $record['leader_community_id'] ?? ''),
    'leader_type_id' => old('leader_type_id',$record['leader_type_id'] ?? ''),
    'leader_region_id' => old('leader_region_id',$record['region_id'] ?? ''),
    // Nuevos campos booleanos
    'finiquito' => old(
        'finiquito',
        $currentProfile === 'candidate'
        ? ($record['candidate_finiquito'] ?? false)
        : ($record['leader_finiquito'] ?? false)
    ),
    'antecedente_penal' => old(
        'antecedente_penal',
        $currentProfile === 'candidate'
        ? ($record['candidate_antecedente_penal'] ?? false)
        : ($record['leader_antecedente_penal'] ?? false)
    ),
    'antecedente_policial' => old(
        'antecedente_policial',
        $currentProfile === 'candidate'
        ? ($record['candidate_antecedente_policial'] ?? false)
        : ($record['leader_antecedente_policial'] ?? false)
    ),
    'denuncia' => old(
        'denuncia',
        $currentProfile === 'candidate'
        ? ($record['candidate_denuncia'] ?? false)
        : ($record['leader_denuncia'] ?? false)
    ),
    'no_empadronamiento' => old('no_empadronamiento', $record['no_empadronamiento'] ?? ''),
    'centro_votacion' => old('centro_votacion', $record['centro_votacion'] ?? ''),

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
                <label>No. de empadronamiento</label>
                <input type="text" name="no_empadronamiento" maxlength="30"
                    value="<?= e($form['no_empadronamiento']) ?>">
            </div>
            <div>
                <label>Centro de votación</label>
                <input type="text" name="centro_votacion" maxlength="30" value="<?= e($form['centro_votacion']) ?>">
            </div>
            <div>
                <label>Correo</label>
                <input type="email" name="email" value="<?= e($form['email']) ?>">
            </div>
            <div class="full">
                <label>Perfiles</label>
                <div class="checkline">
                    <label><input type="checkbox" name="is_candidate" value="1" <?= checked((bool) $form['is_candidate']) ?>> Candidato</label>
                    <label><input type="checkbox" name="is_leader" value="1" <?= checked((bool) $form['is_leader']) ?>>
                        Líder comunitario</label>
                    <label><input type="checkbox" name="is_affiliate" value="1" <?= checked((bool) $form['is_affiliate']) ?>> Afiliado</label>
                </div>
            </div>
        </div>

        <?php if ($isLeader || $isCandidate): ?>
            <div class="section-divider">Documentos y antecedentes</div>

            <div class="form-grid">
                <div class="full">
                    <div class="checkline">
                        <label>
                            <input type="checkbox" name="finiquito" value="1" <?= checked((bool) $form['finiquito']) ?>>
                            Finiquito
                        </label>
                        <label>
                            <input type="checkbox" name="antecedente_penal" value="1" <?= checked((bool) $form['antecedente_penal']) ?>>
                            Antecedente penal
                        </label>
                        <label>
                            <input type="checkbox" name="antecedente_policial" value="1" <?= checked((bool) $form['antecedente_policial']) ?>>
                            Antecedente policial
                        </label>
                        <label>
                            <input type="checkbox" name="denuncia" value="1" <?= checked((bool) $form['denuncia']) ?>>
                            Denuncia
                        </label>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($isLeader): ?>
            <div class="section-divider">Datos adicionales para líder comunitario</div>

            <div class="form-grid">
                <div>
                    <label>Tipo de líder</label>
                    <select name="leader_type_id" id="leader_type_id">
                        <option value="">Seleccione</option>
                        <?php foreach ($leader_types as $row): ?>
                            <option value="<?= e($row['id']) ?>" <?= selected($form['leader_type_id'], $row['id']) ?>>
                                <?= e($row['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div id="field_region" style="display:none">
                    <label>Región</label>
                    <select name="leader_region_id" id="leader_region_id">
                        <option value="">Seleccione</option>
                        <?php foreach ($regions as $row): ?>
                            <option value="<?= e($row['id']) ?>" <?= selected($form['leader_region_id'], $row['id']) ?>>
                                <?= e($row['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div id="field_department" style="display:none">
                    <label>Departamento</label>
                    <select name="leader_department_id" id="leader_department_id">
                        <option value="">Seleccione</option>
                        <?php foreach ($departments as $row): ?>
                            <option value="<?= e($row['id']) ?>" <?= selected($form['leader_department_id'], $row['id']) ?>>
                                <?= e($row['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div id="field_municipality" style="display:none">
                    <label>Municipio</label>
                    <select name="leader_municipality_id" id="leader_municipality_id">
                        <option value="">Seleccione</option>
                        <?php foreach ($municipalities as $row): ?>
                            <option value="<?= e($row['id']) ?>" data-department="<?= e($row['department_id']) ?>"
                                <?= selected($form['leader_municipality_id'], $row['id']) ?>>
                                <?= e($row['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div id="field_community" class="full" style="display:none">
                    <label>Comunidad (opcional)</label>
                    <select name="leader_community_id" id="leader_community_id">
                        <option value="">Seleccione</option>
                        <?php foreach ($communities as $row): ?>
                            <option value="<?= e($row['id']) ?>" data-department="<?= e($row['department_id']) ?>"
                                data-municipality="<?= e($row['municipality_id']) ?>" <?= selected($form['leader_community_id'], $row['id']) ?>>
                                <?= e($row['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        <?php endif; ?>


        <div class="form-grid">
            <div class="full">
                <button type="submit">Guardar persona</button>
            </div>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const typeSelect = document.getElementById('leader_type_id');
                if (!typeSelect) return;

                // Campos por tipo de líder:
                // 1 = MICRO-REGION  → región, departamento, municipio, comunidad
                // 2 = MUNICIPAL     → departamento, municipio
                // 3 = REGIONAL      → región
                // 4 = DEPARTAMENTAL → departamento
                // 5 = NACIONAL      → nada
                const rules = {
                    '1': ['field_region', 'field_department', 'field_municipality', 'field_community'],
                    '2': ['field_department', 'field_municipality'],
                    '3': ['field_region'],
                    '4': ['field_department'],
                    '5': [],
                };

                const allFields = ['field_region', 'field_department', 'field_municipality', 'field_community'];

                function applyRules(val) {
                    const show = rules[val] ?? [];
                    allFields.forEach(id => {
                        const el = document.getElementById(id);
                        if (el) el.style.display = show.includes(id) ? '' : 'none';
                    });

                    // Limpiar selects ocultos para no enviar valores basura
                    allFields.forEach(id => {
                        if (!show.includes(id)) {
                            const sel = document.querySelector(`#${id} select`);
                            if (sel) sel.value = '';
                        }
                    });

                    // Filtros encadenados depto → municipio solo cuando ambos están visibles
                    if (show.includes('field_municipality')) {
                        AppForms.bindLocationFilters('leader_department_id', 'leader_municipality_id', 'leader_community_id');
                    }
                }

                typeSelect.addEventListener('change', () => applyRules(typeSelect.value));

                // Aplicar al cargar (modo edición)
                applyRules(typeSelect.value);
            });
        </script>
        <?php if ($isLeader): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    AppForms.bindLocationFilters('leader_department_id', 'leader_municipality_id', 'leader_community_id');
                });
            </script>
        <?php endif; ?>