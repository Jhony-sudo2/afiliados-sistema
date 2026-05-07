<h1>Restablecer contraseña</h1>
<p class="small">La nueva contraseña debe contener al menos 8 caracteres.</p>

<form method="post" action="<?= app_url('reset-password') ?>">
        <?= csrf_field() ?>
    <input type="hidden" name="token" value="<?= e($token ?? '') ?>">

    <label for="password">Nueva contraseña</label>
    <input id="password" type="password" name="password" required>

    <label for="password_confirmation" style="margin-top:14px;">Confirmar contraseña</label>
    <input id="password_confirmation" type="password" name="password_confirmation" required>

    <div style="margin-top:16px; display:flex; gap:10px;">
        <button type="submit">Actualizar contraseña</button>
<a class="btn-secondary btn" href="<?= app_url('login') ?>">Cancelar</a>    </div>
</form>
