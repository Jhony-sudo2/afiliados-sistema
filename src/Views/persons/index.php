<div class="page-head">
    <div>
        <h1><?= e($pageTitle ?? 'Personas') ?></h1>
        <p class="small"><?= e($pageDescription ?? 'Registro unificado de candidato, líder comunitario y afiliado persona.') ?></p>
    </div>
    <a class="btn" href="<?= app_url('persons/create' . ($profile !== 'all' ? '?profile=' . urlencode((string) $profile) : '')) ?>">
    <?= e($createLabel ?? 'Nueva persona') ?>
</a>
</div>

<div class="card">
<form method="get" action="<?= app_url('persons') ?>" class="filters">
            <div>
            <label>Perfil</label>
            <select name="profile">
                <option value="all" <?= selected($profile, 'all') ?>>Todos</option>
                <option value="candidate" <?= selected($profile, 'candidate') ?>>Candidato persona</option>
                <option value="leader" <?= selected($profile, 'leader') ?>>Líder comunitario</option>
                <option value="affiliate" <?= selected($profile, 'affiliate') ?>>Afiliado persona</option>
            </select>
        </div>
        <div>
            <label>Búsqueda</label>
            <input type="text" name="q" value="<?= e($search) ?>" placeholder="Nombre o DPI">
        </div>
        <div class="filter-actions">
            <button type="submit">Filtrar</button>
<a class="btn-secondary btn" href="<?= app_url('persons' . ($profile !== 'all' ? '?profile=' . urlencode((string) $profile) : '')) ?>">
    Limpiar
</a>        </div>
    </form>
</div>

<div class="table-wrap" style="margin-top:18px;">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Persona</th>
                <th>DPI</th>
                <th>Perfiles</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($persons as $row): ?>
                <tr>
                    <td><?= e($row['id']) ?></td>
                    <td>
                        <strong><?= e($row['first_name'] . ' ' . $row['last_name']) ?></strong><br>
                        <span class="small"><?= e($row['profession']) ?></span>
                    </td>
                    <td><?= e($row['dpi']) ?></td>
                    <td>
                        <?php if ($row['candidate_profile_id']): ?><span class="badge">Candidato</span><?php endif; ?>
                        <?php if ($row['leader_profile_id']): ?><span class="badge">Líder</span><?php endif; ?>
                        <?php if ($row['affiliate_profile_id']): ?><span class="badge">Afiliado</span><?php endif; ?>
                    </td>
                    <td>
    <a class="btn-secondary btn-sm"
       href="<?= app_url('persons/edit?id=' . urlencode((string) $row['id']) . ($profile !== 'all' ? '&profile=' . urlencode((string) $profile) : '')) ?>">
        Editar
    </a>
</td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$persons): ?>
                <tr><td colspan="5" class="small">No hay personas visibles.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
