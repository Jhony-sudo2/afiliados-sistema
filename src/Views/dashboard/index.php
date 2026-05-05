<div class="page-head">
    <div>
        <h1>Dashboard</h1>
        <p class="small">Resumen general del sistema según el alcance del usuario autenticado.</p>
    </div>
</div>

<div class="grid-4">
    <?php foreach ($stats as $label => $value): ?>
        <div class="stat">
            <div class="small"><?= e(ucwords(str_replace('_', ' ', $label))) ?></div>
            <div class="num"><?= e((string) $value) ?></div>
        </div>
    <?php endforeach; ?>
</div>

<div class="card" style="margin-top:18px;">
    <h3>Últimas personas visibles</h3>
    <p class="small"><?= e($scopeLabel ?? '') ?></p>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>DPI</th>
                    <th>Creación</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentPersons as $row): ?>
                    <tr>
                        <td><?= e($row['id']) ?></td>
                        <td><?= e($row['full_name']) ?></td>
                        <td><?= e($row['dpi']) ?></td>
                        <td><?= e($row['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$recentPersons): ?>
                    <tr><td colspan="4" class="small">No hay registros visibles.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
