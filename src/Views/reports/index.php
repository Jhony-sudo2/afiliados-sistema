<div class="page-head">
    <div>
        <h1>Reportes</h1>
        <p class="small">Exportación rápida a CSV y resúmenes por ubicación.</p>
    </div>
    <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <a class="btn-secondary btn" href="/reports/export?type=candidates">Exportar candidatos</a>
        <a class="btn-secondary btn" href="/reports/export?type=leaders">Exportar líderes</a>
        <a class="btn-secondary btn" href="/reports/export?type=affiliates">Exportar afiliados</a>
    </div>
</div>

<div class="split">
    <div class="card">
        <h3>Resumen de candidatos por departamento</h3>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Departamento</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($candidateSummary as $row): ?>
                        <tr>
                            <td><?= e($row['department_name'] ?: 'Sin departamento') ?></td>
                            <td><?= e($row['total']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$candidateSummary): ?>
                        <tr><td colspan="2" class="small">Sin datos.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <h3>Resumen de afiliados por municipio</h3>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Departamento</th>
                        <th>Municipio</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($affiliateSummary as $row): ?>
                        <tr>
                            <td><?= e($row['department_name']) ?></td>
                            <td><?= e($row['municipality_name']) ?></td>
                            <td><?= e($row['total']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$affiliateSummary): ?>
                        <tr><td colspan="3" class="small">Sin datos.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <h3>Resumen de líderes por municipio</h3>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Departamento</th>
                        <th>Municipio</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($leaderSummary as $row): ?>
                        <tr>
                            <td><?= e($row['department_name']) ?></td>
                            <td><?= e($row['municipality_name']) ?></td>
                            <td><?= e($row['total']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$leaderSummary): ?>
                        <tr><td colspan="3" class="small">Sin datos.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
