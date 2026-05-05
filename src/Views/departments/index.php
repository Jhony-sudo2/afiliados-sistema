<div class="page-head">
    <div>
        <h1>Departamentos y municipios</h1>
        <p class="small">Catálogos base cargados desde el Excel. Los municipios dependen del departamento.</p>
    </div>
</div>

<div class="card">
    <h3>Departamentos</h3>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Departamento</th>
                    <th>Total municipios</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($departments as $row): ?>
                    <tr>
                        <td><?= e($row['id']) ?></td>
                        <td><?= e($row['name']) ?></td>
                        <td><?= e($row['municipality_count']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card" style="margin-top:18px;">
    <h3>Municipios</h3>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID municipio</th>
                    <th>Departamento</th>
                    <th>Municipio</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($municipalities as $row): ?>
                    <tr>
                        <td><?= e($row['id']) ?></td>
                        <td><?= e($row['department_name']) ?></td>
                        <td><?= e($row['name']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
