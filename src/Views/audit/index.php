<div class="page-head">
    <div>
        <h1>Auditoría</h1>
        <p class="small">Registro básico de operaciones críticas del sistema.</p>
    </div>
</div>

<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Usuario</th>
                <th>Módulo</th>
                <th>Acción</th>
                <th>Registro</th>
                <th>IP</th>
                <th>Payload</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($logs as $row): ?>
                <tr>
                    <td><?= e($row['created_at']) ?></td>
                    <td><?= e($row['user_name'] ?: 'Sistema') ?></td>
                    <td><?= e($row['module_name']) ?></td>
                    <td><?= e($row['action_name']) ?></td>
                    <td><?= e($row['record_id'] ?: '-') ?></td>
                    <td><?= e($row['ip_address'] ?: '-') ?></td>
                    <td><code><?= e((string) $row['payload_json']) ?></code></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$logs): ?>
                <tr><td colspan="7" class="small">Sin eventos de auditoría.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
